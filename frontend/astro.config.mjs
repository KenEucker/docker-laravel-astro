import { defineConfig } from 'astro/config'
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  output: 'server',
  server: {
    host: true,
    port: 3000,
  },
  vite: {
    plugins: [tailwindcss()],
  },
})
