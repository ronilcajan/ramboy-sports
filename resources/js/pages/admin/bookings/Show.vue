<script setup lang="ts">
import { Head, Link, useForm, setLayoutProps } from '@inertiajs/vue3';
import {
    CalendarDays,
    ArrowLeft,
    CheckCircle,
    XCircle,
    User,
    FileText,
} from '@lucide/vue';

interface Booking {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    notes?: string;
    admin_notes?: string;
    total_price: string;
    receipt_path?: string | null;
    receipt_url?: string | null;
    transaction_code?: string | null;
    status: string;
    created_at: string;
    court?: { id: number; name: string; sport_type: string };
    user?: { id: number; name: string; email: string };
}

const props = defineProps<{
    booking: Booking;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Super Admin Overview', href: '/admin/dashboard' },
        { title: 'All Bookings', href: '/admin/bookings' },
        {
            title: `Booking #${props.booking.id}`,
            href: `/admin/bookings/${props.booking.id}`,
        },
    ],
});

const statusForm = useForm({
    status: props.booking.status,
    notes: props.booking.notes || '',
});

function updateStatus(newStatus: string) {
    statusForm.status = newStatus;
    statusForm.patch(`/admin/bookings/${props.booking.id}/status`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Booking #${booking.id} Details`" />

    <div class="w-full space-y-6 p-6">
        <div class="flex items-center justify-between">
            <Link
                href="/admin/bookings"
                class="flex items-center gap-1 text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white"
            >
                <ArrowLeft class="h-4 w-4" /> Back to Bookings
            </Link>
        </div>

        <div
            class="space-y-6 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div
                class="flex flex-col justify-between gap-4 border-b border-neutral-100 pb-4 sm:flex-row sm:items-center dark:border-neutral-800"
            >
                <div>
                    <span class="font-mono text-xs font-bold text-emerald-600"
                        >Booking Ref #{{ booking.id }}</span
                    >
                    <h1
                        class="text-xl font-bold text-neutral-900 dark:text-white"
                    >
                        Reservation Details
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="updateStatus('approved')"
                        class="flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
                    >
                        <CheckCircle class="h-4 w-4" /> Approve
                    </button>
                    <button
                        @click="updateStatus('rejected')"
                        class="flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-rose-700"
                    >
                        <XCircle class="h-4 w-4" /> Reject
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 text-xs md:grid-cols-2">
                <!-- Customer Details -->
                <div
                    class="space-y-3 rounded-xl border border-neutral-100 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-800/40"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-neutral-900 dark:text-white"
                    >
                        <User class="h-4 w-4 text-emerald-600" /> Customer
                        Information
                    </h3>
                    <div
                        class="space-y-1.5 text-neutral-700 dark:text-neutral-300"
                    >
                        <p><strong>Name:</strong> {{ booking.name }}</p>
                        <p><strong>Email:</strong> {{ booking.email }}</p>
                        <p><strong>Phone:</strong> {{ booking.phone }}</p>
                        <p v-if="booking.user">
                            <strong>Registered User:</strong>
                            {{ booking.user.name }} (ID #{{ booking.user.id }})
                        </p>
                    </div>
                </div>

                <!-- Booking Details -->
                <div
                    class="space-y-3 rounded-xl border border-neutral-100 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-800/40"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-neutral-900 dark:text-white"
                    >
                        <CalendarDays class="h-4 w-4 text-emerald-600" />
                        Reservation Info
                    </h3>
                    <div
                        class="space-y-1.5 text-neutral-700 dark:text-neutral-300"
                    >
                        <p><strong>Court:</strong> {{ booking.court?.name }}</p>
                        <p><strong>Date:</strong> {{ booking.date }}</p>
                        <p>
                            <strong>Time Slots:</strong>
                            <span class="font-mono">{{
                                booking.time_slots
                                    ? booking.time_slots.join(', ')
                                    : ''
                            }}</span>
                        </p>
                        <p>
                            <strong>Total Price:</strong>
                            <span class="font-bold text-emerald-600"
                                >₱{{ booking.total_price }}</span
                            >
                        </p>
                        <p>
                            <strong>Transaction Ref:</strong>
                            <span
                                v-if="booking.transaction_code"
                                class="font-mono font-bold text-neutral-900 dark:text-white"
                                >{{ booking.transaction_code }}</span
                            >
                            <span v-else class="text-neutral-400 italic"
                                >Not provided</span
                            >
                        </p>
                        <p>
                            <strong>Current Status:</strong>
                            <span class="font-bold capitalize">{{
                                booking.status
                            }}</span>
                        </p>
                        <div v-if="booking.admin_notes" class="pt-1">
                            <strong>{{
                                booking.status === 'rejected'
                                    ? 'Rejection Reason:'
                                    : 'Admin Note:'
                            }}</strong>
                            <span
                                class="mt-1 block rounded-lg p-2"
                                :class="
                                    booking.status === 'rejected'
                                        ? 'border border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-300'
                                        : 'border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300'
                                "
                            >
                                {{ booking.admin_notes }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="booking.receipt_path || booking.receipt_url"
                class="space-y-3 rounded-xl border border-neutral-100 bg-neutral-50/50 p-4 text-xs dark:border-neutral-800 dark:bg-neutral-800/40"
            >
                <div class="flex items-center justify-between">
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-neutral-900 dark:text-white"
                    >
                        <FileText class="h-4 w-4 text-emerald-600" /> Customer
                        Payment Receipt Document
                    </h3>
                    <a
                        :href="
                            booking.receipt_url ||
                            '/storage/' + booking.receipt_path
                        "
                        target="_blank"
                        class="flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1 font-semibold text-white hover:bg-emerald-700"
                    >
                        Open Original File &rarr;
                    </a>
                </div>

                <!-- Inline image preview if image format -->
                <div
                    class="mt-2 max-w-md overflow-hidden rounded-xl border border-neutral-200 bg-black/5 dark:border-neutral-700"
                >
                    <img
                        :src="
                            booking.receipt_url ||
                            '/storage/' + booking.receipt_path
                        "
                        alt="Uploaded Customer Receipt"
                        class="mx-auto max-h-72 w-auto rounded-lg object-contain"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
