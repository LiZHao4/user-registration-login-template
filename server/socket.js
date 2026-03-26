const userSockets = new Map()
export function initSocket(io) {
  io.on('connection', (socket) => {
    const userId = socket.data.userId
    if (!userId) {
      socket.disconnect()
      return
    }
    if (!userSockets.has(userId)) {
      userSockets.set(userId, new Set())
    }
    userSockets.get(userId).add(socket)
    socket.on('disconnect', () => {
      const sockets = userSockets.get(userId)
      if (sockets) {
        sockets.delete(socket)
        if (sockets.size === 0) {
          userSockets.delete(userId)
        }
      }
    })
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