import { onMounted, onUnmounted } from 'vue';
import { useI18nSEO } from './useI18nSEO';

export function useSEO(pageName = 'home') {
    const { getSEOData, locale } = useI18nSEO();

    let originalTitle = '';
    const metaTags = [];

    const setTitle = (newTitle) => {
        if (typeof document !== 'undefined') {
            document.title = `${newTitle} - mixlink`;
            document.documentElement.lang = locale.value;
        }
    };

    const setMetaTag = (name, content, property = false) => {
        if (typeof document === 'undefined') {
            return;
        }

        const selector = property ? `meta[property="${name}"]` : `meta[name="${name}"]`;
        let meta = document.querySelector(selector);

        if (!meta) {
            meta = document.createElement('meta');
            if (property) {
                meta.setAttribute('property', name);
            } else {
                meta.setAttribute('name', name);
            }
            document.head.appendChild(meta);
            metaTags.push(meta);
        }

        meta.setAttribute('content', content);
    };

    const setCanonical = (url) => {
        if (typeof document === 'undefined') {
            return;
        }

        let canonical = document.querySelector('link[rel="canonical"]');
        if (!canonical) {
            canonical = document.createElement('link');
            canonical.setAttribute('rel', 'canonical');
            document.head.appendChild(canonical);
            metaTags.push(canonical);
        }
        canonical.setAttribute('href', url);
    };

    const removeMeta = () => {
        metaTags.forEach((tag) => {
            if (tag && tag.parentNode) {
                tag.parentNode.removeChild(tag);
            }
        });
        metaTags.length = 0;

        if (originalTitle && typeof document !== 'undefined') {
            document.title = originalTitle;
        }
    };

    onMounted(() => {
        if (typeof document !== 'undefined') {
            const seoData = getSEOData(pageName);

            originalTitle = document.title;

            setTitle(seoData.title);
            setMetaTag('description', seoData.description);
            setMetaTag('keywords', seoData.keywords);
            setMetaTag('author', 'Baptiste Legat');

            setMetaTag('og:url', window.location.href, true);
            setMetaTag('og:title', `${seoData.title} - mixlink`, true);
            setMetaTag('og:description', seoData.description, true);
            setMetaTag('og:type', seoData.ogType, true);
            setMetaTag('og:image', seoData.ogImage, true);

            setMetaTag('twitter:card', 'summary_large_image');
            setMetaTag('twitter:title', `${seoData.title} - mixlink`);
            setMetaTag('twitter:description', seoData.description);
            setMetaTag('twitter:image', seoData.ogImage);

            setCanonical(window.location.href);
        }
    });

    onUnmounted(() => {
        removeMeta();
    });

    return {
        setTitle,
        setMetaTag,
        setCanonical,
    };
}
