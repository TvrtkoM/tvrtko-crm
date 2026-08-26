<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    Download,
    FileText,
    Handshake,
    Pencil,
    Trash2,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import { show as showCompany } from '@/actions/App/Http/Controllers/CompanyController';
import { show as showContact } from '@/actions/App/Http/Controllers/ContactController';
import { show as showDeal } from '@/actions/App/Http/Controllers/DealController';
import {
    board,
    destroy,
    edit,
    pdf,
    show,
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
import type { KanbanColumn, Offer } from '@/types';

const { offer } = defineProps<{
    offer: Offer;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: (props: { offer: Offer }) => ({
        breadcrumbs: [
            { title: 'Offers', href: board() },
            { title: props.offer.offer_number, href: show(props.offer.id) },
        ],
    }),
});

const deleteOpen = ref(false);
</script>

<template>
    <Head :title="offer.offer_number" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-2">
                <h1
                    class="flex items-center gap-2 text-xl font-semibold tracking-tight"
                >
                    <FileText class="size-5 text-muted-foreground" />
                    {{ offer.offer_number }}
                </h1>
                <p v-if="offer.title" class="text-sm text-muted-foreground">
                    {{ offer.title }}
                </p>
                <StatusBadge :status="offer.status" :options="statuses" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    :href="pdf(offer.id).url"
                    :class="cn(buttonVariants({ size: 'sm' }))"
                >
                    <Download class="size-4" />
                    Download PDF
                </a>

                <Link
                    :href="edit(offer.id)"
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
                    <DetailField label="Deal">
                        <Link
                            v-if="offer.deal"
                            :href="showDeal(offer.deal.id)"
                            class="inline-flex items-center gap-1 hover:underline"
                        >
                            <Handshake class="size-3.5 text-muted-foreground" />
                            {{ offer.deal.title }}
                        </Link>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField label="Company">
                        <Link
                            v-if="offer.deal?.company"
                            :href="showCompany(offer.deal.company.id)"
                            class="inline-flex items-center gap-1 hover:underline"
                        >
                            <Building2 class="size-3.5 text-muted-foreground" />
                            {{ offer.deal.company.name }}
                        </Link>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField label="Contact">
                        <Link
                            v-if="offer.deal?.contact"
                            :href="showContact(offer.deal.contact.id)"
                            class="inline-flex items-center gap-1 hover:underline"
                        >
                            <Users class="size-3.5 text-muted-foreground" />
                            {{ fullName(offer.deal.contact) }}
                        </Link>
                        <span v-else>—</span>
                    </DetailField>
                    <DetailField
                        label="Issue date"
                        :value="formatDate(offer.issue_date)"
                    />
                    <DetailField
                        label="Valid until"
                        :value="formatDate(offer.valid_until)"
                    />
                    <DetailField
                        label="Tax rate"
                        :value="`${Number(offer.tax_rate)} %`"
                    />
                </dl>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Line items</CardTitle>
            </CardHeader>

            <CardContent class="flex flex-col gap-4">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Description</TableHead>
                            <TableHead class="text-right">Quantity</TableHead>
                            <TableHead class="text-right">Unit price</TableHead>
                            <TableHead class="text-right">Line total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!offer.items?.length" :colspan="4">
                            <span class="text-muted-foreground">
                                No line items.
                            </span>
                        </TableEmpty>

                        <TableRow v-for="item in offer.items" :key="item.id">
                            <TableCell>{{ item.description }}</TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ Number(item.quantity) }}
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ formatCurrency(item.unit_price) }}
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ formatCurrency(item.line_total) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <dl
                    class="ml-auto grid w-full max-w-xs gap-2 text-sm sm:w-auto"
                >
                    <div class="flex items-center justify-between gap-8">
                        <dt class="text-muted-foreground">Subtotal</dt>
                        <dd class="tabular-nums">
                            {{ formatCurrency(offer.subtotal) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-8">
                        <dt class="text-muted-foreground">
                            Tax ({{ Number(offer.tax_rate) }} %)
                        </dt>
                        <dd class="tabular-nums">
                            {{ formatCurrency(offer.tax_amount) }}
                        </dd>
                    </div>
                    <div
                        class="flex items-center justify-between gap-8 border-t pt-2 font-semibold"
                    >
                        <dt>Total</dt>
                        <dd class="tabular-nums">
                            {{ formatCurrency(offer.total) }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <Card v-if="offer.notes">
            <CardHeader>
                <CardTitle class="text-base">Terms &amp; notes</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-sm whitespace-pre-line">{{ offer.notes }}</p>
            </CardContent>
        </Card>

        <ConfirmDeleteDialog
            v-model:open="deleteOpen"
            :action="destroy(offer.id)"
            :name="offer.offer_number"
            consequence="Its line items are deleted too."
        />
    </div>
</template>
