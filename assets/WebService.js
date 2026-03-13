import axios from "axios";

class WebService {

    BASE_URL = '';

    getMonitoring(mode) {
        if (mode === 0)
            return axios.get(this.BASE_URL + "/api/monitoring/rn");
        return axios.get(this.BASE_URL + "/api/monitoring/attest");
    }

    searchStudent(searchFiled) {
        return axios.post(this.BASE_URL + "/api/scola/search", searchFiled);
    }

    getStudentDocs(num) {
        return axios.get(this.BASE_URL + "/api/etudiant/" + num);
    }

    importRn(mode, rn) {
        const formData = new FormData();

        formData.append("pdf", rn.pdf);
        formData.append("etu", rn.etu);
        formData.append("sem", rn.sem);
        formData.append("sess", rn.sess);
        formData.append("lib", rn.lib);
        formData.append("tampon", rn.tampon);
        formData.append("numTampon", rn.numTampon);

        if (mode === 0)
            return axios.post(this.BASE_URL + "/api/import/rn", formData, {headers: {"Content-Type": "multipart/form-data"}});

        return axios.post(this.BASE_URL + "/api/import/attests", formData, {headers: {"Content-Type": "multipart/form-data"}});
    }

    truncate(pageIndex, mode) {
        return axios.post(this.BASE_URL + "/api/import/truncate_unit", {
            'page': pageIndex,
            'mode': mode
        });
    }

    getSelection(mode) {
        if (mode === 0)
            return axios.get(this.BASE_URL + "/api/selection/rn");
        return axios.get(this.BASE_URL + "/api/selection/attest");
    }

    transfertRn(studentNums, mode) {
        return axios.post(this.BASE_URL + "/api/transfert/releves", {'nums': studentNums, 'mode': mode});
    }

    fetchMailTemplate() {
        return axios.get(this.BASE_URL + "/api/transfert/mail/template");
    }

    sendMail(studentsNum, mode) {
        return axios.post(this.BASE_URL + "/api/transfert/mail", {'ids': studentsNum, 'mode': mode});
    }

    getTamponExample(mode) {
        return axios.get(this.BASE_URL + "/api/get_tampon_example/" + mode, {responseType: 'blob'});
    }

    applyTampon(dx, dy) {
        return axios.post(this.BASE_URL + "/api/apply_tampon", {
            'dx': dx,
            'dy': dy
        });
    }

    fetchRnFiles(importId, config = {}) {
        return axios.get(this.BASE_URL + "/api/import/imported/" + importId, config);
    }

    removeFiles(id, nums) {
        return axios.post(this.BASE_URL + "/api/monitoring/delete", {
            'dataId': id,
            'numsEtu': nums
        });
    }

    sendMails(nums) {
        return axios.post(this.BASE_URL + "/api/transfert/mail", {
            'numsEtu': nums,
            'mode': 0
        });
    }

    getDownloadURL(mode) {
        if (mode === 0)
            return this.BASE_URL + "/api/etudiant/download/releve/";
        else
            return this.BASE_URL + "/api/etudiant/download/attest/";
    }

    getSrcTampon() {
        return this.BASE_URL + "/api/get_tampon/";
    }

    getPreviewTmpRn(num) {
        return this.BASE_URL + "/preview/tmp/releves/" + num;
    }

    getPreviewTmpAttest(num) {
        return this.BASE_URL + "/preview/tmp/attests/" + num;
    }

    getPreviewRn(num, index) {
        return this.BASE_URL + `/preview/releves/${num}/${index}`;
    }

    getPreviewAttest(num, index) {
        return this.BASE_URL + `/preview/attests/${num}/${index}`;
    }

    rebuild(importId) {
        return axios.get(this.BASE_URL + `/api/selection/rebuild/${importId}`, {responseType: 'blob'});
    }

    rebuild_after_transfert(selected_nums){
        return axios.post(this.BASE_URL + `/api/selection/rebuild_after_transfert`, selected_nums, {responseType: 'blob'});
    }

    getUsers() {
        return axios.get(this.BASE_URL + "/api/user/");
    }

    deleteUser(userId) {
        return axios.delete(this.BASE_URL + "/api/user/" + userId);
    }

    addUser(user) {
        return axios.post(this.BASE_URL + "/api/user/", {
            'user': user
        });
    }

    getGroups() {
        return axios.get(this.BASE_URL + "/api/user/group/");
    }

    deleteGroup(groupId) {
        return axios.delete(this.BASE_URL + "/api/user/group/" + groupId);
    }

    addGroup(group) {
        return axios.post(this.BASE_URL + "/api/user/group", {
            'group': group
        });
    }

    findUsers(user) {
        return axios.post(this.BASE_URL + "/api/user/search", {
            'user': user
        });
    }
}

export default new WebService();