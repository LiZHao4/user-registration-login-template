import { fileURLToPath, URL } from 'node:url'
import { defineConfig, type UserConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import Components from 'unplugin-vue-components/vite'
import AutoImport from 'unplugin-auto-import/vite'
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'
import cssnano from 'cssnano'
import dotenv from 'dotenv'
dotenv.config()
const backendHost = process.env.BACKEND_HOST
const backendPort = process.env.BACKEND_PORT
const frontendHost = process.env.FRONTEND_HOST
const frontendPort = process.env.FRONTEND_PORT
const targetUrl = `http://${backendHost}:${backendPort}`
export default defineConfig({
  plugins: [
    vue(),
    AutoImport({
      resolvers: [ElementPlusResolver()],
    }),
    Components({
      resolvers: [ElementPlusResolver({ importStyle: true })],
    }),
    vueDevTools()
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('element-plus')) {
              return 'vendor-element'
            }
            return 'vendor'
          }
        }
      }
    }
  },
  css: {
    postcss: {
      plugins: [
        cssnano({ preset: ['default', { mergeRules: false }]})
      ]
    }
  },
  server: {
    proxy: {
      '/api': {
        target: targetUrl,
        changeOrigin: true
      },
      '/uploads': {
        target: targetUrl,
        changeOrigin: true
      },
      '/socket.io': {
        target: targetUrl,
        changeOrigin: true,
        ws: true
      }
    },
    port: parseInt(frontendPort),
    host: frontendHost
  }
} as UserConfig)