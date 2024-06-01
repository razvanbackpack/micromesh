import { defineConfig } from 'vite';

export default defineConfig(({command}) => {
  let configObject = {
    base: '/public',
    build: {
      manifest: true,
      emptyOutDir: false,
      outDir: 'public/', 
      rollupOptions: {
        input: ['resources/js/app.js'], 
        output: {
          entryFileNames: 'js/[name]-[hash].js', 
          assetFileNames: 'assets/build/[name].[ext]',  
          dir: 'public/',  
        },
      },
    },
    server: {
      open: false,
      port: 80, 
      watch: {
        usePolling: true,
        paths: ['resources/js/**/*'],
        ignoreInitial: true,  
      },
    }
  };

  if(command === 'serve') configObject.base = '/resources';

  return configObject;
});