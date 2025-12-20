// src/lib/settings.ts
const API_INTERNAL = import.meta.env.API_INTERNAL_URL || 'http://laravel:8000'

interface PublicSettings {
  [key: string]: any
}

/**
 * Fetch public settings from the backend (server-side only)
 *
 * @param cookie - The cookie header from the request
 * @returns Object with public settings
 */
export async function getPublicSettings(cookie?: string): Promise<PublicSettings> {
  try {
    const headers: HeadersInit = {
      accept: 'application/json',
      'x-requested-with': 'XMLHttpRequest',
    }

    if (cookie) {
      headers.cookie = cookie
    }

    const res = await fetch(`${API_INTERNAL}/api/settings/public`, {
      headers,
      credentials: 'include',
    })

    if (!res.ok) {
      console.error(`Failed to fetch public settings: ${res.status}`)
      return {}
    }

    return await res.json()
  } catch (err) {
    console.error('Error fetching public settings:', err)
    return {}
  }
}

/**
 * Get a specific public setting value with fallback chain:
 * 1. Database setting (from settings object)
 * 2. Environment variable (import.meta.env)
 * 3. Provided default value
 *
 * @param settings - The settings object from getPublicSettings()
 * @param key - The setting key
 * @param defaultValue - Default value if setting not found in DB or env
 * @returns The setting value or default
 */
export function getSetting<T = any>(settings: PublicSettings, key: string, defaultValue: T): T {
  // First, check if setting exists in database
  if (settings[key] !== undefined) {
    return settings[key]
  }

  // Second, check environment variable
  const envValue = import.meta.env[key]
  if (envValue !== undefined) {
    return envValue as T
  }

  // Finally, use provided default
  return defaultValue
}
