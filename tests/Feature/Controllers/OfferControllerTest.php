<?php

use App\Enums\OfferStatus;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login on every offer route', function () {
    $offer = Offer::factory()->create();

    $this->get(route('offers.index'))->assertRedirect(route('login'));
    $this->get(route('offers.board'))->assertRedirect(route('login'));
    $this->get(route('offers.create'))->assertRedirect(route('login'));
    $this->post(route('offers.store'))->assertRedirect(route('login'));
    $this->get(route('offers.show', $offer))->assertRedirect(route('login'));
    $this->get(route('offers.edit', $offer))->assertRedirect(route('login'));
    $this->put(route('offers.update', $offer))->assertRedirect(route('login'));
    $this->patch(route('offers.status', $offer))->assertRedirect(route('login'));
    $this->delete(route('offers.destroy', $offer))->assertRedirect(route('login'));
});

test('create honors an optional deal query param and locks the deal', function () {
    $this->actingAs(User::factory()->create());
    $deal = Deal::factory()->create();

    $response = $this->get(route('offers.create', ['deal' => $deal->id]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Offer/Create')
        ->where('deal.id', $deal->id)
    );
});

test('create without a deal param leaves the deal picker empty', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('offers.create'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Offer/Create')
        ->where('deal', null)
    );
});

test('store rejects invalid input', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('offers.store'), [
        'deal_id' => 999,
        'status' => 'NotARealStatus',
        'items' => [],
    ]);

    $response->assertInvalid(['deal_id', 'status', 'items']);
    expect(Offer::count())->toBe(0);
});

test('store writes items and generates a unique offer_number', function () {
    $this->actingAs(User::factory()->create());
    $deal = Deal::factory()->create();

    $response = $this->post(route('offers.store'), [
        'deal_id' => $deal->id,
        'title' => 'Website Revamp',
        'status' => OfferStatus::Draft->value,
        'tax_rate' => 25,
        'items' => [
            ['description' => 'Design', 'quantity' => 1, 'unit_price' => 1000],
            ['description' => 'Development', 'quantity' => 2, 'unit_price' => 1500],
        ],
    ]);

    $offer = Offer::sole();
    $response->assertRedirect(route('offers.show', $offer));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($offer->items)->toHaveCount(2);
    expect($offer->offer_number)->toMatch('/^OFF-\d{4}-\d{4}$/');
    expect($offer->subtotal)->toBe(4000.0);
});

test('update replaces the items and redirects with a flash toast', function () {
    $this->actingAs(User::factory()->create());
    $offer = Offer::factory()->create();
    $offer->items()->create(['description' => 'Old item', 'quantity' => 1, 'unit_price' => 100, 'position' => 0]);

    $response = $this->put(route('offers.update', $offer), [
        'deal_id' => $offer->deal_id,
        'status' => OfferStatus::Sent->value,
        'items' => [
            ['description' => 'New item', 'quantity' => 2, 'unit_price' => 50],
        ],
    ]);

    $response->assertRedirect(route('offers.show', $offer));
    $response->assertInertiaFlash('toast.type', 'success');
    $offer->refresh();
    expect($offer->status)->toBe(OfferStatus::Sent);
    expect($offer->items)->toHaveCount(1);
    expect($offer->items->first()->description)->toBe('New item');
});

test('updateStatus changes the status', function () {
    $this->actingAs(User::factory()->create());
    $offer = Offer::factory()->create(['status' => OfferStatus::Draft]);

    $response = $this->patch(route('offers.status', $offer), [
        'status' => OfferStatus::Sent->value,
    ]);

    $response->assertRedirect();
    expect($offer->fresh()->status)->toBe(OfferStatus::Sent);
});

test('updateStatus rejects a value outside the enum', function () {
    $this->actingAs(User::factory()->create());
    $offer = Offer::factory()->create(['status' => OfferStatus::Draft]);

    $response = $this->patch(route('offers.status', $offer), [
        'status' => 'NotARealStatus',
    ]);

    $response->assertInvalid(['status']);
    expect($offer->fresh()->status)->toBe(OfferStatus::Draft);
});

test('board renders the correct Inertia component with grouped records and column options', function () {
    $this->actingAs(User::factory()->create());
    Offer::factory()->create(['status' => OfferStatus::Draft]);
    Offer::factory()->create(['status' => OfferStatus::Accepted]);

    $response = $this->get(route('offers.board'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Offer/Board')
        ->has('offers.'.OfferStatus::Draft->value, 1)
        ->has('offers.'.OfferStatus::Accepted->value, 1)
        ->has('statuses', 5)
    );
});

test('destroy deletes the offer and cascades its items', function () {
    $this->actingAs(User::factory()->create());
    $offer = Offer::factory()->create();
    $itemId = $offer->items->first()->id;

    $response = $this->delete(route('offers.destroy', $offer));

    $response->assertRedirect(route('offers.board'));
    $response->assertInertiaFlash('toast.type', 'success');
    expect(Offer::find($offer->id))->toBeNull();
    expect(OfferItem::find($itemId))->toBeNull();
});
