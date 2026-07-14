import express from 'express'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
import { pagination } from '../middlewares/pagination.js'
const router = express.Router()
router.post('/user/:userId/follow', authMiddleware, async (req, res) => {
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
router.delete('/user/:userId/follow', authMiddleware, async (req, res) => {
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
router.get('/self/followers', authMiddleware, pagination(), async (req, res) => {
  try {
    const currentUserId = req.userId
    const { page, limit, offset } = req.pagination
    const list = await db.query(
      `SELECT u.id, u.nick, u.user_avatar AS avatar, u.bio,
              (SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = u.id) AS remark,
              (SELECT COUNT(*) > 0 FROM follows f2 WHERE f2.follower_id = ? AND f2.following_id = u.id) as isFollowing
       FROM follows f
       JOIN users u ON f.follower_id = u.id
       WHERE f.following_id = ?
       LIMIT ${limit} OFFSET ${offset}`,
      [currentUserId, currentUserId, currentUserId]
    )
    const totalResult = await db.getOne(
      'SELECT COUNT(*) as total FROM follows WHERE following_id = ?',
      [currentUserId]
    )
    res.json({
      code: 1,
      msg: '获取粉丝列表成功。',
      data: list.map(item => ({
        user_id: item.id,
        nick: item.nick,
        remark: item.remark,
        avatar: item.avatar,
        bio: item.bio,
        follow_status: item.isFollowing ? 3 : 2
      })),
      pagination: {
        total: totalResult.total,
        page: parseInt(page),
        limit: parseInt(limit)
      }
    })
  } catch (error) {
    console.error('获取粉丝列表失败:', error)
    res.status(500).json({ code: -1, message: '服务器内部错误。' })
  }
})
router.get('/self/followings', authMiddleware, pagination(), async (req, res) => {
  try {
    const currentUserId = req.userId
    const { page, limit, offset } = req.pagination
    const list = await db.query(
      `SELECT u.id, u.nick, u.user_avatar AS avatar, u.bio,
              (SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = u.id) AS remark,
              (SELECT COUNT(*) > 0 FROM follows f2 
               WHERE f2.follower_id = u.id AND f2.following_id = ?) AS isFollowedByThem
       FROM follows f
       JOIN users u ON f.following_id = u.id
       WHERE f.follower_id = ?
       LIMIT ${limit} OFFSET ${offset}`,
      [currentUserId, currentUserId, currentUserId]
    )
    const totalResult = await db.getOne(
      'SELECT COUNT(*) as total FROM follows WHERE follower_id = ?',
      [currentUserId]
    )
    res.json({
      code: 1,
      msg: '获取关注列表成功。',
      data: list.map(item => ({
        user_id: item.id,
        nick: item.nick,
        remark: item.remark,
        avatar: item.avatar,
        bio: item.bio,
        follow_status: item.isFollowedByThem ? 3 : 1
      })),
      pagination: {
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