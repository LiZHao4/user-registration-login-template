import express from 'express'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
import { sendToUser } from '../socket.js'
const router = express.Router()
router.use(authMiddleware)
router.post('/send', async (req, res) => {
  try {
    const { target, msg } = req.body
    const trimmedMsg = msg.trim()
    if (trimmedMsg === '') {
      return res.status(400).json({
        code: -1,
        msg: '参数不合法。'
      })
    }
    const userId = req.userId
    const targetId = parseInt(target)
    const validateResult = await db.getOne('SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?) AS validate', [targetId, userId, userId, targetId, userId])
    if (!validateResult || validateResult.validate === 0) {
      return res.status(403).json({
        code: -1,
        msg: '您没有在该会话上发送消息的权限。'
      })
    }
    const result = await db.query('INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 1)', [targetId, trimmedMsg, userId])
    try {
      const friendship = await db.getOne('SELECT source, target FROM friendships WHERE id = ?', [targetId])
      const sender = await db.getOne('SELECT nick, avatar FROM users WHERE id = ?', [userId])
      const senderNick = sender.nick
      const senderAvatar = sender.avatar
      const remarkRow = await db.getOne('SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?', [receiverId, userId])
      const remark = remarkRow.remark
      const unreadCount = await db.getOne('SELECT COUNT(*) AS count FROM chats WHERE session = ? AND sender != ? AND id > (SELECT COALESCE((SELECT max_id FROM message_read_status WHERE session_id = ? AND user_id = ?), 0))', [targetId, receiverId])
      const unreadCountValue = unreadCount.count
      if (friendship) {
        const receiverId = friendship.source === userId ? friendship.target : friendship.source
        const pushData = {
          id: result.insertId,
          session: targetId,
          content: trimmedMsg,
          sender: userId,
          sender_nick: senderNick,
          sender_avatar: senderAvatar,
          remark,
          msg_type: 1,
          type: 'friend',
          time: Math.floor(Date.now() / 1000),
          unread_count: unreadCountValue
        }
        sendToUser(receiverId, 'new_message', pushData)
      } else {
        const groupMembers = await db.query('SELECT user FROM group_members WHERE `group` = ?', [targetId])
        const groupAvatarRow = await db.getOne('SELECT group_avatar FROM `groups` WHERE id = ?', [targetId])
        const groupAvatar = groupAvatarRow.group_avatar
        const groupMemberRow = await db.getOne('SELECT group_nickname FROM group_members WHERE `group` = ? AND user = ?', [targetId, userId])
        const groupNickname = groupMemberRow.group_nickname
        const pushData = {
          id: result.insertId,
          session: targetId,
          content: trimmedMsg,
          sender: userId,
          sender_nick: senderNick,
          remark,
          group_avatar: groupAvatar,
          members: groupMembers.length,
          group_nickname: groupNickname,
          msg_type: 1,
          type: 'group',
          time: Math.floor(Date.now() / 1000),
          unread_count: unreadCountValue
        }
        for (const member of groupMembers) {
          if (member.user !== userId) {
            sendToUser(member.user, 'new_message', pushData)
          }
        }
      }
    } catch (pushError) {
      console.error('实时推送消息失败:', pushError)
    }
    res.json({
      code: 1,
      msg: '消息发送成功。',
      data: {
        id: result.insertId
      }
    })
  } catch (error) {
    console.error('发送消息错误:', error)
    res.status(500).json({
      code: -1,
      msg: '服务器内部错误。'
    })
  }
})
export default router