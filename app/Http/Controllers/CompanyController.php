<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $companies = Company::query()
            ->withCount(['contacts', 'deals'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Company/Index', [
            'companies' => $companies,
        ]);
    }

    /**
     * Display the Kanban board for the resource.
     */
    public function board(): Response
    {
        $companies = Company::query()
            ->withCount(['contacts', 'deals'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Company/Board', [
            'companies' => $companies->groupBy(fn (Company $company): string => $company->status->value),
            'statuses' => CompanyStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Company/Create', [
            'statuses' => CompanyStatus::options(),
            'defaultStatus' => $this->defaultStatus($request),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = Company::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company created.')]);

        return to_route('companies.show', $company);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): Response
    {
        $company->load(['contacts', 'deals']);

        return Inertia::render('Company/Show', [
            'company' => $company,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company): Response
    {
        return Inertia::render('Company/Edit', [
            'company' => $company,
            'statuses' => CompanyStatus::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company updated.')]);

        return to_route('companies.show', $company);
    }

    /**
     * Update the status of the specified resource (Kanban drag).
     */
    public function updateStatus(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(CompanyStatus::class)],
        ]);

        $company->update($validated);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company deleted.')]);

        return to_route('companies.board');
    }

    /**
     * Resolve the status a new company defaults to, honoring the optional `?status=`
     * query param a Kanban column's "new" shortcut appends.
     */
    private function defaultStatus(Request $request): string
    {
        return ($request->enum('status', CompanyStatus::class) ?? CompanyStatus::cases()[0])->value;
    }
}
