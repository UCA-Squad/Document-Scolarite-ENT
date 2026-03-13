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
            <div class="alert alert-warning">La suppression est définitive, les étudiants n'auront plus accès aux
              documents
            </div>
            <div v-if="this.isFilesLoading" class="d-flex align-items-center gap-2 mb-2">
              <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
              <span>Chargement des fichiers...</span>
            </div>
            <div v-if="!this.isFilesLoading && Array.isArray(this.files) && this.files.length === 0" class="text-muted mb-2">
              Aucun document trouvé pour cet import.
            </div>
            <ag-grid-vue v-if="this.selected !== null"
                         :key="this.deleteGridKey"
                         class="ag-theme-alpine"
                         style="height: 60vh"
                         :columnDefs="columnEdit"
                         :rowData="Array.isArray(this.files) ? this.files : []"
                         :defaultColDef="defaultColDef"
                         :onSelectionChanged=onSelectionDeleteChanged
                         @grid-ready="onDeleteGridReady"
                         rowSelection="multiple"
                         pagination="true"
                         animateRows="true"
                         :ensureDomOrder="true"
                         :enableCellTextSelection="true"/>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="button" class="btn btn-primary" v-on:click="removeFiles"
                    :disabled="this.isFilesLoading || this.selectedDeleteRows === null || this.selectedDeleteRows.length === 0">Supprimer les
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
import {displayNotif} from "../../notyf";
import {user} from "../../user"; // Theme

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
      isFilesLoading: false,
      deleteGridApi: null,
      deleteGridKey: 0,
      deleteModalInstance: null,
      deleteModalEl: null,
      onDeleteModalHidden: null,
      onDeleteModalShown: null,
      activeImportId: null,
      filesRequestId: 0,
      filesAbortController: null,
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
      autoSizeStrategy: {type: 'fitCellContents', skipHeader: false},
      columnDefs: this.getColDefs(),
      columnHistorique: [
        {field: "formattedDate", headerName: "Date"},
        {
          headerName: "Nombre de fichiers", valueGetter: params => {
            return params.data.nb_files > 0 ? '+' + params.data.nb_files : params.data.nb_files /*+ " / " + this.selected.nb_students*/;
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
          headerName: 'Fichier', field: 'file',
          headerCheckboxSelection: true,
          checkboxSelection: true,
          headerCheckboxSelectionFilteredOnly: true, // Selectionner que les lignes filtrées
          showDisabledCheckboxes: true,
        },
        {field: "codeEtu", headerName: "Numéro étudiant"},
        {field: "nom", headerName: "Nom"},
        {field: "prenom", headerName: "Prénom"},
      ]
    }
  },
  methods: {
    onCellFocused(event) {
      // console.log(event.column.getColId());
      if (event.column.getColId() === 6) {
        event.api.deselectAll();
        event.api.clearFocusCell();
      }
    },
    getColDefs() {
      return [
        {
          field: "username",
          headerName: "Utilisateur",
          hide: this.monitoring?.every(m => m.username === user.name)
        },
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
          headerName: "Nombre de transferts", cellClass: 'text-center', valueGetter: params => {
            // console.log(params.data.history.slice(-1)[0].nb_files);
            return /*params.data.history.slice(-1)[0].nb_files + " / " +*/ params.data.nb_students;
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
          headerName: "Fichiers", floatingFilter: false, cellRenderer: BtnComponent, cellRendererParams: {
            onClicked: (data) => this.openDeleteModal(data),
            txt: "mdi mdi-file-remove mdi-24px",
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

      // console.log(data.id);
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
      const requestId = ++this.filesRequestId;

      if (this.filesAbortController) {
        this.filesAbortController.abort();
      }

      this.isFilesLoading = true;
      this.files = [];
      this.filesAbortController = new AbortController();

      WebService.fetchRnFiles(importId, {signal: this.filesAbortController.signal}).then(response => {
        // Ignore les réponses périmées quand l'utilisateur change vite d'import.
        if (requestId !== this.filesRequestId || this.activeImportId !== importId) return;
        this.files = response.data;
        this.$nextTick(() => this.refreshDeleteGrid());
      }).catch(error => {
        if (error?.code === 'ERR_CANCELED') return;
        console.log(error);
        alert("Erreur lors de la récupération des données");
      }).finally(() => {
        if (requestId === this.filesRequestId) {
          this.isFilesLoading = false;
          this.$nextTick(() => this.refreshDeleteGrid());
        }
      });
    },
    onDeleteGridReady(params) {
      this.deleteGridApi = params.api;
      this.refreshDeleteGrid();
    },
    refreshDeleteGrid() {
      if (!this.deleteGridApi) return;
      const rowData = Array.isArray(this.files) ? this.files : [];
      this.deleteGridApi.setGridOption('rowData', rowData);
      this.deleteGridApi.refreshCells({force: true});
      this.deleteGridApi.sizeColumnsToFit();
    },
    openDeleteModal(data) {
      this.selected = data;
      this.selectedDeleteRows = [];
      this.files = [];
      this.deleteGridApi = null;
      this.deleteGridKey += 1;
      this.activeImportId = data.id;

      const myModalEl = document.querySelector('#suppressionModal');
      this.deleteModalInstance = bootstrap.Modal.getOrCreateInstance(myModalEl);
      this.deleteModalInstance.show();

      this.fetchFiles(data.id);
    },
    removeFiles() {

      if (this.selectedDeleteRows === null || this.selectedDeleteRows.length === 0)
        return;

      if (!confirm('Voulez-vous vraiment supprimer ' + this.selectedDeleteRows.length + ' document(s) ?'))
        return;

      const numsEtu = this.selectedDeleteRows.map(r => r.codeEtu);
      // console.log(numsEtu);

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
    this.deleteModalEl = document.querySelector('#suppressionModal');
    if (this.deleteModalEl) {
      this.onDeleteModalShown = () => {
        this.$nextTick(() => this.refreshDeleteGrid());
      };
      this.onDeleteModalHidden = () => {
        if (this.filesAbortController) {
          this.filesAbortController.abort();
          this.filesAbortController = null;
        }
        this.deleteGridApi = null;
        this.activeImportId = null;
        this.selectedDeleteRows = [];
        this.files = null;
        this.isFilesLoading = false;
      };

      this.deleteModalEl.addEventListener('shown.bs.modal', this.onDeleteModalShown);
      this.deleteModalEl.addEventListener('hidden.bs.modal', this.onDeleteModalHidden);
    }
  },
  beforeUnmount() {
    if (this.deleteModalEl && this.onDeleteModalShown) {
      this.deleteModalEl.removeEventListener('shown.bs.modal', this.onDeleteModalShown);
    }
    if (this.deleteModalEl && this.onDeleteModalHidden) {
      this.deleteModalEl.removeEventListener('hidden.bs.modal', this.onDeleteModalHidden);
    }
  },
  beforeRouteLeave(to, from, next) {
    if (this.filesAbortController) {
      this.filesAbortController.abort();
    }

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