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
            :class="{ active: activeTab === 'relation' }"
            @click="switchTab('relation')"
          >
            <div class="nav-icon relation-icon">
              <el-icon><User /></el-icon>
            </div>
            <div class="nav-content">
              <span class="nav-title">关系消息</span>
              <span class="nav-desc">好友与群组变动</span>
            </div>
            <div class="nav-badge" v-if="relationUnread > 0">
              {{ relationUnread > 99 ? '99+' : relationUnread }}
            </div>
          </div>
          
          <div 
            class="nav-item" 
            :class="{ active: activeTab === 'interaction' }"
            @click="switchTab('interaction')"
          >
            <div class="nav-icon interaction-icon">
              <el-icon><Bell /></el-icon>
            </div>
            <div class="nav-content">
              <span class="nav-title">互动消息</span>
              <span class="nav-desc">粉丝与评论动态</span>
            </div>
            <div class="nav-badge" v-if="interactionUnread > 0">
              {{ interactionUnread > 99 ? '99+' : interactionUnread }}
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧内容区 -->
      <div class="content-area">
        <!-- 关系消息板块 -->
        <div class="relation-messages" v-show="activeTab === 'relation'">
          <div class="content-header">
            <h2>关系消息</h2>
            <el-button text @click="markAllAsRead('relation')" v-if="relationUnread > 0">
              全部标为已读
            </el-button>
          </div>
          
          <div class="message-list" v-loading="relationLoading">
            <div v-if="relationMessages.length === 0 && !relationLoading" class="empty-state">
              <el-empty description="暂无关系变动消息">
                <template #image>
                  <div class="empty-icon">
                    <el-icon><User /></el-icon>
                  </div>
                </template>
              </el-empty>
            </div>

            <div 
              v-for="msg in relationMessages" 
              :key="msg.id" 
              class="message-card"
              :class="{ unread: !msg.isRead }"
              @click="viewMessage(msg, 'relation')"
            >
              <div class="card-header">
                <div class="msg-type-badge" :class="getRelationTypeClass(msg.subType)">
                  {{ getRelationTypeName(msg.subType) }}
                </div>
                <span class="msg-time">{{ formatTime(msg.sentAt) }}</span>
              </div>
              
              <div class="card-body">
                <div class="msg-thumbnail" v-if="msg.user?.avatar">
                  <img :src="msg.user.avatar" :alt="msg.user.nick" />
                </div>
                <div class="msg-thumbnail default-thumb" v-else>
                  <el-icon><User /></el-icon>
                </div>
                
                <div class="msg-info">
                  <h3 class="msg-title">{{ msg.title }}</h3>
                  <div class="msg-content">{{ msg.content }}</div>
                </div>
              </div>
              
              <div class="unread-dot" v-if="!msg.isRead"></div>
            </div>
          </div>
        </div>

        <!-- 互动消息板块 -->
        <div class="interaction-messages" v-show="activeTab === 'interaction'">
          <div class="content-header">
            <h2>互动消息</h2>
            <el-button text @click="markAllAsRead('interaction')" v-if="interactionUnread > 0">
              全部标为已读
            </el-button>
          </div>
          
          <div class="message-list" v-loading="interactionLoading">
            <div v-if="interactionMessages.length === 0 && !interactionLoading" class="empty-state">
              <el-empty description="暂无互动消息">
                <template #image>
                  <div class="empty-icon">
                    <el-icon><Bell /></el-icon>
                  </div>
                </template>
              </el-empty>
            </div>

            <div 
              v-for="msg in interactionMessages" 
              :key="msg.id" 
              class="message-card"
              :class="{ unread: !msg.isRead }"
              @click="viewMessage(msg, 'interaction')"
            >
              <div class="card-header">
                <div class="msg-type-badge" :class="getInteractionTypeClass(msg.subType)">
                  {{ getInteractionTypeName(msg.subType) }}
                </div>
                <span class="msg-time">{{ formatTime(msg.sentAt) }}</span>
              </div>
              
              <div class="card-body">
                <div class="msg-thumbnail" v-if="msg.user?.avatar">
                  <img :src="msg.user.avatar" :alt="msg.user.nick" />
                </div>
                <div class="msg-thumbnail default-thumb" v-else>
                  <el-icon><Bell /></el-icon>
                </div>
                
                <div class="msg-info">
                  <h3 class="msg-title">{{ msg.title }}</h3>
                  <div class="msg-content">{{ msg.content }}</div>
                  <div v-if="msg.target" class="msg-target">涉及内容：{{ msg.target }}</div>
                </div>
              </div>
              
              <div class="unread-dot" v-if="!msg.isRead"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import {
  Bell,
  User,
  Notification
} from '@element-plus/icons-vue'

const activeTab = ref<'relation' | 'interaction'>('relation')

// 关系消息
const relationLoading = ref(false)
const relationMessages = ref<any[]>([])
const relationUnread = ref(0)

// 互动消息
const interactionLoading = ref(false)
const interactionMessages = ref<any[]>([])
const interactionUnread = ref(0)

const totalUnread = computed(() => relationUnread.value + interactionUnread.value)

const switchTab = (tab: 'relation' | 'interaction') => {
  activeTab.value = tab
}

// 关系消息子类型
const getRelationTypeClass = (subType: number) => {
  const map: Record<number, string> = {
    1: 'type-friend-delete',
    2: 'type-group-dismiss',
    3: 'type-group-kick',
    4: 'type-user-cleanup'
  }
  return map[subType] || 'type-default'
}

const getRelationTypeName = (subType: number) => {
  const map: Record<number, string> = {
    1: '好友删除',
    2: '群聊解散',
    3: '被踢出群',
    4: '好友清理'
  }
  return map[subType] || '关系变动'
}

// 互动消息子类型
const getInteractionTypeClass = (subType: number) => {
  const map: Record<number, string> = {
    1: 'type-new-fan',
    2: 'type-collect',
    3: 'type-comment',
    4: 'type-reply',
    5: 'type-comment-like'
  }
  return map[subType] || 'type-default'
}

const getInteractionTypeName = (subType: number) => {
  const map: Record<number, string> = {
    1: '新增粉丝',
    2: '收藏',
    3: '评论',
    4: '回复',
    5: '评论点赞'
  }
  return map[subType] || '互动'
}

const formatTime = (timestamp: number | string) => {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  
  if (date.toDateString() === now.toDateString()) {
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
  }
  const yesterday = new Date(now)
  yesterday.setDate(yesterday.getDate() - 1)
  if (date.toDateString() === yesterday.toDateString()) {
    return '昨天'
  }
  if (date.getFullYear() === now.getFullYear()) {
    return `${date.getMonth() + 1}月${date.getDate()}日`
  }
  return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`
}

const fetchRelationMessages = async () => {
  relationLoading.value = true
  try {
    // 模拟数据：关系变动通知
    const mockData = [
      {
        id: 1,
        subType: 1, // 好友删除
        isRead: false,
        sentAt: Date.now() - 1800000,
        title: '好友关系变更',
        content: '用户 "小明" 已将您从好友列表中删除。',
        user: {
          id: 101,
          nick: '小明',
          avatar: 'https://picsum.photos/100/100?random=1'
        }
      },
      {
        id: 2,
        subType: 2,
        isRead: false,
        sentAt: Date.now() - 3600000,
        title: '群聊解散',
        content: '群聊 "技术交流群" 已被群主解散。',
        user: null
      },
      {
        id: 3,
        subType: 3,
        isRead: true,
        sentAt: Date.now() - 7200000,
        title: '被移出群聊',
        content: '您已被管理员移出群聊 "项目讨论组"。',
        user: {
          id: 102,
          nick: '管理员',
          avatar: 'https://picsum.photos/100/100?random=2'
        }
      },
      {
        id: 4,
        subType: 4,
        isRead: true,
        sentAt: Date.now() - 86400000,
        title: '好友自动清理',
        content: '因用户 "张三" 注销账号，其与您的好友关系已自动解除。',
        user: {
          id: 103,
          nick: '张三',
          avatar: 'https://picsum.photos/100/100?random=3'
        }
      }
    ]
    relationMessages.value = mockData
    relationUnread.value = mockData.filter(m => !m.isRead).length
  } catch (error) {
    ElMessage.error('获取关系消息失败')
  } finally {
    relationLoading.value = false
  }
}

const fetchInteractionMessages = async () => {
  interactionLoading.value = true
  try {
    const mockData = [
      {
        id: 1,
        subType: 1,
        isRead: false,
        sentAt: Date.now() - 1200000,
        title: '新粉丝',
        content: '用户 "小红" 关注了您。',
        user: {
          id: 201,
          nick: '小红',
          avatar: 'https://picsum.photos/100/100?random=10'
        }
      },
      {
        id: 2,
        subType: 2,
        isRead: false,
        sentAt: Date.now() - 3000000,
        title: '收藏了您的文章',
        content: '用户 "阿杰" 收藏了您的文章《Vue 3 组合式 API 入门指南》。',
        user: {
          id: 202,
          nick: '阿杰',
          avatar: 'https://picsum.photos/100/100?random=11'
        },
        target: '《Vue 3 组合式 API 入门指南》'
      },
      {
        id: 3,
        subType: 3,
        isRead: true,
        sentAt: Date.now() - 7200000,
        title: '评论了您的文章',
        content: '用户 "前端小白" 评论了您的文章："写得很棒，学到了很多！"',
        user: {
          id: 203,
          nick: '前端小白',
          avatar: 'https://picsum.photos/100/100?random=12'
        },
        target: '《Vue 3 组合式 API 入门指南》'
      },
      {
        id: 4,
        subType: 4,
        isRead: true,
        sentAt: Date.now() - 86400000,
        title: '回复了您的评论',
        content: '用户 "李老师" 回复了您在文章《TypeScript 进阶》下的评论。',
        user: {
          id: 204,
          nick: '李老师',
          avatar: 'https://picsum.photos/100/100?random=13'
        },
        target: '《TypeScript 进阶》'
      },
      {
        id: 5,
        subType: 5,
        isRead: false,
        sentAt: Date.now() - 172800000,
        title: '点赞了您的评论',
        content: '用户 "大强" 点赞了您在文章《CSS 布局技巧》中的评论。',
        user: {
          id: 205,
          nick: '大强',
          avatar: 'https://picsum.photos/100/100?random=14'
        },
        target: '《CSS 布局技巧》'
      }
    ]
    interactionMessages.value = mockData
    interactionUnread.value = mockData.filter(m => !m.isRead).length
  } catch (error) {
    ElMessage.error('获取互动消息失败')
  } finally {
    interactionLoading.value = false
  }
}

const viewMessage = (msg: any, type: 'relation' | 'interaction') => {
  if (!msg.isRead) {
    msg.isRead = true
    if (type === 'relation') {
      relationUnread.value--
    } else {
      interactionUnread.value--
    }
  }
}

const markAllAsRead = (type: 'relation' | 'interaction') => {
  if (type === 'relation') {
    relationMessages.value.forEach(msg => { msg.isRead = true })
    relationUnread.value = 0
  } else {
    interactionMessages.value.forEach(msg => { msg.isRead = true })
    interactionUnread.value = 0
  }
  ElMessage.success('已全部标为已读')
}

onMounted(() => {
  fetchRelationMessages()
  fetchInteractionMessages()
})
</script>

<style scoped>
/* 复用原有样式，微调 */
.messages-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
  padding: 24px;
  box-sizing: border-box;
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

.relation-icon {
  background: linear-gradient(135deg, #667eea20, #764ba220);
  color: #667eea;
}

.interaction-icon {
  background: linear-gradient(135deg, #f093fb20, #f5576c20);
  color: #f5576c;
}

.nav-item.active .relation-icon,
.nav-item.active .interaction-icon {
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

/* 消息列表 */
.message-list {
  flex: 1;
  overflow-y: auto;
  padding-right: 8px;
}

.message-list::-webkit-scrollbar {
  width: 6px;
}

.message-list::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 3px;
}

/* 消息卡片 - 统一风格 */
.message-card {
  background: #fafbfc;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid #f1f5f9;
  position: relative;
}

.message-card:hover {
  background: #fff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.message-card.unread {
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

/* 关系类型颜色 */
.type-friend-delete { background: #fee2e2; color: #dc2626; }
.type-group-dismiss { background: #fef3c7; color: #d97706; }
.type-group-kick { background: #fce4ec; color: #e11d48; }
.type-user-cleanup { background: #e0e7ff; color: #4f46e5; }
.type-default { background: #f1f5f9; color: #64748b; }

/* 互动类型颜色 */
.type-new-fan { background: #dbeafe; color: #2563eb; }
.type-collect { background: #fef3c7; color: #d97706; }
.type-comment { background: #e0e7ff; color: #4f46e5; }
.type-reply { background: #d1fae5; color: #059669; }
.type-comment-like { background: #fce7f3; color: #db2777; }

.msg-time {
  font-size: 12px;
  color: #94a3b8;
}

.card-body {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.msg-thumbnail {
  width: 60px;
  height: 60px;
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
  font-size: 28px;
}

.msg-info {
  flex: 1;
  min-width: 0;
}

.msg-title {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 6px 0;
}

.msg-content {
  font-size: 14px;
  color: #475569;
  line-height: 1.6;
}

.msg-target {
  margin-top: 6px;
  font-size: 13px;
  color: #94a3b8;
  background: #f8fafc;
  padding: 4px 10px;
  border-radius: 6px;
  display: inline-block;
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
  .card-body {
    flex-direction: column;
  }
  .msg-thumbnail {
    width: 100%;
    height: 120px;
  }
}
</style>