import express from 'express'
import db from '../config.js'
const router = express.Router()
function addUserInfoToMessages(messages, userInfoMap) {
  for (const message of messages) {
    if (message.sender && userInfoMap[message.sender]) {
      const userInfo = userInfoMap[message.sender]
      message.sender_nick = userInfo.nick
      message.sender_avatar = userInfo.avatar
    }
    if (message.type === 6 && message.content && Array.isArray(message.content)) {
      addUserInfoToMessages(message.content, userInfoMap)
    }
  }
}
router.get('/chat/:id', async (req, res) => {
  try {
    const token = req.cookies.t
    const target = req.params.id
    if (!token || !target) {
      return res.status(400).json({
        code: -1,
        msg: '缺少必要的参数。'
      })
    }
    const sessionResult = await db.getOne('SELECT user FROM user_session WHERE token = ? AND expires >= NOW()', [token])
    if (!sessionResult) {
      return res.status(401).json({
        code: -1,
        msg: '未找到有效的用户信息。'
      })
    }
    const userId = sessionResult.user
    if (!/^\d+$/.test(target)) {
      return res.status(400).json({
        code: -1,
        msg: '会话ID必须是有效的数字。'
      })
    }
    const sessionId = parseInt(target)
    const { min, max, num, getmeta } = req.query
    const parsedMin = min ? parseInt(min) : null
    const parsedMax = max ? parseInt(max) : null
    const parsedNum = num ? parseInt(num) : null
    const isInitialLoad = (parsedMin === null && parsedMax === null && parsedNum === null)
    const getMeta = getmeta !== null
    const validateResult = await db.getOne('SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?) AS validate', [sessionId, userId, userId, sessionId, userId])
    if (!validateResult || validateResult.validate === 0) {
      return res.status(403).json({
        code: -1,
        msg: '不能偷看别人的聊天记录。'
      })
    }
    let type = null
    const friendshipCheck = await db.getOne('SELECT id FROM friendships WHERE id = ? AND (source = ? OR target = ?)', [sessionId, userId, userId])
    if (friendshipCheck) {
      type = 'friend'
    } else {
      const groupCheck = await db.getOne('SELECT id FROM `groups` WHERE id = ?', [sessionId])
      if (!groupCheck) {
        return res.status(404).json({
          code: 0,
          msg: '会话不存在。'
        })
      }
      const groupMemberCheck = await db.getOne('SELECT 1 FROM group_members WHERE `group` = ? AND user = ?', [sessionId, userId])
      if (groupMemberCheck) {
        type = 'group'
      }
    }
    if (!type) {
      return res.status(400).json({
        code: -1,
        msg: '未知的聊天类型。'
      })
    }
    let chatQuery = ''
    let queryParams = []
    let needReverse = false
    if (parsedMin !== null && parsedMax !== null) {
      chatQuery = 'SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi, type FROM chats WHERE session = ? AND id >= ? AND id <= ? ORDER BY id ASC'
      queryParams = [sessionId, parsedMin, parsedMax]
    } else if (parsedMax !== null) {
      chatQuery = 'SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi, type FROM chats WHERE session = ? AND id > ? ORDER BY id ASC'
      queryParams = [sessionId, parsedMax]
      if (parsedNum !== null) {
        chatQuery += ` LIMIT ${parsedNum}`
      }
    } else if (parsedMin !== null) {
      chatQuery = 'SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi, type FROM chats WHERE session = ? AND id < ? ORDER BY id DESC'
      queryParams = [sessionId, parsedMin]
      if (parsedNum !== null) {
        chatQuery += ` LIMIT ${parsedNum}`
      }
      needReverse = true
    } else if (parsedNum !== null) {
      chatQuery = `SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi, type FROM chats WHERE session = ? ORDER BY id DESC LIMIT ${parsedNum}`
      queryParams = [sessionId]
      needReverse = true
    } else {
      chatQuery = 'SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi, type FROM chats WHERE session = ? ORDER BY id'
      queryParams = [sessionId]
    }
    let chatData = await db.query(chatQuery, queryParams)
    if (needReverse) {
      chatData = chatData.reverse()
    }
    for (const chat of chatData) {
      if ([3, 4, 5, 6].includes(chat.type)) {
        try {
          chat.content = JSON.parse(chat.content)
          if (chat.type === 4 && chat.content && chat.content.target) {
            const innerUser = await db.getOne(
              'SELECT nick FROM users WHERE id = ?',
              [chat.content.target]
            )
            if (innerUser) {
              chat.inner_nick = innerUser.nick
            }
          }
          if (chat.type === 6 && chat.content && Array.isArray(chat.content)) {
            const userIds = new Set()
            const collectUserIds = (messages) => {
              for (const msg of messages) {
                if (msg.sender) {
                  userIds.add(msg.sender)
                }
                if (msg.type === 6 && msg.content && Array.isArray(msg.content)) {
                  collectUserIds(msg.content)
                }
              }
            }
            collectUserIds(chat.content)
            if (userIds.size > 0) {
              const userIdArray = Array.from(userIds)
              const placeholders = userIdArray.map(() => '?').join(',')
              const userInfos = await db.query(`SELECT id, nick, user_avatar FROM users WHERE id IN (${placeholders})`, userIdArray)
              const userInfoMap = {}
              for (const userInfo of userInfos) {
                userInfoMap[userInfo.id] = {
                  nick: userInfo.nick,
                  avatar: userInfo.user_avatar
                }
              }
              addUserInfoToMessages(chat.content, userInfoMap)
            }
          }
        } catch (error) {
          console.error('解析消息内容失败:', error)
        }
      }
      if (chat.type !== 2) {
        delete chat.multi
      }
    }
    let metaData = {}
    if (isInitialLoad || getMeta) {
      switch (type) {
        case 'friend':
          const selfAvatar = await db.getOne('SELECT user_avatar FROM users WHERE id = ?', [userId])
          const friendship = await db.getOne('SELECT CASE WHEN source = ? THEN target ELSE source END AS other_user_id, UNIX_TIMESTAMP(request_time) AS request_time, UNIX_TIMESTAMP(allowed_time) AS allowed_time FROM friendships WHERE id = ?', [userId, sessionId])
          if (friendship && friendship.other_user_id) {
            const otherUser = await db.getOne('SELECT nick, user_avatar FROM users WHERE id = ?', [friendship.other_user_id])
            const remark = await db.getOne('SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?', [userId, friendship.other_user_id])
            metaData = {
              id: userId,
              oId: friendship.other_user_id,
              avatar: selfAvatar.user_avatar,
              opposite: otherUser.user_avatar,
              oName: otherUser.nick,
              remark: remark ? remark.remark : null,
              requestTime: friendship.request_time,
              allowedTime: friendship.allowed_time
            }
          }
          break
        case 'group':
          const groupMembers = await db.query('SELECT user, role, group_nickname FROM group_members WHERE `group` = ? ORDER BY joined_at', [sessionId])
          const membersWithInfo = []
          let currentIndex = 0
          for (const member of groupMembers) {
            const userInfo = await db.getOne('SELECT nick, user_avatar FROM users WHERE id = ?', [member.user])
            const remark = await db.getOne('SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?', [userId, member.user])
            const memberData = {
              id: member.user,
              nick: userInfo.nick,
              remark: remark ? remark.remark : null,
              group_nickname: member.group_nickname,
              avatar: userInfo.user_avatar,
              role: member.role
            }
            membersWithInfo.push(memberData)
            if (member.user === userId) {
              currentIndex = membersWithInfo.length - 1
            }
          }
          const joinedAt = await db.getOne('SELECT UNIX_TIMESTAMP(joined_at) AS joined_at FROM group_members WHERE `group` = ? AND user = ?', [sessionId, userId])
          const groupInfo = await db.getOne('SELECT group_name, group_info_permission, group_avatar FROM `groups` WHERE id = ?', [sessionId])
          metaData = {
            members: membersWithInfo,
            joined_at: joinedAt.joined_at,
            group_name: groupInfo.group_name,
            current_user_index: currentIndex,
            group_info_permission: parseInt(groupInfo.group_info_permission),
            group_avatar: groupInfo.group_avatar
          }
          break
        default:
          return res.status(400).json({
            code: -1,
            msg: '未知类型。'
          })
      }
    }
    const maxIdResult = await db.getOne('SELECT COALESCE(MAX(id), 0) AS currentMaxId FROM chats WHERE session = ?', [sessionId])
    const currentMaxId = maxIdResult.currentMaxId
    const existingStatus = await db.getOne('SELECT 1 FROM message_read_status WHERE session_id = ? AND user_id = ?', [sessionId, userId])
    if (existingStatus) {
      await db.query('UPDATE message_read_status SET max_id = ? WHERE session_id = ? AND user_id = ?', [currentMaxId, sessionId, userId])
    } else {
      await db.query('INSERT INTO message_read_status (session_id, user_id, max_id) VALUES (?, ?, ?)', [sessionId, userId, currentMaxId])
    }
    const response = {
      code: 1,
      msg: '聊天信息获取成功。',
      data: chatData,
      type: type,
      sessionId: sessionId
    }
    if (isInitialLoad || getMeta) {
      Object.assign(response, metaData)
    }
    res.json(response)
  } catch (error) {
    console.error('获取聊天记录错误:', error)
    res.status(500).json({
      code: -1,
      msg: '数据库错误。'
    })
  }
})
export default router