<script setup lang="ts">
import { PieChart as PieChartIcon } from '@lucide/vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { ref, computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

export interface SportBreakdownItem {
    label: string;
    count: number;
    revenue: number;
}

export interface StatusBreakdownItem {
    label: string;
    count: number;
}

const props = defineProps<{
    sportTypesBreakdown: SportBreakdownItem[];
    statusBreakdown: StatusBreakdownItem[];
}>();

const activeTab = ref<'sports' | 'statuses'>('sports');

const colors = [
    '#10b981', // emerald-500
    '#0284c7', // sky-600
    '#f59e0b', // amber-500
    '#a855f7', // purple-500
    '#f43f5e', // rose-500
    '#6366f1', // indigo-500
];

const currentItems = computed(() => {
    if (activeTab.value === 'sports') {
        return props.sportTypesBreakdown || [];
    }

    return props.statusBreakdown || [];
});

const totalCount = computed(() => {
    return currentItems.value.reduce((sum, item) => sum + item.count, 0);
});

const chartData = computed(() => {
    const labels = currentItems.value.map((item) => item.label);
    const data = currentItems.value.map((item) => item.count);

    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor: colors.slice(0, labels.length),
                borderColor: 'rgba(23, 23, 23, 0.8)',
                borderWidth: 2,
                hoverOffset: 6,
            },
        ],
    };
});

const chartOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: 'rgba(23, 23, 23, 0.95)',
                titleColor: '#f5f5f5',
                bodyColor: '#e5e5e5',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                padding: 10,
                callbacks: {
                    label: (context: any) => {
                        const val = context.parsed;
                        const pct =
                            totalCount.value > 0
                                ? ((val / totalCount.value) * 100).toFixed(1)
                                : 0;

                        return ` ${context.label}: ${val} (${pct}%)`;
                    },
                },
            },
        },
    };
});
</script>

<template>
    <div
        class="flex flex-col justify-between space-y-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900"
    >
        <!-- Header -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div
                    class="flex items-center gap-2 text-xs font-semibold text-neutral-400"
                >
                    <PieChartIcon class="h-4 w-4 text-sky-500" />
                    <span>Distribution Chart</span>
                </div>

                <!-- Tab Toggle -->
                <div
                    class="flex items-center rounded-xl border border-neutral-200 bg-neutral-100 p-1 text-xs font-bold dark:border-neutral-700 dark:bg-neutral-800"
                >
                    <button
                        @click="activeTab = 'sports'"
                        :class="[
                            'rounded-lg px-2.5 py-1 transition-all',
                            activeTab === 'sports'
                                ? 'bg-white text-emerald-600 shadow-xs dark:bg-neutral-700 dark:text-emerald-400'
                                : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white',
                        ]"
                    >
                        Sports
                    </button>
                    <button
                        @click="activeTab = 'statuses'"
                        :class="[
                            'rounded-lg px-2.5 py-1 transition-all',
                            activeTab === 'statuses'
                                ? 'bg-white text-emerald-600 shadow-xs dark:bg-neutral-700 dark:text-emerald-400'
                                : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white',
                        ]"
                    >
                        Status
                    </button>
                </div>
            </div>

            <div>
                <h3
                    class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    {{
                        activeTab === 'sports'
                            ? 'Sport Category Share'
                            : 'Booking Status Breakdown'
                    }}
                </h3>
                <p class="text-xs text-neutral-500">
                    Percentage distribution across facility operations.
                </p>
            </div>
        </div>

        <!-- Donut Canvas & Central Badge -->
        <div class="relative my-2 flex h-56 w-full items-center justify-center">
            <Doughnut :data="chartData" :options="chartOptions" />
            <div
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
            >
                <span
                    class="text-2xl font-black tracking-tight text-neutral-900 dark:text-white"
                    >{{ totalCount }}</span
                >
                <span class="text-[10px] font-bold text-neutral-400 uppercase"
                    >Total Units</span
                >
            </div>
        </div>

        <!-- Breakdown Legend Grid -->
        <div
            class="grid grid-cols-2 gap-2 border-t border-neutral-100 pt-2 dark:border-neutral-800"
        >
            <div
                v-for="(item, idx) in currentItems"
                :key="item.label"
                class="flex items-center justify-between rounded-xl border border-neutral-800/80 bg-neutral-900/90 px-3 py-2 text-xs shadow-sm dark:bg-neutral-950/90"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                        :style="{
                            backgroundColor: colors[idx % colors.length],
                        }"
                    ></span>
                    <span
                        class="truncate font-semibold text-neutral-200 dark:text-neutral-200"
                        >{{ item.label }}</span
                    >
                </div>
                <span
                    class="ml-2 font-mono font-bold text-white dark:text-white"
                    >{{ item.count }}</span
                >
            </div>
        </div>
    </div>
</template>
