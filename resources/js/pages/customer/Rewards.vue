<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Gift,
    Sparkles,
    Award,
    Ticket,
    CupSoda,
    Shirt,
    Crown,
    Percent,
    Dumbbell,
    Flame,
    CheckCircle,
    Copy,
    Check,
    ChevronRight,
    ArrowUpRight,
    ArrowDownLeft,
    TrendingUp,
    Info,
    X,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import type { LoyaltySummary } from '@/components/customer/PointsSummaryCard.vue';

export interface RewardItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category: string;
    points_cost: number;
    stock: number | null;
    badge_text: string | null;
    icon: string;
    terms: string | null;
    is_in_stock: boolean;
    can_afford: boolean;
    points_needed: number;
    progress_percent: number;
    venue: { id: number; name: string } | null;
}

export interface CategoryItem {
    id: string;
    label: string;
}

const props = withDefaults(
    defineProps<{
        loyaltySummary?: LoyaltySummary;
        rewards?: RewardItem[];
        categories?: CategoryItem[];
    }>(),
    {
        rewards: () => [],
        categories: () => [
            { id: 'all', label: 'All Rewards' },
            { id: 'drink', label: 'Drinks & Refreshments' },
            { id: 'gear', label: 'Equipment & Gear' },
            { id: 'discount', label: 'Booking Discounts' },
            { id: 'apparel', label: 'Apparel & Merch' },
        ],
        loyaltySummary: () => ({
            available_points: 0,
            lifetime_points: 0,
            tier: {
                name: 'Bronze Member',
                badge_color: 'from-amber-700 to-amber-800',
                next_tier_name: 'Silver Preferred',
                points_to_next: 400,
                progress_percentage: 0,
            },
            active_claims: [],
            recent_transactions: [],
            court_loyalty: [],
            total_claims_count: 0,
        }),
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Rewards & Points', href: '/customer/rewards' },
        ],
    },
});

// URL Query parameter tab initialization
const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const tab = new URLSearchParams(window.location.search).get('tab');

        if (
            tab &&
            ['catalog', 'vouchers', 'loyalty', 'history'].includes(tab)
        ) {
            return tab as 'catalog' | 'vouchers' | 'loyalty' | 'history';
        }
    }

    return 'catalog';
};

const activeTab = ref<'catalog' | 'vouchers' | 'loyalty' | 'history'>(
    getInitialTab(),
);

const selectedCategory = ref<string>('all');
const searchQuery = ref<string>('');

// Claim Modal State
const selectedReward = ref<RewardItem | null>(null);
const isClaimModalOpen = ref(false);
const isClaiming = ref(false);
const claimedVoucher = ref<{
    voucher_code: string;
    reward_name: string;
    points_spent: number;
    expires_at: string;
    terms?: string;
} | null>(null);

const copiedCode = ref<string | null>(null);

// Selected Voucher View Modal

// Filtering rewards
const filteredRewards = computed(() => {
    return (props.rewards || []).filter((reward) => {
        const matchesCategory =
            selectedCategory.value === 'all' ||
            reward.category === selectedCategory.value;
        const matchesSearch =
            !searchQuery.value.trim() ||
            reward.name
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            (reward.description &&
                reward.description
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()));

        return matchesCategory && matchesSearch;
    });
});

function getIconComponent(iconName: string) {
    switch (iconName) {
        case 'CupSoda':
            return CupSoda;
        case 'Shirt':
            return Shirt;
        case 'Crown':
            return Crown;
        case 'Percent':
            return Percent;
        case 'Dumbbell':
            return Dumbbell;
        case 'Ticket':
            return Ticket;
        case 'Sparkles':
            return Sparkles;
        default:
            return Gift;
    }
}

function openClaimModal(reward: RewardItem) {
    selectedReward.value = reward;
    claimedVoucher.value = null;
    isClaimModalOpen.value = true;
}

function closeClaimModal() {
    isClaimModalOpen.value = false;
    selectedReward.value = null;
    claimedVoucher.value = null;
}

const form = useForm({});

function confirmClaim() {
    if (!selectedReward.value) {
        return;
    }

    isClaiming.value = true;
    form.post(`/customer/rewards/${selectedReward.value.id}/claim`, {
        preserveScroll: true,
        onSuccess: (pageData: any) => {
            isClaiming.value = false;
            const flash = pageData?.props?.flash as any;
            const voucher =
                pageData?.props?.claimed_voucher || flash?.claimed_voucher;

            if (voucher) {
                claimedVoucher.value = voucher;
            } else {
                closeClaimModal();
            }
        },
        onError: () => {
            isClaiming.value = false;
        },
    });
}

function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text);
    copiedCode.value = text;
    setTimeout(() => {
        if (copiedCode.value === text) {
            copiedCode.value = null;
        }
    }, 2500);
}
</script>

<template>
    <Head title="Rewards & Points Center" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-6 pb-24">
        <!-- Hero Loyalty Card -->
        <div
            class="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-neutral-900 via-neutral-900 to-emerald-950/60 p-6 text-white shadow-2xl md:p-8"
        >
            <div
                class="pointer-events-none absolute -top-20 -right-20 h-80 w-80 rounded-full bg-emerald-500/15 blur-3xl"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-teal-500/10 blur-3xl"
            ></div>

            <div
                class="relative z-10 flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-center"
            >
                <!-- Left Details -->
                <div class="max-w-2xl space-y-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/20 px-3 py-1 text-xs font-black tracking-wider text-emerald-300 uppercase"
                        >
                            <Sparkles class="h-3.5 w-3.5 animate-pulse" />
                            Member Loyalty Club
                        </span>
                        <span
                            v-if="loyaltySummary.tier"
                            class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r px-3.5 py-1 text-xs font-black text-white shadow-md"
                            :class="loyaltySummary.tier.badge_color"
                        >
                            <Award class="h-3.5 w-3.5" />
                            {{ loyaltySummary.tier.name }}
                        </span>
                    </div>

                    <div>
                        <h1
                            class="text-3xl font-black tracking-tight text-white md:text-4xl"
                        >
                            Points & Freebies Hub
                        </h1>
                        <p
                            class="mt-1 max-w-xl text-xs text-neutral-400 md:text-sm"
                        >
                            Earn points every time you reserve a court or
                            location. Repeat bookings at your favorite courts
                            earn exclusive loyalty multiplier bonuses!
                        </p>
                    </div>

                    <!-- Tier Progression Bar -->
                    <div
                        v-if="loyaltySummary.tier?.next_tier_name"
                        class="space-y-2 pt-2"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-bold"
                        >
                            <span class="text-neutral-300">
                                Current Tier:
                                <span class="text-white">{{
                                    loyaltySummary.tier.name
                                }}</span>
                            </span>
                            <span class="text-emerald-400">
                                {{ loyaltySummary.tier.points_to_next }} pts to
                                {{ loyaltySummary.tier.next_tier_name }}
                            </span>
                        </div>
                        <div
                            class="h-2.5 w-full overflow-hidden rounded-full border border-neutral-700 bg-neutral-800"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-300 shadow-sm transition-all duration-700"
                                :style="{
                                    width: `${loyaltySummary.tier.progress_percentage}%`,
                                }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Right Stats Widget -->
                <div
                    class="flex w-full shrink-0 flex-row items-center justify-between gap-6 border-t border-neutral-800 pt-4 lg:w-auto lg:flex-col lg:items-end lg:border-t-0 lg:pt-0"
                >
                    <div class="text-left lg:text-right">
                        <span
                            class="block text-[11px] font-extrabold tracking-wider text-emerald-400 uppercase"
                            >Available Balance</span
                        >
                        <div class="mt-0.5 flex items-baseline gap-1.5">
                            <span
                                class="text-4xl font-black tracking-tight text-white md:text-5xl"
                                >{{ loyaltySummary.available_points }}</span
                            >
                            <span class="text-xs font-bold text-neutral-400"
                                >PTS</span
                            >
                        </div>
                        <span class="mt-0.5 block text-[11px] text-neutral-400">
                            Lifetime Earned:
                            <strong class="text-white"
                                >{{
                                    loyaltySummary.lifetime_points
                                }}
                                pts</strong
                            >
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="activeTab = 'vouchers'"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-neutral-700 bg-neutral-800/80 px-4 py-2 text-xs font-bold text-neutral-200 transition-colors hover:bg-neutral-700"
                        >
                            <Ticket class="h-4 w-4 text-emerald-400" />
                            <span
                                >{{
                                    loyaltySummary?.active_claims?.length ?? 0
                                }}
                                Active Vouchers</span
                            >
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div
            class="flex items-center gap-2 overflow-x-auto border-b border-neutral-200 pb-2 dark:border-neutral-800"
        >
            <button
                type="button"
                @click="activeTab = 'catalog'"
                class="flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                :class="[
                    activeTab === 'catalog'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800',
                ]"
            >
                <Gift class="h-4 w-4" />
                <span>Rewards Catalog</span>
                <span
                    class="rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] font-extrabold text-white"
                >
                    {{ (rewards || []).length }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'vouchers'"
                class="flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                :class="[
                    activeTab === 'vouchers'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800',
                ]"
            >
                <Ticket class="h-4 w-4" />
                <span>My Vouchers</span>
                <span
                    v-if="(loyaltySummary?.active_claims?.length ?? 0) > 0"
                    class="animate-pulse rounded-full bg-amber-400 px-1.5 py-0.5 text-[10px] font-extrabold text-neutral-950"
                >
                    {{ loyaltySummary?.active_claims?.length ?? 0 }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'loyalty'"
                class="flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                :class="[
                    activeTab === 'loyalty'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800',
                ]"
            >
                <Flame class="h-4 w-4" />
                <span>Court Loyalty</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'history'"
                class="flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                :class="[
                    activeTab === 'history'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800',
                ]"
            >
                <TrendingUp class="h-4 w-4" />
                <span>Points Ledger</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: REWARDS CATALOG                    -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'catalog'" class="space-y-6">
            <!-- Filter Bar -->
            <div
                class="flex flex-col items-stretch justify-between gap-4 sm:flex-row sm:items-center"
            >
                <!-- Category Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="selectedCategory = cat.id"
                        class="shrink-0 cursor-pointer rounded-full border px-3.5 py-1.5 text-xs font-bold transition-all"
                        :class="[
                            selectedCategory === cat.id
                                ? 'border-neutral-900 bg-neutral-900 text-white shadow-sm dark:border-white dark:bg-white dark:text-neutral-900'
                                : 'border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:border-neutral-700',
                        ]"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Search box -->
                <div class="relative min-w-[220px]">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search rewards..."
                        class="w-full rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-xs text-neutral-900 placeholder-neutral-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none dark:border-neutral-800 dark:bg-neutral-900 dark:text-white"
                    />
                </div>
            </div>

            <!-- Rewards Cards Grid -->
            <div
                v-if="filteredRewards.length > 0"
                class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="reward in filteredRewards"
                    :key="reward.id"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-300 dark:bg-neutral-900"
                    :class="[
                        reward.can_afford
                            ? 'border-emerald-500/30 hover:border-emerald-500/60 hover:shadow-lg hover:shadow-emerald-500/5'
                            : 'border-neutral-200 opacity-90 hover:opacity-100 dark:border-neutral-800',
                    ]"
                >
                    <!-- Top Badge & Points requirement -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span
                                v-if="reward.badge_text"
                                class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-black tracking-wider text-emerald-600 uppercase dark:text-emerald-400"
                            >
                                {{ reward.badge_text }}
                            </span>
                            <span
                                v-else
                                class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                            >
                                {{ reward.category }}
                            </span>

                            <div
                                class="flex items-center gap-1 rounded-xl bg-neutral-100 px-2.5 py-1 text-xs font-black text-neutral-900 dark:bg-neutral-800 dark:text-white"
                            >
                                <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                                <span>{{ reward.points_cost }}</span>
                                <span class="text-[10px] text-neutral-400"
                                    >PTS</span
                                >
                            </div>
                        </div>

                        <!-- Icon & Title -->
                        <div class="flex items-start gap-3.5">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl transition-transform group-hover:scale-105"
                                :class="[
                                    reward.can_afford
                                        ? 'border border-emerald-500/20 bg-gradient-to-br from-emerald-500/20 to-teal-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'border border-neutral-200 bg-neutral-100 text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800',
                                ]"
                            >
                                <component
                                    :is="getIconComponent(reward.icon)"
                                    class="h-6 w-6"
                                />
                            </div>

                            <div class="space-y-1">
                                <h3
                                    class="text-sm leading-snug font-black tracking-tight text-neutral-900 dark:text-white"
                                >
                                    {{ reward.name }}
                                </h3>
                                <p
                                    class="line-clamp-2 text-xs leading-relaxed text-neutral-500 dark:text-neutral-400"
                                >
                                    {{ reward.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Terms preview -->
                        <div
                            v-if="reward.terms"
                            class="flex items-start gap-1 rounded-xl border border-neutral-100 bg-neutral-50 p-2.5 text-[11px] text-neutral-400 dark:border-neutral-800/60 dark:bg-neutral-800/40"
                        >
                            <Info
                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-neutral-400"
                            />
                            <span class="line-clamp-2">{{ reward.terms }}</span>
                        </div>
                    </div>

                    <!-- Bottom Action & Progress -->
                    <div
                        class="mt-4 space-y-2 border-t border-neutral-100 pt-4 dark:border-neutral-800"
                    >
                        <!-- Progress towards reward if user cannot afford -->
                        <div v-if="!reward.can_afford" class="space-y-1">
                            <div
                                class="flex items-center justify-between text-[10px] font-bold text-neutral-400"
                            >
                                <span
                                    >{{ reward.progress_percent }}% earned</span
                                >
                                <span
                                    >{{ reward.points_needed }} pts needed</span
                                >
                            </div>
                            <div
                                class="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800"
                            >
                                <div
                                    class="h-full rounded-full bg-emerald-500"
                                    :style="{
                                        width: `${reward.progress_percent}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- Claim Button -->
                        <button
                            type="button"
                            @click="openClaimModal(reward)"
                            :disabled="
                                !reward.can_afford || !reward.is_in_stock
                            "
                            class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition-all disabled:cursor-not-allowed"
                            :class="[
                                reward.can_afford && reward.is_in_stock
                                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20 hover:scale-[1.01] hover:bg-emerald-700'
                                    : 'border border-neutral-200 bg-neutral-100 text-neutral-400 dark:border-neutral-700 dark:bg-neutral-800',
                            ]"
                        >
                            <Gift class="h-4 w-4" />
                            <span v-if="!reward.is_in_stock">Out of Stock</span>
                            <span v-else-if="reward.can_afford"
                                >Claim Reward</span
                            >
                            <span v-else
                                >Need {{ reward.points_needed }} More Pts</span
                            >
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="space-y-3 rounded-3xl border border-neutral-200 bg-white p-8 py-16 text-center dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-400 dark:bg-neutral-800"
                >
                    <Gift class="h-6 w-6" />
                </div>
                <h4
                    class="text-base font-black text-neutral-900 dark:text-white"
                >
                    No rewards found
                </h4>
                <p class="mx-auto max-w-sm text-xs text-neutral-500">
                    Try selecting another category or clearing your search
                    filter.
                </p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: MY VOUCHERS                        -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'vouchers'" class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                    >
                        Claimed Digital Vouchers
                    </h2>
                    <p class="text-xs text-neutral-500">
                        Present these vouchers at the reception counter or
                        check-in desk to redeem your freebies.
                    </p>
                </div>
            </div>

            <div
                v-if="(loyaltySummary?.active_claims?.length ?? 0) > 0"
                class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="claim in loyaltySummary?.active_claims ?? []"
                    :key="claim.id"
                    class="relative flex flex-col justify-between overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-neutral-900 via-neutral-900 to-emerald-950/40 p-5 text-white shadow-lg"
                >
                    <!-- Ticket Header -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/20 px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider text-emerald-300 uppercase"
                            >
                                <CheckCircle class="h-3 w-3" />
                                Ready to Use
                            </span>
                            <span
                                class="text-[11px] font-bold text-neutral-400"
                            >
                                Expires: {{ claim.expires_at }}
                            </span>
                        </div>

                        <div>
                            <h3
                                class="text-base font-black tracking-tight text-white"
                            >
                                {{ claim.reward_name }}
                            </h3>
                            <p class="mt-0.5 text-xs text-neutral-400">
                                {{
                                    claim.terms ||
                                    'Show this voucher code to the staff at the facility desk.'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Voucher Code Display & Copy Button -->
                    <div
                        class="mt-5 space-y-3 border-t border-dashed border-neutral-700/80 pt-4"
                    >
                        <div
                            class="flex items-center justify-between rounded-2xl border border-neutral-800 bg-neutral-950/90 p-3.5"
                        >
                            <div>
                                <span
                                    class="block text-[10px] font-extrabold tracking-wider text-neutral-500 uppercase"
                                    >Voucher Code</span
                                >
                                <span
                                    class="font-mono text-sm font-black tracking-wider text-emerald-400 md:text-base"
                                >
                                    {{ claim.voucher_code }}
                                </span>
                            </div>

                            <button
                                type="button"
                                @click="copyToClipboard(claim.voucher_code)"
                                class="flex cursor-pointer items-center gap-1 rounded-xl bg-neutral-800 px-3 py-1.5 text-xs font-bold text-neutral-200 transition-colors hover:bg-neutral-700"
                            >
                                <Check
                                    v-if="copiedCode === claim.voucher_code"
                                    class="h-3.5 w-3.5 text-emerald-400"
                                />
                                <Copy v-else class="h-3.5 w-3.5" />
                                <span>{{
                                    copiedCode === claim.voucher_code
                                        ? 'Copied!'
                                        : 'Copy'
                                }}</span>
                            </button>
                        </div>

                        <div
                            class="flex items-center justify-between text-[11px] text-neutral-400"
                        >
                            <span
                                >Spent:
                                <strong
                                    >{{ claim.points_spent }} pts</strong
                                ></span
                            >
                            <span
                                class="font-bold text-emerald-400"
                                v-if="claim.expires_in_days !== null"
                            >
                                {{ claim.expires_in_days }} days left
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty Vouchers -->
            <div
                v-else
                class="space-y-3 rounded-3xl border border-neutral-200 bg-white p-8 py-16 text-center dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-400 dark:bg-neutral-800"
                >
                    <Ticket class="h-6 w-6" />
                </div>
                <h4
                    class="text-base font-black text-neutral-900 dark:text-white"
                >
                    No active vouchers
                </h4>
                <p class="mx-auto max-w-sm text-xs text-neutral-500">
                    You have not claimed any vouchers yet. Explore our rewards
                    catalog to redeem your points!
                </p>
                <button
                    type="button"
                    @click="activeTab = 'catalog'"
                    class="mt-2 inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
                >
                    <Gift class="h-4 w-4" /> Browse Rewards
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: COURT & LOCATION LOYALTY           -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'loyalty'" class="space-y-6">
            <div>
                <h2
                    class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    Court & Location Loyalty Multipliers
                </h2>
                <p class="text-xs text-neutral-500">
                    Every repeated session at a specific court grants you extra
                    bonus points!
                </p>
            </div>

            <!-- Courts loyalty breakdown -->
            <div
                v-if="(loyaltySummary.court_loyalty?.length ?? 0) > 0"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="court in loyaltySummary.court_loyalty ?? []"
                    :key="court.court_id"
                    class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <span
                                class="rounded-md bg-emerald-500/10 px-2 py-0.5 text-[10px] font-black text-emerald-600 uppercase dark:text-emerald-400"
                            >
                                {{ court.loyalty_level }}
                            </span>
                            <h3
                                class="text-base font-black tracking-tight text-neutral-900 dark:text-white"
                            >
                                {{ court.court_name }}
                            </h3>
                            <p class="text-xs text-neutral-500">
                                {{ court.venue_name }} • {{ court.sport_type }}
                            </p>
                        </div>

                        <div
                            class="shrink-0 rounded-2xl bg-emerald-500/10 p-2.5 text-emerald-600 dark:text-emerald-400"
                        >
                            <Flame class="h-5 w-5" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 border-t border-neutral-100 pt-3 dark:border-neutral-800"
                    >
                        <div
                            class="rounded-xl bg-neutral-50 p-3 dark:bg-neutral-800/50"
                        >
                            <span
                                class="block text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                                >Reservations</span
                            >
                            <span
                                class="text-lg font-black text-neutral-900 dark:text-white"
                                >{{ court.bookings_count }}</span
                            >
                        </div>
                        <div
                            class="rounded-xl bg-neutral-50 p-3 dark:bg-neutral-800/50"
                        >
                            <span
                                class="block text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                                >Points Earned</span
                            >
                            <span
                                class="text-lg font-black text-emerald-600 dark:text-emerald-400"
                                >+{{ court.points_earned }}</span
                            >
                        </div>
                    </div>

                    <Link
                        href="/courts"
                        class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-neutral-100 px-3 py-2 text-xs font-bold text-neutral-700 transition-colors hover:bg-emerald-600 hover:text-white dark:bg-neutral-800 dark:text-neutral-300"
                    >
                        <span>Book Again & Earn Bonus</span>
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <div
                v-else
                class="space-y-3 rounded-3xl border border-neutral-200 bg-white p-8 py-16 text-center dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-400 dark:bg-neutral-800"
                >
                    <Flame class="h-6 w-6" />
                </div>
                <h4
                    class="text-base font-black text-neutral-900 dark:text-white"
                >
                    No court history yet
                </h4>
                <p class="mx-auto max-w-sm text-xs text-neutral-500">
                    Book your first court session to start building your court
                    loyalty multipliers!
                </p>
                <Link
                    href="/courts"
                    class="mt-2 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
                >
                    Book a Court
                </Link>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: POINTS ACTIVITY HISTORY / LEDGER    -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'history'" class="space-y-4">
            <div>
                <h2
                    class="text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    Points Activity Ledger
                </h2>
                <p class="text-xs text-neutral-500">
                    Full audit trail of points earned from court bookings and
                    deducted for reward redemptions.
                </p>
            </div>

            <div
                v-if="(loyaltySummary?.recent_transactions?.length ?? 0) > 0"
                class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="divide-y divide-neutral-100 dark:divide-neutral-800"
                >
                    <div
                        v-for="tx in loyaltySummary?.recent_transactions ?? []"
                        :key="tx.id"
                        class="flex items-center justify-between gap-4 p-4 transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="[
                                    tx.is_positive
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                ]"
                            >
                                <ArrowDownLeft
                                    v-if="tx.is_positive"
                                    class="h-5 w-5"
                                />
                                <ArrowUpRight v-else class="h-5 w-5" />
                            </div>

                            <div class="space-y-0.5">
                                <p
                                    class="text-xs font-bold text-neutral-900 dark:text-white"
                                >
                                    {{ tx.description }}
                                </p>
                                <p class="text-[11px] text-neutral-400">
                                    {{ tx.date }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 text-right">
                            <span
                                class="text-sm font-black tracking-tight"
                                :class="[
                                    tx.is_positive
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-neutral-900 dark:text-white',
                                ]"
                            >
                                {{
                                    tx.is_positive ? `+${tx.points}` : tx.points
                                }}
                                pts
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="space-y-2 rounded-3xl border border-neutral-200 bg-white p-8 py-16 text-center dark:border-neutral-800 dark:bg-neutral-900"
            >
                <p class="text-xs text-neutral-500">
                    No point transactions recorded yet.
                </p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CLAIM MODAL / CELEBRATION MODAL            -->
        <!-- ========================================== -->
        <div
            v-if="isClaimModalOpen && selectedReward"
            class="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/75 p-4 backdrop-blur-sm"
        >
            <div
                class="relative w-full max-w-md overflow-hidden rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <!-- Close button -->
                <button
                    type="button"
                    @click="closeClaimModal"
                    class="absolute top-4 right-4 cursor-pointer rounded-full p-2 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-white"
                >
                    <X class="h-4 w-4" />
                </button>

                <!-- 1. Post-Claim Celebration State -->
                <div v-if="claimedVoucher" class="space-y-5 py-2 text-center">
                    <div
                        class="mx-auto flex h-16 w-16 animate-bounce items-center justify-center rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-lg shadow-emerald-500/30"
                    >
                        <Gift class="h-8 w-8" />
                    </div>

                    <div>
                        <span
                            class="text-xs font-black tracking-wider text-emerald-600 uppercase dark:text-emerald-400"
                            >Reward Unlocked!</span
                        >
                        <h3
                            class="mt-1 text-xl font-black tracking-tight text-neutral-900 dark:text-white"
                        >
                            {{ claimedVoucher.reward_name }}
                        </h3>
                        <p class="mt-1 text-xs text-neutral-500">
                            Your digital voucher is active and ready to use!
                        </p>
                    </div>

                    <!-- Digital Voucher Box -->
                    <div
                        class="space-y-3 rounded-2xl border border-neutral-800 bg-neutral-950 p-4 text-left text-white"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                                >Redemption Code</span
                            >
                            <span class="text-[10px] font-bold text-emerald-400"
                                >Valid for 30 Days</span
                            >
                        </div>

                        <div
                            class="flex items-center justify-between rounded-xl border border-neutral-800 bg-neutral-900 p-3"
                        >
                            <span
                                class="font-mono text-base font-black tracking-wider text-emerald-400 md:text-lg"
                            >
                                {{ claimedVoucher.voucher_code }}
                            </span>
                            <button
                                type="button"
                                @click="
                                    copyToClipboard(claimedVoucher.voucher_code)
                                "
                                class="flex cursor-pointer items-center gap-1 rounded-lg bg-neutral-800 px-3 py-1 text-xs font-bold transition-colors hover:bg-neutral-700"
                            >
                                <Check
                                    v-if="
                                        copiedCode ===
                                        claimedVoucher.voucher_code
                                    "
                                    class="h-3.5 w-3.5 text-emerald-400"
                                />
                                <Copy v-else class="h-3.5 w-3.5" />
                                <span>{{
                                    copiedCode === claimedVoucher.voucher_code
                                        ? 'Copied'
                                        : 'Copy'
                                }}</span>
                            </button>
                        </div>

                        <p class="text-[11px] text-neutral-400">
                            {{
                                claimedVoucher.terms ||
                                'Present this code at the facility counter before or after your court session.'
                            }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button
                            type="button"
                            @click="
                                closeClaimModal();
                                activeTab = 'vouchers';
                            "
                            class="w-full cursor-pointer rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md transition-colors hover:bg-emerald-700"
                        >
                            View My Vouchers
                        </button>
                    </div>
                </div>

                <!-- 2. Pre-Claim Confirmation State -->
                <div v-else class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <component
                                :is="getIconComponent(selectedReward.icon)"
                                class="h-6 w-6"
                            />
                        </div>
                        <div>
                            <span
                                class="text-[10px] font-black tracking-wider text-emerald-600 uppercase dark:text-emerald-400"
                                >Confirm Claim</span
                            >
                            <h3
                                class="text-base font-black tracking-tight text-neutral-900 dark:text-white"
                            >
                                {{ selectedReward.name }}
                            </h3>
                        </div>
                    </div>

                    <p
                        class="text-xs leading-relaxed text-neutral-500 dark:text-neutral-400"
                    >
                        {{ selectedReward.description }}
                    </p>

                    <!-- Points breakdown calculation -->
                    <div
                        class="space-y-2 rounded-2xl border border-neutral-100 bg-neutral-50 p-4 text-xs dark:border-neutral-800 dark:bg-neutral-800/60"
                    >
                        <div
                            class="flex items-center justify-between text-neutral-600 dark:text-neutral-400"
                        >
                            <span>Your current balance:</span>
                            <span
                                class="font-bold text-neutral-900 dark:text-white"
                                >{{ loyaltySummary.available_points }} pts</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between font-medium text-amber-600 dark:text-amber-400"
                        >
                            <span>Points cost:</span>
                            <span class="font-bold"
                                >-{{ selectedReward.points_cost }} pts</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between border-t border-neutral-200 pt-2 font-black text-neutral-900 dark:border-neutral-700 dark:text-white"
                        >
                            <span>Remaining balance:</span>
                            <span
                                class="text-emerald-600 dark:text-emerald-400"
                            >
                                {{
                                    loyaltySummary.available_points -
                                    selectedReward.points_cost
                                }}
                                pts
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeClaimModal"
                            class="flex-1 cursor-pointer rounded-xl border border-neutral-200 px-4 py-2.5 text-xs font-bold text-neutral-700 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmClaim"
                            :disabled="isClaiming"
                            class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-600/20 transition-all hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Sparkles class="h-4 w-4" />
                            <span>{{
                                isClaiming ? 'Claiming...' : 'Confirm & Claim'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
