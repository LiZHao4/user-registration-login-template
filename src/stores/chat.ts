import { defineStore } from 'pinia'
import type { MessageItem } from '@/types/api'
export const useChatStore = defineStore('chat', {
  state: () => ({
    messagesMap: new Map<number, MessageItem[]>()
  }),
  actions: {
    addMessage(chatId: number, message: MessageItem) {
      const messages = this.messagesMap.get(chatId) || []
      messages.push(message)
      this.messagesMap.set(chatId, messages)
    },
    setMessages(chatId: number, messages: MessageItem[]) {
      this.messagesMap.set(chatId, messages)
    },
    getMessages(chatId: number): MessageItem[] {
      return this.messagesMap.get(chatId) || []
    }
  }
})