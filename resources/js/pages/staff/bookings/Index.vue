<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    LayoutList,
    Search,
    CheckCircle,
    XCircle,
    ArrowUpRight,
    FileText,
    Plus,
    X,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import BookingDetailModal from '@/components/admin/BookingDetailModal.vue';
import type { BookingDetail } from '@/components/admin/BookingDetailModal.vue';
import BookingsCalendar from '@/components/admin/BookingsCalendar.vue';

interface Booking {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_path?: string | null;
    receipt_url?: string | null;
    status: string;
    notes?: string;
    court?: { id: number; name: string };
}

interface PaginatedBookings {
    data: Booking[];
    links: any[];
}

const props = defineProps<{
    view: 'calendar' | 'list';
    days?: any[];
    window?: any;
    bookings?: PaginatedBookings;
    courts: { id: number; name: string }[];
    assignedCourts: { id: number; name: string }[];
    filters: {
        search?: string;
        court_id?: string;
        status?: string;
        date?: string;
    };
    basePath: string;
    canDelete: boolean;
    showVenueFilter: boolean;
}>();

function switchView(view: 'calendar' | 'list') {
    router.get(props.basePath, { view }, { preserveState: false });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
            { title: 'Court Bookings', href: '/staff/bookings' },
        ],
    },
});

const search = ref(props.filters.search || '');
const court_id = ref(props.filters.court_id || '');
const status = ref(props.filters.status || '');
const date = ref(props.filters.date || '');

function applyFilters() {
    router.get(
        props.basePath,
        {
            view: 'list',
            search: search.value,
            court_id: court_id.value,
            status: status.value,
            date: date.value,
        },
        { preserveState: true, replace: true },
    );
}

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
const canUpdate = computed(() => {
    return (
        user.value?.is_super_admin ||
        user.value?.is_admin ||
        user.value?.is_staff
    );
});

const showDetailModal = ref(false);
const selectedBookingForModal = ref<BookingDetail | null>(null);

function openBookingDetails(booking: Booking) {
    const detail: BookingDetail = {
        id: booking.id,
        reference_code: `DY-RESRV-${String(booking.id).padStart(6, '0')}`,
        customer_name: booking.name,
        email: booking.email,
        phone: booking.phone,
        date: booking.date,
        time_slots: booking.time_slots,
        total_price: booking.total_price,
        receipt_url:
            booking.receipt_url ||
            (booking.receipt_path ? `/storage/${booking.receipt_path}` : null),
        status: booking.status,
        notes: booking.notes,
        court_name: booking.court?.name || 'Assigned Court',
    };
    selectedBookingForModal.value = detail;
    showDetailModal.value = true;
}

const actionForm = useForm({
    status: '',
});

function updateStatus(bookingId: number, newStatus: string) {
    if (!canUpdate.value) {
        return;
    }

    actionForm.status = newStatus;
    actionForm.patch(`${props.basePath}/${bookingId}/status`, {
        preserveScroll: true,
    });
}

const showCreateModal = ref(false);

const availableSlots = [
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
];

const createForm = useForm({
    court_id: props.assignedCourts[0]?.id || '',
    name: '',
    email: '',
    phone: '',
    date: new Date().toISOString().slice(0, 10),
    time_slots: [] as string[],
    notes: '',
});

function submitCreate() {
    createForm.post('/staff/bookings', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Assigned Court Bookings - Court Staff" />

    <div class="w-full space-y-6 p-6">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
                    Assigned Court Bookings
                </h1>
                <p class="text-xs text-neutral-500">
                    Manage, view, or log new booking entries for your assigned
                    court(s).
                </p>
            </div>

            <div class="flex items-center gap-2">
                <div
                    class="inline-flex rounded-xl border border-neutral-200 bg-white p-1 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <button
                        @click="switchView('calendar')"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                            view === 'calendar'
                                ? 'bg-emerald-600 text-white'
                                : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800',
                        ]"
                    >
                        <CalendarDays class="h-4 w-4" /> Calendar
                    </button>
                    <button
                        @click="switchView('list')"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                            view === 'list'
                                ? 'bg-emerald-600 text-white'
                                : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800',
                        ]"
                    >
                        <LayoutList class="h-4 w-4" /> List
                    </button>
                </div>

                <button
                    @click="showCreateModal = true"
                    class="flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
                >
                    <Plus class="h-4 w-4" /> Create New Booking
                </button>
            </div>
        </div>

        <!-- Calendar board -->
        <BookingsCalendar
            v-if="view === 'calendar'"
            :days="days || []"
            :courts="courts"
            :filters="filters"
            :window="window"
            :base-path="basePath"
            :can-delete="canDelete"
            :show-venue-filter="showVenueFilter"
            :can-update="canUpdate"
        />

        <template v-else>
            <!-- Filters Bar -->
            <div
                class="space-y-3 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div class="relative">
                        <Search
                            class="absolute top-2.5 left-3 h-4 w-4 text-neutral-400"
                        />
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search customer name..."
                            class="w-full rounded-xl border border-neutral-300 bg-neutral-50 py-2 pr-3 pl-9 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        />
                    </div>

                    <select
                        v-if="assignedCourts.length > 1"
                        v-model="court_id"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="">All Assigned Courts</option>
                        <option
                            v-for="c in assignedCourts"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>

                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <input
                        v-model="date"
                        @change="applyFilters"
                        type="date"
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                </div>
            </div>

            <!-- Bookings Table -->
            <div
                class="overflow-x-auto rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr
                            class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                        >
                            <th class="px-3 py-3">ID</th>
                            <th class="px-3 py-3">Customer Name</th>
                            <th class="px-3 py-3">Contact</th>
                            <th class="px-3 py-3">Court</th>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Time Slots</th>
                            <th class="px-3 py-3">Total</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Receipt</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-neutral-100 dark:divide-neutral-800"
                    >
                        <tr
                            v-for="b in bookings?.data || []"
                            :key="b.id"
                            @click="openBookingDetails(b)"
                            class="cursor-pointer transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                        >
                            <td class="px-3 py-3 font-mono font-bold">
                                #{{ b.id }}
                            </td>
                            <td
                                class="px-3 py-3 font-semibold text-neutral-900 dark:text-white"
                            >
                                {{ b.name }}
                            </td>
                            <td class="px-3 py-3 text-neutral-500">
                                <div>{{ b.email }}</div>
                                <div>{{ b.phone }}</div>
                            </td>
                            <td
                                class="px-3 py-3 text-neutral-800 dark:text-neutral-200"
                            >
                                {{ b.court?.name }}
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
                                    b.time_slots ? b.time_slots.join(', ') : ''
                                }}
                            </td>
                            <td class="px-3 py-3 font-bold text-emerald-600">
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
                                    v-if="b.receipt_url || b.receipt_path"
                                    :href="
                                        b.receipt_url ||
                                        '/storage/' + b.receipt_path
                                    "
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                >
                                    <FileText class="h-3.5 w-3.5" /> View
                                    Receipt
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
                                    <template v-if="canUpdate">
                                        <button
                                            v-if="
                                                b.status !== 'approved' &&
                                                b.status !== 'confirmed'
                                            "
                                            @click="
                                                updateStatus(b.id, 'approved')
                                            "
                                            class="p-1 text-emerald-600 hover:text-emerald-700"
                                            title="Approve Request"
                                        >
                                            <CheckCircle class="h-4 w-4" />
                                        </button>
                                        <button
                                            v-if="b.status !== 'rejected'"
                                            @click="
                                                updateStatus(b.id, 'rejected')
                                            "
                                            class="p-1 text-rose-600 hover:text-rose-700"
                                            title="Reject Request"
                                        >
                                            <XCircle class="h-4 w-4" />
                                        </button>
                                    </template>
                                    <button
                                        type="button"
                                        @click="openBookingDetails(b)"
                                        class="p-1 text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                                        title="View Details"
                                    >
                                        <ArrowUpRight class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="(bookings?.data.length || 0) === 0">
                            <td
                                colSpan="10"
                                class="py-8 text-center text-xs text-neutral-400"
                            >
                                No bookings recorded for assigned court.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Centered Create Booking Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
            @click.self="showCreateModal = false"
        >
            <div
                class="my-8 w-full max-w-lg space-y-5 rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-base font-bold text-neutral-900 dark:text-white"
                        >
                            Log New Booking
                        </h3>
                        <p class="text-xs text-neutral-500">
                            Register a new reservation for an assigned court.
                        </p>
                    </div>
                    <button
                        @click="showCreateModal = false"
                        class="text-neutral-400 hover:text-neutral-600 dark:hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div>
                        <label
                            class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                            >Target Court *</label
                        >
                        <select
                            v-model="createForm.court_id"
                            required
                            class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        >
                            <option
                                v-for="c in assignedCourts"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Customer Full Name *</label
                            >
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="John Doe"
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Customer Email *</label
                            >
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                placeholder="customer@example.com"
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Contact Phone *</label
                            >
                            <input
                                v-model="createForm.phone"
                                type="text"
                                required
                                placeholder="(555) 000-0000"
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Booking Date *</label
                            >
                            <input
                                v-model="createForm.date"
                                type="date"
                                required
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block font-medium text-neutral-700 dark:text-neutral-300"
                            >Select Reserved Time Slots *</label
                        >
                        <div
                            class="grid max-h-36 grid-cols-2 gap-2 overflow-y-auto rounded-xl border border-neutral-200 p-1 sm:grid-cols-3 dark:border-neutral-800"
                        >
                            <label
                                v-for="slot in availableSlots"
                                :key="slot"
                                class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-50 px-2 py-1.5 dark:border-neutral-700 dark:bg-neutral-800"
                            >
                                <input
                                    type="checkbox"
                                    :value="slot"
                                    v-model="createForm.time_slots"
                                    class="rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="font-mono text-[11px]">{{
                                    slot
                                }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                            >Notes / Internal Reference</label
                        >
                        <input
                            v-model="createForm.notes"
                            type="text"
                            placeholder="Walk-in payment, cash, phone reservation..."
                            class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="rounded-xl bg-neutral-100 px-4 py-2 font-medium text-neutral-700 transition-colors hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="
                                createForm.processing ||
                                createForm.time_slots.length === 0
                            "
                            class="rounded-xl bg-emerald-600 px-5 py-2 font-semibold text-white shadow transition-colors hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Create Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <BookingDetailModal
        :is-open="showDetailModal"
        :booking="selectedBookingForModal"
        :update-route-prefix="basePath"
        @close="showDetailModal = false"
    />
</template>
