<template>
  <div class="article-card" @click="goToArticle">
    <div class="article-header">
      <el-avatar :src="avatar" :size="24" />
      <span class="article-author">{{ nickname }}</span>
      <span class="article-time">{{ formatDateShort(publishTime) }}</span>
    </div>
    <h3 class="article-title">{{ title }}</h3>
    <p class="article-content">{{ content }}</p>
    <div v-if="images.length" class="article-images">
      <div v-for="(img, index) in displayImages" :key="index" class="image-wrapper">
        <img :src="img" :alt="'图片' + (index + 1)" class="article-image" />
        <div v-if="index === 2 && images.length > 3" class="more-overlay">+{{ images.length - 3 }}</div>
      </div>
    </div>
    <div class="article-stats">
      <span class="stat-item"><el-icon><ChatDotRound /></el-icon>{{ commentCount }}</span>
      <span class="stat-item"><span class="heart-icon">♡</span>{{ likeCount }}</span>
      <!-- 实心爱心：❤️，以后有用 -->
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { formatDateShort } from '@/utils/dateFormatter'
const router = useRouter()
const props = defineProps<{
  id: number
  avatar: string
  nickname: string
  title: string
  content: string
  images: string[]
  publishTime: number
  commentCount: number
  likeCount: number
}>()
const displayImages = computed(() => props.images.slice(0, 3))
const goToArticle = () => {
  router.push(`/article/${props.id}`)
}
</script>
<style scoped>
.article-author {
  font-weight: 600;
  font-size: 14px;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100px;
}
.article-card {
  background: white;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
  break-inside: avoid;
}
.article-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.article-content {
  font-size: 14px;
  color: #666;
  line-height: 1.5;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  white-space: pre-wrap;
}
.article-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}
.article-image {
  width: 100%;
  height: 100%;
  border-radius: 6px;
  object-fit: cover;
}
.article-images {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 5px;
  margin-bottom: 12px;
}
.article-stats {
  display: flex;
  gap: 16px;
}
.article-time {
  font-size: 12px;
  color: #999;
  margin-left: auto;
  flex-shrink: 0;
}
.article-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}
.heart-icon {
  font-size: 14px;
  line-height: 1;
  margin-right: 2px;
}
.image-wrapper {
  position: relative;
  aspect-ratio: 1 / 1;
}
.more-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  font-size: 18px;
  font-weight: bold;
}
.stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #999;
}
.stat-item .el-icon {
  font-size: 14px;
}
</style>