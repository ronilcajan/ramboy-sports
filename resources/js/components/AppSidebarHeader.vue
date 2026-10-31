<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);
const { getInitials } = useInitials();
</script>

<template>
    <header
        class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 bg-background/95 px-4 backdrop-blur-md transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
    >
        <!-- Desktop Header View: Sidebar Toggle & Breadcrumbs -->
        <div class="hidden items-center gap-2 md:flex">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Mobile Header View: App Logo on left & Profile avatar link on right -->
        <div class="flex w-full items-center justify-between md:hidden">
            <Link
                :href="dashboard()"
                class="flex items-center gap-1.5 focus:outline-none"
            >
                <AppLogo />
            </Link>

            <Link
                href="/settings/profile"
                class="relative flex items-center gap-2 rounded-full p-1 transition-colors hover:bg-accent focus:outline-none"
                title="Profile Settings"
            >
                <Avatar
                    class="size-8 overflow-hidden rounded-full border border-sidebar-border/70 shadow-xs"
                >
                    <AvatarImage
                        v-if="auth?.user?.avatar"
                        :src="auth.user.avatar"
                        :alt="auth.user.name"
                    />
                    <AvatarFallback
                        class="rounded-full bg-emerald-600/10 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                    >
                        {{ getInitials(auth?.user?.name) }}
                    </AvatarFallback>
                </Avatar>
            </Link>
        </div>
    </header>
</template>
