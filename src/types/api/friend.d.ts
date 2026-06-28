import type { Response } from '.'
interface Friend {
  id: number
  avatar: string
  nick: string
  time: number
  content: string
  msg_type: number
  msg_sender: number
  msg_nick: string
  unread_count: number
  inner_nick?: string
}
interface PrivateChat extends Friend {
  type: 'friend'
  friend_id: number
}
interface GroupChat extends Friend {
  type: 'group'
  member_count: number
}
type FriendItem = PrivateChat | GroupChat
interface FriendListResponse extends Response {
  data: FriendItem[]
}