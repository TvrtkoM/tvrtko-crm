<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Option = {
    value: string | number;
    label: string;
};

type Props = {
    id?: string;
    options: Option[];
    placeholder?: string;
    /** Adds a "none" entry that maps back to `null`. */
    nullable?: boolean;
    nullLabel?: string;
    disabled?: boolean;
    /** Cast the picked value back to a number (relationship pickers). */
    numeric?: boolean;
};

const {
    id = undefined,
    options,
    placeholder = 'Select…',
    nullable = false,
    nullLabel = 'None',
    disabled = false,
    numeric = false,
} = defineProps<Props>();

const model = defineModel<string | number | null>();

/**
 * Reka's Select cannot hold an empty string, so "no selection" travels through
 * a sentinel value that is translated back to `null` on the way out.
 */
const NONE = '__none__';

const selected = computed<string | undefined>({
    get: () => {
        if (model.value === null || model.value === undefined) {
            return nullable ? NONE : undefined;
        }

        return String(model.value);
    },
    set: (value) => {
        if (value === undefined || value === NONE) {
            model.value = null;

            return;
        }

        model.value = numeric ? Number(value) : value;
    },
});
</script>

<template>
    <Select v-model="selected" :disabled="disabled">
        <SelectTrigger :id="id" class="w-full">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-if="nullable" :value="NONE">
                {{ nullLabel }}
            </SelectItem>
            <SelectItem
                v-for="option in options"
                :key="option.value"
                :value="String(option.value)"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
