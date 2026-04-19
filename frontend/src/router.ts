import { createRouter, createWebHistory } from 'vue-router'
import HomePage from './views/HomePage.vue'
// Some components are lazy-loaded within the router definition

// Wyłącz natywne przywracanie scrolla przez przeglądarkę — obsługujemy to sami w onActivated
if (typeof window !== 'undefined') {
  history.scrollRestoration = 'manual'
}

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
      path: '/blog/:category(poradniki|trendy|case-study|rynek-ooh|prawo-i-regulacje|lokalizacje)',
      name: 'blog-category',
      component: () => import('./views/BlogPage.vue')
    },
    {
      path: '/blog/:category(poradniki|trendy|case-study|rynek-ooh|prawo-i-regulacje|lokalizacje)/:slug',
      name: 'blog-post',
      component: () => import('./views/BlogPostPage.vue')
    },
    // Redirect /blog/:slug → /blog/:category/:slug (linki bez kategorii)
    {
      path: '/blog/:slug',
      name: 'blog-post-redirect',
      component: () => import('./views/BlogPostPage.vue'),
      beforeEnter: async (to, _from, next) => {
        try {
          const isDev = import.meta.env.DEV
          const apiUrl = isDev ? '/api' : 'https://api.reklamap.pl/api'
          const appKey = import.meta.env.VITE_INTERNAL_APP_KEY as string
          const res = await fetch(`${apiUrl}/blog/${to.params.slug}`, {
            headers: { 'X-App-Key': appKey }
          })
          if (!res.ok) throw new Error()
          const post = await res.json()
          next(`/blog/${post.category}/${to.params.slug}`)
        } catch {
          next({ name: 'not-found' })
        }
      }
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
  scrollBehavior(to, from, savedPosition) {
    // If there's a hash in the URL, scroll to it with offset for the header
    if (to.hash) {
      return new Promise((resolve) => {
        setTimeout(() => {
          resolve({
            el: to.hash,
            behavior: 'smooth',
            top: parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-height')) || 100
          })
        }, 100)
      })
    }
    // If it's the same route (path), don't scroll - even if query params changed
    if (from.path === to.path) {
      return false
    }
    // When navigating back to kept-alive pages (home/listings), let the component
    // handle scroll restoration via onActivated to avoid race conditions
    if (savedPosition) {
      const isKeepAlivePage = to.name === 'home' || 
        to.name === 'listings' || 
        to.name === 'listings-by-type' || 
        to.name === 'listings-by-city' || 
        to.name === 'listings-by-type-city'
      if (isKeepAlivePage) {
        return false
      }
      return savedPosition
    }
    // Always scroll to top on forward navigation
    return { top: 0, behavior: 'instant' }
  }
})

// Handle chunk load errors after deployment (old JS files no longer exist)
router.onError((error, to) => {
  if (error.message?.includes('Failed to fetch dynamically imported module') ||
      error.message?.includes('Importing a module script failed') ||
      error.name === 'ChunkLoadError') {
    window.location.href = to.fullPath
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
