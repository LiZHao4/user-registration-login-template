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
import { fileURLToPath } from 'url'
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const router = express.Router()
const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 10485760 } })
router.post('/upload/images', authMiddleware, async (req, res) => {
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
    if (files.length > 100) {
      return res.status(400).json({ code: -1, msg: '最多允许上传100张图片。' })
    }
    const preparedImages = []
    const allResultNames = []
    try {
      for (const file of files) {
        const fileType = await fileTypeFromBuffer(file.buffer)
        if (!fileType || !fileType.mime.startsWith('image/')) {
          throw new Error()
        }
        const processedBuffer = await sharp(file.buffer).png().withMetadata(false).toBuffer()
        const hashValue = crypto.createHash('sha256').update(processedBuffer).digest()
        const rows = await db.query('SELECT name FROM images WHERE hash_value = ?', [hashValue])
        if (rows.length > 0) {
          allResultNames.push(rows[0].name)
          continue
        }
        const randomName = generateRandomString(60)
        const filename = `${randomName}.png`
        const savePath = path.join(__dirname, '../../uploads/images', filename)
        preparedImages.push({ randomName, processedBuffer, savePath, hashValue })
        allResultNames.push(randomName)
      }
      if (preparedImages.length === 0) {
        return res.json({ code: 1, msg: '上传成功。', imageNames: allResultNames })
      }
      const savedFiles = []
      try {
        for (const img of preparedImages) {
          await fs.promises.writeFile(img.savePath, img.processedBuffer)
          savedFiles.push(img.savePath)
        }
      } catch (fileError) {
        for (const p of savedFiles) {
          if (fs.existsSync(p)) await fs.promises.unlink(p).catch(e => console.error(e))
        }
        return res.status(500).json({ code: -1, msg: '上传失败，文件写入错误。' })
      }
      const connection = await db.beginTransaction()
      try {
        for (const img of preparedImages) {
          await connection.execute(
            'INSERT INTO images (name, hash_value) VALUES (?, ?)',
            [img.randomName, img.hashValue]
          )
          await connection.execute(
            'INSERT INTO user_images (user, image_name) VALUES (?, ?)',
            [req.userId, img.randomName]
          )
        }
        await db.commit(connection)
      } catch (dbError) {
        await db.rollback(connection)
        console.error('数据库事务失败:', dbError)
        return res.status(500).json({ code: -1, msg: '上传失败，数据库错误。' })
      }
      res.json({ code: 1, msg: '上传成功。', imageNames: allResultNames })
    } catch (err) {
      for (const img of preparedImages) {
        if (fs.existsSync(img.savePath)) await fs.promises.unlink(img.savePath)
      }
      res.status(500).json({ code: -1, msg: '上传失败。' })
    }
  })
})
export default router