<script>
export default {
  name: "HomeScola"
}
</script>

<template>
  <div class="container">

    <div class="row justify-content-md-center">
      <h2>Gestion des documents de scolarité</h2>
    </div>

    <div style="margin-top: 40px" class="row">

      <div class="col-sm">
        <div class="card text-white bg-primary">
          <div class="card-header">Relevés de notes</div>
          <div class="card-body">
            <h2 style="text-align: center">
              <router-link class="btn btn-secondary" to="/scola/import/rn">Importer des relevés de
                notes
              </router-link>
            </h2>
            <h2 style="text-align: center">
              <router-link class="btn btn-secondary" to="/scola/monitoring/rn">Suivi du dépôt des relevés de notes
              </router-link>
            </h2>
          </div>
        </div>
      </div>

      <div class="col-sm">
        <div class="card text-white bg-primary">
          <div class="card-header">Attestations de réussite</div>
          <div class="card-body">
            <h2 style="text-align: center">
              <router-link class="btn btn-secondary" to="/scola/import/attest">Importer des attestations de réussite
              </router-link>
            </h2>
            <h2 style="text-align: center">
              <router-link class="btn btn-secondary" to="/scola/monitoring/attest">Suivi du dépôt des
                attestations de réussite
              </router-link>
            </h2>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>

<style scoped>

</style>