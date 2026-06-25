<template>
  <div ref="profileRoot" class="profile-wrapper" :style="rootStyle" element-loading-text="加载中...">
    <button class="back-btn-fixed" @click="goBack"><el-icon><ArrowLeft /></el-icon>返回</button>
    <el-result v-if="pageError" icon="error" title="出错了" :sub-title="errorMessage">
      <template #extra><el-button type="primary" @click="reload">重试</el-button></template>
    </el-result>
    <div v-else :class="['profile-container', { 'has-background': !!userData.background }]">
      <div class="user-card">
        <div class="profile-header" :style="headerGradientStyle">
          <div class="avatar-container"><img :src="userData.avatar" class="avatar" alt="avatar" /></div>
          <div class="header-info">
            <h1 class="user-name" :style="headerTextStyle">{{ userData.nick }}</h1>
            <div class="user-meta" :style="headerTextStyle">
              <span class="username">@{{ userData.user }}</span>
              <span class="user-id">ID: {{ userData.id }}</span>
            </div>
          </div>
        </div>
        <div class="user-info">
          <el-row class="user-details" :gutter="16">
            <el-col :span="12" class="detail-item">
              <div class="detail-label">性别</div>
              <div class="detail-value" :style="{ color: detailValueColor }">{{ genderText }}</div>
            </el-col>
            <el-col :span="12" class="detail-item">
              <div class="detail-label">年龄</div>
              <div class="detail-value" :style="{ color: detailValueColor }">{{ ageText }}</div>
            </el-col>
          </el-row>
          <div class="bio-section">
            <div class="bio-title" :style="{ color: secondaryColor }">个人简介</div>
            <div class="bio-content">{{ userData.bio || '暂无简介' }}</div>
          </div>
          <div v-if="userData.friend_status !== 'self'" class="remark-section bio-section">
            <div class="bio-title" :style="{ color: secondaryColor }">
              <span>用户备注</span>
              <el-icon v-if="!remarkEditMode" class="edit-icon" @click="startEditRemark"><Edit /></el-icon>
            </div>
            <div v-if="!remarkEditMode" class="bio-content">{{ userData.remark || '未设置备注' }}</div>
            <div v-else class="remark-edit">
              <el-input v-model="remarkInput" placeholder="请输入备注" size="small" :maxlength="50" clearable />
              <div class="edit-actions">
                <el-button size="small" type="primary" @click="saveRemark" :loading="savingRemark">保存</el-button>
                <el-button size="small" @click="cancelEditRemark">取消</el-button>
              </div>
            </div>
          </div>
          <div v-if="userData.friend_status !== 'self'" class="action-buttons">
            <el-button
              class="btn-friend"
              :class="friendButtonClass"
              :loading="friendLoading"
              :disabled="friendButtonDisabled"
              @click="handleFriendAction"
            >{{ friendButtonText }}</el-button>
            <el-button class="btn-follow" :class="followButtonClass" :loading="followLoading" @click="toggleFollow">
              {{ followButtonText }}
            </el-button>
          </div>
        </div>
      </div>
      <div class="articles-card" v-loading="articlesLoading">
        <div class="card-header"><h3>文章</h3></div>
        <div v-if="articlesStatus === 'error'" class="error-articles">
          <el-icon><WarningFilled /></el-icon>
          <span>文章加载失败</span>
          <el-button type="primary" size="small" @click="reloadArticles">点击重试</el-button>
        </div>
        <div v-else-if="articlesStatus === 'success' && articles.length === 0" class="empty-articles">
          <span>这个人很懒，还没有发表过文章</span>
        </div>
        <ul v-else-if="articlesStatus === 'success' && articles.length > 0" class="article-list">
          <li v-for="article in articles" :key="article.id" class="article-item">
            <router-link :to="`/article/${article.id}`" class="article-link">
              <div class="article-title">{{ article.title }}</div>
              <div class="article-meta">
                <span>{{ formatDateShort(article.updateTime) }}</span><el-icon><ArrowRight /></el-icon>
              </div>
            </router-link>
          </li>
        </ul>
        <div v-if="loadMoreError" class="load-more-error">
          <el-icon><WarningFilled /></el-icon>
          <span>加载更多失败，请</span>
          <el-button type="primary" size="small" @click="reloadMore">重试</el-button>
        </div>
        <el-pagination
          v-if="articlesTotal > pageSize"
          background
          layout="prev,pager,next"
          :total="articlesTotal"
          :page-size="pageSize"
          :current-page="currentPage"
          @current-change="handlePageChange"
          class="pagination"
        />
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, reactive, type CSSProperties } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import type {
  PublicUser, GenderType, PublicUserAPIResponseData, UserArticle, UserArticleListAPIResponseData
} from '@/types/api'
import { formatDateShort } from '@/utils/dateFormatter'
import { ElMessage } from 'element-plus'
const route = useRoute()
const router = useRouter()
const pageLoading = ref(true)
const pageError = ref(false)
const errorMessage = ref('')
const userData = reactive<PublicUser>({
  id: 0,
  nick: '加载中...',
  user: '',
  avatar: '',
  gender: 'N',
  birth: null,
  bio: '',
  remark: '',
  background: null,
  theme_color: null,
  auto_theme: false,
  friend_status: 'self',
  follow_status: 0
})
const friendLoading = ref(false)
const savingRemark = ref(false)
const remarkEditMode = ref(false)
const remarkInput = ref('')
const followLoading = ref(false)
const articles = ref<UserArticle[]>([])
const articlesLoading = ref(false)
const currentPage = ref(1)
const pageSize = 10
const articlesTotal = ref(0)
const articlesStatus = ref<'loading' | 'success' | 'error'>('loading')
const loadMoreError = ref(false)
let originalBodyBg = ''
let articleRequestId = 0
const id = parseInt(route.params.id as string)
const getGenderText = (gender: GenderType): string => {
  if (gender === 'M') return '男'
  if (gender === 'W') return '女'
  return '未设置'
}
const getAgeText = (birth: string): string => {
  if (!birth) return '-'
  try {
    const [year, month, day] = birth.split('-').map(Number)
    const today = new Date()
    let age = today.getFullYear() - year
    const m = today.getMonth() + 1 - month
    if (m < 0 || (m === 0 && today.getDate() < day)) {
      age--
    }
    return age + '岁'
  } catch {
    return '-'
  }
}
const shadeColor = (color: string, percent: number): string => {
  if (!color || color.length < 7) return color
  try {
    let R = parseInt(color.substring(1, 3), 16)
    let G = parseInt(color.substring(3, 5), 16)
    let B = parseInt(color.substring(5, 7), 16)
    R = Math.min(255, Math.max(0, Math.floor(R * (100 + percent) / 100)))
    G = Math.min(255, Math.max(0, Math.floor(G * (100 + percent) / 100)))
    B = Math.min(255, Math.max(0, Math.floor(B * (100 + percent) / 100)))
    return `#${((1 << 24) + (R << 16) + (G << 8) + B).toString(16).slice(1)}`
  } catch {
    return color
  }
}
const getBrightness = (color: string): number => {
  if (!color || color.length < 7) return 0.5
  try {
    const r = parseInt(color.substring(1, 3), 16) / 255
    const g = parseInt(color.substring(3, 5), 16) / 255
    const b = parseInt(color.substring(5, 7), 16) / 255
    return 0.2126 * r + 0.7152 * g + 0.0722 * b
  } catch {
    return 0.5
  }
}
const genderText = computed(() => getGenderText(userData.gender))
const ageText = computed(() => getAgeText(userData.birth))
const primaryColor = computed(() => userData.theme_color || '#4361ee')
const detailValueColor = computed(() => {
  const color = primaryColor.value
  const brightness = getBrightness(color)
  if (brightness > 0.7) {
    return shadeColor(color, -50)
  }
  return color
})
const secondaryColor = computed(() => shadeColor(primaryColor.value, -20))
const headerGradientStyle = computed<CSSProperties>(() => ({
  background: `linear-gradient(135deg, ${primaryColor.value}, ${secondaryColor.value})`
}))
const headerTextStyle = computed<CSSProperties>(() => {
  const brightness = getBrightness(primaryColor.value)
  return { color: brightness > 0.5 ? '#000000' : '#ffffff' }
})
const rootStyle = computed<CSSProperties>(() => ({
  '--primary-color': primaryColor.value,
  '--secondary-color': secondaryColor.value,
  '--btn-add-bg': `linear-gradient(135deg, ${primaryColor.value}, ${secondaryColor.value})`
}))
const friendButtonText = computed(() => {
  switch (userData.friend_status) {
    case 'true': return '已添加'
    case 'pending': return '等待对方同意'
    case 'requested': return '同意请求'
    default: return '添加好友'
  }
})
const friendButtonClass = computed(() => {
  if (userData.friend_status === 'true' || userData.friend_status === 'pending') {
    return 'btn-added'
  }
  return 'btn-add'
})
const friendButtonDisabled = computed(() => {
  return friendLoading.value || 
    ['true', 'pending'].includes(userData.friend_status)
})
const followButtonText = computed(() => {
  switch (userData.follow_status) {
    case 0: return '关注'
    case 1: return '已关注'
    case 2: return '回关'
    case 3: return '互相关注'
  }
})
const followButtonClass = computed(() => userData.follow_status ? 'btn-following' : 'btn-follow')
async function fetchUserData() {
  try {
    const res = await axios.get<PublicUserAPIResponseData>(`/api/user/${id}`)
    if (res.data.code === 1) {
      Object.assign(userData, res.data.data)
    } else {
      throw new Error(res.data.msg)
    }
  } catch (err) {
    if (axios.isAxiosError(err)) {
      throw new Error(err.response.data.msg)
    }
    throw new Error(err.message)
  }
}
async function updateRemark(targetId: string, remark: string) {
  const res = await axios.post(`/set_remark.php`,
    new URLSearchParams({ target: targetId, remark }),
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
  )
  return res.data
}
async function fetchArticles(userId: number, page: number, limit: number) {
  const res = await axios.get<UserArticleListAPIResponseData>(`/api/user/${userId}/articles`, {
    params: { page, limit }
  })
  return res.data
}
function applyBackground(url: string | null) {
  if (url) {
    document.body.style.background = `url(${url}) center / cover no-repeat fixed`
    document.body.classList.add('has-background')
  } else {
    document.body.style.background = 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)'
    document.body.classList.remove('has-background')
  }
}
function resetBackground() {
  document.body.classList.remove('has-background')
  document.body.style.background = originalBodyBg
}
async function loadProfile() {
  pageLoading.value = true
  pageError.value = false
  try {
    await fetchUserData()
    await loadArticles(currentPage.value)
    pageLoading.value = false
  } catch (err) {
    pageError.value = true
    errorMessage.value = err.message || '加载失败。'
    pageLoading.value = false
  }
}
async function loadArticles( page: number) {
  const requestId = ++articleRequestId
  articlesStatus.value = 'loading'
  loadMoreError.value = false
  try {
    const data = await fetchArticles(id, page, pageSize)
    if (requestId !== articleRequestId) return
    if (data.code === 1) {
      articles.value = data.data
      articlesTotal.value = data.pagination.total
      articlesStatus.value = 'success'
    } else {
      throw new Error(data.msg)
    }
  } catch (err) {
    if (requestId === articleRequestId) {
      if (page === 1) {
        articles.value = []
        articlesTotal.value = 0
        articlesStatus.value = 'error'
      } else {
        loadMoreError.value = true
        articlesStatus.value = 'success'
        ElMessage.error('加载更多失败，请稍后重试')
      }
    }
  } finally {
    if (requestId === articleRequestId && articlesStatus.value !== 'error') {
      articlesStatus.value = 'success'
    }
  }
}
const handleFriendAction = () => {}
const toggleFollow = async () => {
  followLoading.value = true
  try {
    const res = await axios.request({
      url: `/api/user/${userData.id}/follow`,
      method: userData.follow_status ? 'delete' : 'post'
    })
    if (res.data.code === 1) {
      userData.follow_status ^= 1
    } else {
      throw new Error(res.data.msg)
    }
  } catch (err) {
    if (axios.isAxiosError(err)) {
      throw new Error(err.response.data.msg)
    }
    throw new Error(err.message)
  } finally {
    followLoading.value = false
  }
}
const startEditRemark = () => {
  remarkInput.value = userData.remark || ''
  remarkEditMode.value = true
}
const cancelEditRemark = () => {
  remarkEditMode.value = false
  remarkInput.value = ''
}
const saveRemark = async () => {
  const targetId = route.params.id as string
  if (!targetId) return
  savingRemark.value = true
  try {
    const data = await updateRemark(targetId, remarkInput.value)
    if (data.code === 1) {
      userData.remark = remarkInput.value
      remarkEditMode.value = false
    } else {
      throw new Error(data.msg)
    }
  } catch (err) {
  } finally {
    savingRemark.value = false
  }
}
const handlePageChange = (page: number) => {
  currentPage.value = page
  if (id) {
    loadArticles(page)
  }
}
const reload = () => {
  const id = route.params.id as string
  if (id) loadProfile()
}
const goBack = () => {
  router.back()
}
const reloadArticles = async () => {
  await loadArticles(currentPage.value)
}
const reloadMore = () => {
  loadMoreError.value = false
  loadArticles(currentPage.value)
}
onMounted(() => {
  originalBodyBg = document.body.style.background
  if (!id) {
    pageError.value = true
    errorMessage.value = '用户ID参数缺失。'
    pageLoading.value = false
    return
  }
  loadProfile()
})
onUnmounted(resetBackground)
watch(() => userData.background, newBg => {
  applyBackground(newBg)
})
watch(() => route.params.id, (newId, oldId) => {
  if (newId && newId !== oldId) {
    currentPage.value = 1
    articles.value = []
    articlesTotal.value = 0
    loadProfile()
  }
})
</script>
<style scoped>
.action-buttons {
  display: flex;
  gap: 12px;
  margin-top: 25px;
}
.action-buttons .el-button {
  flex: 1;
  padding: 12px 0;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1.1rem;
  border: none;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  min-width: 120px;
}
.action-buttons .el-button:not(.is-disabled):hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}
.article-item {
  border-bottom: 1px solid #f1f3f5;
  transition: background-color 0.2s;
}
.article-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}
.article-link {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  text-decoration: none;
  color: inherit;
}
.article-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.article-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6c757d;
  font-size: 0.9rem;
}
.article-title {
  font-size: 1rem;
  font-weight: 500;
  color: var(--dark-text);
}
.articles-card {
  width: 100%;
  background: white;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  box-sizing: border-box;
}
.avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.avatar-container {
  width: 100px;
  height: 100px;
  border: 4px solid rgba(255, 255, 255, 0.8);
  border-radius: 50%;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  background: linear-gradient(45deg, #e0e0e0, #f5f5f5);
  flex-shrink: 0;
}
.back-btn-fixed {
  position: fixed;
  top: 20px;
  left: 20px;
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 40px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  color: #333;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}
.back-btn-fixed:hover {
  background: white;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}
.back-btn-fixed .el-icon {
  font-size: 18px;
}
.bio-content {
  font-size: 1rem;
  line-height: 1.6;
  color: var(--dark-text);
  white-space: pre-wrap;
  word-break: break-word;
}
.bio-section {
  background-color: var(--light-bg);
  padding: 20px;
  border-radius: 12px;
  margin-top: 15px;
}
.bio-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-add {
  background: var(--btn-add-bg);
  color: white;
}
.btn-added {
  background: linear-gradient(135deg, #4cc9f0, #4895ef);
  color: white;
  opacity: 0.9;
  cursor: default;
}
.btn-added.is-disabled {
  opacity: 0.7;
}
.btn-follow {
  background: linear-gradient(135deg, #f72585, #b5179e);
  color: white;
}
.btn-following {
  background: linear-gradient(135deg, #4cc9f0, #4895ef);
  color: white;
  opacity: 0.9;
}
.card-header {
  margin-bottom: 15px;
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 10px;
}
.card-header h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--dark-text);
  margin: 0;
}
.detail-item {
  text-align: center;
}
.detail-label {
  font-size: 0.85rem;
  color: var(--dark-text);
  margin-bottom: 5px;
}
.detail-value {
  font-size: 1.2rem;
  font-weight: 600;
  transition: color 0.3s;
}
.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
.edit-icon {
  cursor: pointer;
  font-size: 0.9rem;
  color: #6c757d;
  transition: color 0.2s;
}
.edit-icon:hover {
  color: var(--primary-color);
}
.empty-articles {
  text-align: center;
  color: #6c757d;
  padding: 30px 0;
}
.error-articles {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  margin: 10px 0;
  transition: all 0.3s ease;
}
.error-articles span {
  font-size: 1.1rem;
  color: #b33a3a;
  font-weight: 500;
}
.error-articles .el-button {
  margin-top: 4px;
  border-radius: 30px;
  padding: 10px 28px;
  font-weight: 600;
  background: linear-gradient(135deg, #f56c6c, #ee5a5a);
  border: none;
  color: #fff;
  transition: all 0.25s ease;
  box-shadow: 0 4px 12px rgba(245, 108, 108, 0.35);
}
.error-articles .el-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(245, 108, 108, 0.45);
  background: linear-gradient(135deg, #f77a7a, #e84a4a);
}
.error-articles .el-icon {
  font-size: 48px;
  color: #f56c6c;
  animation: error-pulse 2s ease-in-out infinite;
}
.header-info {
  flex: 1;
  word-break: break-all;
}
.load-more-error {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 15px 0 10px;
  padding: 10px;
  background-color: #fef0f0;
  border: 1px solid #fde2e2;
  border-radius: 8px;
  color: #f56c6c;
}
.load-more-error .el-button {
  margin-left: 4px;
}
.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
.profile-container {
  width: 100%;
  margin: 40px auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 0 20px;
  box-sizing: border-box;
}
.profile-container.has-background .articles-card {
  background-color: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(2px);
}
.profile-container.has-background .user-card {
  background-color: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(2px);
}
.profile-header {
  padding: 25px 20px;
  display: flex;
  align-items: center;
  gap: 20px;
}
.profile-wrapper {
  --primary-color: #4361ee;
  --secondary-color: #3f37c9;
  --light-bg: #f8f9fa;
  --dark-text: #212529;
  min-height: 100vh;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
}
.remark-edit {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.user-card {
  width: 100%;
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  box-sizing: border-box;
}
.user-details {
  display: flex;
  justify-content: space-around;
  padding: 15px;
  background-color: var(--light-bg);
  border-radius: 12px;
  margin-bottom: 20px;
}
.user-id {
  opacity: 0.7;
}
.user-info {
  padding: 25px;
}
.user-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 1rem;
}
.user-name {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 5px;
  transition: color 0.3s;
}
.username {
  opacity: 0.9;
}
@keyframes error-pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.08);
    opacity: 0.8;
  }
}
@media (min-width: 992px) {
  .articles-card {
    flex: 1;
    min-width: 0;
  }
  .profile-container {
    flex-direction: row;
    max-width: 1200px;
    align-items: flex-start;
  }
  .user-card {
    flex: 0 0 35%;
    max-width: 35%;
  }
}
@media (max-width: 768px) {
  .back-btn-fixed {
    top: 10px;
    left: 10px;
    padding: 6px 12px;
    font-size: 13px;
  }
}
</style>
<style>
body.has-background {
  background-attachment: fixed;
}
</style>