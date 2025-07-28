import { ref } from 'vue';
import { defineStore } from 'pinia';
import { searchMusicApi } from '@/api.js';

export const useMusicSearchStore = defineStore('musicSearch', () => {
    const query = ref('');
    const results = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    async function searchMusic(q) {
        query.value = q;
        isLoading.value = true;
        error.value = null;
        results.value = [];
        try {
            const data = await searchMusicApi(q);
            results.value = data;
        } catch (e) {
            error.value = e.message;
        } finally {
            isLoading.value = false;
        }
    }

    function clearResults() {
        results.value = [];
        error.value = null;
    }

    return {
        query,
        results,
        isLoading,
        error,
        searchMusic,
        clearResults,
    };
});
