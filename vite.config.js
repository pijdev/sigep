import { defineConfig } from 'vite';
import inject from '@rollup/plugin-inject';

export default defineConfig({
  base: '/dist/',
  plugins: [
    inject({
      $: 'jquery',
      jQuery: 'jquery',
    })
  ],
  build: {
    outDir: 'dist',
    chunkSizeWarningLimit: 10000,
    assetsDir: '',
    rollupOptions: {
      input: {
        main: 'src/main.js',
        style: 'src/style.css'
      },
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name].[ext]'
      }
    }
  }
});
