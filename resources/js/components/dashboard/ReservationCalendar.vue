<script setup lang="ts">
import {
    Calendar as CalendarIcon,
    Clock,
    ShieldAlert,
    CheckCircle,
    AlertCircle,
    CalendarRange,
} from '@lucide/vue';
import { ref, computed } from 'vue';

interface BookingSlot {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    status: string;
    total_price: string;
}

interface UnavailabilitySlot {
    id: number;
    date: string;
    start_time?: string;
    end_time?: string;
    all_day: boolean;
    reason?: string;
}

const props = defineProps<{
    bookings: BookingSlot[];
    unavailabilities: UnavailabilitySlot[];
    selectedDate?: string;
}>();

defineEmits(['select-date']);

const viewMode = ref<'day' | 'month'>('month');

const activeDate = ref(
    props.selectedDate || new Date().toISOString().split('T')[0],
);

const currentMonthBookings = computed(() => {
    return props.bookings.filter((b) => b.date === activeDate.value);
});

const currentMonthBlackouts = computed(() => {
    return props.unavailabilities.filter((u) => u.date === activeDate.value);
});

const defaultTimeSlots = [
    '08:00 - 09:00',
    '09:00 - 10:00',
    '10:00 - 11:00',
    '11:00 - 12:00',
    '12:00 - 13:00',
    '13:00 - 14:00',
    '14:00 - 15:00',
    '15:00 - 16:00',
    '16:00 - 17:00',
    '17:00 - 18:00',
    '18:00 - 19:00',
    '19:00 - 20:00',
    '20:00 - 21:00',
    '21:00 - 22:00',
];

/** Statuses that hand the hour back — mirrors Booking::RELEASED_STATUSES. */
const releasedStatuses = ['rejected', 'cancelled'];

function bookingsForSlot(slot: string) {
    return currentMonthBookings.value.filter(
        (b) => b.time_slots && b.time_slots.includes(slot),
    );
}

/** A slot stays available unless something on it still holds the hour. */
function slotIsAvailable(slot: string): boolean {
    return !bookingsForSlot(slot).some(
        (b) => !releasedStatuses.includes(b.status),
    );
}

function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'approved':
        case 'confirmed':
        case 'completed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-300';
        case 'pending':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-300';
        case 'rejected':
        case 'cancelled':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-300';
        default:
            return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300';
    }
}
</script>

<template>
    <div
        class="space-y-4 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
    >
        <div
            class="flex flex-col justify-between gap-3 border-b border-neutral-100 pb-4 sm:flex-row sm:items-center dark:border-neutral-800"
        >
            <div class="flex items-center gap-2">
                <CalendarRange class="h-5 w-5 text-emerald-600" />
                <h3
                    class="text-base font-semibold text-neutral-900 dark:text-white"
                >
                    Interactive Reservation Schedule
                </h3>
            </div>

            <div class="flex items-center gap-3">
                <input
                    type="date"
                    v-model="activeDate"
                    class="rounded-lg border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                />

                <div
                    class="flex items-center rounded-lg border border-neutral-200 bg-neutral-100 p-0.5 text-xs dark:border-neutral-800 dark:bg-neutral-800"
                >
                    <button
                        @click="viewMode = 'month'"
                        :class="[
                            'rounded-md px-3 py-1 font-medium transition-colors',
                            viewMode === 'month'
                                ? 'bg-white text-neutral-900 shadow dark:bg-neutral-900 dark:text-white'
                                : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white',
                        ]"
                    >
                        Daily Matrix
                    </button>
                </div>
            </div>
        </div>

        <!-- Date Header Banner -->
        <div
            class="flex items-center justify-between rounded-lg border border-neutral-100 bg-neutral-50 p-3 dark:border-neutral-800 dark:bg-neutral-800/50"
        >
            <span
                class="flex items-center gap-2 text-sm font-semibold text-neutral-800 dark:text-neutral-200"
            >
                <CalendarIcon class="h-4 w-4 text-emerald-600" />
                Selected Date: {{ activeDate }}
            </span>
            <span class="text-xs text-neutral-500">
                Total Reservations: {{ currentMonthBookings.length }} |
                Blackouts: {{ currentMonthBlackouts.length }}
            </span>
        </div>

        <!-- Hourly Schedule Grid -->
        <div class="space-y-2">
            <div
                v-for="slot in defaultTimeSlots"
                :key="slot"
                class="flex flex-col justify-between gap-2 rounded-lg border border-neutral-100 bg-neutral-50/30 p-3 transition-colors hover:bg-neutral-50 sm:flex-row sm:items-center dark:border-neutral-800/80 dark:bg-neutral-900/30 dark:hover:bg-neutral-800/30"
            >
                <div class="flex min-w-[140px] items-center gap-3">
                    <Clock class="h-4 w-4 text-neutral-400" />
                    <span
                        class="font-mono text-xs font-medium text-neutral-700 dark:text-neutral-300"
                        >{{ slot }}</span
                    >
                </div>

                <!-- Slot State -->
                <div class="flex-1">
                    <!-- Blackout / Unavailable check -->
                    <template
                        v-if="
                            currentMonthBlackouts.some(
                                (u) =>
                                    u.all_day ||
                                    (u.start_time &&
                                        slot.includes(u.start_time)),
                            )
                        "
                    >
                        <div
                            class="inline-flex items-center gap-1.5 rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300"
                        >
                            <ShieldAlert class="h-3.5 w-3.5" />
                            <span
                                >Unavailable / Maintenance:
                                {{
                                    currentMonthBlackouts[0]?.reason || 'Closed'
                                }}</span
                            >
                        </div>
                    </template>

                    <!-- Every booking on this hour, rejections included -->
                    <template v-else>
                        <div
                            v-for="b in bookingsForSlot(slot)"
                            :key="b.id"
                            :class="[
                                'my-0.5 mr-2 inline-flex items-center gap-2 rounded-md border px-3 py-1 text-xs font-medium',
                                getStatusBadgeClass(b.status),
                                releasedStatuses.includes(b.status)
                                    ? 'opacity-60'
                                    : '',
                            ]"
                        >
                            <CheckCircle
                                v-if="
                                    b.status === 'approved' ||
                                    b.status === 'confirmed'
                                "
                                class="h-3.5 w-3.5"
                            />
                            <AlertCircle v-else class="h-3.5 w-3.5" />
                            <span
                                :class="
                                    releasedStatuses.includes(b.status)
                                        ? 'line-through'
                                        : ''
                                "
                            >
                                {{ b.name }} ({{ b.status }}) - ₱{{
                                    b.total_price
                                }}
                            </span>
                        </div>

                        <!-- Still available when nothing on this hour holds it -->
                        <span
                            v-if="slotIsAvailable(slot)"
                            class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                        >
                            Available Slot
                        </span>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
