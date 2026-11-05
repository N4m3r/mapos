import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// base relativo: o build pode ser publicado em qualquer subpasta
// (ex.: https://seu-mapos.com/mobile/) sem recompilar.
export default defineConfig({
  base: './',
  plugins: [react()],
  build: {
    outDir: 'dist',
    sourcemap: false,
    target: 'es2020',
  },
  server: {
    port: 5180,
    host: true,
  },
});
