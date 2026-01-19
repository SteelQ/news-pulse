/**
 * 应用入口文件
 * 初始化 Vue 应用并挂载路由
 */

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css'

const app = createApp(App)

// 注册路由
app.use(router)

// 挂载应用
app.mount('#app')
