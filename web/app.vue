<script setup lang="ts">
function handlePointerMove(e: PointerEvent) {
  const target = e.target as HTMLElement | null
  const card = target?.closest?.('.theme-fixed') as HTMLElement | null
  if (!card) return
  const rect = card.getBoundingClientRect()
  const halfW = (rect.width || 1) / 2
  const halfH = (rect.height || 1) / 2
  const dx = (e.clientX - (rect.left + halfW)) / halfW
  const dy = (e.clientY - (rect.top + halfH)) / halfH

  // CSS linear-gradient angle in degrees (0deg = top, 90deg = right, 180deg = bottom, 270deg = left)
  const angleDeg = Math.atan2(dx, -dy) * (180 / Math.PI)
  const dist = Math.min(1.2, Math.hypot(dx, dy))
  const pos = 50 + dist * 22

  card.style.setProperty('--mdeg', `${angleDeg.toFixed(1)}deg`)
  card.style.setProperty('--mpos', `${pos.toFixed(1)}%`)
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-page text-gray-900" @pointermove="handlePointerMove">
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <ConfirmModal />
  </div>
</template>
