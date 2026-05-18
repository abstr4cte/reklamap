import { onMounted, onUnmounted, onActivated, watch, ref, unref, type Ref } from 'vue'

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
  prevPage?: string
  nextPage?: string
}

export function useSeo(options: SeoOptions | Ref<SeoOptions>) {
  // Tags we CREATED (need removal on unmount)
  const createdTags = ref<(HTMLMetaElement | HTMLLinkElement | HTMLScriptElement)[]>([])
  const scriptTags = ref<HTMLScriptElement[]>([])

  // Update or create a <meta name="..."> tag
  const setMetaName = (name: string, content: string) => {
    let tag = document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null
    if (!tag) {
      tag = document.createElement('meta')
      tag.setAttribute('name', name)
      document.head.appendChild(tag)
      createdTags.value.push(tag)
    }
    tag.setAttribute('content', content)
  }

  // Update or create a <meta property="..."> tag
  const setMetaProp = (property: string, content: string) => {
    let tag = document.querySelector(`meta[property="${property}"]`) as HTMLMetaElement | null
    if (!tag) {
      tag = document.createElement('meta')
      tag.setAttribute('property', property)
      document.head.appendChild(tag)
      createdTags.value.push(tag)
    }
    tag.setAttribute('content', content)
  }

  // Update or create a <link rel="..."> tag
  const setLink = (rel: string, href: string) => {
    let tag = document.querySelector(`link[rel="${rel}"]`) as HTMLLinkElement | null
    if (!tag) {
      tag = document.createElement('link')
      tag.setAttribute('rel', rel)
      document.head.appendChild(tag)
      createdTags.value.push(tag)
    }
    tag.setAttribute('href', href)
  }

  const removeLink = (rel: string) => {
    const tag = document.querySelector(`link[rel="${rel}"]`)
    if (tag) tag.remove()
  }

  const updateMetaTags = () => {
    if (typeof window === 'undefined') return

    const opts = unref(options)

    // Remove previously created script tags (JSON-LD)
    scriptTags.value.forEach(tag => tag.parentNode?.removeChild(tag))
    scriptTags.value = []

    // Title
    if (opts.title) {
      document.title = opts.title
      setMetaProp('og:title', opts.title)
      setMetaName('twitter:title', opts.title)
    }

    // Description
    if (opts.description) {
      setMetaName('description', opts.description)
      setMetaProp('og:description', opts.description)
      setMetaName('twitter:description', opts.description)
    }

    // Keywords
    if (opts.keywords) setMetaName('keywords', opts.keywords)

    // Open Graph
    setMetaProp('og:locale', 'pl_PL')
    setMetaProp('og:site_name', 'ReklaMap')
    setMetaProp('og:type', opts.ogType || 'website')
    const ogUrlValue = opts.ogUrl || opts.canonical
    if (ogUrlValue) setMetaProp('og:url', ogUrlValue)

    if (opts.ogImage) {
      setMetaProp('og:image', opts.ogImage)
      setMetaName('twitter:image', opts.ogImage)
      if (opts.ogImageWidth) setMetaProp('og:image:width', opts.ogImageWidth)
      if (opts.ogImageHeight) setMetaProp('og:image:height', opts.ogImageHeight)
      if (opts.ogImageAlt) setMetaProp('og:image:alt', opts.ogImageAlt)
    }

    // Twitter Card
    setMetaName('twitter:card', 'summary_large_image')

    // Canonical
    if (opts.canonical) {
      setLink('canonical', opts.canonical)
    }

    // Pagination rel=prev/next
    if (opts.prevPage) {
      setLink('prev', opts.prevPage)
    } else {
      removeLink('prev')
    }
    if (opts.nextPage) {
      setLink('next', opts.nextPage)
    } else {
      removeLink('next')
    }

    // Article times
    if (opts.publishedTime) setMetaProp('article:published_time', opts.publishedTime)
    if (opts.modifiedTime) setMetaProp('article:modified_time', opts.modifiedTime)

    // Robots noindex
    if (opts.noindex) {
      setMetaName('robots', 'noindex, follow')
    } else {
      const robotsTag = document.querySelector('meta[name="robots"]')
      if (robotsTag) robotsTag.setAttribute('content', 'index, follow')
    }

    // Structured Data (JSON-LD)
    if (opts.structuredData) {
      const dataArray = Array.isArray(opts.structuredData) ? opts.structuredData : [opts.structuredData]
      dataArray.forEach((data: any) => {
        const scriptTag = document.createElement('script')
        scriptTag.setAttribute('type', 'application/ld+json')
        scriptTag.textContent = JSON.stringify(data)
        document.head.appendChild(scriptTag)
        scriptTags.value.push(scriptTag)
      })
    }
  }

  // Sygnał dla prerender.io: snapshot dopiero gdy meta/JSON-LD z DANYCH są w <head>.
  // Flipujemy tylko gdy jest realny title (strony data-driven ustawiają ref po
  // załadowaniu API — wtedy watch odpala updateMetaTags drugi raz z prawdziwą treścią).
  const signalPrerenderReady = () => {
    if (typeof window === 'undefined') return
    if (unref(options).title) {
      ;(window as unknown as { prerenderReady?: boolean }).prerenderReady = true
    }
  }

  updateMetaTags()

  onMounted(() => { updateMetaTags(); signalPrerenderReady() })
  onActivated(() => { updateMetaTags(); signalPrerenderReady() })

  watch(() => unref(options), () => { updateMetaTags(); signalPrerenderReady() }, { deep: true })

  onUnmounted(() => {
    // Remove only tags we created (not static ones from index.html)
    createdTags.value.forEach(tag => tag.parentNode?.removeChild(tag))
    scriptTags.value.forEach(tag => tag.parentNode?.removeChild(tag))
  })

  return { updateMetaTags }
}
