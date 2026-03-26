import express from 'express'
import { bin2hex } from '../utils.js'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
import bcrypt from 'bcrypt'
const router = express.Router()
router.get('/self', authMiddleware, async (req, res) => {
  try {
    const userId = req.userId
    const userBasic = await db.getOne('SELECT id, nick, user, user_avatar, is_admin FROM users WHERE id = ?', [userId])
    if (!userBasic) {
      return res.status(401).json({
        code: 0,
        msg: '用户不存在或token无效，请重新登录。'
      })
    }
    const userDetail = await db.getOne('SELECT id, user, nick, UNIX_TIMESTAMP(created_at) AS created_at, user_avatar, gender, DATE_FORMAT(birth, "%Y-%m-%d") AS birth, bio, background, auto_theme, theme_color FROM users WHERE id = ?', [userId])
    const unreadResult = await db.getOne('SELECT COUNT(*) AS unread_count FROM system_messages WHERE target = ? AND is_read = 0', [userId])
    const friendRequestResult = await db.getOne('SELECT COUNT(*) AS request_count FROM friend_requests WHERE target = ?', [userId])
    const friendUnreadResult = await db.getOne(
      `SELECT COALESCE(SUM(unread_count), 0) AS total_unread_count
      FROM (
        SELECT c.session,
          COUNT(CASE WHEN c.id > COALESCE(mrs.max_id, 0) AND c.sender != ? THEN 1 END) AS unread_count
        FROM (
          SELECT id FROM friendships WHERE source = ? OR target = ?
          UNION
          SELECT \`group\` AS id FROM group_members WHERE user = ?
        ) AS user_sessions
        INNER JOIN chats c ON user_sessions.id = c.session
        LEFT JOIN message_read_status mrs ON user_sessions.id = mrs.session_id AND mrs.user_id = ?
        GROUP BY c.session
      ) AS session_unreads`,
      [userId, userId, userId, userId, userId]
    )
    const sessionResult = await db.getOne('SELECT UNIX_TIMESTAMP(expires) AS expires FROM user_session WHERE user = ?', [userId])
    const unreadCount = unreadResult ? Number(unreadResult.unread_count) : 0
    const friendRequestCount = friendRequestResult ? Number(friendRequestResult.request_count) : 0
    const friendUnreadCount = friendUnreadResult ? Number(friendUnreadResult.total_unread_count) : 0
    let themeColor = userDetail.theme_color
    if (themeColor !== null) {
      themeColor = '#' + bin2hex(themeColor).toUpperCase()
    }
    let birth = userDetail.birth
    if (birth == null) {
      birth = ''
    }
    const data = {
      id: userBasic.id,
      nick: userBasic.nick,
      user: userBasic.user,
      avatar: userBasic.user_avatar,
      isAdmin: !!userBasic.is_admin,
      systemMessageUnreadCount: unreadCount,
      friendRequestCount, friendUnreadCount,
      token_expires: sessionResult.expires,
      created_at: userDetail.created_at,
      gender: userDetail.gender,
      birth, bio: userDetail.bio,
      background: userDetail.background,
      auto_theme: !!userDetail.auto_theme,
      theme_color: themeColor
    }
    res.json({
      code: 1,
      msg: '用户信息获取成功。',
      data: data
    })
  } catch (error) {
    res.status(500).json({
      code: -1,
      msg: '数据库错误。'
    })
  }
})
router.patch('/self', authMiddleware, async (req, res) => {
  try {
    const userId = req.userId
    const { nick, gender, birth, bio, auto_theme, theme_color, password } = req.body
    const updates = {}
    if (nick !== undefined) {
      if (typeof nick !== 'string') {
        return res.status(400).json({
          code: -1,
          msg: '昵称必须为字符串。'
        })
      }
      updates.nick = nick
    }
    if (gender !== undefined) {
      if (!['M', 'W', 'N'].includes(gender)) {
        return res.status(400).json({
          code: -1,
          msg: '性别参数错误。'
        })
      }
      updates.gender = gender
    }
    if (birth !== undefined) {
      if (birth !== '') {
        const dateRegex = /^\d{4}-\d{2}-\d{2}$/
        if (!dateRegex.test(birth)) {
          return res.status(400).json({
            code: -1,
            msg: '生日格式错误。'
          })
        }
        const date = new Date(birth)
        if (isNaN(date.getTime())) {
          return res.status(400).json({
            code: -1,
            msg: '生日日期无效。'
          })
        }
        else if (date > new Date()) {
          return res.status(400).json({
            code: -1,
            msg: '生日日期不能在未来。'
          })
        }
      }
      updates.birth = birth || ''
    }
    if (bio !== undefined) {
      if (typeof bio !== 'string') {
        return res.status(400).json({
          code: -1,
          msg: '个人简介信息必须是字符串。'
        })
      }
      updates.bio = bio
    }
    if (auto_theme !== undefined) {
      if (typeof auto_theme !== 'boolean') {
        return res.status(400).json({
          code: -1,
          msg: 'auto_theme: 必须是布尔值。'
        })
      }
      updates.auto_theme = auto_theme
    }
    if (theme_color !== undefined) {
      let currentAutoTheme
      if (auto_theme !== undefined) {
        currentAutoTheme = auto_theme
      } else {
        const user = await db.getOne('SELECT auto_theme FROM users WHERE id = ?', [userId])
        currentAutoTheme = user.auto_theme
      }
      if (!currentAutoTheme) {
        const colorRegex = /^#[0-9A-Fa-f]{6}$/
        if (!colorRegex.test(theme_color)) {
          return res.status(400).json({
            code: -1,
            msg: '主题色格式错误。'
          })
        }
        const hex = theme_color.slice(1)
        const binaryColor = Buffer.from(hex, 'hex')
        updates.theme_color = binaryColor
      }
    }
    if (password !== undefined) {
      if (typeof password !== 'string') {
        return res.status(400).json({
          code: -1,
          msg: '密码必须是字符串。'
        })
      }
      if (password.length < 8 || password.length > 32) {
        return res.status(400).json({
          code: -1,
          msg: '密码长度必须在8到32个字符之间。'
        })
      } else if (/[^\x20-x7E]/.test(password)) {
        return res.status(400).json({
          code: -1,
          msg: '密码包含非法字符。'
        })
      }
      const passwordHash = await bcrypt.hash(password, 10)
      updates.password = passwordHash
    }
    if (Object.keys(updates).length === 0) {
      return res.status(200).json({
        code: 1,
        msg: '未提供任何需要更新的字段。'
      })
    }
    const setClause = Object.keys(updates).map(key => `${key} = ?`).join(', ')
    const values = Object.values(updates)
    values.push(userId)
    await db.query(`UPDATE users SET ${setClause} WHERE id = ?`, values)
    res.json({
      code: 1,
      msg: '用户信息更新成功。'
    })
  } catch (error) {
    console.error(error)
    res.status(500).json({
      code: -1,
      msg: '数据库错误。'
    })
  }
})
router.get('/user/:id', async (req, res) => {
  try {
    const targetIdStr = req.params.id
    if (!targetIdStr) {
      return res.status(400).json({
        code: -1,
        msg: '缺少必要的参数。'
      })
    }
    if (!/^\d+$/.test(targetIdStr)) {
      return res.status(400).json({
        code: -1,
        msg: '参数错误。'
      })
    }
    const targetId = parseInt(targetIdStr)
    const rawCurrentUserId = req.cookies.t
    let currentUserId = null
    if (rawCurrentUserId) {
      const sessionRow = await db.getOne('SELECT user FROM user_session WHERE token = ?', [rawCurrentUserId])
      if (sessionRow) {
        currentUserId = sessionRow.user
      }
    }
    const userBasic = await db.getOne(
      `SELECT id, user, nick, user_avatar AS avatar, gender,
              DATE_FORMAT(birth, '%Y-%m-%d') AS birth, bio,
              background, theme_color, auto_theme
       FROM users WHERE id = ?`,
      [targetId]
    )
    if (!userBasic) {
      return res.status(404).json({
        code: -1,
        msg: '用户不存在。'
      })
    }
    let themeColor = null
    if (userBasic.theme_color) {
      themeColor = '#' + bin2hex(userBasic.theme_color).toUpperCase()
    }
    userBasic.auto_theme = !!userBasic.auto_theme
    let remark = null
    let friend_status = 'false'
    let follow_status = 0
    if (currentUserId) {
      const remarkRow = await db.getOne(
        'SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?',
        [currentUserId, targetId]
      )
      remark = remarkRow ? remarkRow.remark : null
      if (currentUserId === targetId) {
        friend_status = 'self'
      } else {
        const friendRow = await db.getOne(
          'SELECT 1 FROM friendships WHERE (source = ? AND target = ?) OR (source = ? AND target = ?) LIMIT 1',
          [currentUserId, targetId, targetId, currentUserId]
        )
        if (friendRow) {
          friend_status = 'true'
        } else {
          const requestRow = await db.getOne(
            'SELECT source, target FROM friend_requests WHERE (source = ? AND target = ?) OR (source = ? AND target = ?) LIMIT 1',
            [currentUserId, targetId, targetId, currentUserId]
          )
          if (requestRow) {
            if (requestRow.source === currentUserId && requestRow.target === targetId) {
              friend_status = 'pending'
            } else {
              friend_status = 'requested'
            }
          } else {
            friend_status = 'false'
          }
        }
      }
      const followRow = await db.getOne(
        `SELECT
           EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ?) AS is_following,
           EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ?) AS is_followed_by`,
        [currentUserId, targetId, targetId, currentUserId]
      )
      if (followRow.is_following && followRow.is_followed_by) {
        follow_status = 3
      } else if (followRow.is_following) {
        follow_status = 1
      } else if (followRow.is_followed_by) {
        follow_status = 2
      } else {
        follow_status = 0
      }
    }
    const userData = {
      ...userBasic,
      remark,
      background: userBasic.background,
      theme_color: themeColor,
      friend_status, follow_status
    }
    res.json({
      code: 1,
      msg: '用户信息获取成功。',
      data: userData
    })
  } catch (error) {
    console.error(error)
    res.status(500).json({
      code: -1,
      msg: '数据库错误。'
    })
  }
})
export default router