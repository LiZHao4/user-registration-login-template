import {} from ''
interface Message {
  id: number
  session: number
  content: string
  sender: number
  remark: string
  msg_nick: string
  msg_type: number
  time: number
  unread_count: number
}
interface FriendMessage extends Message {
  sender_avatar: string
  type: 'friend'
}
interface GroupMessage extends Message {
  group_avatar: string
  members: number
  group_name: string
  group_nickname: string
  type: 'group'
}
type MessageType = FriendMessage | GroupMessage