<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    board,
    edit,
    show,
} from '@/actions/App/Http/Controllers/CompanyController';
import Heading from '@/components/Heading.vue';
import type { Company, KanbanColumn } from '@/types';
import CompanyForm from './CompanyForm.vue';

const { company } = defineProps<{
    company: Company;
    statuses: KanbanColumn[];
}>();

defineOptions({
    layout: (props: { company: Company }) => ({
        breadcrumbs: [
            { title: 'Companies', href: board() },
            { title: props.company.name, href: show(props.company.id) },
            { title: 'Edit', href: edit(props.company.id) },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit ${company.name}`" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${company.name}`"
            description="Update this company's details."
        />

        <CompanyForm
            :company="company"
            :statuses="statuses"
            :cancel-href="show(company.id)"
        />
    </div>
</template>
