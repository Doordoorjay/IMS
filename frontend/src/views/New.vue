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
                        <input type="file" accept="image/*" ref="photoInput" style="display: none"
                            @change="handleImageUpload" />
                    </div>

                    <v-text-field v-model="item.name" label="物品名称" :rules="[v => !!v || '名称为必填项']" required
                        class="mb-4" />
                    <v-text-field v-model="item.upc" label="UPC（可选）" class="mb-4" />
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
import AppNavbar from '@/components/AppNavbar.vue'

const API_BASE = import.meta.env.VITE_API_BASE
const form = ref(null)
const valid = ref(false)
const dateMenu = ref(false)

const item = ref({
    name: '',
    upc: '',
    source: '',
    venue: '',
    received_at: new Date(),
    photo: null,
    code: generateCode()
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
        const maxSize = 800 // 最大宽/高
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
            item.value.photo = new File([blob], file.name, { type: 'image/jpeg' })
            const previewReader = new FileReader()
            previewReader.onload = e => {
                imagePreview.value = e.target.result
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