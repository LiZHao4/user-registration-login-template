<template>
  <div class="message">
    <div :class="['message-wrapper', isMyMessage ? 'sent' : 'received']">
      <el-avatar v-if="!isMyMessage" :src="avatar" :size="40" @click="goToProfile(message.sender)" />
      <div class="message-content">
        <div class="message-header">
          <span v-if="chatType === 'group' && !isMyMessage" class="sender-name">{{ displayName }}</span>
          <span class="message-time">
            <template v-if="message.type == 5">{{ formatDateLong(message.content[currentPage].sent_at) }}</template>
            <template v-else>{{ formatDateLong(message.sent_at) }}</template>
          </span>
        </div>
        <div class="message-bubble" @contextmenu.prevent="showMenu">
          <div class="message-content-inner">
            <div v-if="message.type === 1">{{ message.content }}</div>
            <div v-else-if="message.type === 2">
              <a :href="fileUrl" :download="message.content" class="file-link">
                <small class="file-type-label">文件</small>
                <div class="file-info">
                  <el-icon :size="20" :class="fileIconColorClass">
                    <component :is="fileIconComponent" />
                  </el-icon>
                  <span class="file-name">{{ message.content }}</span>
                </div>
              </a>
            </div>
            <div v-else-if="message.type === 3">
              <small class="invite-label">群聊邀请</small>
              <div class="invite-content">
                <span>{{ getInviteMessage() }}</span>
                <button v-if="showJoinButton" class="join-btn" @click="joinGroup">加入</button>
              </div>
            </div>
            <div v-else-if="message.type === 4">
              <small class="system-label">{{ getSystemMessageType() }}</small>
              <span class="system-content">{{ getSystemMessageContent() }}</span>
            </div>
            <div v-else-if="message.type === 5" class="history-message">{{ getMessageHistoryContent() }}</div>
            <div v-else-if="message.type === 6" @click="goToChatRecords">
              <div class="records-header">
                <span class="records-label">聊天记录</span>
                <span>
                  <span class="records-count">{{ message.content.length }} 条记录</span>
                  <el-icon><ArrowRight /></el-icon>
                </span>
              </div>
              <div v-for="(record, idx) in message.content.slice(0, 2)" :key="idx">
                {{ record.sender_nick }}：
                <template v-if="record.type === 1">{{ record.content }}</template>
                <template v-else-if="record.type === 2">[文件]</template>
                <template v-else-if="record.type === 5">{{ getForwardPreview(record) }}</template>
                <template v-else-if="record.type === 6">[聊天记录] {{ record.content.length }}条</template>
                <template v-else>[未知消息]</template>
              </div>
              <div v-if="message.content.length > 2">……</div>
            </div>
          </div>
        </div>
        <div v-if="message.type === 5" class="pagination-container">
          <span class="pagination-btn" :disabled="currentPage === 0" @click="prevPage">
            <el-icon><ArrowLeft /></el-icon>
          </span>
          <span class="page-indicator">{{ currentPage + 1 }} / {{ message.content.length }}</span>
          <span class="pagination-btn" :disabled="currentPage === message.content.length - 1" @click="nextPage">
            <el-icon><ArrowRight /></el-icon>
          </span>
        </div>
        <div
          v-if="showMessageMenu"
          class="message-menu"
          ref="messageMenu"
          :style="{
            top: menuPosition.y + 'px',
            left: menuPosition.x + 'px'
          }"
        >
          <div class="menu-item" @click="handleEdit" v-if="canEdit">编辑</div>
          <div class="menu-item" @click="handleRecall" v-if="canRecall">撤回</div>
          <div class="menu-item" @click="handleReply">回复</div>
        </div>
      </div>
      <el-avatar v-if="isMyMessage" :src="avatar" :size="40" @click="goToProfile(message.sender)" />
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, type Component } from 'vue'
import { useRouter } from 'vue-router'
import { formatDateLong } from '@/utils/dateFormatter'
import type { GroupInviteMessage, MessageChangeRecords, SystemMessage, MessageItem, ChangeAsRecord } from '@/types/api'
import {
  Document, Picture, VideoCamera, Headset, DataLine, DocumentCopy, Tickets, FolderOpened, ArrowLeft, ArrowRight
} from '@element-plus/icons-vue'
export interface MessageProp {
  message: MessageItem
  chatType: string
  isMyMessage: boolean
  avatar: string
  displayName: string
  currentUserId: number
}
const fileTypeConfig: Record<string, { icon: Component, colorClass: string }> = {
  doc: { icon: Document, colorClass: 'text-primary' },
  docx: { icon: Document, colorClass: 'text-primary' },
  xls: { icon: DataLine, colorClass: 'text-success' },
  xlsx: { icon: DataLine, colorClass: 'text-success' },
  csv: { icon: DataLine, colorClass: 'text-success' },
  ppt: { icon: DocumentCopy, colorClass: 'text-danger' },
  pptx: { icon: DocumentCopy, colorClass: 'text-danger' },
  pdf: { icon: Document, colorClass: 'text-danger' },
  zip: { icon: FolderOpened, colorClass: 'text-warning' },
  rar: { icon: FolderOpened, colorClass: 'text-warning' },
  '7z': { icon: FolderOpened, colorClass: 'text-warning' },
  tar: { icon: FolderOpened, colorClass: 'text-warning' },
  gz: { icon: FolderOpened, colorClass: 'text-warning' },
  jpg: { icon: Picture, colorClass: 'text-info' },
  jpeg: { icon: Picture, colorClass: 'text-info' },
  png: { icon: Picture, colorClass: 'text-info' },
  gif: { icon: Picture, colorClass: 'text-info' },
  bmp: { icon: Picture, colorClass: 'text-info' },
  svg: { icon: Picture, colorClass: 'text-info' },
  mp3: { icon: Headset, colorClass: 'text-info' },
  wav: { icon: Headset, colorClass: 'text-info' },
  flac: { icon: Headset, colorClass: 'text-info' },
  aac: { icon: Headset, colorClass: 'text-info' },
  mp4: { icon: VideoCamera, colorClass: 'text-info' },
  avi: { icon: VideoCamera, colorClass: 'text-info' },
  mov: { icon: VideoCamera, colorClass: 'text-info' },
  wmv: { icon: VideoCamera, colorClass: 'text-info' },
  flv: { icon: VideoCamera, colorClass: 'text-info' },
  txt: { icon: Tickets, colorClass: 'text-secondary' },
  log: { icon: Tickets, colorClass: 'text-secondary' },
  ini: { icon: Tickets, colorClass: 'text-secondary' },
  conf: { icon: Tickets, colorClass: 'text-secondary' }
}
const router = useRouter()
const props = defineProps<MessageProp>()
const fileUrl = computed<string>(() => {
  if (props.message.type !== 2) return ''
  return '/upload/files/' + props.message.multi
})
const currentPage = ref<number>(0)
const showMessageMenu = ref<boolean>(false)
const menuPosition = ref({ x: 0, y: 0 })
const closeMenu = () => {
  showMessageMenu.value = false
}
const messageMenu = ref<HTMLDivElement | null>(null)
const handleGlobalClick = (e: MouseEvent) => {
  if (messageMenu.value && messageMenu.value.contains(e.target as Node)) return
  closeMenu()
}
const emit = defineEmits(['edit', 'recall', 'reply', 'viewRecords'])
const getFileExt = (fileName: string): string => {
  return fileName.split('.').pop()?.toLowerCase() ?? ''
}
const fileIconComponent = computed<Component>(() => {
  if (!props.message.content) return Document
  const ext = getFileExt(props.message.content as string)
  return fileTypeConfig[ext]?.icon ?? Document
})

const fileIconColorClass = computed<string>(() => {
  if (!props.message.content) return ''
  const ext = getFileExt(props.message.content as string)
  return fileTypeConfig[ext]?.colorClass ?? ''
})
const isUserInGroup = computed(() => {
  const content = (props.message as GroupInviteMessage).content
  const inGroupCondition = props.chatType === 'friend' ? 
    content.finish : 
    (content.finish && (content.finish as number[]).includes(props.currentUserId))
  return inGroupCondition
})
const showJoinButton = computed(() => {
  if (!props.message.content || props.isMyMessage) return false
  return !isUserInGroup.value
})
const canEdit = computed(() => {
  return (props.message.type === 1 || props.message.type === 5) && props.isMyMessage
})
const canRecall = computed(() => {
  if (props.message.type === 4 || !props.isMyMessage) return false
  const currentTime = new Date().getTime()
  const messageTime = new Date(props.message.sent_at * 1000).getTime()
  return (currentTime - messageTime) < 120000
})
const goToProfile = (userId: number) => {
  router.push(`/user/${userId}`)
}
const goToChatRecords = () => {
  if (props.message.type === 6) {
    emit('viewRecords', props.message.content)
  }
}
const showMenu = (event: MouseEvent) => {
  window.dispatchEvent(new CustomEvent('close-all-message-menus'))
  menuPosition.value = { x: event.clientX, y: event.clientY }
  showMessageMenu.value = true
}
const getInviteMessage = () => {
  const content = (props.message as GroupInviteMessage).content
  if (props.isMyMessage && !isUserInGroup.value) {
    return `您已邀请对方加入"${content.name}"群聊。`
  } else if (!props.isMyMessage && !isUserInGroup.value) {
    return `对方邀请您加入"${content.name}"群聊。`
  } else if (props.isMyMessage && isUserInGroup.value) {
    return `对方已加入"${content.name}"群聊。`
  } else if (!props.isMyMessage && isUserInGroup.value) {
    return `您已加入"${content.name}"群聊。`
  }
}
const getSystemMessageType = () => {
  const typeMap: Record<string, string> = {
    'quit': '退出群聊',
    'logoff': '用户注销',
    'adminadd': '管理员变更',
    'adminremove': '管理员变更',
    'transfer': '群主转让',
    'join': '加入群聊',
    'recall': '消息撤回'
  }
  return typeMap[(props.message as SystemMessage).content.type] || '系统消息'
}
const getSystemMessageContent = () => {
  if (!props.message.content) return ''
  const propMessage = props.message as SystemMessage
  const content = propMessage.content
  const innerNick = propMessage.inner_nick
  switch (content.type) {
    case 'quit':
      return '已退出群聊。'
    case 'logoff':
      return `"${propMessage.nick}"因注销而退出群聊。`
    case 'adminadd':
      return `已将"${innerNick}"设为群聊管理员。`
    case 'adminremove':
      return `已将"${innerNick}"取消群聊管理员。`
    case 'transfer':
      return `已将群主转让给"${innerNick}"。`
    case 'join':
      return '已加入群聊。'
    case 'recall':
      return '已撤回一条消息。'
    case 'kick':
      return `已将"${innerNick}"踢出群聊。`
    case 'ban':
      return `已将"${innerNick}"禁言。`
    case 'unban':
      return `已将"${innerNick}"解禁。`
    case 'avatar':
      return `已更新群头像。`
    default:
      return '系统消息'
  }
}
const getMessageHistoryContent = () => {
  if (!props.message.content || !props.message.content[currentPage.value]) return ''
  return props.message.content[currentPage.value].msg
}
const joinGroup = () => {
  console.log('加入群聊:', props.message.id)
}
const prevPage = () => {
  if (currentPage.value > 0) {
    currentPage.value--
  }
}
const nextPage = () => {
  if (currentPage.value < (props.message as MessageChangeRecords).content.length - 1) {
    currentPage.value++
  }
}
const handleEdit = () => {
  emit('edit', props.message)
  closeMenu()
}
const handleRecall = () => {
  emit('recall', props.message)
  closeMenu()
}
const handleReply = () => {
  emit('reply', props.message)
  closeMenu()
}
const getForwardPreview = (record: ChangeAsRecord): string => {
  const lastMsg = record.content[record.content.length - 1].msg
  return lastMsg.slice(0, 30) + (lastMsg.length > 30 ? '…' : '')
}
onMounted(() => {
  if (props.message.type === 5 && props.message.content) {
    currentPage.value = props.message.content.length - 1
  }
  window.addEventListener('click', handleGlobalClick)
  window.addEventListener('close-all-message-menus', closeMenu)
})
onUnmounted(() => {
  window.removeEventListener('click', handleGlobalClick)
  window.removeEventListener('close-all-message-menus', closeMenu)
})
</script>
<style scoped>
.file-info {
  display: flex;
  align-items: center;
  gap: 8px;
}
.file-link {
  text-decoration: none;
  color: inherit;
  display: block;
}
.file-name {
  word-break: break-all;
}
.file-type-label {
  color: #666;
  display: block;
  margin-bottom: 4px;
}
.history-message {
  position: relative;
}
.invite-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.invite-label {
  color: #666;
  display: block;
  margin-bottom: 4px;
}
.join-btn {
  align-self: flex-start;
}
.menu-item {
  padding: 8px 12px;
  cursor: pointer;
  font-size: 0.9em;
}
.menu-item:hover {
  background: #f5f5f5;
}
.message {
  margin-bottom: 20px;
  max-width: 100%;
  position: relative;
}
.message-bubble {
  padding: 12px 16px;
  border-radius: 18px;
  max-width: 80%;
  word-wrap: break-word;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  position: relative;
}
.message-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  max-width: calc(100% - 50px);
}
.message-content-inner {
  line-height: 1.4;
  position: relative;
}
.message-header {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
  font-size: 0.8rem;
  color: #666;
  width: 100%;
}
.message-menu {
  position: fixed;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  min-width: 80px;
}
.message-time {
  font-size: 0.75rem;
  opacity: 0.7;
}
.message-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  max-width: 100%;
}
.message-wrapper.received {
  justify-content: flex-start;
}
.message-wrapper.received .message-bubble {
  background-color: white;
  color: #333;
}
.message-wrapper.received .message-content {
  align-items: flex-start;
}
.message-wrapper.received .message-header {
  justify-content: flex-start;
}
.message-wrapper.sent {
  justify-content: flex-end;
}
.message-wrapper.sent .message-bubble {
  background-color: #0084ff;
  color: white;
}
.message-wrapper.sent .message-content {
  align-items: flex-end;
}
.message-wrapper.sent .message-header {
  justify-content: flex-end;
}
.message-wrapper.sent .system-content {
  color: rgba(255, 255, 255, 0.9);
}
.message-wrapper.sent .system-label {
  color: rgba(255, 255, 255, 0.7);
}
.page-indicator {
  font-size: 0.75em;
  color: #666;
}
.pagination-btn {
  border: none;
  background: none;
  padding: 2px 6px;
  cursor: pointer;
  font-size: 0.7em;
}
.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pagination-container {
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 6px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 10;
  margin-top: 5px;
}
.records-count {
  margin-right: 8px;
}
.records-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  padding: 4px 0;
}
.records-label {
  color: #666;
  margin-right: 20px;
}
.sender-name {
  font-weight: 600;
  margin-right: 8px;
}
.system-content {
  color: #888;
}
.system-label {
  color: #666;
  display: block;
  margin-bottom: 4px;
}
.text-danger {
  color: #dc3545;
}
.text-info {
  color: #17a2b8;
}
.text-primary {
  color: #007bff;
}
.text-secondary {
  color: #6c757d;
}
.text-success {
  color: #28a745;
}
.text-warning {
  color: #ffc107;
}
</style>