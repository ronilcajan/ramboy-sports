<script setup lang="ts">
import {
    Calendar,
    Clock,
    CheckCircle,
    Dumbbell,
    TrendingUp,
    Zap,
} from '@lucide/vue';
import { ref, computed, watch, onMounted } from 'vue';
import { useCourtAvailability } from '@/composables/useCourtAvailability';
import { formatSlotRange, getMergedTimeSlots } from '@/utils/timeSlots';

interface CourtItem {
    id: number;
    name: string;
    slug: string;
    sport_type: string;
    base_price: string;
    slot_duration_minutes: number;
    is_active: boolean;
    slot_prices?: Record<string, string | number> | null;
}

const props = defineProps<{
    courts: CourtItem[];
    venueName: string;
}>();

const emit = defineEmits<{
    (
        e: 'book-slot',
        payload: { court: CourtItem; date: string; slot?: string },
    ): void;
}>();

// ── Date helpers ─────────────────────────────────────────────────
function toDateKey(d: Date): string {
    return [
        d.getFullYear(),
        String(d.getMonth() + 1).padStart(2, '0'),
        String(d.getDate()).padStart(2, '0'),
    ].join('-');
}

function parseLocalDate(s: string): Date {
    const p = s.split('-').map(Number);

    return p.length === 3 ? new Date(p[0], p[1] - 1, p[2]) : new Date(s);
}

const todayKey = computed(() => toDateKey(new Date()));
const selectedDate = ref(todayKey.value);
const selectedCourtId = ref<number | null>(null);

const formattedDate = computed(() => {
    const d = parseLocalDate(selectedDate.value);

    return d.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
});

// ── Upcoming 14-day slider ───────────────────────────────────────
const upcomingDays = computed(() => {
    const base = new Date();
    base.setHours(0, 0, 0, 0);

    return Array.from({ length: 14 }, (_, i) => {
        const d = new Date(base);
        d.setDate(base.getDate() + i);
        const key = toDateKey(d);

        return {
            key,
            dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
            dayNum: d.getDate(),
            month: d.toLocaleDateString('en-US', { month: 'short' }),
            isToday: key === todayKey.value,
            isWeekend: d.getDay() === 0 || d.getDay() === 6,
        };
    });
});

// ── Dynamic Time slots ───────────────────────────────────────────
const activeTimeSlots = computed(() => {
    const customPricesList: Record<string, any>[] = [];

    if (props.courts) {
        props.courts.forEach((c) => {
            if (c.slot_prices) {
                customPricesList.push(c.slot_prices);
            }
        });
    }

    const combined = customPricesList.reduce(
        (acc, prices) => ({ ...acc, ...prices }),
        {},
    );

    return getMergedTimeSlots(combined);
});

function slotHour(slot: string): number {
    const [time, period] = slot.split(' ');
    let h = parseInt(time.split(':')[0], 10);

    if (period === 'PM' && h !== 12) {
        h += 12;
    }

    if (period === 'AM' && h === 12) {
        h = 0;
    }

    return h;
}

function slotPeriod(slot: string) {
    const h = slotHour(slot);

    if (h >= 5 && h < 12) {
        return { label: 'Morning', icon: '🌅', color: 'amber' };
    }

    if (h >= 12 && h < 17) {
        return { label: 'Afternoon', icon: '☀️', color: 'orange' };
    }

    if (h >= 17 && h < 21) {
        return { label: 'Evening', icon: '🌆', color: 'violet' };
    }

    return { label: 'Night', icon: '🌙', color: 'slate' };
}

const groupedSlots = computed(() => {
    const order = ['Morning', 'Afternoon', 'Evening', 'Night'];
    const map: Record<
        string,
        { slot: string; meta: ReturnType<typeof slotPeriod> }[]
    > = {};

    for (const slot of activeTimeSlots.value) {
        const meta = slotPeriod(slot);
        (map[meta.label] ??= []).push({ slot, meta });
    }

    return order
        .filter((k) => map[k]?.length)
        .map((k) => ({
            period: k,
            meta: map[k][0].meta,
            slots: map[k].map((x) => x.slot),
        }));
});

// ── Pricing ──────────────────────────────────────────────────────
function slotPrice(court: CourtItem, slot: string): number {
    if (
        court.slot_prices?.[slot] !== undefined &&
        court.slot_prices[slot] !== null
    ) {
        const v = parseFloat(String(court.slot_prices[slot]));

        if (!isNaN(v) && v > 0) {
            return v;
        }
    }

    const b = parseFloat(court.base_price || '0');

    return isNaN(b) ? 0 : b;
}

// ── Availability fetch ───────────────────────────────────────────
const {
    isLoading,
    fetchAvailability: loadAvailability,
    slotsForCourt,
    customerForSlot,
} = useCourtAvailability();

async function fetchAvailability() {
    if (!selectedDate.value) {
        return;
    }

    await loadAvailability({ date: selectedDate.value });
}

function bookedSlots(courtId: number): string[] {
    return slotsForCourt(courtId);
}

function isBooked(courtId: number, slot: string): boolean {
    return bookedSlots(courtId).includes(slot);
}

// ── Derived ──────────────────────────────────────────────────────
const activeCourts = computed(() => props.courts.filter((c) => c.is_active));

const courtsToShow = computed(() =>
    selectedCourtId.value !== null
        ? activeCourts.value.filter((c) => c.id === selectedCourtId.value)
        : activeCourts.value,
);

// ── Summary stats ────────────────────────────────────────────────
const totalAvailable = computed(() =>
    activeCourts.value.reduce(
        (sum, c) =>
            sum + (activeTimeSlots.value.length - bookedSlots(c.id).length),
        0,
    ),
);

const totalBooked = computed(() =>
    activeCourts.value.reduce((sum, c) => sum + bookedSlots(c.id).length, 0),
);

const occupancyPct = computed(() => {
    const total = activeCourts.value.length * activeTimeSlots.value.length;

    return total > 0 ? Math.round((totalBooked.value / total) * 100) : 0;
});

watch(selectedDate, fetchAvailability);
onMounted(fetchAvailability);
</script>

<template>
    <div class="space-y-6">
        <!-- ── Header + stat strip ──────────────────────────── -->
        <div
            class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-700 p-6 text-white shadow-lg dark:border-neutral-800"
        >
            <!-- Decorative blobs -->
            <div
                class="pointer-events-none absolute -top-10 -right-10 size-48 rounded-full bg-white/5 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-8 -left-8 size-36 rounded-full bg-black/10 blur-2xl"
            />

            <div
                class="relative flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between"
            >
                <!-- Title block -->
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold tracking-widest uppercase backdrop-blur-sm"
                        >
                            <Zap class="size-3" />
                            Live Schedule
                        </span>
                    </div>
                    <h3 class="mt-3 text-2xl font-black tracking-tight">
                        Availability Schedule
                    </h3>
                    <p class="mt-1 text-sm text-emerald-100/80">
                        Real-time court slots for
                        <strong class="text-white">{{ venueName }}</strong>
                    </p>
                </div>

                <!-- Stat cards -->
                <div class="flex shrink-0 gap-3">
                    <div
                        class="min-w-[72px] rounded-xl bg-white/15 px-4 py-3 text-center backdrop-blur-sm"
                    >
                        <p class="text-2xl font-black tabular-nums">
                            {{ totalAvailable }}
                        </p>
                        <p
                            class="mt-0.5 text-[10px] font-bold tracking-wider text-emerald-100/80 uppercase"
                        >
                            Open
                        </p>
                    </div>
                    <div
                        class="min-w-[72px] rounded-xl bg-white/15 px-4 py-3 text-center backdrop-blur-sm"
                    >
                        <p class="text-2xl font-black tabular-nums">
                            {{ totalBooked }}
                        </p>
                        <p
                            class="mt-0.5 text-[10px] font-bold tracking-wider text-emerald-100/80 uppercase"
                        >
                            Booked
                        </p>
                    </div>
                    <div
                        class="min-w-[72px] rounded-xl bg-white/15 px-4 py-3 text-center backdrop-blur-sm"
                    >
                        <p class="text-2xl font-black tabular-nums">
                            {{ occupancyPct }}%
                        </p>
                        <p
                            class="mt-0.5 text-[10px] font-bold tracking-wider text-emerald-100/80 uppercase"
                        >
                            Full
                        </p>
                    </div>
                </div>
            </div>

            <!-- Occupancy bar -->
            <div class="relative mt-5">
                <div
                    class="mb-1 flex items-center justify-between text-[11px] font-semibold text-emerald-100/70"
                >
                    <span class="flex items-center gap-1"
                        ><TrendingUp class="size-3" /> Occupancy rate</span
                    >
                    <span>{{ occupancyPct }}%</span>
                </div>
                <div
                    class="h-1.5 w-full overflow-hidden rounded-full bg-white/20"
                >
                    <div
                        class="h-full rounded-full bg-white transition-all duration-700 ease-out"
                        :style="{ width: `${occupancyPct}%` }"
                    />
                </div>
            </div>
        </div>

        <!-- ── Date selector card ────────────────────────────── -->
        <div
            class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <!-- Card header -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4 dark:border-neutral-800"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                    >
                        <Calendar class="size-4" />
                    </div>
                    <div>
                        <p
                            class="text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                        >
                            Viewing
                        </p>
                        <p
                            class="text-sm font-black text-neutral-900 dark:text-white"
                        >
                            {{ formattedDate }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <label
                        for="admin-date-pick"
                        class="text-xs font-semibold text-neutral-500"
                        >Jump to:</label
                    >
                    <input
                        id="admin-date-pick"
                        type="date"
                        v-model="selectedDate"
                        :min="todayKey"
                        class="rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-xs font-bold text-neutral-900 transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                </div>
            </div>

            <!-- 14-day strip -->
            <div
                class="flex scrollbar-thin scrollbar-thumb-neutral-200 gap-2 overflow-x-auto px-5 py-4 dark:scrollbar-thumb-neutral-700"
            >
                <button
                    v-for="d in upcomingDays"
                    :key="d.key"
                    type="button"
                    @click="selectedDate = d.key"
                    class="group flex min-w-[64px] shrink-0 flex-col items-center justify-center rounded-xl border px-3.5 py-2.5 text-center transition-all duration-200 focus:ring-2 focus:ring-emerald-500/40 focus:outline-none"
                    :class="[
                        selectedDate === d.key
                            ? 'scale-105 border-emerald-500 bg-emerald-600 text-white shadow-lg shadow-emerald-600/25'
                            : d.isWeekend
                              ? 'border-violet-100 bg-violet-50 text-neutral-700 hover:border-violet-300 hover:bg-violet-100 dark:border-violet-900/40 dark:bg-violet-950/20 dark:text-neutral-300 dark:hover:bg-violet-950/40'
                              : 'border-neutral-200 bg-neutral-50 text-neutral-700 hover:border-emerald-300 hover:bg-emerald-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-emerald-950/20',
                    ]"
                >
                    <span
                        class="text-[9px] font-extrabold tracking-wider uppercase"
                        :class="
                            selectedDate === d.key
                                ? 'text-emerald-100'
                                : 'text-neutral-400'
                        "
                    >
                        {{ d.isToday ? 'Today' : d.dayName }}
                    </span>
                    <span
                        class="my-1 text-xl leading-none font-black tabular-nums"
                        >{{ d.dayNum }}</span
                    >
                    <span
                        class="text-[9px] font-semibold"
                        :class="
                            selectedDate === d.key
                                ? 'text-emerald-100'
                                : 'text-neutral-400'
                        "
                    >
                        {{ d.month }}
                    </span>
                    <!-- Today dot -->
                    <span
                        v-if="d.isToday && selectedDate !== d.key"
                        class="mt-1 size-1 rounded-full bg-emerald-500"
                    />
                </button>
            </div>
        </div>

        <!-- ── Court filter tabs ─────────────────────────────── -->
        <div
            v-if="activeCourts.length > 1"
            class="flex flex-wrap items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <span
                class="text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                >Filter court:</span
            >
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="selectedCourtId = null"
                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-150"
                    :class="[
                        selectedCourtId === null
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25'
                            : 'border border-neutral-200 bg-neutral-100 text-neutral-600 hover:border-emerald-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400',
                    ]"
                >
                    <Dumbbell class="size-3" />
                    All Courts
                    <span
                        class="rounded-full px-1.5"
                        :class="
                            selectedCourtId === null
                                ? 'bg-white/20 text-white'
                                : 'bg-neutral-200 text-neutral-500 dark:bg-neutral-700'
                        "
                    >
                        {{ activeCourts.length }}
                    </span>
                </button>
                <button
                    v-for="c in activeCourts"
                    :key="c.id"
                    type="button"
                    @click="selectedCourtId = c.id"
                    class="rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-150"
                    :class="[
                        selectedCourtId === c.id
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25'
                            : 'border border-neutral-200 bg-neutral-100 text-neutral-600 hover:border-emerald-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400',
                    ]"
                >
                    {{ c.name }}
                </button>
            </div>
        </div>

        <!-- ── Loading skeleton ──────────────────────────────── -->
        <div v-if="isLoading" class="space-y-4">
            <div
                v-for="i in Math.max(1, courtsToShow.length || 1)"
                :key="i"
                class="animate-pulse rounded-2xl border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center gap-3 border-b border-neutral-100 pb-4 dark:border-neutral-800"
                >
                    <div
                        class="size-10 rounded-xl bg-neutral-200 dark:bg-neutral-700"
                    />
                    <div class="flex-1 space-y-2">
                        <div
                            class="h-4 w-32 rounded-lg bg-neutral-200 dark:bg-neutral-700"
                        />
                        <div
                            class="h-3 w-20 rounded-lg bg-neutral-100 dark:bg-neutral-800"
                        />
                    </div>
                </div>
                <div
                    class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8"
                >
                    <div
                        v-for="j in 16"
                        :key="j"
                        class="h-16 rounded-xl bg-neutral-100 dark:bg-neutral-800"
                    />
                </div>
            </div>
        </div>

        <!-- ── Courts schedule ───────────────────────────────── -->
        <transition-group
            v-else-if="courtsToShow.length > 0"
            name="court-fade"
            tag="div"
            class="space-y-5"
        >
            <div
                v-for="court in courtsToShow"
                :key="court.id"
                class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <!-- Court card header -->
                <div
                    class="relative flex flex-col gap-4 border-b border-neutral-100 bg-gradient-to-r from-neutral-50 to-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800 dark:from-neutral-800/60 dark:to-neutral-900"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 shadow-sm dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <Dumbbell class="size-5" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4
                                    class="font-black text-neutral-900 dark:text-white"
                                >
                                    {{ court.name }}
                                </h4>
                                <span
                                    class="rounded-full border border-neutral-200 bg-neutral-100 px-2 py-0.5 text-[10px] font-bold tracking-wider text-neutral-500 uppercase dark:border-neutral-700 dark:bg-neutral-800"
                                >
                                    {{ court.sport_type }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-neutral-500">
                                From
                                <strong
                                    class="font-bold text-emerald-600 dark:text-emerald-400"
                                    >₱{{ court.base_price }}</strong
                                >
                                / {{ court.slot_duration_minutes }} min
                            </p>
                        </div>
                    </div>

                    <!-- Availability mini-chart -->
                    <div class="flex shrink-0 items-center gap-4">
                        <div class="text-center">
                            <p
                                class="text-xl font-black text-emerald-500 tabular-nums"
                            >
                                {{
                                    activeTimeSlots.length -
                                    bookedSlots(court.id).length
                                }}
                            </p>
                            <p
                                class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                            >
                                Open
                            </p>
                        </div>
                        <div
                            class="h-10 w-px bg-neutral-200 dark:bg-neutral-700"
                        />
                        <div class="text-center">
                            <p
                                class="text-xl font-black text-neutral-400 tabular-nums"
                            >
                                {{ bookedSlots(court.id).length }}
                            </p>
                            <p
                                class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                            >
                                Booked
                            </p>
                        </div>
                        <div
                            class="h-10 w-px bg-neutral-200 dark:bg-neutral-700"
                        />
                        <!-- Mini occupancy bar pill -->
                        <div class="flex flex-col items-center gap-1">
                            <p
                                class="text-xs font-black text-neutral-700 tabular-nums dark:text-neutral-200"
                            >
                                {{
                                    Math.round(
                                        (bookedSlots(court.id).length /
                                            activeTimeSlots.length) *
                                            100,
                                    )
                                }}%
                            </p>
                            <div
                                class="h-1.5 w-16 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700"
                            >
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                    :style="{
                                        width: `${Math.round((bookedSlots(court.id).length / activeTimeSlots.length) * 100)}%`,
                                    }"
                                />
                            </div>
                            <p
                                class="text-[9px] font-semibold tracking-wider text-neutral-400 uppercase"
                            >
                                Full
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Slot groups -->
                <div class="space-y-5 p-5">
                    <div v-for="group in groupedSlots" :key="group.period">
                        <!-- Period label -->
                        <div class="mb-3 flex items-center gap-2">
                            <span class="text-base leading-none">{{
                                group.meta.icon
                            }}</span>
                            <h5
                                class="text-[11px] font-extrabold tracking-widest text-neutral-400 uppercase"
                            >
                                {{ group.period }}
                            </h5>
                            <div
                                class="h-px flex-1 bg-neutral-100 dark:bg-neutral-800"
                            />
                            <!-- Quick count for this period -->
                            <span
                                class="text-[10px] font-bold text-neutral-400"
                            >
                                {{
                                    group.slots.filter(
                                        (s) => !isBooked(court.id, s),
                                    ).length
                                }}
                                / {{ group.slots.length }} open
                            </span>
                        </div>

                        <!-- Slot grid -->
                        <div
                            class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                        >
                            <div
                                v-for="slot in group.slots"
                                :key="slot"
                                class="group/slot relative flex flex-col items-center justify-center rounded-xl border p-3 text-center transition-all duration-150 select-none"
                                :class="[
                                    isBooked(court.id, slot)
                                        ? 'cursor-not-allowed border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-800/40'
                                        : 'cursor-pointer border-emerald-100 bg-emerald-50/60 hover:-translate-y-0.5 hover:border-emerald-400 hover:bg-emerald-50 hover:shadow-md hover:shadow-emerald-500/10 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40',
                                ]"
                                @click="
                                    !isBooked(court.id, slot) &&
                                    emit('book-slot', {
                                        court,
                                        date: selectedDate,
                                        slot,
                                    })
                                "
                            >
                                <!-- Available glow dot -->
                                <span
                                    v-if="!isBooked(court.id, slot)"
                                    class="absolute top-2 right-2 size-1.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/60"
                                />

                                <span
                                    class="text-xs leading-tight font-black"
                                    :class="
                                        isBooked(court.id, slot)
                                            ? 'text-neutral-300 line-through dark:text-neutral-600'
                                            : 'text-neutral-900 dark:text-white'
                                    "
                                    >{{
                                        formatSlotRange(
                                            slot,
                                            court.slot_duration_minutes || 60,
                                        )
                                    }}</span
                                >

                                <span
                                    class="mt-1 text-[10px] font-extrabold"
                                    :class="
                                        isBooked(court.id, slot)
                                            ? 'text-neutral-300 dark:text-neutral-700'
                                            : 'text-emerald-600 dark:text-emerald-400'
                                    "
                                    >₱{{ slotPrice(court, slot) }}</span
                                >

                                <span
                                    v-if="isBooked(court.id, slot)"
                                    class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-rose-100 px-2 py-0.5 text-[9px] font-bold text-rose-400 dark:bg-rose-950/60 dark:text-rose-500"
                                >
                                    <span
                                        class="size-1.5 rounded-full bg-rose-500"
                                    />
                                    Booked
                                </span>
                                <span
                                    v-else
                                    class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-600 transition-colors group-hover/slot:bg-emerald-500 group-hover/slot:text-white dark:bg-emerald-950/60 dark:text-emerald-400"
                                >
                                    <CheckCircle class="size-2.5" /> Open
                                </span>

                                <!-- Customer Name for Booked Slots -->
                                <div
                                    v-if="
                                        isBooked(court.id, slot) &&
                                        customerForSlot(court.id, slot)
                                    "
                                    class="mt-1.5 w-full max-w-full truncate border-t border-neutral-200 pt-1 text-center text-[10px] dark:border-neutral-700/60"
                                    :title="`Customer: ${customerForSlot(court.id, slot)}`"
                                >
                                    <span
                                        class="text-neutral-500 dark:text-neutral-400"
                                        >Customer:
                                    </span>
                                    <strong
                                        class="font-bold text-neutral-800 dark:text-neutral-200"
                                        >{{
                                            customerForSlot(court.id, slot)
                                        }}</strong
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition-group>

        <!-- ── Empty state ───────────────────────────────────── -->
        <div
            v-else
            class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 py-16 text-center dark:border-neutral-700 dark:bg-neutral-900"
        >
            <div
                class="flex size-14 items-center justify-center rounded-2xl bg-neutral-100 dark:bg-neutral-800"
            >
                <Clock class="size-7 text-neutral-400" />
            </div>
            <div>
                <p class="font-bold text-neutral-600 dark:text-neutral-300">
                    No active courts
                </p>
                <p class="mt-1 text-xs text-neutral-400">
                    No active courts have been added to this location yet.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.court-fade-enter-active,
.court-fade-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.court-fade-enter-from,
.court-fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
