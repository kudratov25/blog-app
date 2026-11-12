/**
 * Error pages (404 etc.) rendered as 500 "temporarily unavailable".
 *
 * Nuxt builds the error from the request query, which gives objects with a
 * null prototype (no `hasOwnProperty`). When the payload is serialized,
 * Pinia's `skipHydrate` reducer calls `obj.hasOwnProperty(...)` on every
 * plain object and throws on those (pinia <= 2.3.1), so the error page itself
 * crashes. Copy them into ordinary objects before the payload is serialized.
 */
function toPlain(value: unknown, seen = new WeakSet<object>()): unknown {
  if (!value || typeof value !== 'object' || seen.has(value)) return value
  seen.add(value)

  if (Array.isArray(value)) {
    value.forEach((item, i) => { value[i] = toPlain(item, seen) })
    return value
  }

  const target: Record<string, unknown> = Object.getPrototypeOf(value) === null
    ? {}
    : value as Record<string, unknown>
  for (const key of Object.keys(value)) {
    target[key] = toPlain((value as Record<string, unknown>)[key], seen)
  }
  return target
}

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.hooks.hook('app:rendered', () => {
    const payload = nuxtApp.payload as Record<string, unknown>
    if (payload.error) payload.error = toPlain(payload.error)
  })
})
