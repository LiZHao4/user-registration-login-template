import express from 'express'
import db from '../config.js'
import { verifyToken } from '../middlewares/auth.js'
import { pagination } from '../middlewares/pagination.js'
import { checkArticleVisibility } from '../middlewares/article.js'
const router = express.Router()
router.get('/articles/:id/comments', checkArticleVisibility, pagination(), async (req, res) => {
  const { page, limit, offset } = req.pagination
  try {
    const articleId = req.params.id
    const userId = verifyToken(req.cookies.t)
    const listQuery = `
      SELECT 
        c.id,
        c.content,
        UNIX_TIMESTAMP(c.created_at) AS createdAt,
        u.id AS userId,
        u.nick,
        u.user_avatar AS avatar,
        (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) AS replyCount,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) AS likeCount,
        CASE WHEN ? IS NULL THEN 0 ELSE (
          SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id AND user_id = ?
        ) END AS is_liked,
      FROM comments c
      LEFT JOIN users u ON c.user_id = u.id
      WHERE c.post_id = ? AND c.parent_id IS NULL
      ORDER BY c.created_at DESC
      LIMIT ${limit} OFFSET ${offset}
    `
    const comments = await db.query(listQuery, [userId, userId, articleId])
    const totalResult = await db.getOne(
      'SELECT COUNT(*) AS total FROM comments WHERE post_id = ? AND parent_id IS NULL',
      [articleId]
    )
    const total = totalResult.total
    const list = comments.map(row => ({
      id: row.id,
      user_id: row.userId,
      user_nick: row.nick,
      user_avatar: row.avatar,
      content: row.content,
      createdAt: row.createdAt,
      replyCount: row.replyCount,
      likeCount: row.likeCount,
      isLiked: row.is_liked
    }))
    res.json({
      code: 1,
      msg: '获取评论列表成功。',
      data: list,
      pagination: { total, page, limit }
    })
  } catch (error) {
    console.error('获取评论列表失败:', error)
    res.status(500).json({ code: -1, msg: '服务器错误，请稍后重试。' })
  }
})
export default router