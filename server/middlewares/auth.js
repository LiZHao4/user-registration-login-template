import db from '../config.js'
export const authMiddleware = async (req, res, next) => {
  try {
    const token = req.cookies.t
    if (!token) {
      return res.status(401).json({
        code: 0,
        msg: 'Token不存在，请重新登录。'
      })
    }
    const sessionResult = await db.query('SELECT user FROM user_session WHERE token = ? AND expires >= NOW()', [token])
    if (sessionResult.length === 0) {
      return res.status(401).json({
        code: 0,
        msg: '用户不存在，请重新登录。'
      })
    }
    req.userId = sessionResult[0].user
    next()
  } catch (error) {
    if (error.code === 'ECONNREFUSED') {
      return res.status(500).json({
        code: -1,
        msg: '数据库连接失败。'
      })
    }
    return res.status(500).json({
      code: -1,
      msg: '服务器内部错误。'
    })
  }
}
export async function verifyToken(token) {
  if (!token) return null
  try {
    const sessionResult = await db.query('SELECT user FROM user_session WHERE token = ? AND expires >= NOW()', [token])
    if (sessionResult.length === 0) {
      return null
    }
    return { id: sessionResult[0].user }
  } catch (error) {
    return null
  }
}