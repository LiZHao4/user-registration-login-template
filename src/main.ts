import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import 'element-plus/dist/index.css'
import VueViewer from 'v-viewer'
import 'viewerjs/dist/viewer.css'
const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)
app.use(VueViewer)
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}
app.mount('#app')