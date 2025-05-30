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
                <v-col v-for="item in items" :key="item.code" cols="12" sm="6" md="6" lg="auto" class="pa-2"
                    style="max-width: 100%; flex: 1 1 330px">
                    <v-card class="rounded-xl d-flex justify-space-between align-center" elevation="2"
                        style="overflow: visible; height: 100%; width: 100%" @click="selectItem(item)">

                        <!-- 左侧内容 -->
                        <div class="flex-grow-1 d-flex flex-column justify-space-between pa-4"
                            style="min-width: 0; max-width: calc(100% - 210px)">
                            <!-- 标题组 -->
                            <div>
                                <div class="text-subtitle-1 font-weight-medium mb-1">{{ item.name }}</div>
                                <div class="text-caption text-grey-darken-1 mb-2">Code: {{ item.code }}</div>
                                <div class="text-sm">
                                    <div>
                                        <strong>来源:</strong>
                                        {{ item.source || '无' }}<span v-if="item.venue"> - {{ item.venue }}</span>
                                    </div>
                                    <div><strong>储存位置:</strong> {{ locationMap[item.location_id] || '无' }}</div>

                                    <v-divider class="my-2" />

                                    <div v-if="item.last_action_date" class="mb-2">
                                        <strong>操作时间:</strong> {{ item.last_action_date }}
                                    </div>

                                    <div class="d-flex align-center mb-3">
                                        <v-chip size="x-small" :color="statusColor(item.status)" dark>
                                            {{ statusMap[item.status] || item.status }}
                                        </v-chip>
                                    </div>
                                </div>
                            </div>

                            <!-- 按钮组，仅在在库状态下显示 -->
                            <div v-if="item.status === 'available'" class="d-flex flex-wrap gap-2 mt-2"
                                style="column-gap: 6px; row-gap: 8px;">
                                <v-btn variant="tonal" color="purple" size="small" class="flex-grow-1"
                                    style="min-width: 110px" @click.stop="openGiveDialog(item.code)">
                                    <v-icon start>mdi-hand-heart</v-icon> 赠送出库
                                </v-btn>
                                <v-btn variant="tonal" color="error" size="small" class="flex-grow-1"
                                    style="min-width: 110px" @click.stop="openLostDialog(item.code)">
                                    <v-icon start>mdi-alert-circle</v-icon> 标记丢失
                                </v-btn>
                                <v-btn variant="tonal" color="primary" size="small" class="flex-grow-1"
                                    style="min-width: 110px" @click.stop="openConfirmUseDialog(item.code)">
                                    <v-icon start>mdi-check-circle</v-icon> 标记使用
                                </v-btn>
                                <v-btn variant="tonal" color="teal" size="small" class="flex-grow-1"
                                    style="min-width: 110px" @click.stop="openMoveDialog(item.code)">
                                    <v-icon start>mdi-map-marker</v-icon> 更改位置
                                </v-btn>
                            </div>
                        </div>

                        <!-- 右侧图片 -->
                        <v-img :src="getImageUrl(item.photo_url) || '/default.png'"
                            style="width: 180px; height: 200px; object-fit: cover; min-width: 150px"
                            class="ma-4 rounded" />
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
                                <div class="text-body-1 mb-2">
                                    <strong>状态:</strong>
                                    <v-chip size="small" :color="statusColor(selected.status)" dark>
                                        {{ statusMap[selected.status] || selected.status }}
                                    </v-chip>
                                </div>
                                <div v-if="selected.last_action_date" class="text-body-1 mb-2">
                                    <strong>操作时间:</strong> {{ selected.last_action_date }}
                                </div>
                            </v-col>
                        </v-row>

                        <!-- 🎁 赠送详情 -->
                        <v-divider class="my-3" v-if="selected.status === 'given' && selected.given_info" />
                        <div v-if="selected.status === 'given' && selected.given_info">
                            <h3 class="text-subtitle-1 font-weight-medium mb-2">🎁 赠送信息</h3>
                            <div class="text-body-2 mb-1"><strong>被赠送人:</strong> {{ selected.given_info.to || '未记录' }}
                            </div>
                            <div class="text-body-2 mb-1"><strong>赠送方式:</strong> {{ selected.given_info.method || '未记录'
                            }}</div>
                            <div class="text-body-2 mb-1"><strong>赠送类型:</strong> {{ selected.given_info.giftType ||
                                '未记录' }}
                            </div>
                            <div class="text-body-2 mb-1"><strong>赠送活动:</strong> {{ selected.given_info.event || '无' }}
                            </div>
                            <div class="text-body-2"><strong>赠送时间:</strong> {{ selected.given_info.date ||
                                selected.last_action_date }}</div>
                        </div>

                        <!-- 图片 -->
                        <div class="mt-4 text-center">
                            <v-img :src="getImageUrl(selected.photo_url) || '/default.png'" max-width="300"
                                aspect-ratio="2/3" class="rounded" />
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn text @click="dialog = false">关闭</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>


            <GiveDialog v-model:open="giveDialogOpen" :code="selectedCode" :form="giveDialogForm"
                @submitted="onActionSubmitted" @snackbar="showSnackbar" />
            <LostDialog v-model:open="lostDialogOpen" :code="selectedCode" @submitted="onActionSubmitted"
                @snackbar="showSnackbar" />
            <ConfirmUseDialog v-model:open="confirmUseDialogOpen" :code="selectedCode" @submitted="onActionSubmitted"
                @snackbar="showSnackbar" />
            <MoveDialog v-model:open="moveDialogOpen" :code="selectedCode" :locations="locationMap"
                @submitted="onActionSubmitted" @snackbar="showSnackbar" />

            <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
                {{ snackbar.message }}
            </v-snackbar>
        </v-container>
    </v-main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppNavbar from '@/components/AppNavbar.vue'
import GiveDialog from '@/components/GiveDialog.vue'
import LostDialog from '@/components/LostDialog.vue'
import ConfirmUseDialog from '@/components/ConfirmUseDialog.vue'
import MoveDialog from '@/components/MoveDialog.vue'

const API_BASE = import.meta.env.VITE_API_BASE
const currentTab = ref(0)
const items = ref([])
const selectedCode = ref(null)
const dialog = ref(false)
const selected = ref(null)
const giveDialogOpen = ref(false)
const lostDialogOpen = ref(false)
const confirmUseDialogOpen = ref(false)
const moveDialogOpen = ref(false)
const giveDialogForm = ref({})
const snackbar = ref({ show: false, message: '', color: 'success' })

function showSnackbar(payload, fallbackColor = 'success') {
    if (typeof payload === 'string') {
        snackbar.value = { show: true, message: payload, color: fallbackColor }
    } else if (typeof payload === 'object') {
        snackbar.value = {
            show: true,
            message: payload.message || '',
            color: payload.color || fallbackColor
        }
    }
}


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
            locationMap.value = Object.fromEntries(data.locations.map(loc => [Number(loc.id), loc.name]))
        }
    } catch (e) {
        console.warn('位置加载失败：', e)
    }
}

function loadItems(status = 'available') {
    fetch(`${API_BASE}/api/item/list.php?status=${status}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                items.value = data.items
            } else {
                showSnackbar('加载失败：' + data.error, 'error')
            }
        })
}

function selectItem(item) {
    selected.value = item
    dialog.value = true
}

function openGiveDialog(code) {
    selectedCode.value = code
    giveDialogOpen.value = true
}

function openLostDialog(code) {
    selectedCode.value = code
    lostDialogOpen.value = true
}

function openConfirmUseDialog(code) {
    selectedCode.value = code
    confirmUseDialogOpen.value = true
}

function openMoveDialog(code) {
    selectedCode.value = code
    moveDialogOpen.value = true
}

function onActionSubmitted() {
    loadItems()
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

onMounted(() => {
    loadLocations()
    loadItems()
})
</script>
