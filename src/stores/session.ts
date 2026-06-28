import { defineStore } from 'pinia'
import { getDisplayContent0 } from '@/utils/messageUtils'
import { useUserStore } from './user'
import type { FriendItem } from '@/types/api/friend'
import type { MessageType } from '@/types/socket'
export const useSessionStore = defineStore('session', {
  state: () => ({
    sessions: [] as FriendItem[]
  }),
  getters: {
    friends: state => state.sessions
  },
  actions: {
    initSessions(sessions: FriendItem[]) {
      this.sessions = sessions
    },
    async updateSessionFromMessage(message: MessageType) {
      const sessionId = message.session
      const userStore = useUserStore()
      const existing: FriendItem = this.sessions.find((s: FriendItem) => s.id === sessionId)
      const content = await getDisplayContent0(message, userStore.userId)
      if (existing) {
        existing.time = message.time
        existing.unread_count = message.unread_count
        existing.content = content
        existing.msg_type = message.msg_type
        existing.msg_sender = message.sender
        existing.msg_nick = message.remark || message.msg_nick
        if (message.type === 'friend' && existing.type === 'friend') {
          existing.avatar = message.sender_avatar
        } else if (message.type === 'group' && existing.type === 'group') {
          existing.avatar = message.group_avatar
          existing.nick = message.group_name
          if ('members' in message) {
            existing.member_count = message.members
          }
        }
      } else {
        const newSession: FriendItem = {
          id: sessionId,
          avatar: message.type === 'group' ? message.group_avatar : message.sender_avatar,
          nick: message.type === 'group' ? message.group_name : (message.remark || message.msg_nick),
          time: message.time,
          content,
          msg_type: message.msg_type,
          msg_sender: message.sender,
          msg_nick: message.msg_nick,
          unread_count: message.unread_count,
          type: message.type
        } as FriendItem
        if (message.type === 'group' && newSession.type === 'group') {
          newSession.member_count = message.members
        }
        this.sessions.unshift(newSession)
      }
    },
    clearUnread(sessionId: number) {
      const session = this.sessions.find((s: FriendItem) => s.id === sessionId)
      if (session) {
        session.unread_count = 0
      }
    }
  }
})