import express from 'express'
import bcrypt from 'bcrypt'
import { validateCredentials, getUniqueToken } from '../utils.js'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
const router = express.Router()
router.post('/login', async (req, res) => {
  try {
    const userAgent = req.headers['user-agent']
    const ip = req.ip
    const { user: username, pass: password } = req.body
    if (!validateCredentials(username, password)) {
      return res.status(400).json({
        code: -1,
        msg: '用户名或密码格式不正确。'
      })
    }
    let user = await db.getOne('SELECT id, password, UNIX_TIMESTAMP(unbanned_at) as unbanned_at FROM users WHERE user = ?', [username])
    if (!user) {
      return res.status(404).json({
        code: -1,
        msg: '用户不存在。'
      })
    }
    if (user.unbanned_at && user.unbanned_at > Math.floor(Date.now() / 1000)) {
      return res.status(403).json({
        code: -1,
        msg: '您的账号已被封禁，解封时间：#t',
        unbanned_at: user.unbanned_at
      })
    }
    if (user.password.startsWith('$2y$')) {
      user.password = user.password.replace(/^\$2y\$/, '$2b$')
    }
    const isPasswordValid = await bcrypt.compare(password, user.password)
    if (!isPasswordValid) {
      return res.status(401).json({
        code: -1,
        msg: '密码错误。'
      })
    }
    const token = await getUniqueToken(db)
    const expires = Math.floor(Date.now() / 1000) + (30 * 24 * 60 * 60)
    await db.query('INSERT INTO user_session (user, token, expires, user_agent, ip) VALUES (?, ?, FROM_UNIXTIME(?), ?, ?)', [user.id, token, expires, userAgent, ip])
    res.cookie('t', token, {
      expires: new Date(expires * 1000),
      path: '/',
      httpOnly: true,
      sameSite: 'lax'
    })
    res.json({
      code: 1,
      msg: '登录成功。',
      token, expires
    })
    await db.query('DELETE FROM user_session WHERE expires <= NOW()')
  } catch (error) {
    res.status(500).json({
      code: -1,
      msg: '服务器错误，请稍后重试。'
    })
  }
})
router.post('/regist', async (req, res) => {
  try {
    const { user: username, pass: password } = req.body
    if (!validateCredentials(username, password)) {
      return res.status(400).json({
        code: -1,
        msg: '用户名或密码格式不正确。用户名必须以字母或下划线开头，长度1-32位；密码必须8-32位，包含大小写字母和数字。'
      })
    }
    const existingUser = await db.getOne('SELECT id FROM users WHERE user = ?', [username])
    if (existingUser) {
      return res.status(409).json({
        code: -1,
        msg: '用户名已存在。'
      })
    }
    const hashedPassword = await bcrypt.hash(password, 10)
    await db.query('INSERT INTO users (user, password, nick) VALUES (?, ?, ?)', [username, hashedPassword, username]);
    res.json({
      code: 1,
      msg: '注册成功。'
    })
  } catch (error) {
    res.status(500).json({
      code: -1,
      msg: '服务器错误，请稍后重试。'
    })
  }
})
router.post('/logout', authMiddleware, async (req, res) => {
  try {
    const token = req.cookies.t
    await db.query('DELETE FROM user_session WHERE token = ?', [token])
    res.clearCookie('t', {
      path: '/',
      httpOnly: true,
      sameSite: 'lax'
    })
    res.json({
      code: 1,
      msg: '退出登录成功。'
    })
  } catch (error) {
    res.status(500).json({
      code: -1,
      msg: '服务器错误，请稍后重试。'
    })
  }
})
export default router