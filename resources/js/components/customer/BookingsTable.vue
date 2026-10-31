<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { FileText, Dumbbell, Edit2, AlertCircle, Calendar } from '@lucide/vue';
import { ref, computed } from 'vue';
import { useCourtAvailability } from '@/composables/useCourtAvailability';
import {
    formatSlotRange,
    getMergedTimeSlots,
    isSlotPassed,
} from '@/utils/timeSlots';

export interface CourtItem {
    id: number;
    name: string;
    sport_type: string;
    base_price?: string | number;
    slot_duration_minutes?: number | null;
}

export interface BookingItem {
    id: number;
    reference_code: string;
    court: CourtItem | null;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    status: string;
    notes?: string | null;
    admin_notes?: string | null;
}

const props = defineProps<{
    bookings: BookingItem[];
    courts?: CourtItem[];
}>();

// Edit Booking State
const selectedBooking = ref<BookingItem | null>(null);
const isEditModalOpen = ref(false);
const {
    isLoading: isLoadingSlots,
    fetchAvailability: loadAvailability,
    slotsForCourt,
} = useCourtAvailability();

const realtimeBookedSlots = computed<string[]>(() =>
    editForm.court_id ? slotsForCourt(editForm.court_id) : [],
);

const availableTimeSlots = computed(() => {
    const customFromBookings = props.bookings.flatMap(
        (b) => b.time_slots || [],
    );

    return getMergedTimeSlots(customFromBookings);
});

const todayDateString = computed(() => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');

    return `${yyyy}-${mm}-${dd}`;
});

const editForm = useForm({
    court_id: null as number | null,
    name: '',
    email: '',
    phone: '',
    date: '',
    time: [] as string[],
    notes: '',
});

const selectedCourt = computed(() => {
    if (!editForm.court_id) {
        return selectedBooking.value?.court || null;
    }

    if (props.courts) {
        const found = props.courts.find((c) => c.id === editForm.court_id);

        if (found) {
            return found;
        }
    }

    return selectedBooking.value?.court || null;
});

const calculatedTotalPrice = computed(() => {
    if (!selectedCourt.value || editForm.time.length === 0) {
        return '0.00';
    }

    const base = parseFloat(String(selectedCourt.value.base_price || 0));

    return (base * editForm.time.length).toFixed(2);
});

async function fetchAvailability() {
    if (!editForm.date || !editForm.court_id || !selectedBooking.value) {
        return;
    }

    await loadAvailability({
        date: editForm.date,
        courtId: editForm.court_id,
        excludeBookingId: selectedBooking.value.id,
    });
}

function openEditModal(booking: BookingItem) {
    selectedBooking.value = booking;
    editForm.court_id = booking.court?.id ?? null;
    editForm.name = booking.name;
    editForm.email = booking.email;
    editForm.phone = booking.phone;
    editForm.date = booking.date;
    editForm.time = booking.time_slots ? [...booking.time_slots] : [];
    editForm.notes = booking.notes || '';
    editForm.clearErrors();
    isEditModalOpen.value = true;
    fetchAvailability();
}

function toggleTimeSlot(slot: string) {
    const idx = editForm.time.indexOf(slot);

    if (idx > -1) {
        editForm.time.splice(idx, 1);
    } else {
        editForm.time.push(slot);
    }
}

function isSlotBooked(slot: string): boolean {
    return realtimeBookedSlots.value.includes(slot);
}

function submitEdit() {
    if (!selectedBooking.value) {
        return;
    }

    if (editForm.time.length === 0) {
        editForm.setError('time', 'Please select at least one time slot.');

        return;
    }

    if (editForm.time.some((s) => isSlotPassed(editForm.date, s))) {
        editForm.setError(
            'time',
            'One or more selected time slots have already passed.',
        );

        return;
    }

    editForm.patch(`/bookings/${selectedBooking.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            selectedBooking.value = null;
        },
    });
}

function cancelBooking(bookingId: number) {
    if (confirm('Are you sure you want to cancel this booking reservation?')) {
        const cancelForm = useForm({});
        cancelForm.delete(`/bookings/${bookingId}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div>
        <!-- Desktop Horizontal Table View (hidden md:block) -->
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr
                        class="border-b border-neutral-100 font-bold tracking-wider text-neutral-400 uppercase dark:border-neutral-800"
                    >
                        <th class="px-3 py-3">Reference Code</th>
                        <th class="px-3 py-3">Court / Sport</th>
                        <th class="px-3 py-3">Schedule</th>
                        <th class="px-3 py-3">Amount</th>
                        <th class="px-3 py-3">Receipt</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-neutral-100 dark:divide-neutral-800"
                >
                    <tr
                        v-for="booking in bookings"
                        :key="booking.id"
                        class="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                    >
                        <td
                            class="px-3 py-4 font-mono font-bold text-emerald-600"
                        >
                            {{ booking.reference_code }}
                        </td>
                        <td class="px-3 py-4">
                            <div class="flex flex-col">
                                <span
                                    class="font-bold text-neutral-900 dark:text-white"
                                    >{{
                                        booking.court?.name || 'Deleted Court'
                                    }}</span
                                >
                                <span
                                    class="mt-0.5 flex items-center gap-1 text-[10px] font-medium text-neutral-400"
                                >
                                    <Dumbbell
                                        class="h-3 w-3 text-emerald-600"
                                    />
                                    {{
                                        booking.court?.sport_type ||
                                        'Pickleball'
                                    }}
                                </span>
                            </div>
                        </td>
                        <td class="px-3 py-4">
                            <div class="flex flex-col">
                                <span
                                    class="font-bold text-neutral-800 dark:text-neutral-200"
                                    >{{ booking.date }}</span
                                >
                                <span
                                    class="mt-0.5 font-mono text-[10px] text-neutral-500"
                                    >{{
                                        booking.time_slots
                                            ? booking.time_slots.join(', ')
                                            : 'N/A'
                                    }}</span
                                >
                            </div>
                        </td>
                        <td
                            class="px-3 py-4 font-bold text-neutral-900 dark:text-white"
                        >
                            ₱{{ booking.total_price }}
                        </td>
                        <td class="px-3 py-4">
                            <a
                                v-if="booking.receipt_url"
                                :href="booking.receipt_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline"
                            >
                                <FileText class="h-3.5 w-3.5" /> View Uploaded
                            </a>
                            <span v-else class="text-neutral-400 italic"
                                >No receipt</span
                            >
                        </td>
                        <td class="px-3 py-4">
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold capitalize"
                                :class="{
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400':
                                        booking.status === 'pending',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400':
                                        booking.status === 'approved' ||
                                        booking.status === 'confirmed',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400':
                                        booking.status === 'rejected' ||
                                        booking.status === 'cancelled',
                                    'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300':
                                        booking.status === 'completed',
                                }"
                            >
                                {{ booking.status }}
                            </span>
                            <p
                                v-if="booking.admin_notes"
                                class="mt-1 max-w-xs truncate text-[11px] leading-tight"
                                :class="
                                    booking.status === 'rejected'
                                        ? 'font-medium text-rose-600 dark:text-rose-400'
                                        : 'text-neutral-500 dark:text-neutral-400'
                                "
                                :title="booking.admin_notes"
                            >
                                <span class="font-bold">{{
                                    booking.status === 'rejected'
                                        ? 'Reason:'
                                        : 'Note:'
                                }}</span>
                                {{ booking.admin_notes }}
                            </p>
                        </td>
                        <td class="px-3 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <template
                                    v-if="
                                        booking.status !== 'completed' &&
                                        booking.status !== 'cancelled' &&
                                        booking.status !== 'rejected'
                                    "
                                >
                                    <button
                                        @click="openEditModal(booking)"
                                        class="rounded-lg bg-neutral-100 p-1.5 text-neutral-600 transition-all hover:text-neutral-900 hover:shadow-sm dark:bg-neutral-800 dark:text-neutral-400 dark:hover:text-white"
                                        title="Edit Booking Details"
                                    >
                                        <Edit2 class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        @click="cancelBooking(booking.id)"
                                        class="rounded-lg p-1.5 font-bold text-rose-600 transition-colors hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950/20"
                                        title="Cancel Booking"
                                    >
                                        Cancel
                                    </button>
                                </template>
                                <span
                                    v-else
                                    class="text-[10px] font-medium text-neutral-400 italic"
                                    >Locked</span
                                >
                            </div>
                        </td>
                    </tr>
                    <tr v-if="bookings.length === 0">
                        <td
                            colspan="7"
                            class="py-12 text-center text-xs text-neutral-400"
                        >
                            <div
                                class="flex flex-col items-center justify-center space-y-2"
                            >
                                <AlertCircle
                                    class="h-8 w-8 text-neutral-300 dark:text-neutral-700"
                                />
                                <span
                                    >You haven't made any court bookings
                                    yet.</span
                                >
                                <Link
                                    href="/courts"
                                    class="font-bold text-emerald-600 hover:underline"
                                    >Browse Courts and book &rarr;</Link
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Vertical Display Stack View (block md:hidden) -->
        <div class="space-y-3 md:hidden">
            <div
                v-for="booking in bookings"
                :key="booking.id"
                class="space-y-3 rounded-2xl border border-neutral-200 bg-neutral-50/50 p-4 text-xs shadow-xs dark:border-neutral-800 dark:bg-neutral-800/40"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-200/60 pb-2.5 dark:border-neutral-700/60"
                >
                    <div class="flex flex-col">
                        <span
                            class="text-[10px] font-bold text-neutral-400 uppercase"
                            >Reference Code</span
                        >
                        <span
                            class="font-mono text-sm font-bold text-emerald-600"
                            >{{ booking.reference_code }}</span
                        >
                    </div>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize"
                        :class="{
                            'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400':
                                booking.status === 'pending',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400':
                                booking.status === 'approved' ||
                                booking.status === 'confirmed',
                            'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400':
                                booking.status === 'rejected' ||
                                booking.status === 'cancelled',
                            'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300':
                                booking.status === 'completed',
                        }"
                    >
                        {{ booking.status }}
                    </span>
                </div>

                <div
                    v-if="booking.admin_notes"
                    class="mt-2 rounded-lg border p-2 text-[11px] leading-relaxed"
                    :class="
                        booking.status === 'rejected'
                            ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300'
                            : 'border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800/60 dark:text-neutral-300'
                    "
                >
                    <strong class="font-bold">{{
                        booking.status === 'rejected'
                            ? 'Reason for Rejection:'
                            : 'Admin Note / Remarks:'
                    }}</strong>
                    <span class="ml-1">{{ booking.admin_notes }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-0.5">
                    <div>
                        <span
                            class="block text-[10px] font-bold text-neutral-400 uppercase"
                            >Court / Sport</span
                        >
                        <span
                            class="mt-0.5 block font-bold text-neutral-900 dark:text-white"
                            >{{ booking.court?.name || 'Deleted Court' }}</span
                        >
                        <span
                            class="mt-0.5 flex items-center gap-1 text-[10px] font-medium text-neutral-400"
                        >
                            <Dumbbell class="h-3 w-3 text-emerald-600" />
                            {{ booking.court?.sport_type || 'Pickleball' }}
                        </span>
                    </div>

                    <div>
                        <span
                            class="block text-[10px] font-bold text-neutral-400 uppercase"
                            >Total Amount</span
                        >
                        <span
                            class="mt-0.5 block text-sm font-black text-neutral-900 dark:text-white"
                            >₱{{ booking.total_price }}</span
                        >
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 gap-3 border-t border-neutral-200/40 pt-2 dark:border-neutral-700/40"
                >
                    <div>
                        <span
                            class="block text-[10px] font-bold text-neutral-400 uppercase"
                            >Schedule</span
                        >
                        <span
                            class="mt-0.5 block font-bold text-neutral-800 dark:text-neutral-200"
                            >{{ booking.date }}</span
                        >
                        <span
                            class="mt-0.5 block font-mono text-[10px] text-neutral-500"
                            >{{
                                booking.time_slots
                                    ? booking.time_slots.join(', ')
                                    : 'N/A'
                            }}</span
                        >
                    </div>

                    <div>
                        <span
                            class="block text-[10px] font-bold text-neutral-400 uppercase"
                            >Receipt</span
                        >
                        <a
                            v-if="booking.receipt_url"
                            :href="booking.receipt_url"
                            target="_blank"
                            class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline"
                        >
                            <FileText class="h-3.5 w-3.5" /> View Uploaded
                        </a>
                        <span
                            v-else
                            class="mt-0.5 block text-neutral-400 italic"
                            >No receipt</span
                        >
                    </div>
                </div>

                <div
                    v-if="
                        booking.status !== 'completed' &&
                        booking.status !== 'cancelled' &&
                        booking.status !== 'rejected'
                    "
                    class="flex items-center justify-end gap-2 border-t border-neutral-200/40 pt-2 dark:border-neutral-700/40"
                >
                    <button
                        @click="openEditModal(booking)"
                        class="inline-flex items-center gap-1 rounded-lg bg-neutral-200/60 px-3 py-1.5 text-xs font-semibold text-neutral-700 transition-colors dark:bg-neutral-700/50 dark:text-neutral-300"
                    >
                        <Edit2 class="h-3.5 w-3.5" /> Edit
                    </button>
                    <button
                        @click="cancelBooking(booking.id)"
                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 dark:hover:bg-rose-950/30"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <div
                v-if="bookings.length === 0"
                class="rounded-2xl border border-dashed border-neutral-200 py-10 text-center text-xs text-neutral-400 dark:border-neutral-800"
            >
                <div
                    class="flex flex-col items-center justify-center space-y-2"
                >
                    <AlertCircle
                        class="h-8 w-8 text-neutral-300 dark:text-neutral-700"
                    />
                    <span>You haven't made any court bookings yet.</span>
                    <Link
                        href="/courts"
                        class="font-bold text-emerald-600 hover:underline"
                        >Browse Courts and book &rarr;</Link
                    >
                </div>
            </div>
        </div>

        <!-- Edit Booking Details Modal -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/60 p-4 backdrop-blur-sm"
        >
            <div
                class="max-h-[90vh] w-full max-w-xl space-y-4 overflow-y-auto rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <div>
                        <h3
                            class="text-base font-black tracking-tight text-neutral-900 dark:text-white"
                        >
                            Edit Booking Details
                        </h3>
                        <p class="text-[11px] text-neutral-500">
                            Modify court, date, time slots, or contact info for
                            pending booking:
                            <strong class="font-mono text-emerald-600">{{
                                selectedBooking?.reference_code
                            }}</strong
                            >.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                    <!-- Court & Schedule Section -->
                    <div
                        class="dark:border-neutral-750 space-y-3 rounded-xl border border-neutral-200/80 bg-neutral-50 p-4 dark:bg-neutral-800/50"
                    >
                        <h4
                            class="flex items-center gap-1.5 text-xs font-bold text-neutral-900 dark:text-white"
                        >
                            <Calendar class="h-4 w-4 text-emerald-600" /> Court
                            & Schedule
                        </h4>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <!-- Court Selector -->
                            <div>
                                <label
                                    class="mb-1 block font-bold text-neutral-700 dark:text-neutral-300"
                                    >Select Court</label
                                >
                                <select
                                    v-model="editForm.court_id"
                                    @change="fetchAvailability"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 p-2.5 font-medium text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                >
                                    <option
                                        v-if="!courts || courts.length === 0"
                                        :value="selectedBooking?.court?.id"
                                    >
                                        {{
                                            selectedBooking?.court?.name ||
                                            'Current Court'
                                        }}
                                    </option>
                                    <option
                                        v-for="c in courts"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.name }} ({{ c.sport_type }}) — ₱{{
                                            c.base_price
                                        }}/hr
                                    </option>
                                </select>
                            </div>

                            <!-- Date Picker -->
                            <div>
                                <label
                                    class="mb-1 block font-bold text-neutral-700 dark:text-neutral-300"
                                    >Reservation Date</label
                                >
                                <input
                                    v-model="editForm.date"
                                    type="date"
                                    :min="todayDateString"
                                    @change="fetchAvailability"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 p-2.5 font-medium text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Time Slots Grid -->
                        <div>
                            <div
                                class="mb-1.5 flex items-center justify-between"
                            >
                                <label
                                    class="block font-bold text-neutral-700 dark:text-neutral-300"
                                >
                                    Time Slots
                                </label>
                                <span
                                    v-if="isLoadingSlots"
                                    class="animate-pulse text-[10px] font-semibold text-amber-600"
                                    >Checking availability...</span
                                >
                            </div>

                            <div
                                class="grid max-h-44 grid-cols-2 gap-1.5 overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1.5 sm:grid-cols-4 dark:border-neutral-700 dark:bg-neutral-900"
                            >
                                <button
                                    v-for="slot in availableTimeSlots"
                                    :key="slot"
                                    type="button"
                                    :disabled="
                                        isSlotBooked(slot) ||
                                        isSlotPassed(editForm.date, slot)
                                    "
                                    @click="toggleTimeSlot(slot)"
                                    class="flex flex-col items-center justify-center gap-0.5 rounded-lg border px-2 py-1.5 text-center text-[11px] font-bold transition-all"
                                    :class="{
                                        'cursor-not-allowed border-neutral-200 bg-neutral-100 text-neutral-400 line-through opacity-50 dark:border-neutral-800 dark:bg-neutral-800 dark:text-neutral-600':
                                            isSlotBooked(slot) ||
                                            isSlotPassed(editForm.date, slot),
                                        'border-emerald-600 bg-emerald-600 text-white shadow-sm':
                                            !isSlotBooked(slot) &&
                                            !isSlotPassed(
                                                editForm.date,
                                                slot,
                                            ) &&
                                            editForm.time.includes(slot),
                                        'border-neutral-200 bg-white text-neutral-700 hover:border-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200':
                                            !isSlotBooked(slot) &&
                                            !isSlotPassed(
                                                editForm.date,
                                                slot,
                                            ) &&
                                            !editForm.time.includes(slot),
                                    }"
                                >
                                    <span>{{
                                        formatSlotRange(
                                            slot,
                                            selectedCourt?.slot_duration_minutes ||
                                                60,
                                        )
                                    }}</span>
                                    <span
                                        v-if="isSlotBooked(slot)"
                                        class="text-[8px] tracking-tight text-rose-500 uppercase no-underline"
                                        >Booked</span
                                    >
                                    <span
                                        v-else-if="
                                            isSlotPassed(editForm.date, slot)
                                        "
                                        class="text-[8px] tracking-tight text-neutral-400 uppercase no-underline"
                                        >Passed</span
                                    >
                                    <span
                                        v-else-if="editForm.time.includes(slot)"
                                        class="text-[8px] tracking-tight text-emerald-200 uppercase"
                                        >Selected</span
                                    >
                                </button>
                            </div>
                            <span
                                v-if="editForm.errors.time"
                                class="mt-1 block text-[11px] font-semibold text-rose-500"
                                >{{ editForm.errors.time }}</span
                            >
                        </div>

                        <!-- Price calculation info -->
                        <div
                            class="flex items-center justify-between border-t border-neutral-200/60 pt-1 text-xs font-semibold text-neutral-600 dark:border-neutral-700/60 dark:text-neutral-400"
                        >
                            <span
                                >Selected:
                                {{ editForm.time.length }} slot(s)</span
                            >
                            <span
                                class="font-bold text-emerald-600 dark:text-emerald-400"
                                >Estimated Total: ₱{{
                                    calculatedTotalPrice
                                }}</span
                            >
                        </div>
                    </div>

                    <!-- Customer Contact Details -->
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1 block font-bold text-neutral-700 dark:text-neutral-300"
                                    >Customer Name</label
                                >
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                />
                                <span
                                    v-if="editForm.errors.name"
                                    class="mt-1 block text-[10px] text-rose-500"
                                    >{{ editForm.errors.name }}</span
                                >
                            </div>

                            <div>
                                <label
                                    class="mb-1 block font-bold text-neutral-700 dark:text-neutral-300"
                                    >Email Address</label
                                >
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                />
                                <span
                                    v-if="editForm.errors.email"
                                    class="mt-1 block text-[10px] text-rose-500"
                                    >{{ editForm.errors.email }}</span
                                >
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1 block font-bold text-neutral-700 dark:text-neutral-300"
                                >Contact Phone</label
                            >
                            <input
                                v-model="editForm.phone"
                                type="text"
                                required
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                            <span
                                v-if="editForm.errors.phone"
                                class="mt-1 block text-[10px] text-rose-500"
                                >{{ editForm.errors.phone }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="mb-1 block font-bold text-neutral-700 dark:text-neutral-300"
                                >Reservation Notes</label
                            >
                            <textarea
                                v-model="editForm.notes"
                                rows="2"
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                placeholder="Any special requests or instructions..."
                            ></textarea>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-neutral-100 pt-2 dark:border-neutral-800"
                    >
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="dark:hover:bg-neutral-750 rounded-xl bg-neutral-100 px-4 py-2 font-bold text-neutral-700 transition-colors hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-xl bg-emerald-600 px-5 py-2 font-bold text-white shadow-lg shadow-emerald-600/10 transition-colors hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {{
                                editForm.processing
                                    ? 'Saving...'
                                    : 'Save Changes'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
