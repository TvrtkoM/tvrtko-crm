<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/OfferController';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import SelectInput from '@/components/SelectInput.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency, toDateInput, todayInput } from '@/lib/format';
import type { Deal, KanbanColumn, Offer } from '@/types';

type DealOption = Pick<Deal, 'id' | 'title' | 'company_id' | 'company'>;

type ItemField = 'description' | 'quantity' | 'unit_price';

type Props = {
    statuses: KanbanColumn[];
    deals: DealOption[];
    /** Present on edit; absent on create. */
    offer?: Offer;
    /** Bound from `?deal=` on create — pre-fills and locks the deal picker. */
    lockedDeal?: Deal | null;
    /** Status preselected on create (honors a Kanban column's `?status=`). */
    defaultStatus?: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
};

const {
    statuses,
    deals,
    offer = undefined,
    lockedDeal = null,
    defaultStatus = undefined,
    cancelHref,
} = defineProps<Props>();

const form = useForm({
    deal_id: offer?.deal_id ?? lockedDeal?.id ?? null,
    title: offer?.title ?? lockedDeal?.title ?? '',
    status: offer?.status ?? defaultStatus ?? statuses[0].value,
    issue_date: offer ? toDateInput(offer.issue_date) : todayInput(),
    valid_until: toDateInput(offer?.valid_until),
    tax_rate: offer?.tax_rate ?? '25',
    notes: offer?.notes ?? '',
    items: offer?.items?.length
        ? offer.items.map((item) => ({
              description: item.description,
              quantity: item.quantity,
              unit_price: item.unit_price,
          }))
        : [emptyItem()],
});

const dealOptions = computed(() =>
    deals.map((deal) => ({ value: deal.id, label: dealLabel(deal) })),
);

/**
 * Per-row and document totals, mirroring the server-side accessors on `Offer`
 * so the figures the user types match what is stored on save.
 */
const lineTotals = computed(() =>
    form.items.map((item) =>
        round(toNumber(item.quantity) * toNumber(item.unit_price)),
    ),
);

const subtotal = computed(() =>
    round(lineTotals.value.reduce((sum, lineTotal) => sum + lineTotal, 0)),
);

const taxAmount = computed(() =>
    round((subtotal.value * toNumber(form.tax_rate)) / 100),
);

const total = computed(() => round(subtotal.value + taxAmount.value));

function emptyItem(): {
    description: string;
    quantity: string;
    unit_price: string;
} {
    return { description: '', quantity: '1', unit_price: '' };
}

function dealLabel(deal: DealOption): string {
    return [deal.title, deal.company?.name].filter(Boolean).join(' · ');
}

function toNumber(value: string | number | null): number {
    const parsed =
        typeof value === 'number' ? value : Number.parseFloat(value ?? '');

    return Number.isFinite(parsed) ? parsed : 0;
}

function round(value: number): number {
    return Math.round(value * 100) / 100;
}

function itemError(index: number, field: ItemField): string | undefined {
    return form.errors[`items.${index}.${field}`];
}

function addItem(): void {
    form.items.push(emptyItem());
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function submit(): void {
    form.submit(offer ? update(offer.id) : store(), { preserveScroll: true });
}
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <Card>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <FormField
                    id="deal_id"
                    label="Deal"
                    required
                    :error="form.errors.deal_id"
                    :hint="
                        lockedDeal
                            ? 'Locked to the deal this offer was started from.'
                            : undefined
                    "
                    class="sm:col-span-2"
                >
                    <SelectInput
                        id="deal_id"
                        v-model="form.deal_id"
                        :options="dealOptions"
                        :disabled="Boolean(lockedDeal)"
                        placeholder="Pick a deal"
                        numeric
                    />
                </FormField>

                <FormField
                    id="title"
                    label="Title"
                    :error="form.errors.title"
                    class="sm:col-span-2"
                >
                    <Input
                        id="title"
                        v-model="form.title"
                        placeholder="Website revamp — phase 1"
                    />
                </FormField>

                <FormField
                    id="status"
                    label="Status"
                    required
                    :error="form.errors.status"
                >
                    <SelectInput
                        id="status"
                        v-model="form.status"
                        :options="statuses"
                    />
                </FormField>

                <FormField
                    id="tax_rate"
                    label="Tax rate (%)"
                    :error="form.errors.tax_rate"
                >
                    <Input
                        id="tax_rate"
                        v-model="form.tax_rate"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="25"
                    />
                </FormField>

                <FormField
                    id="issue_date"
                    label="Issue date"
                    :error="form.errors.issue_date"
                >
                    <Input
                        id="issue_date"
                        v-model="form.issue_date"
                        type="date"
                    />
                </FormField>

                <FormField
                    id="valid_until"
                    label="Valid until"
                    :error="form.errors.valid_until"
                >
                    <Input
                        id="valid_until"
                        v-model="form.valid_until"
                        type="date"
                    />
                </FormField>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex-row items-center justify-between gap-3">
                <CardTitle>Line items</CardTitle>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="addItem"
                >
                    <Plus class="size-4" />
                    Add row
                </Button>
            </CardHeader>

            <CardContent class="flex flex-col gap-4">
                <p
                    v-if="form.errors.items"
                    class="text-sm text-red-600 dark:text-red-500"
                >
                    {{ form.errors.items }}
                </p>

                <div
                    class="hidden gap-3 px-1 text-xs font-medium text-muted-foreground sm:grid sm:grid-cols-[1fr_6rem_8rem_8rem_2.25rem]"
                >
                    <span>Description</span>
                    <span>Quantity</span>
                    <span>Unit price (EUR)</span>
                    <span class="text-right">Line total</span>
                    <span class="sr-only">Remove</span>
                </div>

                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="grid gap-3 rounded-lg border p-3 sm:grid-cols-[1fr_6rem_8rem_8rem_2.25rem] sm:items-start sm:border-0 sm:p-0"
                >
                    <div class="grid gap-1">
                        <Input
                            v-model="item.description"
                            :aria-label="`Description of row ${index + 1}`"
                            placeholder="Design & UX"
                        />
                        <InputError
                            :message="itemError(index, 'description')"
                        />
                    </div>

                    <div class="grid gap-1">
                        <Input
                            v-model="item.quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            :aria-label="`Quantity of row ${index + 1}`"
                        />
                        <InputError :message="itemError(index, 'quantity')" />
                    </div>

                    <div class="grid gap-1">
                        <Input
                            v-model="item.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            :aria-label="`Unit price of row ${index + 1}`"
                        />
                        <InputError :message="itemError(index, 'unit_price')" />
                    </div>

                    <p
                        class="self-center text-sm font-medium tabular-nums sm:text-right"
                    >
                        {{ formatCurrency(lineTotals[index]) }}
                    </p>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="justify-self-end text-muted-foreground hover:text-destructive"
                        :disabled="form.items.length === 1"
                        :title="
                            form.items.length === 1
                                ? 'An offer needs at least one line item'
                                : `Remove row ${index + 1}`
                        "
                        @click="removeItem(index)"
                    >
                        <Trash2 class="size-4" />
                        <span class="sr-only">Remove row {{ index + 1 }}</span>
                    </Button>
                </div>

                <dl
                    class="ml-auto grid w-full max-w-xs gap-1 text-sm sm:w-auto"
                >
                    <div class="flex items-center justify-between gap-8">
                        <dt class="text-muted-foreground">Subtotal</dt>
                        <dd class="tabular-nums">
                            {{ formatCurrency(subtotal) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-8">
                        <dt class="text-muted-foreground">
                            Tax ({{ toNumber(form.tax_rate) }}%)
                        </dt>
                        <dd class="tabular-nums">
                            {{ formatCurrency(taxAmount) }}
                        </dd>
                    </div>
                    <div
                        class="mt-1 flex items-center justify-between gap-8 border-t pt-2 font-semibold"
                    >
                        <dt>Total</dt>
                        <dd class="tabular-nums">
                            {{ formatCurrency(total) }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <Card>
            <CardContent>
                <FormField
                    id="notes"
                    label="Notes / terms"
                    :error="form.errors.notes"
                    hint="Rendered as the footer text of the offer PDF."
                >
                    <Textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        placeholder="Payment within 30 days…"
                    />
                </FormField>
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Link
                :href="cancelHref"
                :class="buttonVariants({ variant: 'outline' })"
            >
                Cancel
            </Link>

            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ offer ? 'Save changes' : 'Create offer' }}
            </Button>
        </div>
    </form>
</template>
