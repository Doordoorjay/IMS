<template>
    <AppNavbar />
    <v-main>
    <v-container class="py-6" ref="scrollContainer" @scroll.passive="handleScroll">
            <!-- 分类 Tabs -->
            <v-tabs
    v-model="currentTab"
    background-color="grey-lighten-4"
    grow
    @update:modelValue="loadItems"
>
    <v-tab
        v-for="(label, status) in statusMap"
        :key="status"
        :value="status"
    >
        {{ label }}
    </v-tab>
</v-tabs>

            <!-- 筛选位置 -->
<v-row class="mt-4 mb-2" dense v-if="currentTab === 'available'">
    <v-col cols="12" sm="6" md="3">
        <v-select
            v-model="selectedLocationId"
            :items="[{ id: null, name: '全部位置' }, ...Object.entries(locationMap).map(([id, name]) => ({ id: Number(id), name }))]"
            item-title="name"
            item-value="id"
            label="筛选位置"
            clearable
            variant="outlined"
            density="compact"
            @update:modelValue="loadItems(currentTab)"
        ></v-select> 
        
    </v-col>
  <v-col cols="12" sm="6" md="4">
    <v-text-field
      v-model="searchKeyword"
      label="搜索物品 / 赠送人 / 活动"
      clearable
      variant="outlined"
      density="compact"
      prepend-inner-icon="mdi-magnify"
    ></v-text-field>
  </v-col>
</v-row>

            <!-- 当前列表总数 + 导出按钮组 -->
<v-row class="mt-4 mb-4" align="center" justify="space-between">
    <v-col cols="12" md="6">
        <!-- 当前列表总数美化 -->
        <div class="text-h6 font-weight-medium text-grey-darken-2">
            当前列表总数：
            <v-chip color="primary" variant="tonal" class="ml-2" size="large">
                {{ items.length }} 项
            </v-chip>
        </div>
    </v-col>

    <v-col cols="12" md="6" class="d-flex justify-end flex-wrap gap-2">
        <!-- 如果是 'available' tab，显示两个按钮 -->
        <template v-if="currentTab === 'available'">
            <v-btn color="primary" variant="tonal" @click="exportToExcel('all')">
                <v-icon start>mdi-download</v-icon>
                导出所有物品列表（含历史）
            </v-btn>

            <v-btn color="secondary" variant="tonal" @click="exportToExcel(currentTab)">
                <v-icon start>mdi-download</v-icon>
                导出当前页面下物品
            </v-btn>
        </template>

        <!-- 其他 tab 只显示一个按钮 -->
        <template v-else>
            <v-btn color="primary" variant="tonal" @click="exportToExcel(currentTab)">
                <v-icon start>mdi-download</v-icon>
                导出当前页面下物品
            </v-btn>
        </template>
    </v-col>
</v-row>
            <!-- 列表 -->
            <v-row class="mt-4" dense>
        <v-col
          v-for="item in paginatedItems"
          :key="item.code"
          cols="12"
          sm="6"
          md="6"
          lg="auto"
          class="pa-2"
          style="max-width: 100%; flex: 1 1 330px"
        >
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
                        <v-img
  :src="getImageUrl(item.photo_url) || '/default.png'"
  :lazy-src="'/default.png'"
  lazy
  width="180"
  height="200"
  class="ma-4 rounded"
  style="object-fit: cover; min-width: 150px"
/>
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
        <!-- 加载中提示 -->
      <div v-if="loadingMore" class="text-center my-4">
        <v-progress-circular indeterminate color="primary" />
        <div class="mt-2 text-subtitle-2">加载中...</div>
      </div>

      <!-- 回到顶部按钮 -->
      <v-fab-transition>
        <v-btn
          v-if="showBackToTop"
          icon
          large
          class="back-to-top"
          color="primary"
          @click="scrollToTop"
        >
          <v-icon>mdi-arrow-up</v-icon>
        </v-btn>
      </v-fab-transition>
    </v-container>
  </v-main>
</template>

<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue'
import AppNavbar from '@/components/AppNavbar.vue'
import GiveDialog from '@/components/GiveDialog.vue'
import LostDialog from '@/components/LostDialog.vue'
import ConfirmUseDialog from '@/components/ConfirmUseDialog.vue'
import MoveDialog from '@/components/MoveDialog.vue'
import * as XLSX from 'xlsx'

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const API_BASE = import.meta.env.VITE_API_BASE
const currentTab = ref('available')
const searchKeyword = ref('')
const items = ref([])
const selectedCode = ref(null)
const selectedLocationId = ref(null)
const dialog = ref(false)
const selected = ref(null)
const giveDialogOpen = ref(false)
const lostDialogOpen = ref(false)
const confirmUseDialogOpen = ref(false)
const moveDialogOpen = ref(false)
const giveDialogForm = ref({})
const snackbar = ref({ show: false, message: '', color: 'success' })

const pageSize = 20
const page = ref(1)
const loadingMore = ref(false)
const scrollContainer = ref(null)
const showBackToTop = ref(false)

// 计算分页后的结果
const paginatedItems = computed(() => {
  return filteredItems.value.slice(0, page.value * pageSize)
})

// 监听 tab 或搜索变动，重置分页
watch([() => currentTab.value, searchKeyword], () => {
  page.value = 1
})

function handleScroll() {
  const scrollHeight = document.documentElement.scrollHeight;
  const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
  const clientHeight = document.documentElement.clientHeight;

  showBackToTop.value = scrollTop > 500;

  if (scrollTop + clientHeight >= scrollHeight - 300 && !loadingMore.value && paginatedItems.value.length < filteredItems.value.length) {
    loadingMore.value = true;
    setTimeout(() => {
      page.value++;
      loadingMore.value = false;
    }, 300);
  }
}   

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

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

const filteredItems = computed(() => {
  const keyword = searchKeyword.value?.toLowerCase().trim()
  if (!keyword) return items.value

  return items.value.filter(item => {
    const name = item.name?.toLowerCase() || ''
    const source = item.source?.toLowerCase() || ''
    const venue = item.venue?.toLowerCase() || ''
    return name.includes(keyword) || source.includes(keyword) || venue.includes(keyword)
  })
})


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
    // 如果切换到非 available，重置 selectedLocationId
    if (status !== 'available') {
        selectedLocationId.value = null
    }

    let url = `${API_BASE}/api/item/list.php?status=${status}`
    if (selectedLocationId.value !== null) {
        url += `&location_id=${selectedLocationId.value}`
    }

    fetch(url)
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
function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d)) return ''
    return d.toISOString().split('T')[0]
}

async function exportToExcel(status = 'all') {
    try {
        // 先加载所有位置（仅导出需要）
        const locRes = await fetch(`${API_BASE}/api/locations/load_locations.php`)
        const locData = await locRes.json()
        const locMap = {}
        if (locData.success && Array.isArray(locData.locations)) {
            locData.locations.forEach(loc => {
                locMap[loc.id] = loc.name
            })
        }

        // 构造 URL
        let url = `${API_BASE}/api/item/list.php?status=${status}`
        if (selectedLocationId.value !== null) {
            url += `&location_id=${selectedLocationId.value}`
        }

        const res = await fetch(url)
        const data = await res.json()
        if (!data.success) {
            showSnackbar('导出失败：' + data.error, 'error')
            return
        }

        const items = data.items

        const worksheetData = items.map(item => ({
            名称: item.name,
            Code: item.code,
            UPC: item.UPC || '',
            状态: statusMap[item.status] || item.status,
            来源: item.source || '',
            活动: item.venue || '',
            储存位置: locMap[item.location_id] || '',
            接收时间: formatDate(item.received_at),
            操作时间: formatDate(item.last_action_date),

            // 🎁 赠送字段（仅限赠送状态）
            被赠送人: item.given_info?.to || '',
            赠送方式: item.given_info?.method || '',
            赠送类型: item.given_info?.giftType || item.given_info?.type || '',
            赠送活动: item.given_info?.event || '',
            赠送时间: formatDate(item.given_info?.date),
        }))

        const worksheet = XLSX.utils.json_to_sheet(worksheetData)
        const workbook = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(workbook, worksheet, '物品列表')

        const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' })
        const blob = new Blob([excelBuffer], { type: 'application/octet-stream' })

        const link = document.createElement('a')
        link.href = URL.createObjectURL(blob)
// 拼接文件名
const tabLabel = statusMap[status] || '全部'
const now = new Date()
const yyyy = now.getFullYear()
const mm = String(now.getMonth() + 1).padStart(2, '0')
const dd = String(now.getDate()).padStart(2, '0')
const hs = String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0')
const dateStr = `${yyyy}-${mm}-${dd}-${hs}`

link.download = `${tabLabel}物品列表-${dateStr}.xlsx`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

        showSnackbar('✅ 导出成功！')
    } catch (e) {
        console.error(e)
        showSnackbar('导出失败，请稍后重试', 'error')
    }
}



onMounted(() => {
    loadLocations()
    loadItems()
    window.addEventListener('scroll', handleScroll);
})
</script>
<style scoped>
.back-to-top {
  position: fixed;
  bottom: 32px;
  right: 32px;
  z-index: 999;
}
</style>
