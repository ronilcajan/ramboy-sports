<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Gift,
    Plus,
    Search,
    Pencil,
    Trash2,
    X,
    Sparkles,
    Ticket,
    CupSoda,
    Shirt,
    Crown,
    Percent,
    Dumbbell,
    Building,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface RewardItem {
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
    is_active: boolean;
    claims_count: number;
    venue_id: number | null;
    venue: { id: number; name: string } | null;
    created_at: string;
}

interface PaginatedRewards {
    data: RewardItem[];
    current_page: number;
    last_page: number;
}

interface VenueOption {
    id: number;
    name: string;
}

interface CategoryOption {
    value: string;
    label: string;
}

const props = defineProps<{
    rewards: PaginatedRewards;
    venues: VenueOption[];
    metrics: {
        total_rewards: number;
        active_rewards: number;
        total_claims: number;
        total_points_redeemed: number;
    };
    filters: {
        search?: string;
        category?: string;
        status?: string;
    };
    categories: CategoryOption[];
    icons: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
            { title: 'Freebies & Rewards', href: '/admin/rewards' },
        ],
    },
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'all');
const status = ref(props.filters.status || 'all');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingReward = ref<RewardItem | null>(null);
const deletingReward = ref<RewardItem | null>(null);

const createForm = useForm({
    name: '',
    description: '',
    category: 'drink',
    points_cost: 100,
    stock: null as number | null,
    is_unlimited_stock: true,
    badge_text: '',
    icon: 'Gift',
    terms: '',
    venue_id: null as number | null,
    is_active: true,
});

const editForm = useForm({
    name: '',
    description: '',
    category: 'drink',
    points_cost: 100,
    stock: null as number | null,
    is_unlimited_stock: true,
    badge_text: '',
    icon: 'Gift',
    terms: '',
    venue_id: null as number | null,
    is_active: true,
});

function applyFilters() {
    router.get(
        '/admin/rewards',
        {
            search: search.value,
            category: category.value,
            status: status.value,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = '';
    category.value = 'all';
    status.value = 'all';
    applyFilters();
}

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

function openCreateModal() {
    createForm.reset();
    createForm.is_unlimited_stock = true;
    createForm.stock = null;
    createForm.clearErrors();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.stock = createForm.is_unlimited_stock ? null : createForm.stock;
    createForm.post('/admin/rewards', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
}

function openEditModal(reward: RewardItem) {
    editingReward.value = reward;
    editForm.name = reward.name;
    editForm.description = reward.description || '';
    editForm.category = reward.category;
    editForm.points_cost = reward.points_cost;
    editForm.stock = reward.stock;
    editForm.is_unlimited_stock = reward.stock === null;
    editForm.badge_text = reward.badge_text || '';
    editForm.icon = reward.icon || 'Gift';
    editForm.terms = reward.terms || '';
    editForm.venue_id = reward.venue_id;
    editForm.is_active = reward.is_active;
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingReward.value) {
        return;
    }

    editForm.stock = editForm.is_unlimited_stock ? null : editForm.stock;
    editForm.put(`/admin/rewards/${editingReward.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingReward.value = null;
        },
        preserveScroll: true,
    });
}

function toggleActiveStatus(reward: RewardItem) {
    router.patch(
        `/admin/rewards/${reward.id}/toggle-active`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function confirmDelete(reward: RewardItem) {
    deletingReward.value = reward;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!deletingReward.value) {
        return;
    }

    router.delete(`/admin/rewards/${deletingReward.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingReward.value = null;
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Freebies & Rewards Management" />

    <div class="w-full space-y-6 p-6 pb-16">
        <!-- Header & Action -->
        <div
            class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-emerald-500/10 bg-gradient-to-r from-emerald-600/10 via-teal-600/5 to-transparent p-6 md:flex-row md:items-center dark:border-emerald-500/5"
        >
            <div>
                <h1
                    class="text-2xl font-black tracking-tight text-neutral-900 dark:text-white"
                >
                    Freebies & Rewards Management
                </h1>
                <p class="mt-1 text-xs text-neutral-500">
                    Create, update, and manage customer loyalty freebies,
                    vouchers, stock levels, and point costs.
                </p>
            </div>
            <button
                type="button"
                @click="openCreateModal"
                class="flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-emerald-600/20 transition-all hover:translate-y-[-1px] hover:bg-emerald-700"
            >
                <Plus class="h-4 w-4" /> Add New Freebie / Reward
            </button>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Total Freebies</span
                >
                <span
                    class="mt-1 block text-2xl font-black text-neutral-900 dark:text-white"
                    >{{ metrics.total_rewards }}</span
                >
                <span class="mt-1 block text-[10px] text-neutral-500"
                    >{{ metrics.active_rewards }} currently active</span
                >
            </div>

            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Active Catalog</span
                >
                <span class="mt-1 block text-2xl font-black text-emerald-600">{{
                    metrics.active_rewards
                }}</span>
                <span
                    class="mt-1 block text-[10px] font-medium text-emerald-600"
                    >Available for customer claims</span
                >
            </div>

            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Total Vouchers Claimed</span
                >
                <span class="mt-1 block text-2xl font-black text-amber-600">{{
                    metrics.total_claims
                }}</span>
                <span class="mt-1 block text-[10px] text-neutral-500"
                    >Redemption passes generated</span
                >
            </div>

            <div
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <span
                    class="block text-[11px] font-bold tracking-wider text-neutral-400 uppercase"
                    >Points Redeemed</span
                >
                <span
                    class="mt-1 block text-2xl font-black text-neutral-900 dark:text-white"
                    >{{ metrics.total_points_redeemed }}</span
                >
                <span class="mt-1 block text-[10px] text-neutral-500"
                    >Total customer points spent</span
                >
            </div>
        </div>

        <!-- Filter Bar -->
        <div
            class="flex flex-col items-stretch justify-between gap-4 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm md:flex-row md:items-center dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div
                class="flex flex-1 flex-col items-stretch gap-3 sm:flex-row sm:items-center"
            >
                <div class="relative max-w-sm flex-1">
                    <Search
                        class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-neutral-400"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search freebies by name or description..."
                        class="w-full rounded-xl border border-neutral-200 bg-neutral-50 py-2 pr-3.5 pl-9 text-xs text-neutral-900 placeholder-neutral-400 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <select
                    v-model="category"
                    @change="applyFilters"
                    class="rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                >
                    <option value="all">All Categories</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.value"
                        :value="cat.value"
                    >
                        {{ cat.label }}
                    </option>
                </select>

                <select
                    v-model="status"
                    @change="applyFilters"
                    class="rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                >
                    <option value="all">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>

                <button
                    v-if="search || category !== 'all' || status !== 'all'"
                    type="button"
                    @click="clearFilters"
                    class="cursor-pointer px-2 py-1 text-xs font-bold text-neutral-500 hover:text-neutral-900 dark:hover:text-white"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Rewards Data Table -->
        <div
            class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr
                            class="border-b border-neutral-100 bg-neutral-50/50 font-bold tracking-wider text-neutral-400 uppercase dark:border-neutral-800 dark:bg-neutral-800/30"
                        >
                            <th class="px-4 py-3.5">Freebie / Reward</th>
                            <th class="px-4 py-3.5">Category</th>
                            <th class="px-4 py-3.5">Points Cost</th>
                            <th class="px-4 py-3.5">Stock</th>
                            <th class="px-4 py-3.5">Target Location</th>
                            <th class="px-4 py-3.5">Claimed</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-neutral-100 dark:divide-neutral-800"
                    >
                        <tr
                            v-for="reward in rewards.data"
                            :key="reward.id"
                            class="transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40"
                        >
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                                    >
                                        <component
                                            :is="getIconComponent(reward.icon)"
                                            class="h-5 w-5"
                                        />
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-bold text-neutral-900 dark:text-white"
                                                >{{ reward.name }}</span
                                            >
                                            <span
                                                v-if="reward.badge_text"
                                                class="py-0.2 rounded bg-emerald-500/10 px-1.5 text-[9px] font-black text-emerald-600 uppercase dark:text-emerald-400"
                                            >
                                                {{ reward.badge_text }}
                                            </span>
                                        </div>
                                        <p
                                            class="line-clamp-1 max-w-xs text-[11px] text-neutral-400"
                                        >
                                            {{
                                                reward.description ||
                                                'No description provided.'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td
                                class="px-4 py-3.5 font-semibold text-neutral-600 capitalize dark:text-neutral-300"
                            >
                                {{ reward.category }}
                            </td>

                            <td class="px-4 py-3.5">
                                <span
                                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-500/10 px-2.5 py-1 text-xs font-black text-emerald-600 dark:text-emerald-400"
                                >
                                    <Sparkles
                                        class="h-3.5 w-3.5 text-amber-500"
                                    />
                                    {{ reward.points_cost }} pts
                                </span>
                            </td>

                            <td
                                class="px-4 py-3.5 font-medium text-neutral-600 dark:text-neutral-300"
                            >
                                <span
                                    v-if="reward.stock === null"
                                    class="font-bold text-neutral-400"
                                    >Unlimited</span
                                >
                                <span
                                    v-else-if="reward.stock > 0"
                                    class="font-bold text-emerald-600 dark:text-emerald-400"
                                    >{{ reward.stock }} left</span
                                >
                                <span v-else class="font-bold text-rose-600"
                                    >Out of stock</span
                                >
                            </td>

                            <td class="px-4 py-3.5 text-neutral-500">
                                <span
                                    v-if="reward.venue"
                                    class="flex items-center gap-1"
                                >
                                    <Building class="h-3.5 w-3.5" />
                                    {{ reward.venue.name }}
                                </span>
                                <span
                                    v-else
                                    class="font-semibold text-neutral-400"
                                    >Universal (All Locations)</span
                                >
                            </td>

                            <td
                                class="px-4 py-3.5 font-bold text-neutral-900 dark:text-white"
                            >
                                {{ reward.claims_count }} times
                            </td>

                            <td class="px-4 py-3.5">
                                <button
                                    type="button"
                                    @click="toggleActiveStatus(reward)"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase transition-colors"
                                    :class="[
                                        reward.is_active
                                            ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400',
                                    ]"
                                    title="Click to toggle active status"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            reward.is_active
                                                ? 'bg-emerald-500'
                                                : 'bg-neutral-400'
                                        "
                                    ></span>
                                    <span>{{
                                        reward.is_active ? 'Active' : 'Disabled'
                                    }}</span>
                                </button>
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <button
                                        type="button"
                                        @click="openEditModal(reward)"
                                        class="cursor-pointer rounded-lg p-1.5 text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-800 dark:hover:text-white"
                                        title="Edit Freebie"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>

                                    <button
                                        type="button"
                                        @click="confirmDelete(reward)"
                                        class="cursor-pointer rounded-lg p-1.5 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950/40"
                                        title="Delete Freebie"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="rewards.data.length === 0">
                            <td
                                colspan="8"
                                class="py-12 text-center text-xs text-neutral-400"
                            >
                                No freebies or rewards found matching your
                                criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="rewards.last_page > 1"
                class="flex items-center justify-between border-t border-neutral-100 p-4 text-xs text-neutral-500 dark:border-neutral-800"
            >
                <span
                    >Page {{ rewards.current_page }} of
                    {{ rewards.last_page }}</span
                >
                <div class="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="rewards.current_page <= 1"
                        @click="
                            router.get(
                                `/admin/rewards?page=${rewards.current_page - 1}`,
                                {},
                                { preserveState: true },
                            )
                        "
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="rewards.current_page >= rewards.last_page"
                        @click="
                            router.get(
                                `/admin/rewards?page=${rewards.current_page + 1}`,
                                {},
                                { preserveState: true },
                            )
                        "
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CREATE REWARD MODAL                       -->
        <!-- ========================================== -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/75 p-4 backdrop-blur-sm"
        >
            <div
                class="relative max-h-[90vh] w-full max-w-lg overflow-hidden overflow-y-auto rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <h3
                        class="text-lg font-black text-neutral-900 dark:text-white"
                    >
                        Create New Freebie / Reward
                    </h3>
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="cursor-pointer p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 pt-4">
                    <div class="space-y-1.5">
                        <Label for="create-name"
                            >Reward Name
                            <span class="text-rose-500">*</span></Label
                        >
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="e.g. Ice-Cold Electrolyte Sports Drink"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="create-category">Category</Label>
                            <select
                                id="create-category"
                                v-model="createForm.category"
                                class="w-full rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                            >
                                <option
                                    v-for="c in categories"
                                    :key="c.value"
                                    :value="c.value"
                                >
                                    {{ c.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.category" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="create-icon">Icon</Label>
                            <select
                                id="create-icon"
                                v-model="createForm.icon"
                                class="w-full rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                            >
                                <option
                                    v-for="ic in icons"
                                    :key="ic"
                                    :value="ic"
                                >
                                    {{ ic }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="create-points"
                                >Points Cost
                                <span class="text-rose-500">*</span></Label
                            >
                            <Input
                                id="create-points"
                                v-model.number="createForm.points_cost"
                                type="number"
                                min="1"
                                required
                            />
                            <InputError
                                :message="createForm.errors.points_cost"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="create-badge"
                                >Badge Text (Optional)</Label
                            >
                            <Input
                                id="create-badge"
                                v-model="createForm.badge_text"
                                placeholder="e.g. Popular / VIP"
                            />
                            <InputError
                                :message="createForm.errors.badge_text"
                            />
                        </div>
                    </div>

                    <!-- Stock Setting -->
                    <div
                        class="space-y-2 rounded-xl border border-neutral-100 bg-neutral-50 p-3.5 dark:border-neutral-800 dark:bg-neutral-800/50"
                    >
                        <div class="flex items-center justify-between">
                            <Label class="text-xs font-bold"
                                >Stock Availability</Label
                            >
                            <label
                                class="flex cursor-pointer items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300"
                            >
                                <input
                                    type="checkbox"
                                    v-model="createForm.is_unlimited_stock"
                                    class="rounded text-emerald-600"
                                />
                                <span>Unlimited Stock</span>
                            </label>
                        </div>

                        <div v-if="!createForm.is_unlimited_stock" class="pt-1">
                            <Input
                                :model-value="createForm.stock ?? undefined"
                                @update:model-value="
                                    (val) =>
                                        (createForm.stock =
                                            val !== undefined && val !== ''
                                                ? Number(val)
                                                : null)
                                "
                                type="number"
                                min="0"
                                placeholder="Available Quantity"
                            />
                            <InputError :message="createForm.errors.stock" />
                        </div>
                    </div>

                    <!-- Venue Scope -->
                    <div v-if="venues.length > 1" class="space-y-1.5">
                        <Label for="create-venue">Target Location Scope</Label>
                        <select
                            id="create-venue"
                            v-model="createForm.venue_id"
                            class="w-full rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                        >
                            <option :value="null">
                                Universal (All Locations)
                            </option>
                            <option
                                v-for="v in venues"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="create-description">Description</Label>
                        <textarea
                            id="create-description"
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Briefly describe what the customer receives when claiming this reward..."
                            class="w-full rounded-xl border border-neutral-200 bg-white p-3 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="create-terms"
                            >Terms & Redemption Instructions</Label
                        >
                        <textarea
                            id="create-terms"
                            v-model="createForm.terms"
                            rows="2"
                            placeholder="e.g. Show voucher to front desk counter upon arrival. Valid for 30 days."
                            class="w-full rounded-xl border border-neutral-200 bg-white p-3 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                        ></textarea>
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-2 pt-1 text-xs text-neutral-700 dark:text-neutral-300"
                    >
                        <input
                            type="checkbox"
                            v-model="createForm.is_active"
                            class="rounded text-emerald-600"
                        />
                        <span class="font-bold"
                            >Publish to customer catalog immediately</span
                        >
                    </label>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-neutral-100 pt-4 dark:border-neutral-800"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            @click="showCreateModal = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            class="bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            {{
                                createForm.processing
                                    ? 'Creating...'
                                    : 'Save & Publish'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- EDIT REWARD MODAL                         -->
        <!-- ========================================== -->
        <div
            v-if="showEditModal && editingReward"
            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/75 p-4 backdrop-blur-sm"
        >
            <div
                class="relative max-h-[90vh] w-full max-w-lg overflow-hidden overflow-y-auto rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-3 dark:border-neutral-800"
                >
                    <h3
                        class="text-lg font-black text-neutral-900 dark:text-white"
                    >
                        Edit Freebie / Reward
                    </h3>
                    <button
                        type="button"
                        @click="showEditModal = false"
                        class="cursor-pointer p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 pt-4">
                    <div class="space-y-1.5">
                        <Label for="edit-name"
                            >Reward Name
                            <span class="text-rose-500">*</span></Label
                        >
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            required
                        />
                        <InputError :message="editForm.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-category">Category</Label>
                            <select
                                id="edit-category"
                                v-model="editForm.category"
                                class="w-full rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                            >
                                <option
                                    v-for="c in categories"
                                    :key="c.value"
                                    :value="c.value"
                                >
                                    {{ c.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.category" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-icon">Icon</Label>
                            <select
                                id="edit-icon"
                                v-model="editForm.icon"
                                class="w-full rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                            >
                                <option
                                    v-for="ic in icons"
                                    :key="ic"
                                    :value="ic"
                                >
                                    {{ ic }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-points"
                                >Points Cost
                                <span class="text-rose-500">*</span></Label
                            >
                            <Input
                                id="edit-points"
                                v-model.number="editForm.points_cost"
                                type="number"
                                min="1"
                                required
                            />
                            <InputError
                                :message="editForm.errors.points_cost"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-badge">Badge Text</Label>
                            <Input
                                id="edit-badge"
                                v-model="editForm.badge_text"
                            />
                            <InputError :message="editForm.errors.badge_text" />
                        </div>
                    </div>

                    <!-- Stock Setting -->
                    <div
                        class="space-y-2 rounded-xl border border-neutral-100 bg-neutral-50 p-3.5 dark:border-neutral-800 dark:bg-neutral-800/50"
                    >
                        <div class="flex items-center justify-between">
                            <Label class="text-xs font-bold"
                                >Stock Availability</Label
                            >
                            <label
                                class="flex cursor-pointer items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300"
                            >
                                <input
                                    type="checkbox"
                                    v-model="editForm.is_unlimited_stock"
                                    class="rounded text-emerald-600"
                                />
                                <span>Unlimited Stock</span>
                            </label>
                        </div>

                        <div v-if="!editForm.is_unlimited_stock" class="pt-1">
                            <Input
                                :model-value="editForm.stock ?? undefined"
                                @update:model-value="
                                    (val) =>
                                        (editForm.stock =
                                            val !== undefined && val !== ''
                                                ? Number(val)
                                                : null)
                                "
                                type="number"
                                min="0"
                            />
                            <InputError :message="editForm.errors.stock" />
                        </div>
                    </div>

                    <!-- Venue Scope -->
                    <div v-if="venues.length > 1" class="space-y-1.5">
                        <Label for="edit-venue">Target Location Scope</Label>
                        <select
                            id="edit-venue"
                            v-model="editForm.venue_id"
                            class="w-full rounded-xl border border-neutral-200 bg-white px-3.5 py-2 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                        >
                            <option :value="null">
                                Universal (All Locations)
                            </option>
                            <option
                                v-for="v in venues"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-description">Description</Label>
                        <textarea
                            id="edit-description"
                            v-model="editForm.description"
                            rows="2"
                            class="w-full rounded-xl border border-neutral-200 bg-white p-3 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-terms"
                            >Terms & Redemption Instructions</Label
                        >
                        <textarea
                            id="edit-terms"
                            v-model="editForm.terms"
                            rows="2"
                            class="w-full rounded-xl border border-neutral-200 bg-white p-3 text-xs text-neutral-900 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                        ></textarea>
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-2 pt-1 text-xs text-neutral-700 dark:text-neutral-300"
                    >
                        <input
                            type="checkbox"
                            v-model="editForm.is_active"
                            class="rounded text-emerald-600"
                        />
                        <span class="font-bold"
                            >Reward is active and claimable</span
                        >
                    </label>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-neutral-100 pt-4 dark:border-neutral-800"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            @click="showEditModal = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            {{
                                editForm.processing
                                    ? 'Saving...'
                                    : 'Update Freebie'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- DELETE CONFIRMATION MODAL                  -->
        <!-- ========================================== -->
        <div
            v-if="showDeleteModal && deletingReward"
            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/75 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-sm space-y-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/50"
                >
                    <Trash2 class="h-6 w-6" />
                </div>

                <div class="space-y-1 text-center">
                    <h3
                        class="text-base font-black text-neutral-900 dark:text-white"
                    >
                        Delete Freebie?
                    </h3>
                    <p class="text-xs text-neutral-500">
                        Are you sure you want to delete
                        <strong class="text-neutral-900 dark:text-white">{{
                            deletingReward.name
                        }}</strong
                        >? This action cannot be undone.
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <Button
                        variant="outline"
                        class="flex-1"
                        @click="showDeleteModal = false"
                        >Cancel</Button
                    >
                    <Button
                        variant="destructive"
                        class="flex-1 bg-rose-600 text-white hover:bg-rose-700"
                        @click="executeDelete"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
