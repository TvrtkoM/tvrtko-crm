<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Http\Controllers\Concerns\BuildsIndexQueries;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    use BuildsIndexQueries;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->indexFilters(
            $request,
            ContactStatus::class,
            sortable: ['name', 'company', 'status', 'created_at'],
            defaultSort: 'created_at',
        );

        $contacts = Contact::query()
            ->with('company')
            ->when($filters['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('first_name', "%{$search}%")
                    ->orWhereLike('last_name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%")
                    ->orWhereLike('job_title', "%{$search}%")
                    ->orWhereHas('company', fn (Builder $company) => $company->whereLike('name', "%{$search}%"))
            ))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status));

        $contacts = match ($filters['sort']) {
            'name' => $contacts
                ->orderBy('first_name', $filters['dir'])
                ->orderBy('last_name', $filters['dir']),
            'company' => $contacts->orderBy(
                Company::query()->select('name')->whereColumn('companies.id', 'contacts.company_id'),
                $filters['dir'],
            ),
            default => $contacts->orderBy($filters['sort'], $filters['dir']),
        };

        return Inertia::render('Contact/Index', [
            'contacts' => $contacts
                ->orderBy('id', 'desc')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => ContactStatus::options(),
        ]);
    }

    /**
     * Display the Kanban board for the resource.
     */
    public function board(): Response
    {
        $contacts = Contact::query()
            ->with('company')
            ->latest('updated_at')
            ->get();

        return Inertia::render('Contact/Board', [
            'contacts' => $contacts->groupBy(fn (Contact $contact): string => $contact->status->value),
            'statuses' => ContactStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Contact/Create', [
            'statuses' => ContactStatus::options(),
            'defaultStatus' => $this->defaultStatus($request),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $contact = Contact::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact created.')]);

        return to_route('contacts.show', $contact);
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact): Response
    {
        $contact->load([
            'company',
            'deals' => fn (HasMany $deals) => $deals->with('company')->latest('updated_at'),
        ]);

        return Inertia::render('Contact/Show', [
            'contact' => $contact,
            'statuses' => ContactStatus::options(),
            'dealStatuses' => DealStage::options(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact): Response
    {
        return Inertia::render('Contact/Edit', [
            'contact' => $contact,
            'statuses' => ContactStatus::options(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact updated.')]);

        return to_route('contacts.show', $contact);
    }

    /**
     * Update the status of the specified resource (Kanban drag).
     */
    public function updateStatus(Request $request, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ContactStatus::class)],
        ]);

        $contact->update($validated);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact deleted.')]);

        return to_route('contacts.board');
    }

    /**
     * Resolve the status a new contact defaults to, honoring the optional `?status=`
     * query param a Kanban column's "new" shortcut appends.
     */
    private function defaultStatus(Request $request): string
    {
        return ($request->enum('status', ContactStatus::class) ?? ContactStatus::cases()[0])->value;
    }
}
