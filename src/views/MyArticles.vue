<template>
  <div class="my-articles-container">
    <div class="articles-card">
      <div class="card-header">
        <div class="header-left">
          <el-button link @click="goBack" class="back-btn"><el-icon><ArrowLeft /></el-icon><span>返回</span></el-button>
        </div>
        <div class="header-title">我的文章</div>
        <div class="header-right"><el-button type="primary" @click="goWrite" :icon="Plus">写文章</el-button></div>
      </div>
      <div class="search-bar">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索文章标题..."
          clearable
          :prefix-icon="Search"
          @keyup.enter="handleSearch"
          @clear="handleSearch"
        />
      </div>
      <div class="articles-list" v-loading="loading">
        <div v-if="articles.length === 0 && !loading" class="empty-state">
          <el-empty description="暂无文章，快去写一篇吧～">
            <el-button type="primary" @click="goWrite">写文章</el-button>
          </el-empty>
        </div>
        <div v-for="article in articles" :key="article.id" class="article-item">
          <div class="article-cover" v-if="article.images && article.images.length > 0">
            <img :src="article.images[0]" :alt="article.title" />
          </div>
          <div class="article-cover no-cover" v-else><el-icon><Document /></el-icon></div>
          <div class="article-info">
            <div class="article-title" @click="goDetail(article.id)">{{ article.title }}</div>
            <div class="article-summary">{{ article.content }}</div>
            <div class="article-meta">
              <span class="meta-item"><el-icon><Clock /></el-icon>{{ formatDateShort(article.updateTime) }}</span>
              <span class="meta-item"><el-icon><View /></el-icon>{{ getVisibilityText(article.visibility) }}</span>
              <span class="meta-item"><el-icon><Star /></el-icon>{{ article.likeCount }}</span>
              <span class="meta-item"><el-icon><ChatDotRound /></el-icon>{{ article.commentCount }}</span>
            </div>
          </div>
          <div class="article-actions">
            <el-button type="primary" link @click="goEdit(article.id)" :icon="Edit">编辑</el-button>
            <el-button type="primary" link @click="goDetail(article.id)" :icon="View">查看</el-button>
            <el-button type="danger" link @click="handleDelete(article.id)" :icon="Delete">删除</el-button>
          </div>
        </div>
      </div>
      <div class="pagination-wrapper" v-if="total > 0">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50]"
          :total="total"
          layout="total,sizes,prev,pager,next,jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search, Edit, View, Delete } from '@element-plus/icons-vue'
import axios from 'axios'
import { formatDateShort } from '@/utils/dateFormatter'
const router = useRouter()
const loading = ref(false)
const searchKeyword = ref('')
const articles = ref([])
const currentPage = ref(1)
const pageSize = ref(10)
const total = ref(0)
const fetchArticles = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/self/articles', {
      params: {
        keyword: searchKeyword.value,
        page: currentPage.value,
        limit: pageSize.value
      }
    })
    articles.value = response.data.data
    total.value = response.data.pagination.total
  } catch (error) {
    ElMessage.error('获取文章列表失败')
  } finally {
    loading.value = false
  }
}
const handleSearch = () => {
  currentPage.value = 1
  fetchArticles()
}
const handleSizeChange = (size: number) => {
  pageSize.value = size
  currentPage.value = 1
  fetchArticles()
}
const handleCurrentChange = (page: number) => {
  currentPage.value = page
  fetchArticles()
}
const getVisibilityText = (visibility: string) => {
  const map = {
    public: '公开',
    mutuals: '互关可见',
    private: '仅自己可见'
  }
  return map[visibility] || visibility
}
const goBack = () => {
  router.back()
}
const goWrite = () => {
  router.push('/write')
}
const goEdit = (id: number) => {
  router.push(`/write/${id}`)
}
const goDetail = (id: number) => {
  router.push(`/article/${id}`)
}
const handleDelete = async (id: number) => {
  try {
    await ElMessageBox.confirm('确定要删除这篇文章吗？删除后无法恢复。', '删除确认', {
      confirmButtonText: '确定删除',
      cancelButtonText: '取消',
      type: 'warning'
    })
    articles.value = articles.value.filter(a => a.id !== id)
    total.value--
    ElMessage.success('删除成功')
  } catch {}
}
onMounted(() => {
  fetchArticles()
})
</script>
<style scoped>
.my-articles-container {
  position: relative;
  width: 100%;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 40px 0;
}
.articles-card {
  position: relative;
  z-index: 2;
  max-width: 900px;
  margin: 0 auto;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.5);
  padding: 24px 28px;
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
.header-left,
.header-right {
  flex: 1;
}
.header-right {
  display: flex;
  justify-content: flex-end;
}
.header-title {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
}
.back-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 15px;
  color: #2c3e50;
  padding: 6px 10px;
  border-radius: 30px;
  transition: background-color 0.2s;
}
.back-btn:hover {
  background: rgba(0, 0, 0, 0.05);
  color: #667eea;
}
.header-right .el-button {
  border-radius: 30px;
  padding: 8px 20px;
  font-weight: 500;
}
.search-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}
.search-bar .el-input {
  flex: 1;
}
:deep(.el-input__wrapper) {
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
:deep(.el-select .el-input__wrapper) {
  border-radius: 12px;
}
.articles-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.article-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: all 0.3s;
  border: 1px solid #f1f5f9;
}
.article-item:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}
.article-cover {
  width: 160px;
  height: 100px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
}
.article-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.article-cover.no-cover {
  background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
}
.article-cover.no-cover .el-icon {
  font-size: 36px;
}
.article-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.article-title {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 8px;
  cursor: pointer;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  transition: color 0.2s;
}
.article-title:hover {
  color: #667eea;
}
.article-summary {
  font-size: 14px;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.article-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #94a3b8;
}
.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
}
.meta-item .el-icon {
  font-size: 14px;
}
.article-actions {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
  flex-shrink: 0;
}
.article-actions .el-button {
  padding: 6px 12px;
  font-size: 13px;
  margin: 0;
}
.empty-state {
  padding: 60px 0;
}
.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}
@media (max-width: 768px) {
  .my-articles-container {
    padding: 20px 0;
  }
  .articles-card {
    margin: 0 16px;
    padding: 20px;
    max-width: 100%;
  }
  .header-title {
    font-size: 16px;
  }
  .search-bar {
    flex-direction: column;
  }
  .search-bar .el-select {
    width: 100% !important;
  }
  .article-item {
    flex-direction: column;
  }
  .article-cover {
    width: 100%;
    height: 180px;
  }
  .article-actions {
    flex-direction: row;
    justify-content: flex-end;
  }
}
@media (max-width: 480px) {
  .articles-card {
    padding: 16px;
    border-radius: 20px;
  }
  .article-meta {
    flex-wrap: wrap;
    gap: 12px;
  }
}
</style>