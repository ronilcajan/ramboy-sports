<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Dumbbell,
    CalendarDays,
    DollarSign,
    Users,
    UserCheck,
    CheckCircle,
    XCircle,
    ArrowUpRight,
    TrendingUp,
    FileText,
    Activity,
} from '@lucide/vue';

import { ref } from 'vue';
import BookingDetailModal from '@/components/admin/BookingDetailModal.vue';
import type { BookingDetail } from '@/components/admin/BookingDetailModal.vue';
import DistributionPieChart from '@/components/admin/DistributionPieChart.vue';
import type {
    SportBreakdownItem,
    StatusBreakdownItem,
} from '@/components/admin/DistributionPieChart.vue';
import InteractiveAreaChart from '@/components/admin/InteractiveAreaChart.vue';
import type { DailyTrendItem } from '@/components/admin/InteractiveAreaChart.vue';
import WebsiteTrafficAnalytics from '@/components/admin/WebsiteTrafficAnalytics.vue';
import type { TrafficAnalyticsData } from '@/components/admin/WebsiteTrafficAnalytics.vue';

const activeMainTab = ref<'overview' | 'traffic' | 'courts'>('overview');

interface Stats {
    totalCourts: number;
    activeCourts: number;
    totalBookings: number;
    pendingBookings: number;
    totalRevenue: number;
    totalCustomers: number;
}

interface CourtSummary {
    id: number;
    name: string;
    sport_type: string;
    status: string;
    is_active: boolean;
    staff_count: number;
    total_bookings: number;
    total_revenue: number;
}

interface RecentBooking {
    id: number;
    reference_code?: string;
    customer_name: string;
    email: string;
    phone: string;
    court_name: string;
    sport_type?: string;
    venue_name?: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    status: string;
    notes?: string | null;
    created_at: string;
}

const props = defineProps<{
    stats: Stats;
    dailyTrend: DailyTrendItem[];
    sportTypesBreakdown: SportBreakdownItem[];
    statusBreakdown: StatusBreakdownItem[];
    courtsSummary: CourtSummary[];
    recentBookings: RecentBooking[];
    trafficAnalytics?: TrafficAnalyticsData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
        ],
    },
});

const isDetailModalOpen = ref(false);
const selectedBookingForModal = ref<BookingDetail | null>(null);

function openBookingDetails(booking: RecentBooking) {
    selectedBookingForModal.value = booking as any;
    isDetailModalOpen.value = true;
}

const statusForm = useForm({
    status: '',
});

function quickUpdateStatus(bookingId: number, status: string) {
    statusForm.status = status;
    statusForm.patch(`/admin/bookings/${bookingId}/status`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <div class="w-full space-y-8 p-6">
        <!-- Header Banner -->
        <div
            class="flex flex-col justify-between gap-4 rounded-2xl border border-emerald-900/50 bg-gradient-to-r from-emerald-950 via-neutral-900 to-neutral-900 p-6 text-white shadow-xl md:flex-row md:items-center"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full border border-emerald-500/30 bg-emerald-500/20 px-2.5 py-0.5 text-xs font-bold text-emerald-300"
                    >
                        Super Admin Console
                    </span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">
                    System Administrative Control Hub
                </h1>
                <p class="text-xs text-neutral-400">
                    Manage all facility courts, global bookings, user history,
                    staff assignments, and promotional content.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    href="/admin/courts/create"
                    class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-all hover:bg-emerald-500"
                >
                    <Dumbbell class="h-4 w-4" />
                    <span>Add New Court</span>
                </Link>
            </div>
        </div>

        <!-- Top Level Section Tabs Navigation -->
        <div
            class="flex flex-wrap items-center gap-2 border-b border-neutral-200 pb-3 dark:border-neutral-800"
        >
            <button
                type="button"
                @click="activeMainTab = 'overview'"
                class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all"
                :class="
                    activeMainTab === 'overview'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25'
                        : 'border border-neutral-200 bg-white text-neutral-600 hover:border-emerald-400 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400'
                "
            >
                <TrendingUp class="h-4 w-4" />
                Overview &amp; Revenue
            </button>
            <button
                type="button"
                @click="activeMainTab = 'traffic'"
                class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all"
                :class="
                    activeMainTab === 'traffic'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25'
                        : 'border border-neutral-200 bg-white text-neutral-600 hover:border-emerald-400 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400'
                "
            >
                <Activity class="h-4 w-4" />
                Website Traffic Analytics
                <span
                    class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-extrabold text-emerald-300"
                    >Live</span
                >
            </button>
        </div>

        <!-- TAB 1: OVERVIEW & REVENUE -->
        <div v-if="activeMainTab === 'overview'" class="space-y-8">
            <!-- Metrics Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="space-y-2 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-medium text-neutral-500 dark:text-neutral-400"
                            >Total System Revenue</span
                        >
                        <div
                            class="rounded-xl bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <DollarSign class="h-5 w-5" />
                        </div>
                    </div>
                    <div
                        class="text-2xl font-bold text-neutral-900 dark:text-white"
                    >
                        ₱{{
                            stats.totalRevenue.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                            })
                        }}
                    </div>
                    <span
                        class="flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        <TrendingUp class="h-3.5 w-3.5" /> All confirmed &
                        completed bookings
                    </span>
                </div>

                <div
                    class="space-y-2 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-medium text-neutral-500 dark:text-neutral-400"
                            >Courts Managed</span
                        >
                        <div
                            class="rounded-xl bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <Dumbbell class="h-5 w-5" />
                        </div>
                    </div>
                    <div
                        class="text-2xl font-bold text-neutral-900 dark:text-white"
                    >
                        {{ stats.totalCourts }}
                    </div>
                    <span class="text-[11px] text-neutral-500">
                        {{ stats.activeCourts }} Active Courts operational
                    </span>
                </div>

                <div
                    class="space-y-2 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-medium text-neutral-500 dark:text-neutral-400"
                            >Total Bookings</span
                        >
                        <div
                            class="rounded-xl bg-sky-50 p-2 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400"
                        >
                            <CalendarDays class="h-5 w-5" />
                        </div>
                    </div>
                    <div
                        class="text-2xl font-bold text-neutral-900 dark:text-white"
                    >
                        {{ stats.totalBookings }}
                    </div>
                    <span
                        class="text-[11px] font-medium text-amber-600 dark:text-amber-400"
                    >
                        {{ stats.pendingBookings }} Pending Approval
                    </span>
                </div>

                <div
                    class="space-y-2 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-medium text-neutral-500 dark:text-neutral-400"
                            >Registered Customers</span
                        >
                        <div
                            class="rounded-xl bg-purple-50 p-2 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div
                        class="text-2xl font-bold text-neutral-900 dark:text-white"
                    >
                        {{ stats.totalCustomers }}
                    </div>
                    <span class="text-[11px] text-neutral-500">
                        Active facility user accounts
                    </span>
                </div>
            </div>

            <!-- Super Admin Interactive Charts Section -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Interactive Area Chart (2 Cols) -->
                <div class="lg:col-span-2">
                    <InteractiveAreaChart :daily-trend="props.dailyTrend" />
                </div>

                <!-- Distribution Pie / Donut Chart (1 Col) -->
                <div class="lg:col-span-1">
                    <DistributionPieChart
                        :sport-types-breakdown="props.sportTypesBreakdown"
                        :status-breakdown="props.statusBreakdown"
                    />
                </div>
            </div>

            <!-- Court Overview & Quick Matrix -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Courts Matrix -->
                <div
                    class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm lg:col-span-2 dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                    >
                        <div>
                            <h3
                                class="text-base font-bold text-neutral-900 dark:text-white"
                            >
                                Facility Courts Matrix
                            </h3>
                            <p class="text-xs text-neutral-500">
                                Overview of active court status, staff
                                assignments, and earnings.
                            </p>
                        </div>
                        <Link
                            href="/admin/courts"
                            class="flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                        >
                            <span>Manage All</span>
                            <ArrowUpRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr
                                    class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                                >
                                    <th class="px-3 py-2.5">Court Name</th>
                                    <th class="px-3 py-2.5">Sport</th>
                                    <th class="px-3 py-2.5">Status</th>
                                    <th class="px-3 py-2.5">Assigned Staff</th>
                                    <th class="px-3 py-2.5 text-right">
                                        Revenue
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-neutral-100 dark:divide-neutral-800"
                            >
                                <tr
                                    v-for="court in courtsSummary"
                                    :key="court.id"
                                    class="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                                >
                                    <td
                                        class="px-3 py-3 font-semibold text-neutral-900 dark:text-white"
                                    >
                                        <Link
                                            :href="`/admin/courts/${court.id}`"
                                            class="hover:text-emerald-600"
                                        >
                                            {{ court.name }}
                                        </Link>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-neutral-600 dark:text-neutral-300"
                                    >
                                        {{ court.sport_type }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            :class="[
                                                'rounded-full px-2 py-0.5 text-[10px] font-bold',
                                                court.is_active
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                                            ]"
                                        >
                                            {{ court.status }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-neutral-600 dark:text-neutral-300"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1 rounded-md bg-neutral-100 px-2 py-0.5 font-mono dark:bg-neutral-800"
                                        >
                                            <UserCheck
                                                class="h-3 w-3 text-emerald-600"
                                            />
                                            {{ court.staff_count }} Staff
                                        </span>
                                    </td>
                                    <td
                                        class="px-3 py-3 text-right font-bold text-neutral-900 dark:text-white"
                                    >
                                        ₱{{
                                            court.total_revenue.toLocaleString(
                                                'en-US',
                                                { minimumFractionDigits: 2 },
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Navigation Quick Cards -->
                <div
                    class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <h3
                        class="text-base font-bold text-neutral-900 dark:text-white"
                    >
                        Admin Controls
                    </h3>

                    <div class="space-y-2.5">
                        <Link
                            href="/admin/courts"
                            class="flex items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-3 transition-colors hover:bg-neutral-100 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:bg-neutral-800"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="rounded-lg bg-emerald-100 p-2 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                                >
                                    <Dumbbell class="h-4 w-4" />
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-neutral-900 dark:text-white"
                                    >
                                        Courts & Media
                                    </p>
                                    <p class="text-[10px] text-neutral-500">
                                        Edit courts & hero images
                                    </p>
                                </div>
                            </div>
                            <ArrowUpRight class="h-4 w-4 text-neutral-400" />
                        </Link>

                        <Link
                            href="/admin/bookings"
                            class="flex items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-3 transition-colors hover:bg-neutral-100 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:bg-neutral-800"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="rounded-lg bg-sky-100 p-2 text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                                >
                                    <CalendarDays class="h-4 w-4" />
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-neutral-900 dark:text-white"
                                    >
                                        Bookings Management
                                    </p>
                                    <p class="text-[10px] text-neutral-500">
                                        Approve/reject all reservations
                                    </p>
                                </div>
                            </div>
                            <ArrowUpRight class="h-4 w-4 text-neutral-400" />
                        </Link>

                        <Link
                            href="/admin/staff"
                            class="flex items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-3 transition-colors hover:bg-neutral-100 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:bg-neutral-800"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="rounded-lg bg-purple-100 p-2 text-purple-700 dark:bg-purple-950 dark:text-purple-300"
                                >
                                    <UserCheck class="h-4 w-4" />
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-neutral-900 dark:text-white"
                                    >
                                        Court Staff Accounts
                                    </p>
                                    <p class="text-[10px] text-neutral-500">
                                        Assign staff to courts
                                    </p>
                                </div>
                            </div>
                            <ArrowUpRight class="h-4 w-4 text-neutral-400" />
                        </Link>

                        <Link
                            href="/admin/users"
                            class="flex items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-3 transition-colors hover:bg-neutral-100 dark:border-neutral-800 dark:bg-neutral-800/40 dark:hover:bg-neutral-800"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                                >
                                    <Users class="h-4 w-4" />
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-neutral-900 dark:text-white"
                                    >
                                        User Accounts
                                    </p>
                                    <p class="text-[10px] text-neutral-500">
                                        View customer histories
                                    </p>
                                </div>
                            </div>
                            <ArrowUpRight class="h-4 w-4 text-neutral-400" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings Table -->
            <div
                class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <h3
                        class="text-base font-bold text-neutral-900 dark:text-white"
                    >
                        Recent Bookings Across All Courts
                    </h3>
                    <Link
                        href="/admin/bookings"
                        class="text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                    >
                        View All Bookings &rarr;
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr
                                class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                            >
                                <th class="px-3 py-2.5">Booking ID</th>
                                <th class="px-3 py-2.5">Customer</th>
                                <th class="px-3 py-2.5">Court</th>
                                <th class="px-3 py-2.5">Date</th>
                                <th class="px-3 py-2.5">Time Slots</th>
                                <th class="px-3 py-2.5">Total</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5">Receipt</th>
                                <th class="px-3 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-neutral-100 dark:divide-neutral-800"
                        >
                            <tr
                                v-for="b in recentBookings"
                                :key="b.id"
                                @click="openBookingDetails(b)"
                                class="cursor-pointer transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                            >
                                <td
                                    class="px-3 py-3 font-mono font-bold text-neutral-900 dark:text-white"
                                >
                                    #{{ b.id }}
                                </td>
                                <td
                                    class="px-3 py-3 font-medium text-neutral-900 dark:text-white"
                                >
                                    {{ b.customer_name }}
                                </td>
                                <td
                                    class="px-3 py-3 text-neutral-600 dark:text-neutral-300"
                                >
                                    {{ b.court_name }}
                                </td>
                                <td
                                    class="px-3 py-3 font-semibold text-emerald-600 underline decoration-dotted dark:text-emerald-400"
                                >
                                    {{ b.date }}
                                </td>
                                <td
                                    class="px-3 py-3 font-mono text-[11px] text-neutral-500"
                                >
                                    {{
                                        b.time_slots
                                            ? b.time_slots.join(', ')
                                            : 'N/A'
                                    }}
                                </td>
                                <td
                                    class="px-3 py-3 font-bold text-neutral-900 dark:text-white"
                                >
                                    ₱{{ b.total_price }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-0.5 text-[10px] font-bold capitalize',
                                            b.status === 'approved' ||
                                            b.status === 'confirmed'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : b.status === 'pending'
                                                  ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                                  : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                                        ]"
                                    >
                                        {{ b.status }}
                                    </span>
                                </td>
                                <td class="px-3 py-3" @click.stop>
                                    <a
                                        v-if="b.receipt_url"
                                        :href="b.receipt_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                    >
                                        <FileText class="h-3.5 w-3.5" /> View
                                    </a>
                                    <span
                                        v-else
                                        class="text-[11px] text-neutral-400"
                                        >N/A</span
                                    >
                                </td>
                                <td class="px-3 py-3 text-right" @click.stop>
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <button
                                            v-if="b.status === 'pending'"
                                            @click="
                                                quickUpdateStatus(
                                                    b.id,
                                                    'approved',
                                                )
                                            "
                                            class="p-1 text-emerald-600 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400"
                                            title="Approve Booking"
                                        >
                                            <CheckCircle class="h-4 w-4" />
                                        </button>
                                        <button
                                            v-if="b.status === 'pending'"
                                            @click="
                                                quickUpdateStatus(
                                                    b.id,
                                                    'rejected',
                                                )
                                            "
                                            class="p-1 text-rose-600 transition-colors hover:text-rose-700 dark:hover:text-rose-400"
                                            title="Reject Booking"
                                        >
                                            <XCircle class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openBookingDetails(b)"
                                            class="p-1 text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                                            title="View Full Booking Details"
                                        >
                                            <ArrowUpRight class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- END TAB 1 OVERVIEW -->

        <!-- TAB 2: WEBSITE TRAFFIC ANALYTICS -->
        <div v-if="activeMainTab === 'traffic'">
            <WebsiteTrafficAnalytics
                v-if="trafficAnalytics"
                :analytics="trafficAnalytics"
            />
        </div>

        <!-- Booking Details Modal -->
        <BookingDetailModal
            :is-open="isDetailModalOpen"
            :booking="selectedBookingForModal"
            update-route-prefix="/admin/bookings"
            @close="isDetailModalOpen = false"
        />
    </div>
</template>
