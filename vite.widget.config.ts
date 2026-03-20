import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    publicDir: false,
    build: {
        outDir: 'public/widget',
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'resources/js/widget/widget-entry.ts'),
            name: 'DaveyWidget',
            fileName: () => 'widget.js',
            formats: ['iife'],
        },
        rollupOptions: {
            output: {
                inlineDynamicImports: true,
            },
        },
        cssCodeSplit: false,
        minify: 'esbuild',
    },
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
});
