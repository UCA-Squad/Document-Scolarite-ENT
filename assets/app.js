import './styles/app.css';
import './bootstrap';
import {createApp} from "vue";
import App from "./App.vue";
import {createRouter, createWebHistory} from "vue-router";
import {user} from "./user";
import HomeScola from "./pages/scola/HomeScola.vue";
import ImportRN from "./pages/scola/Releve/ImportRN.vue";
import MonitoringRN from "./pages/scola/Releve/MonitoringRN.vue";
import SearchStudent from "./pages/SearchStudent.vue";
import StudentView from "./pages/StudentView.vue";
import Truncate from "./pages/scola/Releve/Truncate.vue";

const b64 = document.querySelector('#app').dataset.info;
const jsonUser = JSON.parse(atob(b64));

user.setName(jsonUser.username);
user.setRoles(jsonUser.roles);
user.setEncryptedName(jsonUser.encryptedUsername);
user.setNumero(jsonUser.numero);

const routes = [
    {path: '/scola', component: HomeScola, meta: {requiresAdmin: true}},

    {path: '/scola/import/rn', component: ImportRN, meta: {requiresAdmin: true}, props: {mode: 0}},
    {path: '/scola/import/attest', component: ImportRN, meta: {requiresAdmin: true}, props: {mode: 1}},

    {path: '/scola/monitoring/rn', component: MonitoringRN, meta: {requiresAdmin: true}, props: {mode: 0}},
    {path: '/scola/monitoring/attest', component: MonitoringRN, meta: {requiresAdmin: true}, props: {mode: 1}},

    {path: '/scola/search', component: SearchStudent, meta: {requiresAdmin: true}},

    {path: '/student/:num*', component: StudentView, meta: {requiresAdmin: false}},

    {path: '/:pathMatch(.*)*', redirect: '/scola'}
]

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    if (user.isEtudiant() && to.path !== '/student') {
        next({path: '/student'});
    } else if (to.meta.requiresAdmin) {
        if (user.isAdmin()) next()
        else next({path: '/'})
    } else {
        next()
    }
});

const app = createApp(App, {})

app.config.compilerOptions.isCustomElement = (tag) => {
    return tag.startsWith('uca-')
}

app.use(router).mount('#app')