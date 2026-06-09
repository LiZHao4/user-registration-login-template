import express from 'express'
import db from '../config.js'
import { authMiddleware } from '../middlewares/auth.js'
import { generateRandomString } from '../utils.js'
import multer from 'multer'
import sharp from 'sharp'
import path from 'path'
import fs from 'fs'
import { fileTypeFromBuffer } from 'file-type'
const router = express.Router()
router.use(authMiddleware)
const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 10485760 } })
router.post('/upload/images', async (req, res) => {
  upload.array('image', 20)(req, res, async (err) => {
    if (err instanceof multer.MulterError) {
      if (err.code === 'LIMIT_UNEXPECTED_FILE') {
        return res.status(400).json({ code: -1, msg: '最多允许上传20张图片' });
      }
      return res.status(400).json({ code: -1, msg: err.message });
    } else if (err) {
      console.error(err);
      return res.status(500).json({ code: -1, msg: '上传处理失败' });
    }
    const files = req.files
    if (!files || files.length === 0) {
      return res.status(400).json({ code: -1, msg: '没有上传任何图片。' })
    }
    if (files.length > 20) {
      return res.status(400).json({ code: -1, msg: '最多允许上传20张图片。' })
    }
    const savedFiles = []
    try {
      for (const file of files) {
        const fileType = await fileTypeFromBuffer(file.buffer)
        if (!fileType || !fileType.mime.startsWith('image/')) {
          throw new Error()
        }
        const image = sharp(file.buffer)
        const randomName = generateRandomString(60)
        const filename = `${randomName}.png`
        const savePath = path.join(process.cwd(), '..',  'uploads', 'images', filename)
        await image.png().withMetadata(false).toFile(savePath)
        savedFiles.push({ filename, savePath })
        await db.query('INSERT INTO images (name, creator) VALUES (?, ?)', [randomName, req.userId])
      }
      res.json({ code: 1, msg: '上传成功。', filenames: savedFiles.map(f => f.filename) })
    } catch {
      for (const { savePath } of savedFiles) {
        if (fs.existsSync(savePath)) fs.unlinkSync(savePath)
      }
      res.status(500).json({ code: -1, msg: '上传失败。' })
    }
  })
})