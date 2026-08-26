<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, CalendarDays, Handshake, Plus } from '@lucide/vue';
import {
    board,
    create,
    index,
    show,
    updateStatus,
} from '@/actions/App/Http/Controllers/DealController';
import { create as createOffer } from '@/actions/App/Http/Controllers/OfferController';
import KanbanBoard from '@/components/KanbanBoard.vue';
import { buttonVariants } from '@/components/ui/button';
import ViewToggle from '@/components/ViewToggle.vue';
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Deal, KanbanCards, KanbanColumn } from '@/types';

defineProps<{
    deals: KanbanCards<Deal>;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Deals', href: board() }],
    },
});
</script>

<template>
    <Head title="Deals · Board" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1
                class="flex items-center gap-2 text-xl font-semibold tracking-tight"
            >
                <Handshake class="size-5 text-muted-foreground" />
                Deals
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <ViewToggle :board-href="board()" :list-href="index()" />

                <Link
                    :href="create()"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Plus class="size-4" />
                    New deal
                </Link>
            </div>
        </header>

        <KanbanBoard
            :columns="statuses"
            :cards="deals"
            cards-prop="deals"
            :status-action="(id: number) => updateStatus(id)"
            error-message="Could not move the deal. Please try again."
            :create-href="(status: string) => create({ query: { status } })"
            create-label="deal"
        >
            <template #card="{ card }">
                <div class="flex items-start justify-between gap-2">
                    <Link
                        :href="show(card.id)"
                        data-kanban-ignore
                        class="line-clamp-2 text-sm font-medium hover:underline"
                    >
                        {{ card.title }}
                    </Link>

                    <Link
                        :href="createOffer({ query: { deal: card.id } })"
                        data-kanban-ignore
                        :class="
                            cn(
                                buttonVariants({
                                    variant: 'outline',
                                    size: 'sm',
                                }),
                                'h-7 shrink-0 px-2 text-xs',
                            )
                        "
                        :title="`New offer for ${card.title}`"
                    >
                        <Plus class="size-3.5" />
                        Offer
                    </Link>
                </div>

                <p
                    class="mt-1 inline-flex items-center gap-1 truncate text-xs text-muted-foreground"
                >
                    <Building2 class="size-3.5 shrink-0" />
                    {{ card.company?.name ?? 'No company' }}
                </p>

                <div
                    class="mt-3 flex items-center justify-between gap-2 text-xs"
                >
                    <span class="font-medium tabular-nums">
                        {{ formatCurrency(card.value) }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 text-muted-foreground"
                    >
                        <CalendarDays class="size-3.5" />
                        {{ formatDate(card.expected_close_date) }}
                    </span>
                </div>
            </template>
        </KanbanBoard>
    </div>
</template>
