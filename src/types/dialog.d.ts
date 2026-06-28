import type { Component } from 'vue'
interface DialogButton {
  label: string
  type?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
  size?: 'large' | 'default' | 'small'
  click?: (formData: Record<string, any>) => Promise<boolean | void> | boolean | void
}
interface DialogConfig {
  title?: string
  content?: string
  buttons?: DialogButton[]
  component?: Component
}
type DialogConfigFunc = (config: DialogConfig) => void