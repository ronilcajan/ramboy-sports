<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    Sparkles,
    Ticket,
    ArrowDownLeft,
    ArrowUpRight,
    TrendingUp,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface UserDetail {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string;
}

interface Booking {
    id: number;
    date: string;
    time_slots: string[];
    total_price: string;
    status: string;
    court?: { id: number; name: string };
}

interface PaginatedBookings {
    data: Booking[];
}

interface ClaimItem {
    id: number;
    voucher_code: string;
    reward_name: string;
    points_spent: number;
    status: string;
    expires_at: string | null;
    created_at: string;
}

interface LoyaltySummary {
    available_points: number;
    lifetime_points: number;
    tier: {
        name: string;
        badge_color: string;
        next_tier_name: string | null;
        points_to_next: number;
        progress_percentage: number;
    };
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
    user: UserDetail;
    bookings: PaginatedBookings;
    loyaltySummary?: LoyaltySummary;
    claims?: ClaimItem[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Super Admin Overview', href: '/admin/dashboard' },
        { title: 'User Accounts', href: '/admin/users' },
        { title: props.user.name, href: `/admin/users/${props.user.id}` },
    ],
});

const showPointsModal = ref(false);

const pointsForm = useForm({
    action: 'add' as 'add' | 'deduct',
    amount: 50,
    reason: '',
});

function openPointsModal() {
    pointsForm.reset();
    pointsForm.action = 'add';
    pointsForm.amount = 50;
    pointsForm.reason = '';
    pointsForm.clearErrors();
    showPointsModal.value = true;
}

function submitPoints() {
    pointsForm.post(`/admin/users/${props.user.id}/adjust-points`, {
        onSuccess: () => {
            showPointsModal.value = false;
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`${user.name} - User Profile & History`" />

    <div class="w-full space-y-6 p-6 pb-16">
        <Link
            href="/admin/users"
            class="flex cursor-pointer items-center gap-1 text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white"
        >
            <ArrowLeft class="h-4 w-4" /> Back to Users
        </Link>

        <!-- Profile Summary Card -->
        <div
            class="flex flex-col justify-between gap-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span
                        v-for="r in user.roles"
                        :key="r"
                        class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 capitalize dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        {{ r }}
                    </span>
                    <span
                        v-if="loyaltySummary?.tier"
                        class="rounded-full bg-gradient-to-r px-2.5 py-0.5 text-xs font-bold text-white"
                        :class="loyaltySummary.tier.badge_color"
                    >
                        {{ loyaltySummary.tier.name }}
                    </span>
                </div>
                <h1
                    class="text-2xl font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    {{ user.name }}
                </h1>
                <p class="text-xs text-neutral-500">
                    {{ user.email }} &bull; Registered {{ user.created_at }}
                </p>
            </div>

            <button
                type="button"
                @click="openPointsModal"
                class="flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-emerald-600/20 transition-all hover:bg-emerald-700"
            >
                <Sparkles class="h-4 w-4" /> Adjust Loyalty Points
            </button>
        </div>

        <!-- Loyalty Points Overview Grid -->
        <div
            v-if="loyaltySummary"
            class="grid grid-cols-2 gap-4 md:grid-cols-4"
        >
            <div
                class="rounded-2xl border border-emerald-500/20 bg-gradient-to-br from-emerald-500/10 to-teal-500/5 p-5 shadow-sm dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-500 uppercase dark:text-neutral-400"
                    >Available Balance</span
                >
                <div class="mt-1 flex items-baseline gap-1">
                    <span
                        class="text-3xl font-black text-emerald-600 dark:text-emerald-400"
                        >{{ loyaltySummary.available_points }}</span
                    >
                    <span class="text-xs font-bold text-neutral-400">PTS</span>
                </div>
                <span class="mt-1 block text-[10px] text-neutral-500"
                    >Current spendable points</span
                >
            </div>

            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Lifetime Earned</span
                >
                <div class="mt-1 flex items-baseline gap-1">
                    <span
                        class="text-3xl font-black text-neutral-900 dark:text-white"
                        >{{ loyaltySummary.lifetime_points }}</span
                    >
                    <span class="text-xs font-bold text-neutral-400">PTS</span>
                </div>
                <span class="mt-1 block text-[10px] text-neutral-500"
                    >All-time booking points</span
                >
            </div>

            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Loyalty Rank</span
                >
                <span
                    class="mt-1 block text-lg font-black text-neutral-900 dark:text-white"
                    >{{ loyaltySummary.tier.name }}</span
                >
                <span
                    class="mt-1 block text-[10px] font-bold text-emerald-600"
                    v-if="loyaltySummary.tier.next_tier_name"
                >
                    {{ loyaltySummary.tier.points_to_next }} pts to
                    {{ loyaltySummary.tier.next_tier_name }}
                </span>
                <span class="mt-1 block text-[10px] text-neutral-400" v-else
                    >Top tier reached</span
                >
            </div>

            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Claimed Freebies</span
                >
                <span class="mt-1 block text-3xl font-black text-amber-600">{{
                    loyaltySummary.total_claims_count
                }}</span>
                <span class="mt-1 block text-[10px] text-neutral-500"
                    >Total vouchers claimed</span
                >
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Points Transaction History Ledger -->
            <div
                v-if="loyaltySummary"
                class="space-y-3 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-neutral-900 dark:text-white"
                    >
                        <TrendingUp class="h-4 w-4 text-emerald-600" /> Points
                        Activity Ledger
                    </h3>
                    <button
                        type="button"
                        @click="openPointsModal"
                        class="cursor-pointer text-[11px] font-bold text-emerald-600 hover:text-emerald-700"
                    >
                        + Adjust Points
                    </button>
                </div>

                <div
                    class="max-h-80 divide-y divide-neutral-100 overflow-y-auto dark:divide-neutral-800"
                >
                    <div
                        v-for="tx in loyaltySummary.recent_transactions"
                        :key="tx.id"
                        class="flex items-center justify-between gap-3 py-2.5 text-xs"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                                :class="[
                                    tx.is_positive
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                ]"
                            >
                                <ArrowDownLeft
                                    v-if="tx.is_positive"
                                    class="h-4 w-4"
                                />
                                <ArrowUpRight v-else class="h-4 w-4" />
                            </div>
                            <div>
                                <p
                                    class="leading-tight font-semibold text-neutral-900 dark:text-white"
                                >
                                    {{ tx.description }}
                                </p>
                                <p class="text-[10px] text-neutral-400">
                                    {{ tx.date }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="shrink-0 font-black"
                            :class="[
                                tx.is_positive
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-neutral-900 dark:text-white',
                            ]"
                        >
                            {{ tx.is_positive ? `+${tx.points}` : tx.points }}
                            pts
                        </span>
                    </div>

                    <div
                        v-if="loyaltySummary.recent_transactions.length === 0"
                        class="py-6 text-center text-xs text-neutral-400"
                    >
                        No points history recorded yet.
                    </div>
                </div>
            </div>

            <!-- Claimed Vouchers -->
            <div
                class="space-y-3 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-neutral-900 dark:text-white"
                    >
                        <Ticket class="h-4 w-4 text-amber-500" /> Claimed
                        Digital Vouchers
                    </h3>
                </div>

                <div
                    class="max-h-80 divide-y divide-neutral-100 overflow-y-auto dark:divide-neutral-800"
                >
                    <div
                        v-for="c in claims"
                        :key="c.id"
                        class="flex items-center justify-between gap-3 py-2.5 text-xs"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-bold text-neutral-900 dark:text-white"
                                    >{{ c.reward_name }}</span
                                >
                                <span
                                    class="py-0.2 rounded px-1.5 text-[9px] font-black uppercase"
                                    :class="[
                                        c.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
                                    ]"
                                >
                                    {{ c.status }}
                                </span>
                            </div>
                            <p
                                class="mt-0.5 font-mono text-[11px] font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                {{ c.voucher_code }}
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <span
                                class="block text-[11px] font-bold text-neutral-400"
                                >{{ c.points_spent }} pts</span
                            >
                            <span class="text-[10px] text-neutral-400">{{
                                c.created_at
                            }}</span>
                        </div>
                    </div>

                    <div
                        v-if="!claims || claims.length === 0"
                        class="py-6 text-center text-xs text-neutral-400"
                    >
                        No vouchers claimed yet.
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking History Section -->
        <div
            class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div
                class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
            >
                <h3
                    class="flex items-center gap-2 text-sm font-bold text-neutral-900 dark:text-white"
                >
                    <CalendarDays class="h-4 w-4 text-emerald-600" /> Customer
                    Booking History
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr
                            class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                        >
                            <th class="px-3 py-2.5">Booking ID</th>
                            <th class="px-3 py-2.5">Court</th>
                            <th class="px-3 py-2.5">Date</th>
                            <th class="px-3 py-2.5">Time Slots</th>
                            <th class="px-3 py-2.5">Total Amount</th>
                            <th class="px-3 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-neutral-100 dark:divide-neutral-800"
                    >
                        <tr
                            v-for="b in bookings.data"
                            :key="b.id"
                            class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                        >
                            <td class="px-3 py-3 font-mono font-bold">
                                #{{ b.id }}
                            </td>
                            <td
                                class="px-3 py-3 font-medium text-neutral-900 dark:text-white"
                            >
                                {{ b.court?.name || 'N/A' }}
                            </td>
                            <td
                                class="px-3 py-3 text-neutral-600 dark:text-neutral-300"
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
                            <td class="px-3 py-3 font-semibold capitalize">
                                {{ b.status }}
                            </td>
                        </tr>

                        <tr v-if="bookings.data.length === 0">
                            <td
                                colspan="6"
                                class="py-8 text-center text-xs text-neutral-400"
                            >
                                No booking history recorded for this user.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Adjust Points Modal -->
    <Teleport to="body">
        <div
            v-if="showPointsModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            @click.self="showPointsModal = false"
        >
            <div
                class="w-full max-w-md space-y-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="rounded-xl bg-emerald-500/10 p-2 text-emerald-600"
                        >
                            <Sparkles class="h-5 w-5" />
                        </div>
                        <div>
                            <h2
                                class="text-base font-black text-neutral-900 dark:text-white"
                            >
                                Adjust Loyalty Points
                            </h2>
                            <p class="text-xs text-neutral-500">
                                {{ user.name }} (Current:
                                {{ loyaltySummary?.available_points ?? 0 }} pts)
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showPointsModal = false"
                        class="cursor-pointer text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitPoints" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label>Adjustment Action</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="pointsForm.action = 'add'"
                                class="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition-all"
                                :class="[
                                    pointsForm.action === 'add'
                                        ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm'
                                        : 'border-neutral-200 bg-neutral-50 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                                ]"
                            >
                                <span>+ Add Bonus Points</span>
                            </button>
                            <button
                                type="button"
                                @click="pointsForm.action = 'deduct'"
                                class="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition-all"
                                :class="[
                                    pointsForm.action === 'deduct'
                                        ? 'border-rose-600 bg-rose-600 text-white shadow-sm'
                                        : 'border-neutral-200 bg-neutral-50 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                                ]"
                            >
                                <span>- Deduct Points</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="points-amount-show">Points Amount</Label>
                        <Input
                            id="points-amount-show"
                            v-model.number="pointsForm.amount"
                            type="number"
                            min="1"
                            max="50000"
                            required
                        />
                        <InputError :message="pointsForm.errors.amount" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="points-reason-show"
                            >Reason / Memo (Required for Audit Trail)</Label
                        >
                        <Input
                            id="points-reason-show"
                            v-model="pointsForm.reason"
                            placeholder="e.g. Loyalty promotion bonus, special event reward, etc."
                            required
                        />
                        <InputError :message="pointsForm.errors.reason" />
                    </div>

                    <div
                        class="flex items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50 p-3 text-xs font-bold dark:border-neutral-800 dark:bg-neutral-800/50"
                    >
                        <span class="text-neutral-500"
                            >Calculated New Balance:</span
                        >
                        <span class="text-emerald-600 dark:text-emerald-400">
                            {{
                                pointsForm.action === 'add'
                                    ? (loyaltySummary?.available_points ?? 0) +
                                      (pointsForm.amount || 0)
                                    : (loyaltySummary?.available_points ?? 0) -
                                      (pointsForm.amount || 0)
                            }}
                            pts
                        </span>
                    </div>

                    <div
                        class="flex justify-end gap-2 border-t border-neutral-100 pt-2 dark:border-neutral-800"
                    >
                        <Button
                            variant="outline"
                            type="button"
                            @click="showPointsModal = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="pointsForm.processing"
                            class="bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            {{
                                pointsForm.processing
                                    ? 'Adjusting...'
                                    : 'Save Points'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
