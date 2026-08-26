<?php

use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the create form is rendered with the options it needs', function (
    string $route,
    string $component,
    string $firstStatus,
    int $statusCount,
) {
    $response = $this->get(route($route));

    $response->assertInertia(fn (Assert $page) => $page
        ->component($component)
        ->has('statuses', $statusCount)
        ->where('defaultStatus', $firstStatus)
        ->etc()
    );
})->with([
    'companies' => ['companies.create', 'Company/Create', CompanyStatus::Lead->value, 4],
    'contacts' => ['contacts.create', 'Contact/Create', ContactStatus::New->value, 4],
    'deals' => ['deals.create', 'Deal/Create', DealStage::Qualification->value, 5],
    'offers' => ['offers.create', 'Offer/Create', OfferStatus::Draft->value, 5],
]);

test('a kanban column preselects its own status through ?status', function (string $route, string $status) {
    $response = $this->get(route($route, ['status' => $status]));

    $response->assertInertia(fn (Assert $page) => $page->where('defaultStatus', $status)->etc());
})->with([
    'companies' => ['companies.create', CompanyStatus::Customer->value],
    'contacts' => ['contacts.create', ContactStatus::Qualified->value],
    'deals' => ['deals.create', DealStage::Negotiation->value],
    'offers' => ['offers.create', OfferStatus::Sent->value],
]);

test('an unknown ?status falls back to the first enum case', function () {
    $response = $this->get(route('deals.create', ['status' => 'NotARealStage']));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('defaultStatus', DealStage::Qualification->value)
        ->etc()
    );
});

test('the contact form is fed the company picker options', function () {
    $company = Company::factory()->create(['name' => 'Acme Inc']);

    $this->get(route('contacts.create'))->assertInertia(fn (Assert $page) => $page
        ->component('Contact/Create')
        ->has('companies', 1)
        ->where('companies.0.name', 'Acme Inc')
        ->where('companies.0.id', $company->id)
        ->etc()
    );
});

test('the deal form is fed the company and contact picker options', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->for($company)->create();

    $this->get(route('deals.create'))->assertInertia(fn (Assert $page) => $page
        ->component('Deal/Create')
        ->has('companies', 1)
        ->has('contacts', 1)
        ->where('contacts.0.id', $contact->id)
        ->where('contacts.0.company_id', $company->id)
        ->etc()
    );
});

test('the offer form is fed deal options carrying their company', function () {
    $deal = Deal::factory()->create();

    $this->get(route('offers.create'))->assertInertia(fn (Assert $page) => $page
        ->component('Offer/Create')
        ->where('deal', null)
        ->has('deals', 1)
        ->where('deals.0.id', $deal->id)
        ->where('deals.0.company.id', $deal->company_id)
        ->etc()
    );
});

test('the offer shortcut from a deal card locks the deal', function () {
    $deal = Deal::factory()->create();

    $this->get(route('offers.create', ['deal' => $deal->id]))->assertInertia(fn (Assert $page) => $page
        ->component('Offer/Create')
        ->where('deal.id', $deal->id)
        ->where('deal.title', $deal->title)
        ->has('deals')
        ->etc()
    );
});

test('the edit form is rendered with the record and its options', function (
    string $route,
    string $component,
    string $prop,
    int $statusCount,
) {
    $record = match ($prop) {
        'company' => Company::factory()->create(),
        'contact' => Contact::factory()->create(),
        'deal' => Deal::factory()->create(),
        'offer' => Offer::factory()->create(),
    };

    $this->get(route($route, $record))->assertInertia(fn (Assert $page) => $page
        ->component($component)
        ->where($prop.'.id', $record->id)
        ->has('statuses', $statusCount)
        ->etc()
    );
})->with([
    'companies' => ['companies.edit', 'Company/Edit', 'company', 4],
    'contacts' => ['contacts.edit', 'Contact/Edit', 'contact', 4],
    'deals' => ['deals.edit', 'Deal/Edit', 'deal', 5],
    'offers' => ['offers.edit', 'Offer/Edit', 'offer', 5],
]);

test('the offer edit form carries the existing line items', function () {
    $offer = Offer::factory()->create();
    $offer->items()->delete();
    $offer->items()->create(['description' => 'Design', 'quantity' => 2, 'unit_price' => 500, 'position' => 0]);

    $this->get(route('offers.edit', $offer))->assertInertia(fn (Assert $page) => $page
        ->component('Offer/Edit')
        ->has('offer.items', 1)
        ->where('offer.items.0.description', 'Design')
        ->where('offer.total', 1250)
        ->etc()
    );
});

test('a rejected submission returns to the form with errors and persists nothing', function (
    string $formRoute,
    string $storeRoute,
    array $payload,
    array $invalid,
    string $model,
) {
    $response = $this->from(route($formRoute))->post(route($storeRoute), $payload);

    $response->assertRedirect(route($formRoute));
    $response->assertInvalid($invalid);
    expect($model::count())->toBe(0);
})->with([
    'companies' => [
        'companies.create', 'companies.store',
        ['name' => '', 'status' => 'Nope'], ['name', 'status'], Company::class,
    ],
    'contacts' => [
        'contacts.create', 'contacts.store',
        ['first_name' => '', 'status' => 'Nope'], ['first_name', 'status'], Contact::class,
    ],
    'deals' => [
        'deals.create', 'deals.store',
        ['title' => '', 'company_id' => null, 'status' => 'Nope'], ['title', 'company_id', 'status'], Deal::class,
    ],
    'offers' => [
        'offers.create', 'offers.store',
        ['deal_id' => null, 'status' => 'Nope', 'items' => []], ['deal_id', 'status', 'items'], Offer::class,
    ],
]);

test('an offer line item with a blank description is rejected per row', function () {
    $deal = Deal::factory()->create();

    $response = $this->from(route('offers.create'))->post(route('offers.store'), [
        'deal_id' => $deal->id,
        'status' => OfferStatus::Draft->value,
        'tax_rate' => 25,
        'items' => [
            ['description' => 'Design', 'quantity' => 1, 'unit_price' => 100],
            ['description' => '', 'quantity' => 'many', 'unit_price' => 100],
        ],
    ]);

    $response->assertInvalid(['items.1.description', 'items.1.quantity']);
    expect(Offer::count())->toBe(0);
});

test('creating an offer stores the rows in form order and lands on the show page', function () {
    $deal = Deal::factory()->create();

    $response = $this->post(route('offers.store'), [
        'deal_id' => $deal->id,
        'title' => 'Phase 1',
        'status' => OfferStatus::Draft->value,
        'issue_date' => '2026-08-26',
        'valid_until' => '2026-09-26',
        'tax_rate' => 25,
        'items' => [
            ['description' => 'Design', 'quantity' => 2, 'unit_price' => 500],
            ['description' => 'Development', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]);

    $offer = Offer::sole();

    $response->assertRedirect(route('offers.show', $offer));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($offer->offer_number)->toMatch('/^OFF-\d{4}-\d{4}$/');
    expect($offer->items->pluck('description', 'position')->all())
        ->toBe([0 => 'Design', 1 => 'Development']);
    expect($offer->subtotal)->toBe(2000.0);
    expect($offer->tax_amount)->toBe(500.0);
    expect($offer->total)->toBe(2500.0);
});

test('editing a record redirects to its show page with a flash toast', function () {
    $company = Company::factory()->create(['name' => 'Before']);

    $response = $this->put(route('companies.update', $company), [
        'name' => 'After',
        'status' => CompanyStatus::Customer->value,
        'notes' => 'Renamed during the demo.',
    ]);

    $response->assertRedirect(route('companies.show', $company));
    $response->assertInertiaFlash('toast.type', 'success');
    $response->assertInertiaFlash('toast.message', 'Company updated.');
    expect($company->fresh()->name)->toBe('After');
});
