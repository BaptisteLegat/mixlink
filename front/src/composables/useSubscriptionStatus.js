import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/authStore';

export function useSubscriptionStatus() {
    const { t } = useI18n();
    const authStore = useAuthStore();

    const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    };

    const getSubscriptionTagType = (subscription) => {
        if (subscription.isCanceled) {
            return 'warning';
        }
        return subscription.isActive ? 'success' : 'danger';
    };

    const getSubscriptionStatusLabel = (subscription) => {
        if (subscription.isCanceled) {
            return t('profile.canceled', { endDate: formatDate(subscription.endDate) });
        }
        return subscription.isActive ? t('profile.active') : t('profile.inactive');
    };

    const hasActiveSubscription = computed(() => {
        return authStore.subscription && authStore.subscription.isActive && !authStore.subscription.isCanceled;
    });

    return {
        formatDate,
        getSubscriptionTagType,
        getSubscriptionStatusLabel,
        hasActiveSubscription,
    };
}
