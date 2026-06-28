import type { Response, Pagination } from '.'
interface UserArticle {
  id: number
  updateTime: number
  title: string
  content: string
}
interface HomeArticle extends UserArticle {
  avatar: string
  nick: string
  images: string[]
  likeCount: number
  commentCount: number
  isLike: boolean
}
interface ArticleDetail {
  id: number
  user_id: number
  user_avatar: string
  user_nick: string
  title: string
  content: string
  images: string[]
  tags: string[]
  visibility: 'public' | 'mutuals' | 'private'
  publishTime: number
  updateTime: number
  likeCount: number
  isLiked: boolean
}
interface HomeArticleListResponse extends Response {
  data: HomeArticle[]
}
interface UserArticleListResponse extends Response, Pagination {
  data: UserArticle[]
}
interface ArticleDetailResponse extends Response {
  data: ArticleDetail
}