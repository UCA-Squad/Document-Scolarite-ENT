<template>
  <div v-if="this.docs !== null && (Object.keys(docs.rns).length > 0)" class="container">

    <div class="mt-2 mb-2">
      <h5>Vous avez des documents disponibles sur {{ Object.keys(docs.rns) }} années
        différentes</h5>
    </div>


    <div class="accordion" id="accordionPanelsStayOpenExample">
      <div v-for="(year, index) in Object.keys(docs.rns)" class="accordion-item mt-2">
        <h2 class="accordion-header" :id="'panelsStayOpen-heading' + year">
          <button class="accordion-button" type="button" data-bs-toggle="collapse"
                  :data-bs-target="'#panelsStayOpen-collapse' + year" aria-expanded="true"
                  :aria-controls="'panelsStayOpen-collapse' + year">
            {{ year }}
          </button>
        </h2>
        <div :id="'panelsStayOpen-collapse' + year"
             :class="'accordion-collapse collapse' + (index === 0 ? ' show' : '')"
             :aria-labelledby="'panelsStayOpen-heading' + year">
          <div class="accordion-body">
            <div class="row">
              <div class="col-6">
                <h6><strong>Relevés de notes</strong></h6>
                <ul>
                  <li v-for="rn in docs.rns[year]"><a
                      :href="WebService().getDownloadURL() + this.$route.params.num + '/'+ rn.index"
                      target="_blank">{{ rn.name }}</a></li>
                </ul>
              </div>
              <div class="col-6">
                <h6><strong>Attestations de réussite</strong></h6>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>


  </div>

  <div v-else class="container">
    <div class="alert alert-warning m-5"> Aucun document</div>
  </div>
</template>

<script>
import WebService from "../WebService";
import {user} from "../user";

export default {
  name: "StudentView",
  data() {
    return {
      docs: null,
    }
  },
  methods: {
    WebService() {
      return WebService
    },
    fetchDocs() {

      let num = 0;

      if (user.isEtudiant())
        num = user.numero;
      else
        num = this.$route.params.num;

      WebService.getStudentDocs(num).then(response => {
        this.docs = response.data;
        console.log(this.docs);
      }).catch(error => {
        console.log("Error fetching docs");
      })
    },
  },
  mounted() {
    this.fetchDocs();
  }
}
</script>


<style scoped>

</style>