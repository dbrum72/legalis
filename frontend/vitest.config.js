import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    vue(),
  ],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  test: {
    globals: true,
    environment: 'jsdom',

    include: [
      'tests/**/*.spec.js',
      'tests/**/*.test.js',
    ],

    coverage: {
      provider: 'v8',
      reporter: [
        'text',
        'html',
        'lcov',
      ],

      include: [
        'src/**/*.{js,vue}',
      ],

      exclude: [
        'src/main.js',
        'src/router/**',
        'src/views/**',
        'src/playground/**',
      ],
    },
  },
})