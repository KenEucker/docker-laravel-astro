// src/lib/auth.ts
const API_PUBLIC = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000'
const API_INTERNAL = import.meta.env.API_INTERNAL_URL || 'http://laravel:8000'

const FRONTEND_ORIGIN =
  import.meta.env.PUBLIC_SITE_URL || 'http://localhost:3000'

export const requireAdmin = async (Astro: any, redirectTo = '/login') => {
  const user = await requireAuth(Astro)

  const roles = Array.isArray(user?.roles) ? user.roles : []
  if (!roles.includes('admin')) return Astro.redirect(redirectTo)

  return user
}

export const requireUser = async (Astro: any) => {
  const cookie = Astro.request.headers.get('cookie') ?? ''

  const res = await fetch(`${API_INTERNAL}/api/me`, {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      Cookie: cookie,
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

export const ensureCsrfCookie = async () => {
  await fetch(`${API_PUBLIC}/sanctum/csrf-cookie`, {
    credentials: "include",
    headers: {
      accept: "application/json",
      "x-requested-with": "XMLHttpRequest",
    },
  });
};
