<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Enums\DealStage;
use App\Enums\OfferStatus;
use App\Http\Controllers\Concerns\BuildsIndexQueries;
use App\Http\Controllers\Concerns\ReordersKanbanCards;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
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
            DealStage::class,
            sortable: ['title', 'value', 'status', 'expected_close_date', 'created_at'],
            defaultSort: 'created_at',
        );

        $deals = Deal::query()
            ->with(['company', 'contact'])
            ->when($filters['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('title', "%{$search}%")
                    ->orWhereHas('company', fn (Builder $company) => $company->whereLike('name', "%{$search}%"))
                    ->orWhereHas('contact', fn (Builder $contact) => $contact
                        ->whereLike('first_name', "%{$search}%")
                        ->orWhereLike('last_name', "%{$search}%"))
            ))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($filters['sort'], $filters['dir'])
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Deal/Index', [
            'deals' => $deals,
            'filters' => $filters,
            'statuses' => DealStage::options(),
        ]);
    }

    /**
     * Display the Kanban board for the resource.
     */
    public function board(): Response
    {
        $deals = Deal::query()
            ->with(['company', 'contact'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Inertia::render('Deal/Board', [
            'deals' => $deals->groupBy(fn (Deal $deal): string => $deal->status->value),
            'statuses' => DealStage::options(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Deal/Create', [
            'statuses' => DealStage::options(),
            'defaultStatus' => $this->defaultStatus($request),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::query()->orderBy('first_name')->get(['id', 'company_id', 'first_name', 'last_name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDealRequest $request): RedirectResponse
    {
        $deal = Deal::create($request->validated());

        $this->promoteCompanyToCustomer($deal);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal created.')]);

        return to_route('deals.show', $deal);
    }

    /**
     * Display the specified resource.
     */
    public function show(Deal $deal): Response
    {
        $deal->load([
            'company',
            'contact',
            'offers' => fn (HasMany $offers) => $offers->with('items')->latest('updated_at'),
        ]);

        return Inertia::render('Deal/Show', [
            'deal' => $deal,
            'statuses' => DealStage::options(),
            'offerStatuses' => OfferStatus::options(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Deal $deal): Response
    {
        return Inertia::render('Deal/Edit', [
            'deal' => $deal,
            'statuses' => DealStage::options(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::query()->orderBy('first_name')->get(['id', 'company_id', 'first_name', 'last_name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDealRequest $request, Deal $deal): RedirectResponse
    {
        $deal->update($request->validated());

        $this->promoteCompanyToCustomer($deal);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal updated.')]);

        return to_route('deals.show', $deal);
    }

    /**
     * Update the status of the specified resource (Kanban drag).
     */
    public function updateStatus(Request $request, Deal $deal): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(DealStage::class)],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->moveCardToPosition($deal, $validated['status'], $validated['position'] ?? 0);

        $this->promoteCompanyToCustomer($deal);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deal $deal): RedirectResponse
    {
        $deal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal deleted.')]);

        return to_route('deals.board');
    }

    /**
     * Promote the deal's company to `Customer` once the deal is `Won`.
     *
     * Promote-only: a `Lost` deal never demotes, and an existing customer is
     * left untouched. Deliberately lives here (the app-facing write paths)
     * rather than a Deal model event, so seeding — which creates a `Won` deal
     * for every company — does not flip every seeded company to `Customer`.
     */
    private function promoteCompanyToCustomer(Deal $deal): void
    {
        if ($deal->status !== DealStage::Won) {
            return;
        }

        $company = $deal->company;

        if ($company !== null && $company->status !== CompanyStatus::Customer) {
            $company->update(['status' => CompanyStatus::Customer]);
        }
    }

    /**
     * Resolve the status a new deal defaults to, honoring the optional `?status=`
     * query param a Kanban column's "new" shortcut appends.
     */
    private function defaultStatus(Request $request): string
    {
        return ($request->enum('status', DealStage::class) ?? DealStage::cases()[0])->value;
    }
}
