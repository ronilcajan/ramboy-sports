<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    LayoutList,
    Table,
    Search,
    CheckCircle,
    XCircle,
    Trash2,
    ArrowUpRight,
    FileText,
    Plus,
} from '@lucide/vue';
import { ref } from 'vue';
import BookingDetailModal from '@/components/admin/BookingDetailModal.vue';
import type { BookingDetail } from '@/components/admin/BookingDetailModal.vue';
import BookingsCalendar from '@/components/admin/BookingsCalendar.vue';
import BookingsTableView from '@/components/admin/BookingsTableView.vue';
import type {
    TableDateHeader,
    TableBookingItem,
} from '@/components/admin/BookingsTableView.vue';
import CreateBookingModal from '@/components/admin/CreateBookingModal.vue';

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
    admin_notes?: string | null;
    court?: { id: number; name: string };
    user?: { id: number; name: string };
}

interface PaginatedBookings {
    data: Booking[];
    links: any[];
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    view: 'calendar' | 'list' | 'table';
    days?: any[];
    window?: any;
    tableDates?: TableDateHeader[];
    tableBookings?: TableBookingItem[];
    bookings?: PaginatedBookings;
    courts: { id: number; name: string }[];
    venues?: { id: number; name: string }[] | null;
    filters: {
        search?: string;
        court_id?: string;
        status?: string;
        date?: string;
        venue_id?: string;
    };
    basePath: string;
    canDelete: boolean;
    showVenueFilter: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Bookings', href: '/admin/bookings' },
        ],
    },
});

function switchView(view: 'calendar' | 'list' | 'table') {
    router.get(props.basePath, { view }, { preserveState: false });
}

const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedBookingForModal = ref<BookingDetail | null>(null);
const selectedBookingDate = ref<string | undefined>(undefined);
const selectedBookingSlot = ref<string | undefined>(undefined);

function handleCreateBooking(payload?: { date?: string; slot?: string }) {
    selectedBookingDate.value = payload?.date;
    selectedBookingSlot.value = payload?.slot;
    showCreateModal.value = true;
}

function openBookingDetails(booking: any) {
    const detail: BookingDetail = {
        id: booking.id,
        reference_code:
            booking.reference_code ||
            `DY-RESRV-${String(booking.id).padStart(6, '0')}`,
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
        admin_notes: booking.admin_notes,
        court_name: booking.court?.name || 'Assigned Court',
    };
    selectedBookingForModal.value = detail;
    showDetailModal.value = true;
}

// --- List view state ---
const search = ref(props.filters.search || '');
const court_id = ref(props.filters.court_id || '');
const status = ref(props.filters.status || '');
const date = ref(props.filters.date || '');
const venue_id = ref(props.filters.venue_id || '');

function applyFilters() {
    router.get(
        props.basePath,
        {
            view: 'list',
            search: search.value,
            court_id: court_id.value,
            status: status.value,
            date: date.value,
            venue_id: venue_id.value,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = '';
    court_id.value = '';
    status.value = '';
    date.value = '';
    venue_id.value = '';
    applyFilters();
}

const actionForm = useForm({ status: '' });

function updateStatus(bookingId: number, newStatus: string) {
    actionForm.status = newStatus;
    actionForm.patch(`${props.basePath}/${bookingId}/status`, {
        preserveScroll: true,
    });
}

function deleteBooking(bookingId: number) {
    if (confirm('Are you sure you want to delete this booking entry?')) {
        actionForm.delete(`${props.basePath}/${bookingId}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Bookings" />

    <div class="w-full space-y-6 p-6">
        <div
            class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
                    Facility Bookings
                </h1>
                <p class="text-xs text-neutral-500">
                    Monitor, approve, and manage reservations via calendar,
                    table grid, or list.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <!-- View toggle -->
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
                        @click="switchView('table')"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                            view === 'table'
                                ? 'bg-emerald-600 text-white'
                                : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800',
                        ]"
                    >
                        <Table class="h-4 w-4" /> Table View
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
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
                >
                    <Plus class="h-4 w-4" /> New Booking
                </button>
            </div>
        </div>

        <CreateBookingModal
            :open="showCreateModal"
            :courts="courts"
            :action="basePath"
            :initial-date="selectedBookingDate"
            :initial-slot="selectedBookingSlot"
            @close="showCreateModal = false"
        />

        <!-- Calendar board -->
        <BookingsCalendar
            v-if="view === 'calendar'"
            :days="days || []"
            :courts="courts"
            :venues="venues"
            :filters="filters"
            :window="window"
            :base-path="basePath"
            :can-delete="canDelete"
            :show-venue-filter="showVenueFilter"
            :can-update="true"
        />

        <!-- Table View Matrix -->
        <BookingsTableView
            v-else-if="view === 'table'"
            :table-dates="tableDates || []"
            :table-bookings="tableBookings || []"
            :courts="courts"
            :venues="venues"
            :filters="filters"
            :base-path="basePath"
            :show-venue-filter="showVenueFilter"
            @select-booking="openBookingDetails"
            @create-booking="handleCreateBooking"
        />

        <!-- List view -->
        <template v-else>
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
                            placeholder="Search name, email, phone..."
                            class="w-full rounded-xl border border-neutral-300 bg-neutral-50 py-2 pr-3 pl-9 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        />
                    </div>

                    <select
                        v-if="showVenueFilter"
                        v-model="venue_id"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="">All Locations</option>
                        <option
                            v-for="v in venues || []"
                            :key="v.id"
                            :value="v.id"
                        >
                            {{ v.name }}
                        </option>
                    </select>

                    <select
                        v-model="court_id"
                        @change="applyFilters"
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="">All Courts</option>
                        <option v-for="c in courts" :key="c.id" :value="c.id">
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
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <input
                        v-model="date"
                        @change="applyFilters"
                        type="date"
                        class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button
                        @click="applyFilters"
                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                    >
                        Filter
                    </button>
                    <button
                        @click="clearFilters"
                        class="rounded-lg bg-neutral-200 px-3 py-1.5 text-xs font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                    >
                        Clear
                    </button>
                </div>
            </div>

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
                            <th class="px-3 py-3">Amount</th>
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
                            <td
                                class="px-3 py-3 font-mono font-bold text-neutral-900 dark:text-white"
                            >
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
                                class="px-3 py-3 font-medium text-neutral-800 dark:text-neutral-200"
                            >
                                {{ b.court?.name || 'N/A' }}
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
                                    <button
                                        v-if="
                                            b.status !== 'approved' &&
                                            b.status !== 'confirmed'
                                        "
                                        @click="updateStatus(b.id, 'approved')"
                                        class="p-1 text-emerald-600 hover:text-emerald-700"
                                        title="Approve"
                                    >
                                        <CheckCircle class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="b.status !== 'rejected'"
                                        @click="updateStatus(b.id, 'rejected')"
                                        class="p-1 text-rose-600 hover:text-rose-700"
                                        title="Reject"
                                    >
                                        <XCircle class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="openBookingDetails(b)"
                                        class="p-1 text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                                        title="View Details"
                                    >
                                        <ArrowUpRight class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="canDelete"
                                        @click="deleteBooking(b.id)"
                                        class="p-1 text-rose-400 hover:text-rose-600"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="(bookings?.data.length || 0) === 0">
                            <td
                                colSpan="10"
                                class="py-8 text-center text-xs text-neutral-400"
                            >
                                No bookings found matching filter criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <BookingDetailModal
            :is-open="showDetailModal"
            :booking="selectedBookingForModal"
            :update-route-prefix="basePath"
            @close="showDetailModal = false"
        />
    </div>
</template>
