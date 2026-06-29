import { defineStore } from 'pinia'
import type { FriendRequestUser } from '@/types/api/request'
import axios from 'axios'
export const useFriendStore = defineStore('friend', {
  state: () => ({
    receivedRequests: [] as FriendRequestUser[],
    sentRequests: [] as FriendRequestUser[]
  }),
  getters: {
    unreadCount: state => state.receivedRequests.length
  },
  actions: {
    async fetchRequests() {
      try {
        const res = await axios.get('/api/requests')
        if (res.data.code === 1) {
          this.receivedRequests = res.data.received
          this.sentRequests = res.data.sent
        }
      } catch (e) {
        console.error('获取好友请求失败', e)
      }
    },
    addReceivedRequest(request: FriendRequestUser) {
      if (!this.receivedRequests.some(r => r.id === request.id)) {
        this.receivedRequests.unshift(request)
      }
    },
    removeRequest(requestId: number) {
      this.receivedRequests = this.receivedRequests.filter(r => r.id !== requestId)
      this.sentRequests = this.sentRequests.filter(r => r.id !== requestId)
    }
  }
})