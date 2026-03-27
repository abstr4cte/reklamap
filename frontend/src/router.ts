import { createRouter, createWebHistory } from 'vue-router'
import HomePage from './views/HomePage.vue'
// Some components are lazy-loaded within the router definition

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
      component: () => import('./views/AddAdPage.vue')
    },
    // Zachowaj starą ścieżkę dla kompatybilności wstecznej
    {
      path: '/dodaj-ogloszenie',
      redirect: '/dodaj-powierzchnie-reklamowa'
    },
    {
      path: '/powierzchnie-reklamowe',
      name: 'listings',
      component: () => import('./views/ListingsPage.vue')
    },
    // Route dla konkretnych typów (billboardy, citylighty, etc.)
    {
      path: '/powierzchnie-reklamowe/:type(billboardy|citylighty|ekrany-led|banery|sciany-reklamowe|totemy-reklamowe|reklama-w-transporcie|reklama-mobilna|inne)',
      name: 'listings-by-type',
      component: () => import('./views/ListingsPage.vue')
    },
    // Route dla typu + miasto
    {
      path: '/powierzchnie-reklamowe/:type(billboardy|citylighty|ekrany-led|banery|sciany-reklamowe|totemy-reklamowe|reklama-w-transporcie|reklama-mobilna|inne)/:city',
      name: 'listings-by-type-city',
      component: () => import('./views/ListingsPage.vue')
    },
    // Route dla samego miasta (bez typu)
    {
      path: '/powierzchnie-reklamowe/:city',
      name: 'listings-by-city',
      component: () => import('./views/ListingsPage.vue')
    },
    // Zachowaj stare ścieżki dla kompatybilności wstecznej
    {
      path: '/ogloszenia',
      redirect: '/powierzchnie-reklamowe'
    },
    {
      path: '/powierzchnia-reklamowa/:type/:city/:id',
      name: 'ad-detail',
      component: () => import('./views/AdDetailPage.vue')
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
      component: () => import('./views/ComparisonPage.vue')
    },
    {
      path: '/zarzadzaj/:token',
      name: 'management-with-token',
      component: () => import('./views/ManagementPage.vue')
    },
    {
      path: '/zarzadzaj',
      name: 'management',
      component: () => import('./views/ManagementPage.vue')
    },
    {
      path: '/regulamin',
      name: 'regulamin',
      component: () => import('./views/RegulaminPage.vue')
    },
    {
      path: '/faq',
      name: 'faq',
      component: () => import('./views/FaqPage.vue')
    },
    {
      path: '/blog',
      name: 'blog',
      component: () => import('./views/BlogPage.vue')
    },
    {
      path: '/blog/:slug',
      name: 'blog-post',
      component: () => import('./views/BlogPostPage.vue')
    },
    {
      path: '/kontakt',
      name: 'contact',
      component: () => import('./views/ContactPage.vue')
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
  scrollBehavior(to, from, _savedPosition) {
    // If there's a hash in the URL, scroll to it with offset for the header
    if (to.hash) {
      return new Promise((resolve) => {
        setTimeout(() => {
          resolve({
            el: to.hash,
            behavior: 'smooth',
            top: 80
          })
        }, 100)
      })
    }
    // If it's the same route (path), don't scroll - even if query params changed
    if (from.path === to.path) {
      return false
    }
    // Always scroll to top on any navigation (including back/forward)
    return { top: 0, behavior: 'instant' }
  }
})

// Google Analytics Page Tracking
router.afterEach((to) => {
  if ((window as any).gtag) {
    (window as any).gtag('config', 'G-0ZL0NS8F9W', {
      page_path: to.fullPath,
      page_title: document.title
    });
  }
});

export default router
