<script>
import {user} from "./user";

export default {
  name: "Header",
  data() {
    return {
      navHeight: 0,
    }
  },
  computed: {
    user() {
      return user
    },
  },
  props: {
    url_login: String,
    url_logout: String
  },
  methods: {},
  mounted() {
    this.navHeight = this.$refs['depot-nav'].offsetHeight + 1;
  }
}
</script>

<template>
  <nav ref="depot-nav" class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">

      <span type="button" id="btnMenuEnt" class="mdi mdi-dots-grid" style="font-size: 28px; margin-right: 2px"></span>

      <router-link class="navbar-brand" to="/scola">Doc Scola</router-link>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
              aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li v-if="user.isScola()" class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Relevés
            </a>
            <ul class="dropdown-menu">
              <li>
                <router-link class="dropdown-item" to="/scola/import/rn">Import</router-link>
              </li>
              <li>
                <router-link class="dropdown-item" to="/scola/monitoring/rn">Suivi</router-link>
              </li>
            </ul>
          </li>
          <li v-if="user.isScola()" class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Attestations
            </a>
            <ul class="dropdown-menu">
              <li>
                <router-link class="dropdown-item" to="/scola/import/attest">Import</router-link>
              </li>
              <li>
                <router-link class="dropdown-item" to="/scola/monitoring/attest">Suivi</router-link>
              </li>
            </ul>
          </li>
          <li v-if="user.isScola()" class="nav-item">
            <router-link active-class="active" class="nav-link" aria-current="page" to="/scola/search">Recherche
              étudiant
            </router-link>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <uca-menu
      data-opener="btnMenuEnt"
      data-opener-class="active"
      data-toggle-outside="true"
      :data-top="navHeight"
      data-client="DOCSCOLA"
      :data-connected=user.encryptedName
      :data-path-login="url_login"
      :data-path-logout="url_logout"/>
</template>

<style scoped>
.navbar {
  padding: 0.25rem 0;
}

#btnMenuEnt.active {
  color: #009dbd;
}

#btnMenuEnt {
  color: #fff;
}
</style>