import {reactive} from 'vue'

export const user = reactive({
    name: "",
    encryptedName: "",
    roles: [],
    numero: "",

    setName(name) {
        this.name = name;
    },
    setEncryptedName(encryptedName) {
        this.encryptedName = encryptedName;
    },
    setRoles(roles) {
        this.roles = roles;
    },
    setNumero(numero) {
        this.numero = numero;
    },
    asRole(role) {
        return this.roles.includes(role);
    },
    isAdmin() {
        return this.asRole("ROLE_ADMIN");
    },
    isEtudiant() {
        return this.asRole("ROLE_ETUDIANT");
    },
})