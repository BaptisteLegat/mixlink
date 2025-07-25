<script setup>
    import { defineProps, defineEmits } from 'vue';
    import { useI18n } from 'vue-i18n';
    import GroupIcon from 'vue-material-design-icons/AccountGroup.vue';
    import DeleteIcon from 'vue-material-design-icons/Delete.vue';

    const props = defineProps({
        session: {
            type: Object,
            default: () => ({ host: {} }),
        },
        participants: {
            type: Array,
            default: () => [],
        },
        filteredParticipants: {
            type: Array,
            default: () => [],
        },
        participantCount: {
            type: Number,
            default: 0,
        },
        isHost: {
            type: Boolean,
            default: false,
        },
    });

    const emit = defineEmits({
        'kick-participant': {
            type: 'update',
            default: () => {},
        },
    });

    const { t } = useI18n();

    const kickParticipant = (pseudo) => {
        emit('kick-participant', pseudo);
    };
</script>
<template>
    <el-card class="participants-card">
        <template #header>
            <div class="participants-header">
                <GroupIcon style="margin-right: 8px" />
                {{ t('session.participants.title') }}
                <el-badge :value="props.participantCount" class="participants-count" />
            </div>
        </template>
        <div class="participants-list">
            <div class="participant-item">
                <el-avatar :src="props.session.host.profilePicture" size="small">
                    {{ props.session.host.firstName?.charAt(0) || 'H' }}
                </el-avatar>
                <span class="participant-name">{{ props.session.host.firstName || t('session.participants.host') }}</span>
                <el-tag type="success" size="small">{{ t('session.participants.host') }}</el-tag>
            </div>
            <div v-for="participant in props.filteredParticipants" :key="participant.id" class="participant-item">
                <el-avatar size="small">
                    {{ participant.pseudo?.charAt(0) || 'G' }}
                </el-avatar>
                <span class="participant-name">{{ participant.pseudo }}</span>
                <el-tag type="info" size="small">{{ t('session.participants.guest') }}</el-tag>
                <el-button v-if="props.isHost" type="danger" size="small" :icon="DeleteIcon" @click="kickParticipant(participant.pseudo)">
                    {{ t('session.kick.button') }}
                </el-button>
            </div>
        </div>
        <div v-if="0 === props.participantCount" class="no-participants">
            <p>{{ t('session.participants.waiting') }}</p>
        </div>
    </el-card>
</template>
<style scoped>
    .participants-card {
        margin-bottom: 20px;
    }
    .participants-header {
        display: flex;
        align-items: center;
        font-weight: 600;
    }
    .participants-count {
        margin-left: 10px;
    }
    .participants-list .participant-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--el-border-color-lighter);
    }
    .participants-list .participant-item:last-child {
        border-bottom: none;
    }
    .participant-name {
        flex: 1;
        font-weight: 500;
    }
    .no-participants {
        text-align: center;
        padding: 20px;
        color: var(--el-text-color-regular);
    }
    @media (max-width: 768px) {
        .participants-header {
            font-size: 16px;
        }
        .participants-count {
            font-size: 14px;
        }
        .participants-list .participant-item {
            padding: 8px 0;
        }
        .participant-name {
            font-size: 14px;
        }
        .no-participants {
            font-size: 14px;
        }
    }
</style>
