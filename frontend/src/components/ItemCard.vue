<template>
    <v-card class="mt-4 pa-4" elevation="3" rounded="xl">
        <v-card-title class="text-h6 font-weight-bold">
            {{ item.name }}
        </v-card-title>

        <v-card-subtitle class="text-caption text-grey">
            <v-icon size="small" class="mr-1">mdi-barcode</v-icon>
            {{ item.code }} &nbsp;&nbsp;
            <v-icon size="small" class="ml-4 mr-1">mdi-barcode-scan</v-icon>
            UPC: {{ item.UPC || 'N/A' }}
        </v-card-subtitle>

        <v-divider class="my-3" />

        <v-row>
            <!-- 左边信息 -->
            <v-col cols="8">
                <v-row>
                    <v-col cols="6">
                        <div class="text-subtitle-2">来源：</div>
                        <div class="text-body-2">{{ item.source || 'N/A' }}</div>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">来源活动：</div>
                        <div class="text-body-2">{{ item.venue || 'N/A' }}</div>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">状态：</div>
                        <v-chip :color="statusColor(item.status)" dark size="small">
                            <v-icon start icon="mdi-tag" />
                            {{ statusMap[item.status] || item.status }}
                        </v-chip>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">储存位置：</div>
                        <div class="text-body-2">{{ locationMap[item.location_id] || '无' }}</div>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">入库日期：</div>
                        <div class="text-body-2">{{ formatDate(item.received_at) }}</div>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">操作时间：</div>
                        <div class="text-body-2">{{ formatDate(item.created_at) }}</div>
                    </v-col>


                </v-row>
            </v-col>

            <!-- 右边图片 -->
            <v-img :src="getImageUrl(item.photo_url) || '/default.png'"
                style="width: 200px; height: 200px; object-fit: cover" class="ma-4 rounded" />
        </v-row>

        <v-divider class="my-3" />

        <v-card-actions class="pt-2">
            <div class="d-flex flex-wrap" style="gap: 8px; justify-content: flex-end;">
                <v-btn size="small" variant="tonal" color="purple" @click="$emit('give', item.code)"
                    :disabled="item.status !== 'available'" style="min-width: 110px; flex-basis: 48%;">
                    <v-icon start>mdi-hand-heart</v-icon> 赠送出库
                </v-btn>

                <v-btn size="small" variant="tonal" color="error" @click="$emit('lost', item.code)"
                    :disabled="item.status !== 'available'" style="min-width: 110px; flex-basis: 48%;">
                    <v-icon start>mdi-alert-circle</v-icon> 标记丢失
                </v-btn>

                <v-btn size="small" variant="tonal" color="primary" @click="$emit('used', item.code)"
                    :disabled="item.status !== 'available'" style="min-width: 110px; flex-basis: 48%;">
                    <v-icon start>mdi-check-circle</v-icon> 已使用
                </v-btn>

                <v-btn size="small" variant="tonal" color="teal" @click="$emit('move', item.code)"
                    :disabled="item.status !== 'available'" style="min-width: 110px; flex-basis: 48%;">
                    <v-icon start>mdi-map-marker</v-icon> 更改位置
                </v-btn>
            </div>
        </v-card-actions>

    </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'

defineProps({
    item: Object,
    locationMap: Object
})
const API_BASE = import.meta.env.VITE_API_BASE

const statusMap = {
    available: '在库',
    given: '已送出',
    lost: '已丢失',
    used: '已使用'
}
const getImageUrl = (path) =>
    path ? (path.startsWith('/') ? API_BASE + path : `${API_BASE}/uploads/${path}`) : '/default.png'

const locationMap = ref({})

// 示例填充方式（你应该已实现类似逻辑）
fetch(`${API_BASE}/api/locations/load_locations.php`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            locationMap.value = Object.fromEntries(data.locations.map(loc => [loc.id, loc.name]))
        }
    })

const emit = defineEmits(['action', 'give', 'lost', 'used', 'move'])

const formatDate = (str) => new Date(str).toLocaleString()

const statusColor = (status) => {
    switch (status) {
        case 'available': return 'green'
        case 'given': return 'purple'
        case 'lost': return 'red'
        case 'used': return 'blue'
        default: return 'grey'
    }
}
</script>
