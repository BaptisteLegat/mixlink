<script setup>
    import { ref, onMounted } from 'vue';
    import { useMotion } from '@vueuse/motion';
    import HeroSection from '@/components/home/HeroSection.vue';
    import HowItWorks from '@/components/home/HowItWorks.vue';
    import FeaturesSection from '@/components/home/FeaturesSection.vue';
    import PricingSection from '@/components/home/PricingSection.vue';
    import CtaSection from '@/components/home/CtaSection.vue';

    const heroRef = ref(null);
    const featuresRef = ref(null);
    const pricingRef = ref(null);
    const ctaRef = ref(null);

    onMounted(() => {
        useMotion(heroRef, {
            initial: { opacity: 0, y: 100 },
            enter: { opacity: 1, y: 0, transition: { duration: 800 } },
        });

        useMotion(featuresRef, {
            initial: { opacity: 0 },
            enter: {
                opacity: 1,
                transition: {
                    delay: 300,
                    duration: 800,
                },
            },
        });

        useMotion(pricingRef, {
            initial: { opacity: 0 },
            enter: {
                opacity: 1,
                transition: {
                    delay: 600,
                    duration: 800,
                },
            },
        });

        useMotion(ctaRef, {
            initial: { opacity: 0, scale: 0.9 },
            enter: {
                opacity: 1,
                scale: 1,
                transition: {
                    delay: 900,
                    duration: 800,
                },
            },
        });
    });
</script>

<template>
    <el-container class="landing-page">
        <el-main>
            <div ref="heroRef">
                <HeroSection />
            </div>
            <div ref="featuresRef">
                <HowItWorks />
                <FeaturesSection />
            </div>
            <div ref="pricingRef">
                <PricingSection />
            </div>
            <div ref="ctaRef">
                <CtaSection />
            </div>
        </el-main>
    </el-container>
</template>

<style lang="scss" scoped>
    .landing-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
</style>
