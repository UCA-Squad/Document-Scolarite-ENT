<template>

  <img alt="tampon" style="z-index: 1; position: absolute" id="draggable" draggable="true"
       v-on:mousedown="onMouseDown" :src="'/api/get_tampon/' + this.mode">


  <button class="btn btn-primary mb-2" type="button" v-on:click="applyTampon">Appliquer le tampon</button>

  <object v-on:dragover="allowDrop" id="pdf"
          :data="tamponExample + '#zoom=100&pagemode=none&scrollbar=0&toolbar=0&statusbar=1&messages=0&navpanes=0'"
          type="application/pdf" width="840px" height="1180px">
    <p>Unable to display PDF file.</p>
  </object>


</template>

<script>
import WebService from "../../WebService";

export default {
  name: "Tampon",
  props: {
    mode: Number
  },
  emits: ['tamponned'],
  data() {
    return {
      tamponExample: null,
      dragging: false,
      lastX: 0,
      lastY: 0,
    }
  },
  methods: {
    fetchTampon() {
      WebService.getTamponExample(this.mode).then(response => {
        console.log(response.data);
        this.tamponExample = URL.createObjectURL(response.data);
      }).catch(err => {
        console.log(err.response.data.error);
      });
    },
    onMouseMove(event) {
      if (!this.dragging) {
        event.preventDefault();
        return;
      }

      const dx = event.clientX - this.lastX;
      const dy = event.clientY - this.lastY;
      const img = document.getElementById('draggable');
      img.style.left = (img.offsetLeft + dx) + "px";
      img.style.top = (img.offsetTop + dy) + "px";
      this.lastX = event.clientX;
      this.lastY = event.clientY;

      console.log(img.style.left, img.style.top);

    },
    onMouseUp(event) {
      this.dragging = false;
    },
    onMouseDown(event) {
      this.dragging = true;
      this.lastX = event.clientX;
      this.lastY = event.clientY;
    },
    allowDrop(event) {
      event.preventDefault();
    },
    applyTampon() {
      var pdf_width = document.getElementById('pdf').getBoundingClientRect().width - 48; // -48 = -24 px de chaque coté
      var pdf_height = document.getElementById('pdf').getBoundingClientRect().height - 5 - 54; // -5 du top, -54 du bot

      let pdfRect = document.getElementById('pdf').getBoundingClientRect();
      let imgRect = document.getElementById('draggable').getBoundingClientRect();

      let position = {
        'x': imgRect.x - pdfRect.x - 24,
        'y': imgRect.y - pdfRect.y - 5,
      };

      let x = position.x * 210 / pdf_width;
      let y = position.y * 297 / pdf_height;

      console.log(x, y);

      WebService.applyTampon(x, y).then(response => {
        this.$emit('tamponned');
      }).catch(err => {
        console.log(err.response.data.error);
      });
    },
  },
  mounted() {
    this.fetchTampon();

    window.addEventListener('mousemove', this.onMouseMove);
    window.addEventListener('mouseup', this.onMouseUp);
  },
  beforeDestroy() {
    window.removeEventListener('mousemove', this.onMouseMove);
    window.removeEventListener('mouseup', this.onMouseUp);
  }
}
</script>

<style scoped>

</style>