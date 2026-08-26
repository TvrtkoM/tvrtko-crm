<script setup lang="ts">
import { computed } from 'vue';
import { statusColorClasses } from '@/lib/statusColor';
import { cn } from '@/lib/utils';
import type { KanbanColumn } from '@/types';

type Props = {
    status: string;
    /** The entity's enum `options()` payload, carrying labels and colors. */
    options: KanbanColumn[];
};

const { status, options } = defineProps<Props>();

const option = computed(() =>
    options.find((candidate) => candidate.value === status),
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                statusColorClasses(option?.color ?? 'slate').badge,
            )
        "
    >
        {{ option?.label ?? status }}
    </span>
</template>
