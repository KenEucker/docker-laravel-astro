/**
 * Blocks API Client
 *
 * Provides functions to fetch content blocks from the backend API.
 */

const API_URL = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000'

export interface BlockData {
  key: string
  type: string
  data: any
  updated_at: string
  status?: string
  published_at?: string | null
}

export interface BlocksListResponse {
  blocks: Array<{
    key: string
    type: string
    updated_at: string
  }>
}

/**
 * Fetch a single block by key.
 *
 * @param key - The unique block key (e.g., "homepage.hero")
 * @param preview - Whether to use preview mode (requires signed URL)
 * @returns The block data or null if not found
 */
export async function fetchBlock(key: string, preview = false): Promise<BlockData | null> {
  try {
    const url = `${API_URL}/api/content/blocks/${key}${preview ? '?preview=1' : ''}`
    const response = await fetch(url)

    if (!response.ok) {
      if (response.status === 404) {
        return null
      }
      throw new Error(`Failed to fetch block: ${response.statusText}`)
    }

    return await response.json()
  } catch (error) {
    console.error(`Error fetching block "${key}":`, error)
    return null
  }
}

/**
 * Fetch multiple blocks by key prefix.
 *
 * @param prefix - The key prefix to filter by (e.g., "homepage.")
 * @param limit - Maximum number of blocks to fetch
 * @returns List of block metadata
 */
export async function fetchBlocks(prefix?: string, limit = 50): Promise<BlocksListResponse> {
  try {
    const params = new URLSearchParams()
    if (prefix) params.set('prefix', prefix)
    params.set('limit', limit.toString())

    const url = `${API_URL}/api/content/blocks?${params}`
    const response = await fetch(url)

    if (!response.ok) {
      throw new Error(`Failed to fetch blocks: ${response.statusText}`)
    }

    return await response.json()
  } catch (error) {
    console.error('Error fetching blocks:', error)
    return { blocks: [] }
  }
}
