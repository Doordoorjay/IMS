<template>
    <v-container class="py-6 px-4" style="max-width: 720px; margin: auto;">
        <v-btn prepend-icon="mdi-arrow-left" variant="tonal" class="mb-6" @click="router.back()">
            返回
        </v-btn>

        <v-card elevation="3" class="pa-6 rounded-xl">
            <h2 class="text-h5 mb-4 font-weight-medium">系统设置</h2>

            <v-switch v-model="settings.darkMode" label="启用夜间模式" class="my-2" />
            <v-switch v-model="settings.showBackendStatus" label="显示后端状态指示灯" class="my-2" />

            <v-divider class="my-6" />

            <h3 class="text-subtitle-1 mb-3 font-weight-medium">储存位置管理</h3>

            <v-row class="mb-2">
                <v-col cols="9">
                    <v-text-field v-model="newLocation" label="新增储存位置名称" @keyup.enter="addLocation" outlined dense
                        clearable />
                </v-col>
                <v-col cols="3" class="d-flex align-end">
                    <v-btn color="primary" block @click="addLocation">添加</v-btn>
                </v-col>
            </v-row>

            <v-list lines="one">
                <v-list-item v-for="(loc, index) in locations" :key="loc.id" class="rounded-lg bg-grey-lighten-4 mb-2">
                    <template #prepend>
                        <v-avatar color="primary" size="28" class="mr-2">{{ index + 1 }}</v-avatar>
                    </template>
                    <v-list-item-title>{{ loc.name }}</v-list-item-title>
                    <template #append>
                        <v-btn icon color="error" @click="removeLocation(loc.id)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-list-item>
            </v-list>
        </v-card>

        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="1500" location="top right">
            {{ snackbarText }}
        </v-snackbar>

    </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'

const router = useRouter()
const theme = useTheme()
const API_BASE = import.meta.env.VITE_API_BASE || ''

const settings = reactive({
    darkMode: false,
    showBackendStatus: true
})

const locations = ref([])
const newLocation = ref('')
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

function showSnackbar(message, color = 'success') {
    snackbarText.value = message
    snackbarColor.value = color
    snackbar.value = true
}

const autoSaveSettings = async () => {
    try {
        const res = await fetch(`${API_BASE}/api/settings/save_settings.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings)
        })
        const result = await res.json()
        if (!result.status === 'ok') {
            showSnackbar(result.error || '自动保存失败', 'error')
        }
    } catch {
        showSnackbar('无法自动保存设置', 'error')
    }
}

// 初始化设置
onMounted(async () => {
    try {
        const res = await fetch(`${API_BASE}/api/settings/load_settings.php`)
        const data = await res.json()
        settings.darkMode = !!data.darkMode
        settings.showBackendStatus = !!data.showBackendStatus
        theme.global.name.value = settings.darkMode ? 'dark' : 'light'
        showSnackbar('设置已加载', 'info')
    } catch (err) {
        showSnackbar('无法加载设置', 'warning')
    }

    await loadLocations()
})

// 监听设置变更并自动保存
watch(() => settings.darkMode, async (val) => {
    localStorage.setItem('darkMode', JSON.stringify(val))
    theme.global.name.value = val ? 'dark' : 'light'
    await autoSaveSettings()
})
watch(() => settings.showBackendStatus, async (val) => {
    localStorage.setItem('showBackendStatus', JSON.stringify(val))
    await autoSaveSettings()
})

// 加载位置
const loadLocations = async () => {
    try {
        const res = await fetch(`${API_BASE}/api/locations/load_locations.php`)
        const data = await res.json()
        if (data.success) {
            locations.value = data.locations
        } else {
            showSnackbar('加载位置失败：' + (data.error || '未知错误'), 'error')
        }
    } catch {
        showSnackbar('无法加载储存位置', 'error')
    }
}

// 添加位置
const addLocation = async () => {
    const name = newLocation.value.trim()
    if (!name) return showSnackbar('位置名称不能为空', 'warning')
    if (locations.value.some(loc => loc.name === name)) {
        return showSnackbar('该位置已存在', 'warning')
    }

    try {
        const res = await fetch(`${API_BASE}/api/locations/add_location.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name })
        })
        const result = await res.json()
        if (result.success) {
            newLocation.value = ''
            await loadLocations()
            showSnackbar('位置已添加')
        } else {
            showSnackbar(result.error || '添加失败', 'error')
        }
    } catch {
        showSnackbar('无法添加位置', 'error')
    }
}

// 删除位置
const removeLocation = async (id) => {
    try {
        const res = await fetch(`${API_BASE}/api/locations/delete_location.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
        const result = await res.json()
        if (result.success) {
            await loadLocations()
            showSnackbar('位置已删除')
        } else {
            showSnackbar(result.error || '删除失败', 'error')
        }
    } catch {
        showSnackbar('无法删除位置', 'error')
    }
}
</script>
<style scoped>
.v-snackbar__wrapper {
    pointer-events: none;
}
</style>
