<template>
  <v-layout class="fill-height">
    <AppNavbar />
    <v-main>
      <v-container fluid class="pa-6">
        <v-row justify="center">
          <v-col cols="12" md="8" lg="6">
            <!-- 输入框 -->
            <v-text-field ref="codeInput" v-model="code" label="请输入或扫描物品唯一码 / UPC码"
              prepend-inner-icon="mdi-barcode-scan" append-inner-icon="mdi-magnify" @keyup.enter="fetchItem"
              @click:append-inner="fetchItem" variant="outlined" hide-details class="mt-6" autocomplete="off" />
            <!-- 微信扫码按钮，仅在微信端显示 -->
            <v-btn v-if="isWeChat" color="green" block size="large" class="mt-6" prepend-icon="mdi-qrcode-scan"
              @click="triggerWeChatScan">
              一键扫码查询
            </v-btn>

            <!-- 错误提示 -->
            <v-alert v-if="error" type="error" class="mt-4">{{ error }}</v-alert>

            <!-- UPC 多个结果提示 -->
            <v-alert v-if="upcItems.length" type="info" class="mt-4">
              查询的 UPC <strong>{{ lastQuery }}</strong> 下有 <strong>{{ upcItems.length }}</strong> 个物品：
            </v-alert>

            <!-- UPC 多个卡片 -->
            <ItemCard v-for="it in upcItems" :key="it.code" :item="it" :locationMap="locationMap" @action="handleAction"
              @give="openGiveDialog" @lost="openLostDialog" @used="confirmUse" />

            <!-- 唯一码卡片 -->
            <ItemCard v-if="item" :item="item" :locationMap="locationMap" @action="handleAction" @give="openGiveDialog"
              @lost="openLostDialog" @used="confirmUse" />


          </v-col>
        </v-row>
        <GiveDialog v-model:open="giveDialogOpen" :code="selectedCode" :form="giveDialogForm"
          @submitted="onActionSubmitted" @snackbar="showSnackbar" />
        <LostDialog v-model:open="lostDialogOpen" :code="selectedCode" @submitted="onActionSubmitted"
          @snackbar="showSnackbar" />
        <ConfirmUseDialog v-model:open="confirmUseDialogOpen" :code="selectedCode" @submitted="onActionSubmitted"
          @snackbar="showSnackbar" />
      </v-container>
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
        {{ snackbar.message }}
      </v-snackbar>
    </v-main>
  </v-layout>
</template>

<script setup>
import AppNavbar from '@/components/AppNavbar.vue'
import ItemCard from '@/components/ItemCard.vue'
import GiveDialog from '@/components/GiveDialog.vue'
import LostDialog from '@/components/LostDialog.vue'
import ConfirmUseDialog from '@/components/ConfirmUseDialog.vue'
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { nextTick } from 'vue'

const lostDialogOpen = ref(false)
const confirmUseDialogOpen = ref(false)
const code = ref('')
const codeInput = ref(null)
const item = ref(null)
const upcItems = ref([])
const error = ref(null)
const router = useRouter()
const lastQuery = ref('')
const API_BASE = import.meta.env.VITE_API_BASE
const giveDialogOpen = ref(false)
const selectedCode = ref('')
const isWeChat = /MicroMessenger/i.test(navigator.userAgent)

function triggerWeChatScan() {
  const redirectUrl = window.location.origin + window.location.pathname
  const scanUrl = `https://996315.com/api/scan/?redirect_uri=${encodeURIComponent(redirectUrl)}`
  window.location.href = scanUrl
}


const snackbar = ref({
  show: false,
  color: 'success',
  message: ''
})

function showSnackbar({ message, color = 'success' }) {
  snackbar.value.message = message
  snackbar.value.color = color
  snackbar.value.show = true
}


const giveDialogForm = ref({
  to: '',
  method: '',
  tracking: '',
  type: '',
  event: '',
  date: new Date()
})

function openGiveDialog(code) {
  selectedCode.value = code
  giveDialogForm.value = {
    to: '',
    method: '',
    tracking: '',
    type: '',
    event: '',
    date: new Date()
  }
  giveDialogOpen.value = true
}

function openLostDialog(code) {
  selectedCode.value = code
  lostDialogOpen.value = true
}

function confirmUse(code) {
  selectedCode.value = code
  confirmUseDialogOpen.value = true
}

const fetchItem = async () => {

  const input = code.value.trim()

  if (!input) return

  item.value = null
  upcItems.value = []
  error.value = null

  try {
    const res = await fetch(`${API_BASE}/api/item/search.php?q=${encodeURIComponent(input)}`)
    const data = await res.json()

    if (!data.success) {
      error.value = data.error
      return
    }

    if (data.type === 'code') {
      item.value = data.item
      if (!item.value) {
        error.value = `未找到对应 ${input} 的物品`
      } else {
        lastQuery.value = input
      }
      code.value = ''  // ✅ 清空输入框
    } else if (data.type === 'upc') {
      upcItems.value = data.items
      if (upcItems.value.length) {
        lastQuery.value = input
      } else {
        error.value = `未找到此 UPC ${input} 对应的物品`
      }
      code.value = ''  // ✅ 清空输入框
    }
  } catch (e) {
    error.value = '请求失败'
    console.error(e)
  }
  // 查询成功或失败后重新聚焦输入框
  nextTick(() => {
    codeInput.value?.focus()
  })

}
const onActionSubmitted = (action) => {
  if (item.value?.code === selectedCode.value) {
    item.value.status = action
  }
  const match = upcItems.value.find(i => i.code === selectedCode.value)
  if (match) match.status = action
}

const handleAction = async (action, targetCode) => {
  const res = await fetch(`${API_BASE}/api/item/action.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ code: targetCode, action })
  })
  const data = await res.json()
  if (data.success) {
    // 更新唯一码或 upc 列表中的对应状态
    if (item.value && item.value.code === targetCode) item.value.status = action
    const match = upcItems.value.find(i => i.code === targetCode)
    if (match) match.status = action
  } else {
    error.value = data.error || '操作失败'
  }
}



onMounted(async () => {
  codeInput.value?.focus()

  // 检查是否已安装
  try {
    const res = await fetch(`${API_BASE}/api/check-install.php`)
    const result = await res.json()
    if (!result.installed) router.replace('/install')
  } catch (e) {
    console.warn('检测安装状态失败：', e)
  }

  // ✅ 检查是否从扫码跳转回来
  const qr = router.currentRoute.value.query.qrresult
  if (qr) {
    const codeScanned = qr.split(',')[1] || qr
    code.value = codeScanned

    await nextTick() // 等待 DOM 更新
    fetchItem()      // ✅ 自动触发查询
  }
})


</script>
