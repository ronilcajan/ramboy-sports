<script setup lang="ts">
import { ChevronLeft, ChevronRight, X, ZoomIn } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        images: string[];
        initialIndex?: number;
        title?: string;
    }>(),
    {
        initialIndex: 0,
        title: 'Location Image Preview',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const currentIndex = ref(props.initialIndex);

watch(
    () => props.initialIndex,
    (newVal) => {
        currentIndex.value = newVal;
    },
);

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            currentIndex.value = props.initialIndex;

            if (typeof document !== 'undefined') {
                document.body.style.overflow = 'hidden';
            }
        } else {
            if (typeof document !== 'undefined') {
                document.body.style.overflow = '';
            }
        }
    },
);

const currentImage = computed(() => {
    if (props.images && props.images.length > 0) {
        return props.images[currentIndex.value] || props.images[0];
    }

    return '/images/hero_pickleball.png';
});

function prevImage() {
    if (props.images.length <= 1) {
        return;
    }

    currentIndex.value =
        (currentIndex.value - 1 + props.images.length) % props.images.length;
}

function nextImage() {
    if (props.images.length <= 1) {
        return;
    }

    currentIndex.value = (currentIndex.value + 1) % props.images.length;
}

function selectIndex(index: number) {
    currentIndex.value = index;
}

function handleKeydown(e: KeyboardEvent) {
    if (!props.isOpen) {
        return;
    }

    if (e.key === 'Escape') {
        emit('close');
    } else if (e.key === 'ArrowLeft') {
        prevImage();
    } else if (e.key === 'ArrowRight') {
        nextImage();
    }
}

// Teleport to <body> only after hydration to avoid an SSR mismatch
// (the server renders the teleport placeholder next to Inertia's data-page script)
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;

    if (typeof window !== 'undefined') {
        window.addEventListener('keydown', handleKeydown);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleKeydown);
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport v-if="isMounted" to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-lg select-none"
                @click.self="emit('close')"
            >
                <!-- Header Controls Bar -->
                <div
                    class="pointer-events-none absolute top-4 right-4 left-4 z-20 flex items-center justify-between"
                >
                    <div
                        class="pointer-events-auto flex items-center gap-2 rounded-full border border-white/10 bg-black/50 px-4 py-1.5 backdrop-blur-md"
                    >
                        <ZoomIn class="size-4 text-brand" />
                        <span
                            class="max-w-[200px] truncate text-xs font-bold tracking-wide text-white sm:max-w-md"
                        >
                            {{ title }}
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="pointer-events-auto flex size-10 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur-md transition-all hover:scale-105 hover:bg-white/20"
                        aria-label="Close image viewer"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <!-- Main Image Preview Container -->
                <div
                    class="relative flex max-h-[80vh] max-w-5xl items-center justify-center overflow-hidden"
                >
                    <img
                        :src="currentImage"
                        :alt="`${title} Preview`"
                        class="max-h-[75vh] max-w-full rounded-2xl border border-white/10 object-contain shadow-2xl transition-transform duration-300"
                    />

                    <!-- Previous Image Button -->
                    <button
                        v-if="images.length > 1"
                        type="button"
                        @click.stop="prevImage"
                        class="absolute top-1/2 left-2 flex size-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/60 text-white shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:bg-black/80 sm:left-4"
                        aria-label="Previous image"
                    >
                        <ChevronLeft class="size-6" />
                    </button>

                    <!-- Next Image Button -->
                    <button
                        v-if="images.length > 1"
                        type="button"
                        @click.stop="nextImage"
                        class="absolute top-1/2 right-2 flex size-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/60 text-white shadow-lg backdrop-blur-md transition-all hover:scale-110 hover:bg-black/80 sm:right-4"
                        aria-label="Next image"
                    >
                        <ChevronRight class="size-6" />
                    </button>
                </div>

                <!-- Footer Carousel Strip & Counter -->
                <div
                    class="absolute bottom-4 left-1/2 z-20 flex max-w-full -translate-x-1/2 flex-col items-center gap-2 px-4"
                >
                    <!-- Counter Pill -->
                    <span
                        v-if="images.length > 1"
                        class="rounded-full border border-white/10 bg-black/60 px-3 py-1 text-[11px] font-bold tracking-wider text-white/80 uppercase backdrop-blur-md"
                    >
                        {{ currentIndex + 1 }} / {{ images.length }}
                    </span>

                    <!-- Thumbnail Strip -->
                    <div
                        v-if="images.length > 1"
                        class="flex max-w-md items-center gap-2 overflow-x-auto p-1"
                    >
                        <button
                            v-for="(img, idx) in images"
                            :key="idx"
                            type="button"
                            @click.stop="selectIndex(idx)"
                            class="relative size-12 shrink-0 cursor-pointer overflow-hidden rounded-lg border-2 transition-all"
                            :class="[
                                currentIndex === idx
                                    ? 'scale-105 border-brand shadow-md shadow-brand/40'
                                    : 'border-white/20 opacity-50 hover:opacity-100',
                            ]"
                        >
                            <img
                                :src="img"
                                :alt="`Thumbnail ${idx + 1}`"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
