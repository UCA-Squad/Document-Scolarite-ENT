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
  methods: {},
  mounted() {
    this.navHeight = this.$refs['depot-nav'].offsetHeight + 1;
  }
}
</script>

<template>
        <nav ref="depot-nav" class="navbar navbar-expand-lg navbar-dark bg-primary">
          <div class="container-fluid">

            <div type="button" id="btnMenuEnt"
                 style="width: 28px; height: 28px; color: #fff; cursor: pointer; margin: 5px 8px; padding: 3px; display: flex; align-items: center; justify-content: center;">
              <span class="mdi mdi-dots-grid" style="font-size: 28px;"></span>
            </div>

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
                    <li><router-link class="dropdown-item" to="/scola/import/attest">Import</router-link></li>
                    <li><router-link class="dropdown-item" to="/scola/monitoring/attest">Suivi</router-link></li>
                  </ul>
                </li>
                <li v-if="user.isScola()" class="nav-item">
                  <router-link active-class="active" class="nav-link" aria-current="page" to="/scola/search">Recherche
                    étudiant
                  </router-link>
                </li>
              </ul>
<!--              <div class="d-flex">-->
<!--                <button class="btn btn-secondary" id="btnMenuEnt"><span class="mdi mdi-dots-grid"></span></button>-->
<!--              </div>-->
            </div>
          </div>
        </nav>

  <!--
  <div ref="depot-nav"
       style="width: 100%; height: 50px; display: flex; align-items: center; position: fixed; top: 0; left: 0; background-color: #006d82; box-shadow: 0px 5px 10px 0px #9c9c9c; justify-content: space-between;">
    <div style="display: flex; align-items: center;">
      <div type="button" id="btnMenuEnt"
           style="width: 28px; height: 28px; color: #fff; cursor: pointer; margin: 5px 8px; padding: 3px; display: flex; align-items: center; justify-content: center;">
        <span class="mdi mdi-dots-grid" style="font-size: 28px;"></span>
      </div>
      <router-link class="navbar-brand text-white" to="/scola">Doc Scola</router-link>
    </div>
  </div>
  -->


  <uca-menu
      data-opener="btnMenuEnt"
      data-opener-class="active"
      data-toggle-outside="true"
      :data-top="navHeight"
      data-client="DOCSCOLA"
      :data-connected=user.encryptedName
      data-path-login="https://ent.uca.fr/cas/login?service=http://depotdoc.infra.dsi.uca.fr/scola"
      data-path-logout="https://ent.uca.fr/cas/logout?service=http://depotdoc.infra.dsi.uca.fr/logout"/>
</template>

<style scoped>

</style>