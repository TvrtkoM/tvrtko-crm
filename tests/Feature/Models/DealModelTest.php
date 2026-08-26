<?php

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\OfferItem;

test('deal casts status to the enum and value to decimal', function () {
    $deal = Deal::factory()->create(['status' => DealStage::Negotiation, 'value' => 1234.5]);

    expect($deal->status)->toBe(DealStage::Negotiation)
        ->and($deal->value)->toEqual('1234.50');
});

test('deal belongs to a company and an optional primary contact', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->create();
    $deal = Deal::factory()->create([
        'company_id' => $company->id,
        'contact_id' => $contact->id,
    ]);

    expect($deal->company->is($company))->toBeTrue()
        ->and($deal->contact->is($contact))->toBeTrue();
});

test('deal has many offers', function () {
    $deal = Deal::factory()->create();
    $offer = Offer::factory()->for($deal)->create();

    expect($deal->offers)->toHaveCount(1)
        ->and($deal->offers->first()->is($offer))->toBeTrue();
});

test('deleting a deal cascades to its offers and offer items', function () {
    $deal = Deal::factory()->create();
    $offer = Offer::factory()->for($deal)->create();
    $itemIds = $offer->items->pluck('id');

    expect($itemIds)->not->toBeEmpty();

    $deal->delete();

    expect(Offer::query()->find($offer->id))->toBeNull()
        ->and(OfferItem::query()->whereIn('id', $itemIds)->count())->toBe(0);
});
