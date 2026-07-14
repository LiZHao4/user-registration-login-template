import type { Pagination, Response } from '.'
import { FriendStatus } from './user'
interface SearchArticle {
  id: number
  title: string
  content: string
  created_at: number
  updated_at: number
  authorId: number
  authorNick: string
  authorAvatar: string
  tags: { id: number, tag: string }[]
}
interface SearchUser {
  id: number
  user: string
  nick: string
  user_avatar: string
  isFollow: boolean
}
interface SearchTag {
  id: number
  tag: string
  post_count: number
}
interface SearchResponse extends Response, Pagination {
  data: (SearchArticle | SearchUser | SearchTag)[]
}
interface SearchUserItem {
  id: number
  user: string
  nick: string
  avatar: string
  bio: string
  friend_status: FriendStatus
}
interface SearchUserResponse extends Response {
  data: SearchUserItem
}