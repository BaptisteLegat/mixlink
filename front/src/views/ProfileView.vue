<script setup>
    import { useAuthStore } from '@/stores/authStore';
    import { useI18n } from 'vue-i18n';
    import { computed } from 'vue';
    import UserIcon from 'vue-material-design-icons/Account.vue';

    const { t } = useI18n();
    const authStore = useAuthStore();

    const userInitials = computed(() => {
        if (!authStore.user) return '';

        const firstName = authStore.user.firstName || '';
        const lastName = authStore.user.lastName || '';

        if (firstName && lastName) {
            return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
        } else if (firstName) {
            return firstName.charAt(0).toUpperCase();
        } else if (authStore.user.email) {
            return authStore.user.email.charAt(0).toUpperCase();
        }

        return 'U';
    });

    const userName = computed(() => {
        if (!authStore.user) return '';

        const firstName = authStore.user.firstName || '';
        const lastName = authStore.user.lastName || '';

        if (firstName && lastName) {
            return `${firstName} ${lastName}`;
        } else if (firstName) {
            return firstName;
        } else if (authStore.user.email) {
            return authStore.user.email;
        }

        return t('profile.unknown_user');
    });
</script>

<template>
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
                            <el-text tag="p" class="profile-email">{{ authStore.user?.email }}</el-text>
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
                                        <b>{{ t('profile.role') }}:</b> {{ authStore.user?.roles.join(', ') }}
                                    </el-text>
                                </el-col>
                                <el-col :span="24" :md="12">
                                    <el-text tag="p" class="info-label">{{ t('profile.subscription') }}</el-text>
                                    <el-text tag="p" v-if="authStore.subscription">
                                        <b>{{ t('profile.plan') }}:</b> {{ t('home.plans.' + authStore.subscription.plan.name + '.title') }}
                                    </el-text>
                                    <el-text tag="p" v-if="authStore.subscription">
                                        <b>{{ t('profile.start_date') }}:</b> {{ authStore.subscription.startDate }}
                                    </el-text>
                                    <el-text tag="p" v-else>
                                        {{ t('profile.no_subscription') }}
                                    </el-text>
                                </el-col>
                            </el-row>
                        </div>

                        <el-divider />

                        <div class="profile-actions">
                            <el-button type="primary" @click="$router.push('/')">
                                {{ t('profile.back_to_home') }}
                            </el-button>
                            <el-button type="danger" @click="authStore.logout()">
                                {{ t('header.logout') }}
                            </el-button>
                        </div>
                    </el-card>
                </el-col>
            </el-row>
        </el-space>
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
</template>

<style lang="scss" scoped>
    .profile-container {
        overflow: hidden;
        position: relative;
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
</style>
