<template>
    <div class="d-flex align-center">
        <div class="backend-status-indicator" :class="statusClass" title="后端状态"></div>
        <span class="text-caption">{{ statusText }}</span>
    </div>

    <!-- 全屏弹窗提示 -->
    <v-dialog v-model="showDialog" persistent fullscreen transition="dialog-bottom-transition">
        <v-card class="d-flex flex-column align-center justify-center text-center pa-10" color="black" dark>
            <v-icon size="64" color="red">mdi-server-off</v-icon>
            <h2 class="mt-4">后端服务不可用</h2>
            <p class="text-grey">请检查后端是否已启动或 API 地址是否正确。</p>
        </v-card>
    </v-dialog>
</template>


<script setup>
import { ref, onMounted, computed } from 'vue'
import { useIntervalFn } from '@vueuse/core'

const isOnline = ref(true)
const showDialog = ref(false)
const API_BASE = import.meta.env.VITE_API_BASE || ''

const statusText = computed(() =>
    isOnline.value ? '后端在线' : '后端离线'
)

const checkBackend = async () => {
    try {
        const res = await fetch(`${API_BASE}/api/check-install.php`, { cache: 'no-store' })
        isOnline.value = res.ok
        showDialog.value = !res.ok
    } catch (e) {
        isOnline.value = false
        showDialog.value = true
    }
}

onMounted(() => {
    checkBackend()
})

useIntervalFn(() => {
    checkBackend()
}, 10000)

const statusClass = computed(() => (isOnline.value ? 'online' : 'offline'))
</script>

<style scoped>
.backend-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 6px;
    border: 2px solid white;
    display: inline-block;
    box-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
}



.backend-status-indicator.online {
    background-color: #4caf50;
}

.backend-status-indicator.offline {
    background-color: #f44336;
}
</style>
