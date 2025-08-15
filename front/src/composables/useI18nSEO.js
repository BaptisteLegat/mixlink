import { useI18n } from 'vue-i18n';

export function useI18nSEO() {
    const { t, locale } = useI18n();

    const getSEOData = (pageName = 'home') => {
        const pageKey = t(`seo.pages.${pageName}.title`, null, { missingWarn: false, fallbackWarn: false });
        const validPageName = pageKey ? pageName : 'default';

        const title = validPageName === 'default' ? t('seo.appName') : t(`seo.pages.${validPageName}.title`);

        const description = validPageName === 'default' ? t('seo.description') : t(`seo.pages.${validPageName}.description`);

        const keywords = validPageName === 'default' ? t('seo.keywords') : t(`seo.pages.${validPageName}.keywords`);

        return {
            title,
            description,
            keywords,
            ogType: 'website',
            ogImage: '/logo.png',
        };
    };

    return {
        getSEOData,
        locale,
    };
}
