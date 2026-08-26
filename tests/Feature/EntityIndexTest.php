<?php

use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * The per-entity list (table) views: every filter, sort and page is resolved
 * server-side from the query string, and the resolved state is echoed back so
 * the toolbar, sortable headers and pagination control can reflect it.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('every list view renders its component with a paginator, the filter state and column options', function (
    string $route,
    string $component,
    string $prop,
    string $enum,
) {
    $response = $this->get(route($route));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component($component)
        ->has($prop.'.data')
        ->has($prop.'.current_page')
        ->has($prop.'.last_page')
        ->has($prop.'.total')
        ->has($prop.'.links')
        ->has('filters.search')
        ->has('filters.status')
        ->has('filters.sort')
        ->has('filters.dir')
        ->where('statuses', $enum::options())
    );
})->with([
    'companies' => ['companies.index', 'Company/Index', 'companies', CompanyStatus::class],
    'contacts' => ['contacts.index', 'Contact/Index', 'contacts', ContactStatus::class],
    'deals' => ['deals.index', 'Deal/Index', 'deals', DealStage::class],
    'offers' => ['offers.index', 'Offer/Index', 'offers', OfferStatus::class],
]);

test('search narrows companies across name, email, industry and city', function (string $search) {
    Company::factory()->create([
        'name' => 'Northwind Trading',
        'email' => 'hello@northwind.test',
        'industry' => 'Logistics',
        'city' => 'Rijeka',
    ]);
    Company::factory()->create([
        'name' => 'Southport Supplies',
        'email' => 'info@southport.test',
        'industry' => 'Retail',
        'city' => 'Split',
    ]);

    $this->get(route('companies.index', ['search' => $search]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 1)
            ->where('companies.data.0.name', 'Northwind Trading')
            ->where('filters.search', $search)
            ->etc()
        );
})->with([
    'name' => ['northwind'],
    'email' => ['hello@northwind'],
    'industry' => ['Logistics'],
    'city' => ['Rijeka'],
]);

test('the status filter narrows companies to that status', function () {
    Company::factory()->count(2)->create(['status' => CompanyStatus::Customer]);
    Company::factory()->create(['status' => CompanyStatus::Lead]);

    $this->get(route('companies.index', ['status' => CompanyStatus::Customer->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 2)
            ->where('filters.status', CompanyStatus::Customer->value)
            ->etc()
        );
});

test('an unknown status is ignored rather than narrowing the list', function () {
    Company::factory()->count(3)->create();

    $this->get(route('companies.index', ['status' => 'NotARealStatus']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 3)
            ->where('filters.status', null)
            ->etc()
        );
});

test('companies sort by a whitelisted column in both directions', function () {
    Company::factory()->create(['name' => 'Zagreb Zinc']);
    Company::factory()->create(['name' => 'Adriatic Anchors']);

    $this->get(route('companies.index', ['sort' => 'name', 'dir' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('companies.data.0.name', 'Adriatic Anchors')
            ->where('filters.sort', 'name')
            ->where('filters.dir', 'asc')
            ->etc()
        );

    $this->get(route('companies.index', ['sort' => 'name', 'dir' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('companies.data.0.name', 'Zagreb Zinc')
            ->where('filters.dir', 'desc')
            ->etc()
        );
});

test('only whitelisted sort columns and directions are honored', function () {
    Company::factory()->create(['name' => 'Alpha']);
    Company::factory()->create(['name' => 'Beta']);

    $this->get(route('companies.index', ['sort' => 'notes', 'dir' => 'sideways']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'created_at')
            ->where('filters.dir', 'desc')
            ->etc()
        );
});

test('the list paginates fifteen records per page and keeps the filters on page two', function () {
    Company::factory()->count(20)->create(['status' => CompanyStatus::Lead]);
    Company::factory()->count(3)->create(['status' => CompanyStatus::Customer]);

    $this->get(route('companies.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 15)
            ->where('companies.current_page', 1)
            ->where('companies.last_page', 2)
            ->where('companies.total', 23)
            ->etc()
        );

    $this->get(route('companies.index', ['status' => CompanyStatus::Lead->value, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 5)
            ->where('companies.current_page', 2)
            ->where('companies.total', 20)
            ->etc()
        );
});

test('contacts search matches their company name and sort by company uses it', function () {
    $northwind = Company::factory()->create(['name' => 'Northwind Trading']);
    $adriatic = Company::factory()->create(['name' => 'Adriatic Anchors']);

    Contact::factory()->for($northwind)->create(['first_name' => 'Ana', 'last_name' => 'Anić']);
    Contact::factory()->for($adriatic)->create(['first_name' => 'Boris', 'last_name' => 'Barić']);

    $this->get(route('contacts.index', ['search' => 'Northwind']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts.data', 1)
            ->where('contacts.data.0.first_name', 'Ana')
            ->etc()
        );

    $this->get(route('contacts.index', ['sort' => 'company', 'dir' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contacts.data.0.company.name', 'Adriatic Anchors')
            ->where('contacts.data.1.company.name', 'Northwind Trading')
            ->etc()
        );
});

test('contacts sort by name across both name columns', function () {
    Contact::factory()->create(['first_name' => 'Ana', 'last_name' => 'Zubak']);
    Contact::factory()->create(['first_name' => 'Ana', 'last_name' => 'Anić']);

    $this->get(route('contacts.index', ['sort' => 'name', 'dir' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contacts.data.0.last_name', 'Anić')
            ->where('contacts.data.1.last_name', 'Zubak')
            ->etc()
        );
});

test('deals sort by value and can be searched through their company', function () {
    $company = Company::factory()->create(['name' => 'Northwind Trading']);
    Deal::factory()->for($company)->create(['title' => 'Warehouse rollout', 'value' => 50000]);
    Deal::factory()->create(['title' => 'Small retainer', 'value' => 1200]);

    $this->get(route('deals.index', ['sort' => 'value', 'dir' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('deals.data.0.title', 'Warehouse rollout')
            ->etc()
        );

    $this->get(route('deals.index', ['search' => 'northwind']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('deals.data', 1)
            ->where('deals.data.0.title', 'Warehouse rollout')
            ->etc()
        );
});

test('offers sort by their computed total, tax included', function () {
    $cheap = Offer::factory()
        ->has(OfferItem::factory()->state(['quantity' => 1, 'unit_price' => 50]), 'items')
        ->create(['tax_rate' => 0]);

    $middle = Offer::factory()
        ->has(OfferItem::factory()->state(['quantity' => 1, 'unit_price' => 100]), 'items')
        ->create(['tax_rate' => 0]);

    // Cheaper before tax, but the 25% rate pushes it past the 100 EUR offer.
    $taxed = Offer::factory()
        ->has(OfferItem::factory()->state(['quantity' => 2, 'unit_price' => 45]), 'items')
        ->create(['tax_rate' => 25]);

    expect([$cheap->total, $middle->total, $taxed->total])->toBe([50.0, 100.0, 112.5]);

    $this->get(route('offers.index', ['sort' => 'total', 'dir' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('offers.data.0.id', $taxed->id)
            ->where('offers.data.1.id', $middle->id)
            ->where('offers.data.2.id', $cheap->id)
            ->etc()
        );

    $this->get(route('offers.index', ['sort' => 'total', 'dir' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('offers.data.0.id', $cheap->id)
            ->etc()
        );
});

test('offers can be searched by offer number and filtered by status', function () {
    $sent = Offer::factory()->sent()->create();
    Offer::factory()->draft()->create();

    $this->get(route('offers.index', ['search' => $sent->offer_number]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $sent->id)
            ->etc()
        );

    $this->get(route('offers.index', ['status' => OfferStatus::Sent->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $sent->id)
            ->etc()
        );
});

test('deleting from the list redirects to the board with a success toast', function () {
    $company = Company::factory()->create();

    $response = $this->from(route('companies.index'))
        ->delete(route('companies.destroy', $company));

    $response->assertRedirect(route('companies.board'));
    $response->assertInertiaFlash('toast.type', 'success');
    expect(Company::find($company->id))->toBeNull();
});
