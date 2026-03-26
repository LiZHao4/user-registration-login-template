<template>
  <div class="waterfall-container" ref="container" :style="containerStyle">
    <div 
      v-for="(article, index) in articles" 
      :key="article.id" 
      class="waterfall-item"
      :ref="el => { if (el) itemRefs[index] = el }"
      :style="getItemStyle(index)"
    >
      <ArticleCard
        :id="article.id"
        :avatar="article.avatar"
        :nickname="article.nickname"
        :publishTime="article.publishTime"
        :title="article.title"
        :commentCount="article.commentCount"
        :likeCount="article.likeCount"
        :content="article.content"
        :images="article.images"
      />
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import ArticleCard from './ArticleCard.vue';
const props = defineProps({
  gap: {
    type: Number,
    default: 20
  }
});
const container = ref(null);
const columnCount = ref(0);
const columnHeights = ref([]);
const itemPositions = ref([]);
const itemRefs = ref([]);
const containerHeight = ref(0);
const windowWidth = ref(window.innerWidth);
const articles = ref([]);
const containerStyle = computed(() => {
  return {
    height: `${containerHeight.value}px`,
    position: 'relative'
  };
});
const updateColumnCount = () => {
  const width = window.innerWidth;
  windowWidth.value = width;
  if (width < 600) columnCount.value = 1;
  else if (width < 800) columnCount.value = 2;
  else if (width < 1100) columnCount.value = 3;
  else if (width < 1400) columnCount.value = 4;
  else columnCount.value = 5;
  nextTick(() => {
    calculateLayout();
  });
};
const calculateLayout = () => {
  if (!articles.value.length) return;
  columnHeights.value = new Array(columnCount.value).fill(0);
  itemPositions.value = [];
  articles.value.forEach((_, index) => {
    const itemElement = itemRefs.value[index];
    if (!itemElement) return;
    const itemHeight = itemElement.offsetHeight;
    const minHeight = Math.min(...columnHeights.value);
    const columnIndex = columnHeights.value.indexOf(minHeight);
    const itemWidth = getItemWidth();
    const left = columnIndex * (itemWidth + props.gap);
    const top = columnHeights.value[columnIndex];
    columnHeights.value[columnIndex] += itemHeight + props.gap;
    itemPositions.value.push({ left, top, width: itemWidth });
  });
  containerHeight.value = Math.max(...columnHeights.value);
};
const getItemWidth = () => {
  if (!container.value) return 0;
  const containerWidth = container.value.offsetWidth;
  return (containerWidth - (columnCount.value - 1) * props.gap) / columnCount.value;
};
const getItemStyle = index => {
  const position = itemPositions.value[index];
  if (!position) return { visibility: 'hidden' };
  return {
    position: 'absolute',
    left: `${position.left}px`,
    top: `${position.top}px`,
    width: `${position.width}px`,
    visibility: 'visible'
  };
};
const handleResize = () => {
  updateColumnCount();
};
watch(articles, () => {
  nextTick(() => {
    calculateLayout();
  });
}, { deep: true, immediate: true });
onMounted(() => {
  updateColumnCount();
  window.addEventListener('resize', handleResize);
});
onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});
</script>
<style scoped>
.waterfall-container {
  max-width: 1400px;
  margin: 0 auto;
}
.waterfall-item {
  transition: all 0.3s ease;
  box-sizing: border-box;
}
</style>