<template>

  <div class="container-fluid">

    <!-- Historique Modal -->
    <div v-if="this.selected !== null" class="modal fade" id="historiqueModal" tabindex="-1"
         aria-labelledby="historiqueModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="historiqueModalLabel">Historique</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ag-grid-vue
                class="ag-theme-alpine"
                style="height: 40vh"
                :columnDefs="columnHistorique"
                :rowData="this.selected.history"
                :defaultColDef="defaultColDef"
                pagination="true"
                animateRows="true"
                :ensureDomOrder="true"
                :enableCellTextSelection="true"/>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Suppression Modal -->
    <div v-if="this.selected !== null" class="modal fade" id="suppressionModal" tabindex="-1"
         aria-labelledby="suppressionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="suppressionModalLabel">Suppression de documents</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ag-grid-vue
                class="ag-theme-alpine"
                style="height: 60vh"
                :columnDefs="columnEdit"
                :rowData="this.files"
                :defaultColDef="defaultColDef"
                :onSelectionChanged=onSelectionDeleteChanged
                rowSelection="multiple"
                pagination="true"
                animateRows="true"
                :ensureDomOrder="true"
                :enableCellTextSelection="true"/>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" v-on:click="removeFiles">Supprimer les documents</button>
          </div>
        </div>
      </div>
    </div>

    <h3 v-if="this.mode === 0" class="text-center">Suivi des Relevés de notes</h3>
    <h3 v-if="this.mode === 1" class="text-center">Suivi des Attestations de réussite</h3>

    <ag-grid-vue
        class="ag-theme-alpine"
        style="height: 85vh"
        :columnDefs="columnDefs"
        :rowData="monitoring"
        :defaultColDef="defaultColDef"
        pagination="true"
        animateRows="true"
        :ensureDomOrder="true"
        :enableCellTextSelection="true">
    </ag-grid-vue>

  </div>

</template>

<script>
import {AgGridVue} from "ag-grid-vue3";
import WebService from "../../WebService";
import "ag-grid-community/styles/ag-grid.css"; // Core CSS
import "ag-grid-community/styles/ag-theme-alpine.css"; // Theme

const BtnModalComponent = {
  template: `<button data-bs-toggle="modal" :data-bs-target="this.params.modal" class="btn btn-secondary mt-1"
                style="height: 30px"
                v-on:click="this.params.onClicked(this.params.data)">{{ this.params.txt }}</button>`
};

const BtnComponent = {
  template: '<button class="btn btn-secondary" v-on:click="this.params.onClicked(this.params.data)">{{ this.params.txt }}</button>'
};

export default {
  name: "MonitoringDoc",
  props: {
    mode: Number
  },
  components: {AgGridVue},
  data() {
    return {
      selected: null,
      selectedDeleteRows: null,
      files: null,
      monitoring: null,
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
        {field: "username", headerName: "Utilisateur"},
        {
          headerName: "Date de traitement", valueGetter: params => {
            return params.data.history.slice(-1)[0].date;
          }
        },
        {
          headerName: "Fichiers traités", flex: 2, valueGetter: params => {
            return params.data.pdf_filename + " / " + params.data.etu_filename;
          }
        },
        {
          headerName: "Année universitaire / Session / Semestre", valueGetter: params => {
            return params.data.year + " / " + params.data.session + " / " + params.data.semestre;
          }
        },
        {
          headerName: "Code", valueGetter: params => {
            return params.data.type + " / " + params.data.code_obj + " / " + params.data.code;
          }
        },
        {
          headerName: "Libellé", valueGetter: params => {
            return params.data.libelle_form + " / " + params.data.libelle;
          }
        },
        {
          headerName: "Nombre de transferts", valueGetter: params => {
            // console.log(params.data.history.slice(-1)[0].nb_files);
            return params.data.history.slice(-1)[0].nb_files + " / " + params.data.nb_students;
          }
        },
        {
          headerName: "Historique", floatingFilter: false, cellRenderer: BtnModalComponent, cellRendererParams: {
            onClicked: (data) => this.selected = data,
            txt: "Voir",
            modal: "#historiqueModal"
          }
        },
        {
          headerName: "Suppression", editable: false, cellRenderer: BtnModalComponent, cellRendererParams: {
            onClicked: (data) => {
              this.selected = data;
              this.fetchFiles(data.id);
            },
            txt: "Edit",
            modal: "#suppressionModal"
          }
        }
      ],
      columnHistorique: [
        {field: "date", headerName: "Date"},
        {
          headerName: "Nombre de fichiers", valueGetter: params => {
            return params.data.nb_files + " / " + this.selected.nb_students;
          }
        },
        {
          headerName: "Action", editable: false, valueGetter: params => {
            if (params.data.state === 1) return 'Dépot initial';
            else if (params.data.state === 2) return 'Dépot supplémentaire';
            else if (params.data.state === 3) return 'Suppression';
            else return 'Erreur';
          }
        },
      ],
      columnEdit: [
        {
          headerName: 'fichier', valueGetter: params => {
            return params.data;
          },
          headerCheckboxSelection: true,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: true, // Selectionner que les lignes filtrées
          showDisabledCheckboxes: true,
        },
      ]
    }
  },
  methods: {
    fetchRnMonitoring() {
      WebService.getMonitoring(this.mode).then(response => {
        this.monitoring = response.data;
        console.log(this.monitoring);
      }).catch(error => {
        console.log(error);
        alert("Erreur lors de la récupération des données");
      });
    },
    fetchFiles(importId) {
      WebService.fetchRnFiles(importId).then(response => {
        console.log(response.data);
        this.files = response.data;
      }).catch(error => {
        console.log(error);
        alert("Erreur lors de la récupération des données");
      });
    },
    removeFiles() {
      const numsEtu = this.selectedDeleteRows.map(r => r.split('_')[0]);
      console.log(numsEtu);

      WebService.removeFiles(this.selected.id, numsEtu).then(response => {
        console.log(response.data);
      }).catch(error => {
        console.log(error);
      });

    },
    onSelectionDeleteChanged(event) {
      this.selectedDeleteRows = event.api.getSelectedRows();
      console.log(this.selectedDeleteRows);
    },
  },
  beforeMount() {
    this.fetchRnMonitoring();
  },
  mounted() {
    console.log(this.mode);
  },
  watch: {
    mode() {
      this.fetchRnMonitoring();
    }
  }
}
</script>

<style scoped>

</style>