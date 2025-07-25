import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { fetchWithAuth } from '@/api.js';

export const useSessionStore = defineStore('session', () => {
    const currentSession = ref(null);
    const mySessions = ref([]);
    const isLoading = ref(false);

    const isInSession = computed(() => {
        return currentSession.value !== null;
    });

    async function createSession(sessionData) {
        isLoading.value = true;
        try {
            const response = await fetchWithAuth('/api/session', {
                method: 'POST',
                body: JSON.stringify(sessionData),
            });

            if (!response.ok) {
                throw new Error('Failed to create session');
            }

            const session = await response.json();
            mySessions.value.push(session);
            currentSession.value = session;
            return session;
        } finally {
            isLoading.value = false;
        }
    }

    async function getSessionByCode(code) {
        isLoading.value = true;
        try {
            const response = await fetchWithAuth(`/api/session/${code}`, {
                method: 'GET',
            });

            if (!response.ok) {
                throw new Error('Session not found');
            }

            const session = await response.json();
            currentSession.value = session;
            return session;
        } finally {
            isLoading.value = false;
        }
    }

    async function getMySessions() {
        isLoading.value = true;
        try {
            const response = await fetchWithAuth('/api/session/my-sessions', {
                method: 'GET',
            });

            if (!response.ok) {
                mySessions.value = [];
                currentSession.value = null;
                return [];
            }

            const sessions = await response.json();
            mySessions.value = sessions;

            if (currentSession.value) {
                const stillExists = sessions.some((s) => s.code === currentSession.value.code);
                if (!stillExists) {
                    currentSession.value = sessions.length > 0 ? sessions[0] : null;
                }
            } else {
                currentSession.value = sessions.length > 0 ? sessions[0] : null;
            }

            return sessions;
        } finally {
            isLoading.value = false;
        }
    }

    function initCurrentSessionFromProfile(profile) {
        if (profile && profile.currentSession) {
            currentSession.value = profile.currentSession;
        } else {
            currentSession.value = null;
        }
    }

    async function endSession(code) {
        isLoading.value = true;
        try {
            const response = await fetchWithAuth(`/api/session/${code}/end`, {
                method: 'POST',
            });

            if (!response.ok) {
                throw new Error('Failed to end session');
            }

            mySessions.value = mySessions.value.filter((session) => session.code !== code);

            if (currentSession.value && currentSession.value.code === code) {
                currentSession.value = null;
            }

            return true;
        } finally {
            isLoading.value = false;
        }
    }

    async function removeParticipant(code, pseudo, reason = 'leave') {
        isLoading.value = true;
        try {
            const response = await fetchWithAuth(`/api/session/${code}/remove`, {
                method: 'POST',
                body: JSON.stringify({ pseudo, reason }),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'session.remove.error');
            }

            return result;
        } finally {
            isLoading.value = false;
        }
    }

    async function joinSession(code, pseudo) {
        isLoading.value = true;
        try {
            const response = await fetchWithAuth(`/api/session/${code}/join`, {
                method: 'POST',
                body: JSON.stringify({ pseudo }),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'session.join.error');
            }

            return result;
        } finally {
            isLoading.value = false;
        }
    }

    async function getParticipants(code) {
        isLoading.value = true;
        try {
            const response = await fetchWithAuth(`/api/session/${code}/participants`, {
                method: 'GET',
            });
            if (!response.ok) {
                throw new Error('Failed to fetch participants');
            }
            const data = await response.json();
            return data.participants || [];
        } finally {
            isLoading.value = false;
        }
    }

    async function checkGuestSession(code, pseudo) {
        try {
            const participants = await getParticipants(code);
            return participants.some((p) => p.pseudo === pseudo);
        } catch {
            return false;
        }
    }

    function leaveCurrentSession() {
        currentSession.value = null;
    }

    function setCurrentSession(session) {
        currentSession.value = session;
    }

    return {
        currentSession,
        mySessions,
        isLoading,
        isInSession,
        createSession,
        getSessionByCode,
        getMySessions,
        endSession,
        joinSession,
        removeParticipant,
        leaveCurrentSession,
        setCurrentSession,
        initCurrentSessionFromProfile,
        getParticipants,
        checkGuestSession,
    };
});
