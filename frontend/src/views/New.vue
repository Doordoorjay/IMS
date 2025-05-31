<template>
    <AppNavbar />
    <v-main>
        <v-container class="py-10" max-width="600px">
            <v-card elevation="3" rounded="xl" class="pa-6">
                <v-card-title class="text-h5 font-weight-bold mb-4">新增物品</v-card-title>

                <v-form ref="form" v-model="valid" lazy-validation>
                    <!-- 照片上传 -->
                    <div class="mb-6">
                        <label class="text-subtitle-1 font-weight-medium mb-2 d-block">物品照片（可选）</label>
                        <v-hover v-slot:default="{ props }">
                            <v-sheet v-bind="props" height="180" class="d-flex align-center justify-center rounded-lg"
                                color="grey-lighten-4" elevation="1" style="cursor: pointer"
                                @click="triggerImageUpload">
                                <div v-if="!imagePreview">
                                    <v-icon size="48" color="grey-darken-1">mdi-plus</v-icon>
                                </div>
                                <v-img v-else :src="imagePreview" cover height="100%" width="100%" class="rounded-lg" />
                            </v-sheet>
                        </v-hover>
                        <input type="file" accept="image/*" capture="environment" ref="photoInput" style="display: none"
                            @change="handleImageUpload" />
                    </div>

                    <v-text-field v-model="item.name" label="物品名称" :rules="[v => !!v || '名称为必填项']" required
                        class="mb-4" />

                    <!-- UPC -->
                    <v-text-field v-if="isWeChat" v-model="item.upc" label="UPC（可选）" density="default" class="mb-4">
                        <template #append>
                            <v-btn color="green" variant="flat" class="h-100 py-0" style="min-width: 64px"
                                @click="triggerWeChatScanForUPC">
                                <v-icon start>mdi-qrcode-scan</v-icon>扫码
                            </v-btn>
                        </template>
                    </v-text-field>
                    <v-text-field v-else v-model="item.upc" label="UPC（可选）" class="mb-4" />

                    <v-text-field v-model="item.source" label="来源（可选）" class="mb-4" />
                    <v-text-field v-model="item.venue" label="活动（可选）" class="mb-4" />
                    <v-select v-model="item.location_id" :items="locationList" item-title="display" item-value="id"
                        label="储存位置" :rules="[requiredRule]" required class="mb-4" />

                    <!-- 日期 -->
                    <v-menu v-model="dateMenu" :close-on-content-click="false" transition="scale-transition" offset-y
                        max-width="290px" min-width="290px">
                        <template v-slot:activator="{ props }">
                            <v-text-field v-model="formattedDate" label="接收日期" prepend-icon="mdi-calendar" readonly
                                v-bind="props" :rules="[requiredRule]" required class="mb-4" />
                        </template>
                        <v-date-picker v-model="item.received_at" @input="dateMenu = false" />
                    </v-menu>

                    <!-- Code 输入模式 -->
                    <v-radio-group v-model="codeMode" row class="mb-3">
                        <v-radio label="自动生成" value="auto" />
                        <v-radio label="手动输入" value="manual" />
                    </v-radio-group>

                    <v-text-field v-model="item.code" label="物品 Code" :readonly="codeMode === 'auto'"
                        :rules="[v => !!v || 'Code 为必填项']" class="mb-6">
                        <template #append v-if="codeMode === 'manual' && isWeChat">
                            <v-btn color="green" variant="flat" class="h-100 py-0" style="min-width: 64px"
                                @click="triggerWeChatScanForCode">
                                <v-icon start>mdi-qrcode-scan</v-icon>扫码
                            </v-btn>
                        </template>
                    </v-text-field>

                    <div class="text-right">
                        <v-btn color="primary" @click="submit" :disabled="!valid">新增物品</v-btn>
                    </div>
                </v-form>
            </v-card>
        </v-container>
    </v-main>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppNavbar from '@/components/AppNavbar.vue'

const API_BASE = import.meta.env.VITE_API_BASE
const route = useRoute()
const form = ref(null)
const requiredRule = (v) => !!v || '此项为必填'

const valid = ref(false)
const dateMenu = ref(false)
const codeMode = ref('auto')
const skipCodeWatchOnce = ref(false)

const isWeChat = /MicroMessenger/i.test(navigator.userAgent)

const imagePreview = ref(null)
const photoInput = ref(null)
const locationList = ref([])

const item = ref({
    name: '',
    upc: '',
    source: '',
    venue: '',
    received_at: new Date(),
    location_id: '',
    photo: null,
    code: generateCode()
})

// 自动/手动切换时处理 code
watch(codeMode, (mode) => {
    if (skipCodeWatchOnce.value) {
        skipCodeWatchOnce.value = false
        return
    }

    if (mode === 'auto') item.value.code = generateCode()
    else item.value.code = ''
})

watch(item, (val) => {
    localStorage.setItem('item_data', JSON.stringify(val))
}, { deep: true })

watch(imagePreview, (val) => {
    if (val) localStorage.setItem('item_image_preview', val)
})

onMounted(async () => {
    const saved = localStorage.getItem('item_data')
    if (saved) Object.assign(item.value, JSON.parse(saved))
    if (!(item.value.received_at instanceof Date)) {
        item.value.received_at = new Date(item.value.received_at || Date.now())
    }

    const savedMode = localStorage.getItem('code_mode')
    if (savedMode === 'manual' || savedMode === 'auto') {
        codeMode.value = savedMode
    }

    const preview = localStorage.getItem('item_image_preview')
    if (preview) {
        imagePreview.value = preview
        const byteString = atob(preview.split(',')[1])
        const mimeString = preview.split(',')[0].split(':')[1].split(';')[0]
        const ab = new ArrayBuffer(byteString.length)
        const ia = new Uint8Array(ab)
        for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i)
        const blob = new Blob([ab], { type: mimeString })
        const file = new File([blob], 'restored.jpg', { type: mimeString })
        item.value.photo = file
    }

    const params = new URLSearchParams(window.location.search)
    const qr = params.get('qrresult')
    const target = params.get('scan_target')
    if (qr && target) {
        const result = decodeURIComponent(qr.split(',')[1] || qr)
        if (target === 'code') {
            skipCodeWatchOnce.value = true
            item.value.code = result
        } else if (target === 'upc') {
            item.value.upc = result
        }
        localStorage.removeItem('code_mode')
    }

    try {
        const res = await fetch(`${API_BASE}/api/locations/load_locations.php`)
        const json = await res.json()
        if (json.success && Array.isArray(json.locations)) {
            locationList.value = json.locations.map(loc => ({
                ...loc,
                display: `${loc.name}`
            }))
        }
    } catch (e) {
        console.warn('位置加载失败：', e)
    }
})

const formattedDate = computed({
    get: () => {
        const d = item.value.received_at
        return d instanceof Date
            ? d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
            : ''
    },
    set: val => {
        item.value.received_at = new Date(val)
    }
})

function triggerImageUpload() {
    photoInput.value?.click()
}

async function handleImageUpload(e) {
    const file = e.target.files[0]
    if (!file || !file.type.startsWith('image/')) return

    const img = new Image()
    const reader = new FileReader()
    reader.onload = event => img.src = event.target.result
    img.onload = () => {
        const canvas = document.createElement('canvas')
        const maxSize = 800
        let { width, height } = img
        if (width > height && width > maxSize) {
            height *= maxSize / width
            width = maxSize
        } else if (height > maxSize) {
            width *= maxSize / height
            height = maxSize
        }

        canvas.width = width
        canvas.height = height
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, width, height)

        canvas.toBlob(blob => {
            if (!blob) return
            item.value.photo = new File([blob], file.name, { type: 'image/jpeg' })
            const previewReader = new FileReader()
            previewReader.onload = e => imagePreview.value = e.target.result
            previewReader.readAsDataURL(item.value.photo)
        }, 'image/jpeg', 0.7)
    }
    reader.readAsDataURL(file)
}

function generateCode() {
    const now = new Date()
    return 'I' +
        now.getFullYear().toString().slice(2) +
        (now.getMonth() + 1).toString().padStart(2, '0') +
        now.getDate().toString().padStart(2, '0') +
        now.getHours().toString().padStart(2, '0') +
        now.getMinutes().toString().padStart(2, '0') +
        now.getSeconds().toString().padStart(2, '0') +
        Math.floor(Math.random() * 1000).toString().padStart(3, '0')
}

function triggerWeChatScanForUPC() {
    const redirectUrl = location.origin + location.pathname + '?scan_target=upc'
    const scanUrl = `https://996315.com/api/scan/?redirect_uri=${encodeURIComponent(redirectUrl)}`
    window.open(scanUrl, '_self')
}

function triggerWeChatScanForCode() {
    localStorage.setItem('code_mode', codeMode.value)
    const redirectUrl = location.origin + location.pathname + '?scan_target=code'
    const scanUrl = `https://996315.com/api/scan/?redirect_uri=${encodeURIComponent(redirectUrl)}`
    window.open(scanUrl, '_self')
}

async function submit() {
    if (!form.value.validate()) return
    const localDate = new Date(item.value.received_at.getTime() - item.value.received_at.getTimezoneOffset() * 60000)

    const formData = new FormData()
    formData.append('name', item.value.name)
    formData.append('upc', item.value.upc || '')
    formData.append('source', item.value.source || '')
    formData.append('venue', item.value.venue || '')
    formData.append('code', item.value.code)
    formData.append('received_at', localDate.toISOString().substring(0, 10))
    formData.append('location_id', item.value.location_id || '')
    if (item.value.photo) {
        formData.append('photo', item.value.photo)
    }

    try {
        const res = await fetch(`${API_BASE}/api/item/add.php`, {
            method: 'POST',
            body: formData,
        })

        const data = await res.json()
        if (data.success) {
            alert('✅ 物品新增成功！')
            form.value.reset()
            item.value = {
                name: '',
                upc: '',
                source: '',
                venue: '',
                received_at: new Date(),
                location_id: '',
                photo: null,
                code: generateCode()
            }
            codeMode.value = 'auto'
            imagePreview.value = null               // ✅ 清除预览图
            localStorage.removeItem('item_image_preview')
        } else {
            alert('❌ 新增失败：' + data.error)
        }
    } catch (err) {
        console.error('请求失败：', err)
        alert('❌ 网络错误，请稍后再试')
    }
}
</script>


<style scoped></style>
