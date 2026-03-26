<template>
  <div class="message" @contextmenu.prevent="showMenu">
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
        <div class="message-bubble">
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
            <div v-else-if="message.type === 5" class="forward-message">{{ getMessageHistoryContent() }}</div>
            <div v-else-if="message.type === 6" @click="goToChatRecords">
              <div class="records-header">
                <span class="records-label">聊天记录</span>
                <span>
                  <span class="records-count">{{ message.content.length }} 条记录</span>
                  <el-icon><ArrowRight /></el-icon>
                </span>
              </div>
              <div v-for="(record, idx) in message.content.slice(0, 2)" :key="idx">
                {{ record.sender_nick }}：{{ record.type === 1 ? record.content : '[文件]' }}
              </div>
              <div v-if="message.content.length > 2">……</div>
            </div>
            <div v-if="showMessageMenu" class="message-menu">
              <div class="menu-item" @click="handleEdit" v-if="canEdit">编辑</div>
              <div class="menu-item" @click="handleRecall" v-if="canRecall">撤回</div>
              <div class="menu-item" @click="handleReply">回复</div>
            </div>
          </div>
        </div>
        <div v-if="message.type === 5">
          <div class="pagination-container">
            <button class="pagination-btn" :disabled="currentPage === 0" @click="prevPage">
              <el-icon><ArrowLeft /></el-icon>
            </button>
            <span class="page-indicator">{{ currentPage + 1 }} / {{ message.content.length }}</span>
            <button class="pagination-btn" :disabled="currentPage === message.content.length - 1" @click="nextPage">
              <el-icon><ArrowRight /></el-icon>
            </button>
          </div>
        </div>
      </div>
      <el-avatar v-if="isMyMessage" :src="avatar" :size="40" @click="goToProfile(message.sender)" />
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, type Component } from 'vue'
import { useRouter } from 'vue-router'
import { formatDateLong } from '@/utils/dateFormatter'
import type { GroupInviteMessage, MessageChangeRecords, SystemMessage, MessageItem } from '@/types/api'
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
const router = useRouter()
const props = defineProps<MessageProp>()
const fileUrl = computed<string>(() => {
  if (props.message.type !== 2) return ''
  return '/upload/files/' + props.message.multi
})
const currentPage = ref<number>(0)
const showMessageMenu = ref<boolean>(false)
const emit = defineEmits(['edit', 'recall', 'reply', 'viewRecords'])
const fileIconComponent = computed<Component>(() => {
  if (!props.message.content) return Document
  const fileExt = (props.message.content as string).split('.').pop().toLowerCase()
  const iconMap: Record<string, Component> = {
    doc: Document,
    docx: Document,
    xls: DataLine,
    xlsx: DataLine,
    csv: DataLine,
    ppt: DocumentCopy,
    pptx: DocumentCopy,
    pdf: Document,
    zip: FolderOpened,
    rar: FolderOpened,
    '7z': FolderOpened,
    tar: FolderOpened,
    gz: FolderOpened,
    jpg: Picture,
    jpeg: Picture,
    png: Picture,
    gif: Picture,
    bmp: Picture,
    svg: Picture,
    mp3: Headset,
    wav: Headset,
    flac: Headset,
    aac: Headset,
    mp4: VideoCamera,
    avi: VideoCamera,
    mov: VideoCamera,
    wmv: VideoCamera,
    flv: VideoCamera,
    txt: Tickets,
    log: Tickets,
    ini: Tickets,
    conf: Tickets,
  }
  return iconMap[fileExt] || Document
})
const fileIconColorClass = computed<string>(() => {
  if (!props.message.content) return ''
  const fileExt = (props.message.content as string).split('.').pop().toLowerCase()
  const colorMap: Record<string, string> = {
    doc: 'text-primary',
    docx: 'text-primary',
    xls: 'text-success',
    xlsx: 'text-success',
    csv: 'text-success',
    ppt: 'text-danger',
    pptx: 'text-danger',
    pdf: 'text-danger',
    zip: 'text-warning',
    rar: 'text-warning',
    '7z': 'text-warning',
    tar: 'text-warning',
    gz: 'text-warning',
    jpg: 'text-info',
    jpeg: 'text-info',
    png: 'text-info',
    gif: 'text-info',
    bmp: 'text-info',
    svg: 'text-info',
    mp3: 'text-info',
    wav: 'text-info',
    flac: 'text-info',
    aac: 'text-info',
    mp4: 'text-info',
    avi: 'text-info',
    mov: 'text-info',
    wmv: 'text-info',
    flv: 'text-info',
    txt: 'text-secondary',
    log: 'text-secondary',
    ini: 'text-secondary',
    conf: 'text-secondary',
  }
  return colorMap[fileExt] || ''
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
  console.log('显示菜单', event)
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
}
const handleRecall = () => {
  emit('recall', props.message)
}
const handleReply = () => {
  emit('reply', props.message)
}
onMounted(() => {
  if (props.message.type === 5 && props.message.content) {
    currentPage.value = props.message.content.length - 1
  }
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
.forward-message {
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
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  z-index: 100;
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
  top: -20px;
  right: 0;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 6px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 10;
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