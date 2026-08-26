<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    FileText,
    Handshake,
    Pencil,
    Plus,
    Trash2,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import { show as showCompany } from '@/actions/App/Http/Controllers/CompanyController';
import { show as showContact } from '@/actions/App/Http/Controllers/ContactController';
import {
    board,
    destroy,
    edit,
    show,
} from '@/actions/App/Http/Controllers/DealController';
import {
    create as createOffer,
    show as showOffer,
} from '@/actions/App/Http/Controllers/OfferController';
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
import type { Deal, KanbanColumn } from '@/types';

const { deal } = defineProps<{
    deal: Deal;
    statuses: KanbanColumn[];
    offerStatuses: KanbanColumn[];
}>();

defineOptions({
    layout: (props: { deal: Deal }) => ({
        breadcrumbs: [
            { title: 'Deals', href: board() },
            { title: props.deal.title, href: show(props.deal.id) },
        ],
    }),
});

const deleteOpen = ref(false);
</script>

<template>
    <Head :title="deal.title" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-2">
                <h1
                    class="flex items-center gap-2 text-xl font-semibold tracking-tight"
                >
                    <Handshake class="size-5 text-muted-foreground" />
                    {{ deal.title }}
                </h1>
                <StatusBadge :status="deal.status" :options="statuses" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="createOffer({ query: { deal: deal.id } })"
                    :class="
                        cn(buttonVariants({ variant: 'outline', size: 'sm' }))
                    "
                >
                    <Plus class="size-4" />
                    Offer
                </Link>

                <Link
                    :href="edit(deal.id)"
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
                            v-if="deal.company"
                            :href="showCompany(deal.company.id)"
                            class="inline-flex items-center gap-1 hover:underline"
                        >
                            <Building2 class="size-3.5 text-muted-foreground" />
                            {{ deal.company.name }}
                        </Link>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField label="Primary contact">
                        <Link
                            v-if="deal.contact"
                            :href="showContact(deal.contact.id)"
                            class="inline-flex items-center gap-1 hover:underline"
                        >
                            <Users class="size-3.5 text-muted-foreground" />
                            {{ fullName(deal.contact) }}
                        </Link>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField
                        label="Value"
                        :value="formatCurrency(deal.value)"
                    />
                    <DetailField
                        label="Expected close"
                        :value="formatDate(deal.expected_close_date)"
                    />
                    <DetailField
                        label="Created"
                        :value="formatDate(deal.created_at)"
                    />
                </dl>

                <div v-if="deal.notes" class="mt-6 grid gap-1">
                    <dt
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Notes
                    </dt>
                    <dd class="text-sm whitespace-pre-line">
                        {{ deal.notes }}
                    </dd>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <FileText class="size-4 text-muted-foreground" />
                    Offers ({{ deal.offers?.length ?? 0 }})
                </CardTitle>

                <Link
                    :href="createOffer({ query: { deal: deal.id } })"
                    :class="
                        cn(buttonVariants({ variant: 'outline', size: 'sm' }))
                    "
                >
                    <Plus class="size-4" />
                    Offer
                </Link>
            </CardHeader>

            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Offer #</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead class="text-right">Total</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Issue date</TableHead>
                            <TableHead>Valid until</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!deal.offers?.length" :colspan="6">
                            <span class="text-muted-foreground">
                                No offers yet.
                            </span>
                        </TableEmpty>

                        <TableRow v-for="offer in deal.offers" :key="offer.id">
                            <TableCell>
                                <Link
                                    :href="showOffer(offer.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ offer.offer_number }}
                                </Link>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ offer.title ?? '—' }}
                            </TableCell>
                            <TableCell
                                class="text-right font-medium tabular-nums"
                            >
                                {{ formatCurrency(offer.total) }}
                            </TableCell>
                            <TableCell>
                                <StatusBadge
                                    :status="offer.status"
                                    :options="offerStatuses"
                                />
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ formatDate(offer.issue_date) }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ formatDate(offer.valid_until) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            :action="destroy(deal.id)"
            :name="deal.title"
            consequence="Its offers and their line items are deleted too."
        />
    </div>
</template>
