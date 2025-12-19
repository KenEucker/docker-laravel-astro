import { ensureCsrfCookie } from "./auth";

// src/lib/api.ts
const API_URL = import.meta.env.PUBLIC_API_URL || "http://localhost:8000";

const FRONTEND_ORIGIN =
  import.meta.env.PUBLIC_SITE_URL || 'http://localhost:3000'

// Helper to get CSRF token from cookie
function getCsrfToken(): string | null {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  if (match) {
    return decodeURIComponent(match[1]);
  }
  return null;
}

export async function apiRequest(endpoint: string, options: RequestInit = {}) {
  const headers: HeadersInit = {
    Accept: "application/json",
    ...options.headers,
  };

  // Only set JSON content-type when we actually send a body
  if (options.body && !(headers as any)["Content-Type"]) {
    (headers as any)["Content-Type"] = "application/json";
  }

  // Add CSRF token if available
  const csrfToken = getCsrfToken();
  if (csrfToken) {
    (headers as any)["X-XSRF-TOKEN"] = csrfToken;
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
    credentials: "include",
  });

  // 204/205 = empty body by definition
  if (response.status === 204 || response.status === 205) {
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return null;
  }

  const contentType = response.headers.get("content-type") || "";
  const raw = await response.text(); // read once

  // Not OK: try to extract message from JSON, otherwise show raw
  if (!response.ok) {
    if (contentType.includes("application/json")) {
      try {
        const data = raw ? JSON.parse(raw) : {};
        throw new Error(
          data.message || `HTTP error! status: ${response.status}`
        );
      } catch {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
    }
    throw new Error(raw || `HTTP error! status: ${response.status}`);
  }

  // OK but empty
  if (!raw) return null;

  // OK and JSON
  if (contentType.includes("application/json")) {
    return JSON.parse(raw);
  }

  // OK but not JSON (helpful for debugging)
  return raw;
}

export async function login(email: string, password: string) {
  await ensureCsrfCookie()
  return apiRequest("/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
}

export async function register(
  name: string,
  email: string,
  password: string,
  password_confirmation: string
) {
  await ensureCsrfCookie()


  return apiRequest("/register", {
    method: "POST",
    body: JSON.stringify({ name, email, password, password_confirmation }),
  });
}

export async function logout() {
  return apiRequest("/logout", {
    method: "POST",
  });
}

export async function getUser() {
  return apiRequest("/api/user");
}

export async function getEvents() {
  return apiRequest("/api/events");
}

// Password management functions
export async function requestPasswordReset(email: string) {
  await ensureCsrfCookie()

  return apiRequest("/forgot-password", {
    method: "POST",
    credentials: "include",
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ email }),
  });
}

export async function resetPassword(
  email: string,
  password: string,
  password_confirmation: string,
  token: string
) {
  await ensureCsrfCookie()

  return apiRequest("/reset-password", {
    method: "POST",
    body: JSON.stringify({ email, password, password_confirmation, token }),
  });
}

export async function changePassword(
  current_password: string,
  password: string,
  password_confirmation: string
) {
  return apiRequest("/password", {
    method: "PUT",
    credentials: 'include',
    headers: {
      Accept: "application/json",
      Referer: `${FRONTEND_ORIGIN}/`,
    },
    body: JSON.stringify({ current_password, password, password_confirmation }),
  });
}
