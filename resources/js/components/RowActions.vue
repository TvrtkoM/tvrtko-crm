<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Download, EllipsisVertical, Eye, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Props = {
    viewHref: NonNullable<InertiaLinkProps['href']>;
    editHref: NonNullable<InertiaLinkProps['href']>;
    /** Offers only — a plain browser download, not an Inertia visit. */
    pdfHref?: string;
    label: string;
};

const { viewHref, editHref, pdfHref = undefined, label } = defineProps<Props>();

const emit = defineEmits<{ delete: [] }>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon-sm">
                <EllipsisVertical class="size-4" />
                <span class="sr-only">Actions for {{ label }}</span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem as-child>
                <Link :href="viewHref">
                    <Eye class="size-4" />
                    View
                </Link>
            </DropdownMenuItem>

            <DropdownMenuItem as-child>
                <Link :href="editHref">
                    <Pencil class="size-4" />
                    Edit
                </Link>
            </DropdownMenuItem>

            <DropdownMenuItem v-if="pdfHref" as-child>
                <a :href="pdfHref">
                    <Download class="size-4" />
                    Download PDF
                </a>
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <DropdownMenuItem variant="destructive" @select="emit('delete')">
                <Trash2 class="size-4" />
                Delete
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
