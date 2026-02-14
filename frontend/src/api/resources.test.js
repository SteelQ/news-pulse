import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('./client', () => ({
  get: vi.fn(),
  post: vi.fn(),
  patch: vi.fn(),
  del: vi.fn(),
}))

import { fetchEntryContent, getEntries, getEntry } from './entries'
import { del, get, patch, post } from './client'
import {
  createSource,
  deleteSource,
  fetchAllSources,
  fetchSource,
  getSource,
  getSources,
  updateSource,
} from './sources'

describe('entries API wrappers', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('forwards entry endpoints and params to client methods', () => {
    getEntries({ page: 1, source_id: 3 })
    getEntry(8)
    fetchEntryContent(8)

    expect(get).toHaveBeenNthCalledWith(1, '/entries', { page: 1, source_id: 3 })
    expect(get).toHaveBeenNthCalledWith(2, '/entries/8')
    expect(post).toHaveBeenCalledWith('/entries/8/fetch-content')
  })
})

describe('sources API wrappers', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('maps source operations to expected REST endpoints', () => {
    const payload = { name: 'Factory', feed_url: 'https://example.com/feed.xml', type: 'blog' }

    getSources()
    getSource(2)
    createSource(payload)
    updateSource(2, { name: 'Factory Labs' })
    deleteSource(2)
    fetchSource(2)
    fetchAllSources()

    expect(get).toHaveBeenNthCalledWith(1, '/sources')
    expect(get).toHaveBeenNthCalledWith(2, '/sources/2')
    expect(post).toHaveBeenNthCalledWith(1, '/sources', payload)
    expect(patch).toHaveBeenCalledWith('/sources/2', { name: 'Factory Labs' })
    expect(del).toHaveBeenCalledWith('/sources/2')
    expect(post).toHaveBeenNthCalledWith(2, '/sources/2/fetch')
    expect(post).toHaveBeenNthCalledWith(3, '/sources/fetch-all')
  })
})
