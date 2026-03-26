import { fileURLToPath, URL } from 'node:url'
import { defineConfig, type UserConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import fs from 'fs'
import Components from 'unplugin-vue-components/vite'
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'
interface ServerConfig {
  backend_host: string
  backend_port: string
  frontend_port: string
  frontend_host: string
  [key: string]: string
}
interface IniConfig {
  server: ServerConfig
}
function parseIniFile(filePath: string): IniConfig {
  const content = fs.readFileSync(filePath, 'utf-8')
  const config: Partial<IniConfig> = {}
  let currentSection: Record<string, string> = {}
  content.split('\n').forEach(line => {
    line = line.split(';')[0].split('#')[0].trim()
    if (!line) return
    const sectionMatch = line.match(/^\[(.*)\]$/)
    if (sectionMatch) {
      const sectionName = sectionMatch[1].trim()
      if (sectionName === 'server') {
        config.server = {} as ServerConfig
        currentSection = config.server as Record<string, string>
      }
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
  return config as IniConfig
}
const config = parseIniFile('./settings.ini')
const targetUrl = `http://${config.server.backend_host}:${config.server.backend_port}`
export default defineConfig({
  plugins: [
    vue(),
    Components({
      resolvers: [ElementPlusResolver()],
    }),
    vueDevTools()
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    proxy: {
      '/api': {
        target: targetUrl,
        changeOrigin: false
      },
      '/uploads': {
        target: targetUrl,
        changeOrigin: false
      },
      '/socket.io': {
        target: targetUrl,
        changeOrigin: false,
        ws: true
      }
    },
    port: parseInt(config.server.frontend_port),
    host: config.server.frontend_host
  }
} as UserConfig)