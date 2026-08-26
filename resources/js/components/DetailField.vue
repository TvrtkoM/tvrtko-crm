<script setup lang="ts">
type Props = {
    label: string;
    /** Rendered when the default slot is not used; `—` when empty. */
    value?: string | number | null;
};

defineProps<Props>();
</script>

<template>
    <div class="grid gap-1">
        <dt
            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
        >
            {{ label }}
        </dt>
        <dd class="text-sm break-words">
            <slot>{{ value === null || value === '' ? '—' : value }}</slot>
        </dd>
    </div>
</template>
