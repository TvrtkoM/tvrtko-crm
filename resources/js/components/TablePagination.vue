<script setup lang="ts">
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import type { Paginator } from '@/types';

type Props = {
    /** Any paginator prop — only its meta is read. */
    paginator: Paginator<unknown>;
    /** Plural noun for the summary line, e.g. "companies". */
    label: string;
};

const { paginator, label } = defineProps<Props>();

const emit = defineEmits<{ 'update:page': [page: number] }>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">
            <template v-if="paginator.total > 0">
                Showing {{ paginator.from }}–{{ paginator.to }} of
                {{ paginator.total }} {{ label }}
            </template>
            <template v-else>No {{ label }}</template>
        </p>

        <Pagination
            v-if="paginator.last_page > 1"
            :page="paginator.current_page"
            :items-per-page="paginator.per_page"
            :total="paginator.total"
            :sibling-count="1"
            show-edges
            class="mx-0 w-auto justify-end"
            @update:page="(page: number) => emit('update:page', page)"
        >
            <PaginationContent v-slot="{ items }">
                <PaginationPrevious />

                <template v-for="(item, index) in items">
                    <PaginationItem
                        v-if="item.type === 'page'"
                        :key="`page-${item.value}`"
                        :value="item.value"
                        :is-active="item.value === paginator.current_page"
                    >
                        {{ item.value }}
                    </PaginationItem>
                    <PaginationEllipsis
                        v-else
                        :key="`ellipsis-${index}`"
                        :index="index"
                    />
                </template>

                <PaginationNext />
            </PaginationContent>
        </Pagination>
    </div>
</template>
