<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="28"
    height="28"
    viewBox="0 0 24 24"
    fill="none"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="heart-icon"
  >
    <path
      d="M12 21.5C6.8 16.9 2 13.3 2 8.5C2 5.4 4.4 3 7.5 3C9.3 3 10.9 4.1 12 5.5C13.1 4.1 14.7 3 16.5 3
      C19.6 3 22 5.4 22 8.5C22 13.3 17.2 16.9 12 21.5Z"
      :fill="isLiked ? '#FF3B30' : 'none'"
      :stroke="isLiked ? '#FF3B30' : '#D1D1D6'"
      :stroke-width="isLiked ? '0' : '2'"
    />
  </svg>
</template>
<script setup lang="ts">
defineProps<{ isLiked: boolean }>()
</script>