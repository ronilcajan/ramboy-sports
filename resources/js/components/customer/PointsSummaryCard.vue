<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Sparkles,
    Gift,
    Award,
    ChevronRight,
    Flame,
    Ticket,
    TrendingUp,
} from '@lucide/vue';
import { computed } from 'vue';

export interface LoyaltyTier {
    name: string;
    badge_color: string;
    next_tier_name: string | null;
    points_to_next: number;
    progress_percentage: number;
    perks?: string[];
}

export interface LoyaltySummary {
    available_points: number;
    lifetime_points: number;
    tier: LoyaltyTier;
    court_loyalty?: Array<{
        court_id: number;
        court_name: string;
        sport_type: string;
        venue_name: string;
        bookings_count: number;
        points_earned: number;
        loyalty_level: string;
    }>;
    venue_loyalty?: Array<{
        venue_id: number;
        venue_name: string;
        venue_address?: string;
        bookings_count: number;
        points_earned: number;
    }>;
    active_claims: Array<{
        id: number;
        voucher_code: string;
        reward_name: string;
        reward_category: string;
        points_spent: number;
        expires_at: string;
        expires_in_days: number | null;
        terms?: string | null;
    }>;
    recent_transactions: Array<{
        id: number;
        points: number;
        type: string;
        description: string;
        date: string;
        is_positive: boolean;
    }>;
    total_claims_count: number;
}

const props = defineProps<{
    loyalty?: LoyaltySummary;
}>();

const availablePoints = computed(() => props.loyalty?.available_points ?? 0);
const tier = computed(() => props.loyalty?.tier);
const activeClaimsCount = computed(
    () => props.loyalty?.active_claims?.length ?? 0,
);
const topCourt = computed(() => props.loyalty?.court_loyalty?.[0]);
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl border border-emerald-500/20 bg-gradient-to-br from-emerald-950/40 via-neutral-900 to-neutral-950 p-6 text-white shadow-xl"
    >
        <!-- Ambient Decorative Glows -->
        <div
            class="pointer-events-none absolute -top-16 -right-16 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-teal-500/10 blur-2xl"
        ></div>

        <div
            class="relative z-10 flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center"
        >
            <!-- Left: Points & Tier Info -->
            <div class="max-w-xl space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/15 px-3 py-1 text-xs font-black tracking-wide text-emerald-400 uppercase"
                    >
                        <Sparkles class="h-3.5 w-3.5 animate-pulse" />
                        Loyalty Rewards
                    </span>
                    <span
                        v-if="tier"
                        class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r px-3 py-1 text-xs font-black text-white shadow-sm"
                        :class="tier.badge_color"
                    >
                        <Award class="h-3.5 w-3.5" />
                        {{ tier.name }}
                    </span>
                    <span
                        v-if="activeClaimsCount > 0"
                        class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/20 px-2.5 py-0.5 text-[11px] font-bold text-amber-300"
                    >
                        <Ticket class="h-3 w-3" />
                        {{ activeClaimsCount }} Active
                        {{ activeClaimsCount === 1 ? 'Voucher' : 'Vouchers' }}
                    </span>
                </div>

                <div>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-4xl font-black tracking-tight text-white lg:text-5xl"
                        >
                            {{ availablePoints }}
                        </span>
                        <span
                            class="text-sm font-extrabold tracking-wider text-emerald-400 uppercase"
                        >
                            Points Available
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-neutral-400">
                        Earn +50 pts per booking plus repeat court loyalty
                        bonuses every time you play.
                    </p>
                </div>

                <!-- Tier Progression Bar -->
                <div
                    v-if="tier && tier.next_tier_name"
                    class="space-y-1.5 pt-1"
                >
                    <div
                        class="flex items-center justify-between text-[11px] font-bold"
                    >
                        <span class="text-neutral-400">
                            Progress to
                            <span class="text-white">{{
                                tier.next_tier_name
                            }}</span>
                        </span>
                        <span class="text-emerald-400"
                            >{{ tier.points_to_next }} pts to unlock</span
                        >
                    </div>
                    <div
                        class="h-2 w-full overflow-hidden rounded-full border border-neutral-700/50 bg-neutral-800/80"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-700"
                            :style="{ width: `${tier.progress_percentage}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Middle/Right: Court Loyalty Spotlight & Actions -->
            <div
                class="flex w-full shrink-0 flex-col items-stretch gap-3 sm:flex-row lg:w-auto lg:flex-col"
            >
                <!-- Top Court Loyalty Badge -->
                <div
                    v-if="topCourt"
                    class="flex items-center gap-3 rounded-xl border border-neutral-700/40 bg-neutral-800/60 p-3.5 backdrop-blur-sm"
                >
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-400"
                    >
                        <Flame class="h-5 w-5" />
                    </div>
                    <div class="overflow-hidden text-left">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-[10px] font-bold tracking-wider text-neutral-400 uppercase"
                                >Favorite Location</span
                            >
                            <span
                                class="py-0.2 rounded bg-emerald-500/20 px-1.5 text-[9px] font-extrabold text-emerald-300"
                                >{{ topCourt.loyalty_level }}</span
                            >
                        </div>
                        <p class="truncate text-xs font-bold text-white">
                            {{ topCourt.court_name }}
                        </p>
                        <p class="text-[10px] text-neutral-400">
                            {{ topCourt.bookings_count }} bookings • +{{
                                topCourt.points_earned
                            }}
                            pts earned
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <Link
                        href="/customer/rewards"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-black text-white shadow-lg shadow-emerald-500/20 transition-all hover:scale-[1.02] hover:bg-emerald-600 active:scale-[0.98] sm:flex-none"
                    >
                        <Gift class="h-4 w-4" />
                        <span>Claim Freebies</span>
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>

                    <Link
                        href="/customer/rewards?tab=history"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-neutral-700/50 bg-neutral-800 px-3.5 py-2.5 text-xs font-bold text-neutral-300 transition-colors hover:bg-neutral-700 hover:text-white"
                        title="Points History"
                    >
                        <TrendingUp class="h-4 w-4 text-emerald-400" />
                        <span class="hidden sm:inline">Ledger</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
