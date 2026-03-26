<template>
  <div class="write-article-container">
    <div class="editor-navbar">
      <div class="nav-left">
        <button class="nav-btn" @click="goBack" title="返回">
          <el-icon><ArrowLeft /></el-icon>
        </button>
      </div>
      <div class="nav-center">
        <h1 class="page-title">{{ articleId ? '编辑文章' : '写文章' }}</h1>
      </div>
      <div class="nav-right">
        <button class="nav-btn" @click="toggleSettings" :class="{ active: showSettings }" title="文章设置">
          <el-icon><Setting /></el-icon>
        </button>
      </div>
    </div>
    <div class="editor-main">
      <div class="editor-section" :class="{ 'settings-open': showSettings }">
        <div class="title-section">
          <input v-model="article.title" type="text" class="title-input" placeholder="请输入文章标题..." maxlength="100">
          <span class="char-count">{{ article.title.length }}/100</span>
        </div>
        <div class="content-editor">
          <textarea v-model="article.content" class="editor-textarea" placeholder="开始写作吧..."></textarea>
          <div class="editor-footer">
            <div class="word-count">
              <span>字数: {{ wordCount }}</span>
            </div>
            <button class="publish-btn" @click="handlePublish" :disabled="isPublishing">
              <el-icon><Promotion /></el-icon>
              {{ isPublishing ? '发布中...' : articleId ? '更新文章' : '发布文章' }}
            </button>
          </div>
        </div>
      </div>
      <div class="settings-panel" :class="{ 'visible': showSettings }">
        <div class="settings-header">
          <h3 class="settings-title">文章设置</h3>
          <button class="close-settings" @click="showSettings = false">
            <el-icon><Close /></el-icon>
          </button>
        </div>
        <div class="settings-content">
          <div class="setting-item">
            <label>标签</label>
            <div class="tags-input">
              <div class="selected-tags" v-if="article.tags && article.tags.length > 0">
                <span v-for="(tag, index) in article.tags" :key="index" class="tag">
                  {{ tag }}
                  <el-icon class="tag-close" @click="removeTag(index)"><Close /></el-icon>
                </span>
              </div>
              <input v-model="newTag" type="text" placeholder="输入标签，按回车添加" @keyup.enter="addTag" />
            </div>
          </div>
          <div class="setting-item">
            <label>图片</label>
            <button class="upload-btn">
              <el-icon><Plus /></el-icon>
              上传图片
            </button>
            <div class="image-list" v-if="article.images && article.images.length > 0">
              <div v-for="(image, index) in article.images" :key="index" class="image-item">
                <img :src="image" class="image-preview" alt="预览" />
                <button class="image-remove">
                  <el-icon><Close /></el-icon>
                </button>
              </div>
            </div>
          </div>
          <div class="setting-item">
            <label>可见性</label>
            <div class="visibility-options">
              <label class="radio-option">
                <input type="radio" v-model="article.visibility" value="public" />
                <span>公开</span>
              </label>
              <label class="radio-option">
                <input type="radio" v-model="article.visibility" value="private" />
                <span>私密</span>
              </label>
              <label class="radio-option">
                <input type="radio" v-model="article.visibility" value="friends" />
                <span>互相关注</span>
              </label>
            </div>
          </div>
          <div class="setting-item" v-if="articleId && article.published_at">
            <label>发布时间</label>
            <div class="publish-time">{{ formatDateLong(article.published_at) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { formatDateLong } from '@/utils/dateFormatter'
import { ArrowLeft, Close, Promotion, Setting } from '@element-plus/icons-vue'
interface ArticleData {
  id: number | null
  title: string
  content: string
  tags: string[],
  images: string[],
  visibility: 'public' | 'private' | 'friends',
  published_at: number | null
}
const router = useRouter()
const route = useRoute()
const article = ref<ArticleData>({
  id: null,
  title: '',
  content: '',
  tags: [],
  images: [],
  visibility: 'public',
  published_at: null
})
const showSettings = ref<boolean>(false)
const isPublishing = ref<boolean>(false)
const newTag = ref<string>('')
const articleId = computed<number | null>(() => {
  const id = route.params.id
  if (!id) return null
  if (typeof id === 'string') {
    const num = parseInt(id)
    return isNaN(num) ? null : num
  } else {
    const num = parseInt(id[0])
    return isNaN(num) ? null : num
  }
})
const wordCount = computed<number>(() => {
  return article.value.content.trim().length
})
const hasChanges = computed<boolean>(() => {
  return !!(article.value.title.trim() || article.value.content.trim())
})
const handlePublish = async () => {
  if (!article.value.title.trim()) {
    showNotification('请输入文章标题', 'warning')
    return
  }
  if (!article.value.content.trim()) {
    showNotification('请输入文章内容', 'warning')
    return
  }
  isPublishing.value = true
  try {
    const dataToPublish = {
      title: article.value.title,
      content: article.value.content,
      tags: article.value.tags,
      images: article.value.images,
      visibility: article.value.visibility
    }
    const endpoint = articleId.value ? `/api/articles/${articleId.value}` : '/api/articles'
    const method = articleId.value ? 'put' : 'post'
    const response = await axios.request({
      method,
      url: endpoint,
      data: dataToPublish,
      timeout: 5000
    })
    if (response.data.code === 1) {
      showNotification('文章发布成功！', 'success')
      router.push(`/article/${response.data.data.id}`)
    }
  } catch (error) {
    console.error('发布失败:', error)
    showNotification('发布失败，请重试', 'error')
  } finally {
    isPublishing.value = false
  }
}
const addTag = () => {
  const tag = newTag.value.trim()
  if (tag && !article.value.tags.includes(tag)) {
    article.value.tags.push(tag)
    newTag.value = ''
  }
}
const removeTag = (index: number) => {
  article.value.tags.splice(index, 1)
}
const toggleSettings = () => {
  showSettings.value = !showSettings.value
}
const goBack = () => {
  if (hasChanges.value) {
    if (confirm('您有未保存的更改，确定要离开吗？')) {
      router.back()
    }
  } else {
    router.back()
  }
}
const showNotification = (message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info') => {
  alert(`${type}: ${message}`)
}
const loadArticle = async () => {
  if (!articleId.value) return
  try {
    const response = await axios.get(`/api/articles/${articleId.value}`)
    if (response.data.code === 1) {
      const data = response.data.data
      article.value = {
        id: data.id,
        title: data.title,
        content: data.content,
        images: data.images || [],
        tags: data.tags || [],
        visibility: data.visibility || 'public',
        published_at: data.published_at || null
      }
    }
  } catch (error) {
    console.error('加载文章失败:', error)
    showNotification('加载文章失败', 'error')
  }
}
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
  if (hasChanges.value) {
    event.preventDefault()
    event.returnValue = ''
  }
}
onMounted(() => {
  loadArticle()
  window.addEventListener('beforeunload', handleBeforeUnload)
})
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})
</script>
<style scoped>
.char-count {
  position: absolute;
  right: 24px;
  bottom: 5px;
  font-size: 12px;
  color: #999;
}
.close-settings {
  background: none;
  border: none;
  cursor: pointer;
  color: #999;
  font-size: 16px;
  padding: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.close-settings:hover {
  color: #333;
}
.close-settings .el-icon,
.nav-btn .el-icon,
.publish-btn .el-icon {
  width: 1em;
  height: 1em;
  font-size: 16px;
}
.content-editor {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.editor-footer {
  padding: 16px 24px;
  border-top: 1px solid #eaeaea;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fafafa;
}
.editor-main {
  flex: 1;
  display: flex;
  position: relative;
  overflow: hidden;
}
.editor-navbar {
  flex-shrink: 0;
  background: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 12px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eaeaea;
  z-index: 10;
}
.editor-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: white;
  border-radius: 12px;
  margin: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: all 0.3s ease;
}
.editor-textarea {
  flex: 1;
  padding: 24px;
  border: none;
  outline: none;
  resize: none;
  font-size: 16px;
  line-height: 1.6;
  color: #333;
  font-family: 'SF Mono', Monaco, Consolas, monospace;
  background: white;
  min-height: 200px;
}
.editor-textarea::placeholder {
  color: #999;
}
.image-item {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #eaeaea;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 12px;
}
.image-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.image-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  border: none;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 0;
}
.image-remove:hover {
  background: rgba(0, 0, 0, 0.8);
}
.image-remove .el-icon {
  font-size: 14px;
}
.nav-btn {
  background: none;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #666;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 5px;
}
.nav-btn:hover {
  background: #f5f5f5;
  border-color: #2575fc;
  color: #2575fc;
}
.nav-btn.active {
  background: #2575fc;
  color: white;
  border-color: #2575fc;
}
.nav-center {
  flex: 1;
  text-align: center;
}
.nav-left, .nav-right {
  display: flex;
  align-items: center;
  gap: 15px;
}
.page-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin: 0;
}
.publish-btn {
  background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 10px 20px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}
.publish-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.publish-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 117, 252, 0.3);
}
.publish-time {
  font-size: 14px;
  color: #666;
  padding: 8px 0;
}
.radio-option {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #333;
}
.radio-option input {
  margin: 0;
  cursor: pointer;
}
.radio-option span {
  margin-left: 5px;
}
.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 10px;
}
.setting-item {
  margin-bottom: 24px;
}
.setting-item label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}
.settings-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}
.settings-header {
  padding: 16px 20px;
  border-bottom: 1px solid #eaeaea;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}
.settings-panel {
  position: absolute;
  top: 20px;
  right: 20px;
  bottom: 20px;
  width: 320px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  transform: translateX(100%);
  opacity: 0;
  transition: all 0.3s ease;
  z-index: 20;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.settings-panel.visible {
  transform: translateX(0);
  opacity: 1;
}
.settings-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}
.tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.tag .el-icon {
  width: 12px;
  height: 12px;
}
.tag-close {
  cursor: pointer;
  font-size: 12px;
  opacity: 0.7;
  transition: opacity 0.2s;
}
.tag-close:hover {
  opacity: 1;
}
.tags-input {
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 10px;
  background: #fff;
}
.tags-input input {
  width: 100%;
  border: none;
  outline: none;
  font-size: 14px;
  padding: 5px 0;
}
.title-input {
  width: 100%;
  border: none;
  font-size: 28px;
  font-weight: 700;
  color: #333;
  outline: none;
  padding: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.title-input::placeholder {
  color: #999;
  font-weight: 400;
}
.title-section {
  position: relative;
  padding: 20px 24px;
  border-bottom: 1px solid #eaeaea;
}
.upload-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;
  background: #ffffff;
  border: 1px dashed #dcdfe6;
  border-radius: 6px;
  color: #606266;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.upload-btn:hover {
  border-color: #2575fc;
  color: #2575fc;
  background-color: #f0f7ff;
}
.upload-btn .el-icon {
  font-size: 16px;
}
.visibility-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.word-count {
  font-size: 14px;
  color: #666;
}
.write-article-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  overflow: hidden;
}
@media (max-width: 1024px) {
  .editor-main {
    padding: 10px;
  }
  .editor-section {
    margin: 10px;
  }
  .settings-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 400px;
    border-radius: 0;
    box-shadow: -5px 0 20px rgba(0, 0, 0, 0.2);
  }
}
@media (max-width: 768px) {
  .editor-navbar {
    padding: 10px 15px;
  }
  .editor-textarea {
    padding: 15px;
    font-size: 14px;
  }
  .nav-btn {
    padding: 6px 10px;
    font-size: 12px;
  }
  .page-title {
    font-size: 16px;
  }
  .settings-panel {
    max-width: 100%;
  }
  .title-input {
    font-size: 22px;
  }
}
@media (max-width: 480px) {
  .editor-navbar {
    flex-wrap: wrap;
    gap: 10px;
  }
  .nav-center {
    order: 3;
    flex: 100%;
    margin-top: 5px;
  }
  .publish-btn {
    padding: 8px 12px;
  }
  .publish-btn span {
    display: none;
  }
}
</style>