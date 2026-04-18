import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [vue()],
    publicDir: false,
    build: {
        outDir: 'public/iframe-widget',
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'resources/js/widget-iframe/iframe-entry.ts'),
            name: 'DaveyIframeWidget',
            fileName: () => 'iframe-widget.js',
            cssFileName: 'iframe-widget',
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
