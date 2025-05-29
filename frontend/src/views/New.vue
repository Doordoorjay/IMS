<template>
    <AppNavbar />
    <v-main>
        <v-container class="py-10" max-width="600px">
            <v-card elevation="3" rounded="xl" class="pa-6">
                <v-card-title class="text-h5 font-weight-bold mb-4">新增物品</v-card-title>

                <v-form ref="form" v-model="valid" lazy-validation>
                    <!-- 照片上传区 -->
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

                    <!-- UPC有扫码按钮版本（微信环境） -->
                    <v-text-field v-if="isWeChat" v-model="item.upc" label="UPC（可选）" density="default" class="mb-4">
                        <template #append>
                            <v-btn color="green" variant="flat" class="h-100 py-0" style="min-width: 64px"
                                @click="triggerWeChatScan">
                                <v-icon start>mdi-qrcode-scan</v-icon>
                                扫码
                            </v-btn>
                        </template>
                    </v-text-field>
                    <!-- UPC无扫码按钮版本（非微信环境） -->
                    <v-text-field v-else v-model="item.upc" label="UPC（可选）" density="default" class="mb-4" />

                    <v-text-field v-model="item.source" label="来源（可选）" class="mb-4" />
                    <v-text-field v-model="item.venue" label="活动地点（可选）" class="mb-4" />

                    <!-- 日期选择 -->
                    <v-menu v-model="dateMenu" :close-on-content-click="false" transition="scale-transition" offset-y
                        max-width="290px" min-width="290px">
                        <template v-slot:activator="{ props }">
                            <v-text-field v-model="formattedDate" label="接收日期" prepend-icon="mdi-calendar" readonly
                                v-bind="props" class="mb-4" />
                        </template>
                        <v-date-picker v-model="item.received_at" @input="dateMenu = false" />
                    </v-menu>

                    <v-text-field v-model="item.code" label="系统生成 Code" readonly class="mb-6" />

                    <div class="text-right">
                        <v-btn color="primary" @click="submit" :disabled="!valid">
                            新增物品
                        </v-btn>
                    </div>
                </v-form>
            </v-card>
        </v-container>
    </v-main>
</template>

<script setup>
import { ref, computed } from 'vue'
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { watch } from 'vue'
import AppNavbar from '@/components/AppNavbar.vue'

const route = useRoute()
const API_BASE = import.meta.env.VITE_API_BASE
const form = ref(null)
const valid = ref(false)
const dateMenu = ref(false)

// 检测是否是微信端
const isWeChat = /MicroMessenger/i.test(navigator.userAgent)

function triggerWeChatScan() {
    const redirectUrl = window.location.origin + window.location.pathname
    const scanUrl = `https://996315.com/api/scan/?redirect_uri=${encodeURIComponent(redirectUrl)}`
    window.location.href = scanUrl
}

const item = ref({
    name: '',
    upc: '',
    source: '',
    venue: '',
    received_at: new Date(),
    photo: null,
    code: generateCode()
})

watch(item, (val) => {
    localStorage.setItem('item_data', JSON.stringify(val))
}, { deep: true })


onMounted(() => {
    const saved = localStorage.getItem('item_data')
    if (saved) {
        const parsed = JSON.parse(saved)
        Object.assign(item.value, parsed)
    }

    const preview = localStorage.getItem('item_image_preview')
    if (preview) {
        imagePreview.value = preview

        // ✅ 自动转换 base64 为 File 并恢复 photo
        const byteString = atob(preview.split(',')[1])
        const mimeString = preview.split(',')[0].split(':')[1].split(';')[0]

        const ab = new ArrayBuffer(byteString.length)
        const ia = new Uint8Array(ab)
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i)
        }

        const blob = new Blob([ab], { type: mimeString })
        const file = new File([blob], 'restored.jpg', { type: mimeString })
        item.value.photo = file
    }

    const qr = route.query.qrresult
    if (qr) {
        const code = qr.split(',')[1] || qr
        item.value.upc = code
    }
})


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

const imagePreview = ref(null)
const photoInput = ref(null)

watch(imagePreview, (val) => {
    if (val) localStorage.setItem('item_image_preview', val)
})

function triggerImageUpload() {
    photoInput.value?.click()
}

async function handleImageUpload(e) {
    const file = e.target.files[0]
    if (!file || !file.type.startsWith('image/')) return

    const img = new Image()
    const reader = new FileReader()

    reader.onload = async (event) => {
        img.src = event.target.result
    }

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

        // ✅ 这里定义 previewReader，不要漏掉！
        canvas.toBlob(blob => {
            if (!blob) return

            item.value.photo = new File([blob], file.name, { type: 'image/jpeg' })

            const previewReader = new FileReader()
            previewReader.onload = e => {
                imagePreview.value = e.target.result
                localStorage.setItem('item_image_preview', imagePreview.value)
            }
            previewReader.readAsDataURL(item.value.photo)
        }, 'image/jpeg', 0.7)
    }

    reader.readAsDataURL(file)
}



async function submit() {
    if (!form.value.validate()) return

    const formData = new FormData()
    formData.append('name', item.value.name)
    formData.append('upc', item.value.upc || '')
    formData.append('source', item.value.source || '')
    formData.append('venue', item.value.venue || '')
    formData.append('code', item.value.code)
    formData.append('received_at', item.value.received_at.toISOString().substring(0, 10))
    if (item.value.photo) {
        formData.append('photo', item.value.photo)
    }

    try {
        const res = await fetch(`${import.meta.env.VITE_API_BASE}/api/item/add.php`, {
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
                photo: null,
                code: generateCode()
            }
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