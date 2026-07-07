<template>
  <div class="messages-page">
    <div class="messages-container">
      <!-- 左侧导航 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h1 class="page-title">消息中心</h1>
          <div class="unread-badge" v-if="totalUnread > 0">
            {{ totalUnread > 99 ? '99+' : totalUnread }}
          </div>
        </div>
        
        <div class="nav-list">
          <div 
            class="nav-item" 
            :class="{ active: activeTab === 'system' }"
            @click="switchTab('system')"
          >
            <div class="nav-icon system-icon">
              <el-icon><Bell /></el-icon>
            </div>
            <div class="nav-content">
              <span class="nav-title">系统消息</span>
              <span class="nav-desc">官方通知与公告</span>
            </div>
            <div class="nav-badge" v-if="systemUnread > 0">
              {{ systemUnread > 99 ? '99+' : systemUnread }}
            </div>
          </div>
          
          <div 
            class="nav-item" 
            :class="{ active: activeTab === 'social' }"
            @click="switchTab('social')"
          >
            <div class="nav-icon social-icon">
              <el-icon><ChatDotRound /></el-icon>
            </div>
            <div class="nav-content">
              <span class="nav-title">社交消息</span>
              <span class="nav-desc">好友互动与私信</span>
            </div>
            <div class="nav-badge" v-if="socialUnread > 0">
              {{ socialUnread > 99 ? '99+' : socialUnread }}
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧内容区 -->
      <div class="content-area">
        <!-- 系统消息板块 -->
        <div class="system-messages" v-show="activeTab === 'system'">
          <div class="content-header">
            <h2>系统消息</h2>
            <el-button text @click="markAllAsRead" v-if="systemUnread > 0">
              全部标为已读
            </el-button>
          </div>
          
          <div class="message-list" v-loading="systemLoading">
            <div v-if="systemMessages.length === 0 && !systemLoading" class="empty-state">
              <el-empty description="暂无系统消息">
                <template #image>
                  <div class="empty-icon">
                    <el-icon><Bell /></el-icon>
                  </div>
                </template>
              </el-empty>
            </div>

            <div 
              v-for="msg in systemMessages" 
              :key="msg.id" 
              class="system-message-card"
              :class="{ unread: !msg.isRead }"
              @click="viewSystemMessage(msg)"
            >
              <div class="card-header">
                <div class="msg-type-badge" :class="getTypeClass(msg.type)">
                  {{ getTypeName(msg.type) }}
                </div>
                <span class="msg-time">{{ formatTime(msg.sent_at) }}</span>
              </div>
              
              <div class="card-body">
                <div class="msg-thumbnail" v-if="msg.content?.user?.avatar">
                  <img :src="msg.content.user.avatar" :alt="msg.content.title" />
                </div>
                <div class="msg-thumbnail default-thumb" v-else>
                  <el-icon><Notification /></el-icon>
                </div>
                
                <div class="msg-info">
                  <h3 class="msg-title">{{ msg.content?.title || '系统通知' }}</h3>
                  <div class="msg-content">
                    <template v-if="msg.content?.type === 1">
                      <span>{{ msg.content.content.split('%n')[0] }}</span>
                      <span class="user-mention" v-if="msg.content.user">
                        <img :src="msg.content.user.avatar" class="mention-avatar" />
                        <span class="mention-name">{{ msg.content.user.nick }}</span>
                      </span>
                      <span>{{ msg.content.content.split('%n')[1] }}</span>
                    </template>
                    <template v-else>
                      {{ msg.content?.content || '' }}
                    </template>
                  </div>
                </div>
              </div>
              
              <div class="unread-dot" v-if="!msg.isRead"></div>
            </div>
          </div>
        </div>

        <!-- 社交消息板块 -->
        <div class="social-messages" v-show="activeTab === 'social'">
          <div class="content-header">
            <h2>社交消息</h2>
            <div class="header-actions">
              <el-input
                v-model="searchKeyword"
                placeholder="搜索联系人..."
                :prefix-icon="Search"
                clearable
                class="search-input"
              />
            </div>
          </div>
          
          <div class="chat-list" v-loading="socialLoading">
            <div v-if="socialMessages.length === 0 && !socialLoading" class="empty-state">
              <el-empty description="暂无社交消息">
                <template #image>
                  <div class="empty-icon">
                    <el-icon><ChatDotRound /></el-icon>
                  </div>
                </template>
              </el-empty>
            </div>

            <div 
              v-for="chat in socialMessages" 
              :key="chat.id" 
              class="chat-item"
              :class="{ unread: chat.unread > 0 }"
              @click="openChat(chat)"
            >
              <div class="chat-avatar">
                <el-avatar :src="chat.avatar" :size="52" shape="circle">
                  <el-icon><User /></el-icon>
                </el-avatar>
                <div class="online-dot" v-if="chat.isOnline"></div>
              </div>
              
              <div class="chat-info">
                <div class="chat-top">
                  <span class="chat-name">{{ chat.nick }}</span>
                  <span class="chat-time">{{ formatTime(chat.lastMessageTime) }}</span>
                </div>
                <div class="chat-bottom">
                  <span class="last-message">
                    <span v-if="chat.lastMessageType === 'image'">[图片]</span>
                    <span v-else-if="chat.lastMessageType === 'voice'">[语音]</span>
                    <span v-else>{{ chat.lastMessage }}</span>
                  </span>
                  <div class="unread-count" v-if="chat.unread > 0">
                    {{ chat.unread > 99 ? '99+' : chat.unread }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import {
  Bell,
  ChatDotRound,
  Search,
  User,
  Notification
} from '@element-plus/icons-vue'

const router = useRouter()
const activeTab = ref<'system' | 'social'>('system')
const searchKeyword = ref('')

// 系统消息
const systemLoading = ref(false)
const systemMessages = ref<any[]>([])
const systemUnread = ref(0)

// 社交消息
const socialLoading = ref(false)
const socialMessages = ref<any[]>([])
const socialUnread = ref(0)

const totalUnread = computed(() => systemUnread.value + socialUnread.value)

const switchTab = (tab: 'system' | 'social') => {
  activeTab.value = tab
}

const getTypeClass = (type: number) => {
  const classes: Record<number, string> = {
    0: 'type-notice',
    1: 'type-interaction',
    2: 'type-system'
  }
  return classes[type] || 'type-notice'
}

const getTypeName = (type: number) => {
  const names: Record<number, string> = {
    0: '通知',
    1: '互动',
    2: '系统'
  }
  return names[type] || '通知'
}

const formatTime = (timestamp: number | string) => {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  
  // 今天
  if (date.toDateString() === now.toDateString()) {
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
  }
  
  // 昨天
  const yesterday = new Date(now)
  yesterday.setDate(yesterday.getDate() - 1)
  if (date.toDateString() === yesterday.toDateString()) {
    return '昨天'
  }
  
  // 今年
  if (date.getFullYear() === now.getFullYear()) {
    return `${date.getMonth() + 1}月${date.getDate()}日`
  }
  
  // 更早
  return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`
}

const fetchSystemMessages = async () => {
  systemLoading.value = true
  try {
    // 模拟数据
    const mockData = [
      {
        id: 1,
        type: 1,
        isRead: false,
        sent_at: Date.now() - 3600000,
        content: {
          title: '新的点赞',
          type: 1,
          content: '%n 点赞了你的文章《Vue 3 组合式 API 入门指南》',
          user: {
            id: 101,
            nick: '小明同学',
            avatar: 'https://picsum.photos/100/100?random=1'
          }
        }
      },
      {
        id: 2,
        type: 1,
        isRead: false,
        sent_at: Date.now() - 7200000,
        content: {
          title: '新的评论',
          type: 1,
          content: '%n 评论了你的文章："写得很棒，学到了很多！"',
          user: {
            id: 102,
            nick: '前端小白',
            avatar: 'https://picsum.photos/100/100?random=2'
          }
        }
      },
      {
        id: 3,
        type: 0,
        isRead: true,
        sent_at: Date.now() - 86400000,
        content: {
          title: '系统维护通知',
          type: 0,
          content: '尊敬的用户，我们将于本周六凌晨2:00-4:00进行系统维护，期间可能会影响部分功能的使用，请提前做好准备。感谢您的理解与支持！'
        }
      },
      {
        id: 4,
        type: 2,
        isRead: true,
        sent_at: Date.now() - 172800000,
        content: {
          title: '账号安全提醒',
          type: 0,
          content: '检测到您的账号在新设备上登录，如非本人操作，请及时修改密码。'
        }
      }
    ]
    
    systemMessages.value = mockData
    systemUnread.value = mockData.filter(m => !m.isRead).length
  } catch (error) {
    ElMessage.error('获取系统消息失败')
  } finally {
    systemLoading.value = false
  }
}

const fetchSocialMessages = async () => {
  socialLoading.value = true
  try {
    // 模拟数据
    const mockData = [
      {
        id: 1,
        userId: 201,
        nick: '小红',
        avatar: 'https://picsum.photos/100/100?random=10',
        lastMessage: '好的，那我们明天见！',
        lastMessageType: 'text',
        lastMessageTime: Date.now() - 1800000,
        unread: 2,
        isOnline: true
      },
      {
        id: 2,
        userId: 202,
        nick: '阿杰',
        avatar: 'https://picsum.photos/100/100?random=11',
        lastMessage: '[图片]',
        lastMessageType: 'image',
        lastMessageTime: Date.now() - 7200000,
        unread: 0,
        isOnline: true
      },
      {
        id: 3,
        userId: 203,
        nick: '技术交流群',
        avatar: 'https://picsum.photos/100/100?random=12',
        lastMessage: '老王：有人遇到过这个问题吗？',
        lastMessageType: 'text',
        lastMessageTime: Date.now() - 86400000,
        unread: 15,
        isOnline: false
      },
      {
        id: 4,
        userId: 204,
        nick: '李老师',
        avatar: 'https://picsum.photos/100/100?random=13',
        lastMessage: '作业记得按时交哦',
        lastMessageType: 'text',
        lastMessageTime: Date.now() - 172800000,
        unread: 0,
        isOnline: false
      },
      {
        id: 5,
        userId: 205,
        nick: '大强',
        avatar: 'https://picsum.photos/100/100?random=14',
        lastMessage: '[语音]',
        lastMessageType: 'voice',
        lastMessageTime: Date.now() - 259200000,
        unread: 0,
        isOnline: false
      }
    ]
    
    socialMessages.value = mockData
    socialUnread.value = mockData.reduce((sum, chat) => sum + chat.unread, 0)
  } catch (error) {
    ElMessage.error('获取社交消息失败')
  } finally {
    socialLoading.value = false
  }
}

const viewSystemMessage = (msg: any) => {
  if (!msg.isRead) {
    msg.isRead = true
    systemUnread.value--
  }
}

const markAllAsRead = () => {
  systemMessages.value.forEach(msg => {
    msg.isRead = true
  })
  systemUnread.value = 0
  ElMessage.success('已全部标为已读')
}

const openChat = (chat: any) => {
  router.push(`/chat/${chat.userId}`)
}

onMounted(() => {
  fetchSystemMessages()
  fetchSocialMessages()
})
</script>

<style scoped>
.messages-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
  padding: 24px;
}

.messages-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  gap: 24px;
  height: calc(100vh - 48px);
}

/* 左侧导航 */
.sidebar {
  width: 280px;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 24px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
}

.page-title {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

.unread-badge {
  background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 12px;
  min-width: 20px;
  text-align: center;
}

.nav-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.nav-item:hover {
  background: #f5f7fa;
}

.nav-item.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.nav-item.active .nav-title,
.nav-item.active .nav-desc {
  color: #fff;
}

.nav-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.system-icon {
  background: linear-gradient(135deg, #667eea20, #764ba220);
  color: #667eea;
}

.social-icon {
  background: linear-gradient(135deg, #f093fb20, #f5576c20);
  color: #f5576c;
}

.nav-item.active .system-icon,
.nav-item.active .social-icon {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}

.nav-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-title {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a2e;
}

.nav-desc {
  font-size: 12px;
  color: #94a3b8;
}

.nav-badge {
  background: #ff6b6b;
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 7px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
}

.nav-item.active .nav-badge {
  background: #fff;
  color: #667eea;
}

/* 右侧内容区 */
.content-area {
  flex: 1;
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 24px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.content-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}

.content-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #1a1a2e;
  margin: 0;
}

.search-input {
  width: 240px;
}

:deep(.search-input .el-input__wrapper) {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* 消息列表 */
.message-list,
.chat-list {
  flex: 1;
  overflow-y: auto;
  padding-right: 8px;
}

.message-list::-webkit-scrollbar,
.chat-list::-webkit-scrollbar {
  width: 6px;
}

.message-list::-webkit-scrollbar-thumb,
.chat-list::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 3px;
}

/* 系统消息卡片 */
.system-message-card {
  background: #fafbfc;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid #f1f5f9;
  position: relative;
}

.system-message-card:hover {
  background: #fff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.system-message-card.unread {
  background: linear-gradient(135deg, #f0f4ff 0%, #f8f0ff 100%);
  border-color: #e0e7ff;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.msg-type-badge {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 8px;
}

.msg-type-badge.type-notice {
  background: #dbeafe;
  color: #2563eb;
}

.msg-type-badge.type-interaction {
  background: #fce7f3;
  color: #db2777;
}

.msg-type-badge.type-system {
  background: #fef3c7;
  color: #d97706;
}

.msg-time {
  font-size: 12px;
  color: #94a3b8;
}

.card-body {
  display: flex;
  gap: 16px;
}

.msg-thumbnail {
  width: 80px;
  height: 80px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
}

.msg-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.msg-thumbnail.default-thumb {
  background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #667eea;
  font-size: 32px;
}

.msg-info {
  flex: 1;
  min-width: 0;
}

.msg-title {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 8px 0;
}

.msg-content {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.user-mention {
  display: inline-flex;
  align-items: center;
  background: #e0e7ff;
  border-radius: 12px;
  padding: 2px 8px 2px 3px;
  margin: 0 2px;
  vertical-align: middle;
}

.mention-avatar {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  margin-right: 5px;
}

.mention-name {
  font-weight: 500;
  color: #4f46e5;
  font-size: 13px;
}

.unread-dot {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 8px;
  height: 8px;
  background: #ff6b6b;
  border-radius: 50%;
}

/* 社交消息列表 */
.chat-item {
  display: flex;
  gap: 14px;
  padding: 16px;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 8px;
}

.chat-item:hover {
  background: #f8fafc;
}

.chat-item.unread {
  background: linear-gradient(135deg, #f0f7ff 0%, #f5f0ff 100%);
}

.chat-avatar {
  position: relative;
  flex-shrink: 0;
}

.online-dot {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  background: #22c55e;
  border: 2px solid #fff;
  border-radius: 50%;
}

.chat-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 6px;
}

.chat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chat-name {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}

.chat-time {
  font-size: 12px;
  color: #94a3b8;
  flex-shrink: 0;
}

.chat-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.last-message {
  font-size: 13px;
  color: #64748b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  margin-right: 12px;
}

.unread-count {
  background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 7px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
  flex-shrink: 0;
}

/* 空状态 */
.empty-state {
  padding: 60px 0;
}

.empty-icon {
  font-size: 64px;
  color: #cbd5e1;
  margin-bottom: 16px;
}

/* 响应式 */
@media (max-width: 900px) {
  .messages-container {
    flex-direction: column;
    height: auto;
  }
  
  .sidebar {
    width: 100%;
    flex-direction: row;
    align-items: center;
    padding: 16px;
  }
  
  .sidebar-header {
    margin-bottom: 0;
    margin-right: 24px;
  }
  
  .nav-list {
    flex-direction: row;
    flex: 1;
    gap: 8px;
  }
  
  .nav-item {
    flex: 1;
  }
  
  .nav-icon {
    display: none;
  }
  
  .content-area {
    min-height: 500px;
  }
}

@media (max-width: 640px) {
  .messages-page {
    padding: 12px;
  }
  
  .sidebar {
    padding: 12px;
    flex-wrap: wrap;
    gap: 12px;
  }
  
  .sidebar-header {
    width: 100%;
    margin-right: 0;
  }
  
  .nav-desc {
    display: none;
  }
  
  .content-area {
    padding: 16px;
  }
  
  .content-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .search-input {
    width: 100%;
  }
  
  .card-body {
    flex-direction: column;
  }
  
  .msg-thumbnail {
    width: 100%;
    height: 120px;
  }
}
</style>
