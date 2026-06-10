<template>
  <div class="article-detail-container">
    <!-- 返回按钮 -->
    <div class="back-button" @click="goBack">
      <el-icon><ArrowLeft /></el-icon>
      <span>返回</span>
    </div>

    <!-- 加载状态 -->
    <div v-if="loading" class="loading-container">
      <el-skeleton :rows="10" animated />
    </div>

    <!-- 错误状态 -->
    <div v-else-if="error" class="error-container">
      <el-empty description="加载失败，请稍后重试" />
      <el-button type="primary" @click="fetchArticle">重新加载</el-button>
    </div>

    <!-- 文章详情内容 -->
    <div v-else-if="article" class="article-content">
      <!-- 文章头部 -->
      <div class="article-header">
        <div class="author-info">
          <el-avatar :src="article.user_avatar" :size="40" />
          <div class="author-details">
            <div class="author-name">{{ article.user_nick }}</div>
            <div class="publish-time">{{ formatDateShort(article.publishTime) }}</div>
          </div>
        </div>
      </div>

      <!-- 文章标题 -->
      <h1 class="article-title">{{ article.title }}</h1>

      <!-- 文章统计信息 -->
      <div class="article-stats">
        <div class="stat-item" @click="toggleLike">
          <span class="heart-icon" :class="{ liked: article.isLiked }">
            {{ article.isLiked ? '❤️' : '♡' }}
          </span>
          <span>{{ article.likeCount }} 点赞</span>
        </div>
        <div class="stat-item">
          <el-icon><ChatDotRound /></el-icon>
          <span>{{ article.commentCount }} 评论</span>
        </div>
      </div>

      <!-- 文章内容 -->
      <div class="article-body" v-html="formattedContent"></div>

      <!-- 图片画廊 -->
      <div v-if="article.images && article.images.length" class="article-images">
        <div
          v-for="(image, index) in article.images"
          :key="index"
          class="image-item"
          @click="previewImage(index)"
        >
          <el-image
            :src="image"
            :preview-src-list="article.images"
            :initial-index="index"
            fit="cover"
            lazy
          />
        </div>
      </div>

      <!-- 分割线 -->
      <el-divider />

      <!-- 评论区 -->
      <div class="comments-section">
        <h3 class="comments-title">评论区 ({{ article.commentCount }})</h3>

        <!-- 评论输入框 -->
        <div class="comment-input-wrapper">
          <el-avatar :src="currentUserAvatar" :size="32" />
          <div class="comment-input-area">
            <el-input
              v-model="newComment"
              type="textarea"
              :rows="3"
              resize="none"
              placeholder="写下你的评论..."
              maxlength="500"
              show-word-limit
            />
            <div class="comment-actions">
              <el-button type="primary" @click="submitComment" :loading="submitting">
                发布评论
              </el-button>
            </div>
          </div>
        </div>

        <!-- 评论列表 -->
        <div v-if="comments.length" class="comments-list">
          <div
            v-for="comment in comments"
            :key="comment.id"
            class="comment-item"
          >
            <el-avatar :src="comment.avatar" :size="32" />
            <div class="comment-content">
              <div class="comment-header">
                <span class="comment-author">{{ comment.nickname }}</span>
                <span class="comment-time">{{ formatDateShort(comment.createTime) }}</span>
              </div>
              <div class="comment-text">{{ comment.content }}</div>
              <div class="comment-actions">
                <span class="comment-like" @click="likeComment(comment.id)">
                  <span class="heart-icon">♡</span>
                  {{ comment.likeCount || 0 }}
                </span>
                <span class="comment-reply" @click="replyToComment(comment)">
                  回复
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- 空评论状态 -->
        <div v-else class="no-comments">
          <el-empty description="暂无评论，快来抢沙发吧~" :image-size="80" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ElMessage } from 'element-plus'
import { ArrowLeft, ChatDotRound } from '@element-plus/icons-vue'
import { formatDateShort } from '@/utils/dateFormatter'

// 路由实例
const route = useRoute()
const router = useRouter()

// 响应式数据
const loading = ref(true)
const error = ref(false)
const article = ref<ArticleDetail | null>(null)
const comments = ref<CommentItem[]>([])
const newComment = ref('')
const submitting = ref(false)

// 当前用户信息（实际应该从store获取）
const currentUserAvatar = ref('https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png')

// 类型定义
interface ArticleDetail {
  id: number
  user_id: number
  user_avatar: string
  user_nick: string
  title: string
  content: string
  images: string[]
  publishTime: number
  commentCount: number
  likeCount: number
  viewCount: number
  isLiked: boolean
}

interface CommentItem {
  id: number
  avatar: string
  nickname: string
  content: string
  createTime: number
  likeCount: number
  parentId?: number
}

// 格式化文章内容（支持换行和简单HTML转义）
const formattedContent = computed(() => {
  if (!article.value?.content) return ''
  // 将换行符转换为 <br> 标签，同时转义HTML标签防止XSS
  let content = article.value.content
  content = content.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;'
    if (m === '<') return '&lt;'
    if (m === '>') return '&gt;'
    return m
  })
  return content.replace(/\n/g, '<br>')
})

// 返回上一页
const goBack = () => {
  router.back()
}

// 获取文章详情
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
    // 获取文章详情
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

// 点赞/取消点赞文章
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

// 提交评论
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
      // 添加新评论到列表顶部
      const newCommentData: CommentItem = {
        id: response.data.data.id,
        avatar: currentUserAvatar.value,
        nickname: '当前用户', // 实际应该从store获取
        content: newComment.value.trim(),
        createTime: Date.now(),
        likeCount: 0
      }
      comments.value.unshift(newCommentData)
      article.value.commentCount++
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

// 点赞评论
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

// 回复评论（简化实现，实际可展开输入框）
const replyToComment = (comment: CommentItem) => {
  newComment.value = `@${comment.nickname} `
  // 滚动到评论输入框
  const inputArea = document.querySelector('.comment-input-area')
  inputArea?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

// 预览图片（使用el-image自带预览功能）
const previewImage = (index: number) => {
  // el-image的preview功能会自动处理
  const images = document.querySelectorAll('.image-item .el-image')
  if (images[index]) {
    // 触发对应图片的预览
    const imageComponent = (images[index] as any).__vueParentComponent?.ctx
    // 简单实现：通过创建新的事件来触发
  }
}

// 监听路由参数变化
onMounted(() => {
  fetchArticle()
})
</script>

<style scoped>
.article-detail-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  position: relative;
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

.loading-container,
.error-container {
  background: white;
  border-radius: 16px;
  padding: 40px;
  text-align: center;
}

.article-content {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.article-header {
  margin-bottom: 24px;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 12px;
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

.publish-time {
  font-size: 12px;
  color: #999;
}

.article-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1.3;
  margin: 0 0 20px 0;
}

.article-stats {
  display: flex;
  gap: 24px;
  padding: 16px 0;
  border-top: 1px solid #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 32px;
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

.stat-item:hover {
  color: #409eff;
}

.stat-item .liked {
  color: #ff6b6b;
}

.article-body {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
  margin-bottom: 32px;
  white-space: normal;
  word-wrap: break-word;
}

.article-body :deep(p) {
  margin-bottom: 16px;
}

.article-body :deep(img) {
  max-width: 100%;
  border-radius: 8px;
  margin: 16px 0;
}

.article-images {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 32px;
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

.comments-section {
  margin-top: 32px;
}

.comments-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 24px;
}

.comment-input-wrapper {
  display: flex;
  gap: 12px;
  margin-bottom: 32px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 12px;
}

.comment-input-area {
  flex: 1;
}

.comment-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
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

.comment-content {
  flex: 1;
}

.comment-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.comment-author {
  font-size: 14px;
  font-weight: 600;
  color: #333;
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

.comment-actions {
  display: flex;
  gap: 16px;
}

.comment-like,
.comment-reply {
  font-size: 12px;
  color: #999;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: color 0.2s;
}

.comment-like:hover,
.comment-reply:hover {
  color: #409eff;
}

.no-comments {
  padding: 40px 20px;
  text-align: center;
  background: #f8f9fa;
  border-radius: 12px;
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

.comment-like .heart-icon {
  font-size: 14px;
  margin-right: 2px;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .article-detail-container {
    padding: 12px;
  }

  .article-content {
    padding: 20px;
  }

  .article-title {
    font-size: 22px;
  }

  .article-body {
    font-size: 14px;
    line-height: 1.6;
  }

  .article-images {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }

  .comment-input-wrapper {
    padding: 12px;
  }
}

@media (max-width: 480px) {
  .article-stats {
    gap: 16px;
  }

  .article-images {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>