import type { Config } from 'tailwindcss'
import defaultTheme from 'tailwindcss/defaultTheme'
import colors from 'tailwindcss/colors'

// Neutral colors come from CSS variables (assets/css/main.css), so the whole
// palette flips in dark mode without a `dark:` variant on every class.
const v = (name: string) => `rgb(var(--c-${name}) / <alpha-value>)`
const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]
const neutral = Object.fromEntries(shades.map(s => [s, v(`gray-${s}`)]))
const tinted = (name: string, base: Record<string, string>) => ({
  ...base,
  ...Object.fromEntries([50, 100, 200, 600, 700, 800].map(s => [s, v(`${name}-${s}`)])),
})

export default {
  darkMode: 'class',
  content: [
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './composables/**/*.{js,ts}',
    './plugins/**/*.{js,ts}',
    './app.vue',
    './error.vue',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
        mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
        serif: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
      },
      colors: {
        white: v('white'),
        page: v('page'),
        gray: neutral,
        zinc: neutral,
        red: tinted('red', colors.red),
        emerald: tinted('emerald', colors.emerald),
        amber: tinted('amber', colors.amber),
        indigo: tinted('indigo', colors.indigo),
        accent: {
          50: v('gray-50'),
          100: v('gray-100'),
          500: v('gray-900'),
          600: v('gray-900'),
          700: v('gray-950'),
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
  ],
} satisfies Config
