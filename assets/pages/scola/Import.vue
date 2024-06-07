<template>

  <h3 v-if="mode === 0">Extraction des relevés de notes</h3>
  <h3 v-if="mode === 1">Extraction des attestations de réussite</h3>

  <form class="mt-3" @submit.prevent="this.importRn()">

    <div class="mb-3">
      <label for="input_rn" class="form-label">{{ this.mode === 0 ? "Relevé de notes" : "Attestation" }}</label>
      <input type="file" accept="application/pdf" required @change="onPdfFileChange($event)" class="form-control"
             id="input_rn">
    </div>

    <div class="mb-3">
      <label for="input_etu" class="form-label">Fichier ETU</label>
      <input type="file" accept=".etu" required @change="onEtuFileChange($event)" class="form-control" id="input_etu">
    </div>

    <div v-if="mode === 0" class="mb-3">
      <label for="select_sem" class="form-label">Semestre</label>
      <select id="select_sem" required class="form-select" v-model="this.rn.sem">
        <option>1</option>
        <option>2</option>
        <option>A</option>
        <option>1p1</option>
        <option>1p2</option>
        <option>1p3</option>
        <option>2p1</option>
        <option>2p2</option>
        <option>2p3</option>
        <option>Ap1</option>
        <option>Ap2</option>
        <option>Ap3</option>
      </select>
    </div>

    <div v-if="mode === 0" class="mb-3">
      <label for="select_sess" class="form-label">Session</label>
      <select id="select_sess" required class="form-select" v-model="this.rn.sess">
        <option>1</option>
        <option>2</option>
        <option>U</option>
      </select>
    </div>

    <div v-if="mode === 0" class="mb-3">
      <label for="txt_lib" class="form-label">Libellé</label>
      <input type="text" required v-model="this.rn.lib" class="form-control" id="txt_lib"
             aria-describedby="emailHelp">
    </div>

    <div class="mb-3">
      <label for="input_tampon" class="form-label">Tampon</label>
      <input type="file" accept=".png" @change="onTamponFileChange($event)" class="form-control"
             id="input_tampon">
    </div>

    <div class="mb-3">
      <label for="txt_tampon" class="form-label">Numéro de page d'exemple pour tampon</label>
      <input type="number" min="1" required v-model="this.rn.numTampon" :disabled="this.rn.tampon === ''"
             class="form-control" id="txt_tampon">
    </div>

    <button type="submit" class="btn btn-primary" :disabled="!canSubmit">Charger</button>
  </form>

  <div v-if="!canSubmit" class="text-center">
    <div class="lds-ripple">
      <div></div>
      <div></div>
    </div>
  </div>

</template>

<script>
import WebService from "../../WebService";

export default {
  name: "Import",
  emits: ['imported'],
  props: {
    mode: Number
  },
  data() {
    return {
      rn: {
        pdf: "",
        etu: "",
        sem: this.mode === 0 ? "1" : "",
        sess: this.mode === 0 ? "1" : "",
        lib: "",
        tampon: "",
        numTampon: 1,
      },
      canSubmit: true,
    }
  },
  methods: {
    importRn(event) {
      this.canSubmit = false;
      WebService.importRn(this.mode, this.rn).then(response => {
        this.$emit('imported', response.data);
      }).catch(err => {

        // console.log(err.response.data.error);

        if (err.response.data.error !== undefined)
          alert(err.response.data.error);
        else
          alert("Une erreur est survenue lors de l'importation du relevé de notes");

        this.canSubmit = true;
      })
    },
    onPdfFileChange(event) {
      this.rn.pdf = event.target.files[0];
    },
    onEtuFileChange(event) {
      this.rn.etu = event.target.files[0];
    },
    onTamponFileChange(event) {
      this.rn.tampon = event.target.files[0];
    }
  },
  mounted() {
    console.log(this.mode);
  },
  watch: {
    mode() {
      this.rn.sem = this.mode === 0 ? "1" : "";
      this.rn.sess = this.mode === 0 ? "1" : "";
      // this.rn.pdf = "";
      // this.rn.etu = "";
      // this.rn.tampon = "";
    }
  }
}
</script>


<style scoped>
.lds-ripple {
  display: inline-block;
  position: relative;
  width: 80px;
  height: 80px;
}

.lds-ripple div {
  position: absolute;
  border: 4px solid #178F96;
  opacity: 1;
  border-radius: 50%;
  animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
}

.lds-ripple div:nth-child(2) {
  animation-delay: -0.5s;
}

@keyframes lds-ripple {
  0% {
    top: 36px;
    left: 36px;
    width: 0;
    height: 0;
    opacity: 0;
  }
  4.9% {
    top: 36px;
    left: 36px;
    width: 0;
    height: 0;
    opacity: 0;
  }
  5% {
    top: 36px;
    left: 36px;
    width: 0;
    height: 0;
    opacity: 1;
  }
  100% {
    top: 0px;
    left: 0px;
    width: 72px;
    height: 72px;
    opacity: 0;
  }
}
</style>