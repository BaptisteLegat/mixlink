<script setup>
    import { ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import { useSEO } from '@/composables/useSEO';

    const { t } = useI18n();

    useSEO('faq');
    const activeNames = ref(['1']);

    const faqs = ref([
        {
            id: '1',
            category: 'general',
            items: [
                { key: 'what_is', question: 'faq.general.what_is.question', answer: 'faq.general.what_is.answer' },
                { key: 'how_works', question: 'faq.general.how_works.question', answer: 'faq.general.how_works.answer' },
                { key: 'platforms', question: 'faq.general.platforms.question', answer: 'faq.general.platforms.answer' },
            ],
        },
        {
            id: '2',
            category: 'account',
            items: [
                { key: 'create', question: 'faq.account.create.question', answer: 'faq.account.create.answer' },
                { key: 'delete', question: 'faq.account.delete.question', answer: 'faq.account.delete.answer' },
                { key: 'oauth', question: 'faq.account.oauth.question', answer: 'faq.account.oauth.answer' },
            ],
        },
        {
            id: '3',
            category: 'subscription',
            items: [
                { key: 'free', question: 'faq.subscription.free.question', answer: 'faq.subscription.free.answer' },
                { key: 'premium', question: 'faq.subscription.premium.question', answer: 'faq.subscription.premium.answer' },
                { key: 'cancel', question: 'faq.subscription.cancel.question', answer: 'faq.subscription.cancel.answer' },
            ],
        },
        {
            id: '4',
            category: 'technical',
            items: [
                { key: 'browser', question: 'faq.technical.browser.question', answer: 'faq.technical.browser.answer' },
                { key: 'mobile', question: 'faq.technical.mobile.question', answer: 'faq.technical.mobile.answer' },
                { key: 'api', question: 'faq.technical.api.question', answer: 'faq.technical.api.answer' },
            ],
        },
    ]);
</script>

<template>
    <el-container class="faq-container">
        <el-space direction="vertical" class="faq-section" :fill="true" :size="30">
            <el-row justify="center">
                <el-col :span="24" :md="20" :lg="18">
                    <el-text tag="h1" class="page-title">{{ t('faq.title') }}</el-text>
                    <el-divider />
                    <el-text tag="p" class="page-description">{{ t('faq.description') }}</el-text>
                </el-col>
            </el-row>

            <el-row justify="center">
                <el-col :span="24" :md="20" :lg="18">
                    <el-card shadow="hover" class="faq-card">
                        <el-collapse v-model="activeNames" accordion>
                            <template v-for="category in faqs" :key="category.id">
                                <el-collapse-item :title="t(`faq.categories.${category.category}`)" :name="category.id">
                                    <el-space direction="vertical" class="faq-items" :fill="true">
                                        <div v-for="item in category.items" :key="item.key" class="faq-item">
                                            <el-text tag="h3" class="faq-question">
                                                {{ t(item.question) }}
                                            </el-text>
                                            <el-text tag="p" class="faq-answer">
                                                {{ t(item.answer) }}
                                            </el-text>
                                        </div>
                                    </el-space>
                                </el-collapse-item>
                            </template>
                        </el-collapse>
                    </el-card>

                    <el-card shadow="hover" class="contact-card" :class="{ 'contact-card-dark': isDark }">
                        <el-text tag="h3" class="contact-title">{{ t('faq.didnt_find') }}</el-text>
                        <el-text tag="p" class="contact-description">{{ t('faq.contact_us') }}</el-text>
                        <el-button type="primary" @click="$router.push('/contact')" round size="large" class="contact-button">
                            {{ t('faq.contact_button') }}
                        </el-button>
                    </el-card>
                </el-col>
            </el-row>
        </el-space>
    </el-container>
</template>

<style lang="scss" scoped>
    .faq-container {
        overflow: hidden;
        position: relative;
        margin: 0 auto;
        max-width: 1440px;
    }

    .faq-section {
        position: relative;
        z-index: 1;
        padding: 60px 16px;
        width: 100%;
    }

    .page-title {
        font-size: 2rem;
        margin-bottom: 16px;
        font-weight: 700;
        color: var(--el-color-primary);
        display: block;
        text-align: center;

        @media (min-width: 768px) {
            font-size: 2.2rem;
        }
    }

    .page-description {
        display: block;
        text-align: center;
        color: var(--el-text-color-secondary);
        margin-bottom: 20px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
        font-size: 1.1rem;
        line-height: 1.6;
    }

    .faq-card {
        margin-bottom: 30px;
        border-radius: 12px;

        :deep(.el-collapse-item__header) {
            font-size: 1.2rem;
            font-weight: 600;
            padding: 20px;
            color: var(--el-color-primary);
        }

        :deep(.el-collapse-item__content) {
            padding: 0 20px 20px;
        }
    }

    .faq-items {
        width: 100%;
    }

    .faq-item {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid var(--el-border-color-lighter);

        &:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
    }

    .faq-question {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 12px;
        color: var(--el-text-color-primary);
        display: block;
    }

    .faq-answer {
        font-size: 1rem;
        line-height: 1.6;
        color: var(--el-text-color-regular);
        display: block;
    }

    .contact-card {
        text-align: center;
        padding: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, #f9f9f9, #f3f3f3);
        margin-bottom: 30px;

        :deep(.el-card__body) {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        &.contact-card-dark {
            background: linear-gradient(135deg, #2c2c2c, #1f1f1f);

            .contact-title {
                color: var(--el-color-primary-light-3);
            }

            .contact-description {
                color: var(--el-text-color-primary);
            }
        }
    }

    .contact-title {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: var(--el-color-primary);
        display: block;
    }

    .contact-description {
        margin-bottom: 30px;
        max-width: 600px;
        color: var(--el-text-color-regular);
        display: block;
        font-size: 1.2rem;
        line-height: 1.6;
    }

    .contact-button {
        min-width: 200px;
        height: 48px;
        font-size: 1.1rem;
        font-weight: 500;
        padding: 0 25px;
    }
</style>
