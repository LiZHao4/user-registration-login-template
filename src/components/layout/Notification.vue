<template>
  <transition name="notification-fade">
    <div
      v-if="props.modelValue"
      ref="notificationRef"
      :class="['notification', { 'is-mobile': isMobile }]"
      @touchstart="onTouchStart"
      @touchmove="onTouchMove"
      @touchend="onTouchEnd"
    >
      <div v-if="badge" class="notification__badge">{{ badge }}</div>
      <div v-if="imageUrl" class="notification__image">
        <img :src="imageUrl" alt="通知图片" />
      </div>
      <div class="notification__content">
        <div class="notification__header">
          <h3 class="notification__title">{{ title }}</h3>
          <span class="notification__time">
            {{ formatDateShort(time) }}
            <el-button class="notification__close-btn" link :icon="Close" @click="close" />
          </span>
        </div>
        <p class="notification__message">{{ content }}</p>
      </div>
    </div>
  </transition>
</template>
<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { formatDateShort } from '@/utils/dateFormatter'
import { Close } from '@element-plus/icons-vue'
const props = withDefaults(defineProps<{
  title: string
  content: string
  time: number
  imageUrl?: string
  badge?: number
  duration: number
  modelValue: boolean
}>(), {
  time: Math.floor(new Date().getTime() / 1000),
  duration: 5000,
  modelValue: true
})
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'close'): void
}>()
const isMobile = ref(window.innerWidth < 768)
const updateLayout = () => {
  isMobile.value = window.innerWidth < 768
}
let startX = 0
let startY = 0
let isMoving = false
const onTouchStart = (e: TouchEvent) => {
  const touch = e.touches[0]
  startX = touch.clientX
  startY = touch.clientY
  isMoving = true
}
const onTouchMove = (e: TouchEvent) => {
  if (!isMoving) return
  const touch = e.touches[0]
  const deltaX = touch.clientX - startX
  const deltaY = touch.clientY - startY
  const SWIPE_THRESHOLD = 50
  if (deltaY < -SWIPE_THRESHOLD || deltaX > SWIPE_THRESHOLD) {
    close()
    isMoving = false
  }
}
const onTouchEnd = () => {
  isMoving = false
}
const close = () => {
  emit('update:modelValue', false)
  emit('close')
}
let autoCloseTimer: ReturnType<typeof setTimeout> | null = null
const startAutoClose = () => {
  if (props.duration && props.duration > 0) {
    autoCloseTimer = setTimeout(() => {
      close()
    }, props.duration)
  }
}
const clearAutoClose = () => {
  if (autoCloseTimer) {
    clearTimeout(autoCloseTimer)
    autoCloseTimer = null
  }
}
watch(() => props.modelValue, newVal => {
  if (newVal) {
    startAutoClose()
  } else {
    clearAutoClose()
  }
}, { immediate: true })
onMounted(() => {
  updateLayout()
  window.addEventListener('resize', updateLayout)
})
onUnmounted(() => {
  window.removeEventListener('resize', updateLayout)
  clearAutoClose()
})
</script>
<style scoped>
.notification {
  position: fixed;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 12px;
  width: 320px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 12px 16px;
  transition: all 0.3s ease;
  cursor: pointer;
  pointer-events: auto;
  overflow: hidden;
}
.notification__badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background-color: #f56c6c;
  color: white;
  font-size: 12px;
  font-weight: bold;
  border-radius: 50%;
  min-width: 20px;
  height: 20px;
  line-height: 20px;
  text-align: center;
  padding: 0 6px;
  box-shadow: 0 0 0 2px #fff;
  z-index: 1;
}
.notification__close-btn {
  color: #909399;
  transition: color 0.2s;
}
.notification__close-btn:hover {
  color: #409eff;
}
.notification__content {
  flex: 1;
  min-width: 0;
}
.notification__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}
.notification__image {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: 8px;
  overflow: hidden;
  background-color: #f5f7fa;
}
.notification__image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.notification__message {
  margin: 0;
  font-size: 13px;
  color: #606266;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.notification__time {
  font-size: 12px;
  color: #909399;
  white-space: nowrap;
  margin-left: 8px;
}
.notification__title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.notification-fade-enter-active,
.notification-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.notification-fade-enter-from {
  opacity: 0;
  transform: translateX(30px);
}
.notification-fade-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
.notification:not(.is-mobile) {
  top: 20px;
  right: 20px;
}
.notification.is-mobile {
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 32px);
  max-width: 320px;
}
.notification.is-mobile.notification-fade-enter-from,
.notification.is-mobile.notification-fade-leave-to {
  transform: translateX(-50%) translateY(-20px);
}
</style>