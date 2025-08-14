<template>
    <v-dialog v-model="internalOpen" max-width="600px" persistent>
        <v-card>
            <v-card-title class="headline">修改物品图片</v-card-title>
            <v-card-text>
                <div class="d-flex justify-center flex-column align-center">
                    <v-img v-if="imagePreview" :src="imagePreview" max-width="400" contain class="mb-4 rounded" />
                    <div v-else class="text-center text-h6 text-grey">无图片预览</div>
                </div>
                <div class="d-flex flex-column flex-md-row gap-4 justify-center mt-4">
                    <v-btn color="primary" @click="triggerFileUpload">
                        <v-icon start>mdi-upload</v-icon> 上传照片
                    </v-btn>
                    <v-btn v-if="isMobile" color="success" @click="triggerCameraCapture">
                        <v-icon start>mdi-camera</v-icon> 拍照
                    </v-btn>
                </div>
                <input type="file" accept="image/*" ref="fileInput" style="display: none" @change="handleFileChange" />
                <input type="file" accept="image/*" capture="environment" ref="cameraInput" style="display: none" @change="handleFileChange" />
            </v-card-text>
            <v-card-actions>
                <v-btn color="error" @click="cancel">取消</v-btn>
                <v-spacer></v-spacer>
                <v-btn color="primary" :disabled="!imageFile" :loading="loading" @click="submit">保存</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    open: Boolean,
    code: String
})
const emit = defineEmits(['update:open', 'submitted', 'snackbar'])

const internalOpen = ref(props.open)
watch(() => props.open, val => (internalOpen.value = val))
watch(internalOpen, val => emit('update:open', val))

const imageFile = ref(null)
const imagePreview = ref(null)
const loading = ref(false)
const isMobile = ref(/Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent))

const fileInput = ref(null)
const cameraInput = ref(null)
const API_BASE = import.meta.env.VITE_API_BASE

const triggerFileUpload = () => fileInput.value?.click()
const triggerCameraCapture = () => cameraInput.value?.click()

// 从 New.vue 中提取的图片压缩逻辑
const handleFileChange = (e) => {
    const file = e.target.files[0]
    if (!file || !file.type.startsWith('image/')) return

    const img = new Image()
    const reader = new FileReader()
    reader.onload = event => img.src = event.target.result
    img.onload = () => {
        const canvas = document.createElement('canvas')
        const maxSize = 800 // 最大尺寸
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
            // 将压缩后的 blob 转换为 File 对象
            imageFile.value = new File([blob], file.name, { type: 'image/jpeg' })
            const previewReader = new FileReader()
            previewReader.onload = e => imagePreview.value = e.target.result
            previewReader.readAsDataURL(imageFile.value)
        }, 'image/jpeg', 0.7) // 0.7 是压缩质量
    }
    reader.readAsDataURL(file)
}

const submit = async () => {
    if (!imageFile.value) return
    loading.value = true

    const formData = new FormData()
    formData.append('code', props.code)
    formData.append('photo', imageFile.value)

    try {
        const res = await fetch(`${API_BASE}/api/item/update_photo.php`, {
            method: 'POST',
            body: formData,
        })
        const data = await res.json()

        if (data.success) {
            emit('snackbar', { message: '图片更新成功', color: 'success' })
            emit('submitted', data.photo_url)
            internalOpen.value = false
        } else {
            emit('snackbar', { message: data.error || '图片更新失败', color: 'error' })
        }
    } catch (err) {
        emit('snackbar', { message: '网络错误', color: 'error' })
        console.error(err)
    } finally {
        loading.value = false
    }
}

const cancel = () => {
    internalOpen.value = false
    imageFile.value = null
    imagePreview.value = null
}
</script>