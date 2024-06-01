import { defineConfig } from 'vite';

export default defineConfig(({command}) => {
  let configObject = {
    base: '/public',
    build: {
      manifest: true,
      emptyOutDir: false,
      outDir: 'public/',  // Output directory for the build
      rollupOptions: {
        input: 'resources/js/app.js',  // Entry point for the build
        output: {
          entryFileNames: 'js/[name]-[hash].js',  // Add hash to JS file names
          assetFileNames: 'assets/[name].[ext]',  // Add hash to asset file names
          dir: 'public/',  // Output directory for assets
        },
      },
    },
    server: {
      open: false,
      port: 80,  // Consider using a different port for development
      watch: {
        usePolling: true,
        paths: ['resources/js/**/*'],
        ignoreInitial: true,  // Ignore initial build
      },
    }
  };

  if(command === 'serve') configObject.base = '/resources';

  return configObject;
});