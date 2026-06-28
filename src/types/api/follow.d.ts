import type { Pagination, Response } from '.'
interface UserFollowInfo {
  user_id: number
  nick: string
  avatar: string
  bio: string
  follow_status: number
}
interface FollowListResponse extends Response, Pagination {
  data: UserFollowInfo[]
}