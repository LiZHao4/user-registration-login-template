import type { Response } from '.'
interface ImageUploadResponse extends Response {
  imageNames?: string[]
}