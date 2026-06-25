interface MessageNotification {
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
interface FriendMessageNotification extends MessageNotification {
  sender_avatar: string
  type: 'friend'
}
interface GroupMessageNotification extends MessageNotification {
  group_avatar: string
  members: number
  group_name: string
  group_nickname: string
  type: 'group'
}
export type MessageNotificationType = FriendMessageNotification | GroupMessageNotification