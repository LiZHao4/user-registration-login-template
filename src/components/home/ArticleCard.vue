<template>
  <div class="article-card" @click="goToArticle">
    <div class="article-header">
      <el-avatar :src="avatar" :size="16" />
      <span class="article-author">{{ nickname }}</span>
      <span class="article-time">{{ formatDateShort(publishTime) }}</span>
    </div>
    <h3 class="article-title">{{ title }}</h3>
    <p class="article-content">{{ content }}</p>
    <div v-if="images && images.length" class="article-images">
      <img
        v-for="(img, index) in images.slice(0, 3)"
        :key="index"
        :src="img"
        :alt="'图片' + (index + 1)"
        class="article-image"
      />
    </div>
    <div class="article-stats">
      <span class="stat-item"><i class="fas fa-comment"></i>{{ commentCount }}</span>
      <span class="stat-item"><i class="fas fa-heart"></i>{{ likeCount }}</span>
    </div>
  </div>
</template>
<script>
import { formatDateShort } from '@/utils/dateFormatter';
export default {
  name: 'ArticleCard',
  props: {
    id: {
      type: Number,
      required: true
    },
    avatar: {
      type: String,
      required: true
    },
    nickname: {
      type: String,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    content: {
      type: String,
      required: true
    },
    images: {
      type: Array,
      default: () => []
    },
    publishTime: {
      type: Number,
      required: true
    },
    commentCount: {
      type: Number,
      default: 0
    },
    likeCount: {
      type: Number,
      default: 0
    }
  },
  methods: {
    goToArticle() {
      this.$router.push(`/article/${this.id}`);
    },
    formatDateShort
  }
};
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
  white-space: pre;
}
.article-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}
.article-image {
  width: 100%;
  height: 80px;
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
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.3;
}
.stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #999;
}
</style>