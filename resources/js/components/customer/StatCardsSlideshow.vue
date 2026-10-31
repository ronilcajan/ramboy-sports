<script setup lang="ts">
import {
    CalendarRange,
    Clock,
    CheckCircle,
    DollarSign,
    TrendingUp,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted } from 'vue';

export interface DashboardStats {
    total: number;
    pending: number;
    confirmed: number;
    totalSpent: string;
}

defineProps<{
    stats: DashboardStats;
}>();

const currentSlide = ref(0);
const touchStartX = ref(0);
const touchEndX = ref(0);
const isPaused = ref(false);
let autoSlideInterval: ReturnType<typeof setInterval> | null = null;

const slidesCount = 4;

function nextSlide() {
    currentSlide.value = (currentSlide.value + 1) % slidesCount;
}

function prevSlide() {
    currentSlide.value = (currentSlide.value - 1 + slidesCount) % slidesCount;
}

function goToSlide(index: number) {
    currentSlide.value = index;
}

function handleTouchStart(e: TouchEvent) {
    touchStartX.value = e.touches[0].clientX;
    isPaused.value = true;
}

function handleTouchEnd(e: TouchEvent) {
    touchEndX.value = e.changedTouches[0].clientX;
    const diff = touchStartX.value - touchEndX.value;

    if (Math.abs(diff) > 40) {
        if (diff > 0) {
            nextSlide();
        } else {
            prevSlide();
        }
    }

    setTimeout(() => {
        isPaused.value = false;
    }, 2000);
}

function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        if (!isPaused.value) {
            nextSlide();
        }
    }, 4500);
}

onMounted(() => {
    startAutoSlide();
});

onUnmounted(() => {
    if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
    }
});
</script>

<template>
    <div class="relative w-full">
        <!-- Desktop Grid View (md:grid) -->
        <div class="hidden gap-4 md:grid md:grid-cols-2 lg:grid-cols-4">
            <!-- Stat card: Total bookings -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="absolute top-3 right-3 opacity-10 transition-transform group-hover:scale-110"
                >
                    <CalendarRange
                        class="h-12 w-12 text-neutral-600 dark:text-neutral-400"
                    />
                </div>
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Total Bookings</span
                >
                <span
                    class="mt-1 block text-2xl font-black text-neutral-900 dark:text-white"
                    >{{ stats.total }}</span
                >
                <div
                    class="mt-2 flex items-center gap-1 text-[10px] font-medium text-neutral-500"
                >
                    <span>Active and completed reservations</span>
                </div>
            </div>

            <!-- Stat card: Pending approval -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="absolute top-3 right-3 opacity-10 transition-transform group-hover:scale-110"
                >
                    <Clock class="h-12 w-12 text-amber-600" />
                </div>
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Pending Approval</span
                >
                <span class="mt-1 block text-2xl font-black text-amber-600">{{
                    stats.pending
                }}</span>
                <div
                    class="mt-2 flex items-center gap-1 text-[10px] font-medium text-amber-600"
                >
                    <span
                        v-if="stats.pending > 0"
                        class="flex items-center gap-1"
                    >
                        <span
                            class="h-1.5 w-1.5 animate-ping rounded-full bg-amber-500"
                        ></span>
                        Awaiting staff confirmation
                    </span>
                    <span v-else>All bookings processed</span>
                </div>
            </div>

            <!-- Stat card: Confirmed -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="absolute top-3 right-3 opacity-10 transition-transform group-hover:scale-110"
                >
                    <CheckCircle class="h-12 w-12 text-emerald-600" />
                </div>
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Confirmed Bookings</span
                >
                <span class="mt-1 block text-2xl font-black text-emerald-600">{{
                    stats.confirmed
                }}</span>
                <div
                    class="mt-2 flex items-center gap-1 text-[10px] font-medium text-emerald-600"
                >
                    <span>Approved reservations ready for play</span>
                </div>
            </div>

            <!-- Stat card: Total spent -->
            <div
                class="group relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="absolute top-3 right-3 opacity-10 transition-transform group-hover:scale-110"
                >
                    <DollarSign class="h-12 w-12 text-emerald-600" />
                </div>
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Total Investment</span
                >
                <span
                    class="mt-1 block text-2xl font-black text-neutral-900 dark:text-white"
                    >₱{{ stats.totalSpent }}</span
                >
                <div
                    class="mt-2 flex items-center gap-1 text-[10px] font-medium text-neutral-500"
                >
                    <TrendingUp class="h-3.5 w-3.5 text-emerald-600" />
                    <span>Includes approved & pending payments</span>
                </div>
            </div>
        </div>

        <!-- Mobile Slideshow View (< md) -->
        <div
            class="space-y-3 md:hidden"
            @mouseenter="isPaused = true"
            @mouseleave="isPaused = false"
            @touchstart="handleTouchStart"
            @touchend="handleTouchEnd"
        >
            <div
                class="relative min-h-[140px] overflow-hidden rounded-3xl border border-neutral-200/80 bg-white shadow-lg dark:border-neutral-800 dark:bg-neutral-900"
            >
                <!-- Smooth horizontal sliding track -->
                <div
                    class="flex w-full transition-transform duration-500 ease-in-out"
                    :style="{
                        transform: `translateX(-${currentSlide * 100}%)`,
                    }"
                >
                    <!-- Slide 0: Total Bookings -->
                    <div
                        class="flex w-full shrink-0 flex-col justify-between space-y-2 p-6"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-extrabold tracking-wider text-neutral-400 uppercase"
                                >Total Bookings</span
                            >
                            <div
                                class="rounded-xl bg-neutral-100 p-2 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400"
                            >
                                <CalendarRange class="h-6 w-6" />
                            </div>
                        </div>
                        <div>
                            <span
                                class="text-3xl font-black tracking-tight text-neutral-900 dark:text-white"
                                >{{ stats.total }}</span
                            >
                            <p
                                class="mt-1 text-xs font-medium text-neutral-500"
                            >
                                Active and completed reservations
                            </p>
                        </div>
                    </div>

                    <!-- Slide 1: Pending Approval -->
                    <div
                        class="flex w-full shrink-0 flex-col justify-between space-y-2 p-6"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-extrabold tracking-wider text-amber-500 uppercase dark:text-amber-400"
                                >Pending Approval</span
                            >
                            <div
                                class="rounded-xl bg-amber-500/10 p-2 text-amber-600"
                            >
                                <Clock class="h-6 w-6" />
                            </div>
                        </div>
                        <div>
                            <span
                                class="text-3xl font-black tracking-tight text-amber-600 dark:text-amber-400"
                                >{{ stats.pending }}</span
                            >
                            <div
                                class="mt-1 flex items-center gap-1.5 text-xs font-medium text-amber-600"
                            >
                                <span
                                    v-if="stats.pending > 0"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 animate-ping rounded-full bg-amber-500"
                                    ></span>
                                    Awaiting staff confirmation
                                </span>
                                <span v-else>All bookings processed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: Confirmed Bookings -->
                    <div
                        class="flex w-full shrink-0 flex-col justify-between space-y-2 p-6"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-extrabold tracking-wider text-emerald-600 uppercase dark:text-emerald-400"
                                >Confirmed Bookings</span
                            >
                            <div
                                class="rounded-xl bg-emerald-500/10 p-2 text-emerald-600"
                            >
                                <CheckCircle class="h-6 w-6" />
                            </div>
                        </div>
                        <div>
                            <span
                                class="text-3xl font-black tracking-tight text-emerald-600 dark:text-emerald-400"
                                >{{ stats.confirmed }}</span
                            >
                            <p
                                class="mt-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                Approved reservations ready for play
                            </p>
                        </div>
                    </div>

                    <!-- Slide 3: Total Investment -->
                    <div
                        class="flex w-full shrink-0 flex-col justify-between space-y-2 p-6"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-extrabold tracking-wider text-neutral-400 uppercase"
                                >Total Investment</span
                            >
                            <div
                                class="rounded-xl bg-emerald-500/10 p-2 text-emerald-600"
                            >
                                <DollarSign class="h-6 w-6" />
                            </div>
                        </div>
                        <div>
                            <span
                                class="text-3xl font-black tracking-tight text-neutral-900 dark:text-white"
                                >₱{{ stats.totalSpent }}</span
                            >
                            <div
                                class="mt-1 flex items-center gap-1 text-xs font-medium text-neutral-500"
                            >
                                <TrendingUp
                                    class="h-3.5 w-3.5 text-emerald-600"
                                />
                                <span
                                    >Includes approved & pending payments</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slideshow Pagination Dots -->
            <div class="flex items-center justify-center gap-2 pt-1">
                <button
                    v-for="(_, idx) in slidesCount"
                    :key="idx"
                    @click="goToSlide(idx)"
                    class="h-2 rounded-full transition-all duration-300"
                    :class="[
                        currentSlide === idx
                            ? 'w-6 bg-emerald-600 shadow-sm shadow-emerald-600/50'
                            : 'w-2 bg-neutral-300 hover:bg-neutral-400 dark:bg-neutral-700',
                    ]"
                    :aria-label="`Go to slide ${idx + 1}`"
                ></button>
            </div>
        </div>
    </div>
</template>
