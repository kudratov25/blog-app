// PM2 config for the production Nuxt server (blog.kudratov.uz).
//
//   pm2 startOrRestart /var/www/blog-app/web/ecosystem.config.cjs --update-env
//   pm2 save
//
// The env lives here so a PM2 resurrect/restart can never lose it
// (otherwise the site falls back to the localhost apiBase in nuxt.config.ts).
const API = 'https://blog.kudratov.uz/api/v1'

module.exports = {
  apps: [
    {
      name: 'blog-web',
      // deploy.sh points this symlink at the newest release
      script: '/var/www/blog-web/current/server/index.mjs',
      exec_mode: 'fork',
      instances: 1,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: 'production',
        HOST: '127.0.0.1',
        PORT: 3001,
        NUXT_API_BASE: API,
        NUXT_PUBLIC_API_BASE: API,
        NUXT_TELEMETRY_DISABLED: 1,
      },
    },
  ],
}
