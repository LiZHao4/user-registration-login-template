import express from 'express'
import db from '../config.js'
import { pagination } from '../middlewares/pagination.js'
import { verifyToken } from '../middlewares/auth.js'
import { escapeLikeKeyword } from '../utils.js'
const router = express.Router()
router.get('/search', pagination(10), async (req, res) => {
  try {
    const { keyword, type } = req.query
    const { limit, offset } = req.pagination
    const userId = await verifyToken(req.cookies.t)
    if (!keyword || keyword.trim() === '') {
      return res.json({ code: -1, msg: '请提供搜索关键词。' })
    }
    const rawKeyword = keyword.trim()
    const escapedKeyword = escapeLikeKeyword(rawKeyword)
    const searchKey = `%${escapedKeyword}%`
    const result = []
    let total = 0
    switch (type) {
      case 'article': {
        const articleSql = `
          SELECT p.id, p.title, p.content, p.created_at, p.updated_at, 
                 u.id as authorId, u.nick as authorNick, u.user_avatar as authorAvatar
          FROM posts p
          LEFT JOIN users u ON p.user_id = u.id
          WHERE p.title LIKE ? OR p.content LIKE ?
          ORDER BY CASE 
            WHEN p.title LIKE ? THEN 1
            WHEN p.content LIKE ? THEN 2
            ELSE 3
          END, p.updated_at DESC
          LIMIT ${limit} OFFSET ${offset}
        `
        const articleParams = [searchKey, searchKey, searchKey, searchKey]
        const articles = await db.query(articleSql, articleParams)
        for (const article of articles) {
          const tagSql = `
            SELECT pt.id, pt.tag
            FROM post_tags pt
            LEFT JOIN post_tag_relations ptr ON pt.id = ptr.tag_id
            WHERE ptr.post_id = ?
          `
          article.tags = await db.query(tagSql, [article.id])
        }
        result.push(...articles)
        const countSql = 'SELECT COUNT(*) as total FROM posts p WHERE p.title LIKE ? OR p.content LIKE ?'
        const countResult = await db.query(countSql, [searchKey, searchKey])
        total = countResult[0].total
        break
      }
      case 'user': {
        const userSql = `
          SELECT id, user, nick, user_avatar
          FROM users
          WHERE user COLLATE utf8mb4_0900_ai_ci LIKE ? OR nick LIKE ?
          ORDER BY CASE 
            WHEN user COLLATE utf8mb4_0900_ai_ci LIKE ? THEN 1
            WHEN nick LIKE ? THEN 2
            ELSE 3
          END, id
          LIMIT ${limit} OFFSET ${offset}
        `
        const userParams = [searchKey, searchKey, searchKey, searchKey]
        const users = await db.query(userSql, userParams)
        if (userId) {
          const userIds = users.map(u => u.id)
          if (userIds.length > 0) {
            const placeholders = userIds.map(() => '?').join(',')
            const followSql = `
              SELECT following_id
              FROM follows
              WHERE follower_id = ? AND following_id IN (${placeholders})
            `
            const followRows = await db.query(followSql, [userId, ...userIds])
            const followSet = new Set(followRows.map(row => row.following_id))
            users.forEach(u => {
              u.isFollow = followSet.has(u.id)
            })
          }
        } else {
          users.forEach(u => {
            u.isFollow = false
          })
        }
        result.push(...users)
        const countSql = `SELECT
          COUNT(*) as total
          FROM users
          WHERE user COLLATE utf8mb4_0900_ai_ci LIKE ? OR nick LIKE ?
        `
        const countResult = await db.query(countSql, [searchKey, searchKey])
        total = countResult[0].total
        break
      }
      case 'tag': {
        const tagSql = `
          SELECT pt.id, pt.tag, COUNT(ptr.post_id) as post_count
          FROM post_tags pt
          LEFT JOIN post_tag_relations ptr
          ON pt.id = ptr.tag_id
          WHERE pt.tag LIKE ?
          GROUP BY pt.id, pt.tag
          ORDER BY CASE WHEN pt.tag = ? THEN 1 ELSE 2 END
          LIMIT ${limit} OFFSET ${offset}
        `
        const tagParams = [searchKey, keyword.trim()]
        const tags = await db.query(tagSql, tagParams)
        result.push(...tags)
        const countSql = 'SELECT COUNT(*) as total FROM post_tags WHERE tag LIKE ?'
        const countResult = await db.query(countSql, [searchKey])
        total = countResult[0].total
        break
      }
      default:
        return res.json({ code: -1, msg: '不支持的搜索类型。' })
    }
    res.json({
      code: 1,
      msg: '搜索成功。',
      data: result,
      pagination: { total, limit, offset }
    })
  } catch (error) {
    console.error('搜索错误:', error)
    res.status(500).json({ code: -1, msg: '搜索失败。' })
  }
})
router.get('/search/users', async (req, res) => {
  const q = req.query.q
  if (!q) {
    return res.status(400).json({
      code: -1,
      msg: '缺少必要的参数。'
    })
  }
  try {
    const param = q
    let statement = 'SELECT id, user, nick, user_avatar AS avatar, bio FROM users WHERE '
    if (/^\d+$/.test(q)) {
      statement += 'id = ?'
    } else if (/^[a-zA-Z_$][a-zA-Z0-9_$]{0,31}$/.test(q)) {
      statement += 'user = ?'
    } else {
      return res.status(400).json({
        code: -1,
        msg: '请求格式不正确。'
      })
    }
    const row = await db.getOne(statement, [param])
    if (row) {
      res.json({
        code: 1,
        msg: '用户信息获取成功。',
        data: row
      })
    } else {
      res.status(404).json({
        code: -1,
        msg: '用户不存在。'
      })
    }
  } catch (error) {
    console.error('搜索用户错误:', error)
    res.status(500).json({
      code: -1,
      msg: '搜索用户失败。'
    })
  }
})
export default router