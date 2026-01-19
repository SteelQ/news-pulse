<script setup>
/**
 * 内容列表页
 * 展示聚合的条目列表，支持分页和来源筛选
 */

import { ref, onMounted, watch } from 'vue'
import { getEntries } from '../api/entries'
import { getSources } from '../api/sources'
import { useRouter } from 'vue-router'

const router = useRouter()

// 状态
const entries = ref([])
const sources = ref([])
const loading = ref(true)
const error = ref(null)

// 筛选条件
const selectedSourceId = ref('')

// 分页信息
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

// 加载条目列表
async function fetchEntries() {
  loading.value = true
  error.value = null

  try {
    const params = { page: pagination.value.currentPage }
    if (selectedSourceId.value) {
      params.source_id = selectedSourceId.value
    }

    const response = await getEntries(params)
    entries.value = response.data || []

    // 提取分页信息（Laravel 分页格式）
    if (response.meta) {
      pagination.value = {
        currentPage: response.meta.current_page,
        lastPage: response.meta.last_page,
        total: response.meta.total,
      }
    }
  } catch (err) {
    error.value = err.message || '加载条目失败'
    entries.value = []
  } finally {
    loading.value = false
  }
}

// 加载来源列表（用于筛选下拉）
async function fetchSources() {
  try {
    const response = await getSources()
    sources.value = response.data || []
  } catch (err) {
    // 来源加载失败不阻塞页面，仅隐藏筛选
    console.warn('加载来源列表失败:', err.message)
  }
}

// 跳转到详情页
function goToDetail(id) {
  router.push({ name: 'entry-detail', params: { id } })
}

// 来源筛选变化时重新加载
function onSourceChange() {
  pagination.value.currentPage = 1
  fetchEntries()
}

// 上一页
function prevPage() {
  if (pagination.value.currentPage > 1) {
    pagination.value.currentPage--
    fetchEntries()
  }
}

// 下一页
function nextPage() {
  if (pagination.value.currentPage < pagination.value.lastPage) {
    pagination.value.currentPage++
    fetchEntries()
  }
}

// 格式化发布时间
function formatDate(dateStr) {
  if (!dateStr) return '—'
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// 初始加载
onMounted(() => {
  fetchSources()
  fetchEntries()
})
</script>

<template>
  <div class="entry-list">
    <header class="page-header">
      <h1>内容列表</h1>

      <!-- 来源筛选 -->
      <div v-if="sources.length > 0" class="filter-bar">
        <label for="source-filter">按来源筛选：</label>
        <select id="source-filter" v-model="selectedSourceId" @change="onSourceChange">
          <option value="">全部来源</option>
          <option v-for="source in sources" :key="source.id" :value="source.id">
            {{ source.name }}
          </option>
        </select>
      </div>
    </header>

    <!-- 加载态 -->
    <div v-if="loading" class="state-message loading">
      加载中...
    </div>

    <!-- 错误态 -->
    <div v-else-if="error" class="state-message error">
      <p>{{ error }}</p>
      <button @click="fetchEntries">重试</button>
    </div>

    <!-- 空态 -->
    <div v-else-if="entries.length === 0" class="state-message empty">
      <p>暂无内容</p>
      <p v-if="selectedSourceId" class="hint">
        该来源暂无条目，请尝试选择其他来源或清除筛选。
      </p>
      <p v-else class="hint">
        还没有采集到任何内容，请先添加来源并执行采集。
      </p>
    </div>

    <!-- 条目列表 -->
    <ul v-else class="entries">
      <li v-for="entry in entries" :key="entry.id" class="entry-item" @click="goToDetail(entry.id)">
        <h2 class="entry-title">{{ entry.title }}</h2>
        <div class="entry-meta">
          <span class="source-name">{{ entry.source?.name || '未知来源' }}</span>
          <span class="divider">·</span>
          <time class="published-at">{{ formatDate(entry.published_at) }}</time>
        </div>
      </li>
    </ul>

    <!-- 分页 -->
    <nav v-if="!loading && !error && pagination.lastPage > 1" class="pagination">
      <button :disabled="pagination.currentPage <= 1" @click="prevPage">上一页</button>
      <span class="page-info">{{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
      <button :disabled="pagination.currentPage >= pagination.lastPage" @click="nextPage">下一页</button>
    </nav>
  </div>
</template>

<style scoped>
.entry-list {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.page-header h1 {
  margin: 0;
}

.filter-bar {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-bar label {
  font-size: 14px;
  color: #666;
}

.filter-bar select {
  padding: 6px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.state-message {
  text-align: center;
  padding: 48px 20px;
  color: #666;
}

.state-message.loading {
  font-size: 16px;
}

.state-message.error {
  color: #c00;
}

.state-message.error button {
  margin-top: 12px;
  padding: 8px 16px;
  background: #007bff;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.state-message .hint {
  font-size: 14px;
  color: #999;
  margin-top: 8px;
}

.entries {
  list-style: none;
  margin: 0;
  padding: 0;
}

.entry-item {
  padding: 16px 0;
  border-bottom: 1px solid #eee;
  cursor: pointer;
  transition: background 0.15s;
}

.entry-item:hover {
  background: #f9f9f9;
  margin: 0 -12px;
  padding: 16px 12px;
}

.entry-title {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 500;
  color: #333;
  line-height: 1.4;
}

.entry-meta {
  font-size: 14px;
  color: #666;
}

.source-name {
  color: #007bff;
}

.divider {
  margin: 0 6px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.pagination button {
  padding: 8px 16px;
  background: #007bff;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.pagination button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #666;
}
</style>
