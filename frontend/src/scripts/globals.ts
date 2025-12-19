import { ensureCsrfCookie } from "../lib/auth"

declare global {
  interface Window {
    ensureCsrfCookie: () => Promise<void>
  }
}

window.ensureCsrfCookie = ensureCsrfCookie
