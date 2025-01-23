<script setup>
    import { ref } from 'vue';

    const email = ref('');
    const password = ref('');

    function login() {
        console.log(`Email: ${email.value}, Password: ${password.value}`);
    // Ajouter votre logique de connexion ici
    }
</script>
<template>
    <div class="login-page">
        <h1>{{ $t('login.title') }}</h1>
        <el-form>
            <el-form-item label="{{ $t('login.email') }}">
                <el-input type="email" v-model="email" placeholder="you@example.com" />
            </el-form-item>
            <el-form-item label="{{ $t('login.password') }}">
                <el-input type="password" v-model="password" placeholder="********" />
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="login">{{ $t('login.submit') }}</el-button>
            </el-form-item>
        </el-form>
    </div>
</template>
<style scoped>
    .login-page {
        max-width: 400px;
        margin: 0 auto;
        padding: 20px;
        text-align: center;
    }
</style>
