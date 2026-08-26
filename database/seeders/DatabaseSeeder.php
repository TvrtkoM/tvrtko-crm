<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'DemoUser',
            'email' => 'demo@example.com',
            'password' => Hash::make('DemoUser'),
            'email_verified_at' => now(),
        ]);

        // One company per status, plus a few extra for volume.
        $companies = collect(CompanyStatus::cases())
            ->map(fn (CompanyStatus $status): Company => Company::factory()->create(['status' => $status]))
            ->concat(Company::factory()->count(3)->create());

        $deals = collect();

        $companies->each(function (Company $company) use (&$deals): void {
            // One contact per status, so every company has a full spread of contacts.
            $contacts = collect(ContactStatus::cases())
                ->map(fn (ContactStatus $status): Contact => Contact::factory()->create([
                    'company_id' => $company->id,
                    'status' => $status,
                ]));

            // One deal per stage, so every company has a full spread of deals.
            foreach (DealStage::cases() as $stage) {
                $deals->push(Deal::factory()->create([
                    'company_id' => $company->id,
                    'contact_id' => $contacts->random()->id,
                    'status' => $stage,
                ]));
            }
        });

        // Attach offers to a handful of deals, cycling through every offer status so each
        // Kanban column has at least one card.
        $offerStatuses = OfferStatus::cases();

        $deals->shuffle()->take(max(count($offerStatuses), 8))
            ->values()
            ->each(function (Deal $deal, int $index) use ($offerStatuses): void {
                Offer::factory()->create([
                    'deal_id' => $deal->id,
                    'status' => $offerStatuses[$index % count($offerStatuses)],
                ]);
            });
    }
}
