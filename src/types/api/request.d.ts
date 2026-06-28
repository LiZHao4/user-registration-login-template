import type { Response } from '.'
interface FriendRequestUser {
  id: number
  source: number
  target: number
  time: number
  nick: string
  remark: string | null
}
interface FriendRequestResponse extends Response {
  received: FriendRequestUser[]
  sent: FriendRequestUser[]
}