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
      path: '/dodaj-powierzchnie-reklamowa',
      name: 'add-ad',
      component: AddAdPage
    },
    // Zachowaj starą ścieżkę dla kompatybilności wstecznej
    {
      path: '/dodaj-ogloszenie',
      redirect: '/dodaj-powierzchnie-reklamowa'
    },
    {
      path: '/powierzchnie-reklamowe',
      name: 'advertisements',
      component: () => import('./views/AdvertisementsPage.vue')
    },
    {
      path: '/powierzchnie-reklamowe/:type',
      name: 'advertisements-by-type',
      component: () => import('./views/AdvertisementsPage.vue')
    },
    {
      path: '/powierzchnie-reklamowe/:type/:city',
      name: 'advertisements-by-type-city',
      component: () => import('./views/AdvertisementsPage.vue')
    },
    // Zachowaj stare ścieżki dla kompatybilności wstecznej
    {
      path: '/ogloszenia',
      redirect: '/powierzchnie-reklamowe'
    },
    {
      path: '/powierzchnia-reklamowa/:type/:city/:slug-:id',
      name: 'ad-detail',
      component: AdDetailPage
    },
    // Zachowaj starą ścieżkę dla kompatybilności wstecznej
    {
      path: '/ogloszenie/:city/:slug/:id',
      redirect: to => {
        // Przekierowanie ze starej ścieżki na nową
        const { city, slug, id } = to.params
        return `/powierzchnia-reklamowa/inne/${city}/${slug}-${id}`
      }
    },
    {
      path: '/porownaj',
      name: 'comparison',
      component: ComparisonPage
    },
    {
      path: '/zarzadzaj/:token',
      name: 'management-token',
      component: () => import('./views/ManagementTokenPage.vue')
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
      path: '/blog/:slug',
      name: 'blog-post',
      component: () => import('./views/BlogPostPage.vue')
    },
    {
      path: '/kontakt',
      name: 'contact',
      component: ContactPage
    },
    {
      path: '/polityka-prywatnosci',
      name: 'privacy-policy',
      component: () => import('./views/PrivacyPolicyPage.vue')
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('./views/NotFoundPage.vue')
    }
  ],
  scrollBehavior(to, from, savedPosition) {
    // If the user is navigating back/forward and has a saved position, use it
    if (savedPosition) {
      return savedPosition
    } 
    // If there's a hash in the URL, scroll to it with offset
    else if (to.hash) {
      // Wait for the DOM to be updated before scrolling
      return new Promise((resolve) => {
        setTimeout(() => {
          resolve({
            el: to.hash,
            behavior: 'smooth',
            top: 80 // Add some offset to account for the header height
          })
        }, 100)
      })
    } 
    // If it's the same route with different query parameters, don't scroll
    else if (from.path === to.path && JSON.stringify(from.query) !== JSON.stringify(to.query)) {
      return false
    }
    // Otherwise, scroll to top
    else {
      return { top: 0 }
    }
  }
})

export default router
