<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Calendar,
    Clock,
    ChevronLeft,
    ChevronRight,
    Plus,
    BarChart2,
    DollarSign,
    RotateCcw,
} from '@lucide/vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    formatSlotRange,
    getMergedTimeSlots,
    isSlotPassed,
} from '@/utils/timeSlots';

interface CourtOption {
    id: number;
    name: string;
    sport_type?: string;
    base_price?: string | number;
    slot_duration_minutes?: number | null;
}

interface VenueOption {
    id: number;
    name: string;
}

export interface TableDateHeader {
    dateStr: string;
    dayName: string;
    dayNum: string;
    monthName: string;
    formatted: string;
    isToday: boolean;
}

export interface TableBookingItem {
    id: number;
    reference_code: string;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    status: string;
    receipt_url?: string | null;
    notes?: string | null;
    court?: {
        id: number;
        name: string;
        sport_type?: string;
        slot_duration_minutes?: number | null;
    } | null;
    user?: { id: number; name: string } | null;
}

const props = defineProps<{
    tableDates: TableDateHeader[];
    tableBookings: TableBookingItem[];
    courts: CourtOption[];
    venues?: VenueOption[] | null;
    filters: {
        search?: string;
        court_id?: string;
        status?: string;
        date?: string;
        venue_id?: string;
    };
    basePath: string;
    showVenueFilter: boolean;
}>();

const emit = defineEmits<{
    (e: 'select-booking', booking: TableBookingItem): void;
    (e: 'create-booking', payload?: { date?: string; slot?: string }): void;
}>();

const timeSlots = computed(() => {
    const bookedSlots = props.tableBookings.flatMap((b) => b.time_slots || []);

    return getMergedTimeSlots(bookedSlots);
});

function getLocalDateString(d: Date = new Date()): string {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const currentNow = ref(new Date());
let clockIntervalId: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    clockIntervalId = setInterval(() => {
        currentNow.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (clockIntervalId) {
        clearInterval(clockIntervalId);
        clockIntervalId = null;
    }
});

const currentLocalDateStr = computed(() =>
    getLocalDateString(currentNow.value),
);

const isEveningOrNight = computed(() => {
    const hours = currentNow.value.getHours();

    return hours >= 17 || hours < 5; // 5:00 PM (17:00) onwards through late-night hours before 5:00 AM
});

function isCurrentDate(d: TableDateHeader | string): boolean {
    const dateStr = typeof d === 'string' ? d : d.dateStr;
    const now = currentNow.value;
    const opDate = new Date(now);

    if (opDate.getHours() < 5) {
        opDate.setDate(opDate.getDate() - 1);
    }

    const operatingDateStr = getLocalDateString(opDate);

    return (
        dateStr === operatingDateStr || dateStr === currentLocalDateStr.value
    );
}

function getDateHeaderLabel(d: TableDateHeader): string {
    if (isCurrentDate(d)) {
        return isEveningOrNight.value ? 'Tonight' : 'Today';
    }

    return d.dayName;
}

const selectedDate = ref(
    props.filters.date || props.tableDates[0]?.dateStr || getLocalDateString(),
);
const court_id = ref(props.filters.court_id || '');
const status = ref(props.filters.status || '');
const venue_id = ref(props.filters.venue_id || '');

function applyFilters() {
    router.get(
        props.basePath,
        {
            view: 'table',
            date: selectedDate.value,
            court_id: court_id.value || undefined,
            status: status.value || undefined,
            venue_id: venue_id.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    selectedDate.value = getLocalDateString();
    court_id.value = '';
    status.value = '';
    venue_id.value = '';
    applyFilters();
}

function shiftDays(daysOffset: number) {
    const current = new Date(selectedDate.value || new Date());
    current.setDate(current.getDate() + daysOffset);
    selectedDate.value = current.toISOString().split('T')[0];
    applyFilters();
}

/** Statuses that hand the hour back — mirrors Booking::RELEASED_STATUSES. */
const releasedStatuses = ['rejected', 'cancelled'];

// Index every booking by date+slot up front; a slot can hold more than one
// (a rejection and the confirmed booking that replaced it, for example).
const cellIndex = computed(() => {
    const map = new Map<string, TableBookingItem[]>();

    for (const booking of props.tableBookings) {
        if (!Array.isArray(booking.time_slots)) {
            continue;
        }

        for (const slot of booking.time_slots) {
            const key = `${booking.date}|${slot}`;
            const existing = map.get(key);

            if (existing) {
                existing.push(booking);
            } else {
                map.set(key, [booking]);
            }
        }
    }

    return map;
});

function bookingsForCell(dateStr: string, slot: string): TableBookingItem[] {
    return cellIndex.value.get(`${dateStr}|${slot}`) ?? [];
}

function isReleased(statusStr: string): boolean {
    return releasedStatuses.includes(statusStr);
}

/** A slot stays bookable unless something in it still holds the hour. */
function cellIsOpen(dateStr: string, slot: string): boolean {
    return !bookingsForCell(dateStr, slot).some((b) => !isReleased(b.status));
}

function cellStatusClass(statusStr: string): string {
    switch (statusStr) {
        case 'approved':
        case 'confirmed':
        case 'completed':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25';
        case 'pending':
            return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30 hover:bg-amber-500/25';
        case 'rejected':
        case 'cancelled':
            return 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30';
        default:
            return 'bg-neutral-500/15 text-neutral-700 dark:text-neutral-300 border-neutral-500/30';
    }
}

// Summary Calculation per date
function getBookingsForDate(dateStr: string): TableBookingItem[] {
    const map = new Map<number, TableBookingItem>();
    props.tableBookings.forEach((b) => {
        if (b.date === dateStr) {
            map.set(b.id, b);
        }
    });

    return Array.from(map.values());
}

function getCountByStatus(
    dateStr: string,
    statusType: 'confirmed' | 'pending' | 'rejected',
): number {
    const list = getBookingsForDate(dateStr);

    if (statusType === 'confirmed') {
        return list.filter(
            (b) => b.status === 'approved' || b.status === 'confirmed',
        ).length;
    }

    if (statusType === 'pending') {
        return list.filter((b) => b.status === 'pending').length;
    }

    if (statusType === 'rejected') {
        return list.filter(
            (b) => b.status === 'rejected' || b.status === 'cancelled',
        ).length;
    }

    return 0;
}

function getTotalCountForDate(dateStr: string): number {
    return getBookingsForDate(dateStr).length;
}

/**
 * Hours actually reserved on a date. A slot is not always an hour, so each booking
 * is measured by its court's slot length rather than by counting slots.
 */
function getTotalHoursForDate(dateStr: string): number {
    return getBookingsForDate(dateStr)
        .filter(
            (b) =>
                b.status === 'approved' ||
                b.status === 'confirmed' ||
                b.status === 'completed',
        )
        .reduce(
            (sum, b) =>
                sum +
                (b.time_slots?.length || 0) *
                    ((b.court?.slot_duration_minutes || 60) / 60),
            0,
        );
}

export interface DurationMetric {
    hours: number | string;
    minutes: number | string;
    seconds: number | string;
}

const DURATION_STORAGE_KEY = 'admin_manual_duration_booked';

function loadStoredDurations(): Record<string, DurationMetric> {
    try {
        const stored = localStorage.getItem(DURATION_STORAGE_KEY);

        if (stored) {
            return JSON.parse(stored);
        }

        const prev = localStorage.getItem('admin_manual_hours_booked');

        if (prev) {
            const parsed = JSON.parse(prev);
            const migrated: Record<string, DurationMetric> = {};

            for (const [k, v] of Object.entries(parsed)) {
                const totalSec = Math.round(
                    (parseFloat(String(v)) || 0) * 3600,
                );
                migrated[k] = {
                    hours: Math.floor(totalSec / 3600),
                    minutes: Math.floor((totalSec % 3600) / 60),
                    seconds: totalSec % 60,
                };
            }

            return migrated;
        }
    } catch {
        // fallback
    }

    return {};
}

const manualDurations = ref<Record<string, DurationMetric>>(
    loadStoredDurations(),
);

function getDuration(dateStr: string): DurationMetric {
    if (!manualDurations.value[dateStr]) {
        const calcHours = getTotalHoursForDate(dateStr);
        const totalSec = Math.round(calcHours * 3600);
        manualDurations.value[dateStr] = {
            hours: Math.floor(totalSec / 3600),
            minutes: Math.floor((totalSec % 3600) / 60),
            seconds: totalSec % 60,
        };
    }

    return manualDurations.value[dateStr];
}

watch(
    manualDurations,
    (newVal) => {
        try {
            localStorage.setItem(DURATION_STORAGE_KEY, JSON.stringify(newVal));
        } catch {
            // fallback
        }
    },
    { deep: true },
);

function getTotalSecondsForDate(dateStr: string): number {
    const dur = manualDurations.value[dateStr];

    if (!dur) {
        return Math.round(getTotalHoursForDate(dateStr) * 3600);
    }

    const h = Math.max(0, parseInt(String(dur.hours), 10) || 0);
    const m = Math.max(0, parseInt(String(dur.minutes), 10) || 0);
    const s = Math.max(0, parseInt(String(dur.seconds), 10) || 0);

    return h * 3600 + m * 60 + s;
}

function isDurationModified(dateStr: string): boolean {
    const dur = manualDurations.value[dateStr];

    if (!dur) {
        return false;
    }

    const currentSec = getTotalSecondsForDate(dateStr);
    const autoSec = Math.round(getTotalHoursForDate(dateStr) * 3600);

    return currentSec !== autoSec;
}

function resetDuration(dateStr: string): void {
    const calcHours = getTotalHoursForDate(dateStr);
    const totalSec = Math.round(calcHours * 3600);
    manualDurations.value[dateStr] = {
        hours: Math.floor(totalSec / 3600),
        minutes: Math.floor((totalSec % 3600) / 60),
        seconds: totalSec % 60,
    };
    // Sync revenue back to auto-calculated for this date based on the reset hours
    manualRevenue.value[dateStr] = getComputedPriceForDate(dateStr);
}

function getHourlyRateForDate(dateStr: string): number {
    const list = getBookingsForDate(dateStr).filter(
        (b) =>
            b.status === 'approved' ||
            b.status === 'confirmed' ||
            b.status === 'completed',
    );
    const autoHours = list.reduce(
        (sum, b) =>
            sum +
            (b.time_slots?.length || 0) *
                ((b.court?.slot_duration_minutes || 60) / 60),
        0,
    );
    const autoPrice = list.reduce(
        (sum, b) => sum + (parseFloat(b.total_price) || 0),
        0,
    );

    if (autoHours > 0 && autoPrice > 0) {
        return autoPrice / autoHours;
    }

    const pendingList = getBookingsForDate(dateStr).filter(
        (b) => b.status === 'pending',
    );
    const pendingHours = pendingList.reduce(
        (sum, b) =>
            sum +
            (b.time_slots?.length || 0) *
                ((b.court?.slot_duration_minutes || 60) / 60),
        0,
    );
    const pendingPrice = pendingList.reduce(
        (sum, b) => sum + (parseFloat(b.total_price) || 0),
        0,
    );

    if (pendingHours > 0 && pendingPrice > 0) {
        return pendingPrice / pendingHours;
    }

    if (court_id.value) {
        const court = props.courts.find(
            (c) => String(c.id) === String(court_id.value),
        );

        if (court && court.base_price) {
            const p = parseFloat(String(court.base_price));

            if (!isNaN(p) && p > 0) {
                return p;
            }
        }
    }

    const allConfirmed = props.tableBookings.filter((b) =>
        ['approved', 'confirmed', 'completed', 'pending'].includes(b.status),
    );
    const allHours = allConfirmed.reduce(
        (sum, b) =>
            sum +
            (b.time_slots?.length || 0) *
                ((b.court?.slot_duration_minutes || 60) / 60),
        0,
    );
    const allPrice = allConfirmed.reduce(
        (sum, b) => sum + (parseFloat(b.total_price) || 0),
        0,
    );

    if (allHours > 0 && allPrice > 0) {
        return allPrice / allHours;
    }

    if (props.courts && props.courts.length > 0) {
        for (const c of props.courts) {
            const p = parseFloat(String(c.base_price));

            if (!isNaN(p) && p > 0) {
                return p;
            }
        }
    }

    return 25.0;
}

function getComputedPriceForDate(dateStr: string): number {
    const rate = getHourlyRateForDate(dateStr);
    const hours = getTotalSecondsForDate(dateStr) / 3600;

    return Math.round(hours * rate * 100) / 100;
}

const REVENUE_STORAGE_KEY = 'admin_manual_revenue_booked';

function loadStoredRevenue(): Record<string, number | string> {
    try {
        const stored = localStorage.getItem(REVENUE_STORAGE_KEY);

        if (stored) {
            return JSON.parse(stored);
        }
    } catch {
        // fallback
    }

    return {};
}

const manualRevenue = ref<Record<string, number | string>>(loadStoredRevenue());

function getTotalPriceForDate(dateStr: string): number {
    const val = manualRevenue.value[dateStr];

    if (val !== undefined && val !== null && val !== '') {
        const num = parseFloat(String(val));

        if (!isNaN(num)) {
            return Math.max(0, num);
        }
    }

    return getComputedPriceForDate(dateStr);
}

function isRevenueModified(dateStr: string): boolean {
    if (
        manualRevenue.value[dateStr] === undefined ||
        manualRevenue.value[dateStr] === null ||
        manualRevenue.value[dateStr] === ''
    ) {
        return false;
    }

    const current = Number(manualRevenue.value[dateStr]);
    const computedPrice = getComputedPriceForDate(dateStr);

    return Math.abs(current - computedPrice) > 0.01;
}

function resetRevenue(dateStr: string): void {
    manualRevenue.value[dateStr] = getComputedPriceForDate(dateStr);
}

function onDurationChange(dateStr: string): void {
    // Whenever hours, minutes, or seconds are typed or changed,
    // recalculate the daily revenue based on the new total hours
    manualRevenue.value[dateStr] = getComputedPriceForDate(dateStr);
}

function getDurationSummary(dateStr: string): string {
    return formatDurationTotal(getTotalSecondsForDate(dateStr));
}

// When tableBookings or tableDates change, keep unmodified dates synchronized with live data
watch(
    () => [props.tableBookings, props.tableDates],
    () => {
        if (!props.tableDates) {
            return;
        }

        props.tableDates.forEach((d) => {
            const dateStr = d.dateStr;

            if (!isDurationModified(dateStr)) {
                const calcHours = getTotalHoursForDate(dateStr);
                const totalSec = Math.round(calcHours * 3600);
                manualDurations.value[dateStr] = {
                    hours: Math.floor(totalSec / 3600),
                    minutes: Math.floor((totalSec % 3600) / 60),
                    seconds: totalSec % 60,
                };
            }

            if (!isRevenueModified(dateStr)) {
                manualRevenue.value[dateStr] = getComputedPriceForDate(dateStr);
            }
        });
    },
    { deep: true, immediate: true },
);

watch(
    manualRevenue,
    (newVal) => {
        try {
            localStorage.setItem(REVENUE_STORAGE_KEY, JSON.stringify(newVal));
        } catch {
            // fallback
        }
    },
    { deep: true },
);

function formatDurationTotal(totalSeconds: number): string {
    const sec = Math.round(totalSeconds);

    if (sec <= 0) {
        return '0h';
    }

    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;

    const parts: string[] = [];

    if (h > 0) {
        parts.push(`${h}h`);
    }

    if (m > 0) {
        parts.push(`${m}m`);
    }

    if (s > 0) {
        parts.push(`${s}s`);
    }

    return parts.length > 0 ? parts.join(' ') : '0h';
}

function formatPrice(val: number): string {
    return (
        '₱' +
        val.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

function formatRawPrice(val: number): string {
    return val.toFixed(2);
}

const grandTotals = computed(() => {
    let confirmed = 0;
    let pending = 0;
    let rejected = 0;
    let revenue = 0;
    let totalSeconds = 0;
    props.tableDates.forEach((d) => {
        confirmed += getCountByStatus(d.dateStr, 'confirmed');
        pending += getCountByStatus(d.dateStr, 'pending');
        rejected += getCountByStatus(d.dateStr, 'rejected');
        revenue += getTotalPriceForDate(d.dateStr);
        totalSeconds += getTotalSecondsForDate(d.dateStr);
    });

    return {
        confirmed,
        pending,
        rejected,
        revenue,
        totalSeconds,
        hours: totalSeconds / 3600,
        total: confirmed + pending + rejected,
    };
});
</script>

<template>
    <div class="space-y-6">
        <!-- Controls & Filter Toolbar -->
        <div
            class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div
                class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
            >
                <!-- Date Navigation Bar -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="shiftDays(-7)"
                        class="rounded-xl border border-neutral-200 bg-neutral-50 p-2 text-neutral-600 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                        title="Previous 7 Days"
                    >
                        <ChevronLeft class="size-4" />
                    </button>

                    <div class="flex items-center gap-2">
                        <Calendar class="size-4 text-emerald-600" />
                        <input
                            type="date"
                            v-model="selectedDate"
                            @change="applyFilters"
                            class="rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-1.5 text-xs font-bold text-neutral-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        />
                    </div>

                    <button
                        type="button"
                        @click="shiftDays(7)"
                        class="rounded-xl border border-neutral-200 bg-neutral-50 p-2 text-neutral-600 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                        title="Next 7 Days"
                    >
                        <ChevronRight class="size-4" />
                    </button>

                    <button
                        type="button"
                        @click="
                            selectedDate = getLocalDateString();
                            applyFilters();
                        "
                        class="rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-xs font-bold text-neutral-700 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                    >
                        {{ isEveningOrNight ? 'Tonight' : 'Today' }}
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-if="showVenueFilter && venues?.length"
                        v-model="venue_id"
                        @change="applyFilters"
                        class="rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-1.5 text-xs font-semibold text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="">All Locations</option>
                        <option v-for="v in venues" :key="v.id" :value="v.id">
                            {{ v.name }}
                        </option>
                    </select>

                    <select
                        v-model="court_id"
                        @change="applyFilters"
                        class="rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-1.5 text-xs font-semibold text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="">All Courts</option>
                        <option v-for="c in courts" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>

                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-1.5 text-xs font-semibold text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        <option value="">All Statuses</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <button
                        v-if="court_id || status || venue_id"
                        @click="clearFilters"
                        class="rounded-xl bg-neutral-200 px-3 py-1.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-300 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Legend -->
            <div
                class="flex flex-wrap items-center gap-4 border-t border-neutral-100 pt-2 text-xs font-bold dark:border-neutral-800"
            >
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-emerald-500" />
                    <span class="text-neutral-600 dark:text-neutral-300"
                        >Available Slot</span
                    >
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-blue-500" />
                    <span class="text-neutral-600 dark:text-neutral-300"
                        >Approved / Confirmed</span
                    >
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-amber-500" />
                    <span class="text-neutral-600 dark:text-neutral-300"
                        >Pending</span
                    >
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-rose-500" />
                    <span class="text-neutral-600 dark:text-neutral-300"
                        >Rejected / Cancelled</span
                    >
                </div>
            </div>
        </div>

        <!-- Table View Grid Matrix -->
        <div
            class="overflow-x-auto rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <table class="w-full min-w-[900px] border-collapse text-left">
                <thead>
                    <tr
                        class="border-b border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/60"
                    >
                        <!-- First Column: Time Slots Header -->
                        <th
                            class="sticky left-0 z-20 min-w-[150px] border-r border-neutral-200 bg-neutral-100 px-4 py-3.5 text-sm font-black tracking-wider text-neutral-900 uppercase dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        >
                            <div class="flex items-center gap-2">
                                <Clock
                                    class="size-4.5 text-emerald-600 dark:text-emerald-400"
                                />
                                <span>Time Slot</span>
                            </div>
                        </th>

                        <!-- Following Columns: Dates -->
                        <th
                            v-for="d in tableDates"
                            :key="d.dateStr"
                            class="min-w-[145px] px-3 py-3 text-center transition-all"
                            :class="[
                                isCurrentDate(d)
                                    ? 'border-x border-emerald-500/20 bg-emerald-500/[0.04] dark:border-emerald-500/20 dark:bg-emerald-500/[0.06]'
                                    : 'border-r border-neutral-200 dark:border-neutral-800',
                            ]"
                        >
                            <div class="flex flex-col items-center">
                                <span
                                    v-if="isCurrentDate(d)"
                                    class="mb-1 inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold tracking-wider text-emerald-600 uppercase dark:text-emerald-400"
                                >
                                    <span
                                        class="size-2 rounded-full bg-emerald-500"
                                    />
                                    {{ getDateHeaderLabel(d) }}
                                </span>
                                <span
                                    v-else
                                    class="mb-1 text-xs font-extrabold tracking-wider text-neutral-400 uppercase dark:text-neutral-500"
                                >
                                    {{ getDateHeaderLabel(d) }}
                                </span>
                                <span
                                    class="my-0.5 text-base font-black text-neutral-900 dark:text-white"
                                >
                                    {{ d.monthName }} {{ d.dayNum }}
                                </span>
                                <span
                                    class="font-mono text-[11px] font-medium text-neutral-400 dark:text-neutral-500"
                                >
                                    {{ d.dateStr }}
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-neutral-100 text-xs dark:divide-neutral-800"
                >
                    <tr
                        v-for="slot in timeSlots"
                        :key="slot"
                        class="transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                    >
                        <!-- Sticky Time Slot Column -->
                        <td
                            class="sticky left-0 z-10 border-r border-neutral-200 bg-neutral-50 px-4 py-3.5 font-mono text-sm font-bold whitespace-nowrap text-neutral-900 dark:border-neutral-800 dark:bg-neutral-900 dark:text-white"
                        >
                            {{ formatSlotRange(slot, 60) }}
                        </td>

                        <!-- Cells for each date column -->
                        <td
                            v-for="d in tableDates"
                            :key="`${d.dateStr}-${slot}`"
                            class="min-h-16 p-1.5 align-top transition-colors"
                            :class="[
                                isCurrentDate(d)
                                    ? 'border-x border-emerald-500/15 bg-emerald-500/[0.025] dark:border-emerald-500/15 dark:bg-emerald-500/[0.04]'
                                    : 'border-r border-neutral-100 dark:border-neutral-800/60',
                            ]"
                        >
                            <div
                                class="flex h-full min-h-14 w-full flex-col gap-1"
                            >
                                <!-- Every booking on this hour, rejections included -->
                                <div
                                    v-for="b in bookingsForCell(
                                        d.dateStr,
                                        slot,
                                    )"
                                    :key="b.id"
                                    @click="emit('select-booking', b)"
                                    class="flex w-full cursor-pointer flex-col justify-between rounded-xl border p-2 shadow-xs transition-all duration-150"
                                    :class="[
                                        cellStatusClass(b.status),
                                        isReleased(b.status)
                                            ? 'opacity-60'
                                            : '',
                                    ]"
                                >
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <span
                                            class="truncate text-[11px] font-bold"
                                            :class="
                                                isReleased(b.status)
                                                    ? 'line-through'
                                                    : ''
                                            "
                                        >
                                            {{ b.name }}
                                        </span>
                                        <span
                                            class="rounded-sm bg-black/10 px-1 text-[9px] font-extrabold uppercase"
                                        >
                                            {{ b.status }}
                                        </span>
                                    </div>
                                    <div
                                        class="mt-1 flex items-center justify-between text-[10px] opacity-90"
                                    >
                                        <span class="truncate font-mono">{{
                                            b.court?.name || 'Court'
                                        }}</span>
                                        <span class="font-bold"
                                            >₱{{ b.total_price }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Still bookable when nothing here holds the hour and slot is not passed -->
                                <div
                                    v-if="
                                        cellIsOpen(d.dateStr, slot) &&
                                        !isSlotPassed(
                                            d.dateStr,
                                            slot,
                                            currentNow,
                                        )
                                    "
                                    @click="
                                        emit('create-booking', {
                                            date: d.dateStr,
                                            slot,
                                        })
                                    "
                                    class="group flex w-full cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-emerald-300/40 bg-emerald-50/20 transition-all duration-150 hover:border-emerald-500 hover:bg-emerald-50 dark:border-emerald-800/40 dark:bg-emerald-950/10 dark:hover:bg-emerald-950/30"
                                    :class="
                                        bookingsForCell(d.dateStr, slot).length
                                            ? 'py-1.5'
                                            : 'flex-1 p-2'
                                    "
                                >
                                    <span
                                        class="flex items-center gap-1 text-[10px] font-bold text-emerald-600 group-hover:hidden dark:text-emerald-400"
                                    >
                                        <span
                                            class="size-1.5 rounded-full bg-emerald-500"
                                        />
                                        Open
                                    </span>
                                    <span
                                        class="hidden items-center gap-1 text-[10px] font-bold text-emerald-700 group-hover:inline-flex dark:text-emerald-300"
                                    >
                                        <Plus class="size-3" /> Book
                                    </span>
                                </div>

                                <!-- Past or already completed time slot: not available for creating a new booking -->
                                <div
                                    v-else-if="
                                        cellIsOpen(d.dateStr, slot) &&
                                        isSlotPassed(
                                            d.dateStr,
                                            slot,
                                            currentNow,
                                        )
                                    "
                                    class="flex w-full cursor-not-allowed flex-col items-center justify-center rounded-xl border border-neutral-200/50 bg-neutral-100/30 opacity-50 select-none dark:border-neutral-800/50 dark:bg-neutral-900/30"
                                    :class="
                                        bookingsForCell(d.dateStr, slot).length
                                            ? 'py-1.5'
                                            : 'flex-1 p-2'
                                    "
                                    title="This time slot has already passed and cannot be booked."
                                >
                                    <span
                                        class="text-[9px] font-semibold text-neutral-400 dark:text-neutral-500"
                                    >
                                        Passed
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Booking Status Summary Section Below Booking Table -->
        <div class="space-y-4 pt-2">
            <!-- Section Header & Quick Metric Badges -->
            <div
                class="flex flex-col justify-between gap-3 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div>
                    <h3
                        class="flex items-center gap-2 text-sm font-extrabold tracking-wider text-neutral-900 uppercase dark:text-white"
                    >
                        <BarChart2 class="size-4 text-emerald-600" />
                        <span>Booking Status &amp; Daily Revenue Summary</span>
                    </h3>
                    <p class="mt-0.5 text-xs text-neutral-500">
                        Breakdown of booking totals, statuses, and daily total
                        revenue for each date in view.
                    </p>
                </div>

                <!-- Grand Total Metric Badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <span class="size-2 rounded-full bg-emerald-500" />
                        <span>Confirmed: {{ grandTotals.confirmed }}</span>
                    </div>
                    <div
                        class="flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        <span class="size-2 rounded-full bg-amber-500" />
                        <span>Pending: {{ grandTotals.pending }}</span>
                    </div>
                    <div
                        class="flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300"
                    >
                        <span class="size-2 rounded-full bg-rose-500" />
                        <span>Rejected: {{ grandTotals.rejected }}</span>
                    </div>
                    <div
                        class="flex items-center gap-1.5 rounded-xl border border-teal-300 bg-teal-100/70 px-3 py-1.5 text-xs font-extrabold text-teal-800 shadow-2xs dark:border-teal-800 dark:bg-teal-950/60 dark:text-teal-300"
                    >
                        <span
                            >Revenue:
                            {{ formatPrice(grandTotals.revenue) }}</span
                        >
                    </div>
                    <div
                        class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-3 py-1 text-[11px] font-bold text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"
                    >
                        <Clock class="size-3.5" />
                        <span
                            >Hours Booked:
                            {{
                                formatDurationTotal(grandTotals.totalSeconds)
                            }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Summary Table Matrix -->
            <div
                class="overflow-x-auto rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-neutral-200 bg-neutral-100/70 text-neutral-500 dark:border-neutral-800 dark:bg-neutral-800/80"
                        >
                            <th
                                class="sticky left-0 z-20 min-w-[150px] border-r border-neutral-200 bg-neutral-100 px-4 py-3.5 text-sm font-black tracking-wider text-neutral-900 uppercase dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            >
                                Summary Metric
                            </th>
                            <th
                                v-for="d in tableDates"
                                :key="d.dateStr"
                                class="min-w-[145px] px-3 py-2.5 text-center transition-colors"
                                :class="[
                                    isCurrentDate(d)
                                        ? 'border-x border-emerald-500/20 bg-emerald-500/[0.04] dark:border-emerald-500/20 dark:bg-emerald-500/[0.06]'
                                        : 'border-r border-neutral-200 dark:border-neutral-800',
                                ]"
                            >
                                <span
                                    class="block text-sm font-bold text-neutral-900 dark:text-white"
                                >
                                    {{ d.formatted }}
                                </span>
                                <span
                                    v-if="isCurrentDate(d)"
                                    class="mt-0.5 inline-flex items-center gap-1 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold tracking-wider text-emerald-600 uppercase dark:text-emerald-400"
                                >
                                    <span
                                        class="size-1 rounded-full bg-emerald-500"
                                    />
                                    {{ getDateHeaderLabel(d) }}
                                </span>
                                <span
                                    v-else
                                    class="text-[10px] font-semibold text-neutral-400"
                                >
                                    {{ getDateHeaderLabel(d) }}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-neutral-100 text-xs font-semibold dark:divide-neutral-800"
                    >
                        <!-- Confirmed Row -->
                        <tr
                            class="transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                        >
                            <td
                                class="sticky left-0 z-10 flex items-center gap-2 border-r border-neutral-200 bg-neutral-50 px-4 py-2.5 font-bold text-emerald-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-emerald-400"
                            >
                                <span
                                    class="size-2.5 rounded-full bg-emerald-500"
                                />
                                Confirmed
                            </td>
                            <td
                                v-for="d in tableDates"
                                :key="`conf-${d.dateStr}`"
                                class="px-3 py-2.5 text-center font-bold transition-colors"
                                :class="[
                                    isCurrentDate(d)
                                        ? 'border-x border-emerald-500/15 bg-emerald-500/[0.025] dark:border-emerald-500/15 dark:bg-emerald-500/[0.04]'
                                        : 'border-r border-neutral-100 dark:border-neutral-800/60',
                                ]"
                            >
                                <span
                                    :class="
                                        getCountByStatus(
                                            d.dateStr,
                                            'confirmed',
                                        ) > 0
                                            ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-black text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'
                                            : 'text-neutral-400'
                                    "
                                >
                                    {{
                                        getCountByStatus(d.dateStr, 'confirmed')
                                    }}
                                </span>
                            </td>
                        </tr>

                        <!-- Pending Row -->
                        <tr
                            class="transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                        >
                            <td
                                class="sticky left-0 z-10 flex items-center gap-2 border-r border-neutral-200 bg-neutral-50 px-4 py-2.5 font-bold text-amber-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-amber-400"
                            >
                                <span
                                    class="size-2.5 rounded-full bg-amber-500"
                                />
                                Pending
                            </td>
                            <td
                                v-for="d in tableDates"
                                :key="`pend-${d.dateStr}`"
                                class="px-3 py-2.5 text-center font-bold transition-colors"
                                :class="[
                                    isCurrentDate(d)
                                        ? 'border-x border-emerald-500/15 bg-emerald-500/[0.025] dark:border-emerald-500/15 dark:bg-emerald-500/[0.04]'
                                        : 'border-r border-neutral-100 dark:border-neutral-800/60',
                                ]"
                            >
                                <span
                                    :class="
                                        getCountByStatus(d.dateStr, 'pending') >
                                        0
                                            ? 'rounded-full bg-amber-100 px-2 py-0.5 text-xs font-black text-amber-600 dark:bg-amber-950/60 dark:text-amber-400'
                                            : 'text-neutral-400'
                                    "
                                >
                                    {{ getCountByStatus(d.dateStr, 'pending') }}
                                </span>
                            </td>
                        </tr>

                        <!-- Rejected Row -->
                        <tr
                            class="transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                        >
                            <td
                                class="sticky left-0 z-10 flex items-center gap-2 border-r border-neutral-200 bg-neutral-50 px-4 py-2.5 font-bold text-rose-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-rose-400"
                            >
                                <span
                                    class="size-2.5 rounded-full bg-rose-500"
                                />
                                Rejected
                            </td>
                            <td
                                v-for="d in tableDates"
                                :key="`rej-${d.dateStr}`"
                                class="px-3 py-2.5 text-center font-bold transition-colors"
                                :class="[
                                    isCurrentDate(d)
                                        ? 'border-x border-emerald-500/15 bg-emerald-500/[0.025] dark:border-emerald-500/15 dark:bg-emerald-500/[0.04]'
                                        : 'border-r border-neutral-100 dark:border-neutral-800/60',
                                ]"
                            >
                                <span
                                    :class="
                                        getCountByStatus(
                                            d.dateStr,
                                            'rejected',
                                        ) > 0
                                            ? 'rounded-full bg-rose-100 px-2 py-0.5 text-xs font-black text-rose-600 dark:bg-rose-950/60 dark:text-rose-400'
                                            : 'text-neutral-400'
                                    "
                                >
                                    {{
                                        getCountByStatus(d.dateStr, 'rejected')
                                    }}
                                </span>
                            </td>
                        </tr>

                        <!-- Total Bookings Row -->
                        <tr
                            class="bg-neutral-100/60 font-black dark:bg-neutral-800/60"
                        >
                            <td
                                class="sticky left-0 z-10 border-r border-neutral-200 bg-neutral-100 px-4 py-2.5 text-[11px] tracking-wider text-neutral-900 uppercase dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            >
                                Total Bookings
                            </td>
                            <td
                                v-for="d in tableDates"
                                :key="`tot-${d.dateStr}`"
                                class="px-3 py-2.5 text-center text-xs font-extrabold text-neutral-900 transition-colors dark:text-white"
                                :class="[
                                    isCurrentDate(d)
                                        ? 'border-x border-emerald-500/15 bg-emerald-500/[0.04] dark:border-emerald-500/15 dark:bg-emerald-500/[0.06]'
                                        : 'border-r border-neutral-200 dark:border-neutral-700',
                                ]"
                            >
                                {{ getTotalCountForDate(d.dateStr) }}
                            </td>
                        </tr>

                        <!-- Hours Booked Per Day Row (Editable hr, min, sec) -->
                        <tr class="bg-sky-50/70 font-black dark:bg-sky-950/30">
                            <td
                                class="sticky left-0 z-10 flex items-center gap-1.5 border-r border-sky-200 bg-sky-100/80 px-4 py-2.5 text-[11px] tracking-wider text-sky-900 uppercase dark:border-sky-800 dark:bg-sky-950/80 dark:text-sky-300"
                            >
                                <Clock class="size-3.5 text-sky-600" />
                                Hours Booked
                            </td>
                            <td
                                v-for="d in tableDates"
                                :key="`hours-${d.dateStr}`"
                                class="px-2 py-2 text-center transition-colors"
                                :class="[
                                    isCurrentDate(d)
                                        ? 'border-x border-sky-500/20 bg-sky-500/[0.04] dark:bg-sky-500/[0.06]'
                                        : 'border-r border-sky-200/60 dark:border-sky-900/40',
                                ]"
                            >
                                <div
                                    class="inline-flex items-center justify-center gap-1 text-[11px]"
                                >
                                    <!-- Hours -->
                                    <div class="relative flex items-center">
                                        <input
                                            type="number"
                                            min="0"
                                            v-model.number="
                                                getDuration(d.dateStr).hours
                                            "
                                            @input="onDurationChange(d.dateStr)"
                                            placeholder="0"
                                            title="Hours"
                                            class="w-10 [appearance:textfield] rounded-md border border-sky-300/80 bg-white py-1 pr-3.5 pl-1 text-center text-xs font-black text-sky-900 shadow-2xs transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none dark:border-sky-700/80 dark:bg-neutral-900 dark:text-sky-200 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                        />
                                        <span
                                            class="pointer-events-none absolute right-1 text-[10px] font-extrabold text-sky-600/70 dark:text-sky-400/70"
                                            >h</span
                                        >
                                    </div>

                                    <!-- Minutes -->
                                    <div class="relative flex items-center">
                                        <input
                                            type="number"
                                            min="0"
                                            max="59"
                                            v-model.number="
                                                getDuration(d.dateStr).minutes
                                            "
                                            @input="onDurationChange(d.dateStr)"
                                            placeholder="0"
                                            title="Minutes"
                                            class="w-10 [appearance:textfield] rounded-md border border-sky-300/80 bg-white py-1 pr-4 pl-1 text-center text-xs font-black text-sky-900 shadow-2xs transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none dark:border-sky-700/80 dark:bg-neutral-900 dark:text-sky-200 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                        />
                                        <span
                                            class="pointer-events-none absolute right-1 text-[10px] font-extrabold text-sky-600/70 dark:text-sky-400/70"
                                            >m</span
                                        >
                                    </div>

                                    <!-- Seconds -->
                                    <div class="relative flex items-center">
                                        <input
                                            type="number"
                                            min="0"
                                            max="59"
                                            v-model.number="
                                                getDuration(d.dateStr).seconds
                                            "
                                            @input="onDurationChange(d.dateStr)"
                                            placeholder="0"
                                            title="Seconds"
                                            class="w-10 [appearance:textfield] rounded-md border border-sky-300/80 bg-white py-1 pr-3.5 pl-1 text-center text-xs font-black text-sky-900 shadow-2xs transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none dark:border-sky-700/80 dark:bg-neutral-900 dark:text-sky-200 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                        />
                                        <span
                                            class="pointer-events-none absolute right-1 text-[10px] font-extrabold text-sky-600/70 dark:text-sky-400/70"
                                            >s</span
                                        >
                                    </div>

                                    <!-- Reset to calculated if modified -->
                                    <button
                                        v-if="isDurationModified(d.dateStr)"
                                        type="button"
                                        @click="resetDuration(d.dateStr)"
                                        class="cursor-pointer rounded p-0.5 text-sky-500 transition-colors hover:bg-sky-100 hover:text-sky-700 dark:hover:bg-sky-900/40"
                                        :title="`Reset to calculated (${formatDurationTotal(Math.round(getTotalHoursForDate(d.dateStr) * 3600))})`"
                                    >
                                        <RotateCcw class="size-2.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Total Daily Price / Revenue Row (Based on total hours, editable) -->
                        <tr
                            class="border-t-2 border-emerald-500/20 bg-emerald-50/70 font-black dark:bg-emerald-950/30"
                        >
                            <td
                                class="sticky left-0 z-10 flex items-center gap-1.5 border-r border-emerald-200 bg-emerald-100/80 px-4 py-3 text-[11px] tracking-wider text-emerald-900 uppercase dark:border-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300"
                            >
                                <DollarSign class="size-3.5 text-emerald-600" />
                                Total Revenue (₱)
                            </td>
                            <td
                                v-for="d in tableDates"
                                :key="`price-${d.dateStr}`"
                                class="px-2 py-2.5 text-center transition-colors"
                                :class="[
                                    isCurrentDate(d)
                                        ? 'border-x border-emerald-500/20 bg-emerald-500/[0.05] dark:bg-emerald-500/[0.07]'
                                        : 'border-r border-emerald-200/60 dark:border-emerald-900/40',
                                ]"
                            >
                                <div
                                    class="inline-flex items-center justify-center gap-1"
                                >
                                    <div class="relative flex items-center">
                                        <span
                                            class="pointer-events-none absolute left-2 text-xs font-black text-emerald-700/80 dark:text-emerald-400/80"
                                            >₱</span
                                        >
                                        <input
                                            type="number"
                                            min="0"
                                            step="any"
                                            v-model.number="
                                                manualRevenue[d.dateStr]
                                            "
                                            :placeholder="
                                                formatRawPrice(
                                                    getComputedPriceForDate(
                                                        d.dateStr,
                                                    ),
                                                )
                                            "
                                            :title="`Daily Revenue for ${d.formatted} (Based on ${getDurationSummary(d.dateStr)} @ ₱${getHourlyRateForDate(d.dateStr).toFixed(2)}/hr)`"
                                            class="w-24 [appearance:textfield] rounded-lg border border-emerald-300/80 bg-white py-1 pr-2 pl-6 text-center text-xs font-black text-emerald-700 shadow-2xs transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none dark:border-emerald-700/80 dark:bg-neutral-900 dark:text-emerald-300 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                        />
                                    </div>
                                    <button
                                        v-if="isRevenueModified(d.dateStr)"
                                        type="button"
                                        @click="resetRevenue(d.dateStr)"
                                        class="cursor-pointer rounded p-0.5 text-emerald-500 transition-colors hover:bg-emerald-100 hover:text-emerald-700 dark:hover:bg-emerald-900/40"
                                        :title="`Reset to calculated revenue based on total hours (₱${formatRawPrice(getComputedPriceForDate(d.dateStr))})`"
                                    >
                                        <RotateCcw class="size-2.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
