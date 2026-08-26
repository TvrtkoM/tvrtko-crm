<script setup lang="ts" generic="TCard extends Record<string, unknown>">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, useId, watch } from 'vue';
import type { DraggableEvent } from 'vue-draggable-plus';
import { VueDraggable } from 'vue-draggable-plus';
import { toast } from 'vue-sonner';
import { Skeleton } from '@/components/ui/skeleton';
import { statusColorClasses } from '@/lib/statusColor';
import type { KanbanCards, KanbanColumn } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type Props = {
    /** Every case of the entity's status enum, in order. */
    columns: KanbanColumn[];
    /** Records grouped by status value. `undefined` while a deferred prop loads. */
    cards?: KanbanCards<TCard>;
    /** Name of the Inertia page prop holding `cards` — the optimistic update target. */
    cardsProp: string;
    /** Wayfinder action for the entity's `PATCH …/status` route. */
    statusAction: (id: number) => RouteDefinition<'patch'>;
    /** Field holding the card's primary key. */
    idKey?: string;
    /** Field holding the card's status value. */
    statusKey?: string;
    /** Toast shown when persisting a drop fails. */
    errorMessage?: string;
    /** Renders a per-column "new" shortcut that pre-selects that column's status. */
    createHref?: (status: string) => NonNullable<InertiaLinkProps['href']>;
    /** Singular entity name used in the shortcut's tooltip, e.g. "company". */
    createLabel?: string;
};

const {
    columns,
    cards = undefined,
    cardsProp,
    statusAction,
    idKey = 'id',
    statusKey = 'status',
    errorMessage = 'Could not move the card. Please try again.',
    createHref = undefined,
    createLabel = 'card',
} = defineProps<Props>();

defineSlots<{
    card(props: { card: TCard }): unknown;
}>();

/**
 * Local, drag-mutable mirror of the grouped cards. `vue-draggable-plus` needs a
 * writable list per column, while the Inertia prop is the source of truth: every
 * prop change (including an optimistic update and its rollback) rebuilds it.
 */
const lists = ref<Record<string, TCard[]>>({});

const isLoading = computed(() => cards === undefined);
const group = `kanban-${useId()}`;

watch(
    () => cards,
    (value) => {
        lists.value = Object.fromEntries(
            columns.map((column) => [
                column.value,
                [...((value?.[column.value] ?? []) as TCard[])],
            ]),
        );
    },
    { immediate: true, deep: true },
);

function cardId(card: TCard): number {
    return card[idKey] as number;
}

/**
 * Persist a drop at the exact index it was released — whether the card moved
 * into another column (`@add`) or was reordered within its column (`@update`).
 * The board updates optimistically and Inertia rolls the props back on failure.
 */
function onCardDropped(
    column: KanbanColumn,
    event: DraggableEvent<TCard>,
): void {
    const card = event.data;

    if (!card) {
        return;
    }

    const id = cardId(card);
    const index = event.newDraggableIndex ?? 0;

    router
        .optimistic((props: Record<string, unknown>) => {
            const grouped = (props[cardsProp] ?? {}) as KanbanCards<TCard>;
            const next: KanbanCards<TCard> = Object.fromEntries(
                columns.map((candidate) => [
                    candidate.value,
                    (grouped[candidate.value] ?? []).filter(
                        (existing) => cardId(existing) !== id,
                    ),
                ]),
            );

            next[column.value].splice(index, 0, {
                ...card,
                [statusKey]: column.value,
            });

            return { [cardsProp]: next };
        })
        .patch(
            statusAction(id).url,
            { [statusKey]: column.value, position: index },
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => toast.error(errorMessage),
            },
        );
}
</script>

<template>
    <div class="flex min-h-0 flex-1 gap-4 overflow-x-auto pb-4">
        <section
            v-for="column in columns"
            :key="column.value"
            class="flex max-h-full w-72 shrink-0 flex-col gap-3 rounded-xl border bg-muted/40 p-3 dark:bg-muted/20"
        >
            <header class="flex items-center gap-2">
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :class="statusColorClasses(column.color).accent"
                    aria-hidden="true"
                />
                <h2 class="truncate text-sm font-semibold">
                    {{ column.label }}
                </h2>
                <span
                    class="ml-auto rounded-full px-2 py-0.5 text-xs font-medium tabular-nums"
                    :class="statusColorClasses(column.color).badge"
                >
                    {{ isLoading ? '–' : (lists[column.value]?.length ?? 0) }}
                </span>

                <Link
                    v-if="createHref"
                    :href="createHref(column.value)"
                    class="rounded-md p-1 text-muted-foreground transition-colors hover:bg-background hover:text-foreground"
                    :title="`New ${createLabel} in ${column.label}`"
                >
                    <Plus class="size-4" />
                    <span class="sr-only">
                        New {{ createLabel }} in {{ column.label }}
                    </span>
                </Link>
            </header>

            <div v-if="isLoading" class="flex flex-col gap-2">
                <Skeleton
                    v-for="n in 3"
                    :key="n"
                    class="h-24 w-full rounded-lg"
                />
            </div>

            <div v-else class="relative min-h-0 flex-1 overflow-y-auto">
                <p
                    v-if="!lists[column.value]?.length"
                    class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground"
                >
                    No cards
                </p>

                <VueDraggable
                    v-model="lists[column.value]"
                    class="relative flex h-full min-h-32 flex-col gap-2"
                    :group="group"
                    :animation="150"
                    filter="[data-kanban-ignore]"
                    :prevent-on-filter="false"
                    ghost-class="opacity-40"
                    drag-class="shadow-lg"
                    @add="
                        (event: DraggableEvent<TCard>) =>
                            onCardDropped(column, event)
                    "
                    @update="
                        (event: DraggableEvent<TCard>) =>
                            onCardDropped(column, event)
                    "
                >
                    <article
                        v-for="card in lists[column.value]"
                        :key="cardId(card)"
                        class="cursor-grab rounded-lg border bg-card p-3 text-card-foreground shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
                    >
                        <slot name="card" :card="card" />
                    </article>
                </VueDraggable>
            </div>
        </section>
    </div>
</template>
