<template>
  <div class="login-page">
    <div class="login-container">
      <h1>登录</h1>
      <div class="login-form">
        <div class="form-group">
          <label>用户名</label>
          <input type="text" v-model="form.username" required>
        </div>
        <div class="form-group">
          <label>密码</label>
          <input type="password" v-model="form.password" required>
        </div>
        <Button type="primary" @click="submitForm" :loading="loading">登录</Button>
        <Button type="secondary" @click="goToRegister">注册</Button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import type { LoginAPIResponseData } from '@/types/api'
import { ElMessage } from 'element-plus'
import { formatDateLong } from '@/utils/dateFormatter'
interface LoginForm {
  username: string
  password: string
}
const router = useRouter()
const form = reactive<LoginForm>({
  username: '',
  password: ''
})
const loading = ref<boolean>(false)
const submitForm = async () => {
  if (!form.username || !form.password) {
    ElMessage.error('请输入用户名和密码。')
    return
  }
  loading.value = true
  try {
    const result = await axios.post<LoginAPIResponseData>('/api/login', {
      user: form.username,
      pass: form.password
    })
    if (result.data.code !== 1) {
      const message = result.data.msg.replace('#t', formatDateLong(result.data.unbanned_at))
      ElMessage.error(message)
    } else {
      router.push('/')
    }
  } catch (error) {
    ElMessage.error('登录失败，请稍后重试。')
  } finally {
    loading.value = false
  }
}
const goToRegister = () => {
  router.push('/regist')
}
</script>
<style scoped>
Button {
  width: 100%;
  margin-bottom: 0.5rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  box-sizing: border-box;
}
.form-group input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}
.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}
.login-container {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}
.login-container h1 {
  text-align: center;
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #333;
}
.login-form {
  width: 100%;
}
.login-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f5f5f5;
}
</style>