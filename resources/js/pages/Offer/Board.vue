<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, FileText, Handshake, Plus } from '@lucide/vue';
import {
    board,
    create,
    index,
    show,
    updateStatus,
} from '@/actions/App/Http/Controllers/OfferController';
import KanbanBoard from '@/components/KanbanBoard.vue';
import { buttonVariants } from '@/components/ui/button';
import ViewToggle from '@/components/ViewToggle.vue';
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { KanbanCards, KanbanColumn, Offer } from '@/types';

defineProps<{
    offers: KanbanCards<Offer>;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Offers', href: board() }],
    },
});

function dealLabel(offer: Offer): string {
    return (
        [offer.deal?.title, offer.deal?.company?.name]
            .filter(Boolean)
            .join(' · ') || 'No deal'
    );
}
</script>

<template>
    <Head title="Offers · Board" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1
                class="flex items-center gap-2 text-xl font-semibold tracking-tight"
            >
                <FileText class="size-5 text-muted-foreground" />
                Offers
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <ViewToggle :board-href="board()" :list-href="index()" />

                <Link
                    :href="create()"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Plus class="size-4" />
                    New offer
                </Link>
            </div>
        </header>

        <KanbanBoard
            :columns="statuses"
            :cards="offers"
            cards-prop="offers"
            :status-action="(id: number) => updateStatus(id)"
            error-message="Could not move the offer. Please try again."
            :create-href="(status: string) => create({ query: { status } })"
            create-label="offer"
        >
            <template #card="{ card }">
                <Link
                    :href="show(card.id)"
                    data-kanban-ignore
                    class="text-sm font-medium hover:underline"
                >
                    {{ card.offer_number }}
                </Link>

                <p
                    class="mt-1 flex items-start gap-1 text-xs text-muted-foreground"
                >
                    <Handshake class="mt-0.5 size-3.5 shrink-0" />
                    <span class="line-clamp-2 min-w-0 break-words">
                        {{ dealLabel(card) }}
                    </span>
                </p>

                <div
                    class="mt-3 flex items-center justify-between gap-2 text-xs"
                >
                    <span class="font-medium tabular-nums">
                        {{ formatCurrency(card.total) }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 text-muted-foreground"
                        :title="`Valid until ${formatDate(card.valid_until)}`"
                    >
                        <CalendarDays class="size-3.5" />
                        {{ formatDate(card.valid_until) }}
                    </span>
                </div>
            </template>
        </KanbanBoard>
    </div>
</template>
