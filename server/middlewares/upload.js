import multer from 'multer'
import path from 'path'
import { fileURLToPath } from 'url'
import { generateRandomString } from '../utils.js'
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    if (req.originalUrl.includes('/upload/article')) {
      cb(null, path.join(__dirname, '../../uploads/images'))
    } else if (req.originalUrl.includes('/upload/avatar')) {
      cb(null, path.join(__dirname, '../../uploads/avatar'))
    } else if (req.originalUrl.includes('/upload/background')) {
      cb(null, path.join(__dirname, '../../uploads/bg'))
    } else if (req.originalUrl.includes('/upload/file')) {
      cb(null, path.join(__dirname, '../../uploads/files'))
    }
  },
  filename: (req, file, cb) => {
    let randomLength = 60
    if (req.originalUrl.includes('/upload/file')) {
      randomLength = 80
    }
    const randomStr = generateRandomString(randomLength)
    cb(null, randomStr + (req.originalUrl.includes('/upload/file') ? '' : path.extname(file.originalname)))
  }
})
const fileFilter = (req, file, cb) => {
  if (req.originalUrl.includes('/upload/file')) {
    cb(null, true);
  } 
  else {
    if (file.mimetype.startsWith('image/')) {
      cb(null, true);
    } else {
      cb(new multer.MulterError('LIMIT_UNEXPECTED_FILE', '只允许图片。'), false)
    }
  }
}
export const upload = multer({ storage, fileFilter })