<template>
    <v-dialog v-model="moveDialogOpen" max-width="400px">
        <v-card>
            <v-card-title>
                更改储存位置
            </v-card-title>
            <v-card-text>
                <v-select v-model="selectedLocation"
                    :items="Object.entries(locations).map(([id, name]) => ({ title: name, value: id }))" label="选择新的位置"
                    item-title="title" item-value="value" />
            </v-card-text>
            <v-card-actions>
                <v-btn text @click="moveDialogOpen = false">取消</v-btn>
                <v-btn color="primary" @click="submitMove">确认更改</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
const API_BASE = import.meta.env.VITE_API_BASE
const props = defineProps({
    open: Boolean,
    code: String,
    locations: Object
})
const emit = defineEmits(['update:open', 'submitted', 'snackbar'])

const moveDialogOpen = ref(false)
const selectedLocation = ref(null)

watch(() => props.open, (val) => {
    moveDialogOpen.value = val
    if (val) selectedLocation.value = null
})
watch(moveDialogOpen, (val) => emit('update:open', val))

async function submitMove() {
    if (!selectedLocation.value) {
        emit('snackbar', { message: '请选择一个位置', color: 'error' })
        return
    }

    try {
        const res = await fetch(`${API_BASE}/api/item/move.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: props.code,
                location_id: selectedLocation.value
            })
        })

        if (!res.ok) {
            emit('snackbar', { message: '网络错误，请稍后再试', color: 'error' })
            return
        }

        const data = await res.json()

        if (data.success) {
            emit('snackbar', { message: '位置更新成功', color: 'success' })
            emit('submitted', { location_id: selectedLocation.value })
            moveDialogOpen.value = false
        } else {
            emit('snackbar', { message: data.error || '请求失败，请重试', color: 'error' })
        }
    } catch (err) {
        console.error(err)
        emit('snackbar', { message: '请求异常，请检查网络', color: 'error' })
    }
}


</script>
