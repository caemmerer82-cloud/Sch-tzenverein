import { createRouter, createWebHistory } from 'vue-router'
import EventsView from '../views/EventsView.vue'
import EventDetailView from '../views/EventDetailView.vue'
import ShootersView from '../views/ShootersView.vue'
import RegistrationView from '../views/RegistrationView.vue'
import ReportsView from '../views/ReportsView.vue'
import AgeGroupsView from '../views/AgeGroupsView.vue'

const routes = [
  { path: '/', redirect: '/events' },
  { path: '/events', name: 'Events', component: EventsView },
  { path: '/events/:id', name: 'EventDetail', component: EventDetailView, props: true },
  { path: '/shooters', name: 'Shooters', component: ShootersView },
  { path: '/registration', name: 'Registration', component: RegistrationView },
  { path: '/reports', name: 'Reports', component: ReportsView },
  { path: '/age-groups', name: 'AgeGroups', component: AgeGroupsView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
