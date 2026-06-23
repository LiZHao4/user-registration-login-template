import { fileURLToPath, URL } from 'node:url'
import { defineConfig, type UserConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import Components from 'unplugin-vue-components/vite'
import AutoImport from 'unplugin-auto-import/vite'
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'
import { parseIniFile } from './parse'
import cssnano from 'cssnano'
const config = parseIniFile('./settings.ini')
const targetUrl = `http://${config.server.backend_host}:${config.server.backend_port}`
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
    port: parseInt(config.server.frontend_port),
    host: config.server.frontend_host
  }
} as UserConfig)