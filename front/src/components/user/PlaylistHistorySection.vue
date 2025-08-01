<script setup>
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useAuthStore } from '@/stores/authStore';
    import MusicIcon from 'vue-material-design-icons/Music.vue';
    import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue';
    import { useProviderIcons } from '@/composables/useProviderIcons';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const { getProviderIcon, getProviderDisplayName } = useProviderIcons();

    const exportedPlaylists = computed(() => {
        return authStore.exportedPlaylists || [];
    });

    const openPlaylistUrl = (url) => {
        if (url) {
            window.open(url, '_blank');
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return '';

        return new Date(dateString).toLocaleDateString();
    };

    const getPlatformFromUrl = (url) => {
        if (!url) {
            return 'unknown';
        }
        if (url.includes('spotify.com')) {
            return 'spotify';
        }
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            return 'google';
        }
        if (url.includes('soundcloud.com')) {
            return 'soundcloud';
        }

        return 'unknown';
    };
</script>
<template>
    <div class="playlist-history-section">
        <el-divider />
        <el-text tag="p" class="info-label">{{ t('profile.playlist_history.title') }}</el-text>

        <div v-if="exportedPlaylists.length > 0" class="playlists-container">
            <div v-for="playlist in exportedPlaylists" :key="playlist.id" class="playlist-item">
                <div class="playlist-header">
                    <div class="playlist-info">
                        <h4 class="playlist-name">{{ playlist.name || t('profile.playlist_history.default_name') }}</h4>
                        <div class="playlist-meta">
                            <span class="playlist-date">{{ formatDate(playlist.createdAt) }}</span>
                            <span class="playlist-songs-count">{{
                                t('profile.playlist_history.songs_count', { count: playlist.songsCount || 0 })
                            }}</span>
                            <span
                                v-if="playlist.exportedPlaylistUrl"
                                class="playlist-platform"
                                :class="`platform-${getPlatformFromUrl(playlist.exportedPlaylistUrl)}`"
                            >
                                <component :is="getProviderIcon(getPlatformFromUrl(playlist.exportedPlaylistUrl))" :size="14" />
                                {{ getProviderDisplayName(getPlatformFromUrl(playlist.exportedPlaylistUrl)) }}
                            </span>
                        </div>
                    </div>
                    <el-button
                        v-if="playlist.exportedPlaylistUrl"
                        type="primary"
                        size="small"
                        @click="openPlaylistUrl(playlist.exportedPlaylistUrl)"
                        class="open-playlist-btn"
                    >
                        <OpenInNewIcon :size="16" />
                        {{ t('profile.playlist_history.open_playlist') }}
                    </el-button>
                </div>

                <div class="songs-list" v-if="playlist.songs && playlist.songs.length > 0">
                    <div class="songs-grid">
                        <div v-for="song in playlist.songs.slice(0, 6)" :key="song.id" class="song-item">
                            <img v-if="song.image" :src="song.image" :alt="song.title" class="song-cover" />
                            <div class="song-info">
                                <span class="song-title">{{ song.title }}</span>
                                <span class="song-artist">{{ song.artists }}</span>
                            </div>
                        </div>
                        <div v-if="playlist.songs.length > 6" class="more-songs">
                            <span class="more-text">{{ t('profile.playlist_history.and_more', { count: playlist.songs.length - 6 }) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="no-playlists">
            <MusicIcon :size="48" class="no-playlists-icon" />
            <p class="no-playlists-text">{{ t('profile.playlist_history.no_playlists') }}</p>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .playlist-history-section {
        margin: 20px 0;
    }

    .info-label {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 16px;
        display: block;
        color: #6023c0;
    }

    .playlists-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
        max-height: 500px;
        overflow-y: auto;
        padding-right: 8px;

        &::-webkit-scrollbar {
            width: 6px;
        }

        &::-webkit-scrollbar-track {
            background: var(--el-fill-color-lighter);
            border-radius: 3px;
        }

        &::-webkit-scrollbar-thumb {
            background: var(--el-color-primary-light-5);
            border-radius: 3px;
        }

        &::-webkit-scrollbar-thumb:hover {
            background: var(--el-color-primary);
        }
    }

    .playlist-item {
        background-color: var(--el-color-info-light-9);
        border-radius: 8px;
        padding: 16px;
        border: 1px solid var(--el-border-color-light);
    }

    .playlist-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 12px;
    }

    .playlist-info {
        flex: 1;
        min-width: 0;
    }

    .playlist-name {
        margin: 0 0 8px 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--el-text-color-primary);
        word-break: break-word;
    }

    .playlist-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 14px;
        color: var(--el-text-color-secondary);
    }

    .playlist-platform {
        display: flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;

        &.platform-spotify {
            color: #1db954;
        }

        &.platform-google {
            color: #ff0000;
        }

        &.platform-soundcloud {
            color: #ff5500;
        }
    }

    .open-playlist-btn {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .songs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .song-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px;
        border-radius: 6px;
        background-color: var(--el-fill-color-blank);
        border: 1px solid var(--el-border-color-lighter);
    }

    .song-cover {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .song-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .song-title {
        font-weight: 500;
        font-size: 14px;
        color: var(--el-text-color-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .song-artist {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .more-songs {
        grid-column: 1 / -1;
        text-align: center;
        padding: 12px;
        color: var(--el-text-color-secondary);
        font-style: italic;
    }

    .no-playlists {
        text-align: center;
        padding: 40px 20px;
        color: var(--el-text-color-secondary);
    }

    .no-playlists-icon {
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .no-playlists-text {
        font-size: 16px;
        margin: 0;
    }

    @media (max-width: 768px) {
        .playlist-header {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .open-playlist-btn {
            align-self: flex-start;
        }

        .songs-grid {
            grid-template-columns: 1fr;
        }

        .playlist-meta {
            flex-direction: column;
            gap: 4px;
        }
    }
</style>
