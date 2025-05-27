<template>
    <v-app-bar flat class="elevated-navbar" height="64" :style="{
        backdropFilter: 'blur(8px)',
        background: 'rgba(255, 255, 255, 0.85)',
        borderBottom: '1px solid #e0e0e0'
    }">
        <v-container class="d-flex align-center justify-space-between">
            <!-- Logo + Title -->
            <div class="d-flex align-center">
                <v-img src="/logo.svg" height="32" contain class="mr-2" />
                <span class="text-h6 font-weight-medium text-grey-darken-3">Inventory System</span>
            </div>

            <!-- Links -->
            <div class="d-none d-md-flex align-center ga-4">
                <v-btn variant="text" to="/" class="text-grey-darken-1">主页</v-btn>
                <v-btn variant="text" to="/items" class="text-grey-darken-1">物品列表</v-btn>
                <v-btn variant="text" to="/new" class="text-grey-darken-1">新入库</v-btn>
            </div>
        </v-container>
    </v-app-bar>
</template>

<script setup>
// No logic needed for this component yet
</script>

<style scoped>
.elevated-navbar {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border-radius: 0 0 12px 12px;
    transition: background 0.3s ease;
}
</style>
