<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Plus, ShieldAlert, X } from '@lucide/vue';
import { ref, computed } from 'vue';
import ReservationCalendar from '@/components/dashboard/ReservationCalendar.vue';

interface CourtItem {
    id: number;
    name: string;
}

interface BookingSlot {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    status: string;
    total_price: string;
}

interface UnavailabilitySlot {
    id: number;
    date: string;
    start_time?: string;
    end_time?: string;
    all_day: boolean;
    reason?: string;
}

const props = defineProps<{
    assignedCourts: CourtItem[];
    selectedCourt: CourtItem | null;
    currentMonth: string;
    bookings: BookingSlot[];
    unavailabilities: UnavailabilitySlot[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
            { title: 'Court Schedule & Maintenance', href: '/staff/schedules' },
        ],
    },
});

const isAddModalOpen = ref(false);

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
const canManageSchedule = computed(() => {
    return user.value?.is_super_admin || user.value?.is_admin;
});

const addForm = useForm({
    court_id: props.selectedCourt?.id || (props.assignedCourts[0]?.id ?? ''),
    date: '',
    start_time: '08:00',
    end_time: '12:00',
    all_day: true,
    reason: 'Maintenance',
});

function submitAdd() {
    if (!canManageSchedule.value) {
        return;
    }

    addForm.post('/staff/schedules', {
        onSuccess: () => {
            addForm.reset();
            isAddModalOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Court Schedules & Blackout Dates - Court Staff" />

    <div class="w-full space-y-6 p-6">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
                    Court Schedule & Unavailable Dates
                </h1>
                <p class="text-xs text-neutral-500">
                    View reservations calendar and block out dates for court
                    maintenance or holidays.
                </p>
            </div>

            <button
                v-if="canManageSchedule"
                @click="isAddModalOpen = true"
                class="flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
            >
                <Plus class="h-4 w-4" /> Block Unavailable Date/Slot
            </button>
        </div>

        <!-- Interactive Reservation Schedule Component -->
        <ReservationCalendar
            :bookings="bookings"
            :unavailabilities="unavailabilities"
        />

        <!-- Blackout Dates Management Table -->
        <div
            class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <h3
                class="flex items-center gap-2 text-sm font-bold text-neutral-900 dark:text-white"
            >
                <ShieldAlert class="h-4 w-4 text-rose-600" /> Active Blackout &
                Maintenance Entries
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr
                            class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                        >
                            <th class="px-3 py-2.5">Date</th>
                            <th class="px-3 py-2.5">Duration</th>
                            <th class="px-3 py-2.5">Reason</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-neutral-100 dark:divide-neutral-800"
                    >
                        <tr
                            v-for="u in unavailabilities"
                            :key="u.id"
                            class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                        >
                            <td
                                class="px-3 py-3 font-bold text-neutral-900 dark:text-white"
                            >
                                {{ u.date }}
                            </td>
                            <td
                                class="px-3 py-3 text-neutral-600 dark:text-neutral-300"
                            >
                                {{
                                    u.all_day
                                        ? 'Full Day Closed'
                                        : u.start_time + ' - ' + u.end_time
                                }}
                            </td>
                            <td class="px-3 py-3 text-neutral-500">
                                {{ u.reason || 'Maintenance' }}
                            </td>
                        </tr>

                        <tr v-if="unavailabilities.length === 0">
                            <td
                                colSpan="3"
                                class="py-6 text-center text-xs text-neutral-400"
                            >
                                No active blackout slots recorded.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Centered Modal for Adding Blackout Slot -->
    <Teleport to="body">
        <div
            v-if="isAddModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
            @click.self="isAddModalOpen = false"
        >
            <div
                class="my-8 w-full max-w-lg space-y-5 rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-base font-bold text-neutral-900 dark:text-white"
                        >
                            Block Out Court Date
                        </h3>
                        <p class="text-xs text-neutral-500">
                            Select court and date for maintenance or closure.
                        </p>
                    </div>
                    <button
                        @click="isAddModalOpen = false"
                        class="text-neutral-400 hover:text-neutral-600 dark:hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-4 text-xs">
                    <div class="space-y-3">
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Target Court *</label
                            >
                            <select
                                v-model="addForm.court_id"
                                required
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-rose-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
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

                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Blackout Date *</label
                            >
                            <input
                                v-model="addForm.date"
                                type="date"
                                required
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-rose-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Reason</label
                            >
                            <input
                                v-model="addForm.reason"
                                type="text"
                                placeholder="e.g. Court Resurfacing Maintenance"
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-rose-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="rounded-xl bg-neutral-100 px-4 py-2 font-medium text-neutral-700 transition-colors hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="rounded-xl bg-rose-600 px-5 py-2 font-semibold text-white shadow transition-colors hover:bg-rose-700"
                        >
                            Save Blackout Slot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
