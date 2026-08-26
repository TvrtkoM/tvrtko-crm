<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/ContactController';
import FormField from '@/components/FormField.vue';
import SelectInput from '@/components/SelectInput.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Contact, KanbanColumn } from '@/types';

type CompanyOption = {
    id: number;
    name: string;
};

type Props = {
    statuses: KanbanColumn[];
    companies: CompanyOption[];
    /** Present on edit; absent on create. */
    contact?: Contact;
    /** Status preselected on create (honors a Kanban column's `?status=`). */
    defaultStatus?: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
};

const {
    statuses,
    companies,
    contact = undefined,
    defaultStatus = undefined,
    cancelHref,
} = defineProps<Props>();

const form = useForm({
    first_name: contact?.first_name ?? '',
    last_name: contact?.last_name ?? '',
    company_id: contact?.company_id ?? null,
    status: contact?.status ?? defaultStatus ?? statuses[0].value,
    job_title: contact?.job_title ?? '',
    email: contact?.email ?? '',
    phone: contact?.phone ?? '',
    notes: contact?.notes ?? '',
});

const companyOptions = computed(() =>
    companies.map((company) => ({ value: company.id, label: company.name })),
);

function submit(): void {
    form.submit(contact ? update(contact.id) : store(), {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <Card>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <FormField
                    id="first_name"
                    label="First name"
                    required
                    :error="form.errors.first_name"
                >
                    <Input
                        id="first_name"
                        v-model="form.first_name"
                        autofocus
                        placeholder="Ivan"
                    />
                </FormField>

                <FormField
                    id="last_name"
                    label="Last name"
                    :error="form.errors.last_name"
                >
                    <Input
                        id="last_name"
                        v-model="form.last_name"
                        placeholder="Horvat"
                    />
                </FormField>

                <FormField
                    id="company_id"
                    label="Company"
                    :error="form.errors.company_id"
                >
                    <SelectInput
                        id="company_id"
                        v-model="form.company_id"
                        :options="companyOptions"
                        placeholder="No company"
                        null-label="No company"
                        nullable
                        numeric
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
                    id="job_title"
                    label="Job title"
                    :error="form.errors.job_title"
                    class="sm:col-span-2"
                >
                    <Input
                        id="job_title"
                        v-model="form.job_title"
                        placeholder="Head of Procurement"
                    />
                </FormField>

                <FormField id="email" label="Email" :error="form.errors.email">
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="ivan@acme.hr"
                    />
                </FormField>

                <FormField id="phone" label="Phone" :error="form.errors.phone">
                    <Input
                        id="phone"
                        v-model="form.phone"
                        placeholder="+385 91 234 5678"
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
                        placeholder="Anything worth remembering about this contact…"
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
                {{ contact ? 'Save changes' : 'Create contact' }}
            </Button>
        </div>
    </form>
</template>
