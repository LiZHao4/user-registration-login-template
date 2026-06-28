import {} from ''
interface NotificationOptions {
  title: string
  content: string
  time: number
  imageUrl?: string
  badge?: number
  duration: number
  onClick?: () => void
}
interface NotificationInstance extends NotificationOptions {
  id: string
  visible: boolean
}