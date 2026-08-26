<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { RouteDefinition } from '@/wayfinder';

type Props = {
    /** Wayfinder `destroy` action for the record. Absent while nothing is queued. */
    action?: RouteDefinition<'delete'>;
    /** Name of the record, shown in the confirmation copy. */
    name?: string;
    /** Extra warning about what else the delete takes with it. */
    consequence?: string;
};

const {
    action = undefined,
    name = 'this record',
    consequence = undefined,
} = defineProps<Props>();

const open = defineModel<boolean>('open', { required: true });

const processing = ref(false);

function confirm(): void {
    if (!action) {
        return;
    }

    router.delete(action.url, {
        onStart: () => {
            processing.value = true;
        },
        onFinish: () => {
            processing.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete {{ name }}?</DialogTitle>
                <DialogDescription>
                    This cannot be undone.{{
                        consequence ? ` ${consequence}` : ''
                    }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button
                    variant="outline"
                    :disabled="processing"
                    @click="open = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="confirm"
                >
                    <Spinner v-if="processing" />
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
