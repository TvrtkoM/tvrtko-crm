<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;

test('company casts status to the enum', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Customer]);

    expect($company->status)->toBe(CompanyStatus::Customer);
});

test('company has many contacts and deals', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->for($company)->create();
    $deal = Deal::factory()->for($company)->create();

    expect($company->contacts)->toHaveCount(1)
        ->and($company->contacts->first()->is($contact))->toBeTrue()
        ->and($company->deals)->toHaveCount(1)
        ->and($company->deals->first()->is($deal))->toBeTrue();
});

test('deleting a company nulls its contacts company_id', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->for($company)->create();

    $company->delete();

    expect($contact->fresh()->company_id)->toBeNull();
});

test('deleting a company cascades to its deals', function () {
    $company = Company::factory()->create();
    $deal = Deal::factory()->for($company)->create();

    $company->delete();

    expect(Deal::query()->find($deal->id))->toBeNull();
});
