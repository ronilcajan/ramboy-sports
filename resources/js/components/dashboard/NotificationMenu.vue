<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Bell, Check, CheckCheck } from '@lucide/vue';
import { ref } from 'vue';

interface NotificationItem {
    id: string;
    data: {
        booking_id: number;
        court_id: number;
        court_name: string;
        customer_name: string;
        action: string;
        status: string;
        message: string;
    };
    created_at: string;
}

defineProps<{
    notifications: NotificationItem[];
}>();

const isOpen = ref(false);

const actionForm = useForm({});

function markAsRead(id: string) {
    actionForm.patch(`/staff/notifications/${id}/read`, {
        preserveScroll: true,
    });
}

function markAllAsRead() {
    actionForm.patch('/staff/notifications/read-all', {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            class="relative rounded-lg p-2 text-neutral-600 transition-colors hover:bg-neutral-100 focus:outline-none dark:text-neutral-300 dark:hover:bg-neutral-800"
            title="Notifications"
        >
            <Bell class="h-5 w-5" />
            <span
                v-if="notifications.length > 0"
                class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-bold text-white shadow-sm"
            >
                {{ notifications.length }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-xl sm:w-96 dark:border-neutral-800 dark:bg-neutral-900"
        >
            <div
                class="flex items-center justify-between border-b border-neutral-100 bg-neutral-50/50 p-3 dark:border-neutral-800 dark:bg-neutral-900/50"
            >
                <span
                    class="flex items-center gap-1.5 text-sm font-semibold text-neutral-900 dark:text-white"
                >
                    <Bell class="h-4 w-4 text-emerald-600" />
                    Staff Alerts
                </span>

                <button
                    v-if="notifications.length > 0"
                    @click="markAllAsRead"
                    class="flex items-center gap-1 text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400"
                >
                    <CheckCheck class="h-3.5 w-3.5" /> Mark all read
                </button>
            </div>

            <div
                class="max-h-80 divide-y divide-neutral-100 overflow-y-auto dark:divide-neutral-800"
            >
                <div
                    v-for="item in notifications"
                    :key="item.id"
                    class="flex items-start justify-between gap-3 p-3 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/50"
                >
                    <div class="space-y-1 text-xs">
                        <p class="font-medium text-neutral-900 dark:text-white">
                            {{ item.data.message }}
                        </p>
                        <span class="text-[10px] text-neutral-400">
                            Court: {{ item.data.court_name }}
                        </span>
                    </div>

                    <button
                        @click="markAsRead(item.id)"
                        class="p-1 text-neutral-400 transition-colors hover:text-emerald-600 dark:hover:text-emerald-400"
                        title="Mark read"
                    >
                        <Check class="h-4 w-4" />
                    </button>
                </div>

                <div
                    v-if="notifications.length === 0"
                    class="p-6 text-center text-xs text-neutral-400"
                >
                    No new unread notifications.
                </div>
            </div>
        </div>
    </div>
</template>
