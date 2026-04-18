import { defineStore } from 'pinia'
export const useUserStore = defineStore('user', {
  state: () => ({
    userId: null as number | null,
    isLogin: false
  }),
  actions: {
    login(userId: number) {
      this.userId = userId
      this.isLogin = true
    },
    logout() {
      this.userId = null
      this.isLogin = false
    }
  }
})