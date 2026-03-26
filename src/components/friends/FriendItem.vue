<template>
  <div :class="['friend-list-item', { unread: count > 0 }]" @click="handleItemClick">
    <el-avatar :src="avatar" :alt="name" :size="48" />
    <div class="content-container">
      <div class="top-row">
        <span class="friend-name">{{ name }}</span>
        <span class="message-time">{{ formatDateShort(time) }}</span>
      </div>
      <div class="bottom-row">
        <p class="message-preview">{{ message || '暂无消息' }}</p>
        <el-badge v-if="count > 0" :value="count" type="danger" />
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { formatDateShort } from '@/utils/dateFormatter'
interface Props {
  avatar: string
  name: string
  message: string
  time: number
  count: number
}
const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'click'): void
}>()
const handleItemClick = () => {
  emit('click')
}
</script>
<style scoped>
.bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.content-container {
  flex-grow: 1;
  min-width: 0;
}
.friend-list-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background-color 0.2s;
  position: relative;
  gap: 12px;
}
.friend-list-item:hover {
  background-color: #f9f9f9;
}
.friend-list-item.unread {
  background-color: #f5f5f5;
}
.friend-list-item.unread:hover {
  background-color: #ebebeb;
}
.friend-list-item.unread .message-preview {
  color: #333;
  font-weight: 500;
}
.friend-list-item .el-avatar {
  flex-shrink: 0;
}
.friend-name {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.message-preview {
  font-size: 14px;
  color: #666;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex-grow: 1;
  text-align: left;
}
.message-time {
  font-size: 12px;
  color: #999;
  flex-shrink: 0;
  margin-left: 8px;
}
.top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}
</style>