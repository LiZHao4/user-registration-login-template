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
import { getDisplayContent0 } from './utils/messageUtils'
import { useRouter } from 'vue-router'
import { useUserStore } from './stores/user'
import { useChatStore } from './stores/chat'
import axios from 'axios'
import { useSessionStore } from './stores/session'
const notificationContainer = ref<InstanceType<typeof NotificationContainer> | null>(null)
const userStore = useUserStore()
const router = useRouter()
let socket: ReturnType<typeof io> | null = null
let hasShownDialog = false
const initAuth = async () => {
  const res = await axios.get('/api/checkLogin')
  if (res.data.isLogin) {
    userStore.login(res.data.userId)
  }
}
const connectWebSocket = () => {
  if (!userStore.isLogin || socket) return
  socket = io('/', {
    withCredentials: true,
    path: '/socket.io'
  })
  socket.on('connect_error', async err => {
    if (hasShownDialog) return
    hasShownDialog = true
    showGlobalDialog({
      title: 'Socket连接失败',
      content: err.message,
      buttons: [
        {
          label: '确定',
          type: 'primary',
          click: () => {
            hasShownDialog = false
          }
        }
      ]
    })
  })
  socket.on('error', err => {
    if (err.code === 0) {
      doLogout()
    }
  })
  socket.on('new_message', async message => {
    const currentPath = router.currentRoute.value.path
    const sessionStore = useSessionStore()
    await sessionStore.updateSessionFromMessage(message)
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
    } else if (currentPath !== '/friends') {
      const title = message.type === 'group' ? message.group_name : message.remark || message.msg_nick
      const messageContent = await getDisplayContent0(message, userStore.userId)
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
const disconnectWebSocket = () => {
  if (socket) {
    socket.disconnect()
    socket = null
  }
}
const doLogout = () => {
  userStore.logout()
  disconnectWebSocket()
  router.push('/login')
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
onMounted(async () => {
  await initAuth()
  if (userStore.isLogin) {
    connectWebSocket()
  }
  window.addEventListener('user-logged-in', connectWebSocket)
})
onUnmounted(() => {
  window.removeEventListener('user-logged-in', connectWebSocket)
  disconnectWebSocket()
})
</script>
<style>
.app {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>