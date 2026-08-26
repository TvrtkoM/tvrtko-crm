<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import SelectInput from '@/components/SelectInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { KanbanColumn } from '@/types';

type Props = {
    statuses: KanbanColumn[];
    isFiltered: boolean;
    placeholder?: string;
};

const { statuses, isFiltered, placeholder = 'Search…' } = defineProps<Props>();

const emit = defineEmits<{ reset: [] }>();

const search = defineModel<string>('search', { required: true });
const status = defineModel<string | null>('status', { required: true });
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative w-full sm:w-64">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                type="search"
                class="pl-9"
                :placeholder="placeholder"
                aria-label="Search"
            />
        </div>

        <div class="w-full sm:w-48">
            <SelectInput
                v-model="status"
                :options="statuses"
                nullable
                null-label="All statuses"
                placeholder="All statuses"
            />
        </div>

        <Button
            v-if="isFiltered"
            variant="ghost"
            size="sm"
            @click="emit('reset')"
        >
            <X class="size-4" />
            Reset
        </Button>
    </div>
</template>
