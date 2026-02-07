import { createRouter, createWebHistory } from 'vue-router'
import PosView from '../views/PosView.vue'
import AdminView from '../views/AdminView.vue'

const routes = [
    { path: '/', name: 'pos', component: PosView },
    { path: '/admin', name: 'admin', component: AdminView },
]

export default createRouter({
    history: createWebHistory(),
    routes,
})
