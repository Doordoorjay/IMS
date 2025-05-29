<template>
    <AppNavbar />
    <v-main>
        <v-container class="py-6">
            <!-- 分类 Tabs -->
            <v-tabs v-model="currentTab" background-color="grey-lighten-4" grow>
                <v-tab v-for="(label, status) in statusMap" :key="status" @click="loadItems(status)">
                    {{ label }}
                </v-tab>
            </v-tabs>

            <!-- 总数量统计 -->
            <div class="text-subtitle-1 font-weight-medium my-4 text-grey-darken-2">
                当前列表总数：{{ items.length }} 项
            </div>

            <!-- 列表 -->
            <v-row class="mt-4" dense>
                <v-col v-for="item in items" :key="item.code" cols="12" md="6" lg="4">
                    <v-card class="rounded-xl d-flex justify-space-between align-center" elevation="2"
                        @click="selectItem(item)">
                        <div class="flex-grow-1 pa-4">
                            <v-card-title>{{ item.name }}</v-card-title>
                            <v-card-subtitle class="text-caption">Code: {{ item.code }}</v-card-subtitle>
                            <v-card-text class="text-sm">
                                <div><strong>来源:</strong> {{ item.source || '无' }}</div>
                                <div><strong>储存位置:</strong> {{ locationMap[item.location_id] || '无' }}</div>

                                <v-divider class="my-2"></v-divider>

                                <div><strong></strong>
                                    <div v-if="item.last_action_date">
                                        <strong>操作时间:</strong> {{ item.last_action_date }}
                                    </div>
                                    <v-chip size="x-small" :color="statusColor(item.status)" dark>
                                        {{ statusMap[item.status] || item.status }}
                                    </v-chip>
                                </div>

                            </v-card-text>
                        </div>
                        <v-img :src="getImageUrl(item.photo_url) || '/default.png'"
                            style="width: 100px; height: 100px; object-fit: cover" class="ma-4 rounded" />
                    </v-card>
                </v-col>
            </v-row>


            <!-- 弹窗详情 -->
            <v-dialog v-model="dialog" max-width="500px">
                <v-card v-if="selected">
                    <v-card-title>{{ selected.name }}</v-card-title>
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" sm="6">
                                <div class="text-body-1 mb-2"><strong>Code:</strong> {{ selected.code }}</div>
                                <div class="text-body-1 mb-2"><strong>UPC:</strong> {{ selected.UPC || 'N/A' }}</div>
                                <div class="text-body-1 mb-2"><strong>来源:</strong> {{ selected.source || '无' }}</div>
                                <div class="text-body-1 mb-2"><strong>活动:</strong> {{ selected.venue || '无' }}</div>
                                <div class="text-body-1 mb-2"><strong>位置:</strong> {{ locationMap[selected.location_id]
                                    || '无' }}</div>
                                <div class="text-body-1 mb-2"><strong>录入时间:</strong> {{ selected.received_at || 'N/A' }}
                                </div>
                            </v-col>

                            <v-col cols="12" sm="6">
                                <div class="text-body-1 mb-2"><strong>状态:</strong>
                                    <v-chip size="small" :color="statusColor(selected.status)" dark>
                                        {{ statusMap[selected.status] || selected.status }}
                                    </v-chip>
                                </div>
                                <div v-if="selected.last_action_date" class="text-body-1 mb-2">
                                    <strong>操作时间:</strong> {{ selected.last_action_date }}
                                </div>
                            </v-col>
                        </v-row>

                        <!-- 图片展示 -->
                        <div class="mt-4 text-center">
                            <v-img :src="getImageUrl(selected.photo_url) || '/default.png'" max-width="200"
                                aspect-ratio="2/3" class="rounded" />
                        </div>
                    </v-card-text>

                    <v-card-actions>
                        <v-btn text @click="dialog = false">关闭</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </v-main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppNavbar from '@/components/AppNavbar.vue'

const API_BASE = import.meta.env.VITE_API_BASE
const currentTab = ref(0)
const items = ref([])
const dialog = ref(false)
const selected = ref(null)

const getImageUrl = (path) =>
    path ? (path.startsWith('/') ? API_BASE + path : `${API_BASE}/uploads/${path}`) : '/default.png'

const statusMap = {
    available: '在库',
    given: '已送出',
    lost: '已丢失',
    used: '已使用'
}
const locationMap = ref({})


async function loadLocations() {
    try {
        const res = await fetch(`${API_BASE}/api/locations/load_locations.php`)
        const data = await res.json()
        if (data.success && Array.isArray(data.locations)) {
            locationMap.value = Object.fromEntries(
                data.locations.map(loc => [Number(loc.id), loc.name])
            )
        }
    } catch (e) {
        console.warn('位置加载失败：', e)
    }
}

onMounted(() => {
    loadLocations()
})

function actionText(type) {
    switch (type) {
        case 'given': return '已送出'
        case 'used': return '已使用'
        case 'lost': return '已丢失'
        default: return ''
    }
}

function loadItems(status = 'available') {
    fetch(`${API_BASE}/api/item/list.php?status=${status}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                items.value = data.items
            } else {
                alert('加载失败：' + data.error)
            }
        })
}

function selectItem(item) {
    selected.value = item
    dialog.value = true
}

function statusColor(status) {
    switch (status) {
        case 'available': return 'green'
        case 'given': return 'blue-grey'
        case 'lost': return 'red'
        case 'used': return 'grey'
        default: return 'grey'
    }
}

loadItems() // 初始加载
</script>
