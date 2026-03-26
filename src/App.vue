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
const notificationContainer = ref<InstanceType<typeof NotificationContainer> | null>(null)
let socket: ReturnType<typeof io> | null = null
const connectWebSocket = () => {
  if (socket) return
  socket = io('/', {
    withCredentials: true,
    path: '/socket.io'
  })
  socket.on('new_message', () => {
    // const messageContent = getDisplayContent({}, )
    // 先到这，后面我去搞一个Pinia过来，因为这边用户ID我觉得需要用状态管理来做
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