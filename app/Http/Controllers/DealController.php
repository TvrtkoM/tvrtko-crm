<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $deals = Deal::query()
            ->with(['company', 'contact'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Deal/Index', [
            'deals' => $deals,
        ]);
    }

    /**
     * Display the Kanban board for the resource.
     */
    public function board(): Response
    {
        $deals = Deal::query()
            ->with(['company', 'contact'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Deal/Board', [
            'deals' => $deals->groupBy(fn (Deal $deal): string => $deal->status->value),
            'statuses' => DealStage::options(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Deal/Create', [
            'statuses' => DealStage::options(),
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal created.')]);

        return to_route('deals.show', $deal);
    }

    /**
     * Display the specified resource.
     */
    public function show(Deal $deal): Response
    {
        $deal->load(['company', 'contact', 'offers']);

        return Inertia::render('Deal/Show', [
            'deal' => $deal,
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
        ]);

        $deal->update($validated);

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
}
