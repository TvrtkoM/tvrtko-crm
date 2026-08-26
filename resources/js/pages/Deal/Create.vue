<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { board, create } from '@/actions/App/Http/Controllers/DealController';
import Heading from '@/components/Heading.vue';
import type { KanbanColumn } from '@/types';
import DealForm from './DealForm.vue';

defineProps<{
    statuses: KanbanColumn[];
    companies: { id: number; name: string }[];
    contacts: {
        id: number;
        company_id: number | null;
        first_name: string;
        last_name: string | null;
    }[];
    defaultStatus: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Deals', href: board() },
            { title: 'New deal', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New deal" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <Heading
            title="New deal"
            description="Start tracking an opportunity."
        />

        <DealForm
            :statuses="statuses"
            :companies="companies"
            :contacts="contacts"
            :default-status="defaultStatus"
            :cancel-href="board()"
        />
    </div>
</template>
