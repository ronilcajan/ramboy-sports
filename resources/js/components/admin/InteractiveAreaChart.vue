<script setup lang="ts">
import { AreaChart as AreaChartIcon } from '@lucide/vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { ref, computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

export interface DailyTrendItem {
    date: string;
    label: string;
    confirmed: number;
    pending: number;
    revenue: number;
}

const props = defineProps<{
    dailyTrend: DailyTrendItem[];
}>();

const selectedTimeframe = ref<'7' | '30' | '90'>('7');

const timeframeOptions = [
    { value: '7', label: 'Last 7 days' },
    { value: '30', label: 'Last 30 days' },
    { value: '90', label: 'Last 3 months' },
];

const filteredTrend = computed(() => {
    const limit = parseInt(selectedTimeframe.value, 10);
    const data = props.dailyTrend || [];

    return data.slice(-limit);
});

const chartData = computed(() => {
    const labels = filteredTrend.value.map((item) => item.label);
    const confirmedData = filteredTrend.value.map((item) => item.confirmed);
    const revenueData = filteredTrend.value.map((item) => item.revenue);

    return {
        labels,
        datasets: [
            {
                label: 'Confirmed Bookings',
                data: confirmedData,
                borderColor: '#10b981', // emerald-500
                backgroundColor: (context: any) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.45)');
                    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

                    return gradient;
                },
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#10b981',
                yAxisID: 'yBookings',
            },
            {
                label: 'Revenue (₱)',
                data: revenueData,
                borderColor: '#06b6d4', // cyan-500
                backgroundColor: (context: any) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(6, 182, 212, 0.35)');
                    gradient.addColorStop(1, 'rgba(6, 182, 212, 0.0)');

                    return gradient;
                },
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#06b6d4',
                yAxisID: 'yRevenue',
            },
        ],
    };
});

const chartOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index' as const,
            intersect: false,
        },
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
                padding: 12,
                boxPadding: 6,
                usePointStyle: true,
                callbacks: {
                    label: (context: any) => {
                        const label = context.dataset.label || '';
                        const val = context.parsed.y;

                        if (label.includes('Revenue')) {
                            return `${label}: ₱${val.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                        }

                        return `${label}: ${val}`;
                    },
                },
            },
        },
        scales: {
            x: {
                grid: {
                    color: 'rgba(255, 255, 255, 0.03)',
                },
                ticks: {
                    color: '#888888',
                    font: {
                        size: 11,
                    },
                    maxTicksLimit: 8,
                },
            },
            yBookings: {
                type: 'linear' as const,
                display: true,
                position: 'left' as const,
                grid: {
                    color: 'rgba(255, 255, 255, 0.05)',
                },
                ticks: {
                    color: '#888888',
                    font: { size: 11 },
                    precision: 0,
                },
            },
            yRevenue: {
                type: 'linear' as const,
                display: false,
                position: 'right' as const,
                grid: {
                    drawOnChartArea: false,
                },
            },
        },
    };
});
</script>

<template>
    <div
        class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-md dark:border-neutral-800 dark:bg-neutral-900"
    >
        <!-- Chart Header -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div class="space-y-1">
                <div
                    class="flex items-center gap-2 text-xs font-semibold text-neutral-400"
                >
                    <AreaChartIcon class="h-4 w-4 text-emerald-500" />
                    <span>Area Chart</span>
                </div>
                <h3
                    class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    Area Chart - Interactive
                </h3>
                <p class="text-xs text-neutral-500">
                    Showing confirmed bookings & revenue performance for the
                    {{
                        timeframeOptions
                            .find((o) => o.value === selectedTimeframe)
                            ?.label.toLowerCase()
                    }}.
                </p>
            </div>

            <!-- Timeframe Filter Dropdown -->
            <div>
                <select
                    v-model="selectedTimeframe"
                    class="cursor-pointer rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-xs font-bold text-neutral-800 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
                >
                    <option
                        v-for="opt in timeframeOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Chart Body -->
        <div class="relative h-72 w-full pt-2">
            <Line :data="chartData" :options="chartOptions" />
        </div>

        <!-- Bottom Legend Dots -->
        <div
            class="flex items-center justify-center gap-6 pt-2 text-xs font-semibold"
        >
            <div class="flex items-center gap-2">
                <span
                    class="h-2.5 w-2.5 rounded-sm bg-emerald-500 shadow-sm shadow-emerald-500/50"
                ></span>
                <span class="text-neutral-700 dark:text-neutral-300"
                    >Confirmed Bookings</span
                >
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="h-2.5 w-2.5 rounded-sm bg-cyan-500 shadow-sm shadow-cyan-500/50"
                ></span>
                <span class="text-neutral-700 dark:text-neutral-300"
                    >Revenue Stream (₱)</span
                >
            </div>
        </div>
    </div>
</template>
