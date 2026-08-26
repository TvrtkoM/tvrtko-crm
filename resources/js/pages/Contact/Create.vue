<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    board,
    create,
} from '@/actions/App/Http/Controllers/ContactController';
import Heading from '@/components/Heading.vue';
import type { KanbanColumn } from '@/types';
import ContactForm from './ContactForm.vue';

defineProps<{
    statuses: KanbanColumn[];
    companies: { id: number; name: string }[];
    defaultStatus: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contacts', href: board() },
            { title: 'New contact', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New contact" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <Heading
            title="New contact"
            description="Add a person you deal with."
        />

        <ContactForm
            :statuses="statuses"
            :companies="companies"
            :default-status="defaultStatus"
            :cancel-href="board()"
        />
    </div>
</template>
