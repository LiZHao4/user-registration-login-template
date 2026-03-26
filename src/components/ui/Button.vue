<template>
  <button
    @click="handleClick"
    :class="['button', size, type, { disabled, loading }]"
    :style="style"
    :disabled="disabled || loading"
    @mouseenter="hover = true"
    @mouseleave="hover = false">
    <span v-if="loading" class="button-loader"></span>
    <span class="button-content">
      <slot>{{ label }}</slot>
    </span>
  </button>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue'
export type ButtonType = 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'default'
export type ButtonSize = 'small' | 'medium' | 'large'
const props = withDefaults(defineProps<{
  label?: string
  type?: ButtonType
  size?: ButtonSize
  backgroundColor?: string
  textColor?: string
  hoverBackgroundColor?: string
  hoverTextColor?: string
  disabled?: boolean
  loading?: boolean
  borderRadius?: string
}>(), {
  type: 'default',
  size: 'medium'
})
const emit = defineEmits<{
  click: []
}>()
const hover = ref(false)
const style = computed(() => {
  const styles: Record<string, string> = {}
  if (props.backgroundColor) {
    styles['--bg-color'] = props.backgroundColor
  }
  if (props.textColor) {
    styles['--text-color'] = props.textColor
  }
  if (props.hoverBackgroundColor) {
    styles['--hover-bg-color'] = props.hoverBackgroundColor
  }
  if (props.hoverTextColor) {
    styles['--hover-text-color'] = props.hoverTextColor
  }
  if (props.borderRadius) {
    styles['--border-radius'] = props.borderRadius
  }
  return styles
})
const handleClick = () => {
  if (!props.disabled && !props.loading) {
    emit('click')
  }
}
</script>
<style scoped>
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  cursor: pointer;
  font-family: inherit;
  font-weight: 500;
  transition: all 0.2s ease;
  outline: none;
  position: relative;
  background-color: var(--bg-color, #f0f0f0);
  color: var(--text-color, #333);
  border-radius: var(--border-radius, 4px);
}
.button:hover:not(.disabled):not(.loading) {
  background-color: var(--hover-bg-color);
  color: var(--hover-text-color, var(--text-color));
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.button-content {
  display: flex;
  align-items: center;
  justify-content: center;
}
.button-loader {
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-right: 8px;
}
.danger {
  --bg-color: #ff4d4f;
  --text-color: white;
  --hover-bg-color: #ff7875;
}
.default {
  --bg-color: #f0f0f0;
  --text-color: #333;
  --hover-bg-color: #d9d9d9;
}
.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.large {
  padding: 14px 28px;
  font-size: 16px;
}
.large .button-loader {
  width: 16px;
  height: 16px;
}
.loading {
  cursor: wait;
}
.medium {
  padding: 10px 20px;
  font-size: 14px;
}
.medium .button-loader {
  width: 14px;
  height: 14px;
}
.primary {
  --bg-color: #1890ff;
  --text-color: white;
  --hover-bg-color: #40a9ff;
}
.secondary {
  --bg-color: #f5f5f5;
  --text-color: #333;
  --hover-bg-color: #e6e6e6;
}
.small {
  padding: 6px 12px;
  font-size: 12px;
}
.small .button-loader {
  width: 12px;
  height: 12px;
}
.success {
  --bg-color: #52c41a;
  --text-color: white;
  --hover-bg-color: #73d13d;
}
.warning {
  --bg-color: #faad14;
  --text-color: white;
  --hover-bg-color: #ffc53d;
}
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>