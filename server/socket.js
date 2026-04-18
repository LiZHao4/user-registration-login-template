import { parse } from 'cookie'
import { verifyToken } from './middlewares/auth.js'
const userSockets = new Map()
export function initSocket(io) {
  io.on('connection', async (socket) => {
    const cookieHeader = socket.handshake.headers.cookie
    if (!cookieHeader) {
      socket.emit('error', {
        code: 0,
        message: '未提供Cookie，连接被拒绝。'
      })
      socket.disconnect(true)
      return
    }
    const cookies = parse(cookieHeader)
    const sessionToken = cookies.t
    if (!sessionToken) {
      socket.emit('error', {
        code: 0,
        message: '缺少有效的身份验证Cookie。'
      })
      socket.disconnect(true)
      return
    }
    try {
      const userId = await verifyToken(sessionToken)
      if (!userId) {
        socket.emit('error', {
          code: 0,
          message: 'Cookie无效或已过期，验证失败。'
        })
        socket.disconnect(true)
        return
      }
      if (!socket.connected) {
        return
      }
      socket.userId = userId
      if (!userSockets.has(userId)) {
        userSockets.set(userId, new Set())
      }
      userSockets.get(userId).add(socket)
      socket.on('disconnect', () => {
        const sockets = userSockets.get(socket.userId)
        if (sockets) {
          sockets.delete(socket)
          if (sockets.size === 0) {
            userSockets.delete(socket.userId)
          }
        }
      })
    } catch (error) {
      socket.emit('error', { message: '服务器内部错误，验证失败。' })
      socket.disconnect(true)
    }
  })
}
export function sendToUser(userId, event, data) {
  const sockets = userSockets.get(userId)
  if (sockets && sockets.size > 0) {
    sockets.forEach(socket => socket.emit(event, data))
    return true
  }
  return false
}