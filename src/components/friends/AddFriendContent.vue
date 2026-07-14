<template>
  <div class="add-friend-content">
    <div class="search-area">
      <el-input v-model="keyword" placeholder="请输入用户ID或用户名" clearable @keyup.enter="handleSearch">
        <template #append><el-button :loading="loading" @click="handleSearch">搜索</el-button></template>
      </el-input>
    </div>
    <div v-if="loading" class="status-placeholder"><el-skeleton :rows="3" animated /></div>
    <div v-else-if="result" class="result-card">
      <el-avatar :src="result.avatar" :size="80" class="result-avatar" />
      <div class="result-name">{{ result.nick }}</div>
      <div class="result-id">ID：{{ result.id }}</div>
      <div class="result-bio" v-if="result.bio">{{ result.bio }}</div>
      <el-button type="primary" size="large" class="add-btn" :disabled="sent" @click="sendRequest">
        {{ sent ? '已发送请求' : '添加好友' }}
      </el-button>
    </div>
    <div v-else-if="searched && !result" class="status-placeholder">
      <el-empty description="未找到该用户，请检查ID或用户名" :image-size="80" />
    </div>
    <div v-else class="tips">
      <el-icon class="tips-icon"><Search /></el-icon><span>输入准确的用户ID或用户名进行查找</span>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'
import { SearchUserItem, SearchUserResponse } from '@/types/api/search'
const keyword = ref('')
const loading = ref(false)
const searched = ref(false)
const result = ref<SearchUserItem>({} as SearchUserItem)
const sent = ref(false)
const handleSearch = async () => {
  const kw = keyword.value.trim()
  if (!kw) {
    ElMessage.warning('请输入用户ID或用户名')
    return
  }
  loading.value = true
  searched.value = true
  sent.value = false
  try {
    const res = await axios.get<SearchUserResponse>('/api/search/users', { params: { q: kw } })
    if (res.data.code === 1 && res.data.data) {
      result.value = res.data.data
    }
  } catch (error) {
    console.error(error)
    ElMessage.error('搜索失败')
  } finally {
    loading.value = false
  }
}
const sendRequest = async () => {
  if (!result.value) return
  try {
    await axios.post('/api/friend-request', { userId: result.value.id })
    sent.value = true
    ElMessage.success('好友请求已发送')
  } catch (error) {
    ElMessage.error('发送失败，请重试')
  }
}
</script>
<style scoped>
.add-btn {
  width: 80%;
  border-radius: 24px;
}
.add-friend-content {
  padding: 0 4px;
}
.result-avatar {
  margin-bottom: 16px;
  border: 3px solid #fff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.result-bio {
  font-size: 14px;
  color: #606266;
  margin-bottom: 24px;
  background: #fff;
  padding: 6px 16px;
  border-radius: 20px;
  border: 1px dashed #dcdfe6;
}
.result-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px 16px 32px;
  background: #fafbfc;
  border-radius: 16px;
  border: 1px solid #ebeef5;
  margin-top: 8px;
}
.result-id {
  font-size: 13px;
  color: #909399;
  margin-bottom: 8px;
  background: #f0f2f5;
  padding: 2px 12px;
  border-radius: 12px;
}
.result-name {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
}
.search-area {
  margin-bottom: 24px;
}
.status-placeholder {
  padding: 20px 0;
}
.tips {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #a8abb2;
  font-size: 14px;
  margin-top: 60px;
  gap: 8px;
}
.tips-icon {
  font-size: 40px;
  color: #d0d3d9;
}
</style>