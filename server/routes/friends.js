import express from 'express'
import { getDisplayName } from '../utils.js'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
const router = express.Router()
router.get('/friends', authMiddleware, async (req, res) => {
  try {
    const userId = req.userId
    const typeFilter = req.query.type || 'all'
    const simplify = !!req.query.simplify
    if (!['all', 'friend', 'group'].includes(typeFilter)) {
      return res.status(400).json({
        code: -1,
        msg: '无效的type参数'
      })
    }
    const resultData = []
    if (typeFilter === 'all' || typeFilter === 'friend') {
      const friendsResult = await db.query('SELECT id, CASE WHEN source = ? THEN target ELSE source END AS friend_id, UNIX_TIMESTAMP(allowed_time) AS allowed_time FROM friendships WHERE source = ? OR target = ?', [userId, userId, userId])
      for (const friendRow of friendsResult) {
        const friendId = friendRow.friend_id
        const displayName = await getDisplayName(db, userId, friendId)
        const avatarResult = await db.query('SELECT user_avatar AS avatar FROM users WHERE id = ?', [friendId])
        const lastMsgResult = await db.query('SELECT content, UNIX_TIMESTAMP(sent_at) AS sent_at, type, sender FROM chats WHERE session = ? ORDER BY sent_at DESC LIMIT 1', [friendRow.id])
        const unreadResult = await db.query('SELECT COUNT(*) AS count FROM chats WHERE session = ? AND id > (SELECT COALESCE((SELECT max_id FROM message_read_status WHERE session_id = ? AND user_id = ?), 0)) AND sender != ?', [friendRow.id, friendRow.id, userId, userId])
        const lastMsg = lastMsgResult[0] || null
        const unreadCount = unreadResult[0] ? unreadResult[0].count : 0
        const msgNick = await getDisplayName(db, userId, lastMsg ? lastMsg.sender : null)
        const friendData = {
          id: friendRow.id,
          avatar: avatarResult[0]?.avatar || null,
          nick: displayName,
          time: lastMsg ? lastMsg.sent_at : friendRow.allowed_time,
          content: lastMsg ? lastMsg.content : '',
          msg_type: lastMsg ? lastMsg.type : null,
          msg_sender: lastMsg ? lastMsg.sender : null,
          msg_nick: lastMsg ? msgNick : null,
          unread_count: unreadCount,
          type: 'friend',
          friend_id: friendId
        }
        resultData.push(friendData)
      }
    }
    if (typeFilter === 'all' || typeFilter === 'group') {
      const groupsResult = await db.query('SELECT id, group_name, group_avatar FROM `groups` WHERE id IN (SELECT `group` FROM group_members WHERE user = ?)', [userId])
      for (const groupRow of groupsResult) {
        const joinedAtResult = await db.query('SELECT UNIX_TIMESTAMP(joined_at) AS joined_at FROM group_members WHERE user = ? AND `group` = ?', [userId, groupRow.id])
        const lastMessageResult = await db.query('SELECT content, UNIX_TIMESTAMP(sent_at) AS sent_at, type, sender FROM chats WHERE session = ? ORDER BY sent_at DESC LIMIT 1', [groupRow.id])
        const unreadCountResult = await db.query('SELECT COUNT(*) AS count FROM chats WHERE session = ? AND id > (SELECT COALESCE((SELECT max_id FROM message_read_status WHERE session_id = ? AND user_id = ?), 0)) AND sender != ?', [groupRow.id, groupRow.id, userId, userId])
        const memberCountResult = await db.query('SELECT COUNT(*) AS member_count FROM group_members WHERE `group` = ?', [groupRow.id])
        const joinedAt = joinedAtResult[0]?.joined_at || 0
        const lastMessage = lastMessageResult[0] || null
        const unreadCount = unreadCountResult[0] ? unreadCountResult[0].count : 0
        const memberCount = memberCountResult[0] ? memberCountResult[0].member_count : 0
        const groupData = {
          id: groupRow.id,
          nick: groupRow.group_name,
          avatar: groupRow.group_avatar,
          time: joinedAt,
          content: '',
          unread_count: unreadCount,
          type: 'group',
          member_count: memberCount
        }
        if (lastMessage) {
          groupData.time = lastMessage.sent_at
          groupData.content = lastMessage.content
          groupData.msg_type = lastMessage.type
          groupData.msg_sender = lastMessage.sender
          groupData.msg_nick = await getDisplayName(db, userId, lastMessage.sender, groupRow.id)
          if (lastMessage.type === 4) {
            try {
              const sysMsgContent = JSON.parse(lastMessage.content)
              if (sysMsgContent && sysMsgContent.target) {
                const targetName = await getDisplayName(db, userId, sysMsgContent.target, groupRow.id)
                groupData.inner_nick = targetName
              }
            } catch (e) {}
          }
        }
        resultData.push(groupData)
      }
    }
    let finalData = resultData
    if (simplify) {
      finalData = resultData.map(item => ({
        id: item.id,
        nick: item.nick,
        avatar: item.avatar,
        time: item.time
      }))
    }
    finalData.sort((a, b) => b.time - a.time)
    res.json({
      code: 1,
      msg: '好友列表获取成功。',
      data: finalData
    })
  } catch (error) {
    res.status(500).json({
      code: -1,
      msg: '服务器内部错误。'
    })
  }
})
router.get('/requests', authMiddleware, async (req, res) => {
  try {
    const userId = req.userId
    const receivedRequests = await db.query('SELECT fr.id, fr.source, fr.target, UNIX_TIMESTAMP(fr.sent_at) AS time, u.nick, ur.remark FROM friend_requests fr JOIN users u ON fr.source = u.id LEFT JOIN user_remarks ur ON u.id = ur.target_user_id AND ur.user_id = ? WHERE fr.target = ?', [userId, userId])
    const sentRequests = await db.query('SELECT fr.id, fr.source, fr.target, UNIX_TIMESTAMP(fr.sent_at) AS time, u.nick, ur.remark FROM friend_requests fr JOIN users u ON fr.target = u.id LEFT JOIN user_remarks ur ON u.id = ur.target_user_id AND ur.user_id = ? WHERE fr.source = ?', [userId, userId])
    res.json({
      code: 1,
      msg: '好友请求列表获取成功。',
      received: receivedRequests,
      sent: sentRequests
    })
  } catch (error) {
    res.status(500).json({
      code: -1,
      msg: '服务器内部错误。'
    })
  }
})
/*
发送好友请求  POST /api/friend-requests
  请求体 {"target": "用户ID", "message": "好友请求信息"}
  响应体
    请求成功 201 {"code": 1, "msg": "好友请求已发送。"}
    请求失败类型：
      用户不存在 404 {"code": -1, "msg": "用户不存在。"}
      用户已是好友 409 {"code": -1, "msg": "用户已是好友。"}
      用户自己 403 {"code": -1, "msg": "不能添加自己为好友。"}
      请求已发送 409 {"code": -1, "msg": "好友请求已发送。"}

同意好友请求  POST /api/friend-requests/{requestId}/accept
  响应体
    请求成功 200 {"code": 1, "msg": "好友请求已接受。"}
    请求失败类型：
      请求不存在 404 {"code": -1, "msg": "好友请求不存在。"}
      无权限处理请求 403 {"code": -1, "msg": "无权限处理该好友请求。"}

拒绝好友请求  POST /api/friend-requests/{requestId}/reject
  响应体
    请求成功 200 {"code": 1, "msg": "好友请求已拒绝。"}
    请求失败类型：
      请求不存在 404 {"code": -1, "msg": "好友请求不存在。"}
      无权限处理请求 403 {"code": -1, "msg": "无权限处理该好友请求。"}

撤销好友请求  DELETE /api/friend-requests/{requestId}
  响应体
    请求成功 200 {"code": 1, "msg": "好友请求已撤销。"}
    请求失败类型：
      请求不存在 404 {"code": -1, "msg": "好友请求不存在。"}
      无权限处理请求 403 {"code": -1, "msg": "无权限处理该好友请求。"}
*/
export default router