<script>
import Header from "./Header.vue";

export default {
  name: "App",
  components: {Header},
  props: {
    url_login: String,
    url_logout: String
  },
}
</script>

<template>
  <Header :url_login="url_login" :url_logout="url_logout"/>
  <router-view/>
</template>

<style scoped>

</style>