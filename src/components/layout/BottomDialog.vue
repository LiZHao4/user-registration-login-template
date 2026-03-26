<template>
  <div class="dialog-overlay" ref="dialogOverlay">
    <div class="dialog" ref="dialogElement" @click.stop>
      <div class="bottom-dialog__header">
        <h3 class="bottom-dialog__title">{{ config.title }}</h3>
        <button class="bottom-dialog__close" @click="close">×</button>
      </div>
      <div class="bottom-dialog__content">
        <p class="bottom-dialog__content-text" v-if="config.content">{{ config.content }}</p>
        <component
          v-if="config.component"
          :is="config.component"
          :formData="formData"
          :updateForm="updateForm"
          :deleteForm="deleteForm"
        />
      </div>
      <div class="bottom-dialog__footer" v-if="config.buttons && config.buttons.length">
        <el-button
          v-for="(button, index) in config.buttons"
          :key="index"
          :type="button.type || 'default'"
          :size="button.size || 'default'"
          @click="() => handleButtonClick(button)"
          class="bottom-dialog__button"
        >
          {{ button.label }}
        </el-button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, reactive, watch, computed, type Component } from 'vue'
export interface DialogButton {
  label: string
  type?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
  size?: 'large' | 'default' | 'small'
  click?: (formData: Record<string, any>) => Promise<boolean | void> | boolean | void
}
export interface DialogConfig {
  title?: string
  content?: string
  buttons?: DialogButton[]
  component?: Component
}
export type DialogConfigFunc = (config: DialogConfig) => void
export type DialogProps = {
  formData: Record<string, any>
  updateForm: (key: string, value: any) => void
  deleteForm: (key: string) => void
}
const props = defineProps<{
  config: DialogConfig
  visible: boolean
}>()
const emit = defineEmits<{
  'update:visible': [value: boolean]
}>()
const dialogOverlay = ref<HTMLElement | null>(null)
const dialogElement = ref<HTMLElement | null>(null)
const formData = reactive<DialogProps['formData']>({})
const internalVisible = computed({
  get: () => props.visible,
  set: value => emit('update:visible', value)
})
const updateForm: DialogProps['updateForm'] = (key, value) => {
  formData[key] = value
}
const deleteForm: DialogProps['deleteForm'] = key => {
  delete formData[key]
}
const handleButtonClick = async (button: DialogButton) => {
  let shouldClose = true
  if (button.click) {
    const result = await button.click(formData)
    if (result === false) shouldClose = false
  }
  if (shouldClose) internalVisible.value = false
}
const close = () => {
  internalVisible.value = false
}
watch(
  () => props.visible,
  newVal => {
    if (!dialogOverlay.value || !dialogElement.value) return
    if (newVal) {
      dialogOverlay.value.style.display = 'flex'
      dialogElement.value.style.display = 'flex'
      dialogElement.value.style.transform = 'translateY(0)'
      dialogElement.value.style.animation = 'slideUp 0.3s ease forwards'
      dialogElement.value.addEventListener('animationend', () => {
        dialogElement.value.style.animation = ''
      }, { once: true })
    } else {
      dialogElement.value.style.animation = 'slideDown 0.3s ease forwards'
      dialogElement.value.style.transform = 'translateY(100%)'
      dialogElement.value.addEventListener('animationend', () => {
        dialogOverlay.value.style.display = 'none'
        dialogElement.value.style.animation = ''
      }, { once: true })
    }
  }
)
</script>
<style scoped>
.bottom-dialog__button {
  flex: 0 1 auto;
  min-width: 80px;
}
.bottom-dialog__close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background-color 0.2s, color 0.2s;
}
.bottom-dialog__close:hover {
  background: #f5f5f5;
  color: #666;
}
.bottom-dialog__content {
  padding: 1.5rem;
  flex: 1;
  overflow-y: auto;
  color: #666;
  line-height: 1.5;
}
.bottom-dialog__content-text {
  margin: 0;
  white-space: pre;
}
.bottom-dialog__footer {
  padding: 1rem 1.5rem 1.5rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: flex-end;
}
.bottom-dialog__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 1rem;
}
.bottom-dialog__title {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
}
.dialog {
  background: white;
  border-radius: 12px 12px 0 0;
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  display: none;
  flex-direction: column;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: none;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
}
@media (max-width: 480px) {
  .dialog {
    max-width: 100%;
    border-radius: 0;
  }
}
</style>
<style>
@keyframes slideDown {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(100%);
  }
}
@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}
</style>