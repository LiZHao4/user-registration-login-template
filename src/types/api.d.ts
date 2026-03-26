interface APIResponse {
  code: number
  msg: string
}
export interface LoginAPIResponseData extends APIResponse {
  token: string
  expires: number
  unbanned_at?: number
}
export type RegisterAPIResponseData = APIResponse
export type GenderType = 'M' | 'W' | 'N'
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
export interface PublicUser extends User {
  remark: string | null
  friend_status: string
  follow_status: number
}
export interface PrivateUserAPIResponseData extends APIResponse { data: PrivateUser }
export interface PublicUserAPIResponseData extends APIResponse { data: PublicUser }
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
interface Message {
  id: number
  sent_at: number
  sender: number
}
interface TextMessage extends Message {
  type: 1
  content: string
}
interface FileMessage extends Message {
  type: 2
  content: string
  multi: string
}
export interface GroupInviteMessage extends Message {
  type: 3
  content: {
    name: string
    finish: boolean | number[]
    session: number
  }
}
export interface SystemMessage extends Message {
  type: 4
  content: {
    type: string
    target?: number
  }
  nick?: string
  inner_nick?: string
}
interface MessageChangeRecord {
  msg: string
  sent_at: number
}
export interface MessageChangeRecords extends Message {
  type: 5
  content: MessageChangeRecord[]
}
interface ChatRecord {
  sender: number
  sent_at: number
  sender_nick: string
  sender_avatar: string
}
interface TextAsRecord extends ChatRecord {
  type: 1
  content: string
}
interface FileAsRecord extends ChatRecord {
  type: 2
  content: string
  multi: string
}
interface ChangeAsRecord extends ChatRecord {
  type: 5
  content: MessageChangeRecord[]
}
interface ChatRecordMessage extends ChatRecord {
  type: 6
  content: ChatRecordItem[]
}
export type ChatRecordItem = TextAsRecord | FileAsRecord | ChangeAsRecord | ChatRecordMessage
interface ChatRecordsMessage extends Message {
  type: 6
  content: ChatRecordItem[]
}
export type MessageItem = TextMessage | FileMessage | GroupInviteMessage | SystemMessage | MessageChangeRecords | ChatRecordsMessage
export interface FriendListAPIResponseData extends APIResponse {
  data: FriendItem[]
  user_id: number
}
interface FriendRequestUser {
  id: number
  source: number
  target: number
  time: number
  nick: string
  remark: string | null
}
export interface FriendRequestAPIResponseData extends APIResponse {
  received: FriendRequestUser[]
  sent: FriendRequestUser[]
}
export interface ChatAPISimple extends APIResponse {
  data: MessageItem[]
}
interface ChatAPI extends ChatAPISimple {
  sessionId: number
}
interface PrivateChatAPIResponseData extends ChatAPI {
  type: 'friend'
  id: number
  oId: number
  avatar: string
  opposite: string
  oName: string
  remark: string | null
  requestTime: number
  allowedTime: number
}
interface GroupChatAPIResponseData extends ChatAPI {
  type: 'group'
  members: {
    id: number
    nick: string
    remark: string | null
    group_nickname: string | null
    avatar: string
    role: 'owner' | 'admin' | 'member'
  }[]
  joined_at: number
  group_name: string
  current_user_index: number
  group_info_permission: number
  group_avatar: string
}
export type ChatAPIResponseData = PrivateChatAPIResponseData | GroupChatAPIResponseData