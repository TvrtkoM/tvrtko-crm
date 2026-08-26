<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Columns3, Table2 } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';

type Props = {
    boardHref: NonNullable<InertiaLinkProps['href']>;
    /** Omitted until the list views land — the "List" half renders disabled. */
    listHref?: NonNullable<InertiaLinkProps['href']>;
};

const { boardHref, listHref = undefined } = defineProps<Props>();

const { isCurrentUrl } = useCurrentUrl();

const itemClass =
    'inline-flex items-center gap-1.5 rounded-md px-3 py-1 text-sm font-medium transition-colors';
</script>

<template>
    <div
        class="inline-flex items-center gap-1 rounded-lg border bg-muted/50 p-1"
        role="group"
        aria-label="Switch view"
    >
        <Link
            :href="boardHref"
            :class="
                cn(
                    itemClass,
                    isCurrentUrl(boardHref)
                        ? 'bg-background shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                )
            "
            :aria-current="isCurrentUrl(boardHref) ? 'page' : undefined"
        >
            <Columns3 class="size-4" />
            Board
        </Link>

        <Link
            v-if="listHref"
            :href="listHref"
            :class="
                cn(
                    itemClass,
                    isCurrentUrl(listHref)
                        ? 'bg-background shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                )
            "
            :aria-current="isCurrentUrl(listHref) ? 'page' : undefined"
        >
            <Table2 class="size-4" />
            List
        </Link>

        <button
            v-else
            type="button"
            disabled
            :class="cn(itemClass, 'text-muted-foreground/60')"
            title="The list view lands in a later step"
        >
            <Table2 class="size-4" />
            List
        </button>
    </div>
</template>
