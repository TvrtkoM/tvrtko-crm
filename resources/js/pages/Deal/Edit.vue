<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    board,
    edit,
    show,
} from '@/actions/App/Http/Controllers/DealController';
import Heading from '@/components/Heading.vue';
import type { Deal, KanbanColumn } from '@/types';
import DealForm from './DealForm.vue';

const { deal } = defineProps<{
    deal: Deal;
    statuses: KanbanColumn[];
    companies: { id: number; name: string }[];
    contacts: {
        id: number;
        company_id: number | null;
        first_name: string;
        last_name: string | null;
    }[];
}>();

defineOptions({
    layout: (props: { deal: Deal }) => ({
        breadcrumbs: [
            { title: 'Deals', href: board() },
            { title: props.deal.title, href: show(props.deal.id) },
            { title: 'Edit', href: edit(props.deal.id) },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit ${deal.title}`" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${deal.title}`"
            description="Update this deal's details."
        />

        <DealForm
            :deal="deal"
            :statuses="statuses"
            :companies="companies"
            :contacts="contacts"
            :cancel-href="show(deal.id)"
        />
    </div>
</template>
