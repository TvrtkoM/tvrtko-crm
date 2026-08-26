<?php

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;

test('contact casts status to the enum', function () {
    $contact = Contact::factory()->create(['status' => ContactStatus::Qualified]);

    expect($contact->status)->toBe(ContactStatus::Qualified);
});

test('contact belongs to a company', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->for($company)->create();

    expect($contact->company->is($company))->toBeTrue();
});

test('contact has many deals as the primary contact', function () {
    $contact = Contact::factory()->create();
    $deal = Deal::factory()->for($contact)->create();

    expect($contact->deals)->toHaveCount(1)
        ->and($contact->deals->first()->is($deal))->toBeTrue();
});
