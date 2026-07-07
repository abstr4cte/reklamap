#!/usr/bin/env bash
# Deploy frontu z prerenderem na produkcję (Hostido).
# Build+prerender LECI LOKALNIE (api.reklamap.pl jest stąd osiągalne; z runnerów GitHuba
# Hostido blokuje burst ~435 żądań prerenderu jako nadużycie). Na serwer idzie gotowy dist.
#
# Użycie: cd frontend && ./deploy.sh
set -euo pipefail
cd "$(dirname "$0")"

SSH_KEY="$HOME/.ssh/reklamap_deploy"
SSH_PORT=64321
SSH_DEST="host831115@host831115.hostido.net.pl"
DOCROOT="/home/host831115/domains/reklamap.pl/reklamap/frontend/dist"   # realny docroot reklamap.pl

echo "==> 1/3 build + prerender (lokalnie)"
npm run build:seo

echo "==> 2/3 rsync dist → prod docroot"
rsync -az --delete \
  -e "ssh -p $SSH_PORT -i $SSH_KEY -o StrictHostKeyChecking=accept-new" \
  dist/ \
  "$SSH_DEST:$DOCROOT/"

echo "==> 3/3 weryfikacja (bot widzi treść)"
sleep 3
T=$(curl -s -A "Googlebot/2.1" "https://reklamap.pl/powierzchnie-reklamowe/billboardy/koszalin" | grep -oiE '<title>[^<]*</title>' | head -1)
echo "Tytuł u bota: $T"
echo "$T" | grep -qi "Billboardy Koszalin" \
  && echo "✅ DEPLOY OK — prerender serwowany" \
  || { echo "⚠️ strona oddaje generyczny tytuł — sprawdź docroot/.htaccess"; exit 1; }

# Tripwire deindeksu — mocniejszy check niż pojedynczy tytuł: próbkuje z sitemapy (home + combo +
# kategoria + leaf + artykuł bloga) i sprawdza index + zaszyty seed + treść + brak fałszywego
# empty-state. Miękko (nie przerywa — deploy już poszedł), ale głośno sygnalizuje regresję.
echo "==> weryfikacja SEO: tripwire deindeksu"
if ( cd ../backend && php artisan seo:tripwire ); then
  echo "✅ tripwire OK"
else
  echo "⚠️ TRIPWIRE ZGŁOSIŁ PROBLEM — przejrzyj wynik powyżej; rozważ rollback (git) lub ponowny deploy"
fi

echo ""
echo "==> PO WDROŻENIU (SEO): Google Search Console"
echo "    Dla nowych/zmienionych stron: 'Sprawdź URL' → 'Poproś o zaindeksowanie'."
echo "    Zgłoszone 2026-06-16: strona główna + /powierzchnie-reklamowe/billboardy/koszalin."
echo "    Po większym imporcie nośników ponów też zgłoszenie sitemap.xml w GSC."
