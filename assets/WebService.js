import axios from "axios";

class WebService {

    getMonitoring(mode){
        if (mode === 0)
            return axios.get("/monitoring/api/rn");
        return axios.get("/monitoring/api/attest");
    }

    getRnMonitoring() {
        return axios.get("/monitoring/api/rn")
    }

    getAttestMonitoring() {
        return axios.get("/monitoring/api/attest")
    }

    searchStudent(searchFiled) {
        return axios.post("/api/search", searchFiled);
    }

    getStudentDocs(num) {
        return axios.get("/etudiant/api/" + num);
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

        console.log("Mode : " + mode);

        if (mode === 0)
            return axios.post("/import/api/rn", formData, {headers: {"Content-Type": "multipart/form-data"}});

        return axios.post("/import/api/attests", formData, {headers: {"Content-Type": "multipart/form-data"}});
    }

    truncate(pageIndex, mode) {
        return axios.post("/import/truncate_unit", {
            'page': pageIndex,
            'mode': mode
        });
    }

    getSelectionRn() {
        return axios.get("/selection/api/rn");
    }

    getSelection(mode) {
        if (mode === 0)
            return axios.get("/selection/api/rn");
        return axios.get("/selection/api/attest");
    }

    transfertRn(studentNums, mode) {
        return axios.post("/transfert/releves", {'nums': studentNums, 'mode': mode});
    }

    fetchMailTemplate() {
        return axios.get("/transfert/mail/template");
    }

    sendMail(studentsNum, mode) {
        return axios.post("/transfert/mail", {'ids': studentsNum, 'mode': mode});
    }

    getTamponExample(mode) {
        return axios.get("/api/get_tampon_example/" + mode, {responseType: 'blob'});
    }

    applyTampon(dx, dy) {
        return axios.post("/api/apply_tampon", {
            'dx': dx,
            'dy': dy
        });
    }

    fetchRnFiles(importId) {
        return axios.get("/import/imported/" + importId);
    }

    removeFiles(id, nums) {
        return axios.post("/import/delete", {
            'dataId': id,
            'numsEtu': nums
        });
    }

    sendMails(nums) {
        return axios.post("/transfert/mail", {
            'numsEtu': nums,
            'mode': 0
        });
    }
}

export default new WebService();