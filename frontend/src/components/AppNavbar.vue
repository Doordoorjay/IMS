<template>
    <v-app-bar flat class="elevated-navbar" height="64" :style="{
        backdropFilter: 'blur(8px)',
        background: 'rgba(255, 255, 255, 0.85)',
        borderBottom: '1px solid #e0e0e0'
    }">
        <v-container class="d-flex align-center justify-space-between" fluid>
            <!-- 左侧：Logo 和标题 -->
            <div class="d-flex align-center flex-shrink-0">
                <!-- 小屏菜单按钮 -->
                <v-btn icon class="d-md-none mr-2" @click="drawer = true">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <v-icon>mdi-package-variant</v-icon>
                <span class="text-h6 font-weight-medium text-grey-darken-3">&nbsp;储存管理</span>
            </div>

            <!-- 中间：导航按钮（仅在 md 及以上显示） -->
            <div class="d-none d-md-flex align-center justify-center flex-grow-1 ga-4">
                <v-btn variant="text" to="/" class="text-grey-darken-1">主页</v-btn>
                <v-btn variant="text" to="/items" class="text-grey-darken-1">物品列表</v-btn>
                <v-btn variant="text" to="/new" class="text-grey-darken-1">新入库</v-btn>
            </div>

            <!-- 右侧：状态和设置 -->
            <div class="d-flex align-center flex-shrink-0 ga-2">
                <BackendStatus v-if="showBackendStatus" />
                <router-link to="/settings">
                    <v-btn icon>
                        <v-icon>mdi-cog</v-icon>
                    </v-btn>
                </router-link>


            </div>
        </v-container>
    </v-app-bar>

    <!-- 小屏抽屉菜单 -->
    <v-navigation-drawer v-model="drawer" temporary class="d-md-none">
        <v-list nav dense>
            <v-list-item to="/" @click="drawer = false">
                <v-list-item-title>主页</v-list-item-title>
            </v-list-item>
            <v-list-item to="/items" @click="drawer = false">
                <v-list-item-title>物品列表</v-list-item-title>
            </v-list-item>
            <v-list-item to="/new" @click="drawer = false">
                <v-list-item-title>新入库</v-list-item-title>
            </v-list-item>
        </v-list>
    </v-navigation-drawer>
</template>


<script setup>
import BackendStatus from './BackendStatus.vue'
import { ref, onMounted } from 'vue'

const drawer = ref(false)
const showBackendStatus = ref(true)

onMounted(() => {
    // 初始读取 localStorage
    const saved = localStorage.getItem('showBackendStatus')
    showBackendStatus.value = saved === null ? true : JSON.parse(saved)

    // 启动轮询检测（每 2 秒）
    setInterval(() => {
        const current = localStorage.getItem('showBackendStatus')
        const parsed = current === null ? true : JSON.parse(current)
        if (parsed !== showBackendStatus.value) {
            showBackendStatus.value = parsed
        }
    }, 2000)
})

</script>

<style scoped>
.elevated-navbar {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border-radius: 0 0 12px 12px;
    transition: background 0.3s ease;
}
</style>
