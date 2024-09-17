<template>

  <div class="container-fluid">

    <!-- Historique Modal -->
    <div class="modal fade" id="historiqueModal" tabindex="-1"
         aria-labelledby="historiqueModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="historiqueModalLabel">Historique</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ag-grid-vue v-if="this.selected !== null"
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
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Suppression Modal -->
    <div class="modal fade" id="suppressionModal" tabindex="-1"
         aria-labelledby="suppressionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="suppressionModalLabel">Suppression de documents</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ag-grid-vue v-if="this.selected !== null"
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
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="button" class="btn btn-primary" v-on:click="removeFiles"
                    :disabled="this.selectedDeleteRows === null || this.selectedDeleteRows.length === 0">Supprimer les
              documents
            </button>
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
        @cellFocused="onCellFocused"
        :localeText="{noRowsToShow: 'Aucune donnée à afficher'}"
        :enableCellTextSelection="true">
    </ag-grid-vue>

  </div>

</template>

<script>
import {AgGridVue} from "ag-grid-vue3";
import WebService from "../../WebService";
import "ag-grid-community/styles/ag-grid.css"; // Core CSS
import "ag-grid-community/styles/ag-theme-alpine.css";
import {displayNotif} from "../../notyf"; // Theme

const BtnModalComponent = {
  template: `<button data-bs-toggle="modal" :data-bs-target="this.params.modal" data-bs-backdrop="true" class="btn btn-outline-secondary mt-1"
                style="height: 30px;width: 15px" v-on:click="this.params.onClicked(this.params.data)">
                    <span :class="this.params.txt"></span>
             </button>`
};

const BtnComponent = {
  template: `<button style="height: 30px;width: 15px" class="btn btn-outline-secondary mt-1" v-on:click="this.params.onClicked(this.params.data)">
                 <span :class="this.params.txt"></span>
            </button>`
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
      },
      columnDefs: this.getColDefs(),
      columnHistorique: [
        {field: "formattedDate", headerName: "Date"},
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
          headerName: 'Fichiers', valueGetter: params => {
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
    onCellFocused(event) {
      console.log(event.column.getColId());
      if (event.column.getColId() === 6) {
        event.api.deselectAll();
        event.api.clearFocusCell();
      }
    },
    getColDefs() {
      return [
        {field: "username", headerName: "Utilisateur"},
        {
          headerName: "Date de traitement", valueGetter: params => {
            return params.data.history.slice(-1)[0].formattedDate;
          }
        },
        // {
        //   headerName: "Fichiers traités", flex: 2, valueGetter: params => {
        //     return params.data.pdf_filename + " / " + params.data.etu_filename;
        //   }
        // },
        {
          headerName: this.mode === 0 ? "Année universitaire / Session / Semestre" : "Année universitaire",
          valueGetter: params => {
            if (this.mode === 0)
              return params.data.year + " / " + params.data.session + " / " + params.data.semestre;
            else
              return params.data.year;
          }
        },
        {
          headerName: "Code", valueGetter: params => {
            return params.data.type + " / " + params.data.code_obj + " / " + params.data.code;
          }
        },
        {
          headerName: "Libellé", valueGetter: params => {
            if (this.mode === 0)
              return params.data.libelle_form + " / " + params.data.libelle;
            else
              return params.data.libelle_obj + " / " + params.data.libelle;
          }
        },
        {
          headerName: "Nombre de transferts", valueGetter: params => {
            // console.log(params.data.history.slice(-1)[0].nb_files);
            return params.data.history.slice(-1)[0].nb_files + " / " + params.data.nb_students;
          }
        },
        {
          headerName: "Historique",
          floatingFilter: false,
          cellRenderer: BtnModalComponent,
          cellClassRules: {'non-selectable': true},
          cellRendererParams: {
            onClicked: (data) => this.selected = data,
            txt: "mdi mdi-history mdi-24px",
            modal: "#historiqueModal"
          }
        },
        {
          headerName: "Fichiers", floatingFilter: false, cellRenderer: BtnModalComponent, cellRendererParams: {
            onClicked: (data) => {
              this.selected = data;
              this.files = [];
              this.fetchFiles(data.id);
            },
            txt: "mdi mdi-file-remove mdi-24px",
            modal: "#suppressionModal"
          }
        },
        {
          headerName: "Reconstruction", floatingFilter: false, cellRenderer: BtnComponent, cellRendererParams: {
            onClicked: (data) => this.rebuildDoc(data),
            txt: "mdi mdi-file-multiple mdi-24px"
          }
        }
      ];
    },
    rebuildDoc(data) {

      displayNotif('Reconstruction en cours...', 'short_success')

      console.log(data.id);
      WebService.rebuild(data.id).then(response => {
        displayNotif('Reconstruction finie', 'short_success')
        const contentDispositionHeader = response.headers['content-disposition'];
        const fileName = contentDispositionHeader.split(';')[1].split('=')[1].trim().replace(/"/g, '');
        const url = URL.createObjectURL(response.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }).catch(error => {
        displayNotif('Reconstruction fail', 'short_error')
      });
    },
    fetchRnMonitoring() {
      WebService.getMonitoring(this.mode).then(response => {
        this.monitoring = response.data;
        // console.log(this.monitoring);
      }).catch(error => {
        console.log(error);
        displayNotif('Erreur lors de la récupération des données', 'short_error')
      });
    },
    fetchFiles(importId) {
      WebService.fetchRnFiles(importId).then(response => {
        // console.log(response.data);
        this.files = response.data;
      }).catch(error => {
        console.log(error);
        alert("Erreur lors de la récupération des données");
      });
    },
    removeFiles() {

      if (this.selectedDeleteRows === null || this.selectedDeleteRows.length === 0)
        return;

      const numsEtu = this.selectedDeleteRows.map(r => r.split('_')[0]);
      console.log(numsEtu);

      WebService.removeFiles(this.selected.id, numsEtu).then(response => {
        // console.log(response.data);
        const myModalEl = document.querySelector('#suppressionModal');
        const modal = bootstrap.Modal.getOrCreateInstance(myModalEl); // Returns a Bootstrap modal instance
        modal.hide();

        let index = this.monitoring.findIndex(f => f.id === response.data.id);
        if (index !== -1) {
          this.monitoring = [...this.monitoring.slice(0, index), response.data, ...this.monitoring.slice(index + 1)];
        }

        displayNotif(numsEtu.length + ' fichier(s) supprimé(s)', 'short_success');

      }).catch(error => {
        console.log(error);
      });

    },
    onSelectionDeleteChanged(event) {
      this.selectedDeleteRows = event.api.getSelectedRows();
      // console.log(this.selectedDeleteRows);
    },
  },
  beforeMount() {
    this.fetchRnMonitoring();
  },
  mounted() {
    console.log(this.mode);
  },
  beforeRouteLeave(to, from, next) {
    const myModalEl = document.querySelector('#suppressionModal');
    const modal = bootstrap.Modal.getOrCreateInstance(myModalEl);
    modal.hide();

    const myModalEl1 = document.querySelector('#historiqueModal');
    const modal1 = bootstrap.Modal.getOrCreateInstance(myModalEl1);
    modal1.hide();

    next();
  },
  watch: {
    mode() {
      this.columnDefs = this.getColDefs();
      this.monitoring = [];
      this.fetchRnMonitoring();
    }
  }
}
</script>

<style scoped>
</style>