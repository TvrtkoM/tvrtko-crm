<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Handshake, Plus } from '@lucide/vue';
import { ref } from 'vue';
import { show as showCompany } from '@/actions/App/Http/Controllers/CompanyController';
import { show as showContact } from '@/actions/App/Http/Controllers/ContactController';
import {
    board,
    create,
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/DealController';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import RowActions from '@/components/RowActions.vue';
import SortableHeader from '@/components/SortableHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableFilters from '@/components/TableFilters.vue';
import TablePagination from '@/components/TablePagination.vue';
import { buttonVariants } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import ViewToggle from '@/components/ViewToggle.vue';
import { useIndexFilters } from '@/composables/useIndexFilters';
import { formatCurrency, formatDate, fullName } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Deal, IndexFilters, KanbanColumn, Paginator } from '@/types';

const props = defineProps<{
    deals: Paginator<Deal>;
    filters: IndexFilters;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Deals', href: board() }],
    },
});

const { search, status, isFiltered, toggleSort, goToPage, reset } =
    useIndexFilters(index, () => props.filters);

const deleting = ref<Deal | null>(null);
const deleteOpen = ref(false);

function confirmDelete(deal: Deal): void {
    deleting.value = deal;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Deals · List" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
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

        <TableFilters
            v-model:search="search"
            v-model:status="status"
            :statuses="statuses"
            :is-filtered="isFiltered"
            placeholder="Search title, company or contact…"
            @reset="reset"
        />

        <div class="overflow-hidden rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <SortableHeader
                            column="title"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Title
                        </SortableHeader>
                        <TableHead>Company</TableHead>
                        <TableHead>Contact</TableHead>
                        <SortableHeader
                            column="value"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            align="right"
                            @sort="toggleSort"
                        >
                            Value
                        </SortableHeader>
                        <SortableHeader
                            column="status"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Status
                        </SortableHeader>
                        <SortableHeader
                            column="expected_close_date"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Expected close
                        </SortableHeader>
                        <SortableHeader
                            column="created_at"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Created
                        </SortableHeader>
                        <TableHead class="w-12">
                            <span class="sr-only">Actions</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableEmpty v-if="deals.data.length === 0" :colspan="8">
                        <div class="flex flex-col items-center gap-1">
                            <p class="font-medium">No deals found</p>
                            <p class="text-muted-foreground">
                                {{
                                    isFiltered
                                        ? 'Try a different search or status filter.'
                                        : 'Add your first deal to get started.'
                                }}
                            </p>
                        </div>
                    </TableEmpty>

                    <TableRow v-for="deal in deals.data" :key="deal.id">
                        <TableCell>
                            <Link
                                :href="show(deal.id)"
                                class="font-medium hover:underline"
                            >
                                {{ deal.title }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link
                                v-if="deal.company"
                                :href="showCompany(deal.company.id)"
                                class="hover:underline"
                            >
                                {{ deal.company.name }}
                            </Link>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell>
                            <Link
                                v-if="deal.contact"
                                :href="showContact(deal.contact.id)"
                                class="hover:underline"
                            >
                                {{ fullName(deal.contact) }}
                            </Link>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell class="text-right font-medium tabular-nums">
                            {{ formatCurrency(deal.value) }}
                        </TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="deal.status"
                                :options="statuses"
                            />
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(deal.expected_close_date) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(deal.created_at) }}
                        </TableCell>
                        <TableCell>
                            <RowActions
                                :view-href="show(deal.id)"
                                :edit-href="edit(deal.id)"
                                :label="deal.title"
                                @delete="confirmDelete(deal)"
                            />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <TablePagination
            :paginator="deals"
            label="deals"
            @update:page="goToPage"
        />

        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            :action="deleting ? destroy(deleting.id) : undefined"
            :name="deleting?.title"
            consequence="Its offers and their line items are deleted too."
        />
    </div>
</template>
