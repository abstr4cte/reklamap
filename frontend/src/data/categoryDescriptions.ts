export interface CategoryDescription {
  title: string
  description: string
  benefits: string[]
}

export const categoryDescriptions: Record<string, CategoryDescription> = {
  // Wszystkie powierzchnie
  '': {
    title: 'Powierzchnie reklamowe w całej Polsce',
    description: 'Przeglądaj tysiące ofert powierzchni reklamowych dostępnych w całej Polsce. Znajdź idealne miejsce na swoją kampanię reklamową - od billboardów przy głównych trasach, przez citylighty w centrach miast, po nowoczesne ekrany LED. Nasza platforma łączy właścicieli powierzchni reklamowych z reklamodawcami, oferując szeroki wybór lokalizacji i formatów.',
    benefits: [
      'Największa baza powierzchni reklamowych w Polsce',
      'Bezpośredni kontakt z właścicielami',
      'Przejrzyste ceny i warunki wynajmu',
      'Możliwość porównania ofert'
    ]
  },
  
  // Billboardy
  'billboardy': {
    title: 'Billboardy - wielkoformatowa reklama outdoor',
    description: 'Billboardy to klasyczna forma reklamy zewnętrznej, oferująca maksymalną widoczność przy głównych drogach i autostradach. Duże formaty (najczęściej 6x3m lub 12x4m) zapewniają doskonałą ekspozycję dla kampanii wizerunkowych i produktowych. Idealne do budowania świadomości marki wśród szerokiego grona odbiorców.',
    benefits: [
      'Wysoka widoczność przy głównych trasach komunikacyjnych',
      'Duże formaty przyciągające uwagę kierowców',
      'Doskonałe do kampanii ogólnopolskich',
      'Długoterminowa ekspozycja 24/7'
    ]
  },
  
  // Citylighty
  'citylighty': {
    title: 'Citylighty - podświetlane witryny reklamowe',
    description: 'Citylighty to podświetlane witryny reklamowe umieszczane w centrach miast, przy przystankach komunikacji miejskiej i w galeriach handlowych. Format 120x180cm zapewnia doskonałą widoczność zarówno w dzień jak i w nocy. Idealne rozwiązanie dla kampanii lokalnych i produktowych skierowanych do mieszkańców miast.',
    benefits: [
      'Podświetlenie zapewnia widoczność 24h/dobę',
      'Strategiczne lokalizacje w centrach miast',
      'Wysoka częstotliwość kontaktu z odbiorcami',
      'Doskonałe do kampanii lokalnych i produktowych'
    ]
  },
  
  // Ekrany LED
  'ekrany-led': {
    title: 'Ekrany LED - dynamiczna reklama cyfrowa',
    description: 'Nowoczesne ekrany LED to przyszłość reklamy outdoor. Umożliwiają wyświetlanie dynamicznych treści, animacji i spotów wideo, co znacząco zwiększa skuteczność przekazu. Możliwość szybkiej zmiany kreacji i targetowania w różnych porach dnia czyni je niezwykle elastycznym narzędziem marketingowym.',
    benefits: [
      'Dynamiczne treści i animacje przyciągające uwagę',
      'Możliwość wyświetlania wielu kampanii w rotacji',
      'Szybka zmiana kreacji bez kosztów druku',
      'Targetowanie czasowe (różne treści o różnych porach)'
    ]
  },
  
  // Banery
  'banery': {
    title: 'Banery reklamowe - elastyczne rozwiązania outdoor',
    description: 'Banery reklamowe to elastyczne i ekonomiczne rozwiązanie dla firm poszukujących powierzchni reklamowej. Montowane na budynkach, płotach i konstrukcjach tymczasowych, oferują dużą swobodę w doborze lokalizacji i rozmiaru. Idealne dla kampanii krótkoterminowych, eventów i promocji lokalnych.',
    benefits: [
      'Niskie koszty produkcji i montażu',
      'Elastyczność w doborze rozmiaru i lokalizacji',
      'Szybki montaż i demontaż',
      'Idealne dla kampanii sezonowych i eventów'
    ]
  },
  
  // Ściany reklamowe
  'sciany-reklamowe': {
    title: 'Ściany reklamowe - wielkoformatowe murale miejskie',
    description: 'Ściany reklamowe (murale) to monumentalne formaty umieszczane na elewacjach budynków w centrach miast. Oferują największą powierzchnię ekspozycji wśród wszystkich form reklamy outdoor. Doskonałe do budowania prestiżu marki i tworzenia ikonicznych kampanii, które stają się częścią krajobrazu miejskiego.',
    benefits: [
      'Największa powierzchnia ekspozycji',
      'Prestiżowe lokalizacje w centrach miast',
      'Długoterminowa widoczność',
      'Budowanie rozpoznawalności marki na dużą skalę'
    ]
  },
  
  // Totemy reklamowe
  'totemy-reklamowe': {
    title: 'Totemy reklamowe - pionowe pylony informacyjne',
    description: 'Totemy reklamowe to wysokie, wolnostojące konstrukcje umieszczane przy centrach handlowych, stacjach benzynowych i parkingach. Ich pionowa forma zapewnia doskonałą widoczność z daleka, pomagając klientom w nawigacji. Idealne dla marek retail i usług lokalnych.',
    benefits: [
      'Wysoka widoczność z dużej odległości',
      'Pomoc w nawigacji dla klientów',
      'Możliwość umieszczenia wielu marek na jednym totemu',
      'Skuteczne przy centrach handlowych i usługowych'
    ]
  },
  
  // Reklama w transporcie
  'reklama-w-transporcie': {
    title: 'Reklama w transporcie publicznym',
    description: 'Reklama w komunikacji miejskiej obejmuje oklejanie autobusów, tramwajów, metra oraz reklamy na przystankach. Mobilny charakter tego medium zapewnia dotarcie do szerokiego grona odbiorców w różnych częściach miasta. Wysoka częstotliwość kontaktu i długi czas ekspozycji podczas podróży zwiększają skuteczność przekazu.',
    benefits: [
      'Mobilny zasięg - reklama dociera do różnych dzielnic',
      'Wysoka częstotliwość kontaktu z pasażerami',
      'Długi czas ekspozycji podczas podróży',
      'Dotarcie do szerokiego grona odbiorców'
    ]
  },
  
  // Reklama mobilna
  'reklama-mobilna': {
    title: 'Reklama mobilna - kampanie w ruchu',
    description: 'Reklama mobilna to innowacyjne rozwiązanie wykorzystujące pojazdy (przyczepy reklamowe, samochody dostawcze, bike-boardy) do promocji marki. Możliwość targetowania geograficznego i czasowego pozwala dotrzeć z przekazem dokładnie tam, gdzie znajduje się grupa docelowa - od eventów, przez centra handlowe, po dzielnice biznesowe.',
    benefits: [
      'Precyzyjne targetowanie geograficzne',
      'Elastyczność w planowaniu tras i lokalizacji',
      'Wysoka uwaga ze względu na ruchomy charakter',
      'Idealne dla eventów i akcji promocyjnych'
    ]
  },
  
  // Inne
  'inne': {
    title: 'Inne formy reklamy outdoor',
    description: 'Kategoria obejmuje niestandardowe i innowacyjne formy reklamy zewnętrznej, w tym digital signage, reklamy ambientowe, neony, instalacje 3D i guerilla marketing. Te kreatywne rozwiązania pozwalają wyróżnić się na tle konkurencji i stworzyć niezapomniane doświadczenie dla odbiorców.',
    benefits: [
      'Unikalne, wyróżniające się formy reklamy',
      'Wysoki potencjał viralowy',
      'Możliwość kreatywnego podejścia do kampanii',
      'Budowanie innowacyjnego wizerunku marki'
    ]
  }
}

export const cityDescriptions: Record<string, CategoryDescription> = {
  'warszawa': {
    title: 'Powierzchnie reklamowe w Warszawie',
    description: 'Warszawa, jako stolica i największe miasto Polski, oferuje niezrównane możliwości reklamowe. Od prestiżowych lokalizacji w centrum biznesowym, przez ruchliwe arterie komunikacyjne, po dynamicznie rozwijające się dzielnice. Wysoka koncentracja potencjalnych klientów i zróżnicowana demografia sprawiają, że kampanie w Warszawie osiągają maksymalny zasięg.',
    benefits: [
      'Największy rynek reklamowy w Polsce',
      'Prestiżowe lokalizacje w centrum biznesowym',
      'Wysokie natężenie ruchu na głównych trasach',
      'Zróżnicowana demografia odbiorców'
    ]
  },
  
  'krakow': {
    title: 'Powierzchnie reklamowe w Krakowie',
    description: 'Kraków łączy w sobie historyczny urok z nowoczesnym biznesem. Reklama w Krakowie to dotarcie zarówno do milionów turystów odwiedzających miasto, jak i do dynamicznie rozwijającego się sektora IT i usług. Unikalne lokalizacje w pobliżu Starego Miasta i Kazimierza oferują wyjątkową ekspozycję.',
    benefits: [
      'Miliony turystów rocznie',
      'Rozwijający się sektor IT i biznesowy',
      'Prestiżowe lokalizacje w centrum historycznym',
      'Wysoka siła nabywcza mieszkańców'
    ]
  },
  
  'wroclaw': {
    title: 'Powierzchnie reklamowe we Wrocławiu',
    description: 'Wrocław to jedno z najbardziej dynamicznie rozwijających się miast w Polsce. Stolica Dolnego Śląska przyciąga inwestorów, studentów i turystów. Kampanie reklamowe we Wrocławiu docierają do młodej, wykształconej i zamożnej grupy odbiorców. Doskonałe połączenia komunikacyjne i rosnąca liczba centrów biznesowych tworzą idealne warunki dla reklamy outdoor.',
    benefits: [
      'Młoda, wykształcona populacja',
      'Dynamiczny rozwój gospodarczy',
      'Liczne centra biznesowe i handlowe',
      'Wysokie natężenie ruchu turystycznego'
    ]
  },
  
  'poznan': {
    title: 'Powierzchnie reklamowe w Poznaniu',
    description: 'Poznań to stolica Wielkopolski i ważny ośrodek targowo-wystawienniczy. Międzynarodowe targi przyciągają tysiące biznesmenów, a rozwinięty sektor handlowy i usługowy tworzy doskonałe warunki dla kampanii reklamowych. Strategiczne położenie na trasie Berlin-Warszawa zapewnia dodatkową ekspozycję.',
    benefits: [
      'Międzynarodowe targi i wydarzenia biznesowe',
      'Strategiczne położenie na trasie Berlin-Warszawa',
      'Rozwinięty sektor handlowy',
      'Wysoka aktywność gospodarcza'
    ]
  },
  
  'gdansk': {
    title: 'Powierzchnie reklamowe w Gdańsku',
    description: 'Gdańsk, jako część Trójmiasta, oferuje unikalne możliwości reklamowe w największej aglomeracji północnej Polski. Połączenie funkcji portowych, turystycznych i biznesowych tworzy zróżnicowaną grupę odbiorców. Reklama w Gdańsku to dotarcie zarówno do mieszkańców, jak i do milionów turystów odwiedzających nadmorskie kurorty.',
    benefits: [
      'Część największej aglomeracji północnej Polski',
      'Wysoki ruch turystyczny (morze, zabytki)',
      'Ważny ośrodek portowy i logistyczny',
      'Rozwijający się sektor IT i usług'
    ]
  },
  
  'lodz': {
    title: 'Powierzchnie reklamowe w Łodzi',
    description: 'Łódź przechodzi dynamiczną transformację z miasta przemysłowego w nowoczesne centrum biznesu i kultury. Rewitalizacja centrum, rozwój sektora IT i BPO oraz strategiczne położenie w centrum Polski sprawiają, że kampanie reklamowe w Łodzi osiągają doskonały stosunek zasięgu do kosztów.',
    benefits: [
      'Strategiczne położenie w centrum Polski',
      'Dynamiczny rozwój sektora IT i BPO',
      'Rewitalizacja centrum miasta',
      'Atrakcyjne ceny powierzchni reklamowych'
    ]
  },
  
  'katowice': {
    title: 'Powierzchnie reklamowe w Katowicach',
    description: 'Katowice, jako stolica Górnego Śląska, są centrum największej aglomeracji miejskiej w Polsce. Wysoka gęstość zaludnienia, rozwinięta infrastruktura komunikacyjna i liczne centra handlowe tworzą idealne warunki dla kampanii reklamowych. Transformacja z miasta przemysłowego w centrum biznesu i kultury otwiera nowe możliwości.',
    benefits: [
      'Centrum największej aglomeracji w Polsce',
      'Wysoka gęstość zaludnienia',
      'Rozwinięta infrastruktura handlowa',
      'Liczne wydarzenia kulturalne i biznesowe'
    ]
  },
  
  'szczecin': {
    title: 'Powierzchnie reklamowe w Szczecinie',
    description: 'Szczecin to stolica Pomorza Zachodniego i ważny ośrodek portowy. Strategiczne położenie przy granicy z Niemcami i rozwój turystyki sprawia, że kampanie reklamowe w Szczecinie docierają do zróżnicowanej grupy odbiorców. Rewitalizacja centrum i rozwój infrastruktury turystycznej tworzą nowe możliwości reklamowe.',
    benefits: [
      'Strategiczne położenie przy granicy z Niemcami',
      'Ważny ośrodek portowy',
      'Rosnący ruch turystyczny',
      'Rewitalizacja centrum miasta'
    ]
  },
  
  'bydgoszcz': {
    title: 'Powierzchnie reklamowe w Bydgoszczy',
    description: 'Bydgoszcz to dynamicznie rozwijające się miasto w centrum Polski północnej. Ważny węzeł komunikacyjny i rozwijający się sektor przemysłowy tworzą doskonałe warunki dla kampanii reklamowych. Liczne inwestycje w infrastrukturę i centra handlowe zwiększają potencjał reklamowy miasta.',
    benefits: [
      'Ważny węzeł komunikacyjny',
      'Rozwijający się sektor przemysłowy',
      'Liczne centra handlowe',
      'Atrakcyjne ceny powierzchni'
    ]
  },
  
  'lublin': {
    title: 'Powierzchnie reklamowe w Lublinie',
    description: 'Lublin to stolica Polski Wschodniej i ważny ośrodek akademicki. Duża liczba studentów i rozwijający się sektor usług tworzą młodą, aktywną grupę odbiorców. Strategiczne położenie na trasie do granicy z Ukrainą i Białorusią zapewnia dodatkową ekspozycję dla kampanii międzynarodowych.',
    benefits: [
      'Duży ośrodek akademicki',
      'Młoda, aktywna populacja',
      'Strategiczne położenie przy granicy wschodniej',
      'Rozwijający się sektor usług'
    ]
  },
  
  'bialystok': {
    title: 'Powierzchnie reklamowe w Białymstoku',
    description: 'Białystok to stolica Podlasia i największe miasto Polski północno-wschodniej. Rozwijający się sektor handlowy i usługowy oraz strategiczne położenie przy trasach do krajów bałtyckich tworzą interesujące możliwości reklamowe. Miasto charakteryzuje się dynamicznym rozwojem infrastruktury handlowej.',
    benefits: [
      'Największe miasto Polski północno-wschodniej',
      'Strategiczne położenie przy trasach do krajów bałtyckich',
      'Rozwijająca się infrastruktura handlowa',
      'Rosnąca aktywność gospodarcza'
    ]
  },
  
  'gdynia': {
    title: 'Powierzchnie reklamowe w Gdyni',
    description: 'Gdynia, jako część Trójmiasta, łączy funkcje portowe z turystycznymi i biznesowymi. Nowoczesne centrum miasta, promenada nadmorska i liczne wydarzenia kulturalne przyciągają zarówno mieszkańców, jak i turystów. Kampanie reklamowe w Gdyni to dotarcie do zamożnej, aktywnej grupy odbiorców.',
    benefits: [
      'Część dynamicznego Trójmiasta',
      'Nowoczesne centrum biznesowe',
      'Wysoki ruch turystyczny (morze, plaże)',
      'Liczne wydarzenia kulturalne i sportowe'
    ]
  }
}
