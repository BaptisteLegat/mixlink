<script setup>
    import { ref, reactive } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { ElMessage } from 'element-plus';
    import EmailIcon from 'vue-material-design-icons/Email.vue';
    import PhoneIcon from 'vue-material-design-icons/Phone.vue';
    import MapMarkerIcon from 'vue-material-design-icons/MapMarker.vue';
    import SendIcon from 'vue-material-design-icons/Send.vue';
    import { sendContactEmail } from '@/services/emailService';

    const { t } = useI18n();

    const form = reactive({
        name: '',
        email: '',
        subject: '',
        message: '',
    });

    const rules = reactive({
        name: [{ required: true, message: t('contact.form.validation.name_required'), trigger: 'blur' }],
        email: [
            { required: true, message: t('contact.form.validation.email_required'), trigger: 'blur' },
            { type: 'email', message: t('contact.form.validation.email_valid'), trigger: ['blur', 'change'] },
        ],
        subject: [{ required: true, message: t('contact.form.validation.subject_required'), trigger: 'blur' }],
        message: [
            { required: true, message: t('contact.form.validation.message_required'), trigger: 'blur' },
            { min: 10, message: t('contact.form.validation.message_min'), trigger: 'blur' },
        ],
    });

    const formRef = ref(null);
    const isLoading = ref(false);

    const submitForm = async () => {
        if (!formRef.value) return;

        await formRef.value.validate(async (valid) => {
            if (valid) {
                isLoading.value = true;

                try {
                    await sendContactEmail(form);

                    ElMessage({
                        message: t('contact.form.success_message'),
                        type: 'success',
                    });

                    formRef.value.resetFields();
                } catch (error) {
                    console.error('Error submitting form:', error);
                    ElMessage({
                        message: t(error.message),
                        type: 'error',
                    });
                } finally {
                    isLoading.value = false;
                }
            }
        });
    };
</script>

<template>
    <el-container class="contact-container">
        <el-space direction="vertical" class="contact-section" :fill="true" :size="30">
            <el-row justify="center">
                <el-col :span="24" :lg="18" :xl="16">
                    <el-text tag="h2" class="section-title">{{ t('contact.title') }}</el-text>
                    <el-text tag="p" size="large" class="section-subtitle">{{ t('contact.description') }}</el-text>
                </el-col>
            </el-row>

            <el-row :gutter="32" justify="center">
                <el-col :xs="24" :sm="24" :md="14" :lg="12" :xl="12">
                    <el-card shadow="hover" class="contact-card form-card">
                        <el-text tag="h3" class="card-title">{{ t('contact.form.title') }}</el-text>

                        <el-form
                            ref="formRef"
                            :model="form"
                            :rules="rules"
                            label-position="top"
                            require-asterisk-position="right"
                            class="contact-form"
                        >
                            <el-form-item :label="t('contact.form.name')" prop="name">
                                <el-input v-model="form.name" :placeholder="t('contact.form.name_placeholder')" size="large" />
                            </el-form-item>

                            <el-form-item :label="t('contact.form.email')" prop="email">
                                <el-input v-model="form.email" :placeholder="t('contact.form.email_placeholder')" type="email" size="large" />
                            </el-form-item>

                            <el-form-item :label="t('contact.form.subject')" prop="subject">
                                <el-input v-model="form.subject" :placeholder="t('contact.form.subject_placeholder')" size="large" />
                            </el-form-item>

                            <el-form-item :label="t('contact.form.message')" prop="message">
                                <el-input
                                    v-model="form.message"
                                    :placeholder="t('contact.form.message_placeholder')"
                                    type="textarea"
                                    :rows="5"
                                    resize="none"
                                    size="large"
                                />
                            </el-form-item>

                            <el-form-item>
                                <el-button type="primary" @click="submitForm" :loading="isLoading" size="large" class="submit-button">
                                    <SendIcon :size="18" class="icon" />
                                    {{ t('contact.form.submit') }}
                                </el-button>
                            </el-form-item>
                        </el-form>
                    </el-card>
                </el-col>

                <el-col :xs="24" :sm="24" :md="10" :lg="6" :xl="6" class="column-spacing">
                    <el-card shadow="hover" class="contact-card info-card">
                        <el-text tag="h3" class="card-title info-card-title">{{ t('contact.info_title') }}</el-text>

                        <el-space direction="vertical" :size="24" fill class="contact-info">
                            <div class="info-group">
                                <div class="info-icon">
                                    <EmailIcon :size="24" />
                                </div>
                                <div class="info-content">
                                    <el-text tag="h4" class="info-title">{{ t('contact.email_title') }}</el-text>
                                    <el-link href="mailto:contact@mixlink.com" class="info-link">contact@mixlink.com </el-link>
                                </div>
                            </div>

                            <div class="info-group">
                                <div class="info-icon">
                                    <PhoneIcon :size="24" />
                                </div>
                                <div class="info-content">
                                    <el-text tag="h4" class="info-title">{{ t('contact.phone_title') }}</el-text>
                                    <el-link href="tel:+33123456789" class="info-link">+33 7 81 66 41 82</el-link>
                                </div>
                            </div>

                            <div class="info-group">
                                <div class="info-icon">
                                    <MapMarkerIcon :size="24" />
                                </div>
                                <div class="info-content">
                                    <el-text tag="h4" class="info-title">{{ t('contact.address_title') }}</el-text>
                                    <el-text class="info-text">
                                        6 Cr de Verdun Rambaud<br />
                                        69002 Lyon, France
                                    </el-text>
                                </div>
                            </div>
                        </el-space>
                    </el-card>
                </el-col>
            </el-row>
        </el-space>
    </el-container>
</template>

<style lang="scss" scoped>
    .contact-container {
        overflow: hidden;
        position: relative;
        margin: 0 auto;
        max-width: 1440px;
    }

    .contact-section {
        position: relative;
        z-index: 1;
        padding: 60px 16px;
        width: 100%;
    }

    .section-title {
        font-size: 2rem;
        margin-bottom: 16px;
        font-weight: 700;
        color: #6023c0;
        display: block;
        text-align: center;

        @media (min-width: 768px) {
            font-size: 2.2rem;
        }
    }

    .section-subtitle {
        display: block;
        text-align: center;
        line-height: 1.6;
        max-width: 700px;
        margin: 0 auto 20px;
        font-size: 1.1rem;
    }

    .contact-card {
        border-radius: 16px;
        height: 100%;
        transition: transform 0.3s ease;

        &:hover {
            transform: translateY(-5px);
        }
    }

    .form-card {
        padding: 20px;
    }

    .info-card {
        background: var(--el-bg-color);
        padding: 20px;
        border: 1px solid var(--el-border-color-light);

        @media (prefers-color-scheme: dark) {
            background: linear-gradient(135deg, #3a1b71, #2a2a2a);
        }
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 24px;
        display: block;
        text-align: center;
    }

    .contact-form {
        margin-top: 24px;
    }

    .submit-button {
        width: 100%;
        height: 50px;
        margin-top: 12px;
        font-weight: 600;

        .icon {
            margin-right: 8px;
        }
    }

    .contact-info {
        margin-top: 28px;
    }

    .info-group {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .info-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background-color: rgba(96, 35, 192, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .info-content {
        flex: 1;
    }

    .info-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
        color: white;
    }

    .info-text {
        line-height: 1.6;
        display: block;
        color: white;
    }

    .info-link {
        display: block;
        margin-bottom: 4px;
        color: white;

        &:last-child {
            margin-bottom: 0;
        }
    }

    .column-spacing {
        margin-bottom: 24px;
    }

    @media (max-width: 768px) {
        .contact-section {
            padding: 40px 16px;
        }

        .info-card {
            margin-top: 12px;
        }

        .column-spacing {
            margin-bottom: 32px;

            &:last-child {
                margin-bottom: 0;
            }
        }
    }
</style>
