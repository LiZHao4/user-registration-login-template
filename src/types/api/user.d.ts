import type { Response } from '.'
type GenderType = 'M' | 'W' | 'N'
interface User {
  id: number
  nick: string
  user: string
  avatar: string
  gender: GenderType
  birth: string
  bio: string
  background: string | null
  auto_theme: boolean
  theme_color: string | null
}
interface PrivateUser extends User {
  isAdmin: boolean
  systemMessageUnreadCount: number
  friendRequestCount: number
  friendUnreadCount: number
  token_expires: number
  created_at: number
}
type FriendStatus = 'self' | 'true' | 'false' | 'pending' | 'requested'
type FollowStatus = 'true' | 'false' | 'self'
interface PublicUser extends User {
  remark: string | null
  friend_status: FriendStatus
  follow_status: number
}
interface PrivateUserResponse extends Response {
  data: PrivateUser
}
interface PublicUserResponse extends Response {
  data: PublicUser
}