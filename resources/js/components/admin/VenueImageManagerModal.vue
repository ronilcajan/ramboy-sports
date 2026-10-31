<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    UploadCloud,
    Star,
    Trash2,
    X,
    Image as ImageIcon,
    CheckCircle2,
} from '@lucide/vue';
import { ref, watch, onUnmounted } from 'vue';
import InputError from '@/components/InputError.vue';

export interface VenueImageItem {
    id: number;
    path: string;
    url: string;
    is_primary: boolean;
    sort_order?: number;
}

export interface VenueTarget {
    id: number;
    name: string;
    images?: VenueImageItem[];
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        venue: VenueTarget | null;
        canDelete?: boolean;
    }>(),
    {
        canDelete: true,
    },
);

const emit = defineEmits(['close']);

const uploadForm = useForm({
    image: null as File | null,
    is_primary: false,
});

const previewUrl = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        setFile(target.files[0]);
    }
}

function setFile(file: File) {
    if (file.size > 5 * 1024 * 1024) {
        alert('File size exceeds 5MB limit. Please choose a smaller image.');

        return;
    }

    uploadForm.image = file;

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = URL.createObjectURL(file);
}

function handleDrop(event: DragEvent) {
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        setFile(event.dataTransfer.files[0]);
    }
}

function clearSelectedFile() {
    uploadForm.image = null;

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submitUpload() {
    if (!uploadForm.image || !props.venue) {
        return;
    }

    uploadForm.post(`/admin/venues/${props.venue.id}/images`, {
        preserveScroll: true,
        onSuccess: () => {
            clearSelectedFile();
        },
    });
}

function setPrimaryImage(image: VenueImageItem) {
    if (!props.venue) {
        return;
    }

    const actionForm = useForm({});
    actionForm.patch(
        `/admin/venues/${props.venue.id}/images/${image.id}/primary`,
        {
            preserveScroll: true,
        },
    );
}

function deleteImage(image: VenueImageItem) {
    if (!props.venue || !props.canDelete) {
        return;
    }

    if (!confirm('Are you sure you want to delete this venue photo?')) {
        return;
    }

    const actionForm = useForm({});
    actionForm.delete(`/admin/venues/${props.venue.id}/images/${image.id}`, {
        preserveScroll: true,
    });
}

watch(
    () => props.isOpen,
    (val) => {
        if (!val) {
            clearSelectedFile();
        }
    },
);

onUnmounted(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/60 p-4 backdrop-blur-sm"
    >
        <div
            class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-neutral-100 bg-neutral-50/50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-900/50"
            >
                <div>
                    <h3
                        class="flex items-center gap-2 text-lg font-black tracking-tight text-neutral-900 dark:text-white"
                    >
                        <ImageIcon class="h-5 w-5 text-emerald-600" />
                        Location Photo Gallery: {{ venue?.name }}
                    </h3>
                    <p class="text-xs text-neutral-500">
                        Add as many location photos as you like. The primary
                        photo is used as the location's hero image.
                    </p>
                </div>
                <button
                    @click="emit('close')"
                    class="rounded-full p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-800 dark:hover:text-white"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-6">
                <!-- Upload New Photo -->
                <div
                    class="dark:bg-neutral-850 space-y-4 rounded-2xl border border-dashed border-neutral-300 bg-neutral-50/50 p-5 dark:border-neutral-700"
                >
                    <h4
                        class="text-xs font-bold tracking-wider text-neutral-400 uppercase"
                    >
                        Upload New Photo
                    </h4>

                    <div
                        @dragover.prevent
                        @drop.prevent="handleDrop"
                        class="cursor-pointer rounded-xl border-2 border-dashed border-neutral-200 bg-white p-4 text-center transition-colors hover:border-emerald-500 dark:border-neutral-700 dark:bg-neutral-900"
                        @click="fileInput?.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            class="hidden"
                            @change="onFileSelect"
                        />

                        <div
                            v-if="!previewUrl"
                            class="flex flex-col items-center justify-center space-y-1 py-3"
                        >
                            <UploadCloud class="h-8 w-8 text-neutral-400" />
                            <span
                                class="text-xs font-bold text-neutral-700 dark:text-neutral-300"
                                >Click or drag & drop image to preview</span
                            >
                            <span class="text-[10px] text-neutral-400"
                                >Supports JPG, PNG, WEBP up to 5MB</span
                            >
                        </div>

                        <div
                            v-else
                            class="relative flex flex-col items-center justify-center"
                        >
                            <img
                                :src="previewUrl"
                                alt="Preview"
                                class="max-h-48 rounded-lg border border-neutral-200 object-contain dark:border-neutral-700"
                            />
                            <button
                                type="button"
                                @click.stop="clearSelectedFile"
                                class="absolute top-1 right-1 rounded-full bg-neutral-900/80 p-1 text-white transition-colors hover:bg-rose-600"
                                title="Remove preview"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="previewUrl"
                        class="flex flex-col justify-between gap-3 pt-1 sm:flex-row sm:items-center"
                    >
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-neutral-700 dark:text-neutral-300"
                        >
                            <input
                                v-model="uploadForm.is_primary"
                                type="checkbox"
                                class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <span>Set as primary hero photo</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="clearSelectedFile"
                                class="px-3 py-1.5 text-xs font-bold text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="submitUpload"
                                :disabled="uploadForm.processing"
                                class="rounded-xl bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white shadow-md transition-colors hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {{
                                    uploadForm.processing
                                        ? 'Uploading...'
                                        : 'Save & Upload Photo'
                                }}
                            </button>
                        </div>
                    </div>

                    <InputError :message="uploadForm.errors.image" />
                </div>

                <!-- Existing Gallery -->
                <div class="space-y-3">
                    <h4
                        class="text-xs font-bold tracking-wider text-neutral-400 uppercase"
                    >
                        Current Location Photos
                    </h4>

                    <div
                        v-if="venue?.images && venue.images.length > 0"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <div
                            v-for="img in venue.images"
                            :key="img.id"
                            class="group relative flex flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div
                                class="relative aspect-video overflow-hidden bg-neutral-100 dark:bg-neutral-800"
                            >
                                <img
                                    :src="img.url"
                                    :alt="venue.name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <span
                                    v-if="img.is_primary"
                                    class="absolute top-2 left-2 flex items-center gap-1 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-black text-white shadow-sm"
                                >
                                    <Star class="h-3 w-3 fill-white" /> Primary
                                    Hero
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between gap-2 border-t border-neutral-100 bg-neutral-50/50 p-3 dark:border-neutral-800 dark:bg-neutral-900/50"
                            >
                                <button
                                    v-if="!img.is_primary"
                                    @click="setPrimaryImage(img)"
                                    class="flex items-center gap-1 text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline"
                                >
                                    <CheckCircle2 class="h-3.5 w-3.5" /> Set as
                                    Primary
                                </button>
                                <span
                                    v-else
                                    class="flex items-center gap-1 text-[11px] font-bold text-emerald-600"
                                >
                                    <Star
                                        class="h-3.5 w-3.5 fill-emerald-600"
                                    />
                                    Hero Display
                                </span>

                                <button
                                    v-if="canDelete"
                                    @click="deleteImage(img)"
                                    class="rounded-lg p-1.5 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950/40"
                                    title="Delete Photo"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed p-8 text-center text-xs text-neutral-400"
                    >
                        No photos uploaded for this location yet. Upload your
                        first location photo above.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
