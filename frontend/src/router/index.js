/**
 * Vue Router 路由配置
 * 定义前端页面路由结构
 */

import { createRouter, createWebHistory } from 'vue-router'

// 页面组件（懒加载以优化首屏性能）
const EntryList = () => import('../views/EntryList.vue')
const EntryDetail = () => import('../views/EntryDetail.vue')
const SourceList = () => import('../views/SourceList.vue')
const NotFound = () => import('../views/NotFound.vue')

const routes = [
  {
    path: '/',
    name: 'home',
    redirect: '/entries',
  },
  {
    path: '/entries',
    name: 'entry-list',
    component: EntryList,
    meta: { title: '内容列表' },
  },
  {
    path: '/entries/:id',
    name: 'entry-detail',
    component: EntryDetail,
    meta: { title: '内容详情' },
  },
  {
    path: '/sources',
    name: 'source-list',
    component: SourceList,
    meta: { title: '来源管理' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFound,
    meta: { title: '页面未找到' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 路由切换时更新页面标题
router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} - News Pulse` : 'News Pulse'
})

export default router
