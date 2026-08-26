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
 * The per-record show pages: each renders its own component with the record,
 * the enum options its badges need, and the inline related lists the page shows.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('every show page renders its component with the record and its status options', function (
    string $entity,
    string $component,
    string $prop,
    string $enum,
) {
    $record = match ($entity) {
        'companies' => Company::factory()->create(),
        'contacts' => Contact::factory()->create(),
        'deals' => Deal::factory()->create(),
        'offers' => Offer::factory()->create(),
    };

    $response = $this->get(route($entity.'.show', $record));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component($component)
        ->where($prop.'.id', $record->id)
        ->where('statuses', $enum::options())
        ->etc()
    );
})->with([
    'companies' => ['companies', 'Company/Show', 'company', CompanyStatus::class],
    'contacts' => ['contacts', 'Contact/Show', 'contact', ContactStatus::class],
    'deals' => ['deals', 'Deal/Show', 'deal', DealStage::class],
    'offers' => ['offers', 'Offer/Show', 'offer', OfferStatus::class],
]);

test('the company show page carries its contacts, its deals and the pipeline value', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Customer]);
    $contact = Contact::factory()->for($company)->create(['first_name' => 'Ana']);
    Deal::factory()->for($company)->for($contact)->create(['value' => 12000.50]);
    Deal::factory()->for($company)->create(['value' => 7500]);

    // Belongs to another company — must not leak into either list.
    Contact::factory()->create();
    Deal::factory()->create(['value' => 999999]);

    $this->get(route('companies.show', $company))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company/Show')
            ->has('company.contacts', 1)
            ->where('company.contacts.0.first_name', 'Ana')
            ->has('company.deals', 2)
            ->where('company.deals.0.contact.first_name', 'Ana')
            ->where('pipelineValue', 19500.5)
            ->where('contactStatuses', ContactStatus::options())
            ->where('dealStatuses', DealStage::options())
            ->etc()
        );
});

test('the contact show page carries its company and only the deals it leads', function () {
    $company = Company::factory()->create(['name' => 'Northwind Trading']);
    $contact = Contact::factory()->for($company)->create();

    Deal::factory()->for($company)->for($contact)->create(['title' => 'Warehouse rollout']);
    Deal::factory()->for($company)->create(['title' => 'Unrelated deal']);

    $this->get(route('contacts.show', $contact))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contact/Show')
            ->where('contact.company.name', 'Northwind Trading')
            ->has('contact.deals', 1)
            ->where('contact.deals.0.title', 'Warehouse rollout')
            ->where('contact.deals.0.company.name', 'Northwind Trading')
            ->where('dealStatuses', DealStage::options())
            ->etc()
        );
});

test('the deal show page carries company, contact and its offers with totals', function () {
    $company = Company::factory()->create(['name' => 'Northwind Trading']);
    $contact = Contact::factory()->for($company)->create(['first_name' => 'Ana']);
    $deal = Deal::factory()->for($company)->for($contact)->create();

    $offer = Offer::factory()
        ->for($deal)
        ->has(OfferItem::factory()->state(['quantity' => 2, 'unit_price' => 100]), 'items')
        ->create(['tax_rate' => 25]);

    Offer::factory()->create();

    $this->get(route('deals.show', $deal))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Deal/Show')
            ->where('deal.company.name', 'Northwind Trading')
            ->where('deal.contact.first_name', 'Ana')
            ->has('deal.offers', 1)
            ->where('deal.offers.0.id', $offer->id)
            ->where('deal.offers.0.total', 250)
            ->where('offerStatuses', OfferStatus::options())
            ->etc()
        );
});

test('the offer show page carries the deal chain, ordered line items and computed totals', function () {
    $company = Company::factory()->create(['name' => 'Northwind Trading']);
    $contact = Contact::factory()->for($company)->create(['first_name' => 'Ana']);
    $deal = Deal::factory()->for($company)->for($contact)->create(['title' => 'Warehouse rollout']);

    $offer = Offer::factory()
        ->for($deal)
        ->has(
            OfferItem::factory()->count(2)->sequence(
                ['description' => 'Second row', 'quantity' => 1, 'unit_price' => 100, 'position' => 1],
                ['description' => 'First row', 'quantity' => 3, 'unit_price' => 100, 'position' => 0],
            ),
            'items',
        )
        ->create(['tax_rate' => 25]);

    $this->get(route('offers.show', $offer))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Offer/Show')
            ->where('offer.deal.title', 'Warehouse rollout')
            ->where('offer.deal.company.name', 'Northwind Trading')
            ->where('offer.deal.contact.first_name', 'Ana')
            ->has('offer.items', 2)
            ->where('offer.items.0.description', 'First row')
            ->where('offer.items.0.line_total', 300)
            ->where('offer.subtotal', 400)
            ->where('offer.tax_amount', 100)
            ->where('offer.total', 500)
            ->etc()
        );
});

test('deleting from the show page redirects to the board with a success toast', function (string $entity) {
    $record = match ($entity) {
        'companies' => Company::factory()->create(),
        'contacts' => Contact::factory()->create(),
        'deals' => Deal::factory()->create(),
        'offers' => Offer::factory()->create(),
    };

    $response = $this->from(route($entity.'.show', $record))
        ->delete(route($entity.'.destroy', $record));

    $response->assertRedirect(route($entity.'.board'));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($record->newQuery()->find($record->id))->toBeNull();
})->with(['companies', 'contacts', 'deals', 'offers']);
