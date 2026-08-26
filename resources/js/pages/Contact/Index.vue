<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Users } from '@lucide/vue';
import { ref } from 'vue';
import { show as showCompany } from '@/actions/App/Http/Controllers/CompanyController';
import {
    board,
    create,
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/ContactController';
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
import { formatDate, fullName } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Contact, IndexFilters, KanbanColumn, Paginator } from '@/types';

const props = defineProps<{
    contacts: Paginator<Contact>;
    filters: IndexFilters;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contacts', href: board() }],
    },
});

const { search, status, isFiltered, toggleSort, goToPage, reset } =
    useIndexFilters(index, () => props.filters);

const deleting = ref<Contact | null>(null);
const deleteOpen = ref(false);

function confirmDelete(contact: Contact): void {
    deleting.value = contact;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Contacts · List" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1
                class="flex items-center gap-2 text-xl font-semibold tracking-tight"
            >
                <Users class="size-5 text-muted-foreground" />
                Contacts
            </h1>

            <div class="flex flex-wrap items-center gap-2">
                <ViewToggle :board-href="board()" :list-href="index()" />

                <Link
                    :href="create()"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Plus class="size-4" />
                    New contact
                </Link>
            </div>
        </header>

        <TableFilters
            v-model:search="search"
            v-model:status="status"
            :statuses="statuses"
            :is-filtered="isFiltered"
            placeholder="Search name, email, job title or company…"
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
                        <SortableHeader
                            column="company"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Company
                        </SortableHeader>
                        <TableHead>Job title</TableHead>
                        <TableHead>Email</TableHead>
                        <SortableHeader
                            column="status"
                            :sort="filters.sort"
                            :dir="filters.dir"
                            @sort="toggleSort"
                        >
                            Status
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
                    <TableEmpty v-if="contacts.data.length === 0" :colspan="7">
                        <div class="flex flex-col items-center gap-1">
                            <p class="font-medium">No contacts found</p>
                            <p class="text-muted-foreground">
                                {{
                                    isFiltered
                                        ? 'Try a different search or status filter.'
                                        : 'Add your first contact to get started.'
                                }}
                            </p>
                        </div>
                    </TableEmpty>

                    <TableRow
                        v-for="contact in contacts.data"
                        :key="contact.id"
                    >
                        <TableCell>
                            <Link
                                :href="show(contact.id)"
                                class="font-medium hover:underline"
                            >
                                {{ fullName(contact) }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link
                                v-if="contact.company"
                                :href="showCompany(contact.company.id)"
                                class="hover:underline"
                            >
                                {{ contact.company.name }}
                            </Link>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ contact.job_title ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            <a
                                v-if="contact.email"
                                :href="`mailto:${contact.email}`"
                                class="hover:underline"
                            >
                                {{ contact.email }}
                            </a>
                            <span v-else>—</span>
                        </TableCell>
                        <TableCell>
                            <StatusBadge
                                :status="contact.status"
                                :options="statuses"
                            />
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(contact.created_at) }}
                        </TableCell>
                        <TableCell>
                            <RowActions
                                :view-href="show(contact.id)"
                                :edit-href="edit(contact.id)"
                                :label="fullName(contact)"
                                @delete="confirmDelete(contact)"
                            />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <TablePagination
            :paginator="contacts"
            label="contacts"
            @update:page="goToPage"
        />

        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            :action="deleting ? destroy(deleting.id) : undefined"
            :name="deleting ? fullName(deleting) : undefined"
            consequence="Deals where this contact is the primary contact stay, but lose the link."
        />
    </div>
</template>
