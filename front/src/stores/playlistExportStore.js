import { ref } from 'vue';
import { apiExportPlaylist } from '@/api.js';

export function usePlaylistExportStore() {
    const isExporting = ref(false);
    const exportProgress = ref(0);
    const exportResult = ref(null);
    const exportError = ref(null);
    const playlistSongsCount = ref(0);

    async function exportPlaylist(playlistId, platform = null) {
        if (!platform) {
            throw new Error('No platform available for export');
        }

        isExporting.value = true;
        exportProgress.value = 0;
        exportError.value = null;
        exportResult.value = null;

        try {
            // Simulation de progression (dans un vrai cas, vous recevriez des mises à jour en temps réel)
            const progressInterval = setInterval(() => {
                if (exportProgress.value < 90) {
                    exportProgress.value += 10;
                }
            }, 200);

            const result = await apiExportPlaylist(playlistId, platform);

            clearInterval(progressInterval);
            exportProgress.value = 100;
            exportResult.value = result;

            return result;
        } catch (error) {
            console.error('Export failed:', error);
            exportError.value = error.message || 'playlist.export.error.export_failed';
            throw error;
        } finally {
            isExporting.value = false;
        }
    }

    function resetExportState() {
        exportProgress.value = 0;
        exportResult.value = null;
        exportError.value = null;
        isExporting.value = false;
    }

    function updateSongsCount(count) {
        playlistSongsCount.value = count;
    }

    return {
        isExporting,
        exportProgress,
        exportResult,
        exportError,
        playlistSongsCount,
        exportPlaylist,
        resetExportState,
        updateSongsCount,
    };
}
