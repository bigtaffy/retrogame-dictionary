import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const here = fileURLToPath(new URL('.', import.meta.url))

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    // 讓前端在 dev 用 /v2data/... 讀到後端掛的倉庫 data/*.json（與 v2 同路徑）
    proxy: {
      '/v2data': {
        target: 'http://127.0.0.1:8001',
        changeOrigin: true,
      },
    },
    fs: {
      allow: [path.resolve(here, '..', '..', '..')],
    },
  },
})
