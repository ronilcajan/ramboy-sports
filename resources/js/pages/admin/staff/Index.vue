<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';

interface StaffUser {
    id: number;
    name: string;
    email: string;
    assigned_courts: { id: number; name: string }[];
}

interface CourtItem {
    id: number;
    name: string;
    sport_type: string;
}

defineProps<{
    staffMembers: StaffUser[];
    courts: CourtItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
            { title: 'Court Staff Accounts', href: '/admin/staff' },
        ],
    },
});

const isCreateModalOpen = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    court_ids: [] as number[],
});

function submitCreate() {
    createForm.post('/admin/staff', {
        onSuccess: () => {
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

const editForm = useForm({
    name: '',
    email: '',
    court_ids: [] as number[],
});

const editingUserId = ref<number | null>(null);

function startEditing(staff: StaffUser) {
    editingUserId.value = staff.id;
    editForm.name = staff.name;
    editForm.email = staff.email;
    editForm.court_ids = staff.assigned_courts.map((c) => c.id);
}

function submitUpdate(staffId: number) {
    editForm.put(`/admin/staff/${staffId}`, {
        onSuccess: () => {
            editingUserId.value = null;
        },
    });
}

const deleteForm = useForm({});

function deleteStaff(staffId: number) {
    if (confirm('Are you sure you want to remove this staff account?')) {
        deleteForm.delete(`/admin/staff/${staffId}`, {
            preserveScroll: true,
        });
    }
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}
</script>

<template>
    <Head title="Court Staff Management - Super Admin" />

    <div class="w-full space-y-6 p-6">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">
                    Court Staff Accounts & Assignments
                </h1>
                <p class="text-xs text-neutral-500">
                    Create staff user accounts and assign them to specific
                    courts.
                </p>
            </div>

            <button
                @click="isCreateModalOpen = true"
                class="flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
            >
                <Plus class="h-4 w-4" /> Create New Staff Member
            </button>
        </div>

        <!-- Staff List Table -->
        <div
            class="overflow-x-auto rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
        >
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr
                        class="border-b border-neutral-100 font-semibold text-neutral-400 uppercase dark:border-neutral-800"
                    >
                        <th class="px-3 py-3">Staff Name</th>
                        <th class="px-3 py-3">Email Address</th>
                        <th class="px-3 py-3">Assigned Courts</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-neutral-100 dark:divide-neutral-800"
                >
                    <tr
                        v-for="staff in staffMembers"
                        :key="staff.id"
                        class="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                    >
                        <td
                            class="px-3 py-3 font-bold text-neutral-900 dark:text-white"
                        >
                            <div
                                v-if="editingUserId === staff.id"
                                class="space-y-1"
                            >
                                <input
                                    v-model="editForm.name"
                                    class="rounded border border-neutral-300 p-1 text-xs dark:border-neutral-700 dark:bg-neutral-800"
                                />
                            </div>
                            <div v-else class="flex items-center gap-2.5">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400"
                                >
                                    {{ initials(staff.name) }}
                                </span>
                                <span>{{ staff.name }}</span>
                            </div>
                        </td>

                        <td
                            class="px-3 py-3 text-neutral-600 dark:text-neutral-300"
                        >
                            <div v-if="editingUserId === staff.id">
                                <input
                                    v-model="editForm.email"
                                    class="rounded border border-neutral-300 p-1 text-xs dark:border-neutral-700 dark:bg-neutral-800"
                                />
                            </div>
                            <span v-else>{{ staff.email }}</span>
                        </td>

                        <td class="px-3 py-3">
                            <div
                                v-if="editingUserId === staff.id"
                                class="flex flex-wrap gap-2"
                            >
                                <label
                                    v-for="c in courts"
                                    :key="c.id"
                                    class="flex cursor-pointer items-center gap-1"
                                >
                                    <input
                                        type="checkbox"
                                        :value="c.id"
                                        v-model="editForm.court_ids"
                                    />
                                    <span class="text-[11px]">{{
                                        c.name
                                    }}</span>
                                </label>
                            </div>

                            <div v-else class="flex flex-wrap gap-1">
                                <span
                                    v-for="court in staff.assigned_courts"
                                    :key="court.id"
                                    class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                >
                                    {{ court.name }}
                                </span>
                                <span
                                    v-if="staff.assigned_courts.length === 0"
                                    class="text-neutral-400 italic"
                                    >No court assigned</span
                                >
                            </div>
                        </td>

                        <td class="px-3 py-3 text-right">
                            <div
                                v-if="editingUserId === staff.id"
                                class="flex items-center justify-end gap-2"
                            >
                                <button
                                    @click="submitUpdate(staff.id)"
                                    class="rounded bg-emerald-600 px-2.5 py-1 text-xs text-white"
                                >
                                    Save
                                </button>
                                <button
                                    @click="editingUserId = null"
                                    class="rounded bg-neutral-200 px-2.5 py-1 text-xs text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                                >
                                    Cancel
                                </button>
                            </div>

                            <div
                                v-else
                                class="flex items-center justify-end gap-2"
                            >
                                <button
                                    @click="startEditing(staff)"
                                    class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 px-2.5 py-1 text-xs font-semibold text-neutral-700 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:border-neutral-700 dark:text-neutral-300 dark:hover:text-emerald-400"
                                >
                                    <Pencil class="h-3.5 w-3.5" /> Edit Courts
                                </button>
                                <button
                                    @click="deleteStaff(staff.id)"
                                    class="p-1 text-rose-400 hover:text-rose-600"
                                    title="Remove staff"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="staffMembers.length === 0">
                        <td
                            colSpan="4"
                            class="py-8 text-center text-xs text-neutral-400"
                        >
                            No staff user accounts found. Click "Create New
                            Staff Member" above.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Centered Create Staff Member Modal -->
    <Teleport to="body">
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
            @click.self="isCreateModalOpen = false"
        >
            <div
                class="my-8 w-full max-w-lg space-y-5 rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-base font-bold text-neutral-900 dark:text-white"
                        >
                            New Court Staff Credentials
                        </h3>
                        <p class="text-xs text-neutral-500">
                            Create a staff account and assign court monitoring
                            duties.
                        </p>
                    </div>
                    <button
                        @click="isCreateModalOpen = false"
                        class="text-neutral-400 hover:text-neutral-600 dark:hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div class="space-y-3">
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Full Name *</label
                            >
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="e.g. John Doe"
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Email Address *</label
                            >
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                placeholder="staff@example.com"
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block font-medium text-neutral-700 dark:text-neutral-300"
                                >Password *</label
                            >
                            <input
                                v-model="createForm.password"
                                type="password"
                                required
                                class="w-full rounded-xl border border-neutral-300 p-2.5 text-neutral-900 focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-2 block font-medium text-neutral-700 dark:text-neutral-300"
                            >Assign Courts</label
                        >
                        <div
                            class="flex max-h-40 flex-wrap gap-2 overflow-y-auto p-1"
                        >
                            <label
                                v-for="c in courts"
                                :key="c.id"
                                class="flex cursor-pointer items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 transition-colors hover:border-emerald-500 dark:border-neutral-700 dark:bg-neutral-800"
                            >
                                <input
                                    type="checkbox"
                                    :value="c.id"
                                    v-model="createForm.court_ids"
                                    class="rounded text-emerald-600 focus:ring-emerald-500"
                                />
                                <span
                                    class="font-medium text-neutral-900 dark:text-white"
                                    >{{ c.name }}</span
                                >
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="rounded-xl bg-neutral-100 px-4 py-2 font-medium text-neutral-700 transition-colors hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="rounded-xl bg-emerald-600 px-5 py-2 font-semibold text-white shadow transition-colors hover:bg-emerald-700"
                        >
                            Save Staff Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
