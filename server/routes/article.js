import express from 'express'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
const router = express.Router()
router.get('/articles', async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1
    const limit = parseInt(req.query.limit) || 30
    const offset = (page - 1) * limit
    const query = `
      SELECT 
        p.id,
        p.title,
        p.content,
        UNIX_TIMESTAMP(p.created_at) as publishTime,
        u.avatar,
        u.nickname,
        COUNT(DISTINCT pl.id) as likeCount,
        COUNT(DISTINCT c.id) as commentCount,
        GROUP_CONCAT(pi.image_url) as imageUrls,
        -- 热度分数 = 点赞数 * 0.6 + 评论数 * 0.4 + 时间衰减因子
        (COUNT(DISTINCT pl.id) * 0.6 + 
          COUNT(DISTINCT c.id) * 0.4 +
          EXP(-0.1 * (TIMESTAMPDIFF(HOUR, p.created_at, NOW())))) as hotScore
      FROM posts p
      LEFT JOIN users u ON p.user_id = u.id
      LEFT JOIN post_likes pl ON p.id = pl.post_id
      LEFT JOIN comments c ON p.id = c.post_id
      LEFT JOIN post_images pi ON p.id = pi.post_id
      GROUP BY p.id, p.title, p.content, p.created_at, u.avatar, u.nickname
      ORDER BY hotScore DESC, p.created_at DESC
      LIMIT ? OFFSET ?
    `
    const results = await db.query(query, [limit, offset])
    const articles = results.map(article => {
      const images = article.imageUrls ? article.imageUrls.split(',') : []
      return {
        id: article.id,
        avatar: article.avatar,
        nickname: article.nickname,
        publishTime: article.publishTime,
        title: article.title,
        content: article.content, images,
        commentCount: article.commentCount,
        likeCount: article.likeCount
      }
    })
    res.json({
      code: 1,
      msg: '获取文章列表成功。',
      data: articles
    })
  } catch (error) {
    console.error('获取文章列表失败:', error)
    res.status(500).json({
      code: -1,
      msg: '获取文章列表失败。'
    })
  }
})
router.post('/api/articles', authMiddleware, async (req, res) => {
  const userId = req.userId
  const { title, content, images, tags, visibility } = req.body
  if (!title || typeof title !== 'string' || title.length > 255) {
    return res.status(400).json({ code: -1, msg: '标题必须为字符串且不超过255个字符。' })
  }
  if (!content || typeof content !== 'string') {
    return res.status(400).json({ code: -1, msg: '内容不能为空。' })
  }
  const validVisibilities = ['public', 'private', 'friends']
  if (!visibility || !validVisibilities.includes(visibility)) {
    return res.status(400).json({ code: -1, msg: '可见性参数无效。' })
  }
  if (images && !Array.isArray(images)) {
    return res.status(400).json({ code: -1, msg: 'images 必须是一个数组。' })
  }
  if (tags && !Array.isArray(tags)) {
    return res.status(400).json({ code: -1, msg: 'tags 必须是一个数组。' })
  }
  let connection
  try {
    connection = await db.beginTransaction()
    const postSql = 'INSERT INTO posts (user_id, title, content, visibility) VALUES (?, ?, ?, ?)'
    const [postResult] = await connection.execute(postSql, [userId, title, content, visibility])
    const postId = postResult.insertId
    if (images && images.length > 0) {
      for (const imageId of images) {
        const [rows] = await connection.execute('SELECT id FROM images WHERE id = ? AND creator = ?', [imageId, userId])
        if (rows.length === 0) {
          throw new Error('INVALID_IMAGE')
        }
        await connection.execute('INSERT INTO post_images (post_id, image_id) VALUES (?, ?)', [postId, imageId])
      }
    }
    if (tags && tags.length > 0) {
      for (const tagName of tags) {
        const [existingTag] = await connection.execute(
          'SELECT id FROM post_tags WHERE tag = ?',
          [tagName]
        )
        let tagId
        if (existingTag.length > 0) {
          tagId = existingTag[0].id
        } else {
          const [tagResult] = await connection.execute(
            'INSERT INTO post_tags (tag, creator) VALUES (?, ?)',
            [tagName, userId]
          )
          tagId = tagResult.insertId
        }
        await connection.execute(
          'INSERT INTO post_tag_relations (post_id, tag_id) VALUES (?, ?)',
          [postId, tagId]
        )
      }
    }
    await db.commit(connection)
    res.status(201).json({
      code: 1,
      msg: '文章发布成功。',
      data: { postId }
    })
  } catch (error) {
    if (connection) await db.rollback(connection)
    if (error.message && error.message === 'INVALID_IMAGE') {
      return res.status(400).json({
        code: -1,
        msg: '无效的图片ID。'
      })
    }
    res.status(500).json({
      code: -1,
      msg: '服务器错误，请稍后重试。'
    })
  }
})
router.put('/articles/:id', async (req, res) => {
  // TODO: 实现修改已发布的文章接口
})
export default router