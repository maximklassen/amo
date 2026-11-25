import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },

  server: {
    port: 5173,
    proxy: {
      // Alle /api-Anfragen gehen an den PHP-Server (php -S localhost:8000 -t backend/public).
      // Für den Browser bleibt alles auf demselben Origin: kein CORS, keine Cookie-Probleme.
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },

  build: {
    outDir: 'dist',

    // Alternative für einfaches Hosting (ein Document Root):
    // direkt in das Backend bauen. emptyOutDir ist nötig, weil das Ziel außerhalb von frontend/ liegt.
    // outDir: '../backend/public/app',
    // emptyOutDir: true,
  },
})
