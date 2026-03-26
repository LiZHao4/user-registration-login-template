<template>
  <Teleport to="body">
    <TransitionGroup name="notification-list" tag="div" :class="['notification-container', { 'is-mobile': isMobile }]">
      <Notification
        v-for="item in notifications"
        :key="item.id"
        v-model="item.visible"
        :title="item.title"
        :content="item.content"
        :time="item.time"
        :image-url="item.imageUrl"
        :badge="item.badge"
        :duration="item.duration"
        @close="removeNotification(item.id)"
      />
    </TransitionGroup>
  </Teleport>
</template>
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
export interface NotificationOptions {
  title: string
  content: string
  time: number
  imageUrl?: string
  badge?: number
  duration: number
}
interface NotificationInstance extends NotificationOptions {
  id: string
  visible: boolean
}
const notifications = ref<NotificationInstance[]>([])
const isMobile = ref(window.innerWidth < 768)
const updateLayout = () => {
  isMobile.value = window.innerWidth < 768
}
const addNotification = (options: NotificationOptions) => {
  const id = `${Date.now()}-${Math.random().toString(36).substring(2, 10)}`
  const newNotification: NotificationInstance = {
    ...options,
    id,
    visible: true
  }
  notifications.value.push(newNotification)
}
const removeNotification = (id: string) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index !== -1) {
    notifications.value.splice(index, 1)
  }
}
defineExpose({ addNotification })
onMounted(() => {
  updateLayout()
  window.addEventListener('resize', updateLayout)
})
onUnmounted(() => {
  window.removeEventListener('resize', updateLayout)
})
</script>

<style scoped>
.notification-container {
  position: fixed;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  pointer-events: none;
}
.notification-container:not(.is-mobile) {
  top: 20px;
  right: 0;
  align-items: flex-end;
}
.notification-container.is-mobile {
  top: 0;
  left: 0;
  right: 0;
  align-items: center;
}
.notification-container.is-mobile .notification-list-enter-from {
  transform: translateY(-30px);
}
.notification-container.is-mobile .notification-list-leave-to {
  transform: translateY(-30px);
}
.notification-list-enter-active,
.notification-list-leave-active {
  transition: all 0.3s ease;
}
.notification-list-enter-from {
  opacity: 0;
  transform: translateX(30px);
}
.notification-list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>