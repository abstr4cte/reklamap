import { onMounted, onUnmounted, watch, ref } from 'vue'

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
export function useSeo(options: SeoOptions) {
  const metaTags = ref<HTMLMetaElement[]>([])
  const linkTags = ref<HTMLLinkElement[]>([])
  const scriptTags = ref<HTMLScriptElement[]>([])

  const updateMetaTags = () => {
    if (typeof window === 'undefined') return

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
    if (options.title) {
      document.title = options.title
    }

    // Description
    if (options.description) {
      const descTag = document.createElement('meta')
      descTag.setAttribute('name', 'description')
      descTag.setAttribute('content', options.description)
      document.head.appendChild(descTag)
      metaTags.value.push(descTag)
    }

    // Keywords
    if (options.keywords) {
      const keywordsTag = document.createElement('meta')
      keywordsTag.setAttribute('name', 'keywords')
      keywordsTag.setAttribute('content', options.keywords)
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
    if (options.ogType) {
      const ogTypeTag = document.createElement('meta')
      ogTypeTag.setAttribute('property', 'og:type')
      ogTypeTag.setAttribute('content', options.ogType)
      document.head.appendChild(ogTypeTag)
      metaTags.value.push(ogTypeTag)
    }

    // Open Graph Title
    if (options.title) {
      const ogTitleTag = document.createElement('meta')
      ogTitleTag.setAttribute('property', 'og:title')
      ogTitleTag.setAttribute('content', options.title)
      document.head.appendChild(ogTitleTag)
      metaTags.value.push(ogTitleTag)
    }

    // Open Graph Description
    if (options.description) {
      const ogDescTag = document.createElement('meta')
      ogDescTag.setAttribute('property', 'og:description')
      ogDescTag.setAttribute('content', options.description)
      document.head.appendChild(ogDescTag)
      metaTags.value.push(ogDescTag)
    }

    // Open Graph Image
    if (options.ogImage) {
      const ogImageTag = document.createElement('meta')
      ogImageTag.setAttribute('property', 'og:image')
      ogImageTag.setAttribute('content', options.ogImage)
      document.head.appendChild(ogImageTag)
      metaTags.value.push(ogImageTag)
      
      // OG Image Width
      if (options.ogImageWidth) {
        const ogImageWidthTag = document.createElement('meta')
        ogImageWidthTag.setAttribute('property', 'og:image:width')
        ogImageWidthTag.setAttribute('content', options.ogImageWidth)
        document.head.appendChild(ogImageWidthTag)
        metaTags.value.push(ogImageWidthTag)
      }
      
      // OG Image Height
      if (options.ogImageHeight) {
        const ogImageHeightTag = document.createElement('meta')
        ogImageHeightTag.setAttribute('property', 'og:image:height')
        ogImageHeightTag.setAttribute('content', options.ogImageHeight)
        document.head.appendChild(ogImageHeightTag)
        metaTags.value.push(ogImageHeightTag)
      }
      
      // OG Image Alt
      if (options.ogImageAlt) {
        const ogImageAltTag = document.createElement('meta')
        ogImageAltTag.setAttribute('property', 'og:image:alt')
        ogImageAltTag.setAttribute('content', options.ogImageAlt)
        document.head.appendChild(ogImageAltTag)
        metaTags.value.push(ogImageAltTag)
      }
    }

    // Open Graph URL
    if (options.ogUrl) {
      const ogUrlTag = document.createElement('meta')
      ogUrlTag.setAttribute('property', 'og:url')
      ogUrlTag.setAttribute('content', options.ogUrl)
      document.head.appendChild(ogUrlTag)
      metaTags.value.push(ogUrlTag)
    }

    // Canonical URL
    if (options.canonical) {
      const canonicalTag = document.createElement('link')
      canonicalTag.setAttribute('rel', 'canonical')
      canonicalTag.setAttribute('href', options.canonical)
      document.head.appendChild(canonicalTag)
      linkTags.value.push(canonicalTag)
    }
    
    // Article Published Time
    if (options.publishedTime) {
      const publishedTag = document.createElement('meta')
      publishedTag.setAttribute('property', 'article:published_time')
      publishedTag.setAttribute('content', options.publishedTime)
      document.head.appendChild(publishedTag)
      metaTags.value.push(publishedTag)
    }
    
    // Article Modified Time
    if (options.modifiedTime) {
      const modifiedTag = document.createElement('meta')
      modifiedTag.setAttribute('property', 'article:modified_time')
      modifiedTag.setAttribute('content', options.modifiedTime)
      document.head.appendChild(modifiedTag)
      metaTags.value.push(modifiedTag)
    }
    
    // Robots meta tag (noindex if specified)
    if (options.noindex) {
      const robotsTag = document.createElement('meta')
      robotsTag.setAttribute('name', 'robots')
      robotsTag.setAttribute('content', 'noindex, follow')
      document.head.appendChild(robotsTag)
      metaTags.value.push(robotsTag)
    }

    // Twitter Card
    if (options.title || options.description || options.ogImage) {
      const twitterCardTag = document.createElement('meta')
      twitterCardTag.setAttribute('name', 'twitter:card')
      twitterCardTag.setAttribute('content', 'summary_large_image')
      document.head.appendChild(twitterCardTag)
      metaTags.value.push(twitterCardTag)

      if (options.title) {
        const twitterTitleTag = document.createElement('meta')
        twitterTitleTag.setAttribute('name', 'twitter:title')
        twitterTitleTag.setAttribute('content', options.title)
        document.head.appendChild(twitterTitleTag)
        metaTags.value.push(twitterTitleTag)
      }

      if (options.description) {
        const twitterDescTag = document.createElement('meta')
        twitterDescTag.setAttribute('name', 'twitter:description')
        twitterDescTag.setAttribute('content', options.description)
        document.head.appendChild(twitterDescTag)
        metaTags.value.push(twitterDescTag)
      }

      if (options.ogImage) {
        const twitterImageTag = document.createElement('meta')
        twitterImageTag.setAttribute('name', 'twitter:image')
        twitterImageTag.setAttribute('content', options.ogImage)
        document.head.appendChild(twitterImageTag)
        metaTags.value.push(twitterImageTag)
      }
    }

    // Structured Data (JSON-LD)
    if (options.structuredData) {
      const structuredDataArray = Array.isArray(options.structuredData) 
        ? options.structuredData 
        : [options.structuredData]

      structuredDataArray.forEach(data => {
        const scriptTag = document.createElement('script')
        scriptTag.setAttribute('type', 'application/ld+json')
        scriptTag.textContent = JSON.stringify(data)
        document.head.appendChild(scriptTag)
        scriptTags.value.push(scriptTag)
      })
    }
  }

  onMounted(() => {
    updateMetaTags()
  })

  // Watch for changes in options
  watch(() => options, () => {
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
