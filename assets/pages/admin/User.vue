<template>

  <!-- Modal USER -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">Ajouter un utilisateur</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="this.findUsers()">
            <div class="mb-3 d-flex">
              <div class="flex-grow-1">
                <label for="username" class="form-label">Nom d'utilisateur</label>
                <input type="text" class="form-control" placeholder="identifiant | nom" id="username"
                       v-model="newUsername" required>
              </div>
              <div class="ms-2 align-self-end">
                <button type="submit" class="btn btn-primary">Rechercher</button>
              </div>
            </div>
          </form>
          <ag-grid-vue v-if="searchUsers !== null"
                       class="ag-theme-alpine mt-1"
                       style="height: 60vh"
                       :columnDefs="searchUsersColumnDefs"
                       :rowData="this.searchUsers"
                       :defaultColDef="defaultColDef"
                       pagination="true"
                       animateRows="true"
                       :ensureDomOrder="true"
                       :localeText="{noRowsToShow: 'Aucune donnée à afficher'}"
                       :enableCellTextSelection="true"
                       rowSelection="single"
                       :onSelectionChanged=onSearchUsersSelectionChanged
                       :suppressRowClickSelection="true">
          </ag-grid-vue>
          <button v-if="selectedSearchUser !== null" class="btn btn-primary mt-1" v-on:click="this.addUser()">Ajouter
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal GROUP -->
  <div class="modal fade" id="addGroupModal" tabindex="-1" aria-labelledby="addGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addGroupModalLabel">
            {{ this.newGroup.id === undefined ? 'Ajouter un groupe' : 'Mettre à jour le groupe' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="this.addGroup()">
            <div class="mb-3">
              <label for="groupLibelle" class="form-label">Libellé</label>
              <input type="text" class="form-control" id="groupLibelle" v-model="newGroup.libelle" required>
            </div>
            <div class="mb-3">
              <ag-grid-vue class="ag-theme-alpine"
                           ref="groupUsersGrid"
                           style="height: 400px; width: 100%;"
                           :columnDefs="groupUsersColumnDefs"
                           :rowData="this.users"
                           :defaultColDef="defaultColDef"
                           pagination="true"
                           animateRows="true"
                           :rowClassRules=this.userClassRules
                           :ensureDomOrder="true"
                           :localeText="{noRowsToShow: 'Aucune donnée à afficher'}"
                           :enableCellTextSelection="true">
              </ag-grid-vue>
            </div>
            <button type="submit" class="btn btn-primary">
              {{ this.newGroup.id === undefined ? 'Ajouter' : 'Mettre à jour' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-2">
    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button"
                role="tab" aria-controls="tab1" aria-selected="true">Utilisateurs
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab"
                aria-controls="tab2" aria-selected="false">Groupes
        </button>
      </li>
    </ul>
    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
        <h3>Liste des utilisateurs</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal" v-on:click="onAddUser()">
          Ajouter
        </button>
        <ag-grid-vue
            ref="usersGrid"
            class="ag-theme-alpine mt-1"
            style="height: 60vh"
            :columnDefs="usersColumnDefs"
            :rowData="this.users"
            :defaultColDef="defaultColDef"
            pagination="true"
            animateRows="true"
            :ensureDomOrder="true"
            :rowClassRules=this.userClassRules
            :localeText="{noRowsToShow: 'Aucune donnée à afficher'}"
            :enableCellTextSelection="true">
        </ag-grid-vue>
      </div>
      <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
        <h3>Liste des groupes</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" v-on:click="openGroupModal()"
                data-bs-target="#addGroupModal">Ajouter
        </button>

        <ag-grid-vue
            class="ag-theme-alpine mt-1"
            ref="groupsGrid"
            style="height: 60vh"
            :columnDefs="groupsColumnDefs"
            :rowData="groups"
            :defaultColDef="defaultColDef"
            pagination="true"
            animateRows="true"
            :ensureDomOrder="true"
            :localeText="{noRowsToShow: 'Aucune donnée à afficher'}"
            :enableCellTextSelection="true">
        </ag-grid-vue>

      </div>
    </div>
  </div>
</template>

<script>
import {AgGridVue} from "ag-grid-vue3";
import WebService from "../../WebService";
import {displayNotif} from "../../notyf";
import {user} from "../../user";

const BtnComponent = {
  template: `<button style="height: 30px;width: 15px" class="btn btn-outline-secondary mt-1" v-if="this.params.render" v-on:click="this.params.onClicked(this.params.data)">
                 <span :class="this.params.txt"></span>
            </button>`
};

const CheckboxComponent = {
  template: `<input type="checkbox" :checked="this.params.value" :disabled="this.params.disabled"
                    v-on:change="this.params.onChanged(this.params.data)">`
};

const GroupLinkRenderer = {
  template: `<span><a v-for="(grp, index) in this.params.groups" href="#"
                      @click.prevent="this.params.openGroup(grp)">{{ grp.libelle }}<span
      v-if="index < this.params.groups.length - 1">, </span></a></span>`
};

export default {
  name: "User",
  components: {AgGridVue},
  data() {
    return {
      users: null, // Liste des utilisateurs
      copyUsers: null, // Copie des utilisateurs
      searchUsers: null, // Liste des utilisateurs recherchés par uid ou sn pour ajout
      selectedSearchUser: null, // Utilisateur sélectionné pour ajout
      groups: null, // Liste des groupes
      newUsername: '', // username pour recherche dans le ldap
      newGroup: { // DTO pour création d'un groupe
        libelle: '',
        userGroups: []
      },
      userClassRules: {
        'old-user': (params) => params.data.old
      },
      defaultColDef: {
        floatingFilter: true,
        sortable: true,
        filter: true,
        resizable: true,
        minWidth: 100,
        editable: false,
        flex: 1,
      },
      searchUsersColumnDefs: [
        {field: "username", headerName: "Pseudo", checkboxSelection: true},
        {field: "nom", headerName: "Nom"},
        {field: "prenom", headerName: "Prénom"},
        {field: "composante", headerName: "Composante"}
      ],
      usersColumnDefs: this.getUsersColumnDefs(),
      groupsColumnDefs: [
        {field: "libelle", headerName: "Libellé"},
        {
          headerName: "Responsables",
          valueGetter: params => params.data.userGroups.filter(userGroup => userGroup.responsable).map(userGroup => userGroup.username).join(', ')
        },
        {
          headerName: "Utilisateurs",
          valueGetter: params => params.data.userGroups.filter(userGroup => !userGroup.responsable).map(userGroup => userGroup.username).join(', ')
        },
        {
          headerName: "", cellRenderer: BtnComponent, cellRendererParams: {
            onClicked: (data) => {
              this.newGroup = data;
              const modal = new bootstrap.Modal(document.getElementById('addGroupModal'));
              modal.show();
            },
            txt: "mdi mdi-pencil mdi-24px",
            render: true
          },
          width: 110,
          minWidth: 110,
          maxWidth: 110,
          floatingFilter: false,
        },
        {
          headerName: "", cellRenderer: BtnComponent, cellRendererParams: (params) => ({
            onClicked: (data) => {
              // console.log(params);
              this.deleteGroup(data);
            },
            txt: "mdi mdi-delete mdi-24px",
            render: true
          }),
          width: 110,
          minWidth: 110,
          maxWidth: 110,
          floatingFilter: false,
        },
      ],
      groupUsersColumnDefs: this.getGroupUsersColumnDefs()
    }
  },
  watch: {
    newGroup: {
      handler(newVal, oldVal) {
        // console.log("newGroup changed => getGroupUsersColumnDefs");
        this.groupUsersColumnDefs = this.getGroupUsersColumnDefs();
      },
      deep: false
    },
    users: {
      handler() {
        // console.log("users changed => getGroupUsersColumnDefs");
        // this.groupUsersColumnDefs = this.getGroupUsersColumnDefs();
        this.$nextTick(() => {
          if (this.$refs.usersGrid) {
            this.$refs.usersGrid.api.setGridOption('rowData', this.users);
          }
          if (this.$refs.groupUsersGrid) {
            this.$refs.groupUsersGrid.api.setGridOption('rowData', this.users);
          }
        });
      },
      deep: true
    },
  },
  methods: {
    getUsersColumnDefs() {
      return [
        {field: "username", headerName: "Pseudo"},
        {field: "nom", headerName: "Nom"},
        {field: "prenom", headerName: "Prénom"},
        {field: "composante", headerName: "Composante"},
        {
          headerName: "Responsable dans groupe",
          cellRenderer: GroupLinkRenderer,
          cellRendererParams: params => ({
            openGroup: (grp) => {
              // console.log(grp);
              this.newGroup = grp;
              const modal = new bootstrap.Modal(document.getElementById('addGroupModal'));
              modal.show();
            },
            groups: this.groups.filter(group => group.userGroups.some(userGroup => userGroup.responsable &&
                userGroup.username === params.data.username))
          }),
          filterValueGetter: params => this.groups.filter(group => group.userGroups.some(userGroup => userGroup.responsable &&
              userGroup.username === params.data.username)).map(group => group.libelle).join(', '),
          comparator: (valueA, valueB, nodeA, nodeB, isDescending) => {
            let libelleA = this.groups.filter(group => group.userGroups.some(userGroup => userGroup.responsable &&
                userGroup.username === nodeA.data.username)).map(group => group.libelle).join(', ');
            let libelleB = this.groups.filter(group => group.userGroups.some(userGroup => userGroup.responsable &&
                userGroup.username === nodeB.data.username)).map(group => group.libelle).join(', ');
            return libelleA === libelleB ? 0 : libelleA === '' ? 1 : libelleB === '' ? -1 : libelleA < libelleB ? -1 : 1;
          }
        },
        {
          headerName: "Utilisateur dans groupe",
          cellRenderer: GroupLinkRenderer,
          cellRendererParams: params => ({
            openGroup: (grp) => {
              // console.log(grp);
              this.newGroup = grp;
              const modal = new bootstrap.Modal(document.getElementById('addGroupModal'));
              modal.show();
            },
            groups: this.groups.filter(group => group.userGroups.some(userGroup => userGroup.user &&
                userGroup.username === params.data.username))
          }),
          filterValueGetter: params => this.groups.filter(group => group.userGroups.some(userGroup => userGroup.user &&
              userGroup.username === params.data.username)).map(group => group.libelle).join(', '),
          comparator: (valueA, valueB, nodeA, nodeB, isDescending) => {
            let libelleA = this.groups.filter(group => group.userGroups.some(userGroup => userGroup.user &&
                userGroup.username === nodeA.data.username)).map(group => group.libelle).join(', ');
            let libelleB = this.groups.filter(group => group.userGroups.some(userGroup => userGroup.user &&
                userGroup.username === nodeB.data.username)).map(group => group.libelle).join(', ');
            return libelleA === libelleB ? 0 : libelleA === '' ? 1 : libelleB === '' ? -1 : libelleA < libelleB ? -1 : 1;
          }
        },
        {
          headerName: "", cellRenderer: BtnComponent, cellRendererParams: (params) => ({
            onClicked: (data) => {
              this.deleteUser(data);
            },
            txt: "mdi mdi-delete mdi-24px",
            render: !params.data.old
          }),
          width: 110,
          minWidth: 110,
          maxWidth: 110,
          floatingFilter: false,
          comparator: (valueA, valueB, nodeA, nodeB, isDescending) => {
            let userAIsOld = nodeA.data.old;
            let userBIsOld = nodeB.data.old;
            return userAIsOld === userBIsOld ? 0 : userAIsOld ? 1 : -1;
          }
        },
      ];
    },
    getGroupUsersColumnDefs() {
      // console.log("getGroupUsersColumnDefs");
      return [
        {field: "username", headerName: "Pseudo"},
        {
          headerName: "Nom",
          valueGetter: (params) => this.users.find(user => user.username === params.data.username)?.nom || ''
        },
        {
          headerName: "Prénom",
          valueGetter: params => this.users.find(user => user.username === params.data.username)?.prenom || ''
        },
        {
          headerName: "Composante",
          valueGetter: params => this.users.find(user => user.username === params.data.username)?.composante || ''
        },
        {
          headerName: "Responsable", cellRenderer: CheckboxComponent, cellRendererParams: (params) => ({
            onChanged: (data) => {
              let uGrp = this.newGroup.userGroups.find(u => u.username === data.username);
              if (uGrp !== undefined) {
                uGrp.responsable = !uGrp.responsable;
                if (!uGrp.responsable && !uGrp.user) {
                  this.newGroup.userGroups = this.newGroup.userGroups.filter(u => u.username !== data.username);
                }
              } else {
                this.newGroup.userGroups.push({id: data.id, username: data.username, responsable: true, user: false});
              }
              this.$refs.groupUsersGrid.api.refreshCells();
            },
            value: this.newGroup.userGroups.some(u => u.responsable && u.username === params.data.username),
            disabled: this.newGroup.userGroups.some(u => u.user && u.username === params.data.username) ||
                this.users.some(user => user.username === params.data.username && user.old)
          }), comparator: (valueA, valueB, nodeA, nodeB, isDescending) => {
            let isCheckA = this.newGroup.userGroups.some(u => u.responsable && u.username === nodeA.data.username)
            let isCheckB = this.newGroup.userGroups.some(u => u.responsable && u.username === nodeB.data.username)

            if (isCheckA && isCheckB) {
              let userA = this.users.find(user => user.username === nodeA.data.username);
              let userB = this.users.find(user => user.username === nodeB.data.username);
              return userA.nom === userB.nom ? userA.prenom === userB.prenom ? 0 : userA.prenom < userB.prenom ? -1 : 1 : userA.nom < userB.nom ? -1 : 1;
            } else if (isCheckA) {
              return -1;
            } else if (isCheckB) {
              return 1;
            } else {
              return 0;
            }
          },
          sort: 'asc',
          sortIndex: 1
        },
        {
          headerName: "Utilisateur", cellRenderer: CheckboxComponent, cellRendererParams: (params) => ({
            onChanged: (data) => {
              const uGrp = this.newGroup.userGroups.find(u => u.username === data.username);
              if (uGrp !== undefined) {
                uGrp.user = !uGrp.user;
                if (!uGrp.responsable && !uGrp.user) {
                  this.newGroup.userGroups = this.newGroup.userGroups.filter(u => u.username !== data.username);
                }
              } else {
                this.newGroup.userGroups.push({id: data.id, username: data.username, responsable: false, user: true});
              }
              this.$refs.groupUsersGrid.api.refreshCells();
            },
            value: this.newGroup.userGroups.some(u => u.user && u.username === params.data.username),
            disabled: this.newGroup.userGroups.some(u => u.responsable && u.username === params.data.username)
          }), comparator: (valueA, valueB, nodeA, nodeB, isDescending) => {
            let isCheckA = this.newGroup.userGroups.some(u => u.user && u.username === nodeA.data.username)
            let isCheckB = this.newGroup.userGroups.some(u => u.user && u.username === nodeB.data.username)

            if (isCheckA && isCheckB) {
              let userA = this.users.find(user => user.username === nodeA.data.username);
              let userB = this.users.find(user => user.username === nodeB.data.username);
              return userA.nom === userB.nom ? userA.prenom === userB.prenom ? 0 : userA.prenom < userB.prenom ? -1 : 1 : userA.nom < userB.nom ? -1 : 1;
            } else if (isCheckA) {
              return -1;
            } else if (isCheckB) {
              return 1;
            } else {
              return 0;
            }
          },
          sort: 'asc',
          sortIndex: 2
        },
      ];
    },
    openGroupModal() {
      this.newGroup = {
        libelle: '',
        userGroups: []
      };
    },
    onAddUser() {
      this.newUsername = '';
      this.searchUsers = null;
    },
    onSearchUsersSelectionChanged(event) {
      this.selectedSearchUser = event.api.getSelectedRows()[0];
      // console.log(this.selectedSearchUser);
    },
    findUsers() {
      WebService.findUsers(this.newUsername).then(response => {
        this.searchUsers = response.data;
      }).catch(error => {
        displayNotif('Erreur lors de la récupération des utilisateurs', 'short_error')
      });
    },
    fetchUsers() {
      WebService.getUsers().then(response => {
        this.users = response.data;
      }).catch(error => {
        displayNotif('Erreur lors de la récupération des utilisateurs', 'short_error')
      });
    },
    deleteUser(userData) {
      if (!confirm("Voulez-vous vraiment supprimer l'utilisateur [" + userData.username + "] ?")) {
        return;
      }

      WebService.deleteUser(userData.id).then(response => {
        displayNotif('Utilisateur [' + userData.username + '] supprimé', 'short_success');

        if (!response.data.old)
          this.users = this.users.filter(user => user.username !== userData.username);
        else
          this.users.find(user => user.username === userData.username).old = true;

        this.fetchGroups();
      }).catch(error => {
        displayNotif('Erreur lors de la suppression de l\'utilisateur', 'short_error')
      });
    },
    addUser() {
      WebService.addUser(this.selectedSearchUser.username).then(response => {
        displayNotif('Utilisateur [' + this.newUsername + '] ajouté', 'short_success');
        const existingUser = this.users.find(user => user.username === response.data.username);
        if (existingUser) {
          existingUser.old = false;
          existingUser.id = response.data.id;
        } else {
          this.users.push(response.data);
        }
        this.newUsername = '';
        const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
        modal.hide();

      }).catch(error => {
        console.log(error.response.data.message);
        displayNotif(error.response.data.message, 'short_error')
      });
    },
    fetchGroups() {
      return WebService.getGroups().then(response => {
        this.groups = response.data;
      }).catch(error => {
        displayNotif('Erreur lors de la récupération des groupes', 'short_error')
      });
    },
    deleteGroup(groupData) {
      if (!confirm("Voulez-vous vraiment supprimer le groupe [" + groupData.libelle + "] ?")) {
        return;
      }

      WebService.deleteGroup(groupData.id).then(response => {
        displayNotif('Groupe [' + groupData.libelle + '] supprimé', 'short_success');
        this.groups = this.groups.filter(grp => grp.id !== groupData.id);
        this.$refs.usersGrid.api.refreshCells();

      }).catch(error => {
        displayNotif('Erreur lors de la suppression du groupe', 'short_error')
      });
    },
    addGroup() {
      // console.log(this.newGroup);
      WebService.addGroup(this.newGroup).then(response => {
        displayNotif('Groupe [' + this.newGroup.libelle + '] ajouté', 'short_success');
        if (this.newGroup.id === undefined) {
          this.groups.push(response.data);
        }
        this.$refs.usersGrid.api.refreshCells();

        const modal = bootstrap.Modal.getInstance(document.getElementById('addGroupModal'));
        modal.hide();
      }).catch(error => {
        console.log(error.response.data.message);
        if (error.response.data.message !== undefined)
          displayNotif(error.response.data.message, 'short_error')
        else
          displayNotif("Erreur", 'short_error')
      });
    },
  },
  async beforeMount() {
    await this.fetchGroups();
    this.fetchUsers();
  }
}
</script>

<style>
.old-user {
  background-color: #efefef;
}
</style>