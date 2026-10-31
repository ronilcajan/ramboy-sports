<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { LayoutGrid, CalendarDays, PlusCircle, Gift, User } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user as any);

// Only show floating bottom nav for Customer role (not for Super Admin / Admin / Staff who have their own dashboard interfaces)
const isCustomer = computed(() => {
    if (!user.value) {
        return false;
    }

    return (
        !user.value.is_super_admin &&
        !user.value.is_venue_admin &&
        !user.value.is_staff
    );
});

const navItems = [
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        isActive: (url: string) => url === '/dashboard' || url === '/',
    },
    {
        name: 'Bookings',
        href: '/my-bookings',
        icon: CalendarDays,
        isActive: (url: string) => url.startsWith('/my-bookings'),
    },
    {
        name: 'Rewards',
        href: '/customer/rewards',
        icon: Gift,
        isActive: (url: string) => url.startsWith('/customer/rewards'),
    },
    {
        name: 'Explore',
        href: '/courts',
        icon: PlusCircle,
        isActive: (url: string) =>
            url.startsWith('/courts') || url.startsWith('/venues'),
    },
    {
        name: 'Profile',
        href: '/settings/profile',
        icon: User,
        isActive: (url: string) => url.startsWith('/settings'),
    },
];
</script>

<template>
    <div
        v-if="isCustomer"
        class="fixed right-4 bottom-4 left-4 z-50 mx-auto flex max-w-md items-center justify-around rounded-full border border-neutral-800/80 bg-neutral-900/90 px-3 py-2 shadow-2xl backdrop-blur-xl transition-all duration-300 md:hidden dark:bg-neutral-950/95"
    >
        <Link
            v-for="item in navItems"
            :key="item.name"
            :href="item.href"
            class="group relative flex flex-col items-center justify-center rounded-full px-3 py-1.5 transition-all duration-200"
            :class="[
                item.isActive(currentUrl)
                    ? 'scale-105 bg-emerald-500/10 font-bold text-emerald-400'
                    : 'text-neutral-400 hover:bg-neutral-800/50 hover:text-neutral-200',
            ]"
        >
            <component
                :is="item.icon"
                class="h-5 w-5 transition-transform duration-200 group-hover:scale-110"
                :class="[
                    item.isActive(currentUrl)
                        ? 'text-emerald-400'
                        : 'text-neutral-400',
                ]"
            />
            <span class="mt-0.5 text-[10px] font-semibold tracking-tight">
                {{ item.name }}
            </span>

            <span
                v-if="item.isActive(currentUrl)"
                class="absolute -bottom-1 h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50"
            ></span>
        </Link>
    </div>
</template>
