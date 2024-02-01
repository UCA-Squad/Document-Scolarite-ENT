<script>
import Header from "./Header.vue";

export default {
  name: "App",
  components: {Header}
}
</script>

<template>
  <Header/>
  <router-view/>
</template>

<style scoped>

</style>