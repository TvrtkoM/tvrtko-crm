<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Handshake, Pencil, Trash2, Users } from '@lucide/vue';
import { ref } from 'vue';
import { show as showCompany } from '@/actions/App/Http/Controllers/CompanyController';
import {
    board,
    destroy,
    edit,
    show,
} from '@/actions/App/Http/Controllers/ContactController';
import { show as showDeal } from '@/actions/App/Http/Controllers/DealController';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import DetailField from '@/components/DetailField.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency, formatDate, fullName } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Contact, KanbanColumn } from '@/types';

const { contact } = defineProps<{
    contact: Contact;
    statuses: KanbanColumn[];
    dealStatuses: KanbanColumn[];
}>();

defineOptions({
    layout: (props: { contact: Contact }) => ({
        breadcrumbs: [
            { title: 'Contacts', href: board() },
            {
                title: fullName(props.contact),
                href: show(props.contact.id),
            },
        ],
    }),
});

const deleteOpen = ref(false);
</script>

<template>
    <Head :title="fullName(contact)" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-2">
                <h1
                    class="flex items-center gap-2 text-xl font-semibold tracking-tight"
                >
                    <Users class="size-5 text-muted-foreground" />
                    {{ fullName(contact) }}
                </h1>
                <StatusBadge :status="contact.status" :options="statuses" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="edit(contact.id)"
                    :class="
                        cn(buttonVariants({ variant: 'outline', size: 'sm' }))
                    "
                >
                    <Pencil class="size-4" />
                    Edit
                </Link>

                <Button
                    variant="destructive"
                    size="sm"
                    @click="deleteOpen = true"
                >
                    <Trash2 class="size-4" />
                    Delete
                </Button>
            </div>
        </header>

        <Card>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <DetailField label="Company">
                        <Link
                            v-if="contact.company"
                            :href="showCompany(contact.company.id)"
                            class="inline-flex items-center gap-1 hover:underline"
                        >
                            <Building2 class="size-3.5 text-muted-foreground" />
                            {{ contact.company.name }}
                        </Link>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField label="Job title" :value="contact.job_title" />
                    <DetailField label="Email">
                        <a
                            v-if="contact.email"
                            :href="`mailto:${contact.email}`"
                            class="hover:underline"
                        >
                            {{ contact.email }}
                        </a>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField label="Phone" :value="contact.phone" />
                    <DetailField
                        label="Created"
                        :value="formatDate(contact.created_at)"
                    />
                </dl>

                <div v-if="contact.notes" class="mt-6 grid gap-1">
                    <dt
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Notes
                    </dt>
                    <dd class="text-sm whitespace-pre-line">
                        {{ contact.notes }}
                    </dd>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Handshake class="size-4 text-muted-foreground" />
                    Deals as primary contact ({{ contact.deals?.length ?? 0 }})
                </CardTitle>
            </CardHeader>

            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Company</TableHead>
                            <TableHead class="text-right">Value</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Expected close</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!contact.deals?.length" :colspan="5">
                            <span class="text-muted-foreground">
                                This contact is not the primary contact on any
                                deal.
                            </span>
                        </TableEmpty>

                        <TableRow v-for="deal in contact.deals" :key="deal.id">
                            <TableCell>
                                <Link
                                    :href="showDeal(deal.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ deal.title }}
                                </Link>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ deal.company?.name ?? '—' }}
                            </TableCell>
                            <TableCell
                                class="text-right font-medium tabular-nums"
                            >
                                {{ formatCurrency(deal.value) }}
                            </TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="deal.status"
                                    :options="dealStatuses"
                                />
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ formatDate(deal.expected_close_date) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            :action="destroy(contact.id)"
            :name="fullName(contact)"
            consequence="Deals where this contact is the primary contact stay, but lose the link."
        />
    </div>
</template>
