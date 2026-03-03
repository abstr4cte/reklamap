import { onMounted, onUnmounted, watch, ref, unref, type Ref } from 'vue'

interface SeoOptions {
  title?: string
  description?: string
  keywords?: string
  ogType?: string
  ogImage?: string
  ogImageWidth?: string
  ogImageHeight?: string
  ogImageAlt?: string
  ogUrl?: string
  canonical?: string
  structuredData?: object | object[]
  publishedTime?: string
  modifiedTime?: string
  noindex?: boolean
}

/**
 * Composable do zarządzania meta tagami SEO
 * Automatycznie aktualizuje meta tagi w <head> dokumentu
 */
export function useSeo(options: SeoOptions | Ref<SeoOptions>) {
  const metaTags = ref<HTMLMetaElement[]>([])
  const linkTags = ref<HTMLLinkElement[]>([])
  const scriptTags = ref<HTMLScriptElement[]>([])

  const updateMetaTags = () => {
    if (typeof window === 'undefined') return

    const opts = unref(options)

    // Usuń poprzednie meta tagi
    metaTags.value.forEach(tag => {
      if (tag.parentNode) {
        tag.parentNode.removeChild(tag)
      }
    })
    linkTags.value.forEach(tag => {
      if (tag.parentNode) {
        tag.parentNode.removeChild(tag)
      }
    })
    scriptTags.value.forEach(tag => {
      if (tag.parentNode) {
        tag.parentNode.removeChild(tag)
      }
    })

    metaTags.value = []
    linkTags.value = []
    scriptTags.value = []

    // Title
    if (opts.title) {
      document.title = opts.title
    }

    // Description
    if (opts.description) {
      const descTag = document.createElement('meta')
      descTag.setAttribute('name', 'description')
      descTag.setAttribute('content', opts.description)
      document.head.appendChild(descTag)
      metaTags.value.push(descTag)
    }

    // Keywords
    if (opts.keywords) {
      const keywordsTag = document.createElement('meta')
      keywordsTag.setAttribute('name', 'keywords')
      keywordsTag.setAttribute('content', opts.keywords)
      document.head.appendChild(keywordsTag)
      metaTags.value.push(keywordsTag)
    }

    // Open Graph Locale
    const ogLocaleTag = document.createElement('meta')
    ogLocaleTag.setAttribute('property', 'og:locale')
    ogLocaleTag.setAttribute('content', 'pl_PL')
    document.head.appendChild(ogLocaleTag)
    metaTags.value.push(ogLocaleTag)

    // Open Graph Site Name
    const ogSiteNameTag = document.createElement('meta')
    ogSiteNameTag.setAttribute('property', 'og:site_name')
    ogSiteNameTag.setAttribute('content', 'ReklaMap')
    document.head.appendChild(ogSiteNameTag)
    metaTags.value.push(ogSiteNameTag)

    // Open Graph Type
    if (opts.ogType) {
      const ogTypeTag = document.createElement('meta')
      ogTypeTag.setAttribute('property', 'og:type')
      ogTypeTag.setAttribute('content', opts.ogType)
      document.head.appendChild(ogTypeTag)
      metaTags.value.push(ogTypeTag)
    }

    // Open Graph Title
    if (opts.title) {
      const ogTitleTag = document.createElement('meta')
      ogTitleTag.setAttribute('property', 'og:title')
      ogTitleTag.setAttribute('content', opts.title)
      document.head.appendChild(ogTitleTag)
      metaTags.value.push(ogTitleTag)
    }

    // Open Graph Description
    if (opts.description) {
      const ogDescTag = document.createElement('meta')
      ogDescTag.setAttribute('property', 'og:description')
      ogDescTag.setAttribute('content', opts.description)
      document.head.appendChild(ogDescTag)
      metaTags.value.push(ogDescTag)
    }

    // Open Graph Image
    if (opts.ogImage) {
      const ogImageTag = document.createElement('meta')
      ogImageTag.setAttribute('property', 'og:image')
      ogImageTag.setAttribute('content', opts.ogImage)
      document.head.appendChild(ogImageTag)
      metaTags.value.push(ogImageTag)

      // OG Image Width
      if (opts.ogImageWidth) {
        const ogImageWidthTag = document.createElement('meta')
        ogImageWidthTag.setAttribute('property', 'og:image:width')
        ogImageWidthTag.setAttribute('content', opts.ogImageWidth)
        document.head.appendChild(ogImageWidthTag)
        metaTags.value.push(ogImageWidthTag)
      }

      // OG Image Height
      if (opts.ogImageHeight) {
        const ogImageHeightTag = document.createElement('meta')
        ogImageHeightTag.setAttribute('property', 'og:image:height')
        ogImageHeightTag.setAttribute('content', opts.ogImageHeight)
        document.head.appendChild(ogImageHeightTag)
        metaTags.value.push(ogImageHeightTag)
      }

      // OG Image Alt
      if (opts.ogImageAlt) {
        const ogImageAltTag = document.createElement('meta')
        ogImageAltTag.setAttribute('property', 'og:image:alt')
        ogImageAltTag.setAttribute('content', opts.ogImageAlt)
        document.head.appendChild(ogImageAltTag)
        metaTags.value.push(ogImageAltTag)
      }
    }

    // Open Graph URL
    if (opts.ogUrl) {
      const ogUrlTag = document.createElement('meta')
      ogUrlTag.setAttribute('property', 'og:url')
      ogUrlTag.setAttribute('content', opts.ogUrl)
      document.head.appendChild(ogUrlTag)
      metaTags.value.push(ogUrlTag)
    }

    // Canonical URL
    if (opts.canonical) {
      const canonicalTag = document.createElement('link')
      canonicalTag.setAttribute('rel', 'canonical')
      canonicalTag.setAttribute('href', opts.canonical)
      document.head.appendChild(canonicalTag)
      linkTags.value.push(canonicalTag)
    }

    // Article Published Time
    if (opts.publishedTime) {
      const publishedTag = document.createElement('meta')
      publishedTag.setAttribute('property', 'article:published_time')
      publishedTag.setAttribute('content', opts.publishedTime)
      document.head.appendChild(publishedTag)
      metaTags.value.push(publishedTag)
    }

    // Article Modified Time
    if (opts.modifiedTime) {
      const modifiedTag = document.createElement('meta')
      modifiedTag.setAttribute('property', 'article:modified_time')
      modifiedTag.setAttribute('content', opts.modifiedTime)
      document.head.appendChild(modifiedTag)
      metaTags.value.push(modifiedTag)
    }

    // Robots meta tag (noindex if specified)
    if (opts.noindex) {
      const robotsTag = document.createElement('meta')
      robotsTag.setAttribute('name', 'robots')
      robotsTag.setAttribute('content', 'noindex, follow')
      document.head.appendChild(robotsTag)
      metaTags.value.push(robotsTag)
    }

    // Twitter Card
    if (opts.title || opts.description || opts.ogImage) {
      const twitterCardTag = document.createElement('meta')
      twitterCardTag.setAttribute('name', 'twitter:card')
      twitterCardTag.setAttribute('content', 'summary_large_image')
      document.head.appendChild(twitterCardTag)
      metaTags.value.push(twitterCardTag)

      if (opts.title) {
        const twitterTitleTag = document.createElement('meta')
        twitterTitleTag.setAttribute('name', 'twitter:title')
        twitterTitleTag.setAttribute('content', opts.title)
        document.head.appendChild(twitterTitleTag)
        metaTags.value.push(twitterTitleTag)
      }

      if (opts.description) {
        const twitterDescTag = document.createElement('meta')
        twitterDescTag.setAttribute('name', 'twitter:description')
        twitterDescTag.setAttribute('content', opts.description)
        document.head.appendChild(twitterDescTag)
        metaTags.value.push(twitterDescTag)
      }

      if (opts.ogImage) {
        const twitterImageTag = document.createElement('meta')
        twitterImageTag.setAttribute('name', 'twitter:image')
        twitterImageTag.setAttribute('content', opts.ogImage)
        document.head.appendChild(twitterImageTag)
        metaTags.value.push(twitterImageTag)
      }
    }

    // Structured Data (JSON-LD)
    if (opts.structuredData) {
      const structuredDataArray = Array.isArray(opts.structuredData)
        ? opts.structuredData
        : [opts.structuredData]

      structuredDataArray.forEach((data: any) => {
        const scriptTag = document.createElement('script')
        scriptTag.setAttribute('type', 'application/ld+json')
        scriptTag.textContent = JSON.stringify(data)
        document.head.appendChild(scriptTag)
        scriptTags.value.push(scriptTag)
      })
    }
  }

  // Call immediately to handle cases where it's called after onMounted (e.g. inside a watch)
  updateMetaTags()

  onMounted(() => {
    updateMetaTags()
  })

  // Watch for changes in options
  watch(() => unref(options), () => {
    updateMetaTags()
  }, { deep: true })

  onUnmounted(() => {
    // Cleanup
    metaTags.value.forEach(tag => {
      if (tag.parentNode) {
        tag.parentNode.removeChild(tag)
      }
    })
    linkTags.value.forEach(tag => {
      if (tag.parentNode) {
        tag.parentNode.removeChild(tag)
      }
    })
    scriptTags.value.forEach(tag => {
      if (tag.parentNode) {
        tag.parentNode.removeChild(tag)
      }
    })
  })

  return {
    updateMetaTags
  }
}
