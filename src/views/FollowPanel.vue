<template>
  <div class="follow-panel">
    <div class="panel-header">
      <div class="back-btn" @click="handleBack"><el-icon><ArrowLeft /></el-icon><span>返回</span></div>
      <span class="panel-title">社交关系</span>
      <span class="header-placeholder"></span>
    </div>
    <el-tabs v-model="activeTab" class="follow-tabs" stretch>
      <el-tab-pane label="关注" name="followings">
        <div class="user-list">
          <FollowUserItem v-for="user in followings.list.value" :key="user.user_id" :user="user" />
          <div v-if="followings.hasMore.value" ref="sentinelFollowings" class="sentinel"></div>
          <div v-if="followings.loading.value" class="load-tip">加载中...</div>
          <div v-if="!followings.loading.value && followings.list.value.length === 0" class="empty-state">暂无关注</div>
        </div>
      </el-tab-pane>
      <el-tab-pane label="粉丝" name="followers">
        <div class="user-list">
          <FollowUserItem v-for="user in followers.list.value" :key="user.user_id" :user="user" />
          <div v-if="followers.hasMore.value" ref="sentinelFollowers" class="sentinel"></div>
          <div v-if="followers.loading.value" class="load-tip">加载中...</div>
          <div v-if="!followers.loading.value && followers.list.value.length === 0" class="empty-state">还没有粉丝</div>
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { FollowListResponse, UserFollowInfo } from '@/types/api/follow'
import axios from 'axios'
const fetchFollowings = async (page: number, pageSize: number): Promise<FollowListResponse> => {
  const res = await axios.get<FollowListResponse>('/api/self/followings', {
    params: { page, limit: pageSize }
  })
  return res.data
}
const fetchFollowers = async (page: number, pageSize: number): Promise<FollowListResponse> => {
  const res = await axios.get<FollowListResponse>('/api/self/followers', {
    params: { page, limit: pageSize }
  })
  return res.data
}
function useFollowList(fetchFn: (page: number, pageSize: number) => Promise<FollowListResponse>) {
  const list = ref<UserFollowInfo[]>([])
  const page = ref(1)
  const pageSize = 30
  const total = ref(0)
  const loading = ref(false)
  const hasMore = computed(() => list.value.length < total.value)
  const reset = async () => {
    list.value = []
    page.value = 1
    total.value = 0
    await loadMore(true)
  }
  const loadMore = async (force = false) => {
    if (loading.value) return
    if (!force && !hasMore.value) return
    loading.value = true
    try {
      const res = await fetchFn(page.value, pageSize)
      list.value = [...list.value, ...res.data]
      total.value = res.pagination.total
      page.value++
    } catch (error) {
      console.error('加载失败', error)
    } finally {
      loading.value = false
    }
  }
  return { list, loading, hasMore, loadMore, reset }
}
const route = useRoute()
const router = useRouter()
const activeTab = computed({
  get: () => (route.name === 'Followers' ? 'followers' : 'followings'),
  set: (val: string) => {
    if (val === 'followers') {
      router.replace({ name: 'Followers' })
    } else {
      router.replace({ name: 'Followings' })
    }
  }
})
const handleBack = () => {
  router.back()
}
const followings = useFollowList(fetchFollowings)
const followers = useFollowList(fetchFollowers)
const sentinelFollowings = ref<HTMLElement | null>(null)
const sentinelFollowers = ref<HTMLElement | null>(null)
let observerFollowing: IntersectionObserver | null = null
let observerFollowers: IntersectionObserver | null = null
function observeSentinel(
  sentinel: HTMLElement | null,
  loadMoreFn: () => Promise<void>,
  observerRef: { value: IntersectionObserver | null }
) {
  if (observerRef.value) {
    observerRef.value.disconnect()
    observerRef.value = null
  }
  if (!sentinel) return
  const observer = new IntersectionObserver(
    entries => entries[0].isIntersecting && loadMoreFn(),
    { rootMargin: '0px 0px 100px 0px' }
  )
  observer.observe(sentinel)
  observerRef.value = observer
}
watch(
  [() => followings.list.value.length, () => followings.hasMore.value],
  async () => {
    await nextTick()
    observeSentinel(sentinelFollowings.value, followings.loadMore, {
      value: observerFollowing,
    })
  },
  { deep: false }
)
watch(
  [() => followers.list.value.length, () => followers.hasMore.value],
  async () => {
    await nextTick()
    observeSentinel(sentinelFollowers.value, followers.loadMore, {
      value: observerFollowers,
    })
  },
  { deep: false }
)
watch(activeTab, async (newVal) => {
  if (newVal === 'followers' && followers.list.value.length === 0) {
    await followers.reset()
  } else if (newVal === 'followings' && followings.list.value.length === 0) {
    await followings.reset()
  }
})
onMounted(async () => {
  if (activeTab.value === 'followers') {
    await followers.reset()
  } else {
    await followings.reset()
  }
})
onUnmounted(() => {
  if (observerFollowing) {
    observerFollowing.disconnect()
    observerFollowing = null
  }
  if (observerFollowers) {
    observerFollowers.disconnect()
    observerFollowers = null
  }
})
</script>
<style scoped>
.back-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  color: #595959;
  font-size: 14px;
  font-weight: 500;
  padding: 4px 0;
  transition: color 0.2s ease;
  user-select: none;
}
.back-btn:hover {
  color: #6366f1;
}
.back-btn .el-icon {
  font-size: 18px;
}
.empty-state {
  text-align: center;
  padding: 40px 0;
  color: #bfbfbf;
  font-size: 14px;
  grid-column: 1 / -1;
}
.follow-panel {
  max-width: 600px;
  margin: 0 auto;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
  padding: 0 0 12px 0;
  overflow: hidden;
}
.follow-tabs {
  --el-tabs-header-height: 48px;
}
.header-placeholder {
  width: 56px;
  flex-shrink: 0;
}
.load-tip {
  text-align: center;
  padding: 16px 0;
  color: #8c8c8c;
  font-size: 13px;
  grid-column: 1 / -1;
}
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px 10px 16px;
  border-bottom: 1px solid #f0f0f0;
  background: #fff;
}
.panel-title {
  font-weight: 600;
  font-size: 17px;
  color: #1a1a1a;
  letter-spacing: 0.5px;
}
.sentinel {
  height: 1px;
  margin-top: -1px;
  pointer-events: none;
}
.user-list {
  padding: 8px 16px;
}
:deep(.el-tabs__active-bar) {
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  height: 3px;
  border-radius: 2px;
}
:deep(.el-tabs__item) {
  font-size: 16px;
  font-weight: 500;
  color: #8c8c8c;
}
:deep(.el-tabs__item.is-active) {
  color: #1a1a1a;
  font-weight: 600;
}
@media (min-width: 1400px) {
  .follow-panel {
    max-width: 1320px;
    padding: 0 0 20px 0;
  }
  .panel-header {
    padding: 20px 24px 14px 24px;
  }
  .panel-title {
    font-size: 20px;
  }
  .user-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 16px 24px;
  }
  :deep(.follow-btn) {
    padding: 8px 20px;
    font-size: 13px;
  }
  :deep(.user-bio) {
    max-width: 280px;
  }
  :deep(.user-item) {
    border-bottom: none;
    border: 1px solid #f0f0f0;
    border-radius: 12px;
    padding: 16px 20px;
    transition: box-shadow 0.25s, transform 0.2s;
    background: #ffffff;
  }
  :deep(.user-item:hover) {
    background: #fafafa;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
  }
}
</style>