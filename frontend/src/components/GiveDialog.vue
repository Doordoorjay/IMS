<template>
    <v-dialog v-model="internalOpen" max-width="600px" persistent>
        <v-card>
            <v-card-title>赠送出库登记</v-card-title>
            <v-card-text>
                <v-form ref="formRef" v-model="valid" lazy-validation>
                    <v-text-field v-model="form.to" label="被赠送人" :rules="[v => !!v || '必填']" required />
                    <v-select v-model="form.method" label="赠送方式" :items="['面交', '邮寄']" :rules="[v => !!v || '必填']"
                        required />
                    <v-text-field v-if="form.method === '邮寄'" v-model="form.tracking" label="快递单号"
                        :rules="[v => !!v || '必填']" required />
                    <v-select v-model="form.type" label="赠送方式（无料/换物）" :items="['无料赠送', '以物换物']"
                        :rules="[v => !!v || '必填']" required />
                    <v-text-field v-model="form.event" label="赠送活动（可选）" />

                    <v-menu v-model="menu" :close-on-content-click="false" transition="scale-transition" offset-y>
                        <template #activator="{ props }">
                            <v-text-field v-model="formattedDate" label="赠送时间" readonly v-bind="props" />
                        </template>
                        <v-date-picker v-model="form.date" @input="menu = false" />
                    </v-menu>
                </v-form>
            </v-card-text>

            <v-card-actions class="justify-end">
                <v-btn variant="text" @click="cancel">取消</v-btn>
                <v-btn variant="tonal" color="primary" :disabled="!valid" @click="submit">确认赠送</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <!-- 提交反馈 -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
        {{ snackbar.message }}
    </v-snackbar>

</template>

<script setup>
import { ref, computed, watch } from 'vue'

const snackbar = ref({
    show: false,
    message: '',
    color: 'success' // 或 error
})

const props = defineProps({
    open: Boolean,
    code: String,
    form: Object
})

const emit = defineEmits(['update:open', 'submitted', 'snackbar'])

const internalOpen = ref(props.open)
watch(() => props.open, val => (internalOpen.value = val))
watch(internalOpen, val => emit('update:open', val))

const valid = ref(false)
const menu = ref(false)
const formRef = ref(null)

const formattedDate = computed({
    get: () => {
        if (!props.form.date) return ''
        const d = new Date(props.form.date)
        const yyyy = d.getFullYear()
        const mm = String(d.getMonth() + 1).padStart(2, '0')
        const dd = String(d.getDate()).padStart(2, '0')
        return `${yyyy}-${mm}-${dd}`
    },
    set: val => {
        const [yyyy, mm, dd] = val.split('-')
        props.form.date = new Date(Number(yyyy), Number(mm) - 1, Number(dd))
    }
})



const submit = async () => {
    if (!valid.value) return

    const payload = {
        code: props.code,
        action: 'given',
        details: {
            to: props.form.to,
            method: props.form.method,
            tracking: props.form.tracking,
            giftType: props.form.type,
            event: props.form.event,
            date: formattedDate.value
        }
    }

    try {
        const res = await fetch(`${import.meta.env.VITE_API_BASE}/api/item/action.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })

        const data = await res.json()

        if (data.success) {
            emit('snackbar', { message: '提交成功！', color: 'success' })
            emit('submitted', 'given')
            internalOpen.value = false
        } else {
            emit('snackbar', { message: data.error || '提交失败', color: 'error' })
        }
    } catch (err) {
        emit('snackbar', { message: '网络错误，请稍后再试', color: 'error' })
    }
}



const cancel = () => {
    internalOpen.value = false
}
</script>
