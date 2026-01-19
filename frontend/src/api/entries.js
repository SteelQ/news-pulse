/**
 * Entry（条目）相关 API
 * 用于获取聚合内容列表和详情
 */

import { get } from './client'

/**
 * 获取条目列表
 * @param {object} params - 查询参数
 * @param {number} params.page - 页码
 * @param {number} params.per_page - 每页数量
 * @param {number} params.source_id - 按来源筛选
 * @param {string} params.q - 关键字搜索
 * @returns {Promise<object>} - 分页数据 { data, meta, links }
 */
export function getEntries(params = {}) {
  return get('/entries', params)
}

/**
 * 获取条目详情
 * @param {number|string} id - 条目 ID
 * @returns {Promise<object>} - 条目详情 { data }
 */
export function getEntry(id) {
  return get(`/entries/${id}`)
}
