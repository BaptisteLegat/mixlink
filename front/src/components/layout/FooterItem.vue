<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import FacebookIcon from 'vue-material-design-icons/Facebook.vue';
    import TwitterIcon from 'vue-material-design-icons/Twitter.vue';
    import InstagramIcon from 'vue-material-design-icons/Instagram.vue';
    import LinkedinIcon from 'vue-material-design-icons/Linkedin.vue';

    const { t } = useI18n();
</script>
<template>
    <el-footer style="border-top: 1px solid #ebeef5; padding: 20px 0">
        <el-row justify="center" align="middle">
            <el-col :span="24" style="text-align: center">
                <el-link :underline="false" href="/">
                    <h1 :class="isDark ? 'secondary-dark' : 'secondary'">mix</h1>
                    <el-image :src="isDark ? 'logo-dark.svg' : 'logo.svg'" alt="mixlink" style="width: 40px; height: 40px" />
                    <h1 :class="isDark ? 'primary-dark' : 'primary'">link</h1>
                </el-link>
            </el-col>
            <el-col :span="24" style="text-align: center">
                <el-link :underline="false" href="https://facebook.com" target="_blank" style="margin-right: 20px">
                    <FacebookIcon style="width: 20px; height: 20px" />
                </el-link>
                <el-link :underline="false" href="https://twitter.com" target="_blank" style="margin-right: 20px">
                    <TwitterIcon style="width: 20px; height: 20px" />
                </el-link>
                <el-link :underline="false" href="https://instagram.com" target="_blank" style="margin-right: 20px">
                    <InstagramIcon style="width: 20px; height: 20px" />
                </el-link>
                <el-link :underline="false" href="https://linkedin.com" target="_blank">
                    <LinkedinIcon style="width: 20px; height: 20px" />
                </el-link>
            </el-col>
            <el-col :span="24" style="text-align: center; margin-top: 20px">
                <el-link :underline="false" href="/terms" style="margin-right: 20px">
                    {{ t('footer.terms') }}
                </el-link>
                <el-link :underline="false" href="/privacy" style="margin-right: 20px">
                    {{ t('footer.privacy') }}
                </el-link>
                <el-link :underline="false" href="/contact">
                    {{ t('footer.contact') }}
                </el-link>
            </el-col>
        </el-row>
    </el-footer>
</template>
