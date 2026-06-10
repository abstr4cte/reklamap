import type * as LType from 'leaflet'

/**
 * Wspólna konfiguracja klastrowania markerów dla map ReklaMap
 * (HomePage/PolandMap + lista ogłoszeń). Bez tego gęsta podaż
 * (np. ~110 nośników w aglomeracji śląskiej) zlewa się w plamę.
 *
 * Wymaga, by 'leaflet.markercluster' był już zaimportowany (rozszerza L).
 * Style klastra: src/assets/mapCluster.css.
 *
 * @param L instancja Leaflet (statyczna lub załadowana dynamicznie)
 */
export function createClusterGroup(L: typeof LType): any {
  return (L as any).markerClusterGroup({
    showCoverageOnHover: false,
    spiderfyOnMaxZoom: true,
    zoomToBoundsOnClick: true,
    maxClusterRadius: 60,
    iconCreateFunction: (cluster: any) => {
      const count = cluster.getChildCount()
      const tier = count < 10 ? 'sm' : count < 50 ? 'md' : 'lg'
      const size = tier === 'sm' ? 40 : tier === 'md' ? 48 : 56
      return L.divIcon({
        html: `<div class="reklamap-cluster reklamap-cluster--${tier}"><span>${count}</span></div>`,
        className: 'reklamap-cluster-wrapper',
        iconSize: L.point(size, size),
      })
    },
  })
}
