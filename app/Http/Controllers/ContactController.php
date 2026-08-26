<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $contacts = Contact::query()
            ->with('company')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Contact/Index', [
            'contacts' => $contacts,
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
    public function create(): Response
    {
        return Inertia::render('Contact/Create', [
            'statuses' => ContactStatus::options(),
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
        $contact->load(['company', 'deals']);

        return Inertia::render('Contact/Show', [
            'contact' => $contact,
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
}
