<template>
  <div class="chat-records-container">
    <div class="records-toolbar">
      <el-button @click="goBack" text><el-icon><ArrowLeft /></el-icon>返回</el-button>
      <h3>聊天记录</h3>
    </div>
    <div class="records-list" v-if="props.messages.length">
      <div v-for="(record, index) in props.messages" :key="index" class="record-item">
        <div class="record-sender">
          <el-avatar class="record-avatar" :src="record.sender_avatar" :size="32" />
          <span class="record-name">{{ record.sender_nick }}</span>
          <span class="record-time">{{ formatDateLong(record.sent_at) }}</span>
        </div>
        <div class="record-content">
          <template v-if="record.type === 1">{{ record.content }}</template>
          <template v-else-if="record.type === 2">
            <a :href="`/uploads/files/${record.multi}`" download class="file-link">
              <el-icon><Document /></el-icon>{{ record.content }}
            </a>
          </template>
          <template v-else>
            [暂不支持的消息类型]
          </template>
        </div>
      </div>
    </div>
    <div v-else class="empty-records">
      暂无聊天记录
    </div>
  </div>
</template>
<script setup lang="ts">
import { formatDateLong } from '@/utils/dateFormatter'
import type { ChatRecordItem } from '@/types/api'
const props = defineProps<{
  messages: ChatRecordItem[]
}>()
const emit = defineEmits<{
  (e: 'close'): void
}>()
const goBack = () => {
  emit('close')
}
</script>
<style scoped>
.chat-records-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  background-color: #f5f7fa;
}
.empty-records {
  text-align: center;
  color: #999;
  margin-top: 40px;
}
.file-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #0084ff;
  text-decoration: none;
}
.file-link:hover {
  text-decoration: underline;
}
.record-avatar {
  flex-shrink: 0;
}
.record-content {
  margin-left: 40px;
  color: #555;
  word-break: break-word;
}
.record-item {
  background-color: white;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.record-name {
  font-weight: 500;
  color: #333;
}
.record-sender {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.record-time {
  flex-shrink: 0;
  font-size: 0.8rem;
  color: #999;
  margin-left: auto;
}
.records-list {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}
.records-toolbar {
  display: flex;
  align-items: center;
  padding: 0 16px;
  background-color: white;
  border-bottom: 1px solid #e0e0e0;
}
.records-toolbar h3 {
  margin-left: 10px;
}
</style>