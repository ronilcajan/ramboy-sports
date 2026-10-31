<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import BookingsTable from '@/components/customer/BookingsTable.vue';
import type {
    BookingItem,
    CourtItem,
} from '@/components/customer/BookingsTable.vue';

defineProps<{
    bookings: BookingItem[];
    courts?: CourtItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Bookings', href: '/my-bookings' }],
    },
});
</script>

<template>
    <Head title="My Bookings" />

    <div class="w-full space-y-6 p-6 pb-16">
        <div
            class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-emerald-500/10 bg-gradient-to-r from-emerald-600/10 via-teal-600/5 to-transparent p-6 md:flex-row md:items-center dark:border-emerald-500/5"
        >
            <div>
                <h1
                    class="text-2xl font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    My Bookings
                </h1>
                <p class="mt-1 text-xs text-neutral-500">
                    Track status, update details, or cancel your pending court
                    reservations.
                </p>
            </div>
            <Link
                href="/courts"
                class="flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition-all hover:translate-y-[-1px] hover:bg-emerald-700"
            >
                <Plus class="h-4 w-4" /> Book a New Court
            </Link>
        </div>

        <div
            class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div>
                <h3
                    class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    Your Booking History
                </h3>
                <p class="text-xs text-neutral-500">
                    All your reservations in one place.
                </p>
            </div>

            <BookingsTable :bookings="bookings" :courts="courts" />
        </div>
    </div>
</template>
