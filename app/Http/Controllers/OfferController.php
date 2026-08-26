<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Http\Controllers\Concerns\BuildsIndexQueries;
use App\Http\Controllers\Concerns\ReordersKanbanCards;
use App\Http\Requests\StoreOfferRequest;
use App\Http\Requests\UpdateOfferRequest;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\OfferItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OfferController extends Controller
{
    use BuildsIndexQueries;
    use ReordersKanbanCards;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->indexFilters(
            $request,
            OfferStatus::class,
            sortable: ['offer_number', 'total', 'status', 'issue_date'],
            defaultSort: 'issue_date',
        );

        $offers = Offer::query()
            ->with(['deal.company', 'items'])
            ->when($filters['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('offer_number', "%{$search}%")
                    ->orWhereLike('title', "%{$search}%")
                    ->orWhereHas('deal', fn (Builder $deal) => $deal
                        ->whereLike('title', "%{$search}%")
                        ->orWhereHas('company', fn (Builder $company) => $company->whereLike('name', "%{$search}%")))
            ))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status));

        $offers = $filters['sort'] === 'total'
            ? $offers->orderBy($this->totalSubquery(), $filters['dir'])
            : $offers->orderBy($filters['sort'], $filters['dir']);

        return Inertia::render('Offer/Index', [
            'offers' => $offers
                ->orderBy('id', 'desc')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => OfferStatus::options(),
        ]);
    }

    /**
     * Display the Kanban board for the resource.
     */
    public function board(): Response
    {
        $offers = Offer::query()
            ->with(['deal.company', 'items'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Inertia::render('Offer/Board', [
            'offers' => $offers->groupBy(fn (Offer $offer): string => $offer->status->value),
            'statuses' => OfferStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Honors an optional `?deal=` query param so the "+ Offer" shortcut from a Deal card can
     * pre-fill and lock the deal picker.
     */
    public function create(Request $request): Response
    {
        $deal = $request->filled('deal')
            ? Deal::with('company')->findOrFail($request->integer('deal'))
            : null;

        return Inertia::render('Offer/Create', [
            'statuses' => OfferStatus::options(),
            'defaultStatus' => ($request->enum('status', OfferStatus::class) ?? OfferStatus::cases()[0])->value,
            'deal' => $deal,
            'deals' => Deal::query()->with('company')->orderBy('title')->get(['id', 'title', 'company_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfferRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $offer = Offer::create($validated);

        $this->syncItems($offer, $items);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer created.')]);

        return to_route('offers.show', $offer);
    }

    /**
     * Display the specified resource.
     */
    public function show(Offer $offer): Response
    {
        $offer->load([
            'deal.company',
            'deal.contact',
            'items' => fn (HasMany $items) => $items->orderBy('position'),
        ]);

        return Inertia::render('Offer/Show', [
            'offer' => $offer,
            'statuses' => OfferStatus::options(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Offer $offer): Response
    {
        $offer->load(['deal.company', 'items']);

        return Inertia::render('Offer/Edit', [
            'offer' => $offer,
            'statuses' => OfferStatus::options(),
            'deals' => Deal::query()->with('company')->orderBy('title')->get(['id', 'title', 'company_id']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfferRequest $request, Offer $offer): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $offer->update($validated);

        $this->syncItems($offer, $items);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer updated.')]);

        return to_route('offers.show', $offer);
    }

    /**
     * Stream the offer as a downloadable PDF.
     */
    public function pdf(Offer $offer): HttpResponse
    {
        $offer->load(['deal.company', 'deal.contact', 'items']);

        return Pdf::loadView('pdf.offer', ['offer' => $offer])
            ->download($offer->offer_number.'.pdf');
    }

    /**
     * Update the status of the specified resource (Kanban drag).
     */
    public function updateStatus(Request $request, Offer $offer): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OfferStatus::class)],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->moveCardToPosition($offer, $validated['status'], $validated['position'] ?? 0);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Offer $offer): RedirectResponse
    {
        $offer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer deleted.')]);

        return to_route('offers.board');
    }

    /**
     * Correlated subquery mirroring the `total` accessor, so the list view can sort by a
     * value that is computed rather than stored. Portable across SQLite and Postgres —
     * the `100.0` literal matters: SQLite stores `decimal` columns with NUMERIC affinity,
     * so dividing by an integer `100` would truncate the rate to zero.
     *
     * @return Builder<OfferItem>
     */
    private function totalSubquery(): Builder
    {
        return OfferItem::query()
            ->selectRaw('coalesce(sum(offer_items.quantity * offer_items.unit_price), 0) * (1 + offers.tax_rate / 100.0)')
            ->whereColumn('offer_items.offer_id', 'offers.id');
    }

    /**
     * Replace the offer's line items from the `items[]` payload, preserving row order.
     *
     * @param  array<int, array{description: string, quantity: float|string, unit_price: float|string}>  $items
     */
    private function syncItems(Offer $offer, array $items): void
    {
        $offer->items()->delete();

        foreach ($items as $position => $item) {
            $offer->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'position' => $position,
            ]);
        }
    }
}
