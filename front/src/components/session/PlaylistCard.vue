<script setup>
    import { useI18n } from 'vue-i18n';
    import MusicIcon from 'vue-material-design-icons/Music.vue';

    const { t } = useI18n();
</script>
<template>
    <el-card class="playlist-card">
        <template #header>
            <div class="playlist-header">
                <MusicIcon style="margin-right: 8px" />
                {{ t('session.playlist.title') }}
            </div>
        </template>
        <div class="playlist-content">
            <el-empty :description="t('session.playlist.empty')">
                <template #image>
                    <MusicIcon style="width: 60px; height: 60px; color: #909399" />
                </template>
                <template #description>
                    <p>{{ t('session.playlist.empty_description') }}</p>
                </template>
            </el-empty>
        </div>
    </el-card>
</template>
<style scoped>
    .playlist-header {
        display: flex;
        align-items: center;
        font-weight: 600;
    }
    .playlist-content {
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    @media (max-width: 768px) {
        .playlist-header {
            font-size: 16px;
        }
        .playlist-content {
            min-height: 200px;
        }
    }
</style>
