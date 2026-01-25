import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  // Docker 容器内开发服务器配置
  server: {
    host: '0.0.0.0', // 允许外部访问
    port: 5173,
    watch: {
      usePolling: true, // Docker 文件系统监听优化
    },
    // 代理后端 API，避免本地开发时的跨域问题
    proxy: {
      '/api': {
        target: 'http://web',
        changeOrigin: true,
      },
    },
  },
})
