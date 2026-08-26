<?php

namespace App\Http\Controllers;

use App\Enums\DealStage;
use App\Enums\OfferStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the CRM home: headline numbers plus the most recent activity.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'companies' => Company::query()->count(),
                'contacts' => Contact::query()->count(),
                'openDeals' => $this->openDeals()->count(),
                'openPipelineValue' => (float) $this->openDeals()->sum('value'),
                'offersAwaitingResponse' => Offer::query()->where('status', OfferStatus::Sent)->count(),
            ],
            'recentDeals' => Deal::query()
                ->with(['company', 'contact'])
                ->latest()
                ->take(5)
                ->get(),
            'recentOffers' => Offer::query()
                ->with(['deal.company', 'items'])
                ->latest()
                ->take(5)
                ->get(),
            'dealStatuses' => DealStage::options(),
            'offerStatuses' => OfferStatus::options(),
            'pipeline' => $this->pipeline(),
        ]);
    }

    /**
     * Deals still in play — everything that has not reached a terminal stage.
     *
     * @return Builder<Deal>
     */
    private function openDeals(): Builder
    {
        return Deal::query()->whereNotIn('status', [
            DealStage::Won->value,
            DealStage::Lost->value,
        ]);
    }

    /**
     * Total deal value and count per stage, one entry per DealStage (in enum
     * order) so empty stages still appear as zero bars on the dashboard chart.
     *
     * @return array<int, array{status: string, label: string, color: string, count: int, value: float}>
     */
    private function pipeline(): array
    {
        $dealsByStage = Deal::query()
            ->selectRaw('status, count(*) as count, coalesce(sum(value), 0) as value')
            ->groupBy('status')
            ->get()
            ->keyBy(fn (Deal $row): string => $row->status->value);

        return collect(DealStage::cases())->map(fn (DealStage $stage): array => [
            'status' => $stage->value,
            'label' => $stage->label(),
            'color' => $stage->color(),
            'count' => (int) ($dealsByStage[$stage->value]->count ?? 0),
            'value' => (float) ($dealsByStage[$stage->value]->value ?? 0),
        ])->all();
    }
}
