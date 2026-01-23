<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
// App.vue is the root component
// It will render whatever route is active via router-view
</script>
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

#app {
  width: 100%;
  min-height: 100vh;
}
</style>

