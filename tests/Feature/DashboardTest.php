<?php

use App\Enums\DealStage;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Dashboard')->etc());
});

test('the KPI cards match the records in the database', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create();
    Contact::factory()->count(3)->for($company)->create();

    $open = Deal::factory()->for($company)->create(['status' => DealStage::Qualification, 'value' => 1000]);
    Deal::factory()->for($company)->create(['status' => DealStage::Negotiation, 'value' => 2500.50]);
    // Terminal stages: neither open, nor part of the open pipeline value.
    Deal::factory()->for($company)->create(['status' => DealStage::Won, 'value' => 90000]);
    Deal::factory()->for($company)->create(['status' => DealStage::Lost, 'value' => 80000]);

    Offer::factory()->count(2)->sent()->for($open)->create();
    Offer::factory()->draft()->for($open)->create();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.companies', 1)
            ->where('stats.contacts', 3)
            ->where('stats.openDeals', 2)
            ->where('stats.openPipelineValue', 3500.5)
            ->where('stats.offersAwaitingResponse', 2)
            ->etc()
        );
});

test('recent activity lists the five newest deals and offers, newest first', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['name' => 'Northwind Trading']);
    $deals = collect(range(1, 6))->map(fn (int $index) => Deal::factory()->for($company)->create([
        'title' => "Deal {$index}",
        'created_at' => now()->subDays(10 - $index),
    ]));

    $newestDeal = $deals->last();

    $offer = Offer::factory()
        ->for($newestDeal)
        ->has(OfferItem::factory()->state(['quantity' => 2, 'unit_price' => 100]), 'items')
        ->create(['status' => OfferStatus::Sent, 'tax_rate' => 25]);

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentDeals', 5)
            ->where('recentDeals.0.title', 'Deal 6')
            ->where('recentDeals.0.company.name', 'Northwind Trading')
            ->has('recentOffers', 1)
            ->where('recentOffers.0.id', $offer->id)
            ->where('recentOffers.0.total', 250)
            ->where('recentOffers.0.deal.company.name', 'Northwind Trading')
            ->where('dealStatuses', DealStage::options())
            ->where('offerStatuses', OfferStatus::options())
            ->etc()
        );
});
