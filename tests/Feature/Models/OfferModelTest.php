<?php

use App\Enums\OfferStatus;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\OfferItem;

test('offer casts status to the enum, belongs to a deal, and has many items', function () {
    $deal = Deal::factory()->create();
    $offer = Offer::factory()->for($deal)->create(['status' => OfferStatus::Sent]);

    expect($offer->status)->toBe(OfferStatus::Sent)
        ->and($offer->deal->is($deal))->toBeTrue()
        ->and($offer->items()->count())->toBeGreaterThan(0);
});

test('offer computes subtotal, tax amount, and total from its items', function () {
    $deal = Deal::factory()->create();

    $offer = Offer::create([
        'deal_id' => $deal->id,
        'tax_rate' => 20,
    ]);

    OfferItem::factory()->for($offer)->create(['quantity' => 2, 'unit_price' => 100]);
    OfferItem::factory()->for($offer)->create(['quantity' => 1, 'unit_price' => 50]);

    expect($offer->subtotal)->toEqual(250.0)
        ->and($offer->tax_amount)->toEqual(50.0)
        ->and($offer->total)->toEqual(300.0);
});

test('offer number is generated with the correct format and increments per year', function () {
    $deal = Deal::factory()->create();
    $year = now()->year;

    $first = Offer::factory()->for($deal)->create();
    $second = Offer::factory()->for($deal)->create();

    expect($first->offer_number)->toBe("OFF-{$year}-0001")
        ->and($second->offer_number)->toBe("OFF-{$year}-0002");
});

test('offer number sequence resets for a new calendar year', function () {
    $deal = Deal::factory()->create();
    $lastYear = now()->year - 1;

    Offer::factory()->for($deal)->create([
        'offer_number' => "OFF-{$lastYear}-0042",
    ]);

    $offer = Offer::factory()->for($deal)->create();

    expect($offer->offer_number)->toBe('OFF-'.now()->year.'-0001');
});
