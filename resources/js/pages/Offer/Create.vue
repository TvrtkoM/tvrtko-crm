<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { board, create } from '@/actions/App/Http/Controllers/OfferController';
import Heading from '@/components/Heading.vue';
import type { Deal, KanbanColumn } from '@/types';
import OfferForm from './OfferForm.vue';

const { deal } = defineProps<{
    statuses: KanbanColumn[];
    deals: Pick<Deal, 'id' | 'title' | 'company_id' | 'company'>[];
    /** Bound from `?deal=` — the "+ Offer" shortcut on a Deal card. */
    deal: Deal | null;
    defaultStatus: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Offers', href: board() },
            { title: 'New offer', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New offer" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <Heading
            title="New offer"
            :description="
                deal
                    ? `For ${deal.title}. The offer number is generated on save.`
                    : 'The offer number is generated on save.'
            "
        />

        <OfferForm
            :statuses="statuses"
            :deals="deals"
            :locked-deal="deal"
            :default-status="defaultStatus"
            :cancel-href="board()"
        />
    </div>
</template>
