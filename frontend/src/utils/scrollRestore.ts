// Odporne przywracanie pozycji scrolla po powrocie na stronę (keep-alive / back).
//
// Problem: pojedynczy requestAnimationFrame nie wystarcza. Gdy treść doładowuje się
// asynchronicznie (lazy obrazki, ponowny render listy/mapy po reaktywacji), dokument jest
// w pierwszej klatce jeszcze KRÓTKI — scrollTo(zapisaneY) przycina się do aktualnego dołu
// i użytkownik ląduje na stopce zamiast tam, gdzie był.
//
// Rozwiązanie: ponawiamy ustawianie scrolla przez kilka klatek, aż strona urośnie na tyle,
// by osiągnąć cel (albo wyczerpiemy limit klatek). Bezpieczne także gdy keep-alive zadziałał
// poprawnie — wtedy cel jest osiągany w pierwszej próbie i pętla od razu się kończy.

interface RestoreScrollOptions {
  /** Docelowa pozycja scrolla okna (px od góry). */
  windowY?: number
  /** Opcjonalny kontener z własnym scrollem (np. lista na desktopie). */
  el?: HTMLElement | null
  /** Docelowy scrollTop kontenera. */
  elTop?: number
  /** Maks. liczba prób (klatek). Domyślnie ~1 s przy 60 fps. */
  maxFrames?: number
}

export function restoreScrollResilient(opts: RestoreScrollOptions): void {
  const maxFrames = opts.maxFrames ?? 60
  let frame = 0

  const step = () => {
    if (opts.windowY != null) {
      window.scrollTo({ top: opts.windowY, behavior: 'instant' as ScrollBehavior })
    }
    if (opts.el && opts.elTop != null) {
      opts.el.scrollTop = opts.elTop
    }
    frame++

    // Cel osiągnięty, gdy realna pozycja dobiła do żądanej (z tolerancją 2 px).
    // Dopóki strona jest za krótka, scrollY/scrollTop są MNIEJSZE niż cel — ponawiamy.
    const windowDone = opts.windowY == null || window.scrollY >= opts.windowY - 2
    const elDone = !opts.el || opts.elTop == null || opts.el.scrollTop >= opts.elTop - 2

    if ((!windowDone || !elDone) && frame < maxFrames) {
      requestAnimationFrame(step)
    }
  }

  requestAnimationFrame(step)
}
