import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { del, get, patch, post } from './client'

describe('api client', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('builds GET requests with query params and default headers', async () => {
    fetch.mockResolvedValue({
      ok: true,
      status: 200,
      json: vi.fn().mockResolvedValue({ data: [] }),
    })

    await get('/entries', { page: 2, q: 'vue news' })

    expect(fetch).toHaveBeenCalledWith('/api/entries?page=2&q=vue+news', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })
  })

  it('sends JSON body for POST and PATCH requests', async () => {
    fetch.mockResolvedValue({
      ok: true,
      status: 200,
      json: vi.fn().mockResolvedValue({ success: true }),
    })

    const sourcePayload = { name: 'Example', feed_url: 'https://example.com/rss' }
    await post('/sources', sourcePayload)
    await patch('/sources/1', { enabled: true })

    expect(fetch).toHaveBeenNthCalledWith(1, '/api/sources', {
      method: 'POST',
      body: JSON.stringify(sourcePayload),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })

    expect(fetch).toHaveBeenNthCalledWith(2, '/api/sources/1', {
      method: 'PATCH',
      body: JSON.stringify({ enabled: true }),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })
  })

  it('returns null for 204 responses', async () => {
    const json = vi.fn()
    fetch.mockResolvedValue({ ok: true, status: 204, json })

    const result = await del('/sources/1')

    expect(result).toBeNull()
    expect(json).not.toHaveBeenCalled()
  })

  it('throws structured error for non-2xx API responses', async () => {
    fetch.mockResolvedValue({
      ok: false,
      status: 422,
      json: vi.fn().mockResolvedValue({ message: 'Validation failed', errors: { name: ['required'] } }),
    })

    await expect(post('/sources', {})).rejects.toMatchObject({
      message: 'Validation failed',
      status: 422,
      data: { message: 'Validation failed', errors: { name: ['required'] } },
    })
  })

  it('prefixes network errors with localized message', async () => {
    fetch.mockRejectedValue(new Error('Failed to fetch'))

    await expect(get('/entries')).rejects.toMatchObject({
      message: '网络错误: Failed to fetch',
    })
  })
})
