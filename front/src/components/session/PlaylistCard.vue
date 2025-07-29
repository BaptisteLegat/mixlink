<script setup>
    import { defineProps, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import MusicIcon from 'vue-material-design-icons/Music.vue';
    import TrashIcon from 'vue-material-design-icons/TrashCan.vue';
    import { useAuthStore } from '@/stores/authStore';
    import { useSessionStore } from '@/stores/sessionStore';
    import { ElMessageBox, ElMessage } from 'element-plus';

    const { t } = useI18n();
    const props = defineProps({
        playlist: { type: Object, default: null },
    });
    const authStore = useAuthStore();
    const sessionStore = useSessionStore();
    const isAuthenticated = computed(() => authStore.isAuthenticated);

    const sortedSongs = computed(() => {
        if (!props.playlist?.songs) return [];
        return [...props.playlist.songs].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    });

    async function confirmRemoveSong(song) {
        try {
            await ElMessageBox.confirm(
                t('playlist.remove_song.confirm', { title: song.title }),
                t('playlist.remove_song.confirm_title'),
                {
                    confirmButtonText: t('common.confirm'),
                    cancelButtonText: t('common.cancel'),
                    type: 'warning',
                }
            );
            await sessionStore.removeSongFromPlaylist(props.playlist.id, song.spotifyId);
            ElMessage.success(t('playlist.remove_song.success'));
        } catch (err) {
            if (err !== 'cancel') {
                ElMessage.error(t('playlist.remove_song.error'));
            }
        }
    }
</script>

<template>
    <el-card class="playlist-card">
        <template #header>
            <div class="playlist-header">
                <MusicIcon style="margin-right: 8px" />
                <span class="playlist-title">{{ props.playlist?.name || t('playlist.title') }}</span>
                <span class="playlist-count"> {{ props.playlist?.songs?.length || 0 }} {{ t('playlist.songs_count') }} </span>
            </div>
        </template>
        <div class="playlist-content">
            <div v-if="props.playlist && props.playlist.songs && props.playlist.songs.length > 0" class="playlist-scroll">
                <ul>
                    <li v-for="(song, idx) in sortedSongs" :key="song.spotifyId" class="playlist-item">
                        <div class="song-img-wrap">
                            <img :src="song.image" class="song-image" v-if="song.image" />
                            <MusicIcon v-else class="song-image-placeholder" />
                        </div>
                        <div class="song-info">
                            <div class="song-title">{{ song.title }}</div>
                            <div class="song-artists">{{ song.artists }}</div>
                        </div>
                        <span class="song-order">{{ idx + 1 }}</span>
                        <el-button
                            v-if="isAuthenticated"
                            class="delete-btn"
                            type="danger"
                            size="medium"
                            :icon="TrashIcon"
                            @click="() => confirmRemoveSong(song)"
                            circle
                        />
                    </li>
                </ul>
            </div>
            <el-empty v-else :description="t('playlist.empty')">
                <template #image>
                    <MusicIcon style="width: 60px; height: 60px; color: #909399" />
                </template>
                <template #description>
                    <p>{{ t('playlist.empty_description') }}</p>
                </template>
            </el-empty>
        </div>
    </el-card>
</template>

<style scoped>
    .playlist-card {
        margin-bottom: 20px;
        background: var(--el-bg-color-overlay, #18181c);
        border-radius: 16px;
        box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.12);
        border: none;
    }

    .playlist-header {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    .playlist-title {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .playlist-count {
        margin-left: auto;
        color: var(--el-text-color-regular);
        font-size: 14px;
        font-weight: 400;
    }

    .playlist-content {
        max-height: 350px;
        overflow-y: auto;
        padding: 0 8px;
    }

    .playlist-scroll {
        width: 100%;
    }

    ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .playlist-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid var(--el-border-color-lighter);
        transition: background 0.2s;
        background: transparent;
    }

    .playlist-item:hover {
        background: var(--el-fill-color-light, #23232b);
    }

    .song-img-wrap {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--el-bg-color-page, #222);
        overflow: hidden;
        margin-left: 5px;
    }

    .song-image,
    .song-image-placeholder {
        width: 40px;
        height: 40px;
        object-fit: cover;
    }

    .song-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .song-title {
        font-weight: 600;
        color: var(--el-text-color-primary);
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .song-artists {
        color: var(--el-text-color-regular);
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .song-order {
        background: var(--el-bg-color-page, #28283a);
        color: var(--el-text-color-secondary, #aaa);
        border-radius: 8px;
        padding: 2px 8px;
        font-size: 13px;
        min-width: 24px;
        text-align: center;
        margin-right: 8px;
    }

    .delete-btn {
        transition: background 0.2s;
        margin-right: 5px;
    }

    .delete-btn:hover {
        background: #ff4d4f !important;
        color: #fff !important;
    }

    @media (max-width: 600px) {
        .playlist-card {
            border-radius: 8px;
        }

        .playlist-header {
            font-size: 15px;
        }

        .playlist-content {
            max-height: 220px;
            padding: 0 2px;
        }

        .playlist-item {
            gap: 8px;
            padding: 7px 0;
        }

        .song-img-wrap {
            width: 32px;
            height: 32px;
        }

        .song-image,
        .song-image-placeholder {
            width: 28px;
            height: 28px;
        }

        .song-title {
            font-size: 0.95rem;
        }

        .song-artists {
            font-size: 11px;
        }

        .song-order {
            font-size: 11px;
            min-width: 18px;
            padding: 1px 5px;
        }
    }
</style>
