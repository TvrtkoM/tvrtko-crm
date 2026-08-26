<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    Euro,
    FileText,
    Handshake,
    Plus,
    Send,
    Users,
} from '@lucide/vue';
import {
    board as companiesBoard,
    create as createCompany,
} from '@/actions/App/Http/Controllers/CompanyController';
import {
    board as contactsBoard,
    create as createContact,
} from '@/actions/App/Http/Controllers/ContactController';
import {
    board as dealsBoard,
    create as createDeal,
    show as showDeal,
} from '@/actions/App/Http/Controllers/DealController';
import {
    board as offersBoard,
    create as createOffer,
    show as showOffer,
} from '@/actions/App/Http/Controllers/OfferController';
import PipelineChart from '@/components/PipelineChart.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { Deal, KanbanColumn, Offer } from '@/types';

const { stats } = defineProps<{
    stats: {
        companies: number;
        contacts: number;
        openDeals: number;
        openPipelineValue: number;
        offersAwaitingResponse: number;
    };
    recentDeals: Deal[];
    recentOffers: Offer[];
    dealStatuses: KanbanColumn[];
    offerStatuses: KanbanColumn[];
    pipeline: {
        status: string;
        label: string;
        color: string;
        count: number;
        value: number;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const kpis = [
    {
        label: 'Companies',
        value: () => String(stats.companies),
        icon: Building2,
        href: companiesBoard(),
    },
    {
        label: 'Contacts',
        value: () => String(stats.contacts),
        icon: Users,
        href: contactsBoard(),
    },
    {
        label: 'Open deals',
        value: () => String(stats.openDeals),
        icon: Handshake,
        href: dealsBoard(),
    },
    {
        label: 'Open pipeline',
        value: () => formatCurrency(stats.openPipelineValue),
        icon: Euro,
        href: dealsBoard(),
    },
    {
        label: 'Offers sent',
        value: () => String(stats.offersAwaitingResponse),
        icon: Send,
        href: offersBoard(),
    },
];

const quickCreates = [
    { label: 'New company', href: createCompany() },
    { label: 'New contact', href: createContact() },
    { label: 'New deal', href: createDeal() },
    { label: 'New offer', href: createOffer() },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold tracking-tight">Dashboard</h1>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    v-for="quickCreate in quickCreates"
                    :key="quickCreate.label"
                    :href="quickCreate.href"
                    :class="
                        cn(buttonVariants({ variant: 'outline', size: 'sm' }))
                    "
                >
                    <Plus class="size-4" />
                    {{ quickCreate.label }}
                </Link>
            </div>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <Link
                v-for="kpi in kpis"
                :key="kpi.label"
                :href="kpi.href"
                class="rounded-xl transition-shadow hover:shadow-md"
            >
                <Card class="h-full gap-2 py-4">
                    <CardHeader class="px-4">
                        <CardTitle
                            class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <component :is="kpi.icon" class="size-4" />
                            {{ kpi.label }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-4">
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ kpi.value() }}
                        </p>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <PipelineChart :pipeline="pipeline" />

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Handshake class="size-4 text-muted-foreground" />
                        Recent deals
                    </CardTitle>

                    <Link
                        :href="dealsBoard()"
                        class="text-sm text-muted-foreground hover:text-foreground hover:underline"
                    >
                        View all
                    </Link>
                </CardHeader>

                <CardContent>
                    <p
                        v-if="recentDeals.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No deals yet.
                    </p>

                    <ul v-else class="flex flex-col divide-y">
                        <li
                            v-for="deal in recentDeals"
                            :key="deal.id"
                            class="flex items-center justify-between gap-3 py-2 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="showDeal(deal.id)"
                                    class="block truncate text-sm font-medium hover:underline"
                                >
                                    {{ deal.title }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ deal.company?.name ?? 'No company' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-sm font-medium tabular-nums">
                                    {{ formatCurrency(deal.value) }}
                                </span>
                                <StatusBadge
                                    :status="deal.status"
                                    :options="dealStatuses"
                                />
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileText class="size-4 text-muted-foreground" />
                        Recent offers
                    </CardTitle>

                    <Link
                        :href="offersBoard()"
                        class="text-sm text-muted-foreground hover:text-foreground hover:underline"
                    >
                        View all
                    </Link>
                </CardHeader>

                <CardContent>
                    <p
                        v-if="recentOffers.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No offers yet.
                    </p>

                    <ul v-else class="flex flex-col divide-y">
                        <li
                            v-for="offer in recentOffers"
                            :key="offer.id"
                            class="flex items-center justify-between gap-3 py-2 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="showOffer(offer.id)"
                                    class="block truncate text-sm font-medium hover:underline"
                                >
                                    {{ offer.offer_number }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        offer.deal?.company?.name ??
                                        offer.deal?.title ??
                                        'No deal'
                                    }}
                                    ·
                                    {{ formatDate(offer.issue_date) }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-sm font-medium tabular-nums">
                                    {{ formatCurrency(offer.total) }}
                                </span>
                                <StatusBadge
                                    :status="offer.status"
                                    :options="offerStatuses"
                                />
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
