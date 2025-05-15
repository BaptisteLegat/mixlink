import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function useUserDisplay(user) {
    const { t } = useI18n();

    const userInitials = computed(() => {
        if (!user.value) return '';

        const firstName = user.value.firstName || '';
        const lastName = user.value.lastName || '';

        if (firstName && lastName) {
            return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
        } else if (firstName) {
            return firstName.charAt(0).toUpperCase();
        } else if (user.value.email) {
            return user.value.email.charAt(0).toUpperCase();
        }

        return 'U';
    });

    const userName = computed(() => {
        if (!user.value) return '';

        const firstName = user.value.firstName || '';
        const lastName = user.value.lastName || '';

        if (firstName && lastName) {
            return `${firstName} ${lastName}`;
        } else if (firstName) {
            return firstName;
        } else if (user.value.email) {
            return user.value.email;
        }

        return t('profile.unknown_user');
    });

    return {
        userInitials,
        userName,
    };
}
