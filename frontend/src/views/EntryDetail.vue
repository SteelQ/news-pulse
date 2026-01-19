<script setup>
/**
 * 内容详情页
 * 展示单个条目的详细信息，提供原文跳转链接
 */

import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { getEntry } from '../api/entries'

const route = useRoute()

// 路由参数作为数据来源，便于处理路由切换
const entryId = computed(() => route.params.id)

// 详情页状态
const entry = ref(null)
const loading = ref(true)
const error = ref('')
const isNotFound = ref(false)

// 拉取详情数据，区分 404 与其他异常
async function fetchEntry(id) {
  if (!id) {
    error.value = '缺少条目 ID，无法加载详情'
    loading.value = false
    return
  }

  loading.value = true
  error.value = ''
  isNotFound.value = false
  entry.value = null

  try {
    const response = await getEntry(id)
    entry.value = response?.data || null
    if (!entry.value) {
      error.value = '未找到对应条目'
      isNotFound.value = true
    }
  } catch (err) {
    if (err?.status === 404) {
      error.value = '未找到该条目，可能已被删除'
      isNotFound.value = true
    } else {
      error.value = err?.message || '加载条目失败，请稍后重试'
    }
  } finally {
    loading.value = false
  }
}

// 格式化发布时间，保持与列表页一致
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

// 监听路由变化，确保进入详情页时刷新数据
watch(entryId, (id) => {
  fetchEntry(id)
}, { immediate: true })
</script>

<template>
  <div class="entry-detail">
    <header class="page-header">
      <div class="title-group">
        <h1>{{ entry?.title || '内容详情' }}</h1>
        <p v-if="entry" class="subtitle">
          <span>{{ entry.source?.name || '未知来源' }}</span>
          <span class="divider">·</span>
          <time>{{ formatDate(entry.published_at) }}</time>
        </p>
      </div>
      <router-link to="/entries" class="back-link">← 返回列表</router-link>
    </header>

    <!-- 加载态 -->
    <div v-if="loading" class="state-message loading">
      加载中...
    </div>

    <!-- 错误态 -->
    <div v-else-if="error" class="state-message error">
      <p>{{ error }}</p>
      <button v-if="!isNotFound" @click="fetchEntry(entryId)">重试</button>
    </div>

    <!-- 空态 -->
    <div v-else-if="!entry" class="state-message empty">
      暂无内容
    </div>

    <!-- 详情内容 -->
    <div v-else class="entry-body">
      <section class="meta-card">
        <div class="meta-row">
          <span class="label">来源</span>
          <span class="value">{{ entry.source?.name || '未知来源' }}</span>
        </div>
        <div class="meta-row">
          <span class="label">发布时间</span>
          <time class="value">{{ formatDate(entry.published_at) }}</time>
        </div>
        <div v-if="entry.source?.type" class="meta-row">
          <span class="label">来源类型</span>
          <span class="value">{{ entry.source.type }}</span>
        </div>
        <div class="meta-row">
          <span class="label">原文链接</span>
          <a
            v-if="entry.url"
            :href="entry.url"
            class="origin-link"
            target="_blank"
            rel="noopener"
          >
            打开原文
          </a>
          <span v-else class="value muted">暂无链接</span>
        </div>
      </section>

      <section v-if="entry.summary" class="content-block">
        <h2>摘要</h2>
        <p class="content-text">{{ entry.summary }}</p>
      </section>

      <section v-if="entry.content" class="content-block">
        <h2>正文</h2>
        <p class="content-text">{{ entry.content }}</p>
      </section>

      <section v-if="!entry.summary && !entry.content" class="content-block">
        <h2>内容</h2>
        <p class="content-text muted">暂无摘要或正文内容，请通过原文链接查看。</p>
      </section>
    </div>
  </div>
</template>

<style scoped>
.entry-detail {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.title-group h1 {
  margin: 0;
}

.subtitle {
  margin: 6px 0 0;
  font-size: 14px;
  color: #666;
}

.divider {
  margin: 0 6px;
}

.state-message {
  text-align: center;
  padding: 48px 20px;
  color: #666;
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

.back-link {
  display: inline-block;
  color: #42b883;
}

.entry-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.meta-card {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 16px;
  background: #fafafa;
}

.meta-row {
  display: flex;
  gap: 12px;
  padding: 8px 0;
}

.meta-row + .meta-row {
  border-top: 1px dashed #e6e6e6;
}

.label {
  min-width: 72px;
  font-size: 13px;
  color: #666;
}

.value {
  font-size: 14px;
  color: #333;
}

.origin-link {
  font-size: 14px;
  color: #007bff;
}

.content-block h2 {
  margin: 0 0 12px;
  font-size: 16px;
}

.content-text {
  margin: 0;
  color: #333;
  line-height: 1.7;
  white-space: pre-wrap;
}

.muted {
  color: #999;
}
</style>
