<?php

use App\Models\Offer;
use App\Models\OfferItem;

test('offer item belongs to an offer', function () {
    $offer = Offer::factory()->create();
    $item = OfferItem::factory()->for($offer)->create();

    expect($item->offer->is($offer))->toBeTrue();
});

test('offer item computes its line total', function () {
    $item = OfferItem::factory()->create(['quantity' => 3, 'unit_price' => 19.99]);

    expect($item->line_total)->toEqual(59.97);
});
