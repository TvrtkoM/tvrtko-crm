<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    board,
    edit,
    show,
} from '@/actions/App/Http/Controllers/ContactController';
import Heading from '@/components/Heading.vue';
import { fullName } from '@/lib/format';
import type { Contact, KanbanColumn } from '@/types';
import ContactForm from './ContactForm.vue';

const { contact } = defineProps<{
    contact: Contact;
    statuses: KanbanColumn[];
    companies: { id: number; name: string }[];
}>();

defineOptions({
    layout: (props: { contact: Contact }) => ({
        breadcrumbs: [
            { title: 'Contacts', href: board() },
            { title: fullName(props.contact), href: show(props.contact.id) },
            { title: 'Edit', href: edit(props.contact.id) },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit ${fullName(contact)}`" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${fullName(contact)}`"
            description="Update this contact's details."
        />

        <ContactForm
            :contact="contact"
            :statuses="statuses"
            :companies="companies"
            :cancel-href="show(contact.id)"
        />
    </div>
</template>
