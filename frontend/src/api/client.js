/**
 * API 请求客户端
 * 统一封装所有 HTTP 请求，baseURL 来自环境变量 VITE_API_BASE_URL
 */

// API 基础地址，从环境变量读取，未配置时默认使用同源 /api
const BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'

/**
 * 通用请求方法
 * @param {string} endpoint - API 端点路径（不含 baseURL）
 * @param {object} options - fetch 配置选项
 * @returns {Promise<any>} - 解析后的 JSON 响应
 * @throws {Error} - 请求失败时抛出错误，包含状态码和错误信息
 */
async function request(endpoint, options = {}) {
  const url = `${BASE_URL}${endpoint}`

  const config = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    },
    ...options,
  }

  try {
    const response = await fetch(url, config)

    // 处理非 2xx 响应
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      const error = new Error(errorData.message || `请求失败: ${response.status}`)
      error.status = response.status
      error.data = errorData
      throw error
    }

    // 204 No Content 不返回数据
    if (response.status === 204) {
      return null
    }

    return await response.json()
  } catch (error) {
    // 网络错误或其他异常
    if (!error.status) {
      error.message = `网络错误: ${error.message}`
    }
    throw error
  }
}

/**
 * GET 请求
 * @param {string} endpoint - API 端点
 * @param {object} params - 查询参数
 */
export function get(endpoint, params = {}) {
  const query = new URLSearchParams(params).toString()
  const url = query ? `${endpoint}?${query}` : endpoint
  return request(url, { method: 'GET' })
}

/**
 * POST 请求
 * @param {string} endpoint - API 端点
 * @param {object} data - 请求体数据
 */
export function post(endpoint, data = {}) {
  return request(endpoint, {
    method: 'POST',
    body: JSON.stringify(data),
  })
}

/**
 * PATCH 请求
 * @param {string} endpoint - API 端点
 * @param {object} data - 请求体数据
 */
export function patch(endpoint, data = {}) {
  return request(endpoint, {
    method: 'PATCH',
    body: JSON.stringify(data),
  })
}

/**
 * DELETE 请求
 * @param {string} endpoint - API 端点
 */
export function del(endpoint) {
  return request(endpoint, { method: 'DELETE' })
}

export default { get, post, patch, del }
