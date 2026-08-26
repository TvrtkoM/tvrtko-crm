<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Handshake, Plus, Users } from '@lucide/vue';
import {
    board,
    create,
    index,
    show,
    updateStatus,
} from '@/actions/App/Http/Controllers/CompanyController';
import KanbanBoard from '@/components/KanbanBoard.vue';
import { buttonVariants } from '@/components/ui/button';
import ViewToggle from '@/components/ViewToggle.vue';
import { cn } from '@/lib/utils';
import type { Company, KanbanCards, KanbanColumn } from '@/types';

defineProps<{
    companies: KanbanCards<Company>;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Companies', href: board() }],
    },
});
</script>

<template>
    <Head title="Companies · Board" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1
                class="flex items-center gap-2 text-xl font-semibold tracking-tight"
            >
                <Building2 class="size-5 text-muted-foreground" />
                Companies
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <ViewToggle :board-href="board()" :list-href="index()" />

                <Link
                    :href="create()"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Plus class="size-4" />
                    New company
                </Link>
            </div>
        </header>

        <KanbanBoard
            :columns="statuses"
            :cards="companies"
            cards-prop="companies"
            :status-action="(id: number) => updateStatus(id)"
            error-message="Could not move the company. Please try again."
            :create-href="(status: string) => create({ query: { status } })"
            create-label="company"
        >
            <template #card="{ card }">
                <Link
                    :href="show(card.id)"
                    data-kanban-ignore
                    class="line-clamp-2 text-sm font-medium hover:underline"
                >
                    {{ card.name }}
                </Link>

                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{
                        [card.industry, card.city]
                            .filter(Boolean)
                            .join(' · ') || 'No industry or city'
                    }}
                </p>

                <div
                    class="mt-3 flex items-center gap-3 text-xs text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-1">
                        <Users class="size-3.5" />
                        {{ card.contacts_count ?? 0 }}
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <Handshake class="size-3.5" />
                        {{ card.deals_count ?? 0 }}
                    </span>
                </div>
            </template>
        </KanbanBoard>
    </div>
</template>
