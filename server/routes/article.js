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
        u.user_avatar,
        u.nick,
        COUNT(DISTINCT pl.id) as likeCount,
        COUNT(DISTINCT c.id) as commentCount,
        GROUP_CONCAT(pi.image_name) as imageNames,
        -- 热度分数 = 点赞数 * 0.6 + 评论数 * 0.4 + 时间衰减因子
        (COUNT(DISTINCT pl.id) * 0.6 + 
          COUNT(DISTINCT c.id) * 0.4 +
          EXP(-0.1 * (TIMESTAMPDIFF(HOUR, p.created_at, NOW())))) as hotScore
      FROM posts p
      LEFT JOIN users u ON p.user_id = u.id
      LEFT JOIN post_likes pl ON p.id = pl.post_id
      LEFT JOIN comments c ON p.id = c.post_id
      LEFT JOIN post_images pi ON p.id = pi.post_id
      GROUP BY p.id, p.title, p.content, p.created_at, u.user_avatar, u.nick
      ORDER BY hotScore DESC, p.created_at DESC
      LIMIT ${offset}, ${limit}
    `
    // Don't use parameterized query for this SQL statement, or it will cause an error
    const results = await db.query(query)
    const articles = results.map(article => {
      const images = article.imageNames ? article.imageNames.split(',').map(image => `/uploads/images/${image}.png`) : []
      return {
        id: article.id,
        avatar: article.user_avatar,
        nick: article.nick,
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
router.get('/user/:userId/articles', async (req, res) => {
  const userId = parseInt(req.params.userId)
  if (isNaN(userId)) {
    return res.status(400).json({ code: -1, msg: '用户ID无效。' })
  }
  const page = parseInt(req.query.page) || 1
  const limit = parseInt(req.query.limit) || 10
  const offset = (page - 1) * limit
  try {
    const query = `
      SELECT 
        p.id,
        p.title,
        p.content,
        UNIX_TIMESTAMP(p.created_at) as publishTime,
        COUNT(DISTINCT pl.id) as likeCount,
        COUNT(DISTINCT c.id) as commentCount
      FROM posts p
      LEFT JOIN post_likes pl ON p.id = pl.post_id
      LEFT JOIN comments c ON p.id = c.post_id
      WHERE p.user_id = ?
      GROUP BY p.id, p.title, p.content, p.created_at
      ORDER BY p.created_at DESC
      LIMIT ${limit} OFFSET ${offset}
    `
    const articles = await db.query(query, [userId])
    const totalResult = await db.getOne(
      `SELECT COUNT(*) as total FROM posts WHERE user_id = ?`,
      [userId]
    )
    res.json({
      code: 1,
      data: {
        list: articles.map(article => ({
          id: article.id,
          title: article.title,
          content: article.content,
          publishTime: article.publishTime,
          likeCount: article.likeCount || 0,
          commentCount: article.commentCount || 0
        })),
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
router.get('/articles/:id', async (req, res) => {
  const articleId = parseInt(req.params.id)
  if (isNaN(articleId)) {
    return res.status(400).json({ code: -1, msg: '文章 ID 无效。' })
  }
  try {
    const query = `
      SELECT 
        p.id,
        p.title,
        p.content,
        p.visibility,
        UNIX_TIMESTAMP(p.created_at) as publishTime,
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
    /*
    // 可选：可见性检查（如果文章是 private 且当前未登录用户不是作者，则拒绝访问）
    // 如果需要根据当前登录用户判断，可以传入 req.userId；这里先简单返回全部，但建议加上
    // 由于你没有在路由层加 authMiddleware，所以这个接口是公开的。如果文章是 private，应该返回 403。
    // 根据你的业务决定：这里我加上检查，需要前端携带 token 时才能看到自己的私有文章。
    // 如果希望完全公开（所有文章任何人可见），则删除下面这段。
    if (article.visibility === 'private') {
      // 检查是否有登录用户且是作者
      const userId = req.userId // 注意：这个接口没有 authMiddleware，所以 req.userId 可能不存在。如果需要鉴权，可以添加 authMiddleware 或者手动解析 token。
      if (!userId || userId !== article.user_id) {
        return res.status(403).json({ code: -1, msg: '无权查看此文章。' })
      }
    }
    // 对于 'friends' 可见性，也可以在这里做额外判断，按需添加。
    */
    // 提示：可用verifyToken函数来验证token是否有效
    const imageQuery = `
      SELECT i.name
      FROM post_images pi
      JOIN images i ON pi.image_name = i.name
      WHERE pi.post_id = ?
    `
    const imageRows = await db.query(imageQuery, [articleId])
    const images = imageRows.map(row => `/uploads/images/${row.name}.png`)
    const tagQuery = `
      SELECT pt.tag
      FROM post_tag_relations ptr
      JOIN post_tags pt ON ptr.tag_id = pt.id
      WHERE ptr.post_id = ?
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
        visibility: article.visibility,
        publishTime: article.publishTime,
        likeCount: article.likeCount,
        images, tags
      }
    })
  } catch (error) {
    console.error('获取文章详情失败:', error)
    res.status(500).json({ code: -1, msg: '服务器错误，请稍后重试。' })
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
      for (const imageName of images) {
        const [rows] = await connection.execute(
          'SELECT 1 FROM user_images WHERE image_name = ? AND user = ?',
          [imageName, userId]
        )
        if (rows.length === 0) {
          throw new Error('INVALID_IMAGE')
        }
        await connection.execute('INSERT INTO post_images (post_id, image_name) VALUES (?, ?)', [postId, imageName])
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
  const validVisibilities = ['public', 'private', 'friends']
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
      for (const imageName of images) {
        const imgRows = await connection.execute(
          'SELECT image_name FROM user_images WHERE image_name = ? AND user = ?',
          [imageName, userId]
        )
        if (imgRows.length === 0) {
          throw new Error('INVALID_IMAGE')
        }
        await connection.execute(
          'INSERT INTO post_images (post_id, image_name) VALUES (?, ?)',
          [articleId, imageName]
        )
      }
    }
    await connection.execute('DELETE FROM post_tag_relations WHERE post_id = ?', [articleId])
    if (tags && tags.length > 0) {
      for (const tagName of tags) {
        let tagId
        const existingTag = await connection.execute(
          'SELECT id FROM post_tags WHERE tag = ?',
          [tagName]
        )
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
          'INSERT INTO post_tag_relations (post_id, tag_id) VALUES (?, ?)',
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
    res.status(500).json({ code: -1, msg: '服务器错误，请稍后重试。' })
  }
})
export default router