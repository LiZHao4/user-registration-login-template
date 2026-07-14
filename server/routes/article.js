import express from 'express'
import db from '../config.js'
import { authMiddleware, verifyToken } from '../middlewares/auth.js'
import { pagination } from '../middlewares/pagination.js'
import { escapeLikeKeyword } from '../utils.js'
const router = express.Router()
router.get('/articles', pagination(), async (req, res) => {
  try {
    const { limit, offset } = req.pagination
    const userId = await verifyToken(req.cookies.t)
    const query = `
      SELECT 
        p.id,
        p.title,
        p.content,
        UNIX_TIMESTAMP(p.updated_at) as updateTime,
        u.user_avatar,
        u.nick,
        COUNT(DISTINCT pl.id) as likeCount,
        CASE WHEN ? IS NULL THEN 0 ELSE (
          SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?
        ) END AS is_liked,
        COUNT(DISTINCT c.id) as commentCount,
        -- 热度分数 = 点赞数 * 0.6 + 评论数 * 0.4 + 时间衰减因子
        (COUNT(DISTINCT pl.id) * 0.6 + 
          COUNT(DISTINCT c.id) * 0.4 +
          EXP(-0.1 * (TIMESTAMPDIFF(HOUR, p.created_at, NOW())))) as hotScore
      FROM posts p
      LEFT JOIN users u ON p.user_id = u.id
      LEFT JOIN post_likes pl ON p.id = pl.post_id
      LEFT JOIN comments c ON p.id = c.post_id
      WHERE p.visibility = 'public'
        OR ? IS NOT NULL
        AND p.visibility = 'mutuals'
        AND (
          p.user_id = ?
          OR EXISTS (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = p.user_id)
          AND EXISTS (SELECT 1 FROM follows WHERE follower_id = p.user_id AND following_id = ?)
        )
      GROUP BY p.id, p.title, p.content, p.created_at, u.user_avatar, u.nick
      ORDER BY hotScore DESC, p.created_at DESC
      LIMIT ${offset}, ${limit}
    `
    // DO NOT use parameterized placeholder for LIMIT and OFFSET — it will cause an error.
    // Only numeric values are allowed here.
    const results = await db.query(query, [userId, userId, userId, userId, userId, userId])
    const articlePromises = results.map(async article => {
      const imageRecords = await db.query(
        'SELECT image_name FROM post_images WHERE post_id = ? ORDER BY position',
        [article.id]
      )
      const images = imageRecords.map(row => `/uploads/images/${row.image_name}.png`)
      return {
        id: article.id,
        avatar: article.user_avatar,
        nick: article.nick,
        publishTime: article.publishTime,
        updateTime: article.updateTime,
        title: article.title,
        content: article.content, images,
        commentCount: article.commentCount,
        likeCount: article.likeCount,
        isLiked: !!article.is_liked
      }
    })
    const articles = await Promise.all(articlePromises)
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
router.get('/user/:userId/articles', pagination(10), async (req, res) => {
  const targetUserId = parseInt(req.params.userId)
  if (isNaN(targetUserId)) {
    return res.status(400).json({ code: -1, msg: '用户ID无效。' })
  }
  const { page, limit, offset } = req.pagination
  const currentUserId = await verifyToken(req.cookies.t)
  try {
    const query = `
      SELECT 
        p.id,
        p.title,
        p.content,
        UNIX_TIMESTAMP(p.updated_at) as updateTime
      FROM posts p
      LEFT JOIN post_likes pl ON p.id = pl.post_id
      LEFT JOIN comments c ON p.id = c.post_id
      WHERE p.user_id = ?
        AND (
          p.visibility = 'public'
          OR p.visibility = 'mutuals'
          AND EXISTS (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = p.user_id)
          AND EXISTS (SELECT 1 FROM follows WHERE follower_id = p.user_id AND following_id = ?)
          OR p.visibility = 'private' 
          AND p.user_id = ?
        )
      GROUP BY p.id, p.title, p.content, p.created_at, p.updated_at, p.visibility
      ORDER BY p.created_at DESC
      LIMIT ${limit} OFFSET ${offset}
    `
    const articles = await db.query(query, [targetUserId, currentUserId, currentUserId, currentUserId])
    const totalResult = await db.getOne('SELECT COUNT(*) as total FROM posts WHERE user_id = ?', [targetUserId])
    res.json({
      code: 1,
      msg: '获取用户文章列表成功。',
      data: articles,
      pagination: {
        total: totalResult.total,
        page, limit
      }
    })
  } catch (error) {
    console.error('获取用户文章列表失败:', error)
    res.status(500).json({
      code: -1,
      msg: '获取用户文章列表失败。'
    })
  }
})
router.get('/self/articles', authMiddleware, pagination(10), async (req, res) => {
  const userId = req.userId
  const { page, limit, offset } = req.pagination
  const { keyword } = req.query
  try {
    const query = `
      SELECT 
        p.id,
        p.title,
        p.content,
        p.visibility,
        UNIX_TIMESTAMP(p.created_at) as publishTime,
        UNIX_TIMESTAMP(p.updated_at) as updateTime,
        u.user_avatar,
        u.nick,
        COUNT(DISTINCT pl.id) as likeCount,
        COUNT(DISTINCT c.id) as commentCount
      FROM posts p
      LEFT JOIN users u ON p.user_id = u.id
      LEFT JOIN post_likes pl ON p.id = pl.post_id
      LEFT JOIN comments c ON p.id = c.post_id
      WHERE p.user_id = ?
      ${keyword ? 'AND p.title LIKE ?' : ''}
      GROUP BY p.id, p.title, p.content, p.created_at, p.updated_at, p.visibility, u.user_avatar, u.nick
      ORDER BY p.updated_at DESC
      LIMIT ${limit} OFFSET ${offset}
    `
    const params = [userId]
    if (keyword) {
      params.push(`%${escapeLikeKeyword(keyword)}%`)
    }
    const results = await db.query(query, params)
    const articlePromises = results.map(async article => {
      const imageRecords = await db.query(
        'SELECT image_name FROM post_images WHERE post_id = ? ORDER BY position',
        [article.id]
      )
      const images = imageRecords.map(row => `/uploads/images/${row.image_name}.png`)
      return {
        id: article.id,
        avatar: article.user_avatar,
        nick: article.nick,
        publishTime: article.publishTime,
        updateTime: article.updateTime,
        title: article.title,
        content: article.content,
        images,
        visibility: article.visibility,
        commentCount: article.commentCount,
        likeCount: article.likeCount
      }
    })
    const articles = await Promise.all(articlePromises)
    const totalResult = await db.getOne(
      `SELECT COUNT(*) as total FROM posts WHERE user_id = ? ${keyword ? 'AND title LIKE ?' : ''}`,
      params
    )
    res.json({
      code: 1,
      msg: '获取我的文章列表成功。',
      data: articles,
      pagination: {
        total: totalResult.total,
        page, limit
      }
    })
  } catch (error) {
    console.error('获取我的文章列表失败:', error)
    res.status(500).json({
      code: -1,
      msg: '获取我的文章列表失败。'
    })
  }
})
router.get('/articles/:id', async (req, res) => {
  const articleId = parseInt(req.params.id)
  if (isNaN(articleId)) {
    return res.status(400).json({ code: -1, msg: '文章ID无效。' })
  }
  try {
    const currentUserId = await verifyToken(req.cookies.t)
    const query = `
      SELECT 
        p.id,
        p.title,
        p.content,
        p.visibility,
        UNIX_TIMESTAMP(p.created_at) as publishTime,
        UNIX_TIMESTAMP(p.updated_at) as updateTime,
        u.id as user_id,
        u.nick,
        u.user_avatar,
        COUNT(DISTINCT pl.id) as likeCount
      FROM posts p
      LEFT JOIN users u ON p.user_id = u.id
      LEFT JOIN post_likes pl ON p.id = pl.post_id
      WHERE p.id = ?
      GROUP BY p.id
    `
    const rows = await db.query(query, [articleId])
    if (rows.length === 0) {
      return res.status(404).json({ code: -1, msg: '文章不存在。' })
    }
    const article = rows[0]
    const authorId = article.user_id
    let allowed = false
    const visibility = article.visibility
    if (visibility === 'public') {
      allowed = true
    } else if (visibility === 'private') {
      if (currentUserId && currentUserId === authorId) {
        allowed = true
      }
    } else if (visibility === 'mutuals') {
      if (currentUserId) {
        const [follow, followBack] = await Promise.all([
          db.query('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?', [currentUserId, authorId]),
          db.query('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?', [authorId, currentUserId])
        ])
        if (follow.length > 0 && followBack.length > 0) {
          allowed = true
        }
      }
    }
    if (!allowed) {
      return res.status(403).json({ code: -1, msg: '无权查看此文章。' })
    }
    let isFollowing = 'false'
    if (currentUserId) {
      if (currentUserId === authorId) {
        isFollowing = 'self'
      } else {
        const result = await db.query(
          'SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?',
          [currentUserId, authorId]
        )
        if (result.length > 0) isFollowing = 'true'
      }
    }
    const imageQuery = 'SELECT image_name FROM post_images WHERE post_id = ? ORDER BY position'
    const imageRows = await db.query(imageQuery, [articleId])
    const images = imageRows.map(row => `/uploads/images/${row.name}.png`)
    const tagQuery = `
      SELECT pt.tag
      FROM post_tag_relations ptr
      JOIN post_tags pt ON ptr.tag_id = pt.id
      WHERE ptr.post_id = ?
      ORDER BY ptr.position
    `
    const tagRows = await db.query(tagQuery, [articleId])
    const tags = tagRows.map(row => row.tag)
    res.json({
      code: 1,
      msg: '获取文章详情成功。',
      data: {
        id: article.id,
        user_id: article.user_id,
        user_nick: article.nick,
        user_avatar: article.user_avatar,
        title: article.title,
        content: article.content,
        publishTime: article.publishTime,
        updateTime: article.updateTime,
        likeCount: article.likeCount,
        visibility: article.visibility,
        images, tags, isFollowing
      }
    })
  } catch (error) {
    console.error('获取文章详情失败:', error)
    res.status(500).json({ code: -1, msg: '获取文章详情失败。' })
  }
})
router.post('/articles', authMiddleware, async (req, res) => {
  const userId = req.userId
  const { title, content, images, tags, visibility } = req.body
  if (!title || typeof title !== 'string' || title.length > 100) {
    return res.status(400).json({ code: -1, msg: '标题必须为字符串且不超过100个字符。' })
  }
  if (!content || typeof content !== 'string') {
    return res.status(400).json({ code: -1, msg: '内容不能为空。' })
  }
  const validVisibilities = ['public', 'private', 'mutuals']
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
      for (let i = 0; i < images.length; i++) {
        const imageName = images[i]
        const [rows] = await connection.execute(
          'SELECT 1 FROM user_images WHERE image_name = ? AND user = ?',
          [imageName, userId]
        )
        if (rows.length === 0) {
          throw new Error('INVALID_IMAGE')
        }
        await connection.execute(
          'INSERT INTO post_images (post_id, position, image_name) VALUES (?, ?, ?)',
          [postId, i + 1, imageName]
        )
      }
    }
    if (tags && tags.length > 0) {
      for (let i = 0; i < tags.length; i++) {
        const tagName = tags[i]
        const existingTag = await connection.execute('SELECT id FROM post_tags WHERE tag = ?', [tagName])
        let tagId
        if (existingTag.length > 0) {
          tagId = existingTag[0].id
        } else {
          const tagResult = await connection.execute(
            'INSERT INTO post_tags (tag, creator) VALUES (?, ?)',
            [tagName, userId]
          )
          tagId = tagResult.insertId
        }
        await connection.execute(
          'INSERT INTO post_tag_relations (post_id, position, tag_id) VALUES (?, ?, ?)',
          [postId, i + 1, tagId]
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
      msg: '文章发布失败。'
    })
  }
})
router.put('/articles/:id', authMiddleware, async (req, res) => {
  const articleId = parseInt(req.params.id)
  if (isNaN(articleId)) {
    return res.status(400).json({ code: -1, msg: '文章 ID 无效。' })
  }
  const userId = req.userId
  const { title, content, visibility, images, tags } = req.body
  if (!title || typeof title !== 'string' || title.length > 100) {
    return res.status(400).json({ code: -1, msg: '标题必须为字符串且不超过100个字符。' })
  }
  if (!content || typeof content !== 'string') {
    return res.status(400).json({ code: -1, msg: '内容不能为空。' })
  }
  const validVisibilities = ['public', 'private', 'mutuals']
  if (!visibility || !validVisibilities.includes(visibility)) {
    return res.status(400).json({ code: -1, msg: '可见性参数无效。' })
  }
  if (images !== undefined && !Array.isArray(images)) {
    return res.status(400).json({ code: -1, msg: 'images 必须是一个数组。' })
  }
  if (tags !== undefined && !Array.isArray(tags)) {
    return res.status(400).json({ code: -1, msg: 'tags 必须是一个数组。' })
  }
  let connection
  try {
    const postRows = await db.query('SELECT user_id FROM posts WHERE id = ?', [articleId])
    if (postRows.length === 0) {
      return res.status(404).json({ code: -1, msg: '文章不存在。' })
    }
    if (postRows[0].user_id !== userId) {
      return res.status(403).json({ code: -1, msg: '无权修改此文章。' })
    }
    connection = await db.beginTransaction()
    await connection.execute(
      'UPDATE posts SET title = ?, content = ?, visibility = ? WHERE id = ?',
      [title, content, visibility, articleId]
    )
    await connection.execute('DELETE FROM post_images WHERE post_id = ?', [articleId])
    if (images && images.length > 0) {
      for (let i = 0; i < images.length; i++) {
        const imageName = images[i]
        const imgRows = await connection.execute(
          'SELECT image_name FROM user_images WHERE image_name = ? AND user = ?',
          [imageName, userId]
        )
        if (imgRows.length === 0) {
          throw new Error('INVALID_IMAGE')
        }
        await connection.execute(
          'INSERT INTO post_images (post_id, position, image_name) VALUES (?, ?, ?)',
          [articleId, i + 1, imageName]
        )
      }
    }
    await connection.execute('DELETE FROM post_tag_relations WHERE post_id = ?', [articleId])
    if (tags && tags.length > 0) {
      for (let i = 0; i < tags.length; i++) {
        const tagName = tags[i]
        let tagId
        const existingTag = await connection.execute('SELECT id FROM post_tags WHERE tag = ?', [tagName])
        if (existingTag.length > 0) {
          tagId = existingTag[0].id
        } else {
          const tagResult = await connection.execute(
            'INSERT INTO post_tags (tag, creator) VALUES (?, ?)',
            [tagName, userId]
          )
          tagId = tagResult.insertId
        }
        await connection.execute(
          'INSERT INTO post_tag_relations (post_id, tag_id) VALUES (?, ?, ?)',
          [articleId, tagId]
        )
      }
    }
    await db.commit(connection)
    res.json({ code: 1, msg: '文章修改成功。' })
  } catch (error) {
    if (connection) await db.rollback(connection)
    if (error.message === 'INVALID_IMAGE') {
      return res.status(400).json({ code: -1, msg: '无效的图片ID。' })
    }
    console.error('修改文章失败:', error)
    res.status(500).json({ code: -1, msg: '修改文章失败。' })
  }
})
router.delete('/articles/:id', authMiddleware, async (req, res) => {
  const articleId = parseInt(req.params.id)
  if (isNaN(articleId)) {
    return res.status(400).json({ code: -1, msg: '文章ID无效。' })
  }
  const userId = req.userId
  let connection
  try {
    const post = await db.getOne('SELECT user_id FROM posts WHERE id = ?', [articleId])
    if (!post) {
      return res.status(404).json({ code: -1, msg: '文章不存在。' })
    }
    if (post.user_id !== userId) {
      return res.status(403).json({ code: -1, msg: '无权删除此文章。' })
    }
    connection = await db.beginTransaction()
    await connection.execute('DELETE FROM post_images WHERE post_id = ?', [articleId])
    await connection.execute('DELETE FROM post_tag_relations WHERE post_id = ?', [articleId])
    await connection.execute('DELETE FROM post_likes WHERE post_id = ?', [articleId])
    await connection.execute('DELETE FROM comments WHERE post_id = ?', [articleId])
    await connection.execute('DELETE FROM posts WHERE id = ?', [articleId])
    await db.commit(connection)
    res.json({
      code: 1,
      msg: '文章删除成功。'
    })
  } catch (error) {
    if (connection) await db.rollback(connection)
    console.error('文章删除失败:', error)
    res.status(500).json({
      code: -1,
      msg: '文章删除失败。'
    })
  }
})
export default router