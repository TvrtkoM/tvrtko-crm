<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Columns3, Table2 } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';

type Props = {
    boardHref: NonNullable<InertiaLinkProps['href']>;
    listHref: NonNullable<InertiaLinkProps['href']>;
};

const { boardHref, listHref } = defineProps<Props>();

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
    </div>
</template>
