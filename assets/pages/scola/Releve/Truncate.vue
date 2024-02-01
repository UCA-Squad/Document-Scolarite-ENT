<script>
import WebService from "../../../WebService";

export default {
  name: "Truncate",
  emits: ['truncated'],
  props: {
    pageCount: 0,
    pageIndex: 0,
    mode: Number

  },
  data() {
    return {
      percent: 0,
    }
  },
  methods: {
    callTruncate(pageIndex) {
      WebService.truncate(pageIndex, this.mode).then(response => {
        const p = response.data;

        if (p > 0) {
          this.percent = Math.trunc(((p - 1) / this.pageCount) * 100);
          this.callTruncate(p);
        } else {
          this.$emit('truncated');
        }

      }).catch(err => {
        console.log("fail");
      });
    }
  },
  mounted() {
    this.callTruncate(this.pageIndex);
  }
}
</script>

<template>
  <div style="text-align: center">
    <h2 style="display: inline-block;margin: 0 auto">Découpage du document PDF</h2>
  </div>

  <div class="row justify-content-md-center" id="pg_container"
       style="margin-top: 17px;margin-bottom: 17px;width: 100%">
    <div class="progress" style="width: 75%;height: 35px">
      <div class="progress-bar progress-bar-striped progress-bar-animated" id="truncate_pg" role="progressbar"
           :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100"
           :style="{width: percent + '%', visibility: 'visible', height: '35px'}">
        {{ percent }}%
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>