<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle,
    XCircle,
    Receipt,
    TrendingUp,
    ArrowUpRight,
    ArrowDownRight,
} from '@lucide/vue';
import { ref } from 'vue';

interface CourtBreakdownItem {
    id: number;
    name: string;
    sport_type: string;
    total_bookings: number;
    approved_count: number;
    revenue: number;
}

interface ExpenseCategoryItem {
    category: string;
    label: string;
    total_amount: number;
    count: number;
}

interface Reports {
    startDate: string;
    endDate: string;
    totalBookings: number;
    totalRevenue: number;
    totalExpenses: number;
    netProfit: number;
    approvedBookings: number;
    rejectedBookings: number;
    cancelledBookings: number;
    courtBreakdown: CourtBreakdownItem[];
    expenseCategories?: ExpenseCategoryItem[];
}

const props = defineProps<{
    reports: Reports;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
            { title: 'System Analytics & Reports', href: '/admin/reports' },
        ],
    },
});

const start_date = ref(props.reports.startDate);
const end_date = ref(props.reports.endDate);

function filterReports() {
    router.get(
        '/admin/reports',
        { start_date: start_date.value, end_date: end_date.value },
        { preserveState: true, replace: true },
    );
}

function calculateCategoryPercent(amount: number): string {
    const total = props.reports.totalExpenses || 0;

    if (total <= 0) {
        return '0.0%';
    }

    return ((amount / total) * 100).toFixed(1) + '%';
}
</script>

<template>
    <Head title="System Reports - Super Admin" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-6">
        <!-- Header & Date Range Filter -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white"
                >
                    System Reports & Financial Analytics
                </h1>
                <p class="mt-1 text-xs text-neutral-500">
                    Platform-wide booking revenue, operational expenses, and
                    court activity overview.
                </p>
            </div>

            <div
                class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-white p-2 text-xs shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center gap-1.5 px-1 text-neutral-400">
                    <Calendar class="h-3.5 w-3.5" />
                    <span>Range:</span>
                </div>
                <input
                    v-model="start_date"
                    type="date"
                    class="rounded-lg border border-neutral-300 p-1.5 text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                />
                <span class="text-neutral-400">to</span>
                <input
                    v-model="end_date"
                    type="date"
                    class="rounded-lg border border-neutral-300 p-1.5 text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                />
                <button
                    @click="filterReports"
                    class="cursor-pointer rounded-lg bg-emerald-600 px-3.5 py-1.5 font-semibold text-white transition-colors hover:bg-emerald-700"
                >
                    Apply
                </button>
            </div>
        </div>

        <!-- Primary Financial Overview Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Period Revenue -->
            <div
                class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-semibold tracking-wider text-neutral-500 uppercase"
                        >Gross Revenue</span
                    >
                    <div
                        class="rounded-xl bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/40"
                    >
                        <TrendingUp class="h-4 w-4" />
                    </div>
                </div>
                <div
                    class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-500"
                >
                    ₱{{
                        reports.totalRevenue.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })
                    }}
                </div>
                <p class="mt-1 text-[11px] text-neutral-400">
                    Total revenue from confirmed bookings
                </p>
            </div>

            <!-- Total Expenses -->
            <div
                class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-semibold tracking-wider text-neutral-500 uppercase"
                        >Total Expenses</span
                    >
                    <div
                        class="rounded-xl bg-rose-50 p-2 text-rose-600 dark:bg-rose-950/40"
                    >
                        <Receipt class="h-4 w-4" />
                    </div>
                </div>
                <div
                    class="text-3xl font-extrabold text-rose-600 dark:text-rose-500"
                >
                    ₱{{
                        (reports.totalExpenses ?? 0).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })
                    }}
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <p class="text-[11px] text-neutral-400">
                        Recorded operational & facility costs
                    </p>
                    <Link
                        href="/admin/expenses"
                        class="inline-flex items-center gap-0.5 text-[11px] font-semibold text-rose-600 hover:underline dark:text-rose-400"
                    >
                        View Expenses &rarr;
                    </Link>
                </div>
            </div>

            <!-- Net Profit / Margin -->
            <div
                class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-semibold tracking-wider text-neutral-500 uppercase"
                        >Net Operating Balance</span
                    >
                    <div
                        class="rounded-xl p-2"
                        :class="
                            (reports.netProfit ?? 0) >= 0
                                ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/40'
                                : 'bg-amber-50 text-amber-600 dark:bg-amber-950/40'
                        "
                    >
                        <ArrowUpRight
                            v-if="(reports.netProfit ?? 0) >= 0"
                            class="h-4 w-4"
                        />
                        <ArrowDownRight v-else class="h-4 w-4" />
                    </div>
                </div>
                <div
                    class="text-3xl font-extrabold"
                    :class="
                        (reports.netProfit ?? 0) >= 0
                            ? 'text-blue-600 dark:text-blue-400'
                            : 'text-amber-600 dark:text-amber-400'
                    "
                >
                    ₱{{
                        (reports.netProfit ?? 0).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })
                    }}
                </div>
                <p class="mt-1 text-[11px] text-neutral-400">
                    Gross revenue minus total expenses
                </p>
            </div>
        </div>

        <!-- Booking Activity Metric Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div
                class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <span class="text-xs font-medium text-neutral-500"
                        >Total Reservations</span
                    >
                    <div
                        class="mt-0.5 text-2xl font-bold text-neutral-900 dark:text-white"
                    >
                        {{ reports.totalBookings }}
                    </div>
                    <span class="text-[11px] text-neutral-400"
                        >Total bookings scheduled in period</span
                    >
                </div>
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400"
                >
                    <Calendar class="h-5 w-5" />
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <span class="text-xs font-medium text-neutral-500"
                        >Approved / Confirmed</span
                    >
                    <div class="mt-0.5 text-2xl font-bold text-emerald-600">
                        {{ reports.approvedBookings }}
                    </div>
                    <span class="text-[11px] font-medium text-emerald-600"
                        >Completed / Successful bookings</span
                    >
                </div>
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40"
                >
                    <CheckCircle class="h-5 w-5" />
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <span class="text-xs font-medium text-neutral-500"
                        >Rejected / Cancelled</span
                    >
                    <div class="mt-0.5 text-2xl font-bold text-rose-600">
                        {{
                            reports.rejectedBookings + reports.cancelledBookings
                        }}
                    </div>
                    <span class="text-[11px] font-medium text-rose-600"
                        >{{ reports.rejectedBookings }} rejected,
                        {{ reports.cancelledBookings }} cancelled</span
                    >
                </div>
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/40"
                >
                    <XCircle class="h-5 w-5" />
                </div>
            </div>
        </div>

        <!-- Tables Section: Courts & Expenses -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Court Breakdown Table (2 cols on large screen) -->
            <div
                class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-xs lg:col-span-2 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <div>
                        <h3
                            class="text-sm font-bold text-neutral-900 dark:text-white"
                        >
                            Revenue & Volume Breakdown By Court
                        </h3>
                        <p class="text-xs text-neutral-500">
                            Court performance and booking earnings
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-neutral-100 px-2.5 py-1 text-[11px] font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400"
                    >
                        {{ reports.courtBreakdown.length }} Courts
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr
                                class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                            >
                                <th class="px-3 py-3">Court Name</th>
                                <th class="px-3 py-3">Sport Category</th>
                                <th class="px-3 py-3">Reservations</th>
                                <th class="px-3 py-3">Approved</th>
                                <th class="px-3 py-3 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-neutral-100 dark:divide-neutral-800"
                        >
                            <tr
                                v-for="court in reports.courtBreakdown"
                                :key="court.id"
                                class="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                            >
                                <td
                                    class="px-3 py-3 font-bold text-neutral-900 dark:text-white"
                                >
                                    {{ court.name }}
                                </td>
                                <td
                                    class="px-3 py-3 text-neutral-600 dark:text-neutral-300"
                                >
                                    <span
                                        class="rounded-md bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                                    >
                                        {{ court.sport_type }}
                                    </span>
                                </td>
                                <td
                                    class="px-3 py-3 font-medium text-neutral-800 dark:text-neutral-200"
                                >
                                    {{ court.total_bookings }}
                                </td>
                                <td
                                    class="px-3 py-3 font-semibold text-emerald-600"
                                >
                                    {{ court.approved_count }}
                                </td>
                                <td
                                    class="px-3 py-3 text-right font-bold text-neutral-900 dark:text-white"
                                >
                                    ₱{{
                                        court.revenue.toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })
                                    }}
                                </td>
                            </tr>
                            <tr v-if="reports.courtBreakdown.length === 0">
                                <td
                                    colspan="5"
                                    class="py-8 text-center text-xs text-neutral-400"
                                >
                                    No court data found for this period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expense Breakdown By Category (1 col on large screen) -->
            <div
                class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <div>
                        <h3
                            class="text-sm font-bold text-neutral-900 dark:text-white"
                        >
                            Expenses By Category
                        </h3>
                        <p class="text-xs text-neutral-500">
                            Outlay distribution in this date range
                        </p>
                    </div>
                    <Link
                        href="/admin/expenses"
                        class="p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-white"
                        title="Manage Expenses"
                    >
                        <Receipt class="h-4 w-4" />
                    </Link>
                </div>

                <div
                    v-if="
                        reports.expenseCategories &&
                        reports.expenseCategories.length > 0
                    "
                    class="space-y-3"
                >
                    <div
                        v-for="item in reports.expenseCategories"
                        :key="item.category"
                        class="rounded-xl border border-neutral-100 bg-neutral-50/50 p-3 transition-colors hover:bg-neutral-100/50 dark:border-neutral-800 dark:bg-neutral-800/30 dark:hover:bg-neutral-800/60"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span
                                class="font-semibold text-neutral-900 dark:text-white"
                                >{{ item.label }}</span
                            >
                            <span
                                class="font-bold text-rose-600 dark:text-rose-400"
                            >
                                ₱{{
                                    item.total_amount.toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    })
                                }}
                            </span>
                        </div>
                        <div
                            class="mt-1 flex items-center justify-between text-[11px] text-neutral-400"
                        >
                            <span
                                >{{ item.count }}
                                {{
                                    item.count === 1 ? 'entry' : 'entries'
                                }}</span
                            >
                            <span
                                class="font-medium text-neutral-600 dark:text-neutral-300"
                            >
                                {{
                                    calculateCategoryPercent(item.total_amount)
                                }}
                            </span>
                        </div>
                        <!-- Progress bar -->
                        <div
                            class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700"
                        >
                            <div
                                class="h-full rounded-full bg-rose-500 transition-all duration-300"
                                :style="{
                                    width: calculateCategoryPercent(
                                        item.total_amount,
                                    ),
                                }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="space-y-2 py-12 text-center text-xs text-neutral-400"
                >
                    <Receipt
                        class="mx-auto h-8 w-8 text-neutral-300 dark:text-neutral-700"
                    />
                    <p>No expenses recorded in this period.</p>
                    <Link
                        href="/admin/expenses"
                        class="mt-1 inline-block text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                    >
                        + Record an expense
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
