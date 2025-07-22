<script setup>
    import { defineProps, defineEmits } from 'vue';
    import { useI18n } from 'vue-i18n';
    import StopIcon from 'vue-material-design-icons/Stop.vue';
    import ExitIcon from 'vue-material-design-icons/ExitToApp.vue';

    const props = defineProps({
        session: {
            type: Object,
            default: () => ({ name: '', code: '', description: '', host: {} }),
        },
        isHost: {
            type: Boolean,
            default: false,
        },
    });

    const emit = defineEmits({
        'end-session': {
            type: 'update',
            default: () => {},
        },
        'leave-session': {
            type: 'update',
            default: () => {},
        },
    });

    const { t } = useI18n();
</script>
<template>
    <el-card class="session-header">
        <div class="session-info">
            <div class="session-main-info">
                <h1 class="session-title">{{ props.session.name }}</h1>
                <el-tag type="primary" size="large">{{ props.session.code }}</el-tag>
            </div>
            <div class="session-description" v-if="props.session.description">
                <p>{{ props.session.description }}</p>
            </div>
        </div>
        <div class="session-actions">
            <el-button v-if="props.isHost" type="danger" @click="emit('end-session')" :icon="StopIcon">
                {{ t('session.end.button') }}
            </el-button>
            <el-button v-else type="warning" @click="emit('leave-session')" :icon="ExitIcon">
                {{ t('session.leave.button') }}
            </el-button>
        </div>
    </el-card>
</template>
<style scoped>
    .session-header {
        margin-bottom: 20px;
    }
    .session-info .session-main-info {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 10px;
    }
    .session-title {
        margin: 0;
        color: var(--el-text-color-primary);
    }
    .session-description {
        color: var(--el-text-color-regular);
        margin-bottom: 15px;
    }
    .session-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 15px;
    }
</style>
