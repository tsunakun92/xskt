import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import fs from "fs";
import os from "os";
import dotenv from "dotenv";

// Load environment variables from .env file
dotenv.config();

// get host from env
const host = process.env.VITE_HOST;
const https = process.env.VITE_HOST_HTTPS;
const username = process.env.VITE_USERNAME;

// optional HTTPS config for Windows (Valet)
let httpsConfig = https;

if (os.platform() === "win32") {
    const keyPath = `C:\\Users\\${username}\\.config\\herd\\config\\valet\\Certificates\\${host}.key`;
    const certPath = `C:\\Users\\${username}\\.config\\herd\\config\\valet\\Certificates\\${host}.crt`;

    if (fs.existsSync(keyPath) && fs.existsSync(certPath)) {
        httpsConfig = {
            key: fs.readFileSync(keyPath),
            cert: fs.readFileSync(certPath),
        };
    }
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/custom.css",
            ],
            refresh: true,
            publicDirectory: "public",
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        outDir: "public/build",
        emptyOutDir: true,
        manifest: "manifest.json",
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                assetFileNames: "assets/[name]-[hash][extname]",
                chunkFileNames: "assets/[name]-[hash].js",
                entryFileNames: "assets/[name]-[hash].js",
            },
        },
        sourcemap: true,
        minify: false,
        target: "es2018",
    },
    resolve: {
        alias: {
            $: "jquery",
            jquery: "jquery",
        },
    },
    server: {
        host: "0.0.0.0",
        port: 5173,
        https: httpsConfig,
        cors: true,
        strictPort: true,
        hmr: {
            host: host,
            protocol: httpsConfig ? "wss" : "ws",
            overlay: true,
        },
        watch: {
            ignored: [
                "**/node_modules/**",
                "**/public/**",
                "**/storage/**",
                "**/vendor/**",
            ],
            usePolling: true,
            interval: 100,
        },
    },
    optimizeDeps: {
        include: ["jquery"],
        exclude: ["laravel-vite-plugin"],
    },
    css: {
        devSourcemap: true,
    },
});
