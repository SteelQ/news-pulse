/**
 * Source（来源）相关 API
 * 用于管理 RSS/Atom 订阅源
 */

import { get, post, patch, del } from './client'

/**
 * 获取来源列表
 * @returns {Promise<object>} - 来源列表 { data }
 */
export function getSources() {
  return get('/sources')
}

/**
 * 获取来源详情
 * @param {number|string} id - 来源 ID
 * @returns {Promise<object>} - 来源详情 { data }
 */
export function getSource(id) {
  return get(`/sources/${id}`)
}

/**
 * 创建新来源
 * @param {object} data - 来源数据
 * @param {string} data.name - 来源名称
 * @param {string} data.feed_url - RSS/Atom 订阅地址
 * @param {string} data.type - 来源类型（blog/changelog 等）
 * @returns {Promise<object>} - 创建的来源 { data }
 */
export function createSource(data) {
  return post('/sources', data)
}

/**
 * 更新来源
 * @param {number|string} id - 来源 ID
 * @param {object} data - 更新数据
 * @returns {Promise<object>} - 更新后的来源 { data }
 */
export function updateSource(id, data) {
  return patch(`/sources/${id}`, data)
}

/**
 * 删除来源
 * @param {number|string} id - 来源 ID
 * @returns {Promise<null>}
 */
export function deleteSource(id) {
  return del(`/sources/${id}`)
}
