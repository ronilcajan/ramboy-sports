<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X, Tag, Plus, Trash2 } from '@lucide/vue';
import { ref, watch, computed } from 'vue';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getMergedTimeSlots, isDefaultTimeSlot } from '@/utils/timeSlots';

interface CourtImage {
    id: number;
    path: string;
    url: string;
    is_primary: boolean;
}

export interface EditCourtTarget {
    id: number;
    venue_id?: number | null;
    name: string;
    sport_type: string;
    description?: string | null;
    status: string;
    base_price: string | number;
    slot_prices?: Record<string, string | number> | null;
    slot_duration_minutes: number;
    buffer_minutes?: number;
    is_active?: boolean;
    primary_image?: CourtImage | null;
    primary_image_url?: string | null;
}

interface SelectOption {
    value: string;
    label: string;
}

interface VenueOption {
    id: number;
    name: string;
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        court: EditCourtTarget | null;
        venues?: VenueOption[];
        sportTypes?: SelectOption[];
        statuses?: SelectOption[];
    }>(),
    {
        venues: () => [],
        sportTypes: () => [
            { value: 'pickleball', label: 'Pickleball' },
            { value: 'badminton', label: 'Badminton' },
            { value: 'basketball', label: 'Basketball' },
            { value: 'tennis', label: 'Tennis' },
            { value: 'volleyball', label: 'Volleyball' },
            { value: 'futsal', label: 'Futsal' },
        ],
        statuses: () => [
            { value: 'available', label: 'Available' },
            { value: 'maintenance', label: 'Maintenance' },
            { value: 'closed', label: 'Closed' },
        ],
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const showAddSlotInput = ref(false);
const newSlotHour = ref('03');
const newSlotMinute = ref('00');
const newSlotPeriod = ref<'AM' | 'PM'>('AM');

const allTimeSlots = computed(() => {
    return getMergedTimeSlots(editForm.slot_prices);
});

function addCustomTimeSlot() {
    const formatted = `${newSlotHour.value.padStart(2, '0')}:${newSlotMinute.value.padStart(2, '0')} ${newSlotPeriod.value}`;

    if (editForm.slot_prices[formatted] === undefined) {
        editForm.slot_prices[formatted] = '';
    }

    showAddSlotInput.value = false;
}

function removeCustomTimeSlot(slot: string) {
    if (isDefaultTimeSlot(slot)) {
        return;
    }

    delete editForm.slot_prices[slot];
}

const editForm = useForm({
    name: '',
    venue_id: '' as string | number,
    sport_type: 'pickleball',
    description: '',
    status: 'available',
    base_price: '25.00',
    slot_prices: {} as Record<string, string | number>,
    slot_duration_minutes: 60,
    buffer_minutes: 0,
    is_active: true,
    image: null as File | null,
    delete_image: false,
});

const editPreview = ref<string | null>(null);

function populateForm() {
    if (!props.court) {
        return;
    }

    editForm.name = props.court.name || '';
    editForm.venue_id = props.court.venue_id || '';
    editForm.sport_type = props.court.sport_type || 'pickleball';
    editForm.description = props.court.description || '';
    editForm.status = props.court.status || 'available';
    editForm.base_price = String(props.court.base_price || '25.00');
    editForm.slot_duration_minutes = props.court.slot_duration_minutes || 60;
    editForm.buffer_minutes = props.court.buffer_minutes || 0;
    editForm.is_active = props.court.is_active ?? true;
    editForm.slot_prices = props.court.slot_prices
        ? { ...props.court.slot_prices }
        : {};
    editForm.image = null;
    editForm.delete_image = false;

    if (editPreview.value) {
        URL.revokeObjectURL(editPreview.value);
        editPreview.value = null;
    }

    editForm.clearErrors();
}

watch(
    () => [props.isOpen, props.court],
    () => {
        if (props.isOpen && props.court) {
            populateForm();
        }
    },
    { immediate: true },
);

function onImageChange(e: Event) {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];

        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit.');

            return;
        }

        editForm.image = file;
        editForm.delete_image = false;

        if (editPreview.value) {
            URL.revokeObjectURL(editPreview.value);
        }

        editPreview.value = URL.createObjectURL(file);
    }
}

function removeImage() {
    editForm.image = null;
    editForm.delete_image = true;

    if (editPreview.value) {
        URL.revokeObjectURL(editPreview.value);
        editPreview.value = null;
    }
}

function submitEdit() {
    if (!props.court) {
        return;
    }

    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(CourtController.update(props.court.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');

                if (editPreview.value) {
                    URL.revokeObjectURL(editPreview.value);
                    editPreview.value = null;
                }
            },
        });
}

function getExistingImageUrl(): string | null {
    if (!props.court) {
        return null;
    }

    if (props.court.primary_image_url) {
        return props.court.primary_image_url;
    }

    if (props.court.primary_image?.url) {
        return props.court.primary_image.url;
    }

    return null;
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen && court"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="my-8 w-full max-w-2xl space-y-5 rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-neutral-100 pb-4 dark:border-neutral-800"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-emerald-600 uppercase dark:bg-emerald-950/60 dark:text-emerald-400"
                            >
                                {{ court.name }}
                            </span>
                        </div>
                        <h2
                            class="mt-1 text-xl font-black tracking-tight text-neutral-900 dark:text-white"
                        >
                            Edit Court Details &amp; Dynamic Slot Rates
                        </h2>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-full p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:hover:bg-neutral-800 dark:hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form
                    @submit.prevent="submitEdit"
                    class="max-h-[75vh] space-y-5 overflow-y-auto pr-1"
                >
                    <!-- General Details -->
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="edit-court-modal-name"
                                >Court Name *</Label
                            >
                            <Input
                                id="edit-court-modal-name"
                                v-model="editForm.name"
                                type="text"
                                required
                            />
                            <InputError :message="editForm.errors.name" />
                        </div>

                        <div
                            v-if="venues && venues.length > 0"
                            class="space-y-2"
                        >
                            <Label for="edit-court-modal-venue"
                                >Assigned Facility / Location</Label
                            >
                            <select
                                id="edit-court-modal-venue"
                                v-model="editForm.venue_id"
                                class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-bold text-neutral-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            >
                                <option value="">No Location Assigned</option>
                                <option
                                    v-for="v in venues"
                                    :key="v.id"
                                    :value="v.id"
                                >
                                    {{ v.name }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.venue_id" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="edit-court-modal-sport"
                                    >Sport Type *</Label
                                >
                                <select
                                    id="edit-court-modal-sport"
                                    v-model="editForm.sport_type"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-bold text-neutral-900 capitalize focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                >
                                    <option
                                        v-for="st in sportTypes"
                                        :key="st.value"
                                        :value="st.value"
                                    >
                                        {{ st.label }}
                                    </option>
                                </select>
                                <InputError
                                    :message="editForm.errors.sport_type"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="edit-court-modal-status"
                                    >Status *</Label
                                >
                                <select
                                    id="edit-court-modal-status"
                                    v-model="editForm.status"
                                    required
                                    class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-bold text-neutral-900 capitalize focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                >
                                    <option
                                        v-for="st in statuses"
                                        :key="st.value"
                                        :value="st.value"
                                    >
                                        {{ st.label }}
                                    </option>
                                </select>
                                <InputError :message="editForm.errors.status" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-2">
                                <Label for="edit-court-modal-price"
                                    >Base Hourly Rate (₱) *</Label
                                >
                                <Input
                                    id="edit-court-modal-price"
                                    v-model="editForm.base_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                />
                                <InputError
                                    :message="editForm.errors.base_price"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="edit-court-modal-duration"
                                    >Slot Mins *</Label
                                >
                                <Input
                                    id="edit-court-modal-duration"
                                    v-model.number="
                                        editForm.slot_duration_minutes
                                    "
                                    type="number"
                                    min="15"
                                    step="15"
                                    required
                                />
                                <InputError
                                    :message="
                                        editForm.errors.slot_duration_minutes
                                    "
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="edit-court-modal-buffer"
                                    >Buffer Mins *</Label
                                >
                                <Input
                                    id="edit-court-modal-buffer"
                                    v-model.number="editForm.buffer_minutes"
                                    type="number"
                                    min="0"
                                    step="5"
                                    required
                                />
                                <InputError
                                    :message="editForm.errors.buffer_minutes"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Time Slot Pricing Grid -->
                    <div
                        class="space-y-3 rounded-2xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-800/40"
                    >
                        <div
                            class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
                        >
                            <div>
                                <h3
                                    class="flex items-center gap-1.5 text-xs font-extrabold tracking-wider text-neutral-900 uppercase dark:text-white"
                                >
                                    <Tag class="size-3.5 text-emerald-600" />
                                    Dynamic Time Slot Pricing (Optional)
                                </h3>
                                <p class="mt-0.5 text-[11px] text-neutral-500">
                                    Configure specific hourly rates for
                                    peak/off-peak slots. Slots left empty
                                    default to
                                    <strong
                                        >₱{{
                                            editForm.base_price || '0.00'
                                        }}</strong
                                    >.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="showAddSlotInput = !showAddSlotInput"
                                class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-xl border border-emerald-600/30 bg-emerald-600/10 px-3 py-1.5 text-xs font-bold text-emerald-600 transition-all hover:bg-emerald-600/20 dark:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                <Plus class="size-3.5" />
                                <span>Add Time Slot</span>
                            </button>
                        </div>

                        <!-- Inline Add Custom Time Slot Form -->
                        <div
                            v-if="showAddSlotInput"
                            class="flex flex-wrap items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-50/50 p-3 shadow-xs dark:bg-emerald-950/40"
                        >
                            <span
                                class="text-xs font-bold text-emerald-800 dark:text-emerald-300"
                                >New Slot Time:</span
                            >
                            <div
                                class="flex items-center gap-1 text-xs font-bold"
                            >
                                <select
                                    v-model="newSlotHour"
                                    class="rounded-lg border border-neutral-300 bg-white px-2 py-1 text-xs font-bold text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                                >
                                    <option
                                        v-for="h in [
                                            '01',
                                            '02',
                                            '03',
                                            '04',
                                            '05',
                                            '06',
                                            '07',
                                            '08',
                                            '09',
                                            '10',
                                            '11',
                                            '12',
                                        ]"
                                        :key="h"
                                        :value="h"
                                    >
                                        {{ h }}
                                    </option>
                                </select>
                                <span class="text-neutral-500">:</span>
                                <select
                                    v-model="newSlotMinute"
                                    class="rounded-lg border border-neutral-300 bg-white px-2 py-1 text-xs font-bold text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                                >
                                    <option value="00">00</option>
                                    <option value="15">15</option>
                                    <option value="30">30</option>
                                    <option value="45">45</option>
                                </select>
                                <select
                                    v-model="newSlotPeriod"
                                    class="rounded-lg border border-neutral-300 bg-white px-2 py-1 text-xs font-bold text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                                >
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                            <button
                                type="button"
                                @click="addCustomTimeSlot"
                                class="cursor-pointer rounded-lg bg-emerald-600 px-3 py-1 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
                            >
                                Add Slot
                            </button>
                            <button
                                type="button"
                                @click="showAddSlotInput = false"
                                class="cursor-pointer rounded-lg bg-neutral-200 px-2.5 py-1 text-xs font-semibold text-neutral-600 hover:bg-neutral-300 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700"
                            >
                                Cancel
                            </button>
                        </div>

                        <div
                            class="grid max-h-56 grid-cols-2 gap-2.5 overflow-y-auto p-1 pr-2 sm:grid-cols-3 md:grid-cols-4"
                        >
                            <div
                                v-for="slot in allTimeSlots"
                                :key="slot"
                                class="space-y-1 rounded-xl border border-neutral-200 bg-white p-2 shadow-2xs dark:border-neutral-700 dark:bg-neutral-900"
                            >
                                <div
                                    class="flex items-center justify-between text-[11px] font-bold text-neutral-800 dark:text-neutral-200"
                                >
                                    <span>{{ slot }}</span>
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="text-[9px] font-semibold text-neutral-400"
                                        >
                                            {{
                                                editForm.slot_prices[slot] &&
                                                parseFloat(
                                                    String(
                                                        editForm.slot_prices[
                                                            slot
                                                        ],
                                                    ),
                                                ) > 0
                                                    ? 'Custom'
                                                    : 'Base'
                                            }}
                                        </span>
                                        <button
                                            v-if="!isDefaultTimeSlot(slot)"
                                            type="button"
                                            @click="removeCustomTimeSlot(slot)"
                                            class="cursor-pointer rounded p-0.5 text-rose-500 hover:text-rose-700"
                                            title="Remove custom slot"
                                        >
                                            <Trash2 class="size-3" />
                                        </button>
                                    </div>
                                </div>
                                <div class="relative">
                                    <span
                                        class="absolute top-1.5 left-2.5 text-xs font-bold text-neutral-400"
                                        >₱</span
                                    >
                                    <Input
                                        v-model="editForm.slot_prices[slot]"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :placeholder="
                                            editForm.base_price
                                                ? String(editForm.base_price)
                                                : '0.00'
                                        "
                                        class="h-8 pl-6 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="edit-court-modal-description"
                            >Description</Label
                        >
                        <textarea
                            id="edit-court-modal-description"
                            v-model="editForm.description"
                            rows="2"
                            placeholder="Brief description of court features, surface, lighting..."
                            class="w-full rounded-xl border border-neutral-300 bg-neutral-50 px-3 py-2 text-xs font-medium text-neutral-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>

                    <!-- Photo Upload -->
                    <div
                        class="space-y-3 rounded-2xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-800/40"
                    >
                        <Label
                            class="text-xs font-bold text-neutral-700 dark:text-neutral-300"
                            >Primary Court Photo</Label
                        >
                        <div class="flex items-center gap-3">
                            <div v-if="editPreview" class="relative">
                                <img
                                    :src="editPreview"
                                    alt="Preview"
                                    class="h-16 w-24 rounded-xl border border-neutral-200 object-cover shadow-sm dark:border-neutral-700"
                                />
                                <span
                                    class="absolute -top-1.5 -right-1.5 rounded-full bg-emerald-600 px-1.5 text-[8px] font-bold text-white"
                                    >New</span
                                >
                            </div>
                            <div
                                v-else-if="
                                    getExistingImageUrl() &&
                                    !editForm.delete_image
                                "
                                class="relative"
                            >
                                <img
                                    :src="getExistingImageUrl()!"
                                    alt="Current Photo"
                                    class="h-16 w-24 rounded-xl border border-neutral-200 object-cover shadow-sm dark:border-neutral-700"
                                />
                            </div>
                            <div
                                v-else
                                class="flex h-16 w-24 items-center justify-center rounded-xl border border-dashed border-neutral-300 text-xs text-neutral-400 dark:border-neutral-700"
                            >
                                No Photo
                            </div>

                            <div class="space-y-1">
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/webp,image/avif"
                                    @change="onImageChange"
                                    class="text-xs text-neutral-500"
                                />
                                <div
                                    v-if="
                                        getExistingImageUrl() &&
                                        !editForm.delete_image
                                    "
                                    class="pt-0.5"
                                >
                                    <button
                                        type="button"
                                        @click="removeImage"
                                        class="text-[11px] font-semibold text-rose-600 hover:underline"
                                    >
                                        Remove current photo
                                    </button>
                                </div>
                            </div>
                        </div>
                        <InputError :message="editForm.errors.image" />
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="edit-court-modal-active"
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        <Label
                            for="edit-court-modal-active"
                            class="cursor-pointer text-xs font-bold text-neutral-800 dark:text-neutral-200"
                            >Active for online booking</Label
                        >
                    </div>

                    <!-- Modal Footer Actions -->
                    <div
                        class="flex justify-end gap-3 border-t border-neutral-100 pt-3 dark:border-neutral-800"
                    >
                        <Button
                            variant="outline"
                            type="button"
                            @click="emit('close')"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="bg-emerald-600 font-bold text-white hover:bg-emerald-700"
                        >
                            {{
                                editForm.processing
                                    ? 'Saving...'
                                    : 'Save Court & Rates'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
