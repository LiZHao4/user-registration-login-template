import {} from ''
interface Response {
  code: number
  msg: string
}
interface Pagination {
  pagination: {
    total: number
    page: number
    limit: number
  }
}