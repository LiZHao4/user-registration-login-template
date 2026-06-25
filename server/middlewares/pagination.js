export const pagination = (defaultLimit = 30) => {
  return (req, res, next) => {
    const page = parseInt(req.query.page) || 1
    const limit = parseInt(req.query.limit) || defaultLimit
    const offset = (page - 1) * limit
    if (offset < 0 || limit > 100) {
      return res.status(400).json({
        code: -1,
        msg: '参数非法。'
      })
    }
    req.pagination = { page, limit, offset }
    next()
  }
}