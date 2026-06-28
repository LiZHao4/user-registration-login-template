import mysql from 'mysql2/promise'
import path from 'path'
import { fileURLToPath } from 'url'
import dotenv from 'dotenv'
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
dotenv.config({ path: path.join(__dirname, '../.env') })
const dbConfig = {
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT),
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME
}
const pool = mysql.createPool(dbConfig)
async function testConnection() {
  try {
    const connection = await pool.getConnection()
    console.log('✅ MySQL 数据库连接成功')
    connection.release()
  } catch (error) {
    console.error('❌ MySQL 数据库连接失败:', error.message)
    process.exit(1)
  }
}
const db = {
  async query(sql, params = []) {
    try {
      const [results] = await pool.execute(sql, params)
      return results
    } catch (error) {
      throw error
    }
  },
  async getOne(sql, params = []) {
    const results = await this.query(sql, params)
    return results[0] || null
  },
  async insert(sql, params = []) {
    const result = await this.query(sql, params)
    return result.insertId
  },
  async beginTransaction() {
    const connection = await pool.getConnection()
    await connection.beginTransaction()
    return connection
  },
  async commit(connection) {
    await connection.commit()
    connection.release()
  },
  async rollback(connection) {
    await connection.rollback()
    connection.release()
  }
}
testConnection()
export default db