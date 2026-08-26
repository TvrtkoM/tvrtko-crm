<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Mail, Plus, Users } from '@lucide/vue';
import {
    board,
    create,
    show,
    updateStatus,
} from '@/actions/App/Http/Controllers/ContactController';
import KanbanBoard from '@/components/KanbanBoard.vue';
import { buttonVariants } from '@/components/ui/button';
import ViewToggle from '@/components/ViewToggle.vue';
import { fullName } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Contact, KanbanCards, KanbanColumn } from '@/types';

defineProps<{
    contacts: KanbanCards<Contact>;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contacts', href: board() }],
    },
});
</script>

<template>
    <Head title="Contacts · Board" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1
                class="flex items-center gap-2 text-xl font-semibold tracking-tight"
            >
                <Users class="size-5 text-muted-foreground" />
                Contacts
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <ViewToggle :board-href="board()" />

                <Link
                    :href="create()"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Plus class="size-4" />
                    New contact
                </Link>
            </div>
        </header>

        <KanbanBoard
            :columns="statuses"
            :cards="contacts"
            cards-prop="contacts"
            :status-action="(id: number) => updateStatus(id)"
            error-message="Could not move the contact. Please try again."
            :create-href="(status: string) => create({ query: { status } })"
            create-label="contact"
        >
            <template #card="{ card }">
                <Link
                    :href="show(card.id)"
                    data-kanban-ignore
                    class="line-clamp-2 text-sm font-medium hover:underline"
                >
                    {{ fullName(card) }}
                </Link>

                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{ card.job_title || 'No job title' }}
                </p>

                <div
                    class="mt-3 flex flex-col gap-1 text-xs text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-1 truncate">
                        <Building2 class="size-3.5 shrink-0" />
                        {{ card.company?.name ?? 'No company' }}
                    </span>
                    <span class="inline-flex items-center gap-1 truncate">
                        <Mail class="size-3.5 shrink-0" />
                        {{ card.email || '—' }}
                    </span>
                </div>
            </template>
        </KanbanBoard>
    </div>
</template>
