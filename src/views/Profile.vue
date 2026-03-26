<template>
  <div class="profile-container">
    <div class="background-section" :style="backgroundStyle"></div>
    <div class="profile-card">
      <div class="card-header">
        <div class="header-left">
          <el-button type="text" @click="goBack" class="back-btn">
            <el-icon><ArrowLeft /></el-icon>
            <span>返回</span>
          </el-button>
        </div>
        <div class="header-title">编辑资料</div>
        <div class="header-right">
          <el-button type="primary" @click="handleSave" :loading="saving">保存</el-button>
        </div>
      </div>
      <el-form ref="formRef" :model="form" :rules="formRules" label-position="top" class="profile-form">
        <el-form-item label="头像" class="avatar-item">
          <div class="avatar-uploader">
            <el-upload
              :show-file-list="false"
              :http-request="customUpload"
              :before-upload="beforeAvatarUpload"
              :on-success="handleAvatarSuccess"
              :on-error="handleAvatarError"
            >
              <el-avatar :src="avatarUrl" :size="80" shape="circle" />
              <div class="upload-overlay">
                <el-icon><Plus /></el-icon>
                <span>点击更换</span>
              </div>
            </el-upload>
          </div>
        </el-form-item>
        <el-form-item label="昵称" prop="nick">
          <el-input v-model="form.nick" placeholder="请输入昵称" />
        </el-form-item>
        <el-form-item label="性别">
          <el-radio-group v-model="form.gender">
            <el-radio label="M">男</el-radio>
            <el-radio label="W">女</el-radio>
            <el-radio label="N">未设置</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="生日">
          <el-date-picker
            v-model="form.birth"
            type="date"
            placeholder="选择生日"
            value-format="YYYY-MM-DD"
            style="width:100%"
          />
        </el-form-item>
        <el-form-item label="个人简介" prop="bio">
          <el-input v-model="form.bio" type="textarea" :rows="6" placeholder="介绍一下自己吧～" />
        </el-form-item>
        <el-form-item label="背景图片">
          <div class="background-uploader">
            <el-upload
              :show-file-list="false"
              :http-request="uploadBackground"
              :before-upload="beforeBackgroundUpload"
              :on-success="handleBackgroundSuccess"
              :on-error="handleBackgroundError"
            >
              <div class="background-preview" :style="{ backgroundImage: `url(${customBackground})` }">
                <div class="upload-overlay" v-if="!customBackground">
                  <el-icon><Plus /></el-icon>
                  <span>点击上传</span>
                </div>
                <div class="upload-overlay" v-else>
                  <el-icon><Edit /></el-icon>
                  <span>更换</span>
                </div>
              </div>
            </el-upload>
            <div class="bg-tip" v-if="customBackground">已设置背景图</div>
          </div>
        </el-form-item>
        <el-form-item v-if="customBackground" label="主题色与背景同步">
          <el-switch v-model="syncWithBg" inline-prompt active-text="开" inactive-text="关" />
        </el-form-item>
        <el-form-item v-if="!syncWithBg" label="自定义主题色">
          <el-color-picker v-model="themeColor" show-alpha :predefine="predefineColors" />
        </el-form-item>
      </el-form>
      <div class="password-section">
        <el-button type="secondary" @click="handleChangePassword" long>修改密码</el-button>
      </div>
      <div class="footer-save">
        <el-button type="primary" @click="handleSave" :loading="saving" long>保存修改</el-button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { ElMessage } from 'element-plus'
import { ArrowLeft, Plus } from '@element-plus/icons-vue'
import type { UploadRequestOptions, FormInstance, FormRules } from 'element-plus'
import type { PrivateUserAPIResponseData } from '@/types/api'
const router = useRouter()
const avatarUrl = ref<string>('')
const customBackground = ref<string>('')
const syncWithBg = ref<boolean>(true)
const themeColor = ref<string>('#409EFF')
const predefineColors = ref(['#ff4500', '#ff8c00', '#ffd700', '#90ee90', '#00ced1', '#1e90ff', '#c71585', '#409EFF'])
const originFormData = {
  nick: '',
  gender: '',
  birth: '',
  bio: '',
  auto_theme: true,
  theme_color: ''
}
const form = reactive({
  nick: '',
  gender: '',
  birth: '',
  bio: ''
})
const saving = ref<boolean>(false)
const formRef = ref<FormInstance>()
const formRules: FormRules = {
  nick: [
    { required: true, message: '昵称不能为空', trigger: 'blur' }
  ]
}
const backgroundStyle = computed(() => {
  if (customBackground.value) {
    return {
      backgroundImage: `url(${customBackground.value})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
      backgroundRepeat: 'no-repeat'
    }
  } else {
    return {
      background: 'linear-gradient(135deg, #6a11cb 0%, #2575fc 100%)'
    }
  }
})
const fetchUserProfile = async () => {
  try {
    const response = await axios.get<PrivateUserAPIResponseData>('/api/self')
    const data = response.data
    if (data.code === 1) {
      const user = data.data
      originFormData.nick = user.nick
      originFormData.gender = user.gender
      originFormData.birth = user.birth
      originFormData.bio = user.bio
      originFormData.auto_theme = user.auto_theme
      originFormData.theme_color = user.theme_color
      avatarUrl.value = user.avatar
      form.nick = user.nick
      form.gender = user.gender
      form.birth = user.birth
      form.bio = user.bio
      if (user.background) {
        customBackground.value = user.background
        syncWithBg.value = user.auto_theme
      }
      if (user.theme_color) themeColor.value = user.theme_color
    }
  } catch (error) {
    ElMessage.error('获取个人信息失败，请重试')
  }
}
const beforeAvatarUpload = (file: File) => {
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('只能上传图片文件！')
    return false
  }
  return true
}
const customUpload = async (options: UploadRequestOptions) => {
  const formData = new FormData()
  formData.append('file', options.file)
  try {
    const res = await axios.post('/api/upload/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (res.data.code === 1) {
      options.onSuccess(res.data.data.url)
    } else {
      
    }
  } catch (err) {
    
  }
}
const handleAvatarSuccess = (url: string) => {
  avatarUrl.value = url
  ElMessage.success('头像上传成功')
}
const handleAvatarError = () => {
  ElMessage.error('头像上传失败，请重试')
}
const goBack = () => {
  router.back()
}
const handleSave = async () => {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) {
      ElMessage.warning('请完整填写必要信息')
      return
    }
    saving.value = true
    try {
      const submitData = {}
      if (form.nick !== originFormData.nick) {
        submitData['nick'] = form.nick
      }
      if (form.gender !== originFormData.gender) {
        submitData['gender'] = form.gender
      }
      if (form.birth !== originFormData.birth) {
        submitData['birth'] = form.birth
      }
      if (form.bio !== originFormData.bio) {
        submitData['bio'] = form.bio
      }
      if (syncWithBg.value !== originFormData.auto_theme) {
        submitData['auto_theme'] = syncWithBg.value
      }
      if (themeColor.value !== originFormData.theme_color) {
        submitData['theme_color'] = themeColor.value
      }
      const response = await axios.patch('/api/self', submitData)
      if (response.data.code === 1) {
        ElMessage.success('个人信息更新成功')
        goBack()
      } else {
        ElMessage.error(response.data.message)
      }
    } catch (error) {
      ElMessage.error('保存过程中发生错误，请稍后重试')
    } finally {
      saving.value = false
    }
  })
}
const beforeBackgroundUpload = (file: File) => {
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('只能上传图片文件!')
    return false
  }
  return true
}
const uploadBackground = async (options: UploadRequestOptions) => {
}
const handleBackgroundSuccess = (url: string) => {
  customBackground.value = url
  ElMessage.success('背景上传成功')
}
const handleBackgroundError = () => {
  ElMessage.error('背景上传失败，请重试')
}
const handleChangePassword = () => {
  
}
onMounted(fetchUserProfile)
</script>
<style scoped>
.avatar-item {
  display: flex;
  justify-content: center;
  margin-bottom: 30px;
}
.avatar-uploader {
  position: relative;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s;
  width: 100%;
  display: flex;
  justify-content: end;
}
.avatar-uploader .upload-overlay {
  position: absolute;
  top: 0;
  right: 0;
  width: 80px;
  height: 80px;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s;
  border-radius: 50%;
  font-size: 12px;
  gap: 4px;
  backdrop-filter: blur(2px);
}
.avatar-uploader:hover .upload-overlay {
  opacity: 1;
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
  color: #2575fc;
}
.background-preview {
  width: 200px;
  height: 120px;
  border-radius: 12px;
  background-size: cover;
  background-position: center;
  background-color: #f0f2f5;
  border: 1px dashed #d9d9d9;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.3s;
}
.background-preview .upload-overlay {
  background: rgba(0,0,0,0.5);
  color: white;
  padding: 8px 16px;
  border-radius: 30px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  opacity: 0;
}
.background-preview:hover {
  border-color: #409EFF;
}
.background-preview:hover .upload-overlay {
  opacity: 1;
}
.background-section {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
}
.background-uploader {
  width: 100%;
}
.bg-tip {
  margin-top: 6px;
  font-size: 13px;
  color: #67c23a;
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
}
.divider-text {
  font-size: 14px;
  color: #64748b;
  font-weight: 400;
  background: rgba(255,255,255,0.6);
  padding: 0 12px;
  border-radius: 30px;
}
.el-date-editor,
.el-input,
.el-textarea {
  border-radius: 12px;
}
.el-form-item {
  margin-bottom: 22px;
}
.el-form-item:last-child {
  margin-bottom: 0;
}
.el-form-item__label {
  font-weight: 500;
  color: #334155;
  padding-bottom: 6px;
}
.el-radio-group {
  display: flex;
  gap: 20px;
}
.footer-save {
  display: none;
  margin-top: 20px;
}
.footer-save .el-button {
  width: 100%;
  border-radius: 40px;
  height: 44px;
  font-size: 16px;
}
.header-right .el-button {
  border-radius: 30px;
  padding: 8px 20px;
  font-weight: 500;
}
.password-section {
  margin-top: 20px;
  width: 100%;
}
.password-section .el-button {
  width: 100%;
  border-radius: 40px;
  height: 44px;
  font-size: 16px;
}
.profile-card {
  position: relative;
  z-index: 2;
  max-width: 600px;
  margin: 40px auto;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.5);
  padding: 24px 28px;
  transition: all 0.3s ease;
}
.profile-container {
  position: relative;
  width: 100%;
}
.profile-form :deep(.el-textarea__inner) {
  resize: none;
  border-radius: 12px;
}
.upload-overlay .el-icon {
  font-size: 20px;
}
:deep(.el-input__wrapper) {
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
@media (max-width: 768px) {
  .card-header {
    margin-bottom: 20px;
  }
  .footer-save {
    display: block;
  }
  .header-right {
    display: none;
  }
  .header-right .el-button {
    padding: 6px 16px;
    font-size: 14px;
  }
  .header-title {
    font-size: 16px;
  }
  .profile-card {
    margin: 20px 16px;
    padding: 20px;
    max-width: 100%;
  }
}
@media (max-width: 480px) {
  .profile-card {
    padding: 16px;
    border-radius: 20px;
  }
  .upload-overlay {
    font-size: 10px;
  }
}
</style>