<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/DealController';
import FormField from '@/components/FormField.vue';
import SelectInput from '@/components/SelectInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { fullName, toDateInput } from '@/lib/format';
import type { Deal, KanbanColumn } from '@/types';

type CompanyOption = {
    id: number;
    name: string;
};

type ContactOption = {
    id: number;
    company_id: number | null;
    first_name: string;
    last_name: string | null;
};

type Props = {
    statuses: KanbanColumn[];
    companies: CompanyOption[];
    contacts: ContactOption[];
    /** Present on edit; absent on create. */
    deal?: Deal;
    /** Stage preselected on create (honors a Kanban column's `?status=`). */
    defaultStatus?: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
};

const {
    statuses,
    companies,
    contacts,
    deal = undefined,
    defaultStatus = undefined,
    cancelHref,
} = defineProps<Props>();

const form = useForm({
    title: deal?.title ?? '',
    company_id: deal?.company_id ?? null,
    contact_id: deal?.contact_id ?? null,
    status: deal?.status ?? defaultStatus ?? statuses[0].value,
    value: deal?.value ?? '',
    expected_close_date: toDateInput(deal?.expected_close_date),
    notes: deal?.notes ?? '',
});

const companyOptions = computed(() =>
    companies.map((company) => ({ value: company.id, label: company.name })),
);

/** Once a company is picked, only its people can be the primary contact. */
const contactOptions = computed(() =>
    contacts
        .filter(
            (contact) =>
                form.company_id === null ||
                contact.company_id === form.company_id,
        )
        .map((contact) => ({ value: contact.id, label: fullName(contact) })),
);

watch(
    () => form.company_id,
    () => {
        const stillSelectable = contactOptions.value.some(
            (option) => option.value === form.contact_id,
        );

        if (!stillSelectable) {
            form.contact_id = null;
        }
    },
);

function submit(): void {
    form.submit(deal ? update(deal.id) : store(), { preserveScroll: true });
}
/** Return to the previous page; fall back to a fixed route on direct entry. */
function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(
            typeof cancelHref === 'string' ? cancelHref : cancelHref.url,
        );
    }
}
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <Card>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <FormField
                    id="title"
                    label="Title"
                    required
                    :error="form.errors.title"
                    class="sm:col-span-2"
                >
                    <Input
                        id="title"
                        v-model="form.title"
                        autofocus
                        placeholder="Website revamp"
                    />
                </FormField>

                <FormField
                    id="company_id"
                    label="Company"
                    required
                    :error="form.errors.company_id"
                >
                    <SelectInput
                        id="company_id"
                        v-model="form.company_id"
                        :options="companyOptions"
                        placeholder="Pick a company"
                        numeric
                    />
                </FormField>

                <FormField
                    id="contact_id"
                    label="Primary contact"
                    :error="form.errors.contact_id"
                    :hint="
                        form.company_id
                            ? undefined
                            : 'Pick a company first to narrow this list.'
                    "
                >
                    <SelectInput
                        id="contact_id"
                        v-model="form.contact_id"
                        :options="contactOptions"
                        placeholder="No contact"
                        null-label="No contact"
                        nullable
                        numeric
                    />
                </FormField>

                <FormField
                    id="status"
                    label="Stage"
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
                    id="value"
                    label="Value (EUR)"
                    :error="form.errors.value"
                >
                    <Input
                        id="value"
                        v-model="form.value"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                    />
                </FormField>

                <FormField
                    id="expected_close_date"
                    label="Expected close"
                    :error="form.errors.expected_close_date"
                    class="sm:col-span-2"
                >
                    <Input
                        id="expected_close_date"
                        v-model="form.expected_close_date"
                        type="date"
                    />
                </FormField>

                <FormField
                    id="notes"
                    label="Notes"
                    :error="form.errors.notes"
                    class="sm:col-span-2"
                >
                    <Textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        placeholder="Context, next steps, blockers…"
                    />
                </FormField>
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Button type="button" variant="outline" @click="goBack">
                Back
            </Button>

            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ deal ? 'Save changes' : 'Create deal' }}
            </Button>
        </div>
    </form>
</template>
