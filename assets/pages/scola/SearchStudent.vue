<template>

  <div class="container">

    <div class="row justify-content-md-center">
      <h2>Rechercher les documents d'un étudiant</h2>
    </div>

    <div class="mt-3">
      <div class="mb-3">
        <label for="exampleFormControlInput1" class="form-label">Nom ou code étudiant</label>
        <input type="text" v-model="searchField" class="form-control" id="exampleFormControlInput1"
               placeholder="Jeant dupont | 2320215210">
      </div>

      <div class="mb-3">
        <button class="btn btn-primary" type="button" @click="searchStudent" :disabled="searchField === ''">Rechercher
        </button>
      </div>
    </div>

    <div class="ag-theme-alpine">
      <ag-grid-vue
          v-if="students !== null"
          class="ag-theme-alpine"
          style="height: 60vh"
          :columnDefs="columnDefs"
          :rowData="students"
          :defaultColDef="defaultColDef"
          pagination="true"
          animateRows="true"
          :ensureDomOrder="true"
          :enableCellTextSelection="true">
      </ag-grid-vue>
    </div>

  </div>

</template>

<script>
import WebService from "../../WebService";
import {AgGridVue} from "ag-grid-vue3";
import agNavLink from "../../agComponent/agNavLink.vue";

export default {
  name: "SearchStudent",
  components: {AgGridVue},
  data() {
    return {
      searchField: '',
      students: null,
      defaultColDef: {
        floatingFilter: true,
        sortable: true,
        filter: true,
        resizable: true,
        minWidth: 100,
        editable: false,
        flex: 1,
        menuTabs: ['columnsMenuTab'],
      },
      columnDefs: [
        {field: "CLFDcodeEtu", headerName: "Numéro", cellRenderer: agNavLink},
        {field: "sn", headerName: "Nom"},
        {field: "givenName", headerName: "Prénom"},
        {field: "supannEntiteAffectationPrincipale", headerName: "Composante"},
        {field: "nb_docs", headerName: "Nombre de documents"},
      ]
    }
  },
  methods: {
    searchStudent() {
      WebService.searchStudent(this.searchField).then((response) => {
        this.students = response.data;
      }).catch((error) => {
        console.log(error)
      });
    }
  },
}
</script>


<style scoped>

</style>