import express from 'express'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
const router = express.Router()
router.use(authMiddleware)
router.post('/user/:userId/follow', async (req, res) => {
  try {
    const currentUserId = req.userId
    const targetUserId = parseInt(req.params.userId)
    if (!targetUserId) {
      return res.status(400).json({ code: -1, msg: '缺少目标用户ID。' })
    }
    if (currentUserId === targetUserId) {
      return res.status(400).json({ code: -1, msg: '不能关注自己。' })
    }
    const userExists = await db.getOne('SELECT id FROM users WHERE id = ?', [targetUserId])
    if (!userExists) {
      return res.status(404).json({ code: -1, msg: '用户不存在。' })
    }
    const existed = await db.getOne(
      'SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?',
      [currentUserId, targetUserId]
    )
    if (existed) {
      return res.status(409).json({ code: -1, msg: '已经关注过该用户。' })
    }
    await db.insert(
      'INSERT INTO follows (follower_id, following_id) VALUES (?, ?)',
      [currentUserId, targetUserId]
    )
    res.json({ code: 1, msg: '关注成功。' })
  } catch (error) {
    console.error('关注失败:', error)
    res.status(500).json({ code: -1, msg: '服务器内部错误。' })
  }
})
router.delete('/user/:userId/follow', async (req, res) => {
  try {
    const currentUserId = req.userId
    const targetUserId = parseInt(req.params.userId)
    if (!targetUserId) {
      return res.status(400).json({ code: -1, msg: '缺少目标用户ID。' })
    }
    if (currentUserId === targetUserId) {
      return res.status(400).json({ code: -1, msg: '请求无效。' })
    }
    const result = await db.query(
      'DELETE FROM follows WHERE follower_id = ? AND following_id = ?',
      [currentUserId, targetUserId]
    )
    if (result.affectedRows === 0) {
      return res.status(404).json({ code: -1, msg: '尚未关注该用户。' })
    }
    res.json({ code: 1, msg: '取消关注成功。' })
  } catch (error) {
    console.error('取消失败:', error)
    res.status(500).json({ code: -1, msg: '服务器内部错误。' })
  }
})
router.get('/self/followers', async (req, res) => {
  try {
    const currentUserId = req.user.id
    const page = parseInt(req.query.page) || 1
    const limit = parseInt(req.query.limit) || 30
    const offset = (page - 1) * limit
    const list = await db.query(
      `SELECT u.id, u.nickname, u.avatar, u.bio,
              (SELECT COUNT(*) > 0 FROM follows f2 WHERE f2.follower_id = ? AND f2.following_id = u.id) as isFollowing
       FROM follows f
       JOIN users u ON f.follower_id = u.id
       WHERE f.following_id = ?
       LIMIT ${limit} OFFSET ${offset}`,
      [currentUserId, currentUserId]
    )
    const totalResult = await db.getOne(
      'SELECT COUNT(*) as total FROM follows WHERE following_id = ?',
      [currentUserId]
    )
    res.json({
      code: 1,
      msg: '获取粉丝列表成功。',
      data: {
        list,
        total: totalResult,
        page: parseInt(page),
        limit: parseInt(limit)
      }
    })
  } catch (error) {
    console.error('获取粉丝列表失败:', error)
    res.status(500).json({ code: -1, message: '服务器内部错误。' })
  }
})
router.get('/self/following', async (req, res) => {
  try {
    const currentUserId = req.user.id
    const page = parseInt(req.query.page) || 1
    const limit = parseInt(req.query.limit) || 30
    const offset = (page - 1) * limit
    const list = await db.query(
      `SELECT u.id, u.nickname, u.avatar, u.bio, true as isFollowing
       FROM follows f
       JOIN users u ON f.following_id = u.id
       WHERE f.follower_id = ?
       LIMIT ${limit} OFFSET ${offset}`,
      [currentUserId]
    )
    const totalResult = await db.getOne(
      'SELECT COUNT(*) as total FROM follows WHERE follower_id = ?',
      [currentUserId]
    )
    res.json({
      code: 1,
      msg: '获取关注列表成功。',
      data: {
        list,
        total: totalResult.total,
        page: parseInt(page),
        limit: parseInt(limit)
      }
    })
  } catch (error) {
    console.error('获取关注列表失败:', error)
    res.status(500).json({ code: -1, msg: '服务器内部错误。' })
  }
})
export default router