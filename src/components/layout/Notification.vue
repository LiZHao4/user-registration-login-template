<template>
  <div
    v-if="props.modelValue"
    ref="notificationRef"
    :class="['notification', { 'is-mobile': isMobile }]"
    @click="handleClick"
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
</template>
<script setup lang="ts">
import { ref, onUnmounted, watch, computed } from 'vue'
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
  onClick?: () => void
}>(), {
  time: Math.floor(new Date().getTime() / 1000),
  duration: 5000,
  modelValue: true
})
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'close'): void
}>()
const isMobile = computed<boolean>(() => window.innerWidth < 768)
const notificationRef = ref<HTMLDivElement | null>(null)
let startY = 0
let offsetY = 0
let isSwiping = false
let originalTransition = ''
const onTouchStart = (e: TouchEvent) => {
  startY = e.touches[0].clientY
  isSwiping = true
  offsetY = 0
  if (notificationRef.value) {
    originalTransition = notificationRef.value.style.transition
    notificationRef.value.style.transition = 'none'
  }
}
const onTouchMove = (e: TouchEvent) => {
  if (!isSwiping) return
  e.preventDefault()
  const deltaY = e.touches[0].clientY - startY
  offsetY = deltaY
  if (notificationRef.value) {
    notificationRef.value.style.transform = `translateX(-50%) translateY(${deltaY}px)`
  }
}
const onTouchEnd = () => {
  if (!isSwiping) return
  isSwiping = false
  const threshold = 50
  if (Math.abs(offsetY) > threshold) {
    close()
  } else {
    if (notificationRef.value) {
      notificationRef.value.style.transform = ''
      notificationRef.value.style.transition = 'transform 0.2s ease'
      setTimeout(() => {
        if (notificationRef.value) {
          notificationRef.value.style.transition = originalTransition || ''
        }
      }, 200)
    }
  }
  offsetY = 0
}
const handleClick = () => {
  if (props.onClick) {
    props.onClick()
  }
  close()
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
onUnmounted(() => {
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
  cursor: pointer;
  pointer-events: auto;
  overflow: hidden;
}
.notification__badge {
  position: absolute;
  bottom: 15px;
  right: 15px;
  background-color: #f56c6c;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  border-radius: 50%;
  min-width: 20px;
  height: 20px;
  line-height: 20px;
  text-align: center;
  box-shadow: 0 0 0 2px #fff;
  z-index: 1;
}
.notification__close-btn {
  color: #909399;
  transition: color .2s;
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
  line-clamp: 2;
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
</style>