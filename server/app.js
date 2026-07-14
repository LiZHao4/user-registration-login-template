import express from 'express'
import cookieParser from 'cookie-parser'
import authRoutes from './routes/auth.js'
import userRoutes from './routes/user.js'
import friendRoutes from './routes/friends.js'
import chatRoutes from './routes/chat.js'
import articleRoutes from './routes/article.js'
import messageRoutes from './routes/message.js'
import uploadRoutes from './routes/upload.js'
import followRoutes from './routes/follow.js'
import searchRoutes from './routes/search.js'
import path from 'path'
import { fileURLToPath } from 'url'
import morgan from 'morgan'
import fs from 'fs'
import http from 'http'
import { Server } from 'socket.io'
import { initSocket } from './socket.js'
import cookie from 'cookie'
import { verifyToken } from './middlewares/auth.js'
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const app = express()
const HOST = process.env.BACKEND_HOST
const PORT = parseInt(process.env.BACKEND_PORT)
const logsDir = path.join(__dirname, 'logs')
if (!fs.existsSync(logsDir)) {
  fs.mkdirSync(logsDir, { recursive: true })
}
const accessLogStream = fs.createWriteStream(path.join(logsDir, 'access.log'), { flags: 'a' })
const uploadDirs = ['../uploads', '../uploads/avatar', '../uploads/bg', '../uploads/files', '../uploads/images']
uploadDirs.forEach(dir => {
  const fullPath = path.join(__dirname, dir)
  if (!fs.existsSync(fullPath)) {
    fs.mkdirSync(fullPath, { recursive: true })
    console.log(`创建目录: ${fullPath}`)
  }
})
app.use(express.json())
app.use(express.urlencoded({ extended: true }))
app.use(cookieParser())
app.use(morgan('combined', { stream: accessLogStream }))
app.use('/api', authRoutes)
app.use('/api', userRoutes)
app.use('/api', friendRoutes)
app.use('/api', chatRoutes)
app.use('/api', articleRoutes)
app.use('/api', messageRoutes)
app.use('/api', uploadRoutes)
app.use('/api', followRoutes)
app.use('/api', searchRoutes)
app.use('/uploads', express.static(path.join(__dirname, '../uploads')))
app.use(express.static(path.join(__dirname, '../dist')))
app.use((req, res, next) => {
  if (!req.path.startsWith('/api') && !req.path.startsWith('/uploads')) {
    const indexPath = path.join(__dirname, '../dist/index.html')
    if (fs.existsSync(indexPath)) {
      res.sendFile(indexPath)
    } else {
      res.status(404).json({
        code: -1,
        msg: '前端文件未找到，请先构建前端项目。'
      })
    }
  } else {
    next()
  }
})
app.use((err, req, res, next) => {
  res.status(500).send({
    code: -1,
    msg: '服务器内部错误。'
  })
})
app.use((req, res) => {
  res.status(404).send({
    code: -1,
    msg: '未找到页面。'
  })
})
const server = http.createServer(app)
const io = new Server(server, {
  cors: {
    origin: `http://${HOST}:${PORT}`,
    credentials: true
  }
})
io.use(async (socket, next) => {
  try {
    const cookies = cookie.parse(socket.handshake.headers.cookie || '')
    const token = cookies.t
    if (!token) {
      return next(new Error('未提供认证token。'))
    }
    const user = await verifyToken(token)
    if (!user) {
      return next(new Error('无效的token。'))
    }
    socket.data.userId = user.id
    next()
  } catch (err) {
    next(new Error('认证失败：' + err.message))
  }
})
initSocket(io)
server.listen(PORT, HOST, () => {
  console.log(`后端服务器运行在 http://${HOST}:${PORT}`)
})