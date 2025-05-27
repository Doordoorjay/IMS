<template>
    <AppNavbar />

    <v-main>
        <v-container class="py-10" max-width="600px">
            <v-card elevation="3" rounded="xl">
                <v-card-title class="text-h5 font-weight-bold">新增物品</v-card-title>
                <v-card-text>
                    <v-form ref="form" v-model="valid" lazy-validation>
                        <v-text-field v-model="item.name" label="物品名称" :rules="[v => !!v || '名称为必填项']" required />

                        <v-text-field v-model="item.upc" label="UPC（可选）" />
                        <v-text-field v-model="item.source" label="来源（可选）" />
                        <v-text-field v-model="item.venue" label="活动地点（可选）" />

                        <v-menu v-model="dateMenu" :close-on-content-click="false" transition="scale-transition"
                            offset-y max-width="290px" min-width="290px">
                            <template v-slot:activator="{ props }">
                                <v-text-field v-model="formattedDate" label="接收日期" prepend-icon="mdi-calendar" readonly
                                    v-bind="props" />
                            </template>
                            <v-date-picker v-model="item.received_at" @input="dateMenu = false" />
                        </v-menu>

                        <v-file-input v-model="item.photo" label="上传照片（可选）" accept="image/*" show-size />

                        <v-text-field v-model="item.code" label="系统生成 Code" readonly />
                    </v-form>
                </v-card-text>

                <v-card-actions class="justify-end">
                    <v-btn color="primary" @click="submit" :disabled="!valid">
                        新增物品
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-container>
    </v-main>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppNavbar from '@/components/AppNavbar.vue'

const form = ref(null)
const valid = ref(false)
const dateMenu = ref(false)

const item = ref({
    name: '',
    upc: '',
    source: '',
    venue: '',
    received_at: new Date(), // ✅ Date 类型，避免报错
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

// 自动格式化日期输入框显示
const formattedDate = computed({
    get: () => item.value.received_at.toISOString().substring(0, 10),
    set: val => {
        item.value.received_at = new Date(val)
    }
})

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
