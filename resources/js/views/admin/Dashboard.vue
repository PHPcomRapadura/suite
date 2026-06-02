<script setup>
const logoSrc = `${import.meta.env.BASE_URL}images/PHPcomRapadura_color.svg`
</script>

<template>
    <div class="min-h-screen bg-(--color-bg) flex items-center justify-center">
        <div class="text-center">
            <img :src="logoSrc" alt="PHP com Rapadura" class="h-16 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-(--color-text)">Dashboard</h1>
            <p class="text-(--color-text-muted) mt-2">Bem-vindo ao painel administrativo.</p>
        </div>
    </div>
</template>
