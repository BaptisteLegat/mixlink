<script setup>
    import { ref, computed, watch } from 'vue';
    import { ElMessage, ElMessageBox } from 'element-plus';
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
        hasBeenExported: {
            type: Boolean,
            default: false,
        },
        isFreePlan: {
            type: Boolean,
            default: false,
        },
        exportedPlaylistUrl: {
            type: String,
            default: null,
        },
    });

    const exportStore = usePlaylistExportStore();

    const showExportModal = ref(false);
    const localHasBeenExported = ref(props.hasBeenExported);

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
        return props.songsCount === 0 || (props.isFreePlan && localHasBeenExported.value);
    });

    const buttonTitle = computed(() => {
        if (props.songsCount === 0) {
            return t('playlist.export.disabled.no_songs');
        }
        if (props.isFreePlan && localHasBeenExported.value) {
            return t('playlist.export.disabled.already_exported');
        }
        return t('playlist.export.button');
    });

    const exportDescription = computed(() => {
        if (exportStore.exportResult?.value) {
            return t('playlist.export.modal.description_completed');
        }
        if (exportStore.exportError?.value) {
            return t('playlist.export.modal.description_error');
        }
        if (exportStore.isExporting.value) {
            return t('playlist.export.modal.description');
        }
        return t('playlist.export.modal.description_waiting');
    });

    const exportPlaylist = async () => {
        if (isButtonDisabled.value) {
            return;
        }

        if (props.isFreePlan && !localHasBeenExported.value) {
            try {
                await ElMessageBox.confirm(t('playlist.export.confirmation.message'), t('playlist.export.confirmation.title'), {
                    confirmButtonText: t('playlist.export.confirmation.confirm'),
                    cancelButtonText: t('common.cancel'),
                    type: 'warning',
                });
            } catch (err) {
                if ('cancel' === err) {
                    return;
                }

                ElMessage.error(t('playlist.export.confirmation.error'));
            }
        }

        if (props.isFreePlan && !localHasBeenExported.value) {
            showExportModal.value = true;
        }

        exportStore.resetExportState();

        try {
            const result = await exportStore.exportPlaylist(props.playlistId, props.platform);

            if (props.isFreePlan) {
                localHasBeenExported.value = true;
            }

            if (showExportModal.value) {
                setTimeout(() => {
                    showExportModal.value = false;
                }, 2000);
            }

            let successMessage = t('playlist.export.modal.exported_tracks', { count: result.exported_tracks });
            if (result.failed_tracks > 0) {
                successMessage += ` (${t('playlist.export.modal.failed_tracks', { count: result.failed_tracks })})`;
            }

            ElMessage.success(successMessage);
        } catch (error) {
            console.error('Export failed:', error);
        }
    };

    const openPlaylistUrl = () => {
        const url = exportStore.exportResult?.value?.playlist_url || props.exportedPlaylistUrl;
        if (url) {
            window.open(url, '_blank');
        }
    };

    const hasPlaylistUrl = computed(() => {
        return !!(exportStore.exportResult?.value?.playlist_url || props.exportedPlaylistUrl);
    });

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

    watch(
        () => props.hasBeenExported,
        (newValue) => {
            localHasBeenExported.value = newValue;
        }
    );
</script>
<template>
    <div class="playlist-export">
        <div class="export-buttons">
            <div class="export-button-container">
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

                <div v-if="exportStore.isExporting.value && !props.isFreePlan" class="button-progress">
                    <el-progress
                        :percentage="Math.round(exportStore.exportProgress.value)"
                        :status="exportStatus"
                        :stroke-width="4"
                        :show-text="false"
                    />
                    <p class="progress-text-button">
                        {{
                            $t('playlist.export.modal.progress', {
                                current: Math.floor((exportStore.exportProgress.value / 100) * props.songsCount),
                                total: props.songsCount,
                            })
                        }}
                    </p>
                </div>
            </div>

            <el-button v-if="hasPlaylistUrl" type="success" @click="openPlaylistUrl" class="open-playlist-external-btn">
                <el-icon><Download /></el-icon>
                {{ $t('playlist.export.modal.open_playlist') }}
            </el-button>
        </div>

        <div v-if="exportStore.exportResult?.value" class="export-results-section">
            <el-alert :title="$t('playlist.export.modal.success_title')" type="success" :closable="false" show-icon>
                <template #default>
                    <div class="export-stats">
                        <p>{{ $t('playlist.export.modal.exported_tracks', { count: exportStore.exportResult.value.exported_tracks }) }}</p>
                        <p v-if="exportStore.exportResult.value.failed_tracks > 0" class="failed-tracks">
                            {{ $t('playlist.export.modal.failed_tracks', { count: exportStore.exportResult.value.failed_tracks }) }}
                        </p>
                    </div>
                </template>
            </el-alert>
        </div>

        <div v-if="exportStore.exportError?.value" class="export-error-section">
            <el-alert :title="$t('playlist.export.modal.error_title')" type="error" :closable="false" show-icon>
                <template #default>
                    <p>{{ $t(exportStore.exportError.value) }}</p>
                </template>
            </el-alert>
        </div>

        <el-dialog v-model="showExportModal" :title="$t('playlist.export.modal.title')" width="500px" :close-on-click-modal="false">
            <div class="export-content">
                <p class="export-description">
                    {{ exportDescription }}
                </p>

                <div v-if="exportStore.isExporting.value" class="export-progress">
                    <el-progress :percentage="Math.round(exportStore.exportProgress.value)" :status="exportStatus" :stroke-width="8" />
                    <p class="progress-text">
                        {{
                            $t('playlist.export.modal.progress', {
                                current: Math.floor((exportStore.exportProgress.value / 100) * props.songsCount),
                                total: props.songsCount,
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

    .export-buttons {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .export-button-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 200px;
    }

    .button-progress {
        margin-top: 8px;
        width: 100%;
        max-width: 200px;
    }

    .progress-text-button {
        margin-top: 4px;
        text-align: center;
        color: var(--el-text-color-secondary);
        font-size: 12px;
        line-height: 1.2;
    }

    .export-button,
    .open-playlist-external-btn {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .export-results-section,
    .export-error-section {
        margin-top: 16px;
        max-width: 400px;
    }

    .export-results-section .export-stats {
        margin-top: 8px;
    }

    .export-results-section .export-stats p {
        margin: 4px 0;
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

    .export-error {
        margin: 20px 0;
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
</style>
