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
 * Smoke coverage for the four Kanban board pages: every board must render its
 * page component with one column per enum case (value/label/color, in order)
 * and with the card content each `Board.vue` reads off the grouped records.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('every board renders one column per enum case, in order', function (
    string $route,
    string $component,
    string $enum,
) {
    $response = $this->get(route($route));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component($component)
        ->has('statuses', count($enum::cases()))
        ->where('statuses', $enum::options())
        ->etc()
    );
})->with([
    'companies' => ['companies.board', 'Company/Board', CompanyStatus::class],
    'contacts' => ['contacts.board', 'Contact/Board', ContactStatus::class],
    'deals' => ['deals.board', 'Deal/Board', DealStage::class],
    'offers' => ['offers.board', 'Offer/Board', OfferStatus::class],
]);

test('the company board groups cards by status with the counts the card renders', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Customer]);
    Contact::factory()->count(2)->for($company)->create();
    Deal::factory()->for($company)->create();

    $this->get(route('companies.board'))->assertInertia(fn (Assert $page) => $page
        ->component('Company/Board')
        ->has('companies.'.CompanyStatus::Customer->value, 1)
        ->where('companies.'.CompanyStatus::Customer->value.'.0.id', $company->id)
        ->where('companies.'.CompanyStatus::Customer->value.'.0.contacts_count', 2)
        ->where('companies.'.CompanyStatus::Customer->value.'.0.deals_count', 1)
        ->etc()
    );
});

test('the contact board groups cards by status with the company the card renders', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->for($company)->create([
        'status' => ContactStatus::Qualified,
        'job_title' => 'Head of Procurement',
    ]);

    $this->get(route('contacts.board'))->assertInertia(fn (Assert $page) => $page
        ->component('Contact/Board')
        ->has('contacts.'.ContactStatus::Qualified->value, 1)
        ->where('contacts.'.ContactStatus::Qualified->value.'.0.id', $contact->id)
        ->where('contacts.'.ContactStatus::Qualified->value.'.0.job_title', 'Head of Procurement')
        ->where('contacts.'.ContactStatus::Qualified->value.'.0.company.name', $company->name)
        ->etc()
    );
});

test('the deal board groups cards by status with the company and value the card renders', function () {
    $company = Company::factory()->create();
    $deal = Deal::factory()->for($company)->create([
        'status' => DealStage::Negotiation,
        'value' => 4200,
    ]);

    $this->get(route('deals.board'))->assertInertia(fn (Assert $page) => $page
        ->component('Deal/Board')
        ->has('deals.'.DealStage::Negotiation->value, 1)
        ->where('deals.'.DealStage::Negotiation->value.'.0.id', $deal->id)
        ->where('deals.'.DealStage::Negotiation->value.'.0.value', '4200.00')
        ->where('deals.'.DealStage::Negotiation->value.'.0.company.name', $company->name)
        ->etc()
    );
});

test('the offer board groups cards by status with the deal and total the card renders', function () {
    $company = Company::factory()->create();
    $deal = Deal::factory()->for($company)->create();
    $offer = Offer::factory()->for($deal)->create([
        'status' => OfferStatus::Sent,
        'tax_rate' => 25,
    ]);
    $offer->items()->delete();
    OfferItem::factory()->for($offer)->create(['quantity' => 2, 'unit_price' => 100]);

    $this->get(route('offers.board'))->assertInertia(fn (Assert $page) => $page
        ->component('Offer/Board')
        ->has('offers.'.OfferStatus::Sent->value, 1)
        ->where('offers.'.OfferStatus::Sent->value.'.0.offer_number', $offer->offer_number)
        ->where('offers.'.OfferStatus::Sent->value.'.0.total', 250)
        ->where('offers.'.OfferStatus::Sent->value.'.0.deal.company.name', $company->name)
        ->etc()
    );
});

test('a drag persists the new status and the board reflects it after a reload', function () {
    $deal = Deal::factory()->create(['status' => DealStage::Qualification]);

    $this->patch(route('deals.status', $deal), ['status' => DealStage::Won->value])
        ->assertRedirect();

    $this->get(route('deals.board'))->assertInertia(fn (Assert $page) => $page
        ->missing('deals.'.DealStage::Qualification->value)
        ->has('deals.'.DealStage::Won->value, 1)
        ->etc()
    );
});

test('a rejected drag leaves the card in its original column', function () {
    $deal = Deal::factory()->create(['status' => DealStage::Qualification]);

    $this->patch(route('deals.status', $deal), ['status' => 'NotARealStage'])
        ->assertInvalid('status');

    $this->get(route('deals.board'))->assertInertia(fn (Assert $page) => $page
        ->has('deals.'.DealStage::Qualification->value, 1)
        ->etc()
    );
});
