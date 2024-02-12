import './styles/app.css';
// import './bootstrap';
import {createApp} from "vue";
import App from "./App.vue";
import {createRouter, createWebHistory} from "vue-router";
import {user} from "./user";
import HomeScola from "./pages/scola/HomeScola.vue";
import ImportDoc from "./pages/scola/ImportDoc.vue";
import MonitoringDoc from "./pages/scola/Monitoring.vue";
import SearchStudent from "./pages/scola/SearchStudent.vue";
import StudentView from "./pages/StudentView.vue";
import axios from "axios";

const b64 = document.querySelector('#app').dataset.info;
const jsonUser = JSON.parse(atob(b64));

user.setName(jsonUser.username);
user.setRoles(jsonUser.roles);
user.setEncryptedName(jsonUser.encryptedUsername);
user.setNumero(jsonUser.numero);

const routes = [
    {path: '/scola', component: HomeScola, meta: {requiresScola: true}},

    {path: '/scola/import/rn', component: ImportDoc, meta: {requiresScola: true}, props: {mode: 0}},
    {path: '/scola/import/attest', component: ImportDoc, meta: {requiresScola: true}, props: {mode: 1}},

    {path: '/scola/monitoring/rn', component: MonitoringDoc, meta: {requiresScola: true}, props: {mode: 0}},
    {path: '/scola/monitoring/attest', component: MonitoringDoc, meta: {requiresScola: true}, props: {mode: 1}},

    {path: '/scola/search', component: SearchStudent, meta: {requiresScola: true}},

    {path: '/student/:num*', component: StudentView, meta: {requiresScola: false}},

    {path: '/:pathMatch(.*)*', redirect: '/scola'}
]

const router = createRouter({
    history: createWebHistory('/doc-scola/'),
    routes,
});

axios.interceptors.response.use(function (response) {
    // Optional: Do something with response data
    return response;
}, function (error) {
    //Do whatever you want with the response error here:

    console.log('AXIOS ERR');

    if (error.response.status === 403) {
        window.location.reload();
    }

    //But, be SURE to return the rejected promise, so the caller still has
    //the option of additional specialized handling at the call-site:
    return Promise.reject(error);
});

router.beforeEach((to, from, next) => {
    if (user.isEtudiant() && to.path !== '/student') {
        next({path: '/student'});
    } else if (to.meta.requiresScola) {
        if (user.isScola()) next()
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