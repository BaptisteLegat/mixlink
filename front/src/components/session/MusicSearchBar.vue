<script setup>
    import { ref, computed, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMusicSearchStore } from '@/stores/musicSearchStore';
    import { useSessionStore } from '@/stores/sessionStore';
    import { ElMessage } from 'element-plus';

    const { t } = useI18n();
    const searchStore = useMusicSearchStore();
    const sessionStore = useSessionStore();
    const searchInput = ref('');
    const showAll = ref(false);
    const debounceTimeout = ref(null);

    const MIN_QUERY_LENGTH = 3;
    const PAGE_SIZE = 6;

    const paginatedResults = computed(() => {
        if (showAll.value) return searchStore.results;
        return searchStore.results.slice(0, PAGE_SIZE);
    });

    function handleSearch() {
        if (searchInput.value.trim().length < MIN_QUERY_LENGTH) {
            ElMessage.warning(t('music_search.input_required'));
            return;
        }
        searchStore.searchMusic(searchInput.value.trim());
        showAll.value = false;
    }

    function handleAdd(track) {
        // On suppose que la playlist courante est dans sessionStore.currentSession.playlist
        const playlist = sessionStore.currentSession?.playlist;
        if (!playlist || !playlist.id) {
            ElMessage.error(t('playlist.add_song.no_playlist'));
            return;
        }
        const songData = {
            spotifyId: track.spotifyId || track.id,
            title: track.name,
            artists: Array.isArray(track.artists) ? track.artists.join(', ') : track.artists,
            image: track.image,
        };
        sessionStore
            .addSongToPlaylist(playlist.id, songData)
            .then(() => {
                ElMessage.success(t('playlist.add_song.success'));
            })
            .catch((err) => {
                if (err.errors && Array.isArray(err.errors)) {
                    const msg = err.errors.map((e) => t(e.message)).join('\n');
                    ElMessage.error(msg);
                    return;
                }
                let translated = t(err.message);
                ElMessage.error(
                    translated !== err.message && translated !== 'session.' + err.message
                        ? translated
                        : t('playlist.add_song.error')
                );
            });
    }

    function handleShowMore() {
        showAll.value = true;
    }

    function isSongInPlaylist(track) {
        const playlist = sessionStore.currentSession?.playlist;
        if (!playlist || !playlist.songs) return false;
        return playlist.songs.some((song) => song.spotifyId === (track.spotifyId || track.id));
    }

    // Recherche dynamique avec debounce
    watch(searchInput, (val) => {
        if (debounceTimeout.value) clearTimeout(debounceTimeout.value);
        if (val.trim().length >= MIN_QUERY_LENGTH) {
            debounceTimeout.value = setTimeout(() => {
                searchStore.searchMusic(val.trim());
                showAll.value = false;
            }, 400);
        } else {
            searchStore.clearResults();
        }
    });
</script>
<template>
    <div class="music-search-bar">
        <el-input
            v-model="searchInput"
            :placeholder="t('music_search.placeholder')"
            size="large"
            @keyup.enter="handleSearch"
            clearable
            class="search-input"
        >
            <template #append>
                <el-button :loading="searchStore.isLoading" type="primary" @click="handleSearch">
                    {{ t('music_search.search_button') }}
                </el-button>
            </template>
        </el-input>
        <div v-if="searchStore.error" class="error-message">
            <el-alert :title="t(searchStore.error)" type="error" show-icon />
        </div>
        <el-skeleton v-if="searchStore.isLoading" :rows="3" animated style="margin-top: 24px" />
        <el-table
            v-else-if="searchStore.results.length > 0"
            :data="paginatedResults"
            style="width: 100%; margin-top: 20px"
            :empty-text="t('music_search.no_results')"
            class="search-results-table"
        >
            <el-table-column label="" width="60">
                <template #default="{ row }">
                    <el-image :src="row.image" :alt="row.name" style="width: 48px; height: 48px; border-radius: 8px" fit="cover" />
                </template>
            </el-table-column>
            <el-table-column :label="t('music_search.title')" prop="name" />
            <el-table-column :label="t('music_search.artists')">
                <template #default="{ row }">
                    {{ row.artists.join(', ') }}
                </template>
            </el-table-column>
            <el-table-column width="120">
                <template #default="{ row }">
                    <el-button
                        type="success"
                        size="small"
                        @click="handleAdd(row)"
                        :disabled="isSongInPlaylist(row)"
                        :title="isSongInPlaylist(row) ? t('music_search.already_in_playlist') : ''"
                    >
                        {{ t('music_search.add_button') }}
                    </el-button>
                </template>
            </el-table-column>
        </el-table>
        <div v-if="!showAll && searchStore.results.length > PAGE_SIZE" class="show-more-container">
            <el-button type="primary" @click="handleShowMore">{{ t('music_search.show_more') }}</el-button>
        </div>
    </div>
</template>
<style scoped>
    .music-search-bar {
        margin: 24px 0;
        width: 100%;
    }
    .search-input {
        width: 100%;
        max-width: 100%;
        margin-bottom: 10px;
    }
    .error-message {
        margin: 16px 0;
    }
    .search-results-table {
        margin-top: 20px;
        width: 100%;
    }
    .show-more-container {
        display: flex;
        justify-content: center;
        margin-top: 16px;
    }
    @media (max-width: 768px) {
        .search-input {
            max-width: 100%;
        }
    }
</style>
