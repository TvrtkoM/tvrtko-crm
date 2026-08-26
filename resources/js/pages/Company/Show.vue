<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Handshake, Pencil, Plus, Trash2, Users } from '@lucide/vue';
import { ref } from 'vue';
import {
    board,
    destroy,
    edit,
    show,
} from '@/actions/App/Http/Controllers/CompanyController';
import {
    create as createContact,
    show as showContact,
} from '@/actions/App/Http/Controllers/ContactController';
import {
    create as createDeal,
    show as showDeal,
} from '@/actions/App/Http/Controllers/DealController';
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
import type { Company, KanbanColumn } from '@/types';

const { company } = defineProps<{
    company: Company;
    statuses: KanbanColumn[];
    contactStatuses: KanbanColumn[];
    dealStatuses: KanbanColumn[];
    pipelineValue: number;
}>();

defineOptions({
    layout: (props: { company: Company }) => ({
        breadcrumbs: [
            { title: 'Companies', href: board() },
            { title: props.company.name, href: show(props.company.id) },
        ],
    }),
});

const deleteOpen = ref(false);
</script>

<template>
    <Head :title="company.name" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-2">
                <h1
                    class="flex items-center gap-2 text-xl font-semibold tracking-tight"
                >
                    <Building2 class="size-5 text-muted-foreground" />
                    {{ company.name }}
                </h1>
                <StatusBadge :status="company.status" :options="statuses" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="edit(company.id)"
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
                    <DetailField label="Industry" :value="company.industry" />
                    <DetailField label="Email">
                        <a
                            v-if="company.email"
                            :href="`mailto:${company.email}`"
                            class="hover:underline"
                        >
                            {{ company.email }}
                        </a>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField label="Phone" :value="company.phone" />
                    <DetailField label="Website">
                        <a
                            v-if="company.website"
                            :href="company.website"
                            target="_blank"
                            rel="noopener"
                            class="hover:underline"
                        >
                            {{ company.website }}
                        </a>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField label="Address" :value="company.address" />
                    <DetailField
                        label="Location"
                        :value="
                            [company.city, company.country]
                                .filter(Boolean)
                                .join(', ') || null
                        "
                    />
                    <DetailField
                        label="Pipeline value"
                        :value="formatCurrency(pipelineValue)"
                    />
                    <DetailField
                        label="Created"
                        :value="formatDate(company.created_at)"
                    />
                </dl>

                <div v-if="company.notes" class="mt-6 grid gap-1">
                    <dt
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Notes
                    </dt>
                    <dd class="text-sm whitespace-pre-line">
                        {{ company.notes }}
                    </dd>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Users class="size-4 text-muted-foreground" />
                    Contacts ({{ company.contacts?.length ?? 0 }})
                </CardTitle>

                <Link
                    :href="createContact()"
                    :class="
                        cn(buttonVariants({ variant: 'outline', size: 'sm' }))
                    "
                >
                    <Plus class="size-4" />
                    Contact
                </Link>
            </CardHeader>

            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Job title</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty
                            v-if="!company.contacts?.length"
                            :colspan="4"
                        >
                            <span class="text-muted-foreground">
                                No contacts yet.
                            </span>
                        </TableEmpty>

                        <TableRow
                            v-for="contact in company.contacts"
                            :key="contact.id"
                        >
                            <TableCell>
                                <Link
                                    :href="showContact(contact.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ fullName(contact) }}
                                </Link>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ contact.job_title ?? '—' }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ contact.email ?? '—' }}
                            </TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="contact.status"
                                    :options="contactStatuses"
                                />
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Handshake class="size-4 text-muted-foreground" />
                    Deals ({{ company.deals?.length ?? 0 }})
                </CardTitle>

                <Link
                    :href="createDeal()"
                    :class="
                        cn(buttonVariants({ variant: 'outline', size: 'sm' }))
                    "
                >
                    <Plus class="size-4" />
                    Deal
                </Link>
            </CardHeader>

            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead class="text-right">Value</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Expected close</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!company.deals?.length" :colspan="5">
                            <span class="text-muted-foreground">
                                No deals yet.
                            </span>
                        </TableEmpty>

                        <TableRow v-for="deal in company.deals" :key="deal.id">
                            <TableCell>
                                <Link
                                    :href="showDeal(deal.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ deal.title }}
                                </Link>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{
                                    deal.contact ? fullName(deal.contact) : '—'
                                }}
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
            :action="destroy(company.id)"
            :name="company.name"
            consequence="Its deals are deleted too; its contacts stay but lose the company link."
        />
    </div>
</template>
