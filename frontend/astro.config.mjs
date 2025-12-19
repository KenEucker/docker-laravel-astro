import { defineConfig } from "astro/config";
import vue from '@astrojs/vue';
import tailwindcss from "@tailwindcss/vite";
import { fileURLToPath } from 'url';

export default defineConfig({
  output: "server",
  server: {
    host: true,
    port: 3000,
  },
  integrations: [vue()],
  vite: {
    plugins: [tailwindcss()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      allowedHosts: ["intranet.local.test", "admin.local.test"],
    },
  },
});
