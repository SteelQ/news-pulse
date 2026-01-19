<script setup>
/**
 * 内容详情页
 * 展示单个条目的详细信息，提供原文跳转链接
 */

import { useRoute } from 'vue-router'

const route = useRoute()
const entryId = route.params.id
</script>

<template>
  <div class="entry-detail">
    <h1>内容详情</h1>
    <p class="placeholder">条目 #{{ entryId }} 详情将在 TASK-032 中实现</p>
    <router-link to="/entries" class="back-link">← 返回列表</router-link>
  </div>
</template>

<style scoped>
.entry-detail {
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
