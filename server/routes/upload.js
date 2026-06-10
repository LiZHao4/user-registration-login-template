import express from 'express'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
import { generateRandomString } from '../utils.js'
import multer from 'multer'
import sharp from 'sharp'
import path from 'path'
import fs from 'fs'
import { fileTypeFromBuffer } from 'file-type'
import crypto from 'crypto'
const router = express.Router()
router.use(authMiddleware)
const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 10485760 } })
router.post('/upload/images', async (req, res) => {
  upload.array('image', 100)(req, res, async (err) => {
    if (err instanceof multer.MulterError) {
      if (err.code === 'LIMIT_UNEXPECTED_FILE') {
        return res.status(400).json({ code: -1, msg: '最多允许上传100张图片。' });
      }
      return res.status(400).json({ code: -1, msg: err.message })
    } else if (err) {
      console.error(err)
      return res.status(500).json({ code: -1, msg: '上传处理失败。' })
    }
    const files = req.files
    if (!files || files.length === 0) {
      return res.status(400).json({ code: -1, msg: '没有上传任何图片。' })
    }
    if (files.length > 20) {
      return res.status(400).json({ code: -1, msg: '最多允许上传100张图片。' })
    }
    const newFiles = []
    try {
      const resultNames = []
      for (const file of files) {
        const fileType = await fileTypeFromBuffer(file.buffer)
        if (!fileType || !fileType.mime.startsWith('image/')) {
          throw new Error()
        }
        const processedBuffer = await sharp(file.buffer).png().withMetadata(false).toBuffer()
        const hashValue = crypto.createHash('sha256').update(processedBuffer).digest()
        const [rows] = await db.query('SELECT name FROM images WHERE hash_value = ?', [hashValue])
        if (rows.length > 0) {
          resultNames.push(rows[0].name)
          continue
        }
        const randomName = generateRandomString(60)
        const filename = `${randomName}.png`
        const savePath = path.join(process.cwd(), '..', 'uploads', 'images', filename)
        await fs.promises.writeFile(savePath, processedBuffer)
        await db.query('INSERT INTO images (name, hash_value) VALUES (?, ?)', [randomName, hashValue])
        await db.query('INSERT INTO user_images (user, image_name) VALUES (?, ?)', [req.userId, randomName])
        newFiles.push({ name: randomName, savePath })
        resultNames.push(randomName)
      }
      res.json({ code: 1, msg: '上传成功。', imageNames: savedFiles.map(f => f.randomName) })
    } catch {
      for (const { savePath } of savedFiles) {
        if (fs.existsSync(savePath)) fs.unlinkSync(savePath)
      }
      res.status(500).json({ code: -1, msg: '上传失败。' })
    }
  })
})