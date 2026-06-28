import type { Response } from '.'
interface LoginResponse extends Response {
  token: string
  expires: number
  id: number
  unbanned_at?: number
}
type RegisterResponse = Response
type LogoutResponse = Response