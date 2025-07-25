<script setup>
    import { defineProps, defineEmits } from 'vue';
    import { useI18n } from 'vue-i18n';

    const props = defineProps({
        currentUserPseudo: {
            type: String,
            default: '',
        },
        hasJoined: {
            type: Boolean,
            default: false,
        },
    });

    const emit = defineEmits({
        'update:pseudo': {
            type: 'update',
            default: () => {},
        },
        'join-as-guest': {
            type: 'update',
            default: () => {},
        },
    });

    const { t } = useI18n();

    const joinAsGuest = () => {
        emit('join-as-guest');
    };
</script>
<template>
    <el-card v-if="!hasJoined" class="guest-join-card">
        <div class="guest-join-content">
            <h3>{{ t('session.join.title') }}</h3>
            <p>{{ t('session.join.description') }}</p>
            <div class="pseudo-input-group">
                <el-input
                    :model-value="props.currentUserPseudo"
                    :placeholder="t('session.join.pseudo_placeholder')"
                    size="large"
                    maxlength="20"
                    show-word-limit
                    @update:model-value="emit('update:pseudo', $event)"
                    @keyup.enter="joinAsGuest"
                />
                <el-button type="primary" size="large" @click="joinAsGuest" :disabled="!props.currentUserPseudo.trim()">
                    {{ t('session.join.button') }}
                </el-button>
            </div>
        </div>
    </el-card>
</template>
<style scoped>
    .guest-join-card {
        margin-bottom: 20px;
    }
    .guest-join-content {
        text-align: center;
    }
    .guest-join-content h3 {
        margin-bottom: 10px;
        color: var(--el-text-color-primary);
    }
    .guest-join-content p {
        color: var(--el-text-color-regular);
        margin-bottom: 20px;
    }
    .pseudo-input-group {
        display: flex;
        gap: 10px;
        max-width: 400px;
        margin: 0 auto;
    }
    .pseudo-input-group .el-input {
        flex: 1;
    }
    @media (max-width: 768px) {
        .pseudo-input-group {
            flex-direction: column;
        }
        .pseudo-input-group .el-input {
            width: 100%;
        }
    }
</style>
