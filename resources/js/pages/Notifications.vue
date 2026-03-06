<template>
    <div>
        <AuthPageHeader :title="$t('Notifications')" />

        <div class="px-2 pt-2 md:px-4 md:pt-4 lg:px-6 lg:pt-6">
            <div class="p-4 lg:p-6 bg-white rounded-xl flex flex-col gap-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-slate-900 text-lg font-medium">
                        {{ $t('Notification List') }}
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="btn-outline"
                            :disabled="isLoading || unreadCount === 0"
                            @click="markAllAsRead"
                        >
                            {{ $t('Mark all read') }}
                        </button>
                        <button
                            type="button"
                            class="btn-danger"
                            :disabled="isLoading || notifications.length === 0"
                            @click="clearAllNotifications"
                        >
                            {{ $t('Clear all') }}
                        </button>
                    </div>
                </div>

                <div v-if="isLoading" class="text-slate-500">
                    {{ $t('Loading') }}...
                </div>

                <div
                    v-else-if="notifications.length === 0"
                    class="text-slate-500 border border-dashed border-slate-200 rounded-lg p-6"
                >
                    {{ $t('No notifications found') }}
                </div>

                <div v-else class="flex flex-col gap-3">
                    <button
                        v-for="notification in paginatedNotifications"
                        :key="notification.id"
                        type="button"
                        class="w-full text-left p-4 rounded-lg border transition"
                        :class="notification.is_read ? 'border-slate-200 bg-white' : 'border-primary-200 bg-primary-50/40'"
                        @click="openNotification(notification)"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="!notification.is_read"
                                        class="w-2.5 h-2.5 rounded-full bg-primary shrink-0"
                                    ></span>
                                    <h3 class="text-slate-900 font-medium truncate">
                                        {{ notification.title || $t('Notification') }}
                                    </h3>
                                </div>
                                <p class="text-sm text-slate-600 mt-1 line-clamp-2">
                                    {{ notification.content || '-' }}
                                </p>
                            </div>
                            <div class="text-xs text-slate-500 shrink-0">
                                {{ formatDate(notification.created_at) }}
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="totalItems > perPage" class="px-2 md:px-4 lg:px-6 mt-4">
            <div class="bg-white p-3 rounded-xl flex justify-between items-center w-full gap-4 flex-wrap">
                <div class="text-slate-800 text-base font-normal leading-normal">
                    {{ $t('Showing') }} {{ perPage * (currentPage - 1) + 1 }} {{ $t('to') }}
                    {{ perPage * (currentPage - 1) + paginatedNotifications.length }}
                    {{ $t('of') }} {{ totalItems }} {{ $t('results') }}
                </div>
                <div>
                    <vue-awesome-paginate
                        :total-items="totalItems"
                        :items-per-page="perPage"
                        type="button"
                        :max-pages-shown="3"
                        v-model="currentPage"
                        :hide-prev-next-when-ends="true"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import AuthPageHeader from '../components/AuthPageHeader.vue';
import { useAuth } from '../stores/AuthStore';

const authStore = useAuth();
const router = useRouter();

const isLoading = ref(false);
const notifications = ref([]);

const currentPage = ref(1);
const perPage = ref(10);

const unreadCount = computed(() => notifications.value.filter((item) => !item.is_read).length);
const totalItems = computed(() => notifications.value.length);

const paginatedNotifications = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return notifications.value.slice(start, start + perPage.value);
});

onMounted(() => {
    fetchNotifications();
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

const fetchNotifications = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get('/notifications', authHeaders());
        notifications.value = response.data.data?.notifications ?? [];

        if (currentPage.value > 1 && paginatedNotifications.value.length === 0) {
            currentPage.value = 1;
        }
    } catch (error) {
        if (error?.response?.status === 401) {
            handleUnauthorized();
        }
    } finally {
        isLoading.value = false;
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/read-all', {}, authHeaders());
        notifications.value = notifications.value.map((item) => ({ ...item, is_read: 1 }));
    } catch (error) {
        if (error?.response?.status === 401) {
            handleUnauthorized();
        }
    }
};

const clearAllNotifications = async () => {
    try {
        await axios.delete('/notifications/clear-all', authHeaders());
        notifications.value = [];
        currentPage.value = 1;
    } catch (error) {
        if (error?.response?.status === 401) {
            handleUnauthorized();
        }
    }
};

const openNotification = async (notification) => {
    if (!notification.is_read) {
        try {
            await axios.post(`/notifications/${notification.id}/read`, {}, authHeaders());
            notification.is_read = 1;
        } catch (error) {
            if (error?.response?.status === 401) {
                handleUnauthorized();
                return;
            }
        }
    }

    if (notification.url) {
        if (/^https?:\/\//i.test(notification.url)) {
            window.location.href = notification.url;
            return;
        }

        router.push(notification.url);
    }
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
.btn-outline {
    @apply px-3 py-2 text-sm border border-slate-300 text-slate-700 rounded-md hover:bg-slate-50 disabled:opacity-50;
}

.btn-danger {
    @apply px-3 py-2 text-sm border border-red-200 text-red-600 rounded-md hover:bg-red-50 disabled:opacity-50;
}
</style>
