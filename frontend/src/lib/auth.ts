// src/lib/auth.ts
const API_PUBLIC = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000'
const API_INTERNAL = import.meta.env.API_INTERNAL_URL || 'http://laravel:8000'

const FRONTEND_ORIGIN =
  import.meta.env.PUBLIC_SITE_URL || 'http://localhost:3000'

export const requireUser = async (Astro: any) => {
  const cookie = Astro.request.headers.get('cookie') ?? ''

  console.log('Fetching user with cookies:', `${API_INTERNAL}/api/user`)    
  const res = await fetch(`${API_INTERNAL}/api/user`, {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      Cookie: cookie,

      // Helps Sanctum treat it as a SPA request
      Origin: FRONTEND_ORIGIN,
      Referer: `${FRONTEND_ORIGIN}/`,
    },
  })

  if (!res.ok) return null
  return await res.json()
}

export const requireAuth = async (Astro: any, redirectTo = '/login') => {
  const user = await requireUser(Astro)
  if (!user) return Astro.redirect(redirectTo)
  return user
}

export const redirectIfAuthed = async (Astro: any, redirectTo = '/dashboard') => {
  const user = await requireUser(Astro)
  if (user) return Astro.redirect(redirectTo)
  return null
}
