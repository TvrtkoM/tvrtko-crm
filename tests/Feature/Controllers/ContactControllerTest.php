<?php

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login on every contact route', function () {
    $contact = Contact::factory()->create();

    $this->get(route('contacts.index'))->assertRedirect(route('login'));
    $this->get(route('contacts.board'))->assertRedirect(route('login'));
    $this->get(route('contacts.create'))->assertRedirect(route('login'));
    $this->post(route('contacts.store'))->assertRedirect(route('login'));
    $this->get(route('contacts.show', $contact))->assertRedirect(route('login'));
    $this->get(route('contacts.edit', $contact))->assertRedirect(route('login'));
    $this->put(route('contacts.update', $contact))->assertRedirect(route('login'));
    $this->patch(route('contacts.status', $contact))->assertRedirect(route('login'));
    $this->delete(route('contacts.destroy', $contact))->assertRedirect(route('login'));
});

test('store rejects invalid input', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('contacts.store'), [
        'first_name' => '',
        'status' => 'NotARealStatus',
    ]);

    $response->assertInvalid(['first_name', 'status']);
    expect(Contact::count())->toBe(0);
});

test('store persists a contact and redirects with a flash toast', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();

    $response = $this->post(route('contacts.store'), [
        'company_id' => $company->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'status' => ContactStatus::Contacted->value,
    ]);

    $contact = Contact::sole();
    $response->assertRedirect(route('contacts.show', $contact));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($contact->first_name)->toBe('Jane');
    expect($contact->status)->toBe(ContactStatus::Contacted);
});

test('update rejects invalid input', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();

    $response = $this->put(route('contacts.update', $contact), [
        'first_name' => '',
        'status' => ContactStatus::New->value,
    ]);

    $response->assertInvalid(['first_name']);
});

test('update persists changes and redirects with a flash toast', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create(['first_name' => 'Old']);

    $response = $this->put(route('contacts.update', $contact), [
        'first_name' => 'New',
        'status' => ContactStatus::Qualified->value,
    ]);

    $response->assertRedirect(route('contacts.show', $contact));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($contact->fresh()->first_name)->toBe('New');
    expect($contact->fresh()->status)->toBe(ContactStatus::Qualified);
});

test('updateStatus changes the status', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create(['status' => ContactStatus::New]);

    $response = $this->patch(route('contacts.status', $contact), [
        'status' => ContactStatus::Qualified->value,
    ]);

    $response->assertRedirect();
    expect($contact->fresh()->status)->toBe(ContactStatus::Qualified);
});

test('updateStatus rejects a value outside the enum', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create(['status' => ContactStatus::New]);

    $response = $this->patch(route('contacts.status', $contact), [
        'status' => 'NotARealStatus',
    ]);

    $response->assertInvalid(['status']);
    expect($contact->fresh()->status)->toBe(ContactStatus::New);
});

test('board renders the correct Inertia component with grouped records and column options', function () {
    $this->actingAs(User::factory()->create());
    Contact::factory()->create(['status' => ContactStatus::New]);
    Contact::factory()->create(['status' => ContactStatus::Qualified]);

    $response = $this->get(route('contacts.board'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Contact/Board')
        ->has('contacts.'.ContactStatus::New->value, 1)
        ->has('contacts.'.ContactStatus::Qualified->value, 1)
        ->has('statuses', 4)
    );
});

test('destroy deletes the contact and honors the deal null-on-delete rule', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create();
    $deal = Deal::factory()->create(['contact_id' => $contact->id]);

    $response = $this->delete(route('contacts.destroy', $contact));

    $response->assertRedirect(route('contacts.board'));
    $response->assertInertiaFlash('toast.type', 'success');
    expect(Contact::find($contact->id))->toBeNull();
    expect($deal->fresh()->contact_id)->toBeNull();
});
