<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    board,
    create,
} from '@/actions/App/Http/Controllers/CompanyController';
import Heading from '@/components/Heading.vue';
import type { KanbanColumn } from '@/types';
import CompanyForm from './CompanyForm.vue';

defineProps<{
    statuses: KanbanColumn[];
    defaultStatus: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Companies', href: board() },
            { title: 'New company', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New company" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <Heading
            title="New company"
            description="Add a company to the pipeline."
        />

        <CompanyForm
            :statuses="statuses"
            :default-status="defaultStatus"
            :cancel-href="board()"
        />
    </div>
</template>
