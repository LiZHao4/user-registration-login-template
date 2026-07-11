import db from '../config.js'
import { verifyToken } from './auth.js'
export async function checkArticleVisibility(req, res, next) {
  const articleId = parseInt(req.params.id)
  if (isNaN(articleId)) {
    return res.status(400).json({ code: -1, msg: '文章 ID 无效。' })
  }
  try {
    const article = await db.getOne('SELECT id, user_id, visibility FROM posts WHERE id = ?', [articleId])
    if (!article) {
      return res.status(404).json({ code: -1, msg: '文章不存在。' })
    }
    const token = req.cookies.t
    const currentUserId = await verifyToken(token)
    let allowed = false
    const { user_id: authorId, visibility } = article
    if (visibility === 'public') {
      allowed = true
    } else if (visibility === 'private' && currentUserId === authorId) {
      allowed = true
    } else if (visibility === 'mutuals' && currentUserId) {
      const [follow, followBack] = await Promise.all([
        db.query('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?', [currentUserId, authorId]),
        db.query('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?', [authorId, currentUserId])
      ])
      if (follow.length > 0 && followBack.length > 0) {
        allowed = true
      }
    }
    if (!allowed) {
      return res.status(403).json({ code: -1, msg: '无权查看此文章。' })
    }
    next()
  } catch (error) {
    console.error('检查文章可见性失败:', error)
    res.status(500).json({ code: -1, msg: '服务器错误，请稍后重试。' })
  }
}