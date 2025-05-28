<template>
    <v-dialog v-model="internalOpen" max-width="500px" persistent>
        <v-card>
            <v-card-title>标记物品丢失</v-card-title>
            <v-card-text>
                <v-menu v-model="menu" :close-on-content-click="false" transition="scale-transition" offset-y>
                    <template #activator="{ props }">
                        <v-text-field v-model="formattedDate" label="丢失日期" readonly v-bind="props" />
                    </template>
                    <v-date-picker v-model="lostDate" @input="menu = false" />
                </v-menu>
            </v-card-text>
            <v-card-actions class="justify-end">
                <v-btn variant="text" @click="internalOpen = false">取消</v-btn>
                <v-btn color="error" variant="tonal" @click="submit">确认标记</v-btn>
            </v-card-actions>
        </v-card>

        <!-- ✅ Snackbar 提示 -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({ open: Boolean, code: String })
const emit = defineEmits(['update:open', 'submitted', 'snackbar'])


const internalOpen = ref(props.open)
watch(() => props.open, val => (internalOpen.value = val))
watch(internalOpen, val => emit('update:open', val))

const menu = ref(false)
const lostDate = ref(new Date())

const formattedDate = computed({
    get: () => {
        const d = lostDate.value
        if (!(d instanceof Date)) return ''
        const yyyy = d.getFullYear()
        const mm = String(d.getMonth() + 1).padStart(2, '0')
        const dd = String(d.getDate()).padStart(2, '0')
        return `${yyyy}-${mm}-${dd}`
    },
    set: val => {
        const [yyyy, mm, dd] = val.split('-')
        lostDate.value = new Date(Number(yyyy), Number(mm) - 1, Number(dd))
    }
})



const snackbar = ref({ show: false, message: '', color: 'success' })

const submit = async () => {
    const payload = {
        code: props.code,
        action: 'lost',
        details: { date: formattedDate.value }
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
            emit('submitted', 'used') // 或 'lost'
            internalOpen.value = false
        } else {
            emit('snackbar', { message: data.error || '提交失败', color: 'error' })
        }
    } catch (err) {
        emit('snackbar', { message: '网络错误', color: 'error' })
    }
}
</script>
