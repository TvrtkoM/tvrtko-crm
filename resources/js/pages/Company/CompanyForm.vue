<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { router, useForm } from '@inertiajs/vue3';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/CompanyController';
import FormField from '@/components/FormField.vue';
import SelectInput from '@/components/SelectInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Company, KanbanColumn } from '@/types';

type Props = {
    statuses: KanbanColumn[];
    /** Present on edit; absent on create. */
    company?: Company;
    /** Status preselected on create (honors a Kanban column's `?status=`). */
    defaultStatus?: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
};

const {
    statuses,
    company = undefined,
    defaultStatus = undefined,
    cancelHref,
} = defineProps<Props>();

const form = useForm({
    name: company?.name ?? '',
    status: company?.status ?? defaultStatus ?? statuses[0].value,
    email: company?.email ?? '',
    phone: company?.phone ?? '',
    website: company?.website ?? '',
    industry: company?.industry ?? '',
    address: company?.address ?? '',
    city: company?.city ?? '',
    country: company?.country ?? '',
    notes: company?.notes ?? '',
});

function submit(): void {
    form.submit(company ? update(company.id) : store(), {
        preserveScroll: true,
    });
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
                    id="name"
                    label="Name"
                    required
                    :error="form.errors.name"
                    class="sm:col-span-2"
                >
                    <Input
                        id="name"
                        v-model="form.name"
                        autofocus
                        placeholder="Acme d.o.o."
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
                    id="industry"
                    label="Industry"
                    :error="form.errors.industry"
                >
                    <Input
                        id="industry"
                        v-model="form.industry"
                        placeholder="Software"
                    />
                </FormField>

                <FormField id="email" label="Email" :error="form.errors.email">
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="hello@acme.hr"
                    />
                </FormField>

                <FormField id="phone" label="Phone" :error="form.errors.phone">
                    <Input
                        id="phone"
                        v-model="form.phone"
                        placeholder="+385 1 234 5678"
                    />
                </FormField>

                <FormField
                    id="website"
                    label="Website"
                    :error="form.errors.website"
                    hint="Include the scheme, e.g. https://acme.hr"
                    class="sm:col-span-2"
                >
                    <Input
                        id="website"
                        v-model="form.website"
                        type="url"
                        placeholder="https://acme.hr"
                    />
                </FormField>

                <FormField
                    id="address"
                    label="Address"
                    :error="form.errors.address"
                    class="sm:col-span-2"
                >
                    <Input
                        id="address"
                        v-model="form.address"
                        placeholder="Ilica 1"
                    />
                </FormField>

                <FormField id="city" label="City" :error="form.errors.city">
                    <Input id="city" v-model="form.city" placeholder="Zagreb" />
                </FormField>

                <FormField
                    id="country"
                    label="Country"
                    :error="form.errors.country"
                >
                    <Input
                        id="country"
                        v-model="form.country"
                        placeholder="Croatia"
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
                        placeholder="Anything worth remembering about this company…"
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
                {{ company ? 'Save changes' : 'Create company' }}
            </Button>
        </div>
    </form>
</template>
