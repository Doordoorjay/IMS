<template>
    <v-dialog v-model="internalOpen" max-width="400px" persistent>
        <v-card>
            <v-card-title>确认使用</v-card-title>
            <v-card-text>
                是否确认该物品已使用？此操作不可撤销。
            </v-card-text>
            <v-card-actions class="justify-end">
                <v-btn variant="text" @click="internalOpen = false">取消</v-btn>
                <v-btn color="primary" variant="tonal" @click="submit">确认</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({ open: Boolean, code: String })
const emit = defineEmits(['update:open', 'submitted', 'snackbar'])

const internalOpen = ref(props.open)
watch(() => props.open, val => (internalOpen.value = val))
watch(internalOpen, val => emit('update:open', val))

const submit = async () => {
    const now = new Date()
    const yyyy = now.getFullYear()
    const mm = String(now.getMonth() + 1).padStart(2, '0')
    const dd = String(now.getDate()).padStart(2, '0')
    const dateStr = `${yyyy}-${mm}-${dd}`

    const payload = {
        code: props.code,
        action: 'used',
        details: { date: dateStr }
    }

    try {
        const res = await fetch(`${import.meta.env.VITE_API_BASE}/api/item/action.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })

        const data = await res.json()

        if (data.success) {
            emit('snackbar', { message: '操作成功', color: 'success' })
            emit('submitted', 'used')
            internalOpen.value = false
        } else {
            emit('snackbar', { message: data.error || '提交失败', color: 'error' })
        }
    } catch (err) {
        emit('snackbar', { message: '网络错误', color: 'error' })
    }
}
</script>
