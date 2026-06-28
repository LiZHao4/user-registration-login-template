import type { Response } from '.'
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
interface GroupInviteMessage extends Message {
  type: 3
  content: {
    name: string
    finish: boolean | number[]
    session: number
  }
}
interface SystemMessage extends Message {
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
interface MessageChangeRecords extends Message {
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
type ChatRecordItem = TextAsRecord | FileAsRecord | ChangeAsRecord | ChatRecordMessage
interface ChatRecordsMessage extends Message {
  type: 6
  content: ChatRecordItem[]
}
type MessageItem = TextMessage | FileMessage | GroupInviteMessage | SystemMessage | MessageChangeRecords | ChatRecordsMessage
interface ChatAPISimple extends Response {
  data: MessageItem[]
}
interface ChatAPI extends ChatAPISimple {
  sessionId: number
}
interface PrivateChatResponse extends ChatAPI {
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
interface GroupChatResponse extends ChatAPI {
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
type ChatResponse = PrivateChatResponse | GroupChatResponse