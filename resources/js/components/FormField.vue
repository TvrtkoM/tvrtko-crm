<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

type Props = {
    /** Id of the control this label points at; also exposed to the default slot. */
    id: string;
    label: string;
    /** Message from `form.errors` for this field, if any. */
    error?: string;
    hint?: string;
    required?: boolean;
};

defineProps<Props>();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-muted-foreground">*</span>
        </Label>

        <slot :id="id" />

        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>

        <InputError :message="error" />
    </div>
</template>
