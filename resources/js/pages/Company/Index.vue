<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Plus } from '@lucide/vue';
import { ref } from 'vue';
import {
    board,
    create,
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/CompanyController';
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
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Company, IndexFilters, KanbanColumn, Paginator } from '@/types';

const props = defineProps<{
    companies: Paginator<Company>;
    filters: IndexFilters;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Companies', href: board() }],
    },
});

const { search, status, isFiltered, toggleSort, goToPage, reset } =
    useIndexFilters(index, () => props.filters);

const deleting = ref<Company | null>(null);
const deleteOpen = ref(false);

function confirmDelete(company: Company): void {
    deleting.value = company;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Companies · List" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1
                class="flex items-center gap-2 text-xl font-semibold tracking-tight"
            >
                <Building2 class="size-5 text-muted-foreground" />
                Companies
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <ViewToggle :board-href="board()" :list-href="index()" />

                <Link
                    :href="create()"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Plus class="size-4" />
                    New company
                </Link>
            </div>
        </header>

        <TableFilters
            v-model:search="search"
            v-model:status="status"
            :statuses="statuses"
            :is-filtered="isFiltered"
            placeholder="Search name, email, industry or city…"
            @reset="reset"
        />

        <div class="overflow-hidden rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <SortableHeader
                            column="name"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Name
                        </SortableHeader>
                        <TableHead>Industry</TableHead>
                        <TableHead>City</TableHead>
                        <SortableHeader
                            column="status"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Status
                        </SortableHeader>
                        <TableHead class="text-right">Contacts</TableHead>
                        <TableHead class="text-right">Deals</TableHead>
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
                    <TableEmpty v-if="companies.data.length === 0" :colspan="8">
                        <div class="flex flex-col items-center gap-1">
                            <p class="font-medium">No companies found</p>
                            <p class="text-muted-foreground">
                                {{
                                    isFiltered
                                        ? 'Try a different search or status filter.'
                                        : 'Add your first company to get started.'
                                }}
                            </p>
                        </div>
                    </TableEmpty>

                    <TableRow
                        v-for="company in companies.data"
                        :key="company.id"
                    >
                        <TableCell>
                            <Link
                                :href="show(company.id)"
                                class="font-medium hover:underline"
                            >
                                {{ company.name }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ company.industry ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ company.city ?? '—' }}
                        </TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="company.status"
                                :options="statuses"
                            />
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ company.contacts_count ?? 0 }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ company.deals_count ?? 0 }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(company.created_at) }}
                        </TableCell>
                        <TableCell>
                            <RowActions
                                :view-href="show(company.id)"
                                :edit-href="edit(company.id)"
                                :label="company.name"
                                @delete="confirmDelete(company)"
                            />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <TablePagination
            :paginator="companies"
            label="companies"
            @update:page="goToPage"
        />

        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            :action="deleting ? destroy(deleting.id) : undefined"
            :name="deleting?.name"
            consequence="Its deals are deleted too; its contacts stay but lose the company link."
        />
    </div>
</template>
