<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    board,
    edit,
    show,
} from '@/actions/App/Http/Controllers/OfferController';
import Heading from '@/components/Heading.vue';
import type { Deal, KanbanColumn, Offer } from '@/types';
import OfferForm from './OfferForm.vue';

const { offer } = defineProps<{
    offer: Offer;
    statuses: KanbanColumn[];
    deals: Pick<Deal, 'id' | 'title' | 'company_id' | 'company'>[];
}>();

defineOptions({
    layout: (props: { offer: Offer }) => ({
        breadcrumbs: [
            { title: 'Offers', href: board() },
            { title: props.offer.offer_number, href: show(props.offer.id) },
            { title: 'Edit', href: edit(props.offer.id) },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit ${offer.offer_number}`" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${offer.offer_number}`"
            description="Update this offer and its line items."
        />

        <OfferForm
            :offer="offer"
            :statuses="statuses"
            :deals="deals"
            :cancel-href="show(offer.id)"
        />
    </div>
</template>
