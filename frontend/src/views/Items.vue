<template>
    <AppNavbar />
    <v-main>
        <v-container class="py-6">
            <!-- 分类 Tabs -->
            <v-tabs v-model="currentTab" background-color="grey-lighten-4" grow>
                <v-tab v-for="(label, status) in statusMap" :key="status" @click="loadItems(status)">
                    {{ label }}
                </v-tab>
            </v-tabs>

            <!-- 列表 -->
            <v-row class="mt-4" dense>
                <v-col v-for="item in items" :key="item.code" cols="12" md="6" lg="4">
                    <v-card class="rounded-xl" elevation="2" @click="selectItem(item)">
                        <v-card-title>{{ item.name }}</v-card-title>
                        <v-card-subtitle class="text-caption">Code: {{ item.code }}</v-card-subtitle>
                        <v-card-text class="text-sm">
                            <div><strong>来源:</strong> {{ item.source || '无' }}</div>
                            <div><strong>状态:</strong>
                                <v-chip size="x-small" :color="statusColor(item.status)" dark>{{ item.status }}</v-chip>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- 弹窗详情 -->
            <v-dialog v-model="dialog" max-width="500px">
                <v-card v-if="selected">
                    <v-card-title>{{ selected.name }}</v-card-title>
                    <v-card-text>
                        <div><strong>Code:</strong> {{ selected.code }}</div>
                        <div><strong>UPC:</strong> {{ selected.UPC || 'N/A' }}</div>
                        <div><strong>来源:</strong> {{ selected.source || '无' }}</div>
                        <div><strong>活动:</strong> {{ selected.venue || '无' }}</div>
                        <div><strong>接收:</strong> {{ selected.received_at || 'N/A' }}</div>
                        <div><strong>状态:</strong>
                            <v-chip size="small" :color="statusColor(selected.status)" dark>{{ selected.status
                            }}</v-chip>
                        </div>
                        <v-img :src="getImageUrl(selected.photo_url)" max-height="200" contain />
                    </v-card-text>
                    <v-card-actions>
                        <v-btn text @click="dialog = false">关闭</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </v-main>
</template>

<script setup>
import { ref } from 'vue'
import AppNavbar from '@/components/AppNavbar.vue'

const API_BASE = import.meta.env.VITE_API_BASE
const currentTab = ref(0)
const items = ref([])
const dialog = ref(false)
const selected = ref(null)
const getImageUrl = (path) =>
    path ? (path.startsWith('/') ? API_BASE + path : `${API_BASE}/uploads/${path}`) : ''

const statusMap = {
    available: '在库',
    given: '已送出',
    lost: '已丢失',
    used: '已使用'
}

function loadItems(status = 'available') {
    fetch(`${API_BASE}/api/item/list.php?status=${status}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                items.value = data.items
            } else {
                alert('加载失败：' + data.error)
            }
        })
}

function selectItem(item) {
    selected.value = item
    dialog.value = true
}

function statusColor(status) {
    switch (status) {
        case 'available': return 'green'
        case 'given': return 'purple'
        case 'lost': return 'red'
        case 'used': return 'blue'
        default: return 'grey'
    }
}

// 初始加载
loadItems()
</script>
