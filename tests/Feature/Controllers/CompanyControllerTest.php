<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login on every company route', function () {
    $company = Company::factory()->create();

    $this->get(route('companies.index'))->assertRedirect(route('login'));
    $this->get(route('companies.board'))->assertRedirect(route('login'));
    $this->get(route('companies.create'))->assertRedirect(route('login'));
    $this->post(route('companies.store'))->assertRedirect(route('login'));
    $this->get(route('companies.show', $company))->assertRedirect(route('login'));
    $this->get(route('companies.edit', $company))->assertRedirect(route('login'));
    $this->put(route('companies.update', $company))->assertRedirect(route('login'));
    $this->patch(route('companies.status', $company))->assertRedirect(route('login'));
    $this->delete(route('companies.destroy', $company))->assertRedirect(route('login'));
});

test('store rejects invalid input', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('companies.store'), [
        'name' => '',
        'status' => 'NotARealStatus',
    ]);

    $response->assertInvalid(['name', 'status']);
    expect(Company::count())->toBe(0);
});

test('store persists a company and redirects with a flash toast', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('companies.store'), [
        'name' => 'Acme Inc',
        'email' => 'hello@acme.test',
        'status' => CompanyStatus::Prospect->value,
    ]);

    $company = Company::sole();
    $response->assertRedirect(route('companies.show', $company));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($company->name)->toBe('Acme Inc');
    expect($company->status)->toBe(CompanyStatus::Prospect);
});

test('update rejects invalid input', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();

    $response = $this->put(route('companies.update', $company), [
        'name' => '',
        'status' => CompanyStatus::Lead->value,
    ]);

    $response->assertInvalid(['name']);
});

test('update persists changes and redirects with a flash toast', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create(['name' => 'Old Name']);

    $response = $this->put(route('companies.update', $company), [
        'name' => 'New Name',
        'status' => CompanyStatus::Customer->value,
    ]);

    $response->assertRedirect(route('companies.show', $company));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($company->fresh()->name)->toBe('New Name');
    expect($company->fresh()->status)->toBe(CompanyStatus::Customer);
});

test('updateStatus changes the status', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

    $response = $this->patch(route('companies.status', $company), [
        'status' => CompanyStatus::Customer->value,
    ]);

    $response->assertRedirect();
    expect($company->fresh()->status)->toBe(CompanyStatus::Customer);
});

test('updateStatus rejects a value outside the enum', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

    $response = $this->patch(route('companies.status', $company), [
        'status' => 'NotARealStatus',
    ]);

    $response->assertInvalid(['status']);
    expect($company->fresh()->status)->toBe(CompanyStatus::Lead);
});

test('board renders the correct Inertia component with grouped records and column options', function () {
    $this->actingAs(User::factory()->create());
    Company::factory()->create(['status' => CompanyStatus::Lead]);
    Company::factory()->create(['status' => CompanyStatus::Customer]);

    $response = $this->get(route('companies.board'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Company/Board')
        ->has('companies.'.CompanyStatus::Lead->value, 1)
        ->has('companies.'.CompanyStatus::Customer->value, 1)
        ->has('statuses', 4)
    );
});

test('destroy deletes the company and nulls/cascades related records', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();
    $contact = Contact::factory()->for($company)->create();
    $deal = Deal::factory()->for($company)->create();

    $response = $this->delete(route('companies.destroy', $company));

    $response->assertRedirect(route('companies.board'));
    $response->assertInertiaFlash('toast.type', 'success');
    expect(Company::find($company->id))->toBeNull();
    expect($contact->fresh()->company_id)->toBeNull();
    expect(Deal::find($deal->id))->toBeNull();
});
