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
                        <div class="text-subtitle-2">来源人：</div>
                        <div class="text-body-2">{{ item.source || 'N/A' }}</div>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">来源活动：</div>
                        <div class="text-body-2">{{ item.venue || 'N/A' }}</div>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">状态：</div>
                        <v-chip :color="statusColor(item.status)" dark size="small" class="text-capitalize">
                            <v-icon start icon="mdi-tag" />
                            {{ item.status }}
                        </v-chip>
                    </v-col>
                    <v-col cols="6">
                        <div class="text-subtitle-2">创建时间：</div>
                        <div class="text-body-2">{{ formatDate(item.created_at) }}</div>
                    </v-col>
                </v-row>
            </v-col>

            <!-- 右边图片 -->
            <v-col cols="4" class="d-flex justify-end align-start">
                <v-img v-if="item.photo" :src="getImageUrl(item.photo)" height="120" width="120" class="rounded"
                    cover />
            </v-col>
        </v-row>

        <v-divider class="my-3" />

        <v-card-actions class="justify-end">
            <v-btn size="small" variant="tonal" color="purple" @click="$emit('give', item.code)"
                :disabled="item.status !== 'available'">
                <v-icon start>mdi-hand-heart</v-icon> 赠送出库
            </v-btn>

            <v-btn size="small" variant="tonal" color="error" @click="$emit('lost', item.code)"
                :disabled="item.status !== 'available'">
                <v-icon start>mdi-alert-circle</v-icon> 标记丢失
            </v-btn>

            <v-btn size="small" variant="tonal" color="primary" @click="$emit('used', item.code)"
                :disabled="item.status !== 'available'">
                <v-icon start>mdi-check-circle</v-icon> 已使用
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script setup>
defineProps({ item: Object })
const API_BASE = import.meta.env.VITE_API_BASE

const getImageUrl = (photo) => {
    return photo ? `${API_BASE}/uploads/${photo}` : ''
}

const emit = defineEmits(['action', 'give', 'lost', 'used'])

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
