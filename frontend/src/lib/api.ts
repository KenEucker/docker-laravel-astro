// src/lib/api.ts
const API_URL = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000';

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
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };

  // Add CSRF token if available
  const csrfToken = getCsrfToken();
  if (csrfToken) {
    headers['X-XSRF-TOKEN'] = csrfToken;
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Request failed' }));
    throw new Error(error.message || `HTTP error! status: ${response.status}`);
  }

  return response.json();
}

export async function login(email: string, password: string) {
  // Get CSRF cookie first
  await fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
  });

  return apiRequest('/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
}

export async function register(name: string, email: string, password: string, password_confirmation: string) {
  await fetch(`${API_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
  });

  return apiRequest('/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password, password_confirmation }),
  });
}

export async function logout() {
  return apiRequest('/logout', {
    method: 'POST',
  });
}

export async function getUser() {
  return apiRequest('/api/user');
}

export async function getEvents() {
  return apiRequest('/api/events');
}