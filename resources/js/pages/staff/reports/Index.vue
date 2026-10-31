<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface CourtItem {
    id: number;
    name: string;
}

interface CourtBreakdownItem {
    id: number;
    name: string;
    sport_type: string;
    total_bookings: number;
    approved_count: number;
    revenue: number;
}

interface Reports {
    startDate: string;
    endDate: string;
    totalBookings: number;
    totalRevenue: number;
    approvedBookings: number;
    pendingBookings: number;
    rejectedBookings: number;
    cancelledBookings: number;
    courtBreakdown?: CourtBreakdownItem[];
}

const props = defineProps<{
    assignedCourts: CourtItem[];
    selectedCourt: CourtItem | null;
    reports: Reports | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
            { title: 'Court Reports', href: '/staff/reports' },
        ],
    },
});

const start_date = ref(props.reports?.startDate || '');
const end_date = ref(props.reports?.endDate || '');
const court_id = ref(
    props.selectedCourt?.id || (props.assignedCourts[0]?.id ?? ''),
);

function filterReports() {
    router.get(
        '/staff/reports',
        {
            court_id: court_id.value,
            start_date: start_date.value,
            end_date: end_date.value,
        },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Court Performance Reports - Staff" />

    <div class="w-full space-y-6 p-6">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
                    Assigned Court Performance Reports
                </h1>
                <p class="text-xs text-neutral-500">
                    Overall booking statistics and revenue breakdown across
                    assigned courts.
                </p>
            </div>

            <!-- Date & Court Filter -->
            <div
                class="flex flex-wrap items-center gap-2 rounded-xl border border-neutral-200 bg-white p-2 text-xs shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <select
                    v-if="assignedCourts.length > 1"
                    v-model="court_id"
                    @change="filterReports"
                    class="rounded-lg border border-neutral-300 p-1.5 text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                >
                    <option
                        v-for="c in assignedCourts"
                        :key="c.id"
                        :value="c.id"
                    >
                        {{ c.name }}
                    </option>
                </select>

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
                    class="rounded-lg bg-emerald-600 px-3 py-1.5 font-semibold text-white transition-colors hover:bg-emerald-700"
                >
                    Apply
                </button>
            </div>
        </div>

        <!-- Reports Metrics Grid -->
        <div v-if="reports" class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="space-y-1 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <span class="text-xs font-medium text-neutral-500"
                        >Assigned Court Revenue</span
                    >
                    <div class="text-2xl font-bold text-emerald-600">
                        ₱{{
                            reports.totalRevenue.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                            })
                        }}
                    </div>
                    <span class="text-[11px] text-neutral-400"
                        >Total generated earnings</span
                    >
                </div>

                <div
                    class="space-y-1 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <span class="text-xs font-medium text-neutral-500"
                        >Total Reservations</span
                    >
                    <div
                        class="text-2xl font-bold text-neutral-900 dark:text-white"
                    >
                        {{ reports.totalBookings }}
                    </div>
                    <span class="text-[11px] text-neutral-400"
                        >Bookings placed in date range</span
                    >
                </div>

                <div
                    class="space-y-1 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <span class="text-xs font-medium text-neutral-500"
                        >Approved / Confirmed</span
                    >
                    <div class="text-2xl font-bold text-emerald-600">
                        {{ reports.approvedBookings }}
                    </div>
                    <span class="text-[11px] font-medium text-emerald-600"
                        >Successfully processed</span
                    >
                </div>

                <div
                    class="space-y-1 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <span class="text-xs font-medium text-neutral-500"
                        >Rejected / Cancelled</span
                    >
                    <div class="text-2xl font-bold text-rose-600">
                        {{
                            reports.rejectedBookings + reports.cancelledBookings
                        }}
                    </div>
                    <span class="text-[11px] font-medium text-rose-600"
                        >Rejected or cancelled</span
                    >
                </div>
            </div>

            <!-- Court Breakdown Table matching Admin Reports layout -->
            <div
                v-if="
                    reports.courtBreakdown && reports.courtBreakdown.length > 0
                "
                class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">
                    Revenue & Volume Breakdown By Assigned Court
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr
                                class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                            >
                                <th class="px-3 py-3">Court Name</th>
                                <th class="px-3 py-3">Sport Category</th>
                                <th class="px-3 py-3">Total Reservations</th>
                                <th class="px-3 py-3">Approved Count</th>
                                <th class="px-3 py-3 text-right">
                                    Revenue Generated
                                </th>
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
                                    {{ court.sport_type }}
                                </td>
                                <td
                                    class="px-3 py-3 text-neutral-800 dark:text-neutral-200"
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
                                        })
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-2xl border border-dashed p-8 text-center text-xs text-neutral-400"
        >
            No report data available for assigned court.
        </div>
    </div>
</template>
