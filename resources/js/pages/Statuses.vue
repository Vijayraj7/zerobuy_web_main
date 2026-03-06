<template>
    <div>
        <AuthPageHeader :title="$t('Statuses')" />

        <div class="px-2 pt-2 md:px-4 md:pt-4 lg:px-6 lg:pt-6">
            <div class="p-4 lg:p-6 bg-white rounded-xl flex flex-col gap-4">
                <div class="text-slate-900 text-lg font-medium">
                    {{ $t('Followed Shop Updates') }}
                </div>

                <div v-if="isLoading" class="text-slate-500">
                    {{ $t('Loading') }}...
                </div>

                <div
                    v-else-if="statuses.length === 0"
                    class="text-slate-500 border border-dashed border-slate-200 rounded-lg p-6"
                >
                    {{ $t('No status updates available') }}
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <button
                        v-for="statusGroup in statuses"
                        :key="statusGroup.shop_id"
                        type="button"
                        class="text-left border border-slate-200 rounded-xl p-4 hover:border-primary transition"
                        @click="openStatusViewer(statusGroup)"
                    >
                        <div class="flex items-center gap-3">
                            <img
                                :src="statusGroup.user_image_url"
                                :alt="statusGroup.store_name"
                                class="w-11 h-11 rounded-full object-cover border border-primary-200"
                            />
                            <div class="min-w-0">
                                <div class="text-slate-900 font-medium truncate">
                                    {{ statusGroup.store_name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ formatDate(statusGroup.timestamp) }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-sm text-slate-600 line-clamp-2 min-h-10">
                            {{ firstMessage(statusGroup) }}
                        </div>

                        <div class="mt-3 text-xs text-primary font-medium">
                            {{ statusGroup.status_items?.length || 0 }} {{ $t('updates') }}
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <TransitionRoot as="template" :show="viewerOpen">
            <Dialog as="div" class="relative z-20" @close="viewerOpen = false">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/60" />
                </TransitionChild>

                <div class="fixed inset-0 z-20 overflow-y-auto p-4">
                    <div class="flex min-h-full items-center justify-center">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-300"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-xl bg-white rounded-2xl overflow-hidden shadow-xl">
                                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                                    <div>
                                        <div class="text-slate-900 font-semibold">
                                            {{ selectedStatus?.store_name }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ currentStatusIndex + 1 }} / {{ selectedStatus?.status_items?.length || 0 }}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200"
                                        @click="viewerOpen = false"
                                    >
                                        x
                                    </button>
                                </div>

                                <div v-if="currentStatusItem" class="p-4">
                                    <img
                                        :src="currentStatusItem.image_url"
                                        alt="status"
                                        class="w-full h-72 object-cover rounded-xl border border-slate-100"
                                    />

                                    <p class="mt-4 text-slate-700 min-h-14">
                                        {{ currentStatusItem.message || '-' }}
                                    </p>

                                    <div class="mt-2 text-xs text-slate-500">
                                        {{ formatDate(currentStatusItem.timestamp) }}
                                    </div>
                                </div>

                                <div class="p-4 pt-0 flex items-center justify-between gap-3">
                                    <button
                                        type="button"
                                        class="viewer-btn"
                                        :disabled="currentStatusIndex === 0"
                                        @click="showPrev"
                                    >
                                        {{ $t('Previous') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="viewer-btn"
                                        :disabled="!selectedStatus || currentStatusIndex >= selectedStatus.status_items.length - 1"
                                        @click="showNext"
                                    >
                                        {{ $t('Next') }}
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';

import AuthPageHeader from '../components/AuthPageHeader.vue';
import { useAuth } from '../stores/AuthStore';

const authStore = useAuth();
const router = useRouter();

const isLoading = ref(false);
const statuses = ref([]);

const viewerOpen = ref(false);
const selectedStatus = ref(null);
const currentStatusIndex = ref(0);

const currentStatusItem = computed(() => {
    if (!selectedStatus.value?.status_items?.length) {
        return null;
    }

    return selectedStatus.value.status_items[currentStatusIndex.value] ?? null;
});

onMounted(() => {
    fetchStatuses();
});

const authHeaders = () => ({
    headers: {
        Authorization: authStore.token,
    },
});

const handleUnauthorized = () => {
    authStore.token = null;
    authStore.user = null;
    authStore.addresses = [];
    authStore.favoriteProducts = 0;
    router.push('/');
};

const fetchStatuses = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get('/statuses', authHeaders());
        statuses.value = response.data.data?.statuses ?? [];
    } catch (error) {
        if (error?.response?.status === 401) {
            handleUnauthorized();
        }
    } finally {
        isLoading.value = false;
    }
};

const openStatusViewer = async (statusGroup) => {
    selectedStatus.value = statusGroup;
    currentStatusIndex.value = 0;
    viewerOpen.value = true;
    await markView(statusGroup.status_items?.[0]);
};

const showPrev = async () => {
    if (currentStatusIndex.value <= 0) {
        return;
    }

    currentStatusIndex.value -= 1;
    await markView(currentStatusItem.value);
};

const showNext = async () => {
    if (!selectedStatus.value || currentStatusIndex.value >= selectedStatus.value.status_items.length - 1) {
        return;
    }

    currentStatusIndex.value += 1;
    await markView(currentStatusItem.value);
};

const markView = async (statusItem) => {
    const statusId = statusItem?.status_id;
    if (!statusId) {
        return;
    }

    try {
        await axios.post(`/statuses/${statusId}/view`, {}, authHeaders());
    } catch (error) {
        if (error?.response?.status === 401) {
            handleUnauthorized();
        }
    }
};

const firstMessage = (statusGroup) => {
    return statusGroup?.status_items?.[0]?.message || '-';
};

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
};
</script>

<style scoped>
.viewer-btn {
    @apply px-3 py-2 border border-slate-300 rounded-md text-sm hover:bg-slate-50 disabled:opacity-50;
}
</style>
