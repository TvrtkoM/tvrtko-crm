<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Plus } from '@lucide/vue';
import { ref } from 'vue';
import { show as showDeal } from '@/actions/App/Http/Controllers/DealController';
import {
    board,
    create,
    destroy,
    edit,
    index,
    pdf,
    show,
} from '@/actions/App/Http/Controllers/OfferController';
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
import { formatCurrency, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { IndexFilters, KanbanColumn, Offer, Paginator } from '@/types';

const props = defineProps<{
    offers: Paginator<Offer>;
    filters: IndexFilters;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Offers', href: board() }],
    },
});

const { search, status, isFiltered, toggleSort, goToPage, reset } =
    useIndexFilters(index, () => props.filters);

const deleting = ref<Offer | null>(null);
const deleteOpen = ref(false);

function confirmDelete(offer: Offer): void {
    deleting.value = offer;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Offers · List" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1
                class="flex items-center gap-2 text-xl font-semibold tracking-tight"
            >
                <FileText class="size-5 text-muted-foreground" />
                Offers
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <ViewToggle :board-href="board()" :list-href="index()" />

                <Link
                    :href="create()"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Plus class="size-4" />
                    New offer
                </Link>
            </div>
        </header>

        <TableFilters
            v-model:search="search"
            v-model:status="status"
            :statuses="statuses"
            :is-filtered="isFiltered"
            placeholder="Search offer number, title, deal or company…"
            @reset="reset"
        />

        <div class="overflow-hidden rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <SortableHeader
                            column="offer_number"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Offer #
                        </SortableHeader>
                        <TableHead>Deal / Company</TableHead>
                        <SortableHeader
                            column="total"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            align="right"
                            @sort="toggleSort"
                        >
                            Total
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
                            column="issue_date"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Issue date
                        </SortableHeader>
                        <TableHead>Valid until</TableHead>
                        <TableHead class="w-12">
                            <span class="sr-only">Actions</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableEmpty v-if="offers.data.length === 0" :colspan="7">
                        <div class="flex flex-col items-center gap-1">
                            <p class="font-medium">No offers found</p>
                            <p class="text-muted-foreground">
                                {{
                                    isFiltered
                                        ? 'Try a different search or status filter.'
                                        : 'Create your first offer to get started.'
                                }}
                            </p>
                        </div>
                    </TableEmpty>

                    <TableRow v-for="offer in offers.data" :key="offer.id">
                        <TableCell>
                            <Link
                                :href="show(offer.id)"
                                class="font-medium hover:underline"
                            >
                                {{ offer.offer_number }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link
                                v-if="offer.deal"
                                :href="showDeal(offer.deal.id)"
                                class="hover:underline"
                            >
                                {{ offer.deal.title }}
                            </Link>
                            <span v-else class="text-muted-foreground">—</span>
                            <span
                                v-if="offer.deal?.company"
                                class="block text-xs text-muted-foreground"
                            >
                                {{ offer.deal.company.name }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right font-medium tabular-nums">
                            {{ formatCurrency(offer.total) }}
                        </TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="offer.status"
                                :options="statuses"
                            />
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(offer.issue_date) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(offer.valid_until) }}
                        </TableCell>
                        <TableCell>
                            <RowActions
                                :view-href="show(offer.id)"
                                :edit-href="edit(offer.id)"
                                :pdf-href="pdf(offer.id).url"
                                :label="offer.offer_number"
                                @delete="confirmDelete(offer)"
                            />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <TablePagination
            :paginator="offers"
            label="offers"
            @update:page="goToPage"
        />

        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            :action="deleting ? destroy(deleting.id) : undefined"
            :name="deleting?.offer_number"
            consequence="Its line items are deleted too."
        />
    </div>
</template>
