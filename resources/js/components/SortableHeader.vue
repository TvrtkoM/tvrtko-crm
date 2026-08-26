<script setup lang="ts">
import { ArrowDown, ArrowUp, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import { TableHead } from '@/components/ui/table';
import { cn } from '@/lib/utils';

type Props = {
    /** Whitelisted `?sort` value this header issues. */
    column: string;
    /** Current sort state, straight off the `filters` prop. */
    sort: string;
    dir: 'asc' | 'desc';
    align?: 'left' | 'right';
};

const { column, sort, dir, align = 'left' } = defineProps<Props>();

const emit = defineEmits<{ sort: [column: string] }>();

const isActive = computed(() => sort === column);
const icon = computed(() => {
    if (!isActive.value) {
        return ChevronsUpDown;
    }

    return dir === 'asc' ? ArrowUp : ArrowDown;
});
</script>

<template>
    <TableHead
        :aria-sort="
            isActive ? (dir === 'asc' ? 'ascending' : 'descending') : 'none'
        "
    >
        <button
            type="button"
            :class="
                cn(
                    'inline-flex items-center gap-1 transition-colors hover:text-foreground',
                    align === 'right' && 'w-full justify-end',
                    isActive ? 'text-foreground' : 'text-muted-foreground',
                )
            "
            @click="emit('sort', column)"
        >
            <slot />
            <component :is="icon" class="size-3.5 shrink-0" />
        </button>
    </TableHead>
</template>
