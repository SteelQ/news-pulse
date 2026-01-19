<script setup>
/**
 * 404 页面
 * 当访问不存在的路由时显示
 */
</script>

<template>
  <div class="not-found">
    <h1>404</h1>
    <p>抱歉，您访问的页面不存在</p>
    <router-link to="/" class="home-link">返回首页</router-link>
  </div>
</template>

<style scoped>
.not-found {
  text-align: center;
  padding: 60px 20px;
}

.not-found h1 {
  font-size: 72px;
  color: #ccc;
  margin-bottom: 10px;
}

.not-found p {
  color: #666;
  margin-bottom: 20px;
}

.home-link {
  color: #42b883;
}
</style>
