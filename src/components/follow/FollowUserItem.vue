<template>
  <div class="user-item">
    <el-avatar :src="user.avatar" :size="48" />
    <div class="user-info">
      <div class="user-name">{{ user.nick }}</div>
      <div class="user-bio">{{ user.bio || '这个人很懒，什么都没留下' }}</div>
    </div>
    <el-button type="primary" plain size="small" :class="['follow-btn', { 'followed': user.follow_status % 2 == 1 }]">{{
      user.follow_status === 0 ? '关注' :
      user.follow_status === 1 ? '已关注' :
      user.follow_status === 2 ? '回关' :
      '互相关注'
    }}</el-button>
  </div>
</template>
<script setup lang="ts">
import type { UserFollowInfo } from '@/types/api/follow'
defineProps<{
  user: UserFollowInfo
}>()
</script>
<style scoped>
.follow-btn {
  flex-shrink: 0;
  border-radius: 20px;
  padding: 6px 16px;
  font-size: 12px;
  border-color: #6366f1;
  color: #6366f1;
  transition: all 0.25s;
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
  transition: background-color 0.2s;
}
.user-item:hover {
  background: #fafafa;
}
.user-item:last-child {
  border-bottom: none;
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