import SpotifyIcon from 'vue-material-design-icons/Spotify.vue';
import GoogleIcon from 'vue-material-design-icons/Google.vue';
import AppleIcon from 'vue-material-design-icons/Apple.vue';
import EmailIcon from 'vue-material-design-icons/Email.vue';

export function useProviderIcons() {
    const getProviderIcon = (providerName) => {
        switch (providerName.toLowerCase()) {
            case 'spotify':
                return SpotifyIcon;
            case 'google':
                return GoogleIcon;
            case 'apple':
                return AppleIcon;
            default:
                return EmailIcon;
        }
    };

    const getProviderDisplayName = (providerName) => {
        return providerName.charAt(0).toUpperCase() + providerName.slice(1);
    };

    return {
        getProviderIcon,
        getProviderDisplayName,
    };
}
