<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, ChevronRight, Gift } from '@lucide/vue';
import { computed } from 'vue';
import BookingsTable from '@/components/customer/BookingsTable.vue';
import type {
    BookingItem,
    CourtItem,
} from '@/components/customer/BookingsTable.vue';
import PointsSummaryCard from '@/components/customer/PointsSummaryCard.vue';
import type { LoyaltySummary } from '@/components/customer/PointsSummaryCard.vue';
import StatCardsSlideshow from '@/components/customer/StatCardsSlideshow.vue';
import { dashboard } from '@/routes';

const props = defineProps<{
    bookings: BookingItem[];
    courts?: CourtItem[];
    loyaltySummary?: LoyaltySummary;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

// Stats Calculation
const stats = computed(() => {
    const total = props.bookings.length;
    const pending = props.bookings.filter((b) => b.status === 'pending').length;
    const confirmed = props.bookings.filter(
        (b) => b.status === 'approved' || b.status === 'confirmed',
    ).length;
    const totalSpent = props.bookings
        .filter((b) => b.status !== 'rejected' && b.status !== 'cancelled')
        .reduce((sum, b) => sum + parseFloat(b.total_price || '0'), 0);

    return {
        total,
        pending,
        confirmed,
        totalSpent: totalSpent.toFixed(2),
    };
});

// Show only the most recent bookings on the dashboard; the full list lives on the Bookings page.
const recentBookings = computed(() => props.bookings.slice(0, 5));
</script>

<template>
    <Head title="Dashboard" />

    <div class="w-full space-y-6 p-6 pb-16">
        <!-- Hero Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-emerald-500/10 bg-gradient-to-r from-emerald-600/10 via-teal-600/5 to-transparent p-6 md:flex-row md:items-center dark:border-emerald-500/5"
        >
            <div>
                <h1
                    class="text-2xl font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    Welcome Back!
                </h1>
                <p class="mt-1 text-xs text-neutral-500">
                    Manage your active court reservations, earn loyalty points,
                    and claim exclusive member freebies.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    href="/customer/rewards"
                    class="flex items-center gap-1.5 rounded-xl border border-neutral-700/60 bg-neutral-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition-all hover:translate-y-[-1px] hover:bg-neutral-800 dark:bg-neutral-800 dark:hover:bg-neutral-700"
                >
                    <Gift class="h-4 w-4 text-emerald-400" /> Freebies Hub
                </Link>
                <Link
                    href="/courts"
                    class="flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition-all hover:translate-y-[-1px] hover:bg-emerald-700"
                >
                    <Plus class="h-4 w-4" /> Book a New Court
                </Link>
            </div>
        </div>

        <!-- Loyalty Points & Rewards Overview Card -->
        <PointsSummaryCard :loyalty="loyaltySummary" />

        <!-- Stat Cards Slideshow (Mobile Slideshow / Desktop Grid) -->
        <StatCardsSlideshow :stats="stats" />

        <!-- Recent Bookings Section -->
        <div
            class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3
                        class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                    >
                        Recent Bookings
                    </h3>
                    <p class="text-xs text-neutral-500">
                        Your latest reservations. Manage them all on the
                        Bookings page.
                    </p>
                </div>
                <Link
                    href="/my-bookings"
                    class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline"
                >
                    View all <ChevronRight class="h-3.5 w-3.5" />
                </Link>
            </div>

            <BookingsTable :bookings="recentBookings" :courts="courts" />
        </div>
    </div>
</template>
