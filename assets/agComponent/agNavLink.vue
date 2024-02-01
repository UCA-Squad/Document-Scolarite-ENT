<script>
export default {
  name: "agNavLink",
  data() {
    return {
      toPath: "/",
      toName: "",
    }
  },
  mounted() {
    this.toPath = `/student/${this.params.data.CLFDcodeEtu}`
    this.toName = this.params.data.CLFDcodeEtu[0];
  },
}
</script>

<template>
  <router-link :to="toPath">{{ toName }}</router-link>
</template>

<style scoped>

</style>