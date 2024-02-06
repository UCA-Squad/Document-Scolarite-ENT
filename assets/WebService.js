import axios from "axios";

class WebService {

    getMonitoring(mode) {
        if (mode === 0)
            return axios.get("/api/monitoring/rn");
        return axios.get("/api/monitoring/attest");
    }

    searchStudent(searchFiled) {
        return axios.post("/api/scola/search", searchFiled);
    }

    getStudentDocs(num) {
        return axios.get("/api/etudiant/" + num);
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
            return axios.post("/api/import/rn", formData, {headers: {"Content-Type": "multipart/form-data"}});

        return axios.post("/api/import/attests", formData, {headers: {"Content-Type": "multipart/form-data"}});
    }

    truncate(pageIndex, mode) {
        return axios.post("/api/import/truncate_unit", {
            'page': pageIndex,
            'mode': mode
        });
    }

    getSelection(mode) {
        if (mode === 0)
            return axios.get("/api/selection/rn");
        return axios.get("/api/selection/attest");
    }

    transfertRn(studentNums, mode) {
        return axios.post("/api/transfert/releves", {'nums': studentNums, 'mode': mode});
    }

    fetchMailTemplate() {
        return axios.get("/api/transfert/mail/template");
    }

    sendMail(studentsNum, mode) {
        return axios.post("/api/transfert/mail", {'ids': studentsNum, 'mode': mode});
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
        return axios.get("/api/import/imported/" + importId);
    }

    removeFiles(id, nums) {
        return axios.post("/api/monitoring/delete", {
            'dataId': id,
            'numsEtu': nums
        });
    }

    sendMails(nums) {
        return axios.post("/api/transfert/mail", {
            'numsEtu': nums,
            'mode': 0
        });
    }
}

export default new WebService();