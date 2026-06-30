<template>
  <div class="write-article-container">
    <div class="editor-navbar">
      <div class="nav-left">
        <button class="nav-btn" @click="goBack" title="返回"><el-icon><ArrowLeft /></el-icon></button>
      </div>
      <div class="nav-center"><h1 class="page-title">{{ articleId ? '编辑文章' : '写文章' }}</h1></div>
      <div class="nav-right">
        <button @click="toggleSettings" :class="['nav-btn', { active: showSettings }]" title="文章设置">
          <el-icon><Setting /></el-icon>
        </button>
      </div>
    </div>
    <div class="editor-main">
      <div class="editor-section">
        <div class="title-section">
          <input v-model="article.title" type="text" class="title-input" placeholder="请输入文章标题..." maxlength="100">
          <span class="char-count">{{ article.title.length }}/100</span>
        </div>
        <div class="content-editor">
          <textarea v-model="article.content" class="editor-textarea" placeholder="开始写作吧..."></textarea>
          <div class="image-manager-wrapper">
            <div class="image-manager-header" @click="toggleImagePanel">
              <div class="header-left">
                <el-icon><Picture /></el-icon>
                <span class="image-manager-title">文章图片</span>
                <span class="image-count-badge" v-if="imageCount > 0">{{ imageCount }}</span>
              </div>
              <div class="header-right">
                <span class="image-manager-tip">支持多种图片格式，单张不超过10MB</span>
                <el-icon class="collapse-icon"><ArrowDown v-if="!imagePanelCollapsed" /><ArrowRight v-else /></el-icon>
              </div>
            </div>
            <div class="image-manager-content" v-show="!imagePanelCollapsed">
              <div class="image-list" v-if="article.images && article.images.length > 0">
                <div v-for="(image, index) in displayImages" :key="index" class="image-item">
                  <img :src="image" class="image-preview" alt="预览" />
                  <button class="image-remove" @click="removeImage(index)" title="删除图片">
                    <el-icon><Close /></el-icon>
                  </button>
                </div>
              </div>
              <div v-else class="image-empty"><el-icon><Picture /></el-icon><span>暂无图片，点击下方按钮上传</span></div>
              <div class="upload-area">
                <button class="upload-btn" @click="triggerFileSelect" :disabled="isUploading">
                  <el-icon><Plus /></el-icon>{{ isUploading ? '上传中...' : '上传图片' }}
                </button>
                <input
                  ref="fileInputRef"
                  type="file"
                  accept="image/*"
                  multiple
                  style="display:none"
                  @change="handleFileChange"
                />
              </div>
            </div>
          </div>
          <div class="editor-footer">
            <div class="info-stats">
              <span class="word-count">字数: {{ wordCount }}</span>
              <span class="image-count"><el-icon><Picture /></el-icon>{{ imageCount }}张图片</span>
            </div>
            <button class="publish-btn" @click="handlePublish" :disabled="isPublishing">
              <el-icon><Promotion /></el-icon>
              <span>{{ isPublishing ? '发布中...' : articleId ? '更新文章' : '发布文章' }}</span>
            </button>
          </div>
        </div>
      </div>
      <div :class="['settings-panel', { 'visible': showSettings }]">
        <div class="settings-header">
          <h3 class="settings-title">文章设置</h3>
          <button class="close-settings" @click="showSettings = false"><el-icon><Close /></el-icon></button>
        </div>
        <div class="settings-content">
          <div class="setting-item">
            <label>标签</label>
            <div class="tags-input">
              <div class="selected-tags" v-if="article.tags && article.tags.length > 0">
                <span v-for="(tag, index) in article.tags" :key="index" class="tag">
                  {{ tag }}<el-icon class="tag-close" @click="removeTag(index)"><Close /></el-icon>
                </span>
              </div>
              <input v-model="newTag" type="text" placeholder="输入标签，按回车添加" @keyup.enter="addTag" />
            </div>
          </div>
          <div class="setting-item">
            <label>可见性</label>
            <div class="visibility-options">
              <label class="radio-option">
                <input type="radio" v-model="article.visibility" value="public" /><span>公开</span>
              </label>
              <label class="radio-option">
                <input type="radio" v-model="article.visibility" value="private" /><span>私密</span>
              </label>
              <label class="radio-option">
                <input type="radio" v-model="article.visibility" value="friends" /><span>互相关注</span>
              </label>
            </div>
          </div>
          <div class="setting-item" v-if="articleId && article.published_at">
            <label>发布时间</label><div class="publish-time">{{ formatDateLong(article.published_at) }}</div>
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
import { ElMessage } from 'element-plus'
import type { ImageUploadResponse } from '@/types/api/upload'
import type { ArticleDetailResponse } from '@/types/api/article'
interface ArticleData {
  id: number | null
  title: string
  content: string
  tags: string[]
  images: string[]
  visibility: 'public' | 'mutuals' | 'private'
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
const isUploading = ref<boolean>(false)
const newTag = ref<string>('')
const fileInputRef = ref<HTMLInputElement | null>(null)
const imagePanelCollapsed = ref<boolean>(false)
const originalArticle = ref<ArticleData | null>(null)
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
const imageCount = computed<number>(() => {
  return article.value.images?.length || 0
})
const hasChanges = computed<boolean>(() => {
  if (!originalArticle.value) return false
  return (
    article.value.title !== originalArticle.value.title ||
    article.value.content !== originalArticle.value.content ||
    JSON.stringify(article.value.tags) !== JSON.stringify(originalArticle.value.tags) ||
    JSON.stringify(article.value.images) !== JSON.stringify(originalArticle.value.images) ||
    article.value.visibility !== originalArticle.value.visibility
  )
})
const displayImages = computed<string[]>(() => {
  return article.value.images.map(url => `/uploads/images/${url}.png`)
})
const triggerFileSelect = () => {
  if (isUploading.value) return
  fileInputRef.value?.click()
}
const handleFileChange = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = input.files
  if (!files || files.length === 0) return
  const currentCount = article.value.images.length
  const remainingSlots = 100 - currentCount
  if (files.length > remainingSlots) {
    showNotification(`最多只能上传100张图片，当前已上传${currentCount}张`, 'warning')
    input.value = ''
    return
  }
  const validFiles: File[] = []
  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    if (!file.type.startsWith('image/')) {
      showNotification(`文件“${file.name}”不是图片格式`, 'error')
      continue
    }
    if (file.size > 10 * 1024 * 1024) {
      showNotification(`图片“${file.name}”超过10MB限制`, 'error')
      continue
    }
    validFiles.push(file)
  }
  if (validFiles.length === 0) {
    input.value = ''
    return
  }
  await uploadImages(validFiles)
  input.value = ''
}
const uploadImages = async (files: File[]) => {
  isUploading.value = true
  try {
    const formData = new FormData()
    files.forEach(file => {
      formData.append('image', file)
    })
    const response = await axios.post<ImageUploadResponse>('/api/upload/images', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 300000
    })
    if (response.data.code === 1) {
      const urls = response.data.imageNames
      if (urls.length) {
        article.value.images.push(...urls)
        showNotification(`成功上传${urls.length}张图片`, 'success')
      } else {
        throw new Error('未返回图片地址')
      }
    }
  } catch (error: any) {
    if (error.response) {
      const data = error.response.data
      const message = data.msg
      ElMessage.error(message)
      showNotification(message, 'error')
    } 
    else if (error.request) {
      showNotification('网络异常，请检查网络连接后重试', 'error')
    } 
    else {
      showNotification('批量上传失败，请重试', 'error')
    }
  } finally {
    isUploading.value = false
  }
}
const removeImage = (index: number) => {
  article.value.images.splice(index, 1)
}
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
    const endpoint = `/api/articles${articleId.value ? `/${articleId.value}` : ''}`
    const method = articleId.value ? 'put' : 'post'
    const response = await axios.request({
      method,
      url: endpoint,
      data: dataToPublish,
      timeout: 5000
    })
    if (response.data.code === 1) {
      showNotification('文章发布成功！', 'success')
      removeBeforeUnload()
      router.back()
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
      removeBeforeUnload()
      router.back()
    }
  } else {
    removeBeforeUnload()
    router.back()
  }
}
const showNotification = (message: string, type: 'primary' | 'success' | 'error' | 'warning' | 'info' = 'info') => {
  ElMessage[type](message)
}
const loadArticle = async () => {
  if (!articleId.value) return
  try {
    const response = await axios.get<ArticleDetailResponse>(`/api/articles/${articleId.value}`)
    if (response.data.code === 1) {
      const data = response.data.data
      article.value = {
        id: data.id,
        title: data.title,
        content: data.content,
        images: data.images.map(image => image.match(/^\/uploads\/images\/(.+)\.png$/)[1]) || [],
        tags: data.tags || [],
        visibility: data.visibility || 'public',
        published_at: data.publishTime || null
      }
      originalArticle.value = JSON.parse(JSON.stringify(article.value))
    }
  } catch {
    showNotification('加载文章失败', 'error')
  }
}
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
  if (hasChanges.value) {
    event.preventDefault()
    event.returnValue = ''
  }
}
const toggleImagePanel = () => {
  imagePanelCollapsed.value = !imagePanelCollapsed.value
}
const removeBeforeUnload = () => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
}
onMounted(() => {
  loadArticle()
  window.addEventListener('beforeunload', handleBeforeUnload)
})
onBeforeUnmount(() => {
  removeBeforeUnload()
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
.collapse-icon {
  transition: transform 0.2s;
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
.header-left,
.header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}
.image-count,
.word-count {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  color: #666;
}
.image-count-badge {
  background: #2575fc;
  color: white;
  font-size: 12px;
  border-radius: 12px;
  padding: 0 6px;
  min-width: 20px;
  height: 20px;
  line-height: 20px;
  text-align: center;
}
.image-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 30px;
  color: #999;
  font-size: 14px;
  background: #fafafa;
  border-radius: 8px;
  margin-bottom: 12px;
}
.image-item {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #eaeaea;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s;
}
.image-item:hover {
  transform: scale(1.02);
}
.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}
.image-manager-content {
  padding: 0 24px 16px 24px;
}
.image-manager-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 24px;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}
.image-manager-header:hover {
  background: #f5f5f5;
}
.image-manager-tip {
  font-size: 12px;
  color: #999;
}
.image-manager-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
  gap: 6px;
}
.image-manager-wrapper {
  border-top: 1px solid #eaeaea;
  background: #fefefe;
}
.image-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.image-remove {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
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
  background: rgba(0, 0, 0, 0.9);
}
.image-remove .el-icon {
  font-size: 14px;
}
.info-stats {
  display: flex;
  gap: 20px;
  align-items: center;
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
.nav-left,
.nav-right {
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
.upload-area {
  display: flex;
  justify-content: flex-start;
}
.upload-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 20px;
  background: #ffffff;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  color: #606266;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.upload-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.upload-btn:hover:not(:disabled) {
  border-color: #2575fc;
  color: #2575fc;
  background-color: #f0f7ff;
}
.visibility-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.write-article-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  overflow: hidden;
}
@media (width <= 1024px) {
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
@media (max-width <= 768px) {
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