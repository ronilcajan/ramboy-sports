<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    X,
    CheckCircle,
    XCircle,
    AlertCircle,
    User,
    Mail,
    Phone,
    Calendar,
    Clock,
    Dumbbell,
    Building2,
    FileText,
    MessageSquare,
    Pencil,
    Save,
    StickyNote,
} from '@lucide/vue';
import { ref, computed, watch } from 'vue';
import { formatSlotRange, getMergedTimeSlots } from '@/utils/timeSlots';

export interface BookingDetail {
    id: number;
    reference_code?: string;
    customer_name?: string;
    name?: string;
    email: string;
    phone: string;
    court_name?: string;
    court?: {
        id: number;
        name: string;
        sport_type?: string;
        slot_duration_minutes?: number | null;
    } | null;
    venue_name?: string;
    venue?: { id: number; name: string } | null;
    sport_type?: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    receipt_path?: string | null;
    status: string;
    notes?: string | null;
    admin_notes?: string | null;
    created_at?: string;
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        booking: BookingDetail | null;
        updateRoutePrefix?: string;
        canUpdate?: boolean;
    }>(),
    {
        updateRoutePrefix: '/admin/bookings',
        canUpdate: true,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const actionForm = useForm({
    status: '',
});

const isEditing = ref(false);

const availableTimeSlots = computed(() => {
    const slots = props.booking?.time_slots || [];

    return getMergedTimeSlots(slots);
});

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    date: '',
    time_slots: [] as string[],
    notes: '',
    admin_notes: '',
    status: '',
});

const remarksForm = useForm({
    admin_notes: '',
});

const savedAdminNotes = ref<Record<number, string>>({});

function adminNotesFor(booking: BookingDetail): string {
    return savedAdminNotes.value[booking.id] ?? booking.admin_notes ?? '';
}

watch(
    () => props.booking,
    (b) => {
        if (b) {
            remarksForm.admin_notes = adminNotesFor(b);
            remarksForm.clearErrors();
        }
    },
    { immediate: true },
);

function saveRemarks() {
    if (!props.booking) {
        return;
    }

    remarksForm.patch(`${props.updateRoutePrefix}/${props.booking.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (props.booking) {
                savedAdminNotes.value[props.booking.id] =
                    remarksForm.admin_notes;
            }
        },
    });
}

function startEditing() {
    if (!props.booking) {
        return;
    }

    editForm.name = props.booking.name || props.booking.customer_name || '';
    editForm.email = props.booking.email || '';
    editForm.phone = props.booking.phone || '';
    editForm.date = props.booking.date || '';
    editForm.time_slots = [...(props.booking.time_slots || [])];
    editForm.notes = props.booking.notes || '';
    editForm.admin_notes = adminNotesFor(props.booking);
    editForm.status = props.booking.status || 'pending';
    editForm.clearErrors();
    isEditing.value = true;
}

function cancelEditing() {
    isEditing.value = false;
    editForm.clearErrors();
}

function toggleTimeSlot(slot: string) {
    const idx = editForm.time_slots.indexOf(slot);

    if (idx >= 0) {
        editForm.time_slots.splice(idx, 1);
    } else {
        editForm.time_slots.push(slot);
    }
}

function submitEdit() {
    if (!props.booking) {
        return;
    }

    editForm.patch(`${props.updateRoutePrefix}/${props.booking.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (props.booking) {
                savedAdminNotes.value[props.booking.id] = editForm.admin_notes;
            }

            isEditing.value = false;
        },
    });
}

watch(
    () => props.isOpen,
    (newVal) => {
        if (!newVal) {
            isEditing.value = false;
            editForm.clearErrors();
            remarksForm.clearErrors();
        } else if (props.booking) {
            remarksForm.admin_notes = adminNotesFor(props.booking);
            remarksForm.clearErrors();
        }
    },
);

const displayName = computed(() => {
    if (!props.booking) {
        return '';
    }

    return (
        props.booking.customer_name ||
        props.booking.name ||
        'Anonymous Customer'
    );
});

const displayCourt = computed(() => {
    if (!props.booking) {
        return 'N/A';
    }

    if (props.booking.court_name) {
        return props.booking.court_name;
    }

    if (props.booking.court?.name) {
        return props.booking.court.name;
    }

    return 'Unassigned Court';
});

const displayVenue = computed(() => {
    if (!props.booking) {
        return '';
    }

    if (props.booking.venue_name) {
        return props.booking.venue_name;
    }

    if (props.booking.venue?.name) {
        return props.booking.venue.name;
    }

    return '';
});

const displaySport = computed(() => {
    if (!props.booking) {
        return '';
    }

    if (props.booking.sport_type) {
        return props.booking.sport_type;
    }

    if (props.booking.court?.sport_type) {
        return props.booking.court.sport_type;
    }

    return '';
});

const receiptLink = computed(() => {
    if (!props.booking) {
        return null;
    }

    if (props.booking.receipt_url) {
        return props.booking.receipt_url;
    }

    if (props.booking.receipt_path) {
        return `/storage/${props.booking.receipt_path}`;
    }

    return null;
});

const formattedReference = computed(() => {
    if (!props.booking) {
        return '';
    }

    if (props.booking.reference_code) {
        return props.booking.reference_code;
    }

    return `DY-RESRV-${String(props.booking.id).padStart(6, '0')}`;
});

function updateStatus(newStatus: string) {
    if (!props.booking || !props.canUpdate) {
        return;
    }

    actionForm.status = newStatus;
    actionForm.patch(`${props.updateRoutePrefix}/${props.booking.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
}

function statusClasses(s: string): string {
    if (s === 'approved' || s === 'confirmed') {
        return 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
    }

    if (s === 'pending') {
        return 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
    }

    if (s === 'completed') {
        return 'bg-neutral-200 text-neutral-800 border-neutral-300 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-700';
    }

    return 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800';
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen && booking"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/70 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl transition-all dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-neutral-100 bg-neutral-50/50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-800/40"
                    >
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ formattedReference }}
                                </span>
                                <span
                                    :class="[
                                        'rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                                        statusClasses(booking.status),
                                    ]"
                                >
                                    {{ booking.status }}
                                </span>
                            </div>
                            <h3
                                class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                            >
                                {{
                                    isEditing
                                        ? 'Edit Booking Reservation'
                                        : 'Customer Reservation Details'
                                }}
                            </h3>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="canUpdate !== false && !isEditing"
                                type="button"
                                @click="startEditing"
                                class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                            >
                                <Pencil class="size-3.5" /> Edit Time &amp; Info
                            </button>
                            <button
                                type="button"
                                @click="emit('close')"
                                class="cursor-pointer rounded-full p-1.5 text-neutral-400 transition-colors hover:bg-neutral-200 hover:text-neutral-900 dark:hover:bg-neutral-800 dark:hover:text-white"
                            >
                                <X class="size-5" />
                            </button>
                        </div>
                    </div>

                    <!-- EDIT FORM MODE -->
                    <form
                        v-if="isEditing"
                        @submit.prevent="submitEdit"
                        class="max-h-[75vh] space-y-4 overflow-y-auto p-6"
                    >
                        <div
                            v-if="Object.keys(editForm.errors).length > 0"
                            class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-600 dark:text-rose-400"
                        >
                            <ul
                                class="list-inside list-disc space-y-0.5 font-medium"
                            >
                                <li
                                    v-for="(err, k) in editForm.errors"
                                    :key="k"
                                >
                                    {{ err }}
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-1.5">
                            <label
                                class="block text-xs font-bold text-neutral-700 dark:text-neutral-300"
                                >Customer Full Name</label
                            >
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-bold text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-neutral-700 dark:text-neutral-300"
                                    >Email Address</label
                                >
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-semibold text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-neutral-700 dark:text-neutral-300"
                                    >Phone Number</label
                                >
                                <input
                                    v-model="editForm.phone"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-semibold text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Date & Time Slot Editors -->
                        <div
                            class="space-y-3 rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-800/40"
                        >
                            <div class="space-y-1.5">
                                <label
                                    class="block flex items-center gap-1.5 text-xs font-bold text-neutral-700 dark:text-neutral-300"
                                >
                                    <Calendar
                                        class="size-3.5 text-emerald-600"
                                    />
                                    Reserved Date
                                </label>
                                <input
                                    v-model="editForm.date"
                                    type="date"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 bg-white px-3 py-2 text-xs font-bold text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                                />
                            </div>

                            <div class="space-y-2">
                                <label
                                    class="block flex items-center gap-1.5 text-xs font-bold text-neutral-700 dark:text-neutral-300"
                                >
                                    <Clock class="size-3.5 text-emerald-600" />
                                    Reserved Time Slots
                                </label>
                                <p class="text-[11px] text-neutral-400">
                                    Click time slot pills to add or remove them
                                    from this reservation.
                                </p>

                                <div
                                    class="grid max-h-36 grid-cols-3 gap-1.5 overflow-y-auto rounded-xl border border-neutral-200 bg-white p-1 dark:border-neutral-800 dark:bg-neutral-900"
                                >
                                    <button
                                        v-for="slot in availableTimeSlots"
                                        :key="slot"
                                        type="button"
                                        @click="toggleTimeSlot(slot)"
                                        :class="[
                                            'rounded-lg border px-2 py-1.5 text-center font-mono text-xs font-bold transition-all',
                                            editForm.time_slots.includes(slot)
                                                ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                                : 'border-neutral-200 bg-neutral-50 text-neutral-700 hover:border-emerald-400 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                                        ]"
                                    >
                                        {{
                                            formatSlotRange(
                                                slot,
                                                booking?.court
                                                    ?.slot_duration_minutes ||
                                                    60,
                                            )
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label
                                class="block text-xs font-bold text-neutral-700 dark:text-neutral-300"
                                >Customer Notes</label
                            >
                            <textarea
                                v-model="editForm.notes"
                                rows="2"
                                placeholder="Special requests from the customer..."
                                class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-medium text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label
                                class="block flex items-center gap-1.5 text-xs font-bold text-neutral-700 dark:text-neutral-300"
                            >
                                <StickyNote class="size-3.5 text-amber-600" />
                                Note / Remarks
                            </label>
                            <p class="text-[11px] text-neutral-400">
                                Internal only — the customer never sees this.
                            </p>
                            <textarea
                                v-model="editForm.admin_notes"
                                rows="3"
                                placeholder="Payment follow-up, equipment requests, who to contact on arrival..."
                                class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-medium text-neutral-900 focus:ring-2 focus:ring-amber-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                            <p
                                v-if="editForm.errors.admin_notes"
                                class="text-[11px] font-semibold text-red-600"
                            >
                                {{ editForm.errors.admin_notes }}
                            </p>
                        </div>

                        <div
                            class="flex justify-end gap-2 border-t border-neutral-100 pt-2 dark:border-neutral-800"
                        >
                            <button
                                type="button"
                                @click="cancelEditing"
                                class="rounded-xl border border-neutral-300 px-4 py-2 text-xs font-bold text-neutral-700 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="
                                    editForm.processing ||
                                    editForm.time_slots.length === 0
                                "
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-md hover:bg-emerald-700 disabled:opacity-50"
                            >
                                <Save class="size-3.5" /> Save Changes
                            </button>
                        </div>
                    </form>

                    <!-- READ-ONLY VIEW MODE -->
                    <div
                        v-else
                        class="max-h-[75vh] space-y-5 overflow-y-auto p-6"
                    >
                        <!-- Error Banner -->
                        <div
                            v-if="Object.keys(actionForm.errors).length > 0"
                            class="flex items-start gap-2.5 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-600 shadow-sm dark:text-rose-400"
                        >
                            <AlertCircle
                                class="mt-0.5 size-4 shrink-0 text-rose-600"
                            />
                            <div>
                                <strong class="block text-sm font-bold"
                                    >Action Failed</strong
                                >
                                <ul
                                    class="mt-1 list-inside list-disc space-y-0.5"
                                >
                                    <li
                                        v-for="(err, key) in actionForm.errors"
                                        :key="key"
                                        class="font-medium"
                                    >
                                        {{ err }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Customer Information Card -->
                        <div
                            class="space-y-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div
                                class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-800"
                            >
                                <div class="flex items-center gap-2">
                                    <User class="size-4 text-emerald-600" />
                                    <h4
                                        class="text-xs font-bold tracking-wider text-neutral-500 uppercase"
                                    >
                                        Customer Information
                                    </h4>
                                </div>
                                <button
                                    v-if="canUpdate !== false"
                                    type="button"
                                    @click="startEditing"
                                    class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:underline dark:text-emerald-400"
                                >
                                    <Pencil class="size-3" /> Edit
                                </button>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2"
                            >
                                <div>
                                    <span
                                        class="block text-[10px] font-semibold text-neutral-400 uppercase"
                                        >Full Name</span
                                    >
                                    <span
                                        class="text-sm font-bold text-neutral-900 dark:text-white"
                                        >{{ displayName }}</span
                                    >
                                </div>
                                <div>
                                    <span
                                        class="block text-[10px] font-semibold text-neutral-400 uppercase"
                                        >Phone Number</span
                                    >
                                    <span
                                        class="flex items-center gap-1 font-semibold text-neutral-800 dark:text-neutral-200"
                                    >
                                        <Phone
                                            class="size-3 text-neutral-400"
                                        />
                                        {{ booking.phone || 'N/A' }}
                                    </span>
                                </div>
                                <div class="col-span-full">
                                    <span
                                        class="block text-[10px] font-semibold text-neutral-400 uppercase"
                                        >Email Address</span
                                    >
                                    <span
                                        class="flex items-center gap-1 truncate font-semibold text-neutral-800 dark:text-neutral-200"
                                    >
                                        <Mail
                                            class="size-3 shrink-0 text-neutral-400"
                                        />
                                        {{ booking.email || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Reservation Details Card -->
                        <div
                            class="space-y-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div
                                class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-800"
                            >
                                <div class="flex items-center gap-2">
                                    <Calendar class="size-4 text-emerald-600" />
                                    <h4
                                        class="text-xs font-bold tracking-wider text-neutral-500 uppercase"
                                    >
                                        Facility &amp; Schedule Reservation
                                    </h4>
                                </div>
                                <button
                                    v-if="canUpdate !== false"
                                    type="button"
                                    @click="startEditing"
                                    class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:underline dark:text-emerald-400"
                                >
                                    <Pencil class="size-3" /> Edit Date &amp;
                                    Time
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div v-if="displayVenue" class="col-span-2">
                                    <span
                                        class="block text-[10px] font-semibold text-neutral-400 uppercase"
                                        >Location Facility</span
                                    >
                                    <span
                                        class="flex items-center gap-1.5 font-bold text-neutral-900 dark:text-white"
                                    >
                                        <Building2
                                            class="size-3.5 text-emerald-600"
                                        />
                                        {{ displayVenue }}
                                    </span>
                                </div>

                                <div>
                                    <span
                                        class="block text-[10px] font-semibold text-neutral-400 uppercase"
                                        >Reserved Court</span
                                    >
                                    <span
                                        class="flex items-center gap-1.5 font-bold text-neutral-900 dark:text-white"
                                    >
                                        <Dumbbell
                                            class="size-3.5 text-emerald-600"
                                        />
                                        {{ displayCourt }}
                                        <span
                                            v-if="displaySport"
                                            class="text-[10px] font-normal text-neutral-400"
                                            >({{ displaySport }})</span
                                        >
                                    </span>
                                </div>

                                <div>
                                    <span
                                        class="block text-[10px] font-semibold text-neutral-400 uppercase"
                                        >Booking Date</span
                                    >
                                    <span
                                        class="flex items-center gap-1.5 font-bold text-neutral-900 dark:text-white"
                                    >
                                        <Calendar
                                            class="size-3.5 text-emerald-600"
                                        />
                                        {{ booking.date }}
                                    </span>
                                </div>

                                <div class="col-span-2">
                                    <span
                                        class="block text-[10px] font-semibold text-neutral-400 uppercase"
                                        >Reserved Time Slots</span
                                    >
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="slot in booking.time_slots"
                                            :key="slot"
                                            class="rounded-lg border border-neutral-200 bg-neutral-100 px-2.5 py-1 font-mono text-xs font-bold text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                        >
                                            {{
                                                formatSlotRange(
                                                    slot,
                                                    booking.court
                                                        ?.slot_duration_minutes ||
                                                        60,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="col-span-2 mt-1 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900/60 dark:bg-emerald-950/40"
                                >
                                    <div>
                                        <span
                                            class="block text-[10px] font-bold tracking-wider text-emerald-700 uppercase dark:text-emerald-300"
                                            >Total Booking Amount</span
                                        >
                                        <span
                                            class="text-xs font-medium text-neutral-500"
                                            >{{
                                                booking.time_slots
                                                    ? booking.time_slots.length
                                                    : 1
                                            }}
                                            slot(s) reserved</span
                                        >
                                    </div>
                                    <span
                                        class="text-xl font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                        ₱{{ booking.total_price }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Special Requests / Customer Notes -->
                        <div
                            v-if="booking.notes"
                            class="space-y-1.5 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div class="flex items-center gap-2">
                                <MessageSquare
                                    class="size-4 text-emerald-600"
                                />
                                <h4
                                    class="text-xs font-bold tracking-wider text-neutral-500 uppercase"
                                >
                                    Customer Notes / Requests
                                </h4>
                            </div>
                            <p
                                class="rounded-lg border border-neutral-100 bg-neutral-50 p-2.5 text-xs leading-relaxed text-neutral-700 italic dark:border-neutral-800 dark:bg-neutral-800/50 dark:text-neutral-300"
                            >
                                "{{ booking.notes }}"
                            </p>
                        </div>

                        <!-- Note / Remarks for Super Admin / Staff -->
                        <div
                            class="space-y-3 rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <StickyNote class="size-4 text-amber-600" />
                                    <h4
                                        class="text-xs font-bold tracking-wider text-amber-800 uppercase dark:text-amber-400"
                                    >
                                        Note / Remarks
                                    </h4>
                                    <span
                                        class="text-[10px] font-semibold text-amber-600/70 dark:text-amber-500/70"
                                        >Admin / Internal</span
                                    >
                                </div>
                                <button
                                    v-if="canUpdate"
                                    type="button"
                                    @click="saveRemarks"
                                    :disabled="remarksForm.processing"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1 text-xs font-bold text-white shadow-xs transition hover:bg-amber-700 disabled:opacity-50"
                                >
                                    <Save
                                        class="size-3"
                                        v-if="!remarksForm.processing"
                                    />
                                    <span>{{
                                        remarksForm.processing
                                            ? 'Saving...'
                                            : 'Save Note'
                                    }}</span>
                                </button>
                            </div>

                            <textarea
                                v-model="remarksForm.admin_notes"
                                :readonly="!canUpdate"
                                rows="3"
                                placeholder="Add notes, remarks, or special instructions for this booking..."
                                class="w-full rounded-lg border border-amber-200 bg-white p-2.5 text-xs text-neutral-800 placeholder-neutral-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none dark:border-amber-800 dark:bg-neutral-900 dark:text-neutral-200"
                            />
                            <p
                                v-if="remarksForm.errors.admin_notes"
                                class="text-[11px] font-semibold text-red-600"
                            >
                                {{ remarksForm.errors.admin_notes }}
                            </p>
                        </div>

                        <!-- Payment Receipt Upload -->
                        <div
                            class="space-y-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div
                                class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-800"
                            >
                                <div class="flex items-center gap-2">
                                    <FileText class="size-4 text-emerald-600" />
                                    <h4
                                        class="text-xs font-bold tracking-wider text-neutral-500 uppercase"
                                    >
                                        Payment Proof Receipt
                                    </h4>
                                </div>
                                <a
                                    v-if="receiptLink"
                                    :href="receiptLink"
                                    target="_blank"
                                    class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:underline dark:text-emerald-400"
                                >
                                    View Full Image &rarr;
                                </a>
                            </div>

                            <div
                                v-if="receiptLink"
                                class="group relative aspect-[16/9] max-h-48 overflow-hidden rounded-xl border border-neutral-200 bg-neutral-900 dark:border-neutral-800"
                            >
                                <img
                                    :src="receiptLink"
                                    alt="Payment receipt proof"
                                    class="h-full w-full object-contain"
                                />
                            </div>
                            <div
                                v-else
                                class="rounded-xl border border-dashed border-neutral-300 p-4 text-center text-xs text-neutral-400 dark:border-neutral-700"
                            >
                                No payment receipt uploaded for this booking.
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons (Confirm / Reject) -->
                    <div
                        v-if="!isEditing"
                        class="flex flex-col items-center justify-between gap-3 border-t border-neutral-100 bg-neutral-50/50 px-6 py-4 sm:flex-row dark:border-neutral-800 dark:bg-neutral-800/40"
                    >
                        <button
                            type="button"
                            @click="emit('close')"
                            class="w-full cursor-pointer rounded-xl border border-neutral-300 px-4 py-2 text-xs font-bold text-neutral-700 transition-colors hover:bg-neutral-100 sm:w-auto dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                        >
                            Close Details
                        </button>

                        <div
                            v-if="canUpdate !== false"
                            class="flex w-full items-center gap-2 sm:w-auto"
                        >
                            <!-- Confirm / Approve Button -->
                            <button
                                type="button"
                                @click="updateStatus('approved')"
                                :disabled="actionForm.processing"
                                class="inline-flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-extrabold text-white shadow-md shadow-emerald-600/20 transition-all hover:bg-emerald-700 active:scale-95 disabled:opacity-50 sm:flex-initial"
                                title="Confirm this booking reservation"
                            >
                                <CheckCircle class="size-4" />
                                <span>Confirm Booking</span>
                            </button>

                            <!-- Reject Button -->
                            <button
                                type="button"
                                @click="updateStatus('rejected')"
                                :disabled="actionForm.processing"
                                class="inline-flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-5 py-2 text-xs font-extrabold text-rose-600 transition-all hover:bg-rose-100 active:scale-95 disabled:opacity-50 sm:flex-initial dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60"
                                title="Reject this booking reservation"
                            >
                                <XCircle class="size-4" />
                                <span>Reject Booking</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
