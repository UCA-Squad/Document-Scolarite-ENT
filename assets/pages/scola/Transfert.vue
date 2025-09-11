<template>
  <!--  <div style="text-align: center">-->
  <!--    <h3 style="display: inline-block;margin: 0 auto">Transfert de-->
  <!--      {{ this.nbDocToTransfert }} / {{ this.students.length }} documents</h3>-->
  <!--  </div>-->

  <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 10px;">
    <div style="text-align: center; flex: 1;">
      <h3 style="display: inline-block; margin: 0 auto;">
        Transfert de {{ nbDocToTransfert }} / {{ students.length }} documents
      </h3>
    </div>
    <button v-if="templateMail !== null" v-on:click="rebuild()" class="btn btn-primary">
      Reconstruire
    </button>
  </div>

  <div v-if="!tranfert_done" class="row justify-content-md-center"
       style="margin-top: 17px;margin-bottom: 17px;width: 100%">
    <div class="progress" style="width: 75%;height: 35px">
      <div :class="{'progress-bar': true, 'progress-bar-striped': true, 'progress-bar-animated': !tranfert_done}"
           role="progressbar" :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100"
           :style="{width: percent + '%', visibility: 'visible', height: '35px'}">
        {{ percent }}%
      </div>
    </div>
  </div>

  <div v-if="templateMail !== null" class="container" style="width: 100%">
    <div style="border-style: outset; padding: 5px">
      <div class="row">
        <div class="col-2">
          <p>Expéditeur : </p>
        </div>
        <div class="col-6">
          <a href="mailto: noreply@uca.fr">noreply@uca.fr</a>
        </div>
      </div>
      <div class="row">
        <div class="col-2">
          <p>Sujet : </p>
        </div>
        <div class="col-6">
          <p>Dépôt de document Université Clermont Auvergne</p>
        </div>
      </div>
      <div class="row">
        <div class="col-2">
          <p>Contenu : </p>
        </div>
        <div class="col-6" v-html="templateMail">
        </div>
      </div>
    </div>

    <div style="text-align: center" class="mt-2 mb-2">
      <h5><strong>Voulez-vous envoyer un mail de notification aux étudiants concernés ?</strong></h5>
      <div class="row justify-content-md-center">
        <div class="col-auto">
          <button class="btn btn-primary" v-on:click="sendMails(true)" type="button">Oui
          </button>
        </div>
        <div class="col-auto">
          <button v-on:click="sendMails(false)" class="btn btn-secondary" type="button">Non
          </button>
        </div>
      </div>
    </div>

  </div>

</template>

<script>
import WebService from "../../WebService";
import {displayNotif} from "../../notyf";

export default {
  name: "Transfert",
  emits: ['finished'],
  props: {
    students: null,
    selectedRows: null,
    mode: Number
  },
  data() {
    return {
      index: 0, // index de 0 à selectedRows.length
      percent: 0, // 0 - 100 %
      tranfert_done: false,
      templateMail: null,
      nbDocToTransfert: 0,
      numsToTransfert: null,
    }
  },
  methods: {
    fetchMailTemplate() {
      WebService.fetchMailTemplate().then(response => {
        this.templateMail = response.data;
      }).catch(err => {
        console.log("fail");
      });
    },
    transfert() {

      WebService.transfertRn(this.numsToTransfert, this.mode).then(response => {
        this.numsToTransfert = response.data;
        this.index = this.nbDocToTransfert - this.numsToTransfert.length;
        this.percent = Math.floor((this.index / this.nbDocToTransfert) * 100);

        if (this.numsToTransfert.length > 0) {
          this.transfert();
        } else {
          this.tranfert_done = true;
          this.fetchMailTemplate();
        }
      }).catch(err => {
        alert("Une erreur est survenue lors du transfert des documents. Merci de réessayer plus tard.");
      });
    },
    sendMails(shouldSendMail) {
      if (!shouldSendMail) {
        this.$emit('finished');
        return;
      }
      WebService.sendMails(this.selectedRows).then(response => {
        alert("Les mails ont été envoyés");
        this.$emit('finished');
      }).catch(err => {
        console.log("fail");
      });
    },
    rebuild() {
      WebService.rebuild_after_transfert(this.selectedRows).then(response => {
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
      }).catch(err => {
        displayNotif('Reconstruction échouée', 'short_error')

      })
    }
  },
  mounted() {
    this.nbDocToTransfert = this.selectedRows.length;
    this.numsToTransfert = this.selectedRows;

    console.log(this.numsToTransfert);

    this.transfert();
  }
}
</script>

<style scoped>

</style>