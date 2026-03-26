<template>
  <div class="home-container">
    <div class="background-section" :style="backgroundStyle"></div>
    <div class="top-navbar">
      <div class="nav-left">
        <div class="user-info" @click="toggleUserCard" ref="userInfoRef">
          <el-avatar :src="avatar" :size="16" />
          <span class="nickname">{{ nickname }}</span>
        </div>
        <div class="user-card" v-show="showUserCard" ref="userCardRef">
          <div class="card-header">
            <div class="card-avatar">
              <el-avatar :src="avatar" :size="48" />
            </div>
            <div class="card-user-info">
              <div class="card-nickname">{{ nickname }}</div>
              <div class="card-details">
                <div class="card-detail-item">ID：{{ userId }}</div>
                <div class="card-detail-item">用户名：{{ username }}</div>
              </div>
            </div>
            <div class="card-edit" @click="goToProfile">
              <el-icon><Edit /></el-icon>
              <span>修改</span>
            </div>
          </div>
          <div class="card-divider"></div>
          <div class="card-menu">
            <div class="menu-item" @click="goToHomePage">
              <el-icon><House /></el-icon>
              <span>我的主页</span>
              <el-icon><ArrowRight /></el-icon>
            </div>
            <div class="menu-item" @click="goToFollowing">
              <el-icon><User /></el-icon>
              <span>我的关注</span>
              <el-icon><ArrowRight /></el-icon>
            </div>
            <div class="menu-item" @click="goToFollowers">
              <el-icon><UserFilled /></el-icon>
              <span>我的粉丝</span>
              <el-icon><ArrowRight /></el-icon>
            </div>
            <div class="menu-item" @click="goToMyArticles">
              <el-icon><Document /></el-icon>
              <span>我的文章</span>
              <el-icon><ArrowRight /></el-icon>
            </div>
            <div class="menu-item" @click="goToSettings">
              <el-icon><Setting /></el-icon>
              <span>设置</span>
              <el-icon><ArrowRight /></el-icon>
            </div>
          </div>
        </div>
      </div>
      <div class="nav-right">
        <div class="nav-icons">
          <div class="nav-icon" @click="goToMessages" title="我的消息">
            <el-icon><Bell /></el-icon>
            <span class="text">我的消息</span>
            <el-badge :value="unreadMessages" :offset="[0, 6]" type="danger" :max="99" v-if="unreadMessages !== 0" />
          </div>
          <div class="nav-icon" @click="goToFriends" title="好友列表">
            <el-icon><UserFilled /></el-icon>
            <span class="text">好友列表</span>
            <el-badge :value="friendRequests" :offset="[0, 6]" type="danger" :max="99" v-if="friendRequests !== 0" />
          </div>
          <div class="nav-icon" @click="handleLogout" title="退出登录">
            <el-icon><SwitchButton /></el-icon>
            <span class="text">退出登录</span>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <WaterfallFlow />
    </div>
    <div class="floating-write-btn" @click="goToWriteArticle" title="写文章" :style="writeButtonStyle">
      <el-icon><Edit /></el-icon>
      <span class="btn-text">写文章</span>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { getContrastColor } from '@/utils/color'
import type { APIResponse, PrivateUserAPIResponseData } from '@/types/api'
const router = useRouter()
const userData = ref<PrivateUserAPIResponseData>({} as PrivateUserAPIResponseData)
const userInfoRef = ref<HTMLDivElement | null>(null)
const userCardRef = ref<HTMLDivElement | null>(null)
const showUserCard = ref<boolean>(false)
const avatar = computed<string>(() => userData.value.data?.avatar || 'default.png')
const nickname = computed<string>(() => userData.value.data?.nick || '默认昵称')
const userId = computed<number>(() => userData.value.data?.id || 0)
const username = computed<string>(() => userData.value.data?.user || 'default_user')
const customBackground = computed<string>(() => userData.value.data?.background || '')
const themeColor = computed<string>(() => userData.value.data?.theme_color || '')
const unreadMessages = computed<number>(() => userData.value.data?.systemMessageUnreadCount)
const friendRequests = computed<number>(() => 
  userData.value.data?.friendUnreadCount +
  userData.value.data?.friendRequestCount
)
const backgroundStyle = computed(() => {
  if (customBackground.value) {
    return {
      backgroundImage: `url(${customBackground.value})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
      backgroundRepeat: 'no-repeat'
    }
  } else {
    return {
      background: 'linear-gradient(135deg, #6a11cb 0%, #2575fc 100%)'
    }
  }
})
const writeButtonStyle = computed(() => {
  if (themeColor.value) {
    const textColor = getContrastColor(themeColor.value)
    return {
      background: themeColor.value,
      color: textColor,
      boxShadow: `0 4px 20px ${themeColor.value}33`
    }
  } else {
    return {
      background: 'linear-gradient(135deg, #6a11cb 0%, #2575fc 100%)',
      color: 'white',
      boxShadow: '0 4px 20px rgba(37, 117, 252, 0.3)'
    }
  }
})
const handleClickOutside = (event: MouseEvent) => {
  if (
    userInfoRef.value && 
    userCardRef.value &&
    !userInfoRef.value.contains(event.target as Node) &&
    !userCardRef.value.contains(event.target as Node)
  ) {
    showUserCard.value = false
  }
}
const toggleUserCard = (event: MouseEvent) => {
  event.stopPropagation()
  showUserCard.value = !showUserCard.value
}
const goToMessages = () => {
  router.push('/system')
}
const goToFriends = () => {
  router.push('/friends')
}
const goToProfile = () => {
  router.push('/profile')
}
const goToWriteArticle = () => {
  router.push('/write')
}
const goToFollowing = () => {
  router.push('/following')
  showUserCard.value = false
}
const goToFollowers = () => {
  router.push('/followers')
  showUserCard.value = false
}
const goToMyArticles = () => {
  router.push('/my-articles')
  showUserCard.value = false
}
const goToSettings = () => {
  router.push('/settings')
  showUserCard.value = false
}
const goToHomePage = () => {
  router.push('/user/' + userId.value)
  showUserCard.value = false
}
onMounted(async () => {
  try {
    const response = await axios.get('/api/self')
    const data: PrivateUserAPIResponseData = response.data
    if (data.code === 1) {
      userData.value = data
    }
  } catch (error) {
    console.error('获取用户信息失败:', error)
    if (axios.isAxiosError(error) && error.response.status === 401) {
      router.push('/login')
    }
  }
  document.addEventListener('click', handleClickOutside)
})
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
const handleLogout = async () => {
  try {
    const response: { data: APIResponse } = await axios.post('/api/logout', null, {
      withCredentials: true
    })
    if (response.data.code === 1) {
      router.push('/login')
    }
  } catch (error) {
    console.error('退出登录失败:', error)
    alert('退出登录时发生错误')
  }
}
</script>
<style scoped>
.background-section {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
}
.card-avatar {
  flex-shrink: 0;
}
.card-detail-item {
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-details {
  font-size: 12px;
  color: #666;
}
.card-divider {
  height: 1px;
  background: #f0f0f0;
  margin: 0 16px;
}
.card-edit {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 10px;
  background: #f5f5f5;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  color: #666;
  transition: all 0.2s ease;
  flex-shrink: 0;
}
.card-edit:hover {
  background: #e8e8e8;
  color: #2575fc;
}
.card-edit .el-icon {
  margin-right: 4px;
}
.card-header {
  display: flex;
  align-items: center;
  padding: 16px;
  gap: 12px;
}
.card-menu {
  padding: 8px 0;
}
.card-nickname {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-user-info {
  flex: 1;
  min-width: 0;
}
.container {
  position: relative;
  z-index: 1;
  padding: 20px;
  margin-top: 40px;
}
.floating-write-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  padding: 12px 20px;
  border-radius: 50px;
  box-shadow: 0 4px 20px rgba(37, 117, 252, 0.3);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  z-index: 1000;
  font-size: 14px;
  font-weight: 600;
}
.floating-write-btn:active {
  transform: translateY(0);
}
.floating-write-btn:hover {
  transform: translateY(-2px);
  filter: brightness(1.1);
}
.floating-write-btn .el-icon {
  font-size: 16px;
}
.home-container {
  position: relative;
}
.menu-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #333;
  font-size: 14px;
}
.menu-item:hover {
  background: #f8f9fa;
  color: #2575fc;
}
.menu-item .el-icon:first-child {
  width: 20px;
  margin-right: 12px;
  color: #666;
  font-size: 14px;
  text-align: center;
}
.menu-item .el-icon:last-child {
  color: #999;
  font-size: 12px;
}
.menu-item span {
  flex: 1;
}
.nav-icon {
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #666;
  font-size: 15px;
  gap: 10px;
  white-space: nowrap;
  padding: 5px 10px;
}
.nav-icon .el-icon {
  font-size: 16px;
}
.nav-icon:hover,
.user-info:hover {
  background: rgba(0, 0, 0, 0.05);
  color: #2575fc;
  transform: translateY(-2px);
}
.nav-icons {
  display: flex;
  align-items: center;
  gap: 10px;
}
.nav-left {
  min-width: 0;
}
.nickname {
  font-size: 14px;
  font-weight: 600;
  flex-shrink: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: default;
}
.top-navbar {
  position: fixed;
  top: 0;
  left: 0;
  width: calc(100% - 20px);
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 40px;
  padding: 0 10px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
.user-info {
  display: flex;
  align-items: center;
  max-width: 100%;
  margin-right: 20px;
  padding: 5px;
  border-radius: 8px;
  transition: all 0.3s ease;
  cursor: pointer;
  gap: 10px;
}
.user-info .el-avatar {
  flex-shrink: 0;
}
.user-card {
  position: absolute;
  top: 100%;
  left: 8px;
  margin-top: 8px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  width: 280px;
  z-index: 1001;
  overflow: hidden;
  animation: fadeIn 0.2s ease;
  border: 1px solid rgba(0, 0, 0, 0.1);
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
@media (max-width: 250px) {
  .nickname {
    display: none;
  }
}
@media (max-width: 480px) {
  .floating-write-btn {
    bottom: 15px;
    right: 15px;
    width: 45px;
    height: 45px;
  }
  .nav-icon {
    height: 20px;
    font-size: 16px;
    gap: 5px;
  }
  .nav-icons {
    gap: 10px;
  }
  .text {
    display: none;
  }
  .user-card {
    width: 260px;
  }
}
@media (max-width: 768px) {
  .btn-text {
    display: none;
  }
  .floating-write-btn {
    bottom: 20px;
    right: 20px;
    padding: 0;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    justify-content: center;
  }
}
</style>