<template>
  <div class="register-page">
    <div class="register-container">
      <div class="register-header">
        <Button class="back-btn" @click="goToLogin">← 返回</Button>
        <h1>注册</h1>
      </div>
      <div class="register-form">
        <div class="form-group">
          <label>用户名</label>
          <input
            type="text"
            v-model="form.username"
            @blur="validateUsername"
            :class="{ 'is-invalid': errors.username }"
            required
          >
          <span v-if="errors.username" class="error-message">{{ errors.username }}</span>
        </div>
        <div class="form-group">
          <label>密码</label>
          <input
            type="password"
            v-model="form.password"
            @blur="validatePassword"
            :class="{ 'is-invalid': errors.password }"
            required
          >
          <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
        </div>
        <div class="form-group">
          <label>确认密码</label>
          <input
            type="password"
            v-model="form.confirmPassword"
            @blur="validateConfirmPassword"
            :class="{ 'is-invalid': errors.confirmPassword }"
            required
          >
          <span v-if="errors.confirmPassword" class="error-message">{{ errors.confirmPassword }}</span>
        </div>
        <div class="button-group">
          <Button type="primary" @click="submitForm" :loading="loading" :disabled="!isFormValid">注册</Button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import Button from '@/components/ui/Button.vue'
import axios from 'axios'
import type { RegisterAPIResponseData } from '@/types/api'
import { ElMessage } from 'element-plus'
interface RegisterForm {
  username: string
  password: string
  confirmPassword: string
}
interface FormErrors {
  username?: string
  password?: string
  confirmPassword?: string
}
const router = useRouter()
const form = reactive<RegisterForm>({
  username: '',
  password: '',
  confirmPassword: ''
})
const errors = reactive<FormErrors>({})
const loading = ref<boolean>(false)
const validateUsername = () => {
  if (form.username.length < 1 || form.username.length > 32 || !/^[a-zA-Z_$][a-zA-Z0-9_$]*$/.test(form.username)) {
    errors.username = 
    '用户名必须以字母、下划线_或美元符号$开头，可包含字母（大小写）、数字、下划线_或美元符号$，总长度1到32位。'
  } else {
    delete errors.username
  }
}
const validatePassword = () => {
  if (
    form.password.length < 8 ||
    form.password.length > 32 ||
    !/[a-z]/.test(form.password) ||
    !/[A-Z]/.test(form.password) ||
    !/[0-9]/.test(form.password)
  ) {
    errors.password = '密码长度8到32位，必须包含至少1个小写字母、1个大写字母和1个数字。'
  } else {
    delete errors.password
  }
}
const validateConfirmPassword = () => {
  if (!form.confirmPassword) {
    errors.confirmPassword = '请再次输入密码。'
  } else if (form.confirmPassword !== form.password) {
    errors.confirmPassword = '两次输入的密码不一致。'
  } else {
    delete errors.confirmPassword
  }
}
const isFormValid = computed(() => {
  return (
    form.username &&
    form.password &&
    form.confirmPassword &&
    !errors.username &&
    !errors.password &&
    !errors.confirmPassword
  )
})
const submitForm = async () => {
  validateUsername()
  validatePassword()
  validateConfirmPassword()
  if (!isFormValid.value) {
    ElMessage.error('请正确填写表单')
    return
  }
  loading.value = true
  try {
    const response = await axios.post<RegisterAPIResponseData>('/api/register', {
      username: form.username,
      password: form.password
    })
    if (response.data.code !== 1) {
      ElMessage.error(response.data.msg)
    } else {
      ElMessage.success('注册成功，请登录')
      router.push('/login')
    }
  } catch (error) {
    ElMessage.error('注册失败，请稍后重试')
  } finally {
    loading.value = false
  }
}
const goToLogin = () => {
  router.push('/login')
}
</script>
<style scoped>
.back-btn {
  background: transparent;
  color: #007bff;
  font-size: 1rem;
  cursor: pointer;
  position: absolute;
}
.back-btn:hover {
  background: transparent;
}
.button-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 1.5rem;
}
.error-message {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #dc3545;
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
  transition: border-color 0.2s, box-shadow 0.2s;
}
.form-group input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}
.form-group input.is-invalid {
  border-color: #dc3545;
}
.form-group input.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
}
.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}
.register-container {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}
.register-form {
  width: 100%;
}
.register-header {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
  position: relative;
}
.register-header h1 {
  flex: 1;
  text-align: center;
  margin: 0;
}
.register-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f5f5f5;
}
</style>