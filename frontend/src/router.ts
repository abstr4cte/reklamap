import { createRouter, createWebHistory } from 'vue-router'
import HomePage from './views/HomePage.vue'
import AddAdPage from './views/AddAdPage.vue'
import AdDetailPage from './views/AdDetailPage.vue'
import ComparisonPage from './views/ComparisonPage.vue'
import ManagementPage from './views/ManagementPage.vue'
import RegulaminPage from './views/RegulaminPage.vue'
import FaqPage from './views/FaqPage.vue'
import BlogPage from './views/BlogPage.vue'
import ContactPage from './views/ContactPage.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage
    },
    {
      path: '/dodaj-ogloszenie',
      name: 'add-ad',
      component: AddAdPage
    },
    {
      path: '/ogloszenie/:id',
      name: 'ad-detail',
      component: AdDetailPage
    },
    {
      path: '/porownaj',
      name: 'comparison',
      component: ComparisonPage
    },
    {
      path: '/zarzadzaj',
      name: 'management',
      component: ManagementPage
    },
    {
      path: '/regulamin',
      name: 'regulamin',
      component: RegulaminPage
    },
    {
      path: '/faq',
      name: 'faq',
      component: FaqPage
    },
    {
      path: '/blog',
      name: 'blog',
      component: BlogPage
    },
    {
      path: '/blog/:id',
      name: 'blog-post',
      component: () => import('./views/BlogPostPage.vue')
    },
    {
      path: '/kontakt',
      name: 'contact',
      component: ContactPage
    }
  ],
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else if (to.hash) {
      return {
        el: to.hash,
        behavior: 'smooth'
      }
    } else {
      return { top: 0 }
    }
  }
})

export default router
