<template>
  <div class="search-container">
    <div class="top-navbar">
      <div class="nav-left">
        <el-button text @click="goBack" class="back-btn"><el-icon><ArrowLeft /></el-icon> 返回</el-button>
      </div>
      <div class="nav-center">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索..."
          size="small"
          clearable
          @keyup.enter="handleSearch"
          class="search-input"
        />
        <el-button :icon="Search" circle size="small" @click="handleSearch" class="search-button" />
      </div>
      <div class="nav-right"></div>
    </div>
    <div class="search-tabs">
      <el-tabs v-model="activeTab" @tab-change="onTabChange">
        <el-tab-pane label="文章" name="article" />
        <el-tab-pane label="用户" name="user" />
        <el-tab-pane label="标签" name="tag" />
      </el-tabs>
    </div>
    <div class="search-results" v-loading="loading" element-loading-text="搜索中...">
      <div v-if="lists[activeTab].total > 0" class="result-stats">找到 {{ lists[activeTab].total }} 个结果</div>
      <template v-if="activeTab === 'article'">
        <div v-for="item in lists.article.list" :key="item.id" class="result-item" @click="goToArticle(item.id)">
          <div class="item-title">{{ item.title }}</div>
          <div class="item-summary" v-html="highlightText(item.summary || item.content)"></div>
          <div class="item-meta">
            <div class="meta-left">
              <el-avatar :src="item.authorAvatar" :size="20" />
              <span class="author-name">{{ item.authorNick }}</span>
              <span class="publish-time">{{ formatDateShort(item.updated_at) }}</span>
            </div>
            <div class="meta-right">
              <el-tag v-for="tag in item.tags" :key="tag" size="small" class="tag">{{ tag }}</el-tag>
            </div>
          </div>
        </div>
      </template>
      <template v-if="activeTab === 'user'">
        <div v-for="item in lists.user.list" :key="item.id" class="result-item" @click="goToUser(item.id)">
          <div class="user-info">
            <el-avatar :src="item.user_avatar" :size="40" />
            <div class="user-detail">
              <div class="user-nick">{{ item.nick }}</div>
              <div class="user-username">@{{ item.user }}</div>
              <div class="user-id">ID: {{ item.id }}</div>
            </div>
            <el-button size="small" :type="item.isFollow ? 'default' : 'primary'" @click.stop="toggleFollow(item)">
              {{ item.isFollow ? '已关注' : '关注' }}
            </el-button>
          </div>
        </div>
      </template>
      <template v-if="activeTab === 'tag'">
        <div v-for="tag in lists.tag.list" :key="tag.id" class="result-item" @click="goToTag(tag.id)">
          <div class="tag-content">
            <span class="tag-name"># {{ tag.tag }}</span><span class="tag-count">{{ tag.post_count }} 篇文章</span>
          </div>
        </div>
      </template>
      <el-empty v-if="!loading && lists[activeTab].total === 0" description="没有找到相关内容" />
      <div v-if="hasMore" class="load-more" @click="loadMore" v-loading="loadingMore"><span>加载更多</span></div>
      <div v-else-if="lists[activeTab].total > 0 && !loading" class="load-end">—— 已加载全部 ——</div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { Search } from '@element-plus/icons-vue'
import type { SearchResponse, SearchUser } from '@/types/api/search'
import { formatDateShort } from '@/utils/dateFormatter'
const router = useRouter()
const route = useRoute()
const searchKeyword = ref<string>((route.query.keyword as string) || '')
const effectiveKeyword = ref<string>((route.query.keyword as string) || '')
const activeTab = ref<'article' | 'user' | 'tag'>((route.query.type as 'article' | 'user' | 'tag') || 'article')
const pageSize = 10
const loading = ref(false)
const loadingMore = ref(false)
const lists = ref({
  article: { list: [], total: 0, page: 1, isLoaded: false },
  user: { list: [], total: 0, page: 1, isLoaded: false },
  tag: { list: [], total: 0, page: 1, isLoaded: false }
})
const hasMore = computed<boolean>(() => lists.value[activeTab.value].list.length < lists.value[activeTab.value].total)
const escapeHtml = (text: string): string => {
  const div = document.createElement('div')
  div.textContent = text
  return div.innerHTML
}
const highlightText = (text: string): string => {
  if (!effectiveKeyword.value || !text) return text || ''
  let safeText = escapeHtml(text)
  const keyword = effectiveKeyword.value.trim()
  if (!keyword) return safeText
  const escapedKeyword = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const reg = new RegExp(escapedKeyword, 'gi')
  return safeText.replace(reg, (match) => `<span class="highlight">${match}</span>`)
}
const goBack = () => {
  router.back()
}
const goToArticle = (id: number) => {
  router.push(`/article/${id}`)
}
const goToUser = (id: number) => {
  router.push(`/user/${id}`)
}
const goToTag = (id: number) => {
  router.push(`/tag/${id}`)
}
const toggleFollow = async (user: SearchUser) => {
  try {
    const method = user.isFollow ? 'delete' : 'post'
    const res = await axios[method](`/api/user/${user.id}/follow`)
    if (res.data.code === 1) {
      user.isFollow = !user.isFollow
    }
  } catch (error) {
    console.error('关注操作失败', error)
  }
}
const handleSearch = () => {
  const keyword = searchKeyword.value.trim()
  if (!keyword) return
  effectiveKeyword.value = keyword
  router.replace({ query: { keyword } })
  for (const key in lists.value) {
    lists.value[key].list = []
    lists.value[key].page = 1
    lists.value[key].isLoaded = false
  }
  fetchResults(true)
}
const onTabChange = (tab: 'article' | 'user' | 'tag') => {
  activeTab.value = tab
  router.replace({ query: { type: tab } })
  const hasData = lists.value[tab].isLoaded
  if (!hasData && effectiveKeyword.value) {
    lists.value[tab].page = 1
    lists.value[tab].list = []
    fetchResults(false, false)
  }
}
const loadMore = () => {
  if (!hasMore.value) return
  lists.value[activeTab.value].page++
  fetchResults(false, true)
}
const fetchResults = async (reset = false, isLoadMore = false) => {
  if (loading.value || loadingMore.value) return
  if (isLoadMore) {
    loadingMore.value = true
  } else {
    loading.value = true
  }
  try {
    const params = {
      keyword: effectiveKeyword.value,
      type: activeTab.value,
      page: lists.value[activeTab.value].page,
      limit: pageSize
    }
    const response = await axios.get<SearchResponse>('/api/search', { params })
    const data = response.data
    if (data.code === 1) {
      lists.value[activeTab.value].total = data.pagination.total
      if (reset) {
        for (const key in lists.value) {
          lists.value[key].list = []
          lists.value[key].isLoaded = false
        }
      } else {
        if (lists.value[activeTab.value].list.length === 0) {
          lists.value[activeTab.value].list = []
        }
      }
      lists.value[activeTab.value].list.push(...data.data)
      lists.value[activeTab.value].isLoaded = true
    } else {
      console.error('搜索失败', data.msg)
    }
  } catch (error) {
    console.error('搜索请求失败', error)
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}
const handleScroll = () => {
  const container = document.querySelector('.search-results')
  if (!container) return
  const { scrollTop, scrollHeight, clientHeight } = container
  if (scrollTop + clientHeight >= scrollHeight - 50) {
    if (hasMore.value) {
      loadMore()
    }
  }
}
onMounted(() => {
  if (searchKeyword.value) {
    fetchResults(true)
  }
  const container = document.querySelector('.search-results')
  if (container) {
    container.addEventListener('scroll', handleScroll)
  }
})
onUnmounted(() => {
  const container = document.querySelector('.search-results')
  if (container) {
    container.removeEventListener('scroll', handleScroll)
  }
})
watch(() => route.query.keyword, newKeyword => {
  if (newKeyword && newKeyword !== searchKeyword.value) {
    searchKeyword.value = newKeyword as string
    handleSearch()
  }
})
</script>
<style scoped>
.author-name {
  color: #555;
}
.back-btn {
  font-size: 14px;
  color: #666;
}
.back-btn:hover {
  color: #2575fc;
}
.el-empty {
  margin-top: 40px;
}
.highlight {
  background: #ffeaa7;
  padding: 0 2px;
  border-radius: 2px;
}
.item-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  color: #999;
}
.item-summary {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.item-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
}
.load-end, .load-more {
  text-align: center;
  padding: 12px 0;
  color: #999;
  font-size: 13px;
}
.load-more {
  cursor: pointer;
  color: #2575fc;
}
.load-more:hover {
  text-decoration: underline;
}
.meta-left {
  display: flex;
  align-items: center;
  gap: 8px;
}
.nav-center {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  justify-content: center;
}
.nav-left {
  min-width: 80px;
}
.nav-right {
  min-width: 60px;
}
.publish-time {
  color: #bbb;
}
.result-item {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  transition: all 0.2s;
  cursor: pointer;
}
.result-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}
.result-stats {
  font-size: 13px;
  color: #888;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eee;
}
.search-button {
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.6);
  border: none;
  backdrop-filter: blur(4px);
  transition: all 0.2s;
}
.search-button:hover {
  background: rgba(255, 255, 255, 0.9);
  transform: scale(1.05);
}
.search-input {
  min-width: 60px;
  max-width: 300px;
  flex: 1;
}
.search-results {
  position: relative;
  z-index: 1;
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  height: calc(100vh - 140px);
  overflow-y: auto;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(4px);
  border-radius: 8px;
  margin-top: 10px;
}
.search-tabs {
  position: relative;
  z-index: 1;
  margin-top: 50px;
  padding: 0 20px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(4px);
  border-bottom: 1px solid #eee;
}
.tag {
  margin-left: 4px;
}
.tag-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}
.tag-count {
  font-size: 13px;
  color: #999;
  background: #f5f7fa;
  padding: 2px 10px;
  border-radius: 12px;
  white-space: nowrap;
}
.tag-name {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}
.tag-result {
  font-size: 16px;
  cursor: pointer;
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
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
.user-detail {
  flex: 1;
}
.user-id {
  font-size: 12px;
  color: #bbb;
}
.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.user-nick {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}
.user-username {
  font-size: 13px;
  color: #888;
}
:deep(.el-tabs__header) {
  margin-bottom: 0;
}
:deep(.el-tabs__item) {
  font-size: 14px;
}
@media (width <= 480px) {
  .back-btn span {
    display: none;
  }
  .back-btn .el-icon {
    margin-right: 0;
  }
}
@media (width <= 768px) {
  .search-input {
    max-width: 160px;
  }
  .search-results {
    padding: 12px;
    margin: 10px 8px;
    height: calc(100vh - 130px);
  }
}
</style>