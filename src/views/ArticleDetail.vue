<template>
  <div ref="containerRef" class="article-detail-container">
    <div class="back-button" @click="goBack"><el-icon><ArrowLeft /></el-icon><span>返回</span></div>
    <div v-if="loading" class="loading-container"><el-skeleton :rows="10" animated /></div>
    <div v-else-if="error" class="error-container">
      <el-empty description="加载失败，请稍后重试" /><el-button type="primary" @click="fetchArticle">重新加载</el-button>
    </div>
    <div v-else-if="article" class="article-content">
      <div class="article-header">
        <div class="author-info">
          <el-avatar :src="article.user_avatar" :size="40" />
          <div class="author-details">
            <div class="author-name">{{ article.user_nick }}</div>
            <div class="publish-time">{{
              formatDateLong(article.publishTime) +
              (isArticleEdited ? ` 发布 | ${formatDateLong(article.updateTime)} 更新` : '')
            }}</div>
          </div>
        </div>
      </div>
      <h1 class="article-title">{{ article.title }}</h1>
      <div class="article-stats">
        <div class="stat-item" @click="toggleLike">
          <span class="heart-icon" :class="{ liked: article.isLiked }">{{ article.isLiked ? '❤️' : '♡' }}</span>
          <span>{{ article.likeCount }} 点赞</span>
        </div>
      </div>
      <div class="article-body">{{ article.content }}</div>
      <div v-if="article.images.length">
        <viewer :images="article.images" :options="viewerOptions" class="article-images">
          <div v-for="(image, index) in article.images" :key="index" class="image-item">
            <img :src="image" class="image-thumb" alt="文章图片" />
          </div>
        </viewer>
      </div>
      <el-divider />
      <div class="comments-section">
        <h3 class="comments-title">评论区 ({{ comments.length }})</h3>
        <div v-if="comments.length" class="comments-list">
          <div v-for="comment in comments" :key="comment.id" class="comment-item">
            <el-avatar :src="comment.avatar" :size="32" />
            <div class="comment-content">
              <div class="comment-header">
                <span class="comment-author">{{ comment.nickname }}</span>
                <span class="comment-time">{{ formatDateShort(comment.createTime) }}</span>
              </div>
              <div class="comment-text">{{ comment.content }}</div>
              <div class="comment-actions">
                <span class="comment-like" @click="likeComment(comment.id)">
                  <span class="heart-icon">♡</span>{{ comment.likeCount }}
                </span>
                <span class="comment-reply" @click="replyToComment(comment)">回复</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="no-comments"><el-empty description="暂无评论，快来抢沙发吧~" :image-size="80" /></div>
      </div>
    </div>
    <div ref="commentBarRef" class="comment-bar">
      <div class="comment-bar-inner">
        <el-input
          v-model="newComment"
          type="textarea"
          placeholder="说点什么……"
          resize="none"
          class="comment-input"
          @keydown.ctrl.enter="submitComment"
          :autosize="{ minRows: 1, maxRows: 10 }"
        />
        <el-button
          type="primary"
          :loading="submitting"
          @click="submitComment"
          class="comment-submit-btn"
        >
          发布
        </el-button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ElMessage } from 'element-plus'
import { formatDateShort, formatDateLong } from '@/utils/dateFormatter'
import type { ArticleDetail } from '@/types/api'
const route = useRoute()
const router = useRouter()
const loading = ref(true)
const error = ref(false)
const article = ref<ArticleDetail | null>(null)
const comments = ref<CommentItem[]>([])
const newComment = ref('')
const submitting = ref(false)
const containerRef = ref<HTMLElement | null>(null)
const commentBarRef = ref<HTMLElement | null>(null)
const isArticleEdited = computed<boolean>(() => article.value.updateTime > article.value.publishTime)
const viewerOptions = {
  toolbar: {
    prev: 1,
    next: 1,
    zoomIn: 1,
    zoomOut: 1,
    reset: 1,
    rotateLeft: 1,
    rotateRight: 1,
    play: false,
  },
  title: false,
  minZoomRatio: 0.1,
  maxZoomRatio: 10
}
let resizeObserver: ResizeObserver | null = null
interface CommentItem {
  id: number
  avatar: string
  nickname: string
  content: string
  createTime: number
  likeCount: number
  parentId?: number
}
const goBack = () => {
  router.back()
}
const fetchArticle = async () => {
  const id = route.params.id
  if (!id) {
    error.value = true
    loading.value = false
    return
  }
  loading.value = true
  error.value = false
  try {
    const articleResponse = await axios.get<{ code: number; data: ArticleDetail }>(
      `/api/articles/${id}`
    )
    if (articleResponse.data.code === 1) {
      article.value = articleResponse.data.data
    } else {
      throw new Error('获取文章详情失败')
    }
    // 获取评论列表
    // const commentsResponse = await axios.get<{ code: number; data: CommentItem[] }>(
    //   `/api/article/${id}/comments`
    // )
    // if (commentsResponse.data.code === 200) {
    //   comments.value = commentsResponse.data.data
    // }
  } catch (err) {
    console.error('获取文章详情失败:', err)
    error.value = true
    ElMessage.error('加载文章失败，请稍后重试')
  } finally {
    loading.value = false
  }
}
const toggleLike = async () => {
  if (!article.value) return
  try {
    const response = await axios.post(`/api/article/${article.value.id}/like`)
    if (response.data.code === 200) {
      article.value.isLiked = !article.value.isLiked
      article.value.likeCount += article.value.isLiked ? 1 : -1
      ElMessage.success(article.value.isLiked ? '点赞成功' : '取消点赞')
    }
  } catch (err) {
    console.error('点赞失败:', err)
    ElMessage.error('操作失败，请稍后重试')
  }
}
const submitComment = async () => {
  if (!newComment.value.trim()) {
    ElMessage.warning('请输入评论内容')
    return
  }
  if (!article.value) return
  submitting.value = true
  try {
    const response = await axios.post(`/api/article/${article.value.id}/comments`, {
      content: newComment.value.trim()
    })
    if (response.data.code === 200) {
      const newCommentData: CommentItem = {
        id: response.data.data.id,
        avatar: 'placeholder',
        nickname: '当前用户',
        content: newComment.value.trim(),
        createTime: Date.now(),
        likeCount: 0
      }
      comments.value.unshift(newCommentData)
      newComment.value = ''
      ElMessage.success('评论发布成功')
    }
  } catch (err) {
    console.error('发布评论失败:', err)
    ElMessage.error('发布失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}
const likeComment = async (commentId: number) => {
  try {
    const response = await axios.post(`/api/comment/${commentId}/like`)
    if (response.data.code === 200) {
      const comment = comments.value.find(c => c.id === commentId)
      if (comment) {
        comment.likeCount = (comment.likeCount || 0) + 1
        ElMessage.success('点赞成功')
      }
    }
  } catch (err) {
    console.error('点赞评论失败:', err)
    ElMessage.error('操作失败')
  }
}
const replyToComment = (comment: CommentItem) => {
  newComment.value = `@${comment.nickname} `
  const inputArea = document.querySelector('.comment-input-area')
  inputArea?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}
const updateContainerPadding = () => {
  if (containerRef.value && commentBarRef.value) {
    const barHeight = commentBarRef.value.offsetHeight
    containerRef.value.style.paddingBottom = `${barHeight + 20}px`
  }
}
onMounted(() => {
  fetchArticle()
  nextTick(() => {
    if (commentBarRef.value) {
      updateContainerPadding()
      resizeObserver = new ResizeObserver(() => {
        updateContainerPadding()
      })
      resizeObserver.observe(commentBarRef.value)
    }
  })
})
onUnmounted(() => {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
})
</script>
<style scoped>
.article-body {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
  margin-bottom: 32px;
  white-space: pre-wrap;
  word-wrap: break-word;
}
.article-content {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}
.article-detail-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  position: relative;
}
.article-header {
  margin-bottom: 24px;
}
.article-images {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 32px;
}
.article-stats {
  display: flex;
  gap: 24px;
  padding: 16px 0;
  border-top: 1px solid #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 32px;
}
.article-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1.3;
  margin: 0 0 20px 0;
}
.author-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.author-name {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}
.author-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.back-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  margin-bottom: 24px;
  background: white;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
  color: #666;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}
.back-button:hover {
  transform: translateX(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  color: #333;
}
.comment-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
  gap: 16px;
}
.comment-author {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}
.comment-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;
  
  padding: 12px 20px;
  display: flex;
  justify-content: center;
}
.comment-bar-inner {
  max-width: 1000px;
  width: 100%;
  display: flex;
  gap: 12px;
  align-items: flex-end;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(12px);
  padding: 8px 12px 8px 16px;
  border-top: 1px outset #f0f2f5;
  border-radius: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: box-shadow 0.2s;
}
.comment-bar-inner:focus-within {
  box-shadow: 0 4px 16px rgba(64, 158, 255, 0.15);
}
.comment-content {
  flex: 1;
}
.comment-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.comment-input {
  flex: 1;
  min-height: 40px;
}
.comment-input :deep(.el-textarea__inner) {
  background: transparent;
  padding: 0;
  font-size: 14px;
  box-shadow: none;
  border: none;
  border-radius: 0;
  max-height: 40vh;
  overflow-y: auto;
}
.comment-input :deep(.el-textarea__inner:focus) {
  border: none;
  box-shadow: none;
}
.comment-item {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 12px;
  transition: all 0.2s;
}
.comment-item:hover {
  background: #f0f2f5;
}
.comment-like, .comment-reply {
  font-size: 12px;
  color: #999;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: color 0.2s;
}
.comment-like:hover, .comment-reply:hover {
  color: #409eff;
}
.comment-like .heart-icon {
  font-size: 14px;
  margin-right: 2px;
}
.comment-submit-btn {
  height: 40px;
  padding: 0 24px;
  border-radius: 20px;
  font-weight: 500;
  flex-shrink: 0;
  background: linear-gradient(135deg, #409eff, #66b1ff);
  border: none;
  color: white;
  transition: transform .3s;
}
.comment-submit-btn:hover {
  opacity: 0.9;
  transform: scale(1.02);
}
.comment-submit-btn:active {
  transform: scale(0.96);
}
.comment-time {
  font-size: 12px;
  color: #999;
}
.comment-text {
  font-size: 14px;
  line-height: 1.5;
  color: #555;
  margin-bottom: 8px;
  word-wrap: break-word;
}
.comments-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.comments-section {
  margin-top: 32px;
}
.comments-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 24px;
}
.error-container, .loading-container {
  background: white;
  border-radius: 16px;
  padding: 40px;
  text-align: center;
}
.heart-icon {
  font-size: 16px;
  cursor: pointer;
  transition: color 0.2s;
  display: inline-block;
  line-height: 1;
}
.heart-icon.liked {
  color: #ff6b6b;
}
.image-item {
  position: relative;
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  background: #f5f5f5;
  aspect-ratio: 1;
}
.image-item .el-image {
  width: 100%;
  height: 100%;
  transition: transform 0.3s ease;
}
.image-item:hover .el-image {
  transform: scale(1.05);
}
.image-item:hover .image-thumb {
  transform: scale(1.05);
}
.image-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
  cursor: pointer;
  transition: transform 0.3s ease;
}
.no-comments {
  padding: 40px 20px;
  text-align: center;
  background: #f8f9fa;
  border-radius: 12px;
}
.publish-time {
  font-size: 12px;
  color: #999;
}
.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: #666;
  cursor: pointer;
  transition: color 0.2s;
}
.stat-item .liked {
  color: #ff6b6b;
}
@media (max-width: 768px) {
  .article-body {
    font-size: 14px;
    line-height: 1.6;
  }
  .article-content {
    padding: 20px;
  }
  .article-detail-container {
    padding: 12px;
  }
  .article-images {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
  .article-title {
    font-size: 22px;
  }
}
@media (max-width: 480px) {
  .article-images {
    grid-template-columns: repeat(2, 1fr);
  }
  .article-stats {
    gap: 16px;
  }
}
</style>