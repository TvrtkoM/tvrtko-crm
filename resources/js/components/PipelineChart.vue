<script setup lang="ts">
import { BarChart3 } from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/format';
import { statusColorClasses } from '@/lib/statusColor';
import { cn } from '@/lib/utils';

type PipelineStage = {
    status: string;
    label: string;
    color: string;
    count: number;
    value: number;
};

const { pipeline } = defineProps<{ pipeline: PipelineStage[] }>();

const maxValue = computed(() =>
    Math.max(1, ...pipeline.map((stage) => stage.value)),
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
                <BarChart3 class="size-4 text-muted-foreground" />
                Pipeline by stage
            </CardTitle>
        </CardHeader>

        <CardContent>
            <ul class="flex flex-col gap-3">
                <li
                    v-for="stage in pipeline"
                    :key="stage.status"
                    class="flex items-center gap-3"
                >
                    <span class="w-28 shrink-0 truncate text-sm text-muted-foreground">
                        {{ stage.label }}
                    </span>

                    <div
                        class="relative h-2.5 flex-1 rounded-full bg-muted"
                        :title="`${stage.label}: ${formatCurrency(stage.value)} · ${stage.count} deals`"
                    >
                        <div
                            class="absolute inset-y-0 left-0 rounded-full"
                            :class="cn(statusColorClasses(stage.color).accent)"
                            :style="{ width: `${(stage.value / maxValue) * 100}%` }"
                        />
                    </div>

                    <span
                        class="w-40 shrink-0 text-right text-sm font-medium tabular-nums"
                    >
                        {{ formatCurrency(stage.value) }}
                        <span class="font-normal text-muted-foreground">
                            · {{ stage.count }}
                        </span>
                    </span>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
