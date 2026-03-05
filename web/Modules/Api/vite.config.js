import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";
import fs from "fs";
import os from "os";
import dotenv from "dotenv";

// Load environment variables from .env file
dotenv.config({ path: path.resolve(__dirname, "../../.env") });

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
                "resources/assets/sass/api.scss",
                "resources/assets/js/api.js",
            ],
            refresh: true,
            publicDirectory: "../../public",
            buildDirectory: "build-api",
            hotFile: "../../public/hot-api",
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        outDir: "../../public/build-api",
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
            "@api": path.resolve(__dirname, "./resources"),
            "@": path.resolve(__dirname, "../../resources"),
            $: "jquery",
            jquery: "jquery",
        },
    },
    server: {
        host: "0.0.0.0",
        port: 5175,
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
                "../../bootstrap/cache/**",
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
        preprocessorOptions: {
            scss: {
                quietDeps: true,
            },
        },
    },
});

