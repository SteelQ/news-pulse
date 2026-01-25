<script setup>
/**
 * 来源管理页
 * 展示订阅源列表，支持新增来源
 */

import { onMounted, ref } from 'vue'
import { createSource, getSources } from '../api/sources'

// 列表状态
const sources = ref([])
const loading = ref(true)
const error = ref('')

// 新增表单状态
const creating = ref(false)
const formErrors = ref({})
const submitError = ref('')
const submitSuccess = ref('')

const form = ref({
  name: '',
  feed_url: '',
  // 后端当前要求 type 字段，这里默认使用 blog 以减少操作成本
  type: 'blog',
  is_enabled: true,
})

function resetForm() {
  form.value = {
    name: '',
    feed_url: '',
    type: 'blog',
    is_enabled: true,
  }
}

// 统一时间展示格式，保持和列表页一致
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

// 处理后端 422 校验错误，提取每个字段的首条提示
function normalizeValidationErrors(errors) {
  const normalized = {}
  Object.keys(errors || {}).forEach((key) => {
    const value = errors[key]
    normalized[key] = Array.isArray(value) ? value[0] : String(value)
  })
  return normalized
}

// 最小前端校验，避免空数据直接提交
function validateForm() {
  const nextErrors = {}
  if (!form.value.name.trim()) {
    nextErrors.name = '请输入来源名称'
  }
  if (!form.value.feed_url.trim()) {
    nextErrors.feed_url = '请输入订阅地址'
  }

  formErrors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

// 加载来源列表
async function fetchSources() {
  loading.value = true
  error.value = ''
  try {
    const response = await getSources()
    sources.value = response.data || []
  } catch (err) {
    error.value = err?.message || '加载来源失败'
    sources.value = []
  } finally {
    loading.value = false
  }
}

// 提交新增来源
async function submitForm() {
  if (creating.value) return
  submitError.value = ''
  submitSuccess.value = ''
  formErrors.value = {}

  if (!validateForm()) {
    return
  }

  creating.value = true
  try {
    const payload = {
      name: form.value.name.trim(),
      feed_url: form.value.feed_url.trim(),
      type: form.value.type,
      is_enabled: form.value.is_enabled,
    }
    await createSource(payload)
    submitSuccess.value = '来源已创建'
    resetForm()
    await fetchSources()
  } catch (err) {
    if (err?.status === 422 && err?.data?.errors) {
      // 友好展示后端校验错误
      formErrors.value = normalizeValidationErrors(err.data.errors)
      submitError.value = err?.data?.message || '请检查表单输入'
    } else {
      submitError.value = err?.message || '新增来源失败，请稍后重试'
    }
  } finally {
    creating.value = false
  }
}

onMounted(() => {
  fetchSources()
})
</script>

<template>
  <div class="source-list">
    <header class="page-header">
      <div>
        <h1>来源管理</h1>
        <p class="subtitle">维护 RSS/Atom 订阅源，便于后续采集</p>
      </div>
      <router-link to="/entries" class="back-link">← 返回列表</router-link>
    </header>

    <section class="form-card">
      <h2>新增来源</h2>
      <form class="source-form" @submit.prevent="submitForm">
        <div class="form-row">
          <label for="source-name">来源名称</label>
          <input
            id="source-name"
            v-model="form.name"
            type="text"
            placeholder="例如：Laravel News"
          >
          <p v-if="formErrors.name" class="field-error">{{ formErrors.name }}</p>
        </div>

        <div class="form-row">
          <label for="source-feed">Feed 地址</label>
          <input
            id="source-feed"
            v-model="form.feed_url"
            type="url"
            placeholder="https://example.com/feed.xml"
          >
          <p v-if="formErrors.feed_url" class="field-error">{{ formErrors.feed_url }}</p>
        </div>

        <div v-if="submitError" class="form-message error">{{ submitError }}</div>
        <div v-if="submitSuccess" class="form-message success">{{ submitSuccess }}</div>

        <button type="submit" :disabled="creating">
          {{ creating ? '提交中...' : '新增来源' }}
        </button>
      </form>
    </section>

    <section class="list-card">
      <div class="list-header">
        <h2>来源列表</h2>
        <button class="ghost-button" :disabled="loading" @click="fetchSources">
          刷新
        </button>
      </div>

      <!-- 采集提示：新增来源后执行采集命令 -->
      <p class="fetch-hint">
        采集提示：新增来源后，可在终端执行
        <code>docker compose exec app php artisan news:fetch-source &lt;来源ID&gt;</code>
        拉取条目（来源 ID 可在列表中查看）。
      </p>

      <!-- 加载态 -->
      <div v-if="loading" class="state-message loading">
        加载中...
      </div>

      <!-- 错误态 -->
      <div v-else-if="error" class="state-message error">
        <p>{{ error }}</p>
        <button @click="fetchSources">重试</button>
      </div>

      <!-- 空态 -->
      <div v-else-if="sources.length === 0" class="state-message empty">
        暂无来源，请先新增订阅源
      </div>

      <!-- 来源列表 -->
      <div v-else class="table-wrapper">
        <table class="source-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>名称</th>
              <th>Feed 地址</th>
              <th>启用状态</th>
              <th>最后采集</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="source in sources" :key="source.id">
              <td class="id">{{ source.id }}</td>
              <td class="name">{{ source.name }}</td>
              <td class="feed-url">
                <a :href="source.feed_url" target="_blank" rel="noopener">
                  {{ source.feed_url }}
                </a>
              </td>
              <td>
                <span :class="['status', source.is_enabled ? 'enabled' : 'disabled']">
                  {{ source.is_enabled ? '启用' : '停用' }}
                </span>
              </td>
              <td>{{ formatDate(source.last_fetched_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
.source-list {
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

.subtitle {
  margin: 6px 0 0;
  font-size: 14px;
  color: #666;
}

.back-link {
  display: inline-block;
  color: #42b883;
}

.form-card,
.list-card {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 20px;
  background: #fafafa;
  margin-bottom: 20px;
}

.form-card h2,
.list-card h2 {
  margin: 0 0 16px;
  font-size: 18px;
}

.source-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-row label {
  font-size: 14px;
  color: #555;
}

.form-row input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.field-error {
  font-size: 13px;
  color: #c00;
}

.form-message {
  font-size: 14px;
  padding: 8px 12px;
  border-radius: 4px;
}

.form-message.error {
  background: #ffecec;
  color: #c00;
}

.form-message.success {
  background: #eef9f1;
  color: #2f7a3f;
}

.source-form button {
  align-self: flex-start;
  padding: 8px 16px;
  background: #007bff;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.source-form button:disabled {
  background: #b3c7ff;
  cursor: not-allowed;
}

.list-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.fetch-hint {
  margin: 8px 0 16px;
  font-size: 13px;
  color: #555;
}

.fetch-hint code {
  margin: 0 4px;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid #eee;
  background: #fff;
  font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
  font-size: 12px;
}

.ghost-button {
  padding: 6px 12px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
}

.ghost-button:disabled {
  color: #999;
  cursor: not-allowed;
}

.state-message {
  text-align: center;
  padding: 32px 20px;
  color: #666;
}

.state-message.error {
  color: #c00;
}

.state-message.error button {
  margin-top: 12px;
  padding: 6px 12px;
  background: #007bff;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.table-wrapper {
  overflow-x: auto;
}

.source-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  background: #fff;
}

.source-table th,
.source-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #eee;
  text-align: left;
  vertical-align: top;
}

.source-table .id {
  width: 64px;
  color: #666;
}

.source-table th {
  font-size: 13px;
  color: #666;
  background: #fafafa;
}

.source-table .feed-url a {
  color: #007bff;
  word-break: break-all;
}

.status {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
}

.status.enabled {
  background: #e9f7ef;
  color: #1a7f37;
}

.status.disabled {
  background: #fcebea;
  color: #b42318;
}
</style>
