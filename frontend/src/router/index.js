import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Install from '../views/Install.vue'
import New from '../views/New.vue'
import Items from '../views/Items.vue'
import Settings from '../views/Settings.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/install', name: 'Install', component: Install },
  { path: '/New', name: 'New', component: New },
  { path: '/Items', name: 'Items', component: Items },
  { path: '/Settings', name: 'Settings', component: Settings }
]

export default createRouter({
  history: createWebHistory(),
  routes
})
// This code sets up a Vue Router with two routes: the home page and an install page.