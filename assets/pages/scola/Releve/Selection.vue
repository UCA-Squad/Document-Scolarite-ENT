<template>

  <!--  HEADER INFO-->
  <div v-if="this.data !== null" class="mt-2 row justify-content-md-center opaker">
    <div class="form-group col-sm-2">
      <label class="control-label">Semestre</label>
      <input v-model="this.data.semestre" class="form-control" style="text-align: center" name="session" type="text"
             disabled>
    </div>
    <div class="form-group col-sm-2">
      <label class="control-label">Session</label>
      <input v-model="this.data.session" class="form-control" style="text-align: center" type="text" disabled>
    </div>
    <div class="form-group col-sm-2">
      <label class="control-label">Libellé</label>
      <input class="form-control" style="text-align: center" type="text" disabled
             :value="data.libelle === '' ? data.libelle_form : data.libelle">
    </div>
    <div class="form-group col-sm-2">
      <label class="control-label">Code</label>
      <input class="form-control" style="text-align: center" type="text" disabled
             :value="data.code === '' ? data.code_obj : data.code ">
    </div>
    <div class="form-group col-sm-2">
      <label class="control-label">Année</label>
      <input v-model="this.data.year" class="form-control" style="text-align: center" type="text" disabled>
    </div>
  </div>


  <!--  BUTTONS-->
  <div id="transfert_div" class="row justify-content-md-center mt-2">
    <button type="button" class="btn btn-primary" id="btn_go" @click="OnGO">Continuer</button>
  </div>

  <!--  TABLEAU-->
  <ag-grid-vue
      class="ag-theme-alpine"
      style="height: 75vh"
      :columnDefs="columnDefs"
      :rowData="this.students"
      :defaultColDef="defaultColDef"
      pagination="true"
      suppressRowClickSelection="true"
      rowSelection="multiple"
      animateRows="true"
      :ensureDomOrder="true"
      :onSelectionChanged=onSelectionChanged
      :enableCellTextSelection="true">
  </ag-grid-vue>

</template>

<script>
import WebService from "../../../WebService";
import {AgGridVue} from "ag-grid-vue3";
import "ag-grid-community/styles/ag-grid.css"; // Core CSS
import "ag-grid-community/styles/ag-theme-alpine.css"; // Theme

export default {
  name: "Selection",
  props: {
    mode: Number
  },
  components: {AgGridVue},
  emits: ['selected'],
  data() {
    return {
      data: null,
      students: null,
      selectedRows: null,
      defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        minWidth: 100,
        editable: false,
        flex: 1,
      },
      columnDefs: [
        {
          field: "numero",
          headerName: "Numero",
          headerCheckboxSelection: true,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: true, // Selectionner que les lignes filtrées
          showDisabledCheckboxes: true,
        },
        {field: "name", headerName: "Nom"},
        {field: "surname", headerName: "Prenom"},
        {field: "birthday", headerName: "Date de naissance"},
        {field: "mail", headerName: "Mail"},
        {
          headerName: "Relevé", flex: 2, cellRenderer: params => {
            return `<a target="_blank" href="/preview/tmp/releves/${params.data.numero}">${params.data.file}</a>`;
          }
        },
        {
          headerName: "Relevé déjà existant", cellStyle: {textAlign: 'center'}, cellRenderer: params => {
            if (params.data.index !== -1)
              return `<a target="_blank" href="/preview/releves/${params.data.numero}/${params.data.index}">Voir</a>`;
          }
        },
      ]
    }
  },
  methods: {
    fetchSelection() {
      WebService.getSelection(this.mode).then(response => {
        console.log(response.data);
        this.data = response.data.data;
        this.students = response.data.students;
      }).catch(err => {
        console.log("fail");
      });
    },
    onSelectionChanged(event) {
      this.selectedRows = event.api.getSelectedRows();
    },
    OnGO() {
      this.$emit('selected', this.selectedRows.map(r => r.numero), this.students, this.data);
    },
  },
  mounted() {
    this.fetchSelection();
  }
}
</script>


<style scoped>

</style>