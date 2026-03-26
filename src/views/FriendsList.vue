<template>
  <div class="friends-list">
    <div class="list-header">
      <div class="header-top">
        <div class="header-left">
          <el-button class="back-button" @click="goBack" text circle>
            <el-icon><ArrowLeft /></el-icon>
          </el-button>
          <h3 class="header-title">好友列表</h3>
        </div>
        <div class="header-buttons">
          <el-button type="primary">
            <el-icon><User /></el-icon>
            <span class="header-button-text">添加好友</span>
          </el-button>
          <el-button type="primary">
            <el-icon><Bell /></el-icon>
            <span class="header-button-text">好友请求</span>
            <el-badge v-if="newFriendsCount > 0" :value="newFriendsCount" :max="99" />
          </el-button>
          <el-button type="primary">
            <el-icon><Plus /></el-icon>
            <span class="header-button-text">创建群组</span>
          </el-button>
        </div>
      </div>
    </div>
    <div class="friends-container">
      <div v-if="loading" class="loading-state">
        <el-skeleton :rows="5" animated />
      </div>
      <el-empty v-else-if="friends.length === 0" description="您还没有好友，快去添加吧！" :image-size="100" />
      <div v-else class="friends-scrollable">
        <FriendItem 
          v-for="friend in friends" 
          :key="friend.id"
          :avatar="friend.avatar" 
          :name="getDisplayNick(friend)"
          :time="friend.time" 
          :count="friend.unread_count"
          :message="getDisplayContent(friend, currentUserId)"
          @click="selectFriend(friend)" 
        />
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import { getDisplayNick, getDisplayContent } from '@/utils/messageUtils'
import axios from 'axios'
import type { FriendItem, FriendListAPIResponseData, FriendRequestAPIResponseData } from '@/types/api'
import type { DialogConfigFunc } from '@/components/layout/BottomDialog.vue'
const router = useRouter()
const loading = ref<boolean>(false)
const friends = ref<FriendItem[]>([])
const currentUserId = ref<number>(0)
const newFriendsCount = ref<number>(0)
const showGlobalDialog = inject<DialogConfigFunc>('showGlobalDialog')
const fetchFriendsList = async (): Promise<void> => {
  loading.value = true
  try {
    const [friendsResponse, requestsResponse] = await Promise.all([
      axios.get<FriendListAPIResponseData>('/api/friends'),
      axios.get<FriendRequestAPIResponseData>('/api/requests')
    ])
    if (friendsResponse.data.code === 1) {
      friends.value = friendsResponse.data.data
      currentUserId.value = friendsResponse.data.user_id
    }
    if (requestsResponse.data.code === 1) {
      newFriendsCount.value = requestsResponse.data.received.length
    }
  } catch (error) {
    let message = '无法连接到服务器，请检查网络。'
    if (axios.isAxiosError(error)) {
      message = error.response.data.msg || message
    } else if (error instanceof Error) {
      message = error.message
    }
    showGlobalDialog({
      title: '错误',
      content: message
    })
  } finally {
    loading.value = false
  }
}
const selectFriend = (friend: FriendItem) => {
  router.push(`/chat/${friend.id}`)
}
const goBack = () => {
  router.back()
}
onMounted(fetchFriendsList)
</script>
<style scoped>
.back-button {
  padding: 8px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 50%;
  transition: background-color 0.2s;
}
.back-button:hover {
  background-color: rgba(0, 0, 0, 0.04);
}
.friends-container {
  flex: 1;
  padding: 0;
  overflow: hidden;
}
.friends-list {
  height: 100dvh;
  display: flex;
  flex-direction: column;
  background-color: #fff;
}
.friends-scrollable {
  height: 100%;
  overflow-y: auto;
}
.header-buttons {
  display: flex;
  gap: 8px;
}
.header-buttons .el-button .el-badge {
  margin-left: 8px;
}
.header-buttons .el-button .el-icon {
  margin-right: 8px;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.header-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}
.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.list-header {
  padding: 16px 20px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e4e7ed;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.loading-state {
  padding: 20px;
}
:deep(.el-empty) {
  padding: 60px 0;
}
:deep(.el-skeleton) {
  padding: 20px;
}
@media (max-width: 768px) {
  .header-buttons {
    width: 100%;
    justify-content: space-between;
  }
  .header-buttons .el-button {
    flex: 1;
    justify-content: center;
  }
  .header-top {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .list-header {
    padding: 12px 16px;
  }
}
@media (max-width: 480px) {
  .header-title {
    font-size: 16px;
  }
  .header-button-text {
    display: none;
  }
  .header-buttons .el-button .el-icon {
    margin-right: 0;
  }
  .list-header {
    padding: 10px 12px;
  }
}
</style>