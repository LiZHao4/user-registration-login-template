<template>
  <div class="user-item" @click="goToUser">
    <el-avatar :src="user.avatar" :size="48" />
    <div class="user-info">
      <div class="user-name">{{ user.remark || user.nick }}</div>
      <div class="user-bio">{{ user.bio || '这个人很懒，什么都没留下' }}</div>
    </div>
    <el-button
      type="primary"
      plain
      size="small"
      :class="['follow-btn', { 'followed': user.follow_status % 2 == 1 }]"
      @click.stop="handleFollow"
    >{{ buttonText }}</el-button>
  </div>
</template>
<script setup lang="ts">
import type { UserFollowInfo } from '@/types/api/follow'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { computed } from 'vue'
const props = defineProps<{
  user: UserFollowInfo
}>()
const emit = defineEmits<{
  (e: 'follow', userId: number, newStatus: number): void
}>()
const router = useRouter()
const goToUser = () => {
  router.push(`/user/${props.user.user_id}`)
}
const buttonText = computed(() => {
  const status = props.user.follow_status
  if (status === 0) return '关注'
  else if (status === 1) return '已关注'
  else if (status === 2) return '回关'
  else return '互相关注'
})
const handleFollow = async () => {
  const currentStatus = props.user.follow_status
  const apiUrl = `/api/user/${props.user.user_id}/follow`
  const method = currentStatus % 2 === 0 ? 'post' : 'delete'
  try {
    await axios[method](apiUrl)
    emit('follow', props.user.user_id, currentStatus ^ 1)
  } catch {}
}
</script>
<style scoped>
.follow-btn {
  flex-shrink: 0;
  border-radius: 20px;
  padding: 6px 16px;
  font-size: 12px;
  border-color: #6366f1;
  color: #6366f1;
  transition: all .25s;
}
.follow-btn:hover {
  background: #6366f1;
  color: #fff;
}
.follow-btn.followed {
  background: #f0f0f0;
  border-color: #d9d9d9;
  color: #595959;
}
.follow-btn.followed:hover {
  background: #e6e6e6;
  border-color: #bfbfbf;
  color: #262626;
}
.user-bio {
  font-size: 13px;
  color: #8c8c8c;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 180px;
}
.user-info {
  flex: 1;
  margin-left: 14px;
  min-width: 0;
}
.user-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color .2s;
}
.user-item:hover {
  background: #fafafa;
}
.user-name {
  font-size: 15px;
  font-weight: 500;
  color: #1a1a1a;
  display: flex;
  align-items: center;
  gap: 6px;
}
</style>