// src/lib/auth.ts
const PUBLIC_API = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000'
const API_INTERNAL = import.meta.env.API_INTERNAL_URL || 'http://laravel:8000'
const FRONTEND_REFERER =
  import.meta.env.PUBLIC_SITE_URL || 'http://localhost:3000'

export const requireAdmin = async (Astro: any, redirectTo = '/login') => {
  const user = await requireAuth(Astro)

  if (!user.is_admin) return Astro.redirect(redirectTo)

  return user
}

export const requireUser = async (Astro: any) => {
  const cookie = Astro.request.headers.get('cookie') ?? ''
  const publicHost = new URL(PUBLIC_API).host
  const publicProto = new URL(PUBLIC_API).protocol.replace(':', '')

  const res = await fetch(`${API_INTERNAL}/api/me`, {
    headers: {
      Accept: 'application/json',
      Cookie: cookie,
      Referer: `${FRONTEND_REFERER}/`,
      Host: publicHost,
      'X-Requested-With': 'XMLHttpRequest',
      'X-Forwarded-Host': publicHost,
      'X-Forwarded-Proto': publicProto,
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
  await fetch(`${PUBLIC_API}/sanctum/csrf-cookie`, {
    credentials: "include",
    headers: {
      accept: "application/json",
      "x-requested-with": "XMLHttpRequest",
    },
  });
};
