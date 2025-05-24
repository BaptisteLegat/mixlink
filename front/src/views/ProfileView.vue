<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useAuthStore } from '@/stores/authStore';
    import GradientBackground from '@/components/ui/GradientBackground.vue';
    import DeleteAccountModal from '@/components/user/DeleteAccountModal.vue';
    import UserIcon from 'vue-material-design-icons/Account.vue';
    import PlanSelector from '@/components/subscription/PlanSelector.vue';
    import UnsubscribeModal from '@/components/subscription/UnsubscribeModal.vue';
    import { useUserDisplay } from '@/composables/useUserDisplay';
    import { useProviderIcons } from '@/composables/useProviderIcons';
    import { useSubscriptionStatus } from '@/composables/useSubscriptionStatus';
    import { ElMessage } from 'element-plus';
    import { useRouter } from 'vue-router';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const router = useRouter();

    const unsubscribeModal = ref(null);
    const deleteAccountModal = ref(null);

    const { userInitials, userName } = useUserDisplay(computed(() => authStore.user));
    const { getProviderIcon, getProviderDisplayName } = useProviderIcons();
    const { formatDate, getSubscriptionTagType, getSubscriptionStatusLabel, hasActiveSubscription } = useSubscriptionStatus();

    function openUnsubscribeModal() {
        unsubscribeModal.value.showDialog();
    }

    function openDeleteAccountModal() {
        deleteAccountModal.value.showDialog();
    }

    async function handleAccountDeleted() {
        ElMessage.success(t('profile.delete_account.success'));
        await router.push('/');
    }
</script>
<template>
    <div class="profile-page">
        <GradientBackground :showGrid="false" />
        <el-container class="profile-container" v-if="authStore.isAuthenticated">
            <el-space direction="vertical" class="profile-section" :fill="true" :size="30">
                <el-row justify="center">
                    <el-col :span="24" :lg="18" :xl="16">
                        <el-text tag="h2" class="section-title">{{ t('profile.title') }}</el-text>
                    </el-col>
                </el-row>
                <el-row justify="center">
                    <el-col :span="24" :lg="18" :xl="16">
                        <el-card shadow="hover" class="profile-card">
                            <div class="profile-header">
                                <el-avatar
                                    :size="100"
                                    :src="authStore.user?.profilePicture"
                                    :icon="authStore.user?.profilePicture ? null : UserIcon"
                                    class="profile-avatar"
                                >
                                <template v-if="!authStore.user?.profilePicture">{{ userInitials }}</template>
                            </el-avatar>
                            <el-text tag="h3" class="profile-name">{{ userName }}</el-text>
                        </div>
                            <el-divider />
                            <div class="profile-info">
                                <el-row :gutter="20">
                                    <el-col :span="24" :md="12">
                                        <el-text tag="p" class="info-label">{{ t('profile.account_info') }}</el-text>
                                        <el-text tag="p">
                                            <b>{{ t('profile.id') }}:</b> {{ authStore.user?.id }}
                                        </el-text>
                                        <el-text tag="p">
                                            <b>{{ t('profile.email') }}:</b> {{ authStore.user?.email }}
                                        </el-text>
                                        <el-text tag="p">
                                            <b>{{ t('profile.first_name') }}:</b> {{ authStore.user?.firstName ?? t('profile.unknown') }}
                                        </el-text>
                                        <el-text tag="p">
                                            <b>{{ t('profile.last_name') }}:</b> {{ authStore.user?.lastName ?? t('profile.unknown') }}
                                        </el-text>
                                    </el-col>
                                    <el-col :span="24" :md="12">
                                        <el-text tag="p" class="info-label">{{ t('profile.subscription') }}</el-text>
                                        <template v-if="authStore.subscription">
                                            <el-text tag="p">
                                                <b>{{ t('profile.plan') }}:</b> {{ t('home.plans.' + authStore.subscription.plan.name + '.title') }}
                                            </el-text>
                                            <el-text tag="p">
                                                <b>{{ t('profile.status') }}:</b>
                                                <el-tag :type="getSubscriptionTagType(authStore.subscription)">
                                                    {{ getSubscriptionStatusLabel(authStore.subscription) }}
                                                </el-tag>
                                            </el-text>
                                            <el-text tag="p">
                                                <b>{{ t('profile.start_date') }}:</b> {{ formatDate(authStore.subscription.startDate) }}
                                            </el-text>
                                            <el-text tag="p">
                                                <b>{{ t('profile.end_date') }}:</b> {{ formatDate(authStore.subscription.endDate) }}
                                            </el-text>
                                            <el-text tag="p" v-if="authStore.subscription.stripeSubscriptionId">
                                                <b>{{ t('profile.subscription_id') }}:</b> {{ authStore.subscription.stripeSubscriptionId }}
                                            </el-text>

                                            <div class="subscription-actions" v-if="hasActiveSubscription">
                                                <el-button type="danger" plain @click="openUnsubscribeModal">
                                                    {{ t('profile.unsubscribe.button') }}
                                                </el-button>
                                            </div>
                                        </template>
                                        <el-text tag="p" v-else>
                                            {{ t('profile.no_subscription') }}
                                        </el-text>
                                    </el-col>
                                </el-row>
                            </div>
                            <el-divider />
                            <el-text tag="h3" class="subscription-section-title">
                                {{ hasActiveSubscription ? t('profile.change_subscription') : t('profile.choose_subscription') }}
                            </el-text>
                            <PlanSelector :compact="true" />
                            <el-divider v-if="authStore.providers && authStore.providers.length > 0" />
                            <div class="connected-services" v-if="authStore.providers && authStore.providers.length > 0">
                                <el-text tag="p" class="info-label">{{ t('profile.connected_services') }}</el-text>

                                <div class="providers-grid">
                                    <div v-for="provider in authStore.providers" :key="provider.id" class="provider-card">
                                        <component :is="getProviderIcon(provider.name)" :size="32" class="provider-icon" />
                                        <div class="provider-details">
                                            <h4 class="provider-name">{{ getProviderDisplayName(provider.name) }}</h4>
                                            <span class="provider-status">{{ t('profile.connected') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <el-divider v-if="authStore.providers && authStore.providers.length > 0" />
                            <div class="profile-actions">
                                <el-button type="primary" @click="$router.push('/')">
                                    {{ t('profile.back_to_home') }}
                                </el-button>
                                <el-button type="danger" @click="authStore.logout()">
                                    {{ t('header.logout') }}
                                </el-button>
                            </div>
                            <el-divider />
                            <div class="danger-zone">
                                <el-text tag="h3" class="danger-title">{{ t('profile.danger_zone.title') }}</el-text>
                                <el-space direction="vertical" :fill="true" :size="15">
                                    <div v-if="authStore.subscription" class="danger-action">
                                        <div>
                                            <el-text tag="h4">{{ t('profile.danger_zone.unsubscribe_title') }}</el-text>
                                            <el-text type="info">{{ t('profile.danger_zone.unsubscribe_description') }}</el-text>
                                        </div>
                                        <el-button type="danger" plain @click="openUnsubscribeModal">
                                            {{ t('profile.unsubscribe.button') }}
                                        </el-button>
                                    </div>

                                    <div class="danger-action">
                                        <div>
                                            <el-text tag="h4">{{ t('profile.danger_zone.delete_title') }}</el-text>
                                            <el-text type="info">{{ t('profile.danger_zone.delete_description') }}</el-text>
                                        </div>
                                        <el-button type="danger" @click="openDeleteAccountModal">
                                            {{ t('profile.delete_account.button') }}
                                        </el-button>
                                    </div>
                                </el-space>
                            </div>
                        </el-card>
                    </el-col>
                </el-row>
            </el-space>
            <UnsubscribeModal ref="unsubscribeModal" />
            <DeleteAccountModal ref="deleteAccountModal" @account-deleted="handleAccountDeleted" />
        </el-container>
        <el-container v-else>
            <el-row justify="center">
                <el-col :span="24" :lg="18" :xl="16">
                    <el-text tag="h2" class="section-title">{{ t('profile.not_authenticated') }}</el-text>
                    <el-button type="primary" @click="$router.push('/login')">
                        {{ t('header.login') }}
                    </el-button>
                </el-col>
            </el-row>
        </el-container>
    </div>
</template>

<style lang="scss" scoped>
    .profile-page {
        position: relative;
        min-height: calc(100vh - 160px);
        overflow: hidden;
    }

    .profile-container {
        position: relative;
        z-index: 10;
        margin: 0 auto;
        max-width: 1440px;
    }

    .profile-section {
        position: relative;
        z-index: 1;
        padding: 60px 16px;
        width: 100%;
    }

    .section-title {
        font-size: 2rem;
        margin-bottom: 16px;
        font-weight: 700;
        color: #6023c0;
        display: block;
        text-align: center;

        @media (min-width: 768px) {
            font-size: 2.2rem;
        }
    }

    .profile-card {
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        padding: 20px;
    }

    .profile-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }

    .profile-avatar {
        margin-bottom: 16px;
        font-size: 36px;
        background-color: #6023c0;
        color: white;
    }

    .profile-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .profile-email {
        color: var(--el-text-color-secondary);
    }

    .profile-info {
        margin: 20px 0;
    }

    .info-label {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 12px;
        display: block;
        color: #6023c0;
    }

    .subscription-section-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 16px;
        display: block;
        color: #6023c0;
        text-align: center;
    }

    .subscription-actions {
        margin-top: 16px;
        display: flex;
        justify-content: flex-start;
    }

    .profile-actions {
        display: flex;
        justify-content: center;
        gap: 16px;
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .profile-actions {
            flex-direction: column;
        }
    }
    .connected-services {
        margin: 20px 0;
    }

    .providers-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .provider-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background-color: var(--el-color-info-light-9);
        border-radius: 8px;
        padding: 12px 16px;
        min-width: 180px;
    }

    .provider-icon {
        color: var(--el-color-primary);
    }

    .provider-details {
        display: flex;
        flex-direction: column;
    }

    .provider-name {
        font-weight: 600;
        margin: 0;
        font-size: 1rem;
    }

    .provider-status {
        font-size: 0.85rem;
        color: #67c23a;
    }

    .danger-zone {
        margin-top: 20px;
    }

    .danger-title {
        color: var(--el-color-danger);
        font-weight: bold;
        margin-bottom: 16px;
    }

    .danger-action {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border: 1px solid var(--el-border-color);
        border-radius: 8px;
    }
</style>
