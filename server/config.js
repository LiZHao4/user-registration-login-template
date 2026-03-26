import mysql from 'mysql2/promise'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
function parseIniFile(filePath) {
  const content = fs.readFileSync(filePath, 'utf-8')
  const config = {}
  let currentSection = config
  content.split('\n').forEach(line => {
    line = line.split(';')[0].split('#')[0].trim()
    if (!line) return
    const sectionMatch = line.match(/^\[(.*)\]$/)
    if (sectionMatch) {
      const sectionName = sectionMatch[1].trim()
      config[sectionName] = {}
      currentSection = config[sectionName]
      return
    }
    const keyValueMatch = line.match(/^(\w+)\s*=\s*(.*)$/)
    if (keyValueMatch) {
      const key = keyValueMatch[1].trim()
      let value = keyValueMatch[2].trim()
      if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
        value = value.slice(1, -1)
      }
      currentSection[key] = value
    }
  })
  return config
}
const config = parseIniFile(path.join(__dirname, '../settings.ini'))
const dbConfig = {
  host: config.database.host,
  port: config.database.port,
  user: config.database.user,
  password: config.database.pass,
  database: config.database.db
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