<template>

  <div class="container">
    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item active" :class="{'text-primary': step === 0, 'fw-bold': step === 0}"
            aria-current="page">Import
        </li>
        <li class="breadcrumb-item active" :class="{'text-primary': step === 1, 'fw-bold': step === 1}"
            aria-current="page">Tamponnage
        </li>
        <li class="breadcrumb-item active" :class="{'text-primary': step === 2, 'fw-bold': step === 2}"
            aria-current="page">Découpage
        </li>
        <li class="breadcrumb-item active" :class="{'text-primary': step === 3, 'fw-bold': step === 3}"
            aria-current="page">Sélection
        </li>
        <li class="breadcrumb-item active" :class="{'text-primary': step === 4, 'fw-bold': step === 4}"
            aria-current="page">Transfert
        </li>
      </ol>
    </nav>
  </div>

  <div :class="{'container': step !== 2, 'container-fluid': step === 2}">

    <Import :mode="mode" v-if="step === 0" v-on:imported="onDataImported"/>
    <Tampon :mode="mode" v-if="step === 1" v-on:tamponned="onTamponned"/>
    <Truncate :mode="mode" v-if="step === 2" :page-count="this.importData.pageCount"
              :page-index="this.importData.pageFirst"
              v-on:truncated="onDataTruncated"/>
    <Selection :mode="mode" v-if="step === 3" v-on:selected="onSelected"/>
    <Transfert :mode="mode" v-if="step === 4" :students="this.students" :selectedRows="this.selectedRows"
               v-on:finished="onTransfertFinished"/>
  </div>

</template>

<script>
import Import from "./Import.vue";
import Truncate from "./Truncate.vue";
import Selection from "./Selection.vue";
import Transfert from "./Transfert.vue";
import Tampon from "./Tampon.vue";

export default {
  name: "ImportDoc",
  components: {Tampon, Transfert, Selection, Truncate, Import},
  props: {
    mode: Number
  },
  data() {
    return {
      step: 0,              // Un nombre représentant l'étape actuelle
      importData: null,     // Les données importées par formulaire

      students: null,
      selectedRows: null
    }
  },
  methods: {
    onDataImported(data) {
      this.importData = data;

      if (this.importData.sameFiles === true && this.importData.sameParams)
        alert('Le nom des fichiers ainsi que les paramètres correspondent à un dépôt existant');
      else if (this.importData.sameFiles === true)
        alert('Le nom des fichiers correspondent à un dépôt existant');

      if (this.importData.step === "tampon")
        this.step = 1;
      else
        this.step = 2;
    },
    onDataTruncated() {
      this.step = 3;
    },
    onSelected(selectedRows, students, data) {
      this.step = 4;

      this.students = students;
      this.selectedRows = selectedRows;
    },
    onTamponned() {
      this.step = 2;
    },
    onTransfertFinished() {
      this.step = 5;
      if (this.mode === 0)
        this.$router.push({path: '/scola/monitoring/rn'});
      else if (this.mode === 1)
        this.$router.push({path: '/scola/monitoring/attest'});
    }
  },
  beforeRouteLeave(to, from, next) {
    if (this.step > 0 && this.step !== 5) {
      if (confirm("Voulez-vous vraiment quitter cette page ?")) {
        next();
      } else {
        next(false);
      }
    } else {
      next();
    }
  },
  mounted() {
    console.log(this.mode);
  }
}
</script>

<style scoped>

</style>