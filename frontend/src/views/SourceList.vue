<script setup>
/**
 * 来源管理页
 * 展示订阅源列表，支持新增和编辑
 */
</script>

<template>
  <div class="source-list">
    <h1>来源管理</h1>
    <p class="placeholder">来源管理将在 TASK-033 中实现（可选）</p>
    <router-link to="/entries" class="back-link">← 返回列表</router-link>
  </div>
</template>

<style scoped>
.source-list {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.placeholder {
  color: #666;
  font-style: italic;
}

.back-link {
  display: inline-block;
  margin-top: 20px;
  color: #42b883;
}
</style>
