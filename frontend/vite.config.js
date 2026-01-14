import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  root: './',
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: '../public_html/assets',
    assetsDir: '.',
    emptyOutDir: true,
    rollupOptions: {
      input: './src/index.jsx',
      output: {
        entryFileNames: 'index.js',
        chunkFileNames: '[name].[hash].js',
        assetFileNames: '[name].[hash][extname]'
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  }
})
