<script setup>
    import { ref, computed, watch } from 'vue';
    import { ElMessage } from 'element-plus';
    import { Download } from '@element-plus/icons-vue';
    import { useI18n } from 'vue-i18n';
    import { usePlaylistExportStore } from '@/stores/playlistExportStore';

    const { t } = useI18n();

    const props = defineProps({
        playlistId: {
            type: String,
            required: true,
        },
        songsCount: {
            type: Number,
            default: 0,
        },
        platform: {
            type: String,
            required: true,
        },
    });

    const emit = defineEmits(['export-completed']);

    const exportStore = usePlaylistExportStore();

    const showExportModal = ref(false);

    const exportStatus = computed(() => {
        if (exportStore.exportError?.value) {
            return 'exception';
        }
        if (exportStore.exportProgress.value === 100) {
            return 'success';
        }
        return '';
    });

    const isButtonDisabled = computed(() => {
        return props.songsCount === 0;
    });

    const buttonTitle = computed(() => {
        if (props.songsCount === 0) {
            return t('playlist.export.disabled.no_songs');
        }

        return t('playlist.export.button');
    });

    const exportPlaylist = async () => {
        if (isButtonDisabled.value) {
            return;
        }

        try {
            const result = await exportStore.exportPlaylist(props.playlistId, props.platform);

            ElMessage.success(t('playlist.export.success'));
            emit('export-completed', result);
        } catch (error) {
            console.error('Export failed:', error);
            const errorMessage = t(`playlist.export.errors.${error.message}`, error.message);
            ElMessage.error(errorMessage);
        }
    };

    const openPlaylistUrl = () => {
        if (exportStore.exportResult?.value?.playlist_url) {
            window.open(exportStore.exportResult.value.playlist_url, '_blank');
        }
    };

    const closeModal = () => {
        showExportModal.value = false;
        exportStore.resetExportState();
    };

    watch(
        () => props.songsCount,
        (newCount) => {
            exportStore.updateSongsCount(newCount);
        }
    );
</script>
<template>
    <div class="playlist-export">
        <el-button
            type="primary"
            :loading="exportStore.isExporting.value"
            :disabled="isButtonDisabled"
            :title="buttonTitle"
            @click="exportPlaylist"
            class="export-button"
        >
            <el-icon><Download /></el-icon>
            {{ $t('playlist.export.button') }}
        </el-button>

        <el-dialog v-model="showExportModal" :title="$t('playlist.export.modal.title')" width="500px" :close-on-click-modal="false">
            <div class="export-content">
                <p class="export-description">
                    {{ $t('playlist.export.modal.description') }}
                </p>

                <div v-if="exportStore.isExporting.value" class="export-progress">
                    <el-progress :percentage="exportStore.exportProgress.value" :status="exportStatus" :stroke-width="8" />
                    <p class="progress-text">
                        {{
                            $t('playlist.export.modal.progress', {
                                current: exportStore.exportResult?.value?.exported_tracks || 0,
                                total:
                                    (exportStore.exportResult?.value?.exported_tracks || 0) + (exportStore.exportResult?.value?.failed_tracks || 0),
                            })
                        }}
                    </p>
                </div>

                <div v-if="exportStore.exportResult?.value" class="export-result">
                    <el-alert :title="$t('playlist.export.modal.success_title')" type="success" :closable="false" show-icon>
                        <template #default>
                            <div class="export-stats">
                                <p>{{ $t('playlist.export.modal.exported_tracks', { count: exportStore.exportResult.value.exported_tracks }) }}</p>
                                <p v-if="exportStore.exportResult.value.failed_tracks > 0" class="failed-tracks">
                                    {{ $t('playlist.export.modal.failed_tracks', { count: exportStore.exportResult.value.failed_tracks }) }}
                                </p>
                                <el-button type="primary" size="small" @click="openPlaylistUrl" class="open-playlist-btn">
                                    {{ $t('playlist.export.modal.open_playlist') }}
                                </el-button>
                            </div>
                        </template>
                    </el-alert>
                </div>

                <div v-if="exportStore.exportError?.value" class="export-error">
                    <el-alert :title="$t('playlist.export.modal.error_title')" type="error" :closable="false" show-icon>
                        <template #default>
                            <p>{{ $t(exportStore.exportError.value) }}</p>
                        </template>
                    </el-alert>
                </div>
            </div>

            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="closeModal">
                        {{ $t('common.close') }}
                    </el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<style scoped>
    .playlist-export {
        display: inline-block;
    }

    .export-button {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .export-content {
        padding: 20px 0;
    }

    .export-description {
        margin-bottom: 20px;
        color: var(--el-text-color-regular);
        line-height: 1.5;
    }

    .export-progress {
        margin: 20px 0;
    }

    .progress-text {
        margin-top: 8px;
        text-align: center;
        color: var(--el-text-color-secondary);
        font-size: 14px;
    }

    .export-result {
        margin: 20px 0;
    }

    .export-stats {
        margin-top: 12px;
    }

    .export-stats p {
        margin: 4px 0;
    }

    .failed-tracks {
        color: var(--el-color-warning);
    }

    .open-playlist-btn {
        margin-top: 12px;
    }

    .export-error {
        margin: 20px 0;
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
</style>
