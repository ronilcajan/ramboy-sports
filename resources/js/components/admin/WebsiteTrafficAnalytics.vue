<script setup lang="ts">
import {
    Activity,
    Eye,
    Users,
    Clock,
    TrendingUp,
    Globe,
    Smartphone,
    Compass,
    BarChart3,
    Sparkles,
    LayoutGrid,
} from '@lucide/vue';
import { ref, computed } from 'vue';

export interface TrafficSummary {
    totalPageViews: number;
    uniqueVisitors: number;
    avgSessionTime: string;
    bounceRate: string;
    viewsGrowth: string;
    visitorsGrowth: string;
}

export interface TrafficTrendPoint {
    date: string;
    label: string;
    views: number;
    visitors: number;
}

export interface TopPageItem {
    name: string;
    url: string;
    category: string;
    views: number;
    visitors: number;
    bounceRate: string;
}

export interface DeviceBreakdownItem {
    device: string;
    percentage: number;
    count: number;
    color: string;
}

export interface SourceBreakdownItem {
    source: string;
    percentage: number;
    color: string;
}

export interface TrafficAnalyticsData {
    summary: TrafficSummary;
    trend: TrafficTrendPoint[];
    topPages: TopPageItem[];
    deviceBreakdown: DeviceBreakdownItem[];
    sourcesBreakdown: SourceBreakdownItem[];
}

const props = defineProps<{
    analytics: TrafficAnalyticsData;
}>();

const timeRange = ref<'7d' | '30d' | '90d'>('30d');
const activeAnalyticsTab = ref<'all' | 'trend' | 'pages' | 'devices'>('all');
const hoveredIndex = ref<number | null>(null);

// Filter trend based on timeframe selector
const filteredTrend = computed(() => {
    if (timeRange.value === '7d') {
        return props.analytics.trend.slice(-7);
    }

    return props.analytics.trend;
});

// Calculate max views for SVG chart scaling
const maxViews = computed(() => {
    const max = Math.max(...filteredTrend.value.map((t) => t.views), 100);

    return Math.ceil(max / 100) * 100;
});

// SVG Chart Path & Points Generation
const chartWidth = 700;
const chartHeight = 220;

const calculatedTrendPoints = computed(() => {
    const data = filteredTrend.value;

    if (data.length === 0) {
        return [];
    }

    return data.map((d, i) => {
        const x = (i / (data.length - 1)) * chartWidth;
        const yViews =
            chartHeight - (d.views / maxViews.value) * (chartHeight - 30) - 15;
        const yVisitors =
            chartHeight -
            (d.visitors / maxViews.value) * (chartHeight - 30) -
            15;

        return {
            ...d,
            index: i,
            x,
            yViews,
            yVisitors,
        };
    });
});

const activeHoverPoint = computed(() =>
    hoveredIndex.value !== null
        ? calculatedTrendPoints.value[hoveredIndex.value]
        : null,
);

const pointsViews = computed(() => {
    return calculatedTrendPoints.value
        .map((p) => `${p.x.toFixed(1)},${p.yViews.toFixed(1)}`)
        .join(' ');
});

const pointsVisitors = computed(() => {
    return calculatedTrendPoints.value
        .map((p) => `${p.x.toFixed(1)},${p.yVisitors.toFixed(1)}`)
        .join(' ');
});

const areaPathViews = computed(() => {
    if (!pointsViews.value) {
        return '';
    }

    const firstX = 0;
    const lastX = chartWidth;

    return `M ${firstX},${chartHeight} L ${pointsViews.value.replace(/ /g, ' L ')} L ${lastX},${chartHeight} Z`;
});

function formatNumber(num: number): string {
    return num.toLocaleString('en-US');
}
</script>

<template>
    <div class="space-y-6">
        <!-- ── Header Banner ─────────────────────────────────── -->
        <div
            class="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-emerald-900 via-teal-900 to-neutral-900 p-6 text-white shadow-xl sm:p-8"
        >
            <!-- Background Glow Blobs -->
            <div
                class="pointer-events-none absolute -top-12 -right-12 size-64 rounded-full bg-emerald-500/10 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-16 -left-16 size-64 rounded-full bg-teal-500/10 blur-3xl"
            />

            <div
                class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-500/15 px-3 py-1 text-xs font-bold tracking-wider text-emerald-300 uppercase backdrop-blur-md"
                    >
                        <Sparkles class="size-3.5" />
                        <span>Super Admin Telemetry</span>
                    </div>
                    <h2
                        class="mt-3 flex items-center gap-3 text-2xl font-black tracking-tight text-white sm:text-3xl"
                    >
                        <Activity class="size-7 text-emerald-400" />
                        Website Traffic &amp; Visitor Analytics
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm text-emerald-100/80">
                        Real-time visitor traffic monitoring, location page
                        engagements, device breakdown, and customer acquisition
                        channels across the platform.
                    </p>
                </div>

                <!-- Time Period Selector Buttons -->
                <div
                    class="flex shrink-0 items-center gap-1.5 rounded-2xl border border-white/10 bg-white/10 p-1.5 backdrop-blur-md"
                >
                    <button
                        type="button"
                        @click="timeRange = '7d'"
                        class="cursor-pointer rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all"
                        :class="
                            timeRange === '7d'
                                ? 'bg-emerald-500 text-white shadow-md'
                                : 'text-emerald-100 hover:bg-white/10'
                        "
                    >
                        Last 7 Days
                    </button>
                    <button
                        type="button"
                        @click="timeRange = '30d'"
                        class="cursor-pointer rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all"
                        :class="
                            timeRange === '30d'
                                ? 'bg-emerald-500 text-white shadow-md'
                                : 'text-emerald-100 hover:bg-white/10'
                        "
                    >
                        Last 30 Days
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Sub-tab Navigation Bar ──────────────────────────── -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-3 dark:border-neutral-800"
        >
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'all'"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                    :class="
                        activeAnalyticsTab === 'all'
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                            : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700'
                    "
                >
                    <LayoutGrid class="size-3.5" />
                    All Analytics Overview
                </button>
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'trend'"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                    :class="
                        activeAnalyticsTab === 'trend'
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                            : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700'
                    "
                >
                    <BarChart3 class="size-3.5" />
                    Traffic Trend &amp; Visitors
                </button>
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'pages'"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                    :class="
                        activeAnalyticsTab === 'pages'
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                            : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700'
                    "
                >
                    <Globe class="size-3.5" />
                    Popular Pages &amp; Conversions
                </button>
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'devices'"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                    :class="
                        activeAnalyticsTab === 'devices'
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                            : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700'
                    "
                >
                    <Smartphone class="size-3.5" />
                    Devices &amp; Acquisition
                </button>
            </div>
        </div>

        <!-- ── Summary KPI Cards (Visible in 'all' and 'trend') ── -->
        <div
            v-if="
                activeAnalyticsTab === 'all' || activeAnalyticsTab === 'trend'
            "
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <!-- Total Page Views Card -->
            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-neutral-400 uppercase"
                        >Total Page Views</span
                    >
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                    >
                        <Eye class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span
                        class="text-2xl font-black text-neutral-900 tabular-nums dark:text-white"
                    >
                        {{ formatNumber(analytics.summary.totalPageViews) }}
                    </span>
                    <span
                        class="inline-flex items-center gap-0.5 text-xs font-black text-emerald-600 dark:text-emerald-400"
                    >
                        <TrendingUp class="size-3.5" />
                        {{ analytics.summary.viewsGrowth }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">
                    Across all catalog &amp; court pages
                </p>
            </div>

            <!-- Unique Visitors Card -->
            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-neutral-400 uppercase"
                        >Unique Visitors</span
                    >
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400"
                    >
                        <Users class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span
                        class="text-2xl font-black text-neutral-900 tabular-nums dark:text-white"
                    >
                        {{ formatNumber(analytics.summary.uniqueVisitors) }}
                    </span>
                    <span
                        class="inline-flex items-center gap-0.5 text-xs font-black text-teal-600 dark:text-teal-400"
                    >
                        <TrendingUp class="size-3.5" />
                        {{ analytics.summary.visitorsGrowth }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">
                    Unique IP &amp; session users
                </p>
            </div>

            <!-- Avg Session Duration Card -->
            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-neutral-400 uppercase"
                        >Avg. Session Time</span
                    >
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400"
                    >
                        <Clock class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span
                        class="text-2xl font-black text-neutral-900 tabular-nums dark:text-white"
                    >
                        {{ analytics.summary.avgSessionTime }}
                    </span>
                    <span
                        class="inline-flex items-center gap-0.5 text-xs font-bold text-violet-600 dark:text-violet-400"
                    >
                        +8.2% avg
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">
                    Time spent exploring courts
                </p>
            </div>

            <!-- Bounce Rate Card -->
            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-bold tracking-wider text-neutral-400 uppercase"
                        >Bounce Rate</span
                    >
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                    >
                        <Compass class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span
                        class="text-2xl font-black text-neutral-900 tabular-nums dark:text-white"
                    >
                        {{ analytics.summary.bounceRate }}
                    </span>
                    <span
                        class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                    >
                        -3.4% lower
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">
                    Single-page exits ratio
                </p>
            </div>
        </div>

        <!-- ── Interactive Traffic Trend Chart with Floating Tooltip ── -->
        <div
            v-if="
                activeAnalyticsTab === 'all' || activeAnalyticsTab === 'trend'
            "
            class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div
                class="flex flex-col justify-between gap-4 border-b border-neutral-100 pb-4 sm:flex-row sm:items-center dark:border-neutral-800"
            >
                <div>
                    <h3
                        class="flex items-center gap-2 text-base font-black text-neutral-900 dark:text-white"
                    >
                        <BarChart3 class="size-5 text-emerald-500" />
                        Daily Page Views &amp; Visitor Trend
                    </h3>
                    <p class="mt-0.5 text-xs text-neutral-500">
                        Hover over any date point to view real-time daily
                        metrics.
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-4 text-xs font-bold">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-emerald-500" />
                        <span class="text-neutral-700 dark:text-neutral-300"
                            >Page Views</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-teal-400" />
                        <span class="text-neutral-700 dark:text-neutral-300"
                            >Unique Visitors</span
                        >
                    </div>
                </div>
            </div>

            <!-- SVG Vector Chart with Interactive Tooltip Container -->
            <div class="relative mt-6 w-full">
                <!-- Floating Glassmorphic Tooltip Card -->
                <div
                    v-if="activeHoverPoint"
                    class="pointer-events-none absolute z-30 mb-3 flex min-w-[170px] -translate-x-1/2 -translate-y-full transform flex-col rounded-2xl border border-neutral-200 bg-white/95 p-3.5 text-xs shadow-xl backdrop-blur-md transition-all duration-75 dark:border-neutral-700 dark:bg-neutral-900/95"
                    :style="{
                        left: `${(activeHoverPoint.x / chartWidth) * 100}%`,
                        top: `${Math.min(activeHoverPoint.yViews, activeHoverPoint.yVisitors)}px`,
                    }"
                >
                    <div
                        class="mb-2 flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-800"
                    >
                        <span
                            class="text-xs font-black text-neutral-900 dark:text-white"
                            >{{ activeHoverPoint.label }}</span
                        >
                        <span class="font-mono text-[10px] text-neutral-400">{{
                            activeHoverPoint.date
                        }}</span>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-4">
                            <span
                                class="flex items-center gap-1.5 font-bold text-neutral-600 dark:text-neutral-300"
                            >
                                <span
                                    class="size-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"
                                />
                                Views:
                            </span>
                            <strong
                                class="text-xs font-black text-emerald-600 tabular-nums dark:text-emerald-400"
                                >{{
                                    formatNumber(activeHoverPoint.views)
                                }}</strong
                            >
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span
                                class="flex items-center gap-1.5 font-bold text-neutral-600 dark:text-neutral-300"
                            >
                                <span
                                    class="size-2 rounded-full bg-teal-400 shadow-sm shadow-teal-400/50"
                                />
                                Visitors:
                            </span>
                            <strong
                                class="text-xs font-black text-teal-600 tabular-nums dark:text-teal-400"
                                >{{
                                    formatNumber(activeHoverPoint.visitors)
                                }}</strong
                            >
                        </div>
                    </div>
                </div>

                <svg
                    class="h-56 w-full overflow-visible"
                    :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                    preserveAspectRatio="none"
                >
                    <defs>
                        <linearGradient
                            id="viewsGradient"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stop-color="#10b981"
                                stop-opacity="0.35"
                            />
                            <stop
                                offset="100%"
                                stop-color="#10b981"
                                stop-opacity="0.0"
                            />
                        </linearGradient>
                    </defs>

                    <!-- Horizontal Gridlines -->
                    <line
                        x1="0"
                        y1="20"
                        :x2="chartWidth"
                        y2="20"
                        stroke="currentColor"
                        class="text-neutral-200 dark:text-neutral-800"
                        stroke-dasharray="4 4"
                    />
                    <line
                        x1="0"
                        y1="80"
                        :x2="chartWidth"
                        y2="80"
                        stroke="currentColor"
                        class="text-neutral-200 dark:text-neutral-800"
                        stroke-dasharray="4 4"
                    />
                    <line
                        x1="0"
                        y1="140"
                        :x2="chartWidth"
                        y2="140"
                        stroke="currentColor"
                        class="text-neutral-200 dark:text-neutral-800"
                        stroke-dasharray="4 4"
                    />
                    <line
                        x1="0"
                        y1="200"
                        :x2="chartWidth"
                        y2="200"
                        stroke="currentColor"
                        class="text-neutral-200 dark:text-neutral-800"
                    />

                    <!-- Views Area -->
                    <path :d="areaPathViews" fill="url(#viewsGradient)" />

                    <!-- Vertical Hover Line -->
                    <line
                        v-if="activeHoverPoint"
                        :x1="activeHoverPoint.x"
                        y1="10"
                        :x2="activeHoverPoint.x"
                        :y2="chartHeight"
                        stroke="#10b981"
                        stroke-width="1.5"
                        stroke-dasharray="3 3"
                    />

                    <!-- Views Line -->
                    <polyline
                        fill="none"
                        stroke="#10b981"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :points="pointsViews"
                    />

                    <!-- Visitors Line -->
                    <polyline
                        fill="none"
                        stroke="#2dd4bf"
                        stroke-width="2.5"
                        stroke-dasharray="5 3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :points="pointsVisitors"
                    />

                    <!-- Active Point Dots -->
                    <g v-if="activeHoverPoint">
                        <circle
                            :cx="activeHoverPoint.x"
                            :cy="activeHoverPoint.yViews"
                            r="6"
                            fill="#10b981"
                            stroke="#ffffff"
                            stroke-width="2"
                        />
                        <circle
                            :cx="activeHoverPoint.x"
                            :cy="activeHoverPoint.yVisitors"
                            r="5"
                            fill="#2dd4bf"
                            stroke="#ffffff"
                            stroke-width="2"
                        />
                    </g>

                    <!-- Invisible Hover Hitboxes -->
                    <rect
                        v-for="(p, i) in calculatedTrendPoints"
                        :key="p.date"
                        :x="
                            Math.max(
                                0,
                                p.x -
                                    chartWidth /
                                        (calculatedTrendPoints.length * 2),
                            )
                        "
                        y="0"
                        :width="chartWidth / calculatedTrendPoints.length"
                        :height="chartHeight"
                        fill="transparent"
                        class="cursor-pointer"
                        @mouseenter="hoveredIndex = i"
                        @mouseleave="hoveredIndex = null"
                    />
                </svg>

                <!-- Date Labels Below Chart -->
                <div
                    class="mt-3 flex justify-between px-1 text-[10px] font-bold text-neutral-400"
                >
                    <span
                        v-for="(item, idx) in filteredTrend"
                        :key="item.date"
                        v-show="
                            idx % Math.ceil(filteredTrend.length / 8) === 0 ||
                            idx === filteredTrend.length - 1
                        "
                    >
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Top Pages & Traffic Breakdown (Visible in 'all', 'pages', 'devices') ── -->
        <div
            v-if="
                activeAnalyticsTab === 'all' ||
                activeAnalyticsTab === 'pages' ||
                activeAnalyticsTab === 'devices'
            "
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
        >
            <!-- Top Visited Pages Table (Visible in 'all' and 'pages') -->
            <div
                v-if="
                    activeAnalyticsTab === 'all' ||
                    activeAnalyticsTab === 'pages'
                "
                :class="
                    activeAnalyticsTab === 'pages'
                        ? 'lg:col-span-3'
                        : 'lg:col-span-2'
                "
                class="flex flex-col justify-between overflow-hidden rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <div
                        class="flex items-center justify-between border-b border-neutral-100 pb-4 dark:border-neutral-800"
                    >
                        <div>
                            <h3
                                class="flex items-center gap-2 text-base font-black text-neutral-900 dark:text-white"
                            >
                                <Globe class="size-5 text-emerald-500" />
                                Most Visited Website Pages
                            </h3>
                            <p class="mt-0.5 text-xs text-neutral-500">
                                Most-viewed public routes over the last 30 days.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr
                                    class="border-b border-neutral-200 text-[10px] font-extrabold tracking-wider text-neutral-400 uppercase dark:border-neutral-800"
                                >
                                    <th class="px-3 py-2.5">Page Name</th>
                                    <th class="px-3 py-2.5">Category</th>
                                    <th class="px-3 py-2.5 text-right">
                                        Views
                                    </th>
                                    <th class="px-3 py-2.5 text-right">
                                        Visitors
                                    </th>
                                    <th class="px-3 py-2.5 text-right">
                                        Bounce
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-neutral-100 font-semibold dark:divide-neutral-800"
                            >
                                <tr
                                    v-for="page in analytics.topPages"
                                    :key="page.name"
                                    class="transition-colors hover:bg-neutral-50/60 dark:hover:bg-neutral-800/40"
                                >
                                    <td class="px-3 py-3">
                                        <p
                                            class="font-bold text-neutral-900 dark:text-white"
                                        >
                                            {{ page.name }}
                                        </p>
                                        <p
                                            class="mt-0.5 font-mono text-[10px] text-neutral-400"
                                        >
                                            {{ page.url }}
                                        </p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-bold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400"
                                        >
                                            {{ page.category }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right font-bold text-neutral-900 tabular-nums dark:text-white"
                                    >
                                        {{ formatNumber(page.views) }}
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right font-bold text-neutral-500 tabular-nums"
                                    >
                                        {{ formatNumber(page.visitors) }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <span
                                            class="rounded-md bg-emerald-50 px-2 py-0.5 font-black text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                                        >
                                            {{ page.bounceRate }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Device & Traffic Sources Widgets (Visible in 'all' and 'devices') -->
            <div
                v-if="
                    activeAnalyticsTab === 'all' ||
                    activeAnalyticsTab === 'devices'
                "
                :class="
                    activeAnalyticsTab === 'devices'
                        ? 'grid grid-cols-1 gap-6 space-y-0 md:grid-cols-2 lg:col-span-3'
                        : 'space-y-6'
                "
            >
                <!-- Device Breakdown Card -->
                <div
                    class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-extrabold tracking-wider text-neutral-900 uppercase dark:text-white"
                    >
                        <Smartphone class="size-4 text-emerald-500" />
                        Device Breakdown
                    </h3>
                    <p class="mt-0.5 text-xs text-neutral-500">
                        Platform visits by user device category.
                    </p>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="dev in analytics.deviceBreakdown"
                            :key="dev.device"
                        >
                            <div
                                class="mb-1 flex items-center justify-between text-xs font-bold"
                            >
                                <span
                                    class="text-neutral-700 dark:text-neutral-300"
                                    >{{ dev.device }}</span
                                >
                                <span
                                    class="font-extrabold text-neutral-900 dark:text-white"
                                    >{{ dev.percentage }}%</span
                                >
                            </div>
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="dev.color"
                                    :style="{ width: `${dev.percentage}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Traffic Sources Card -->
                <div
                    class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-extrabold tracking-wider text-neutral-900 uppercase dark:text-white"
                    >
                        <Compass class="size-4 text-teal-500" />
                        Traffic Sources
                    </h3>
                    <p class="mt-0.5 text-xs text-neutral-500">
                        Origin of incoming platform visitors.
                    </p>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div
                            v-for="src in analytics.sourcesBreakdown"
                            :key="src.source"
                            class="rounded-2xl border border-neutral-100 bg-neutral-50 p-3 dark:border-neutral-800 dark:bg-neutral-800/50"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                            >
                                {{ src.source }}
                            </p>
                            <p
                                class="mt-1 text-lg font-black text-neutral-900 dark:text-white"
                            >
                                {{ src.percentage }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
