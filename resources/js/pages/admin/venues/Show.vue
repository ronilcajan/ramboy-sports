<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building,
    MapPin,
    Phone,
    Mail,
    Dumbbell,
    CheckCircle,
    XCircle,
    Trash2,
    ArrowUpRight,
    FileText,
    Search,
    Eye,
    CalendarPlus,
    Calendar,
    Image as ImageIcon,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import BookingDetailModal from '@/components/admin/BookingDetailModal.vue';
import type { BookingDetail } from '@/components/admin/BookingDetailModal.vue';
import CreateBookingModal from '@/components/admin/CreateBookingModal.vue';
import VenueImageManagerModal from '@/components/admin/VenueImageManagerModal.vue';
import type { VenueImageItem } from '@/components/admin/VenueImageManagerModal.vue';
import VenueScheduleTab from '@/components/admin/VenueScheduleTab.vue';

interface VenueProfile {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    image_url?: string | null;
    cover_image_url?: string | null;
    gcash_number?: string | null;
    gcash_qr_url?: string | null;
    maya_number?: string | null;
    maya_qr_url?: string | null;
    is_active: boolean;
    images?: VenueImageItem[];
    courts_count: number;
    created_at: string;
}

interface CourtItem {
    id: number;
    name: string;
    slug: string;
    sport_type: string;
    description?: string | null;
    base_price: string;
    slot_duration_minutes: number;
    is_active: boolean;
    status: string;
    primary_image_url?: string | null;
}

interface BookingItem {
    id: number;
    reference_code: string;
    court_name: string;
    sport_type?: string;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    status: string;
    notes?: string | null;
    created_at: string;
}

interface PaginatedBookings {
    data: BookingItem[];
    links: any[];
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    venue: VenueProfile;
    courts: CourtItem[];
    bookings: PaginatedBookings;
    filters: { search?: string; status?: string };
    canDelete: boolean;
    canManageVenue: boolean;
}>();

const showVenueImageModal = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Locations', href: '/admin/venues' },
            { title: 'Location Profile' },
        ],
    },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const showDetailModal = ref(false);
const selectedBooking = ref<BookingDetail | null>(null);
const isBookingModalOpen = ref(false);
const activeTab = ref<'bookings' | 'schedule'>('bookings');
const modalInitialDate = ref<string | undefined>(undefined);
const modalInitialCourtId = ref<number | undefined>(undefined);
const modalInitialSlot = ref<string | undefined>(undefined);

function handleBookSlot(payload: { court: any; date: string; slot?: string }) {
    modalInitialCourtId.value = payload.court?.id;
    modalInitialDate.value = payload.date;
    modalInitialSlot.value = payload.slot;
    isBookingModalOpen.value = true;
}

// Only pass courts from this venue to the booking modal
const venueCourts = computed(() =>
    props.courts
        .filter((c) => c.is_active)
        .map((c) => ({
            id: c.id,
            name: c.name,
            base_price: c.base_price,
            slot_prices: null,
        })),
);

function applyFilters() {
    router.get(
        `/admin/venues/${props.venue.id}`,
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = '';
    status.value = '';
    applyFilters();
}

function openBookingDetails(b: BookingItem) {
    selectedBooking.value = {
        id: b.id,
        reference_code: b.reference_code,
        name: b.name,
        email: b.email,
        phone: b.phone,
        court_name: b.court_name,
        sport_type: b.sport_type,
        date: b.date,
        time_slots: b.time_slots,
        total_price: b.total_price,
        receipt_url: b.receipt_url,
        status: b.status,
        notes: b.notes,
        created_at: b.created_at,
    };
    showDetailModal.value = true;
}

function updateBookingStatus(bookingId: number, newStatus: string) {
    router.patch(
        `/admin/bookings/${bookingId}/status`,
        { status: newStatus },
        { preserveScroll: true },
    );
}

function deleteBooking(bookingId: number) {
    if (confirm('Delete this booking permanently?')) {
        router.delete(`/admin/bookings/${bookingId}`, { preserveScroll: true });
    }
}

function removeVenueImage() {
    if (confirm('Permanently remove this venue cover photo?')) {
        router.delete(`/admin/venues/${props.venue.id}/image`, {
            preserveScroll: true,
        });
    }
}

const activeCourtCount = computed(
    () => props.courts.filter((c) => c.is_active).length,
);
</script>

<template>
    <Head :title="`${venue.name} – Venue Profile`" />

    <div class="w-full space-y-8 p-6">
        <!-- Back link -->
        <Link
            href="/admin/venues"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-500 transition-colors hover:text-emerald-600"
        >
            <ArrowLeft class="h-3.5 w-3.5" /> Back to Locations
        </Link>

        <!-- Venue Header Card -->
        <div
            class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <!-- Cover Image Banner -->
            <div
                class="relative h-44 overflow-hidden bg-neutral-100 sm:h-56 dark:bg-neutral-800"
            >
                <img
                    v-if="venue.image_url || venue.cover_image_url"
                    :src="venue.image_url || venue.cover_image_url || ''"
                    :alt="venue.name"
                    class="h-full w-full object-cover"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600"
                >
                    <Building class="h-16 w-16" />
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"
                />

                <!-- Delete Venue Image Action -->
                <button
                    v-if="venue.image_url"
                    type="button"
                    @click="removeVenueImage"
                    class="absolute top-4 left-4 inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-rose-600/90 px-3 py-1 text-[11px] font-bold text-white shadow-md backdrop-blur-md transition-all hover:bg-rose-700"
                    title="Remove Cover Image"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Remove Cover Photo</span>
                </button>

                <span
                    :class="[
                        'absolute top-4 right-4 rounded-full px-3 py-1 text-[11px] font-bold capitalize shadow-md',
                        venue.is_active
                            ? 'bg-emerald-500 text-white'
                            : 'bg-rose-500 text-white',
                    ]"
                >
                    {{ venue.is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <!-- Venue Info Below Cover -->
            <div class="p-6 sm:p-8">
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div>
                        <h1
                            class="text-2xl font-bold text-neutral-900 dark:text-white"
                        >
                            {{ venue.name }}
                        </h1>
                        <p
                            v-if="venue.description"
                            class="mt-1 max-w-2xl text-sm text-neutral-500"
                        >
                            {{ venue.description }}
                        </p>

                        <div
                            class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs text-neutral-500"
                        >
                            <span
                                v-if="venue.address"
                                class="inline-flex items-center gap-1.5"
                            >
                                <MapPin
                                    class="h-3.5 w-3.5 text-neutral-400"
                                />{{ venue.address }}
                            </span>
                            <span
                                v-if="venue.phone"
                                class="inline-flex items-center gap-1.5"
                            >
                                <Phone class="h-3.5 w-3.5 text-neutral-400" />{{
                                    venue.phone
                                }}
                            </span>
                            <span
                                v-if="venue.email"
                                class="inline-flex items-center gap-1.5"
                            >
                                <Mail class="h-3.5 w-3.5 text-neutral-400" />{{
                                    venue.email
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Venue actions -->
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="showVenueImageModal = true"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                        >
                            <ImageIcon class="h-4 w-4" />
                            <span
                                >Photos ({{ venue.images?.length ?? 0 }})</span
                            >
                        </button>
                        <div
                            class="rounded-xl border border-neutral-200 px-4 py-3 text-center dark:border-neutral-700"
                        >
                            <p class="text-xl font-black text-emerald-600">
                                {{ venue.courts_count }}
                            </p>
                            <p
                                class="text-[10px] font-semibold tracking-wider text-neutral-400 uppercase"
                            >
                                Courts
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-neutral-200 px-4 py-3 text-center dark:border-neutral-700"
                        >
                            <p class="text-xl font-black text-blue-600">
                                {{ activeCourtCount }}
                            </p>
                            <p
                                class="text-[10px] font-semibold tracking-wider text-neutral-400 uppercase"
                            >
                                Active
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Payment methods badges -->
                <div
                    v-if="venue.gcash_number || venue.maya_number"
                    class="mt-5 flex flex-wrap gap-2"
                >
                    <span
                        v-if="venue.gcash_number"
                        class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"
                    >
                        GCash: {{ venue.gcash_number }}
                    </span>
                    <span
                        v-if="venue.maya_number"
                        class="inline-flex items-center gap-1 rounded-full bg-green-50 px-3 py-1 text-[11px] font-semibold text-green-700 dark:bg-green-950/40 dark:text-green-300"
                    >
                        Maya: {{ venue.maya_number }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Courts Section -->
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">
                    Courts
                </h2>
                <p class="text-xs text-neutral-500">
                    All courts registered under this location.
                </p>
            </div>

            <div
                v-if="courts.length"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Link
                    v-for="court in courts"
                    :key="court.id"
                    :href="`/admin/courts/${court.id}`"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition-all hover:border-emerald-400/50 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div
                        class="relative aspect-video overflow-hidden bg-neutral-100 dark:bg-neutral-800"
                    >
                        <img
                            v-if="court.primary_image_url"
                            :src="court.primary_image_url"
                            :alt="court.name"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600"
                        >
                            <Dumbbell class="h-8 w-8" />
                        </div>
                        <span
                            :class="[
                                'absolute top-2 right-2 rounded-full px-2 py-0.5 text-[10px] font-bold capitalize shadow-sm',
                                court.is_active
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                            ]"
                        >
                            {{ court.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col gap-1.5 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h3
                                class="text-sm leading-tight font-semibold text-neutral-900 dark:text-white"
                            >
                                {{ court.name }}
                            </h3>
                            <span
                                class="shrink-0 rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] font-medium text-neutral-500 dark:bg-neutral-800"
                            >
                                {{ court.sport_type }}
                            </span>
                        </div>
                        <p class="line-clamp-2 text-xs text-neutral-500">
                            {{ court.description || 'No description' }}
                        </p>
                        <div
                            class="mt-auto flex items-center justify-between border-t border-neutral-100 pt-2 dark:border-neutral-800"
                        >
                            <span class="text-sm font-bold text-emerald-600"
                                >₱{{ court.base_price
                                }}<span
                                    class="text-[10px] font-normal text-neutral-400"
                                    >/hr</span
                                ></span
                            >
                            <span
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-400 transition-colors group-hover:text-emerald-500"
                            >
                                <Eye class="h-3.5 w-3.5" /> View
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <div
                v-else
                class="rounded-2xl border border-dashed border-neutral-300 py-12 text-center text-sm text-neutral-500 dark:border-neutral-700"
            >
                No courts have been added to this location yet.
            </div>
        </section>

        <!-- Tabs: Bookings / Availability Schedule -->
        <section class="space-y-4">
            <!-- Tab Switcher -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="flex items-center gap-1 rounded-xl border border-neutral-200 bg-neutral-100 p-1 dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <button
                        type="button"
                        @click="activeTab = 'bookings'"
                        :class="[
                            'flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200',
                            activeTab === 'bookings'
                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-800 dark:text-white'
                                : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300',
                        ]"
                    >
                        <FileText class="h-3.5 w-3.5" />
                        Bookings
                        <span
                            class="ml-0.5 rounded-full bg-neutral-200 px-1.5 py-0.5 text-[10px] font-bold text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300"
                        >
                            {{ bookings.data.length }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'schedule'"
                        :class="[
                            'flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200',
                            activeTab === 'schedule'
                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-800 dark:text-white'
                                : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300',
                        ]"
                    >
                        <Calendar class="h-3.5 w-3.5" />
                        Availability Schedule
                    </button>
                </div>

                <!-- New Booking button (only show on bookings tab) -->
                <button
                    v-if="activeTab === 'bookings'"
                    type="button"
                    @click="isBookingModalOpen = true"
                    :disabled="venueCourts.length === 0"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    :title="
                        venueCourts.length === 0
                            ? 'No active courts available'
                            : 'Create a new booking for this venue'
                    "
                >
                    <CalendarPlus class="h-4 w-4" />
                    New Booking
                </button>
            </div>

            <!-- BOOKINGS TAB -->
            <div v-if="activeTab === 'bookings'" class="space-y-4">
                <!-- Filters -->
                <div
                    class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <div class="relative flex-1">
                            <Search
                                class="absolute top-2.5 left-3 h-4 w-4 text-neutral-400"
                            />
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Search by name, email, phone, or ID..."
                                class="w-full rounded-xl border border-neutral-300 bg-neutral-50 py-2 pr-3 pl-9 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>

                        <select
                            v-model="status"
                            @change="applyFilters"
                            class="rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>

                        <div class="flex gap-2">
                            <button
                                @click="applyFilters"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                            >
                                Search
                            </button>
                            <button
                                @click="clearFilters"
                                class="rounded-lg bg-neutral-200 px-3 py-1.5 text-xs font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                            >
                                Clear
                            </button>
                        </div>
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
                                <th class="px-3 py-3">Ref</th>
                                <th class="px-3 py-3">Customer</th>
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
                                v-for="b in bookings.data"
                                :key="b.id"
                                @click="openBookingDetails(b)"
                                class="cursor-pointer transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                            >
                                <td
                                    class="px-3 py-3 font-mono font-bold whitespace-nowrap text-neutral-900 dark:text-white"
                                >
                                    {{ b.reference_code }}
                                </td>
                                <td class="px-3 py-3">
                                    <div
                                        class="font-semibold text-neutral-900 dark:text-white"
                                    >
                                        {{ b.name }}
                                    </div>
                                    <div class="text-neutral-400">
                                        {{ b.email }}
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-3 font-medium text-neutral-800 dark:text-neutral-200"
                                >
                                    {{ b.court_name }}
                                </td>
                                <td
                                    class="px-3 py-3 font-semibold text-emerald-600 dark:text-emerald-400"
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
                                            b.status === 'confirmed' ||
                                            b.status === 'completed'
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
                                        v-if="b.receipt_url"
                                        :href="b.receipt_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                    >
                                        <FileText class="h-3.5 w-3.5" /> View
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
                                            @click="
                                                updateBookingStatus(
                                                    b.id,
                                                    'approved',
                                                )
                                            "
                                            class="p-1 text-emerald-600 hover:text-emerald-700"
                                            title="Approve"
                                        >
                                            <CheckCircle class="h-4 w-4" />
                                        </button>
                                        <button
                                            v-if="b.status !== 'rejected'"
                                            @click="
                                                updateBookingStatus(
                                                    b.id,
                                                    'rejected',
                                                )
                                            "
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

                            <tr v-if="bookings.data.length === 0">
                                <td
                                    colspan="9"
                                    class="py-8 text-center text-xs text-neutral-400"
                                >
                                    No bookings found for this location.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="bookings.last_page > 1"
                    class="flex justify-center gap-1 pt-2"
                >
                    <template v-for="link in bookings.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                                link.active
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-neutral-100 text-neutral-600 hover:bg-emerald-50 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700',
                            ]"
                            preserve-scroll
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-neutral-300 dark:text-neutral-600"
                            v-html="link.label"
                        />
                    </template>
                </div>
                <!-- end pagination -->
            </div>
            <!-- end bookings tab -->

            <!-- AVAILABILITY SCHEDULE TAB -->
            <div v-if="activeTab === 'schedule'">
                <VenueScheduleTab
                    :courts="courts"
                    :venue-name="venue.name"
                    @book-slot="handleBookSlot"
                />
            </div>
        </section>

        <BookingDetailModal
            :is-open="showDetailModal"
            :booking="selectedBooking"
            update-route-prefix="/admin/bookings"
            @close="showDetailModal = false"
        />

        <!-- New Booking Modal — scoped to this venue's courts -->
        <CreateBookingModal
            :open="isBookingModalOpen"
            :courts="venueCourts"
            :initial-date="modalInitialDate"
            :initial-court-id="modalInitialCourtId"
            :initial-slot="modalInitialSlot"
            action="/admin/bookings"
            @close="isBookingModalOpen = false"
        />

        <!-- Venue Photo Gallery Modal — bound to the live prop so uploads appear immediately -->
        <VenueImageManagerModal
            :is-open="showVenueImageModal"
            :venue="{
                id: venue.id,
                name: venue.name,
                images: venue.images ?? [],
            }"
            :can-delete="canManageVenue"
            @close="showVenueImageModal = false"
        />
    </div>
</template>
