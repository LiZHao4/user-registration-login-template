<template>
  <div class="app">
    <router-view></router-view>
    <BottomDialog
      :visible="globalDialogVisible"
      :config="globalDialogConfig"
      @update:visible="globalDialogVisible = $event"
    />
    <NotificationContainer ref="notificationContainer" />
  </div>
</template>
<script setup lang="ts">
import { ref, provide, reactive, onUnmounted, onMounted } from 'vue'
import type { DialogConfig, DialogConfigFunc } from './components/layout/BottomDialog.vue'
import { io } from 'socket.io-client'
import NotificationContainer from './components/layout/NotificationContainer.vue'
import { getDisplayContent } from './utils/messageUtils'
import { useRouter } from 'vue-router'
import { useUserStore } from './stores/user'
import { useChatStore } from './stores/chat'
const notificationContainer = ref<InstanceType<typeof NotificationContainer> | null>(null)
const userStore = useUserStore()
let socket: ReturnType<typeof io> | null = null
const router = useRouter()
const connectWebSocket = () => {
  if (socket) return
  socket = io('/', {
    withCredentials: true,
    path: '/socket.io'
  })
  socket.on('error', err => {
    if (err.code === 0) {
      userStore.logout()
      router.push('/login')
    }
  })
  socket.on('new_message', message => {
    const currentPath = router.currentRoute.value.path
    if (currentPath === `/chat/${message.session}`) {
      const messageStructure = {
        id: message.id,
        sent_at: message.time,
        sender: message.sender,
        content: message.content,
        type: message.msg_type
      }
      const chatStore = useChatStore()
      chatStore.addMessage(parseInt(message.session), messageStructure)
    } else if (currentPath === '/friends') {
      // 需要通知FriendsList.vue组件，需要更新好友列表中消息，时间，以及未读数量
      // 还没写好接口，下次再说
    } else {
      const title = message.type === 'group' ? message.group_name : message.remark || message.msg_nick
      const messageContent = getDisplayContent(message, userStore.userId)
      const imageUrl = message.type === 'group' ? message.group_avatar : message.sender_avatar
      notificationContainer.value.addNotification({
        title,
        content: messageContent,
        imageUrl,
        time: message.time,
        duration: 5000,
        badge: message.unread_count,
        onClick: () => {
          router.push(`/chat/${message.session}`)
        }
      })
    }
  })
}
const globalDialogVisible = ref<boolean>(false)
const globalDialogConfig = reactive<DialogConfig>({})
const showGlobalDialog: DialogConfigFunc = config => {
  globalDialogConfig.title = config.title || '提示'
  globalDialogConfig.content = config.content || ''
  globalDialogConfig.buttons = config.buttons || [{ label: '确定', type: 'primary' }]
  if (config.component) {
    globalDialogConfig.component = config.component
  } else {
    delete globalDialogConfig.component
  }
  globalDialogVisible.value = true
}
const hideGlobalDialog = () => {
  globalDialogVisible.value = false
}
provide('showGlobalDialog', showGlobalDialog)
provide('hideGlobalDialog', hideGlobalDialog)
onMounted(connectWebSocket)
onUnmounted(() => {
  if (socket) {
    socket.disconnect()
    socket = null
  }
})
</script>
<style>
.app {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>