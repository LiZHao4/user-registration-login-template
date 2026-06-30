<template>
  <div class="chat-container">
    <div class="chat-messages" ref="messagesContainer">
      <template v-if="chatData">
        <MessageBubble
          v-for="message in messages"
          :key="message.id"
          :message="message"
          :chat-type="chatData.type"
          :is-my-message="isMyMessage(message.sender)"
          :avatar="getAvatar(message.sender)"
          :display-name="getSenderDisplayName(message.sender)"
          :current-user-id="currentUserId"
          @view-records="handleViewRecords"
        />
      </template>
    </div>
    <div class="chat-input-area">
      <div class="toolbar">
        <div class="toolbar-left">
          <el-button @click="$router.back()" text><el-icon><ArrowLeft /></el-icon><span>返回</span></el-button>
          <div class="chat-title" v-if="chatData">
            <span class="title-text">{{ displayName }}</span>
            <span class="member-count" v-if="chatData.type === 'group'">({{ chatData.members.length }})</span>
          </div>
        </div>
        <div class="toolbar-right">
          <el-button @click="handleMoreOptions" text><el-icon><More /></el-icon><span>更多</span></el-button>
          <el-button @click="triggerFileUpload" text><el-icon><Paperclip /></el-icon><span>文件</span></el-button>
          <input type="file" ref="fileInput" @change="handleFileSend" style="display:none" />
        </div>
      </div>
      <div class="input-area">
        <el-input
          v-model="inputText"
          type="textarea"
          :autosize="true"
          placeholder="输入消息..."
          @keydown="handleKeydown"
          resize="none"
          class="message-textarea"
        />
        <el-button type="primary" @click="sendMessage" :disabled="!inputText.trim()">
          <el-icon><Promotion /></el-icon><span>发送</span>
        </el-button>
      </div>
    </div>
  </div>
  <dialog ref="recordsDialog" class="records-dialog">
    <ChatRecords :messages="currentRecords" @close="closeRecordsDialog" />
  </dialog>
</template>
<script setup lang="ts">
import { ref, onMounted, nextTick, inject, computed, onUnmounted, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import type { ChatResponse, ChatAPISimple, ChatRecordItem, MessageItem } from '@/types/api/chat'
import type { DialogConfigFunc, DialogButton } from '@/types/dialog'
import { formatDateLong } from '@/utils/dateFormatter'
import { useChatStore } from '@/stores/chat'
import { useSessionStore } from '@/stores/session'
const chatStore = useChatStore()
const sessionStore = useSessionStore()
const showGlobalDialog = inject<DialogConfigFunc>('showGlobalDialog')
const route = useRoute()
const router = useRouter()
const chatId = route.params.id
let chatIdNum: number = 0
if (typeof chatId !== 'string') {
  showGlobalDialog({
    title: '错误',
    content: '无效的聊天ID。',
    buttons: [
      { label: '返回', click: () => { router.back() } }
    ]
  })
} else {
  chatIdNum = parseInt(chatId)
}
const inputText = ref<string>('')
const chatData = ref<ChatResponse | null>(null)
const messagesContainer = ref<HTMLDivElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const recordsDialog = ref<HTMLDialogElement | null>(null)
const currentRecords = ref<ChatRecordItem[]>([])
const isLoadingMore = ref<boolean>(false)
const hasMore = ref<boolean>(true)
const shouldAutoScroll = ref<boolean>(true)
const earliestMessageId = computed<number | null>(() => {
  if (chatData.value && chatData.value.data.length > 0) {
    return chatData.value.data[0].id
  }
  return null
})
const currentUserId = computed<number>(() => {
  if (chatData.value.type === 'group') {
    const idx = chatData.value.current_user_index
    return chatData.value.members[idx].id
  }
  return chatData.value.id
})
const displayName = computed<string>(() => {
  if (chatData.value.type === 'group') {
    return chatData.value.group_name
  }
  return getDisplayName(chatData.value.oName, chatData.value.remark)
})
const messages = computed<MessageItem[]>(() => chatStore.getMessages(chatIdNum))
const fetchMessages = async (before?: number, num: number = 30) => {
  try {
    const response = await axios.get<ChatAPISimple>(`/api/chat/${chatIdNum}?num=${num}${before ? `&min=${before}` : ''}`)
    return response.data.data 
  } catch (error) {
    return []
  }
}
const loadMoreMessages = async () => {
  if (isLoadingMore.value || !hasMore.value) return
  isLoadingMore.value = true
  const newMessages = await fetchMessages(earliestMessageId.value, 30)
  if (newMessages.length === 0) {
    hasMore.value = false
    return
  }
  const container = messagesContainer.value
  const oldScrollHeight = container.scrollHeight
  const currentMessages = chatStore.getMessages(chatIdNum)
  chatStore.setMessages(chatIdNum, [...newMessages, ...currentMessages])
  await nextTick()
  const newScrollHeight = container.scrollHeight
  const delta = newScrollHeight - oldScrollHeight
  container.scrollTop = delta
  isLoadingMore.value = false
}
const isMyMessage = (senderId: number): boolean => {
  return senderId === currentUserId.value
}
const getMyAvatar = (): string => {
  if (chatData.value.type === 'group') {
    if (chatData.value.members && chatData.value.current_user_index !== undefined) {
      return chatData.value.members[chatData.value.current_user_index].avatar
    }
  } else {
    return chatData.value.avatar
  }
  return ''
}
const getSenderAvatar = (senderId: number): string => {
  if (chatData.value.type === 'group') {
    if (chatData.value.members) {
      const member = chatData.value.members.find(m => m.id === senderId)
      return member ? member.avatar : ''
    }
  } else {
    return chatData.value.opposite
  }
  return ''
}
const getDisplayName = (nick: string, remark: string | null, groupNickname?: string | null): string => {
  return remark || groupNickname || nick
}
const getSenderDisplayName = (senderId: number): string => {
  if (chatData.value.type !== 'group' || !chatData.value.members) {
    return ''
  }
  const member = chatData.value.members.find(m => m.id === senderId)
  if (member) {
    return getDisplayName(member.nick, member.remark, member.group_nickname)
  }
  return '未知用户'
}
const sendMessage = async () => {
  if (!inputText.value.trim()) return
  try {
    const response = await axios.post('/api/send', {
      target: chatId,
      msg: inputText.value,
    })
    if (response.data.code === 1) {
      inputText.value = ''
      const chatResponse = await axios.get<ChatAPISimple>(
        `/api/chat/${encodeURIComponent(chatIdNum)}?max=${response.data.data.id - 1}`
      )
      chatStore.addMessage(chatIdNum, chatResponse.data.data[0])
      await nextTick()
      scrollToBottomSmooth()
    }
  } catch (error) {
    console.error('Error sending message:', error)
  }
}
const scrollToBottom = () => {
  const container = messagesContainer.value
  container.scrollTop = container.scrollHeight
}
const handleMoreOptions = () => {
  let contentString: string = ''
  let buttons: DialogButton[] = []
  const chat = chatData.value
  if (chat.type === 'group') {
    contentString = `加入时间：${formatDateLong(chat.joined_at)}`
  } else {
    contentString = `请求时间：${formatDateLong(chat.requestTime)}
接受时间：${formatDateLong(chat.allowedTime)}`
    buttons.push({
      label: '查看对方个人主页',
      click: () => {
        router.push(`/user/${chat.oId}`)
      }
    })
  }
  showGlobalDialog({
    title: displayName.value,
    content: contentString,
    buttons
  })
}
const triggerFileUpload = () => {
  fileInput.value.click()
}
const handleFileSend = (event: Event) => {
  const input = event.target as HTMLInputElement
  // 剩下的下次再写
}
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}
const getAvatar = (senderId: number): string => {
  if (isMyMessage(senderId)) {
    return getMyAvatar()
  }
  return getSenderAvatar(senderId)
}
const handleViewRecords = (records: ChatRecordItem[]) => {
  currentRecords.value = records
  nextTick(() => {
    recordsDialog.value.showModal()
  })
}
const closeRecordsDialog = () => {
  recordsDialog.value.close()
  currentRecords.value = null
}
const handleScroll = () => {
  window.dispatchEvent(new CustomEvent('close-all-message-menus'))
  const container = messagesContainer.value
  if (!container) return
  const distanceToBottom = container.scrollHeight - container.scrollTop - container.clientHeight
  shouldAutoScroll.value = distanceToBottom < 50
  if (container.scrollTop <= 50 && !isLoadingMore.value && hasMore.value) {
    loadMoreMessages()
  }
}
const initChat = async () => {
  const response = await axios.get<ChatResponse>(`/api/chat/${chatIdNum}?num=30&getmeta=`)
  const rawData = response.data
  chatStore.setMessages(chatIdNum, rawData.data)
  const reactiveData = reactive(rawData)
  Object.defineProperty(reactiveData, 'data', {
    get: () => chatStore.getMessages(chatIdNum),
    enumerable: true,
    configurable: true
  })
  chatData.value = reactiveData
  sessionStore.clearUnread(chatIdNum)
}
const scrollToBottomSmooth = () => {
  const container = messagesContainer.value
  if (container) {
    container.scrollTo({
      top: container.scrollHeight,
      behavior: 'smooth'
    })
  }
}
watch(() => messages.value.length, (newLen, oldLen) => {
  if (newLen > oldLen) {
    const lastMsg = messages.value[messages.value.length - 1]
    const isFromOther = lastMsg && lastMsg.sender !== currentUserId.value
    if (shouldAutoScroll.value && isFromOther) {
      nextTick(() => {
        scrollToBottomSmooth()
      })
    }
  }
})
onMounted(async () => {
  await initChat()
  await nextTick()
  scrollToBottom()
  messagesContainer.value.addEventListener('scroll', handleScroll, { passive: true })
})
onUnmounted(() => {
  if (messagesContainer.value) {
    messagesContainer.value.removeEventListener('scroll', handleScroll)
  }
  navigator.sendBeacon(`/api/chat/${chatId}/mark-read`)
})
</script>
<style scoped>
.chat-container {
  display: flex;
  flex-direction: column;
  height: 100dvh;
  border-radius: 12px;
  overflow: hidden;
}
.chat-input-area {
  border-top: 1px solid #e0e0e0;
  background-color: #fff;
}
.chat-messages {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
  background-color: #f5f7fa;
  white-space: pre-wrap;
}
.chat-messages::-webkit-scrollbar {
  width: 6px;
}
.chat-messages::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}
.chat-messages::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
.chat-messages::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}
.chat-title {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  font-weight: 600;
  margin: 0 12px;
}
.input-area {
  display: flex;
  padding: 16px;
  gap: 12px;
  align-items: end;
}
.member-count {
  font-size: .8em;
  color: #666;
  margin-left: 4px;
}
.message-textarea {
  flex: 1;
  font-family: inherit;
  font-size: 14px;
  resize: none;
  transition: border-color .2s;
}
.message-textarea :deep(textarea) {
  min-height: 32px;
  max-height: 50vh;
  overflow-y: auto;
}
.message-textarea:focus {
  outline: none;
  border-color: #0084ff;
  box-shadow: 0 0 0 2px rgba(0, 132, 255, 0.1);
}
.records-dialog {
  border: none;
  border-radius: 12px;
  padding: 0;
  width: 90%;
  height: 100%;
  max-width: 600px;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}
.records-dialog::backdrop {
  background-color: #00000080;
}
.send-btn {
  display: flex;
  align-items: center;
  background-color: #0084ff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  cursor: pointer;
  align-self: flex-end;
  min-width: 70px;
  justify-content: center;
  transition: background-color .2s;
}
.send-btn:disabled {
  background-color: #b3d9ff;
  cursor: not-allowed;
}
.send-btn:hover:not(:disabled) {
  background-color: #0073e6;
}
.send-btn i {
  margin-right: 6px;
}
.title-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  position: relative;
}
.toolbar-btn {
  display: flex;
  align-items: center;
  background: 0 0;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 6px 12px;
  margin-right: 8px;
  cursor: pointer;
  font-size: .9em;
  color: #555;
  transition: all .2s;
}
.toolbar-btn:hover {
  background-color: #f5f5f5;
  border-color: #ccc;
}
.toolbar-left, .toolbar-right {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}
.toolbar-left {
  flex: 1;
  min-width: 0;
}
.toolbar-btn i {
  margin-right: 4px;
}
.toolbar-btn span {
  line-height: 1em;
}
</style>