<script setup>
/**
 * 根组件
 * 提供全局布局和导航
 */
</script>

<template>
  <div id="app">
    <!-- 顶部导航栏 -->
    <header class="app-header">
      <nav class="nav-container">
        <router-link to="/" class="logo">News Pulse</router-link>
        <div class="nav-links">
          <router-link to="/entries">内容列表</router-link>
          <router-link to="/sources">来源管理</router-link>
        </div>
      </nav>
    </header>

    <!-- 主内容区域 -->
    <main class="app-main">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.app-header {
  background: #1a1a1a;
  padding: 0 20px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 60px;
}

.logo {
  font-size: 1.25rem;
  font-weight: bold;
  color: #42b883;
  text-decoration: none;
}

.nav-links {
  display: flex;
  gap: 20px;
}

.nav-links a {
  color: #fff;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 4px;
  transition: background 0.2s;
}

.nav-links a:hover,
.nav-links a.router-link-active {
  background: rgba(255, 255, 255, 0.1);
}

.app-main {
  min-height: calc(100vh - 60px);
}
</style>
