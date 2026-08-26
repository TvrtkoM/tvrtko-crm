<?php

use App\Enums\CompanyStatus;
use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login on every deal route', function () {
    $deal = Deal::factory()->create();

    $this->get(route('deals.index'))->assertRedirect(route('login'));
    $this->get(route('deals.board'))->assertRedirect(route('login'));
    $this->get(route('deals.create'))->assertRedirect(route('login'));
    $this->post(route('deals.store'))->assertRedirect(route('login'));
    $this->get(route('deals.show', $deal))->assertRedirect(route('login'));
    $this->get(route('deals.edit', $deal))->assertRedirect(route('login'));
    $this->put(route('deals.update', $deal))->assertRedirect(route('login'));
    $this->patch(route('deals.status', $deal))->assertRedirect(route('login'));
    $this->delete(route('deals.destroy', $deal))->assertRedirect(route('login'));
});

test('store rejects invalid input', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('deals.store'), [
        'title' => '',
        'company_id' => 999,
        'status' => 'NotARealStatus',
    ]);

    $response->assertInvalid(['title', 'company_id', 'status']);
    expect(Deal::count())->toBe(0);
});

test('store persists a deal and redirects with a flash toast', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create();

    $response = $this->post(route('deals.store'), [
        'company_id' => $company->id,
        'title' => 'New Website',
        'value' => 5000,
        'status' => DealStage::Proposal->value,
    ]);

    $deal = Deal::sole();
    $response->assertRedirect(route('deals.show', $deal));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($deal->title)->toBe('New Website');
    expect($deal->status)->toBe(DealStage::Proposal);
});

test('update rejects invalid input', function () {
    $this->actingAs(User::factory()->create());
    $deal = Deal::factory()->create();

    $response = $this->put(route('deals.update', $deal), [
        'title' => '',
        'company_id' => $deal->company_id,
        'status' => DealStage::Qualification->value,
    ]);

    $response->assertInvalid(['title']);
});

test('update persists changes and redirects with a flash toast', function () {
    $this->actingAs(User::factory()->create());
    $deal = Deal::factory()->create(['title' => 'Old Title']);

    $response = $this->put(route('deals.update', $deal), [
        'company_id' => $deal->company_id,
        'title' => 'New Title',
        'status' => DealStage::Won->value,
    ]);

    $response->assertRedirect(route('deals.show', $deal));
    $response->assertInertiaFlash('toast.type', 'success');
    expect($deal->fresh()->title)->toBe('New Title');
    expect($deal->fresh()->status)->toBe(DealStage::Won);
});

test('updateStatus changes the status', function () {
    $this->actingAs(User::factory()->create());
    $deal = Deal::factory()->create(['status' => DealStage::Qualification]);

    $response = $this->patch(route('deals.status', $deal), [
        'status' => DealStage::Won->value,
    ]);

    $response->assertRedirect();
    expect($deal->fresh()->status)->toBe(DealStage::Won);
});

test('dragging a deal to Won promotes its company to Customer', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create(['status' => CompanyStatus::Prospect]);
    $deal = Deal::factory()->for($company)->create(['status' => DealStage::Negotiation]);

    $this->patch(route('deals.status', $deal), ['status' => DealStage::Won->value]);

    expect($company->fresh()->status)->toBe(CompanyStatus::Customer);
});

test('updating a deal to Won promotes its company to Customer', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create(['status' => CompanyStatus::Lead]);
    $deal = Deal::factory()->for($company)->create(['status' => DealStage::Proposal]);

    $this->put(route('deals.update', $deal), [
        'company_id' => $company->id,
        'title' => $deal->title,
        'status' => DealStage::Won->value,
    ]);

    expect($company->fresh()->status)->toBe(CompanyStatus::Customer);
});

test('storing a deal already Won promotes its company to Customer', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create(['status' => CompanyStatus::Prospect]);

    $this->post(route('deals.store'), [
        'company_id' => $company->id,
        'title' => 'Closed Won',
        'status' => DealStage::Won->value,
    ]);

    expect($company->fresh()->status)->toBe(CompanyStatus::Customer);
});

test('moving a deal to Lost leaves the company status unchanged', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::factory()->create(['status' => CompanyStatus::Prospect]);
    $deal = Deal::factory()->for($company)->create(['status' => DealStage::Negotiation]);

    $this->patch(route('deals.status', $deal), ['status' => DealStage::Lost->value]);

    expect($company->fresh()->status)->toBe(CompanyStatus::Prospect);
});

test('updateStatus rejects a value outside the enum', function () {
    $this->actingAs(User::factory()->create());
    $deal = Deal::factory()->create(['status' => DealStage::Qualification]);

    $response = $this->patch(route('deals.status', $deal), [
        'status' => 'NotARealStatus',
    ]);

    $response->assertInvalid(['status']);
    expect($deal->fresh()->status)->toBe(DealStage::Qualification);
});

test('board renders the correct Inertia component with grouped records and column options', function () {
    $this->actingAs(User::factory()->create());
    Deal::factory()->create(['status' => DealStage::Qualification]);
    Deal::factory()->create(['status' => DealStage::Won]);

    $response = $this->get(route('deals.board'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Deal/Board')
        ->has('deals.'.DealStage::Qualification->value, 1)
        ->has('deals.'.DealStage::Won->value, 1)
        ->has('statuses', 5)
    );
});

test('destroy deletes the deal and cascades its offers', function () {
    $this->actingAs(User::factory()->create());
    $deal = Deal::factory()->create();
    $offer = Offer::factory()->for($deal)->create();

    $response = $this->delete(route('deals.destroy', $deal));

    $response->assertRedirect(route('deals.board'));
    $response->assertInertiaFlash('toast.type', 'success');
    expect(Deal::find($deal->id))->toBeNull();
    expect(Offer::find($offer->id))->toBeNull();
});
