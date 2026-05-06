export interface CategoryDescription {
  title: string
  description: string
  benefits: string[]
}

export const categoryDescriptions: Record<string, CategoryDescription> = {
  // Wszystkie powierzchnie
  '': {
    title: 'Powierzchnie reklamowe w całej Polsce – Wynajem i Sprzedaż',
    description: 'Witamy na ReklaMap – największej platformie agregującej powierzchnie reklamowe w Polsce. Nasz serwis to kompleksowe narzędzie dla firm i osób prywatnych, które chcą skutecznie promować swój biznes w przestrzeni publicznej. Oferujemy dostęp do rosnącej bazy nośników: od tradycyjnych billboardów przy autostradach, przez eleganckie citylighty w centrach handlowych, po nowoczesne, cyfrowe ekrany LED (DOOH). Dzięki nam znajdziesz idealną lokalizację dla swojej kampanii, porównasz ceny i skontaktujesz się bezpośrednio z właścicielem nośnika, pomijając zbędnych pośredników. Nasza baza obejmuje zarówno duże aglomeracje, jak i mniejsze miejscowości, zapewniając pełne pokrycie ogólnopolskie.',
    benefits: [
      'Największa i najbardziej aktualna baza powierzchni reklamowych w Polsce',
      'Bezpośredni kontakt z wystawcą – ReklaMap jest bezpłatny',
      'Intuicyjna mapa interaktywna ułatwiająca wybór lokalizacji',
      'Możliwość porównania do 5 ogłoszeń jednocześnie według parametrów technicznych',
      'Zaawansowane filtry wyszukiwania: natężenie ruchu, oświetlenie, wymiary'
    ]
  },

  // Billboardy
  'billboardy': {
    title: 'Billboardy – Skuteczna Reklama Wielkoformatowa Outdoor',
    description: 'Billboardy to fundament sukcesu każdej kampanii outdoorowej. Są to nośniki o dużym formacie (standardowo 12m² i 18m²), projektowane tak, aby przyciągać wzrok z dużej odległości, szczególnie przy głównych arteriach komunikacyjnych, drogach krajowych i autostradach. Reklama na billboardach zapewnia stałą obecność marki w świadomości odbiorców przez 24 godziny na dobę. W naszej bazie znajdziesz billboardy tradycyjne, oświetlone, a także tablice typu prismatron. To idealne rozwiązanie do budowania zasięgu, promocji nowych produktów oraz informowania o lokalizacji punktów sprzedaży.',
    benefits: [
      'Zapewniają maksymalny zasięg wśród kierowców i pasażerów',
      'Oświetlenie typu LED gwarantuje widoczność po zmroku',
      'Idealne do kampanii wizerunkowych trwających od 1 do 12 miesięcy',
      'Możliwość wynajmu całych sieci nośników dla kampanii ogólnopolskich',
      'Wysoki parametr OTS (Opportunity To See) dzięki lokalizacji przy trasach o dużym natężeniu'
    ]
  },

  // Citylighty
  'citylighty': {
    title: 'Citylighty – Reklama w Sercu Miasta (Voucher na Klienta)',
    description: 'Citylighty to elegancka forma reklamy zewnętrznej, idealnie wkomponowana w tkankę miejską. Najczęściej spotykane na przystankach komunikacji miejskiej, deptakach oraz w okolicach galerii handlowych. Standardowy wymiar 120x180 cm pozwala na przedstawienie szczegółowych informacji, które odbiorca (pieszy lub pasażer) może dokładnie przestudiować. Dzięki wewnętrznemu podświetleniu, reklama na citylightach prezentuje się niezwykle atrakcyjnie po zmroku. Jest to niezastąpione narzędzie dla branży fashion, beauty, retail oraz dla instytucji kultury i miast promujących wydarzenia.',
    benefits: [
      'Bliskość odbiorcy – reklama na wysokości wzroku pieszego',
      'Prestiżowy wygląd dzięki przeszklonym obudowom i podświetleniu',
      'Dotarcie do osób oczekujących na transport publiczny (długi czas kontaktu)',
      'Idealne do działań prosprzedażowych i informacyjnych w centrach miast',
      'Możliwość zastosowania kreatywnych rozwiązań (np. zapachowych lub interaktywnych)'
    ]
  },

  // Ekrany LED
  'ekrany-led': {
    title: 'Ekrany LED i Telebimy – Nowoczesna Reklama Cyfrowa (DOOH)',
    description: 'Ekrany LED, znane również jako telebimy reklamowe, to obecnie najbardziej prestiżowy i przyciągający uwagę nośnik w arsenale reklamy zewnętrznej. Dzięki technologii Digital Out-of-Home (DOOH), Twoja kampania przestaje być statyczna. Możesz wyświetlać dynamiczne spoty wideo, animacje 2D/3D oraz zmieniać treść w czasie rzeczywistym w zależności od pogody, pory dnia czy aktualnych wyników sportowych. Wysoka jasność diod LED gwarantuje, że reklama będzie doskonale widoczna nawet w pełnym słońcu oraz w nocy z dużej odległości. To idealne rozwiązanie dla marek technologicznych, motoryzacyjnych oraz kinowych premier.',
    benefits: [
      'Brak kosztów druku i montażu fizycznego materiału',
      'Dynamiczny przekaz, który przyciąga wzrok o 60% skuteczniej niż statyczna tablica',
      'Elastyczność – możliwość emisji krótkich kampanii (np. tylko w weekendy)',
      'Wysoka rozdzielczość obrazu budująca nowoczesny wizerunek marki',
      'Możliwość integracji z mediami społecznościowymi (Live Feeds)'
    ]
  },

  // Banery
  'banery': {
    title: 'Banery Reklamowe i Siatki Mesh – Ekonomiczna Reklama Zewnętrzna',
    description: 'Banery reklamowe to jedno z najbardziej kosztowo-efektywnych rozwiązań w marketingu outdoorowym. Wykonane z wytrzymałych materiałów winylowych lub przepuszczających wiatr siatek mesh, doskonale sprawdzają się w miejscach o niestandardowych wymiarach – od ogrodzeń, przez elewacje budynków, aż po konstrukcje tymczasowe. Ich główną zaletą jest szybkość produkcji oraz łatwość montażu w niemal dowolnej lokalizacji. Są niezastąpione przy promocjach lokalnych, informowaniu o nowych inwestycjach deweloperskich czy jako nośniki kierunkowe prowadzące do Twojego lokalu.',
    benefits: [
      'Najniższy koszt wejścia w reklamę zewnętrzną',
      'Możliwość dopasowania do dowolnego formatu (produkcja na wymiar)',
      'Wysoka odporność na warunki atmosferyczne (deszcz, promienie UV)',
      'Łatwość przenoszenia reklamy w inne miejsce w trakcie trwania kampanii',
      'Idealne do kampanii sezonowych, wyprzedaży i eventów'
    ]
  },

  // Ściany reklamowe
  'sciany-reklamowe': {
    title: 'Ściany Reklamowe i Murale – Sztuka Reklamy Wielkoformatowej',
    description: 'Ściany reklamowe (często w formie murali lub wielkich siatek mesh) to najbardziej spektakularny format OOH, który na stałe wpisuje się w krajobraz miejski. Dzięki ogromnej powierzchni ekspozycji, reklama na elewacji budynku jest niemożliwa do przeoczenia i buduje ogromny prestiż marki. W dobie mediów społecznościowych estetyczne murale stają się często tłem dla zdjęć i zyskują darmowy zasięg "viralowy" w internecie. To format premium, idealny dla domów mody, branży gamingowej, technologicznej oraz wielkich kampanii wizerunkowych, które chcą zdominować przestrzeń danego miasta.',
    benefits: [
      'Największa powierzchnia ekspozycji dostępna w reklamie zewnętrznej',
      'Efekt "WOW" i budowanie dominacji marki w centrum aglomeracji',
      'Długi czas życia reklamy – widoczność przez wiele miesięcy w jednym punkcie',
      'Potencjał social media – murale generują tysiące zdjęć na Instagramie i TikToku',
      'Szansa na stworzenie reklamy, która staje się częścią miejskiej architektury'
    ]
  },

  // Totemy reklamowe
  'totemy-reklamowe': {
    title: 'Totemy Reklamowe – Pylony Reklamowe przy Galeriach i Biurowcach',
    description: 'Totemy reklamowe (pylony reklamowe) to wolnostojące, pionowe konstrukcje reklamowe umieszczane przy wjazdach do centrów handlowych, galerii, stacji benzynowych, biurowców i parkingów. Dzięki wysokości sięgającej od 3 do nawet 10 metrów, totem zapewnia doskonałą widoczność zarówno dla kierowców, jak i pieszych z odległości kilkudziesięciu metrów. Standardowy totem mieści od 1 do 5 kaset reklamowych, co pozwala na jednoczesną ekspozycję kilku najemców lub produktów. Nowoczesne wersje wyposażone są w podświetlenie LED, które gwarantuje pełną widoczność po zmroku i w trudnych warunkach atmosferycznych. Totem to niezbędny element oznakowania każdego centrum usługowego — pomaga klientom w nawigacji, jednocześnie budując rozpoznawalność marki w przestrzeni wokół obiektu. To niezastąpione medium dla sieci handlowych, restauracji, aptek i salonów samochodowych szukających ekspozycji bezpośrednio przy punkcie sprzedaży.',
    benefits: [
      'Widoczność z odległości do kilkudziesięciu metrów — zarówno dla kierowców, jak i pieszych',
      'Możliwość ekspozycji 2–5 marek lub produktów jednocześnie na jednej konstrukcji',
      'Podświetlenie LED gwarantuje widoczność przez całą dobę, niezależnie od warunków',
      'Idealne dla retail, gastronomii i usług — bezpośrednia ekspozycja przy punkcie sprzedaży',
      'Długa żywotność konstrukcji — jednorazowa inwestycja z wieloletnią ekspozycją marki'
    ]
  },

  // Reklama w transporcie
  'reklama-w-transporcie': {
    title: 'Reklama w Transporcie Publicznym – Mobilny Zasięg w Mieście',
    description: 'Reklama w transporcie to jedno z najbardziej dynamicznych mediów OOH, oferujące unikalną cechę: mobilność. Zamiast czekać na klienta, Twoja reklama sama do niego dociera, podróżując przez najważniejsze dzielnice i sypialnie miast. Nasza baza obejmuje oklejanie autobusów i tramwajów (Full Wrap lub Half Wrap), tablice reklamowe wewnątrz pojazdów oraz digital signage w systemach informacji pasażerskiej. To doskonały sposób na dotarcie do aktywnych osób, studentów i pracowników biurowych, którzy spędzają średnio 40-60 minut dziennie na dojazdach. Reklama w transporcie buduje wysoką częstotliwość kontaktu z marką i jest idealna do kampanii o szerokim profilu demograficznym.',
    benefits: [
      'Ruchomy charakter sprawia, że reklama „żyje” w różnych częściach miasta',
      'Wysoki czas ekspozycji i uwagi – pasażerowie szukają zajęcia podczas jazdy',
      'Doskonałe rozwiązanie dla kampanii rekrutacyjnych i lokalnych promocji',
      'Możliwość wyboru konkretnych linii autobusowych w wybranych dzielnicach',
      'Bardzo niski koszt dotarcia (CPT) w porównaniu do innych mediów'
    ]
  },

  // Reklama mobilna
  'reklama-mobilna': {
    title: 'Reklama Mobilna – Przyczepy i Pojazdy Reklamowe w Ruchu',
    description: 'Reklama mobilna (Mobile Billboard) to niezwykle skuteczny sposób na dotarcie tam, gdzie stacjonarne nośniki nie mają dostępu. Wykorzystując specjalnie przygotowane przyczepy lub oklejone pojazdy, możesz skierować swój komunikat dokładnie pod drzwi konkurencji, na parkingi centrów handlowych lub w miejsca dużych imprez masowych. Elastyczność planowania tras i godzin przejazdu pozwala na maksymalizację zasięgu w momentach największych szczytów komunikacyjnych. To idealne rozwiązanie przy otwarciu nowych sklepów, kampaniach politycznych lub akcjach promocyjnych "last-minute".',
    benefits: [
      'Możliwość dotarcia w miejsca o ograniczonej dostępności stałych nośników',
      'Precyzyjne targetowanie czasowe i geograficzne (Ty wybierasz trasę)',
      'Wysoki poziom uwagi odbiorców ze względu na nietypowy, ruchomy format',
      'Możliwość wzmocnienia przekazu audio (nagłośnienie z przyczepy)',
      'Doskonałe uzupełnienie stacjonarnych kampanii billboardowych'
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
    title: 'Powierzchnie reklamowe w Warszawie – Wynajem Billboardów i Screenów',
    description: 'Warszawa to największy i najbardziej konkurencyjny rynek reklamowy w Polsce. Reklama w stolicy to nie tylko prestiż, ale przede wszystkim szansa na dotarcie do ponad 2 milionów mieszkańców oraz setek tysięcy osób codziennie dojeżdżających do pracy. Nasza oferta w Warszawie obejmuje wielkoformatowe siatki na budynkach w centrum, ekrany LED przy rondzie Dmowskiego, a także gęstą sieć billboardów przy trasach wylotowych (S8, S2, autostrada A2). Wybierając powierzchnie reklamowe w Warszawie z ReklaMap, zyskujesz pewność najlepszej ekspozycji w kluczowych węzłach komunikacyjnych i dzielnicach biznesowych takich jak Wola czy Mokotów.',
    benefits: [
      'Najwyższy zasięg i częstotliwość kontaktu w kraju',
      'Dostęp do najbardziej prestiżowych lokalizacji (Mordor, Centrum, Bulwary)',
      'Szeroki wybór nośników DOOH (Digital Out-of-Home)',
      'Możliwość targetowania na konkretne grupy (studenci, biznes, rodziny)',
      'Doskonałe pokrycie głównych ciągów komunikacyjnych i stacji Metra'
    ]
  },

  'krakow': {
    title: 'Powierzchnie reklamowe w Krakowie – Billboardy i Reklama Miejska',
    description: 'Kraków, jako stolica Małopolski i drugie co do wielkości miasto w kraju, to unikalne miejsce na mapie polskiej reklamy outdoorowej. Reklama w Krakowie pozwala dotrzeć do zamożnych mieszkańców, ponad 200 tysięcy studentów oraz – co kluczowe – do milionów turystów z całego świata odwiedzających Stare Miasto i Kazimierz. Nasza baza w Krakowie obejmuje prestiżowe nośniki przy obwodnicy (A4), billboardy przy trasach wlotowych od strony Katowic i Warszawy, a także nowoczesne citylighty w okolicach krakowskich galerii handlowych. Strategiczna obecność w Krakowie to gwarancja rozpoznawalności marki w mieście, które łączy historyczny prestiż z nowoczesnym sektorem usług i IT (Kraków Technology Park).',
    benefits: [
      'Dotarcie do ogromnej liczby turystów krajowych i zagranicznych',
      'Wysoka ekspozycja w kluczowych węzłach komunikacyjnych (Rondo Mogilskie, Matecznego)',
      'Obecność w największym w Polsce skupisku firm z sektora nowoczesnych usług biznesowych',
      'Szeroki wybór nośników typu citylight w centrach przesiadkowych',
      'Doskonała widoczność przy trasach prowadzących w stronę Zakopanego i lotniska Balice'
    ]
  },

  'wroclaw': {
    title: 'Powierzchnie reklamowe we Wrocławiu – Wynajem Billboardów i Nośników OOH',
    description: 'Wrocław, miasto spotkań i stolica Dolnego Śląska, to jeden z najprężniej rozwijających się ośrodków gospodarczych w Europie Środkowej. Reklama we Wrocławiu to szansa na kontakt z młodą, dynamiczną i wykształconą populacją. Nasza platforma oferuje dostęp do nośników wielkoformatowych przy autostradzie A4 i drodze S5, a także do nowoczesnych ekranów LED w okolicach Sky Tower i wrocławskiego Rynku. Dzięki setkom biurowców i obecności globalnych korporacji, Wrocław jest idealnym miejscem dla kampanii B2B oraz promowania usług premium. Wykorzystaj potencjał miasta, w którym krzyżują się szlaki handlowe z zachodniej Europy.',
    benefits: [
      'Jedna z najszybciej rosnących grup konsumenckich w Polsce',
      'Wysokie natężenie ruchu na Autostradowej Obwodnicy Wrocławia (AOW)',
      'Doskonała widoczność w okolicach dużych hubów komunikacyjnych (Dworzec Główny, Lotnisko)',
      'Prestiżowe lokalizacje przy centrach innowacji i parkach technologicznych',
      'Duży udział nośników oświetlonych gwarantujących widoczność 24/7'
    ]
  },

  'poznan': {
    title: 'Powierzchnie reklamowe w Poznaniu – Billboardy przy Trasach i w Mieście',
    description: 'Poznań to synonim solidności i dobrej organizacji biznesowej. Jako gospodarz Międzynarodowych Targów Poznańskich (MTP), miasto przyciąga setki tysięcy profesjonalistów z całego świata. Reklama w Poznaniu pozwala na skuteczne dotarcie do klienta biznesowego oraz mieszkańców o wysokiej sile nabywczej. Nasza baza obejmuje nośniki przy autostradzie A2 (szlak Berlin-Warszawa), billboardy wzdłuż ulicy Głogowskiej oraz nowoczesne formaty OOH w okolicach Starego Browaru i dworca Poznań Główny. To idealna lokalizacja dla kampanii targowych, przemysłowych oraz motoryzacyjnych.',
    benefits: [
      'Bezpośrednie dotarcie do uczestników największych imprez targowych w Polsce',
      'Strategiczne położenie na głównym szlaku transportowym wschód-zachód',
      'Wysoka skuteczność reklamy przy kluczowych centrach handlowo-rozrywkowych',
      'Doskonałe parametry widoczności na trasach wylotowych w stronę Niemiec',
      'Stabilny i przewidywalny rynek z dużą lojalnością klientów lokalnych'
    ]
  },

  'gdansk': {
    title: 'Powierzchnie reklamowe w Gdańsku – Reklama w Trójmieście',
    description: 'Gdańsk to serce Trójmiasta, kluczowy port Bałtyku i jeden z najważniejszych ośrodków turystycznych w Europie. Reklama w Gdańsku to szansa na dotarcie do unikalnego miksu odbiorców: stałych mieszkańców aglomeracji, pracowników dynamicznego sektora IT (Oliwa Business Centre) oraz morskiego przemysłu. W sezonie letnim Gdańsk staje się centrum polskiej turystyki, co radykalnie podnosi parametry OTS dostępnych nośników. Oferujemy billboardy przy Obwodnicy Trójmiasta, reklamy wielkoformatowe przy Trasie Słowackiego oraz ekrany w prestiżowych lokalizacjach nadmorskich.',
    benefits: [
      'Synergia z pozostałymi miastami aglomeracji (Gdynia, Sopot)',
      'Sezonowy wzrost zasięgu dzięki milionom turystów nadmorskich',
      'Obecność przy kluczowych węzłach transportowych (Port Gdańsk, Lotnisko)',
      'Dostęp do nowoczesnych nośników cyfrowych w dzielnicach biznesowych',
      'Wysoka siła oddziaływania reklamy w miejscach wypoczynku i rekreacji'
    ]
  },

  'lodz': {
    title: 'Powierzchnie reklamowe w Łodzi – Billboardy i Murale',
    description: 'Łódź to miasto o niesamowitej energii, przechodzące jedną z najbardziej spektakularnych rewitalizacji w tej części Europy. Reklama w Łodzi to dotarcie do mieszkańców miasta, które staje się hubem logistycznym Polski. Dzięki unikalnej architekturze, Łódź jest polską stolicą murali reklamowych – wielkoformatowych malowideł na bocznych ścianach kamienic, które stają się viralowymi atrakcjami. Oferujemy również szeroką bazę billboardów przy ulicy Piotrkowskiej, nośników przy Manufakturze oraz ekspozycję wzdłuż autostrady A1 i drogi ekspresowej S14.',
    benefits: [
      'Unikalne formaty reklamowe (murale artystyczne na elewacjach)',
      'Strategiczne położenie w samym centrum siatki autostrad w Polsce',
      'Dynamicznie rosnąca siła nabywcza dzięki nowym inwestycjom biznesowym',
      'Wysoki poziom uwagi odbiorców w zrewitalizowanych przestrzeniach miejskich',
      'Bardzo dobry współczynnik kosztu dotarcia (CPM) względem zasięgu'
    ]
  },

  'katowice': {
    title: 'Powierzchnie reklamowe w Katowicach i GOP – Billboardy na Śląsku',
    description: 'Reklama w Katowicach to brama do największego obszaru metropolitalnego w Polsce – Górnośląsko-Zagłębiowskiej Metropolii. To rynek obejmujący ponad 2 miliony konsumentów żyjących w gęsto zaludnionych miastach połączonych Drogową Trasą Średnicową (DTŚ). Nośniki w Katowicach, Chorzowie czy Sosnowcu oferują rekordowe parametry OTS ze względu na ogromne natężenie ruchu lokalnego i tranzytowego. Nasza baza to przede wszystkim billboardy przy autostradzie A4, ekrany LED w okolicach Spodka i Międzynarodowego Centrum Kongresowego oraz prestiżowe siatki mesh w samym centrum Katowic.',
    benefits: [
      'Największa koncentracja ludności i potencjalnych klientów w promieniu 20km',
      'Ciągła ekspozycja przy kluczowej arterii regionu – Trasie Średnicowej',
      'Doskonałe dotarcie do klienta biznesowego (Strefy Ekonomiczne)',
      'Wysoka efektywność kampanii outdoorowych przy dużych skupiskach retail',
      'Potencjał cross-border dzięki bliskości granicy czeskiej i słowackiej'
    ]
  },

  'szczecin': {
    title: 'Powierzchnie reklamowe w Szczecinie – Reklama przy Granicy i w Porcie',
    description: 'Szczecin, stolica Pomorza Zachodniego, to miasto o unikalnym położeniu geopolitycznym. Reklama w Szczecinie pozwala na dotarcie do mieszkańców regionu oraz tysięcy osób korzystających z bliskości granicy z Niemcami i portu morskiego. Nasza oferta obejmuje billboardy przy trasach wjazdowych (S3, S6), nośniki w centrum miasta przy Placu Rodła oraz powierzchnie reklamowe blisko centrów handlowych (Galaxy, Kaskada). Dzięki dynamicznej rewitalizacji Bulwarów i nabrzeża, Szczecin oferuje również atrakcyjne lokalizacje dla kampanii sezonowych i turystycznych. To brama na rynki zachodnie, idealna dla firm transportowych, logistycznych i handlowych.',
    benefits: [
      'Strategiczne sąsiedztwo z Niemcami i Berlinem (zaledwie 130 km)',
      'Wysoka ekspozycja przy kluczowych arteriaach prowadzących nad morze',
      'Dotarcie do pracowników sektora morskiego i logistycznego',
      'Nowoczesne nośniki w zrewitalizowanych częściach miasta',
      'Doskonały stosunek ceny do zasięgu na tle innych miast wojewódzkich'
    ]
  },

  'bydgoszcz': {
    title: 'Powierzchnie reklamowe w Bydgoszczy – Billboardy w Centrum Polski',
    description: 'Bydgoszcz to jeden z największych ośrodków przemysłowo-handlowych w Polsce północnej. Reklama w Bydgoszczy to skuteczny sposób na dotarcie do ponad 300 tysięcy mieszkańców oraz tysięcy osób dojeżdżających do pracy i na studia. Miasto, jako ważny węzeł komunikacyjny, oferuje doskonałą widoczność przy drogach krajowych (DK10, DK25) oraz w strategicznych punktach centrum, takich jak okolice Ronda Jagiellonów. Dzięki obecności licznych parków przemysłowych i technologicznych, Bydgoszcz jest atrakcyjnym miejscem dla kampanii rekrutacyjnych oraz promowania usług dla biznesu i konsumentów.',
    benefits: [
      'Stały dopływ odbiorców dzięki strategicznemu węzłowi komunikacyjnemu',
      'Wysoka skuteczność reklamy przy centrach handlowych (Focus, Zielone Arkady)',
      'Dotarcie do wykwalifikowanej kadry sektora IT i przemysłu obronnego',
      'Atrakcyjne ceny najmu powierzchni przy zachowaniu dużego zasięgu',
      'Możliwość precyzyjnego targetowania na osiedla sypialne i strefy biznesu'
    ]
  },

  'lublin': {
    title: 'Powierzchnie reklamowe w Lublinie – Reklama w Stolicy Polski Wschodniej',
    description: 'Lublin, największe miasto we wschodniej Polsce, to dynamicznie rozwijający się ośrodek akademicki i biznesowy. Reklama w Lublinie pozwala na dotarcie do młodej, aktywnej grupy odbiorców (ponad 60 tysięcy studentów) oraz do biznesu zorientowanego na rynki wschodnie. Nasza baza obejmuje billboardy przy drodze ekspresowej S12/S17, nośniki wielkoformatowe przy Al. Spółdzielczości Pracy oraz powierzchnie w samym sercu miasta. Lublin to brama na Wschód, idealna lokalizacja dla kampanii edukacyjnych, retail oraz logistycznych, oferująca bardzo dobre parametry zasięgowe w nowoczesnej infrastrukturze miejskiej.',
    benefits: [
      'Najsilniejsze dotarcie do studentów i młodych dorosłych w regionie',
      'Strategiczne położenie na trasie do przejść granicznych',
      'Dynamicznie rosnąca infrastruktura biznesowa i handlowa',
      'Wysoka częstotliwość kontaktu w okolicach uczelni i akademików',
      'Rosnący prestiż reklamowy dzięki tytułowi Europejskiej Stolicy Kultury (poprzedni) i Młodzieży'
    ]
  },

  'bialystok': {
    title: 'Powierzchnie reklamowe w Białymstoku – Billboardy na Podlasiu',
    description: 'Białystok to główne centrum gospodarcze i administracyjne północno-wschodniej Polski. Reklama w Białymstoku to szansa na skuteczne dotarcie do blisko 300 tysięcy mieszkańców oraz osób podróżujących w stronę krajów bałtyckich (Via Baltica). Nasza platforma oferuje dostęp do nośników przy drogach S8 i DK19, a także billboardy w kluczowych lokalizacjach miasta, jak okolice Al. Jana Pawła II. Białystok charakteryzuje się dużą lojalnością klientów wobec lokalnych marek, co sprawia, że kampanie w tym mieście mają bardzo wysoki współczynnik konwersji.',
    benefits: [
      'Dotarcie do największej aglomeracji w Polsce północno-wschodniej',
      'Wysokie natężenie ruchu tranzytowego w stronę Litwy i Estonii',
      'Skuteczna reklama przy rozbudowanych centrach handlowych regionu',
      'Dynamiczny rozwój infrastruktury miejskiej sprzyjający nowym nośnikom OOH',
      'Możliwość dotarcia do szerokiego grona odbiorców rolniczych i handlowych'
    ]
  },

  'gdynia': {
    title: 'Powierzchnie reklamowe w Gdyni – Reklama w Gdynia Business Center',
    description: 'Gdynia to najnowocześniejsza część Trójmiasta, miasto portowe o ogromnym potencjale innowacyjnym i biznesowym. Reklama w Gdyni pozwala na dotarcie do zamożnych mieszkańców, pracowników nowoczesnych biurowców (PPNT, Łużycka) oraz tysięcy turystów odwiedzających Skwer Kościuszki. Nasza baza obejmuje billboardy przy Estakadzie Kwiatkowskiego, nośniki wielkoformatowe wzdłuż Drogi Gdyńskiej oraz powierzchnie reklamowe w prestiżowym Orłowie. Gdynia to idealne miejsce dla marek premium, usług finansowych oraz firm związanych z gospodarką morską i nowoczesnymi technologiami.',
    benefits: [
      'Prestiż i nowoczesność – Gdynia buduje wizerunek innowacyjnej marki',
      'Wysoka siła nabywcza mieszkańców (jedne z wyższych zarobków w kraju)',
      'Unikalne lokalizacje reklamowe w pobliżu portów jachtowych i biur',
      'Zasięg ogólnopolski w trakcie dużych wydarzeń (Open\'er Festival)',
      'Bezpośrednie dotarcie do klienta biznesowego i wyższej kadry zarządzającej'
    ]
  }
}

export const typeCityDescriptions: Record<string, CategoryDescription> = {

  // ── BILLBOARDY ────────────────────────────────────────────────────────────

  'billboardy-warszawa': {
    title: 'Billboardy w Warszawie – Wynajem Wielkoformatowych Nośników Reklamowych',
    description: 'Billboardy w Warszawie to najskuteczniejszy sposób dotarcia do ponad 2 milionów mieszkańców stolicy i setek tysięcy osób codziennie dojeżdżających do pracy. Największy popyt dotyczy lokalizacji przy trasach wylotowych (S8, A2, S2), Obwodnicy Śródmiejskiej oraz kluczowych węzłach komunikacyjnych — Rondo ONZ, Rondo Wiatraczna i Rondo Daszyńskiego. Standardowy format 12m² zapewnia ekspozycję całą dobę w miejscach o najwyższym natężeniu ruchu w Polsce.',
    benefits: [
      'Najwyższe zasięgi w kraju — ponad 2 mln mieszkańców i dzienny ruch wahadłowy',
      'Kluczowe lokalizacje przy A2, S8, S2 i Obwodnicy Śródmiejskiej',
      'Koncentracja kampanii wizerunkowych i produktowych dla rynku ogólnopolskiego',
      'Dostęp do prestiżowych lokalizacji przy centrum biznesowym (Wola, Mokotów)',
      'Możliwość budowania zasięgu na całej trasie przejazdu przez miasto'
    ]
  },

  'billboardy-krakow': {
    title: 'Billboardy w Krakowie – Reklama Wielkoformatowa w Stolicy Małopolski',
    description: 'Billboardy w Krakowie docierają jednocześnie do ponad 800 tysięcy mieszkańców, 200 tysięcy studentów i ponad 14 milionów turystów rocznie. Kluczowe lokalizacje to autostrada A4 (wjazd od Katowic i Tarnowa), Trasa Łagiewnicka, rondo Mogilskie oraz drogi prowadzące do lotniska Balice i Zakopanego. Kraków to jeden z najefektywniejszych, choć zarazem najbardziej konkurencyjnych rynków billboardowych w Polsce.',
    benefits: [
      'Dotarcie do turystów z całego świata odwiedzających Stare Miasto i Wawel',
      'Strategiczne lokalizacje przy A4 — szlak Katowice–Kraków–Tarnów',
      'Duże skupisko studentów i środowisk akademickich (AGH, UJ, Politechnika)',
      'Wysoka efektywność kampanii przy trasach do lotniska Balice i na południe kraju',
      'Unikalne połączenie odbiorców turystycznych, biznesowych i konsumenckich'
    ]
  },

  'billboardy-wroclaw': {
    title: 'Billboardy we Wrocławiu – Reklama Zewnętrzna na Dolnym Śląsku',
    description: 'Billboardy we Wrocławiu zapewniają dostęp do jednego z najszybciej rosnących rynków konsumenckich w Polsce. Najlepsze lokalizacje skupiają się przy Autostradowej Obwodnicy Wrocławia (AOW), autostradzie A4 i ul. Legnickiej prowadzącej do centrum. Wrocław przyciąga inwestorów i pracowników globalnych korporacji — billboard tutaj jest równie skuteczny w kampaniach konsumenckich, jak i B2B.',
    benefits: [
      'Jeden z najszybciej rosnących rynków reklamowych w Polsce',
      'Wysokie natężenie ruchu na AOW i A4 — szlak Berlin–Wrocław–Katowice',
      'Skupisko firm technologicznych i BPO/SSC (Nokia, Google, Volvo IT)',
      'Dostęp do prestiżowych lokalizacji w okolicach Sky Tower i centrum',
      'Doskonała widoczność przy trasach prowadzących do lotniska Strachowice'
    ]
  },

  'billboardy-poznan': {
    title: 'Billboardy w Poznaniu – Reklama Wielkoformatowa na Szlaku Berlin–Warszawa',
    description: 'Billboardy w Poznaniu to strategiczna inwestycja reklamowa na głównym szlaku komunikacyjnym Europy — autostradzie A2 Berlin–Warszawa. Miasto przyciąga corocznie setki tysięcy gości na Międzynarodowe Targi Poznańskie, tworząc unikalną okazję dla kampanii B2B. Kluczowe lokalizacje billboardowe to okolice MTP, ul. Głogowska, Rondo Rataje i trasy dojazdowe z zachodniej granicy.',
    benefits: [
      'Strategiczne położenie na autostradzie A2 Berlin–Warszawa',
      'Unikalna ekspozycja podczas imprez targowych MTP (200+ tys. gości rocznie)',
      'Silny rynek B2B — idealne dla branży przemysłowej i motoryzacyjnej',
      'Dostęp do zamożnych mieszkańców Jeżyc i Piątkowa',
      'Niższe stawki niż w Warszawie przy podobnym profilu odbiorcy biznesowego'
    ]
  },

  'billboardy-gdansk': {
    title: 'Billboardy w Gdańsku – Reklama Outdoor w Trójmieście',
    description: 'Billboardy w Gdańsku docierają do ponad 600 tysięcy mieszkańców Gdańska i całego Trójmiasta, milionów turystów odwiedzających Długi Targ i Westerplatte, a także do pracowników największego portu bałtyckiego w Polsce. Strategiczne lokalizacje przy obwodnicy Trójmiasta (S6), Trasie Słowackiego i drogach dojazdowych do portu gwarantują pełną ekspozycję całą dobę.',
    benefits: [
      'Zasięg na całe Trójmiasto — Gdańsk, Gdynia, Sopot (ponad 750 tys. mieszkańców)',
      'Kluczowe lokalizacje przy S6 i Trasie Słowackiego z wysokim natężeniem ruchu',
      'Silny sezon turystyczny — wzmożony ruch od maja do września',
      'Dostęp do zamożnych konsumentów i kadry zarządzającej Grupy Lotos i PKO BP',
      'Specyficzny rynek morski i logistyczny — idealne dla B2B i kampanii B2C'
    ]
  },

  // ── CITYLIGHTY ────────────────────────────────────────────────────────────

  'citylighty-warszawa': {
    title: 'Citylighty w Warszawie – Reklama przy Przystankach i w Centrach Handlowych',
    description: 'Citylighty w Warszawie to format idealny dla marek celujących w aktywnych mieszkańców stolicy — osoby dojeżdżające metrem, tramwajem i autobusami ZTM. Największa sieć nośników koncentruje się przy przystankach na Śródmieściu, Woli i Ursynowie, wzdłuż Al. Jerozolimskich i ul. Marszałkowskiej. Standardowy format 120×180 cm podświetlany od środka zapewnia czas kontaktu nawet kilka minut przy każdym oczekiwaniu na transport.',
    benefits: [
      'Wysoki czas kontaktu — pasażerowie czytają reklamy podczas oczekiwania na pojazd',
      'Gęsta sieć nośników na głównych ciągach pieszych i przystankach ZTM',
      'Idealne dla branży fashion, beauty, retail i promocji wydarzeń kulturalnych',
      'Podświetlenie LED gwarantuje pełną widoczność przez całą dobę',
      'Możliwość precyzyjnego targetowania na konkretne dzielnice i linie komunikacyjne'
    ]
  },

  'citylighty-krakow': {
    title: 'Citylighty w Krakowie – Reklama w Centrum i przy Przystankach MPK',
    description: 'Citylighty w Krakowie docierają do pieszych i pasażerów komunikacji miejskiej w jednym z najbardziej turystycznych miast Polski. Kluczowe lokalizacje to okolice Dworca Głównego, al. Mickiewicza, Nowy Kleparz oraz Galeria Krakowska. Podświetlana forma nośnika sprawia, że reklama działa efektywnie zarówno w dzień, jak i wieczorem, gdy turyści i mieszkańcy najchętniej przemieszczają się pieszo.',
    benefits: [
      'Dotarcie do turystów w kluczowych punktach tranzytowych Krakowa',
      'Ekspozycja przy przystankach MPK o bardzo wysokim dziennym przepływie pasażerów',
      'Wysoka skuteczność kampanii dla branży gastronomicznej, hotelarskiej i rozrywkowej',
      'Nośniki przy Dworcu Głównym z ekspozycją na przyjezdnych z całej Polski',
      'Format szczególnie skuteczny podczas Festiwalu Filmowego i imprez masowych'
    ]
  },

  'citylighty-wroclaw': {
    title: 'Citylighty we Wrocławiu – Reklama przy Przystankach MPK i w Galeriach',
    description: 'Citylighty we Wrocławiu sprawdzają się szczególnie w sąsiedztwie uczelni wyższych (Politechnika Wrocławska, Uniwersytet Wrocławski), centrów handlowych (Magnolia Park, Galeria Dominikańska) i przystanków MPK przy pl. Dominikańskim i ul. Świdnickiej. Wrocław ma jeden z wyższych wskaźników korzystania z komunikacji miejskiej wśród dużych miast Polski, co przekłada się na wysoki codzienny czas ekspozycji nośników.',
    benefits: [
      'Wysoki udział komunikacji miejskiej — pasażerowie MPK to Twoi stali odbiorcy',
      'Dostęp do środowisk studenckich (ponad 100 tys. studentów we Wrocławiu)',
      'Strategiczne lokalizacje przy Magnolia Park i Galerii Dominikańskiej',
      'Idealne dla kampanii FMCG, telekomunikacji i usług finansowych',
      'Niższy koszt dotarcia niż w Warszawie przy zbliżonym profilu konsumenta'
    ]
  },

  'citylighty-poznan': {
    title: 'Citylighty w Poznaniu – Reklama w Centrum i przy Przystankach MPK',
    description: 'Citylighty w Poznaniu docierają do ponad 200 tysięcy studentów i aktywnych mieszkańców centrum. Najlepsze lokalizacje to okolice Jeżyc, Starego Rynku, przystanki przy ul. Święty Marcin i Rondo Kaponiera. W trakcie targów MTP i dużych eventów ruch pieszych w centrum Poznania wzrasta o kilkadziesiąt procent, znacząco podnosząc zasięg citylightów.',
    benefits: [
      'Duże skupisko studentów i młodych profesjonalistów w centrum',
      'Wysoka efektywność kampanii przy Starym Rynku i Jeżycach',
      'Wzrost zasięgu podczas targów MTP i eventów miejskich',
      'Idealne dla branży odzieżowej, gastronomicznej i usług miejskich',
      'Konkurencyjne stawki cenowe przy wysokiej jakości ekspozycji'
    ]
  },

  'citylighty-gdansk': {
    title: 'Citylighty w Gdańsku – Reklama Miejska w Trójmieście',
    description: 'Citylighty w Gdańsku są szczególnie efektywne w sezonie turystycznym (maj–wrzesień), gdy miliony turystów przemierzają pieszo Stare Miasto, Oliwę i pobliski Sopot. Poza sezonem format celuje w aktywnych mieszkańców Trójmiasta korzystających z SKM, tramwajów i autobusów ZTM Gdańsk. Kluczowe lokalizacje to otoczenie Dworca Głównego, al. Grunwaldzka i ul. Długa w Śródmieściu.',
    benefits: [
      'Sezonowy wzrost zasięgu latem — miliony turystów na Długim Targu i w Oliwie',
      'Całoroczna ekspozycja przy głównych węzłach komunikacji SKM i tramwajowej',
      'Dostęp do zamożnych mieszkańców Wrzeszcza, Oliwy i Sopotu',
      'Idealne dla branży turystycznej, hotelarskiej i handlowej',
      'Możliwość łączenia kampanii z citylightami w Gdyni i Sopocie'
    ]
  },

  // ── EKRANY LED ────────────────────────────────────────────────────────────

  'ekrany-led-warszawa': {
    title: 'Ekrany LED w Warszawie – Cyfrowa Reklama DOOH w Stolicy',
    description: 'Ekrany LED w Warszawie to najbardziej prestiżowy format reklamy cyfrowej (DOOH) w Polsce. Największe skupisko nośników LED znajdziesz przy Rondzie ONZ, ul. Marszałkowskiej, centrum Złote Tarasy i Dworcu Centralnym. Dzięki możliwości zmiany treści w czasie rzeczywistym ekrany LED w stolicy idealnie nadają się do kampanii reaktywnych, premier produktów i live marketingu podczas wydarzeń sportowych.',
    benefits: [
      'Największy rynek ekranów LED w Polsce — setki lokalizacji w całym mieście',
      'Dynamiczny przekaz wideo przyciąga uwagę 60% skuteczniej niż nośniki statyczne',
      'Możliwość targetowania czasowego — różne kreacje rano, w południe i wieczorem',
      'Brak kosztów druku i logistyki przy zmianie kampanii',
      'Idealne do kampanii real-time (pogoda, wyniki sportowe, odliczanie do eventu)'
    ]
  },

  'ekrany-led-krakow': {
    title: 'Ekrany LED w Krakowie – Cyfrowa Reklama Zewnętrzna DOOH',
    description: 'Ekrany LED w Krakowie koncentrują się w okolicach Galerii Krakowskiej, ronda Mogilskiego i głównych arterii miejskich. Format DOOH w Krakowie zyskuje szczególną wartość podczas masowych wydarzeń kulturalnych — Cracovia Marathon, Festiwalu Filmowego, Wianków — gdy miasto odwiedzają dziesiątki tysięcy dodatkowych osób dziennie. Wysoka jasność i dynamiczny przekaz gwarantują najwyższy współczynnik zauważalności w całej Małopolsce.',
    benefits: [
      'Najwyższa zauważalność spośród wszystkich formatów OOH w centrum Krakowa',
      'Szczególna efektywność podczas licznych festiwali i wydarzeń masowych',
      'Możliwość emisji treści dostosowanych do turystów (w kilku językach)',
      'Koncentracja nośników przy Galerii Krakowskiej i Dworcu Głównym',
      'Elastyczność kampanii — możliwość emisji tylko w weekendy lub w godzinach szczytu'
    ]
  },

  'ekrany-led-wroclaw': {
    title: 'Ekrany LED we Wrocławiu – Nowoczesna Reklama Cyfrowa DOOH',
    description: 'Ekrany LED we Wrocławiu to format rozwijający się najszybciej spośród wszystkich nośników OOH w mieście. Kluczowe lokalizacje to okolice Sky Tower, rondo Reagana i Dworzec Wrocław Główny. Podczas wydarzeń takich jak Brave Festival, Wratislavia Cantans czy Maraton Wrocław, ekrany LED przy głównych ciągach komunikacyjnych osiągają rekordowe zasięgi dobowe.',
    benefits: [
      'Najdynamiczniej rosnący segment OOH we Wrocławiu',
      'Prestiżowe lokalizacje przy Sky Tower i centrum biznesowym',
      'Wysoka efektywność podczas licznych imprez kulturalnych i sportowych',
      'Dotarcie do pracowników firm technologicznych i BPO/SSC',
      'Możliwość integracji kampanii z reklamą digital (retargeting po ekspozycji DOOH)'
    ]
  },

  'ekrany-led-poznan': {
    title: 'Ekrany LED w Poznaniu – Cyfrowa Reklama Zewnętrzna DOOH',
    description: 'Ekrany LED w Poznaniu najlepiej sprawdzają się w okolicach Starego Browaru, CH King Cross Marcelin i Ronda Kaponiera. W trakcie targów MTP i dużych eventów lokalne ekrany LED osiągają wielokrotnie wyższe zasięgi niż w standardowe dni robocze. Format szczególnie polecany dla branży motoryzacyjnej, technologicznej i marek premium kierujących przekaz do decydentów biznesowych.',
    benefits: [
      'Strategiczne lokalizacje przy Starym Browarze i centrum handlowym King Cross',
      'Wzmożone zasięgi podczas targów MTP — bezpośrednie dotarcie do gości biznesowych',
      'Rosnąca sieć nośników cyfrowych w centrum i przy głównych arteriach',
      'Idealny format dla kampanii premier, launchy produktów i eventów',
      'Możliwość elastycznej emisji dopasowanej do kalendarza targowego'
    ]
  },

  'ekrany-led-gdansk': {
    title: 'Ekrany LED w Gdańsku – Cyfrowa Reklama Outdoor w Trójmieście',
    description: 'Ekrany LED w Gdańsku i całym Trójmieście oferują ekspozycję na jednym z najbardziej turystycznych rynków reklamowych w Polsce. Najaktywniejsze lokalizacje to okolice Forum Gdańsk, ul. Długa w Śródmieściu i węzeł komunikacyjny przy Dworcu Głównym. Sezonowość rynku (wzmożony ruch latem) to idealna okazja do elastycznych kampanii DOOH dostosowanych do napływu turystów krajowych i zagranicznych.',
    benefits: [
      'Ekspozycja na turystów krajowych i zagranicznych w sezonie letnim',
      'Kluczowe lokalizacje przy Forum Gdańsk i Dworcu Głównym',
      'Zasięg na całe Trójmiasto dzięki wysokiemu natężeniu ruchu na arteriach',
      'Możliwość kampanii wielojęzycznych skierowanych do turystów zagranicznych',
      'Rosnąca sieć nośników cyfrowych przy głównych centrach handlowych'
    ]
  },

  // ── BILLBOARDY — Łódź i Katowice ─────────────────────────────────────────

  'billboardy-lodz': {
    title: 'Billboardy w Łodzi – Reklama Wielkoformatowa na Skrzyżowaniu Autostrad',
    description: 'Billboardy w Łodzi oferują jedne z najkorzystniejszych stawek na mapie dużych polskich miast przy jednoczesnym zasięgu ponad 650 tysięcy mieszkańców. Miasto leży na skrzyżowaniu autostrad A1 (północ–południe) i A2 (wschód–zachód), tworząc naturalne centrum logistyczne Polski. Billboardy przy węźle Łódź Centrum, al. Piłsudskiego i trasach wylotowych na Warszawę i Wrocław należą do najchętniej wybieranych lokalizacji w regionie.',
    benefits: [
      'Strategiczne położenie na skrzyżowaniu A1 i A2 — centrum logistyczne Polski',
      'Jedne z najniższych stawek CPT wśród dużych miast przy wysokim zasięgu',
      'Dostęp do ponad 650 tys. mieszkańców i dużego ruchu tranzytowego',
      'Kluczowe lokalizacje przy węźle autostradowym i ul. Piotrkowskiej',
      'Idealne dla kampanii ogólnopolskich z centrum dystrybucji w Łodzi'
    ]
  },

  'billboardy-katowice': {
    title: 'Billboardy w Katowicach – Reklama Outdoor w Sercu Górnośląskiej Metropolii',
    description: 'Billboardy w Katowicach to dostęp do ponad 2 milionów mieszkańców Górnośląsko-Zagłębiowskiej Metropolii — największego skupiska miejskiego w Polsce. Autostrada A4 przecinająca aglomerację śląską oraz DTŚ i DK86 generują jedne z najwyższych natężeń ruchu samochodowego w kraju. Reklama w Katowicach dociera jednocześnie do Gliwic, Chorzowa, Bytomia i Sosnowca, co czyni ten rynek wyjątkowo efektywnym kosztowo.',
    benefits: [
      'Zasięg na całą Metropolię GZM — ponad 2 mln mieszkańców',
      'Jedno z najwyższych natężeń ruchu w Polsce (A4, DTŚ, DK86)',
      'Niski koszt dotarcia (CPT) przy bardzo szerokim zasięgu geograficznym',
      'Skupisko przemysłu, logistyki i sektora usług — idealne dla B2B',
      'Możliwość łączenia kampanii z nośnikami w Gliwicach, Chorzowie i Sosnowcu'
    ]
  },

  // ── CITYLIGHTY — Łódź i Katowice ─────────────────────────────────────────

  'citylighty-lodz': {
    title: 'Citylighty w Łodzi – Reklama przy Przystankach MPK i w Centrum',
    description: 'Citylighty w Łodzi skupiają się przy przystankach komunikacji miejskiej wzdłuż ul. Piotrkowskiej, al. Piłsudskiego i w okolicach dużych centrów handlowych (Manufaktura, Galeria Łódzka). Łódź dynamicznie się zmienia — rewitalizacja centrum i rosnąca liczba studentów sprawiają, że reklama w sercu miasta dociera do coraz bardziej aktywnej i zamożniejszej grupy odbiorców. Citylighty to najtańszy format dla kampanii citywide w dużym mieście.',
    benefits: [
      'Dostęp do studentów i mieszkańców rewitalizowanego centrum Łodzi',
      'Gęsta sieć nośników wzdłuż ul. Piotrkowskiej i głównych arterii',
      'Ekspozycja przy Manufakturze i Galerii Łódzkiej z wysokim ruchem pieszych',
      'Konkurencyjne stawki przy szerokim zasięgu miejskim',
      'Idealne dla kampanii lokalnych sieci handlowych i usługowych'
    ]
  },

  'citylighty-katowice': {
    title: 'Citylighty w Katowicach – Reklama Miejska w Centrum Metropolii Śląskiej',
    description: 'Citylighty w Katowicach docierają do aktywnych mieszkańców centrum i pasażerów KZK GOP — jednego z największych organizatorów komunikacji miejskiej w Polsce. Kluczowe lokalizacje to okolice Galerii Silesia, przystanki przy ul. Francuskiej, rondo im. gen. Ziętka oraz okolice Spodka i NOSPR. Format szczególnie skuteczny podczas eventów masowych organizowanych w Spodku i MCK, przyciągających publiczność z całej aglomeracji.',
    benefits: [
      'Zasięg na pasażerów KZK GOP — jeden z największych systemów komunikacji w Polsce',
      'Kluczowe lokalizacje przy Galerii Silesia i centrum kulturalnym (Spodek, NOSPR)',
      'Wysoka efektywność podczas eventów w Spodku i Międzynarodowym Centrum Kongresowym',
      'Dostęp do pracowników biurowych i kadry zarządzającej w centrum Katowic',
      'Możliwość kampanii obejmujących całą aglomerację śląską'
    ]
  },

  // ── EKRANY LED — Łódź i Katowice ─────────────────────────────────────────

  'ekrany-led-lodz': {
    title: 'Ekrany LED w Łodzi – Cyfrowa Reklama DOOH w Centrum Polski',
    description: 'Ekrany LED w Łodzi to wciąż rozwijający się, a przez to bardziej przystępny cenowo segment reklamy cyfrowej w mieście o strategicznym położeniu komunikacyjnym. Najaktywniejsze lokalizacje koncentrują się przy Manufakturze, al. Piłsudskiego i głównych skrzyżowaniach centrum. Łódź jako centrum logistyczne i e-commerce przyciąga coraz więcej inwestorów, co przekłada się na rosnące zapotrzebowanie na kampanie DOOH wśród lokalnych i ogólnopolskich marek.',
    benefits: [
      'Korzystniejsze stawki niż w Warszawie przy rosnącym wolumenie ekspozycji',
      'Strategiczne położenie dla kampanii centralnej Polski',
      'Rosnąca liczba lokalizacji przy Manufakturze i centrum handlowym',
      'Idealne dla e-commerce i firm logistycznych z centrum dystrybucji w Łodzi',
      'Dynamiczny format zwiększający zauważalność kampanii w centrum miasta'
    ]
  },

  'ekrany-led-katowice': {
    title: 'Ekrany LED w Katowicach – Cyfrowa Reklama Outdoor w Metropolii Śląskiej',
    description: 'Ekrany LED w Katowicach i całej Metropolii GZM oferują ekspozycję na jednym z największych skupisk miejskich w Polsce. Kluczowe lokalizacje to okolice Spodka, Galerii Silesia i głównych skrzyżowań DTŚ. Format DOOH w Katowicach zyskuje na wartości podczas licznych eventów masowych — Tauron Nowa Muzyka, Festiwal Siesta, Śląski Festiwal Nauki — przyciągających dziesiątki tysięcy uczestników z całego regionu.',
    benefits: [
      'Ekspozycja na 2 mln mieszkańców Metropolii GZM dzięki lokalizacjom przy DTŚ',
      'Wzmożone zasięgi podczas eventów w Spodku i MCK Katowice',
      'Dynamiczny przekaz wideo wyróżniający się na tle tradycyjnych nośników OOH',
      'Idealne dla branży energetycznej, przemysłowej i usług B2B',
      'Rosnąca sieć ekranów przy centrach handlowych i węzłach komunikacyjnych'
    ]
  },

  // ── BANERY — top 5 miast ──────────────────────────────────────────────────

  'banery-warszawa': {
    title: 'Banery reklamowe w Warszawie – Wynajem Powierzchni na Budynkach i Płotach',
    description: 'Banery reklamowe w Warszawie to elastyczny i kosztowo efektywny format outdoor, montowany na rusztowaniach budowlanych, ogrodzeniach placów budowy i elewacjach budynków. Stolica z intensywną zabudową i licznymi inwestycjami budowlanymi oferuje wiele atrakcyjnych lokalizacji baner w centrum i na Pradze. Format sprawdza się szczególnie przy długoterminowych kampaniach wizerunkowych i promocjach lokalnych.',
    benefits: [
      'Duże powierzchnie ekspozycji w najbardziej ruchliwych miejscach stolicy',
      'Niższy koszt produkcji i montażu niż nośniki wielkoformatowe',
      'Idealne na ogrodzeniach placów budowy w centrum (długi czas ekspozycji)',
      'Możliwość zamówienia niestandardowych wymiarów dopasowanych do lokalizacji',
      'Skuteczne przy kampaniach deweloperskich, handlowych i wizerunkowych'
    ]
  },

  'banery-krakow': {
    title: 'Banery reklamowe w Krakowie – Elastyczna Reklama Zewnętrzna',
    description: 'Banery reklamowe w Krakowie pozwalają na ekspozycję w miejscach niedostępnych dla typowych nośników billboardowych — na płotach budowlanych przy inwestycjach w centrum, na elewacjach kamienic i przy obiektach sportowych. W mieście z rygorystyczną polityką estetyczną centrum historycznego, baner jest często jedyną możliwą formą wielkoformatowej reklamy przy starówce.',
    benefits: [
      'Jedyny wielkoformatowy format dopuszczalny w okolicach krakowskiej starówki',
      'Możliwość ekspozycji przy licznych inwestycjach budowlanych w centrum',
      'Atrakcyjne lokalizacje przy obiektach sportowych (Wisła, Cracovia)',
      'Elastyczne wymiary i możliwość szybkiej zmiany kreacji',
      'Niższy koszt przy porównywalnym zasięgu do innych formatów OOH'
    ]
  },

  'banery-wroclaw': {
    title: 'Banery reklamowe we Wrocławiu – Wynajem Powierzchni Banerowych',
    description: 'Banery reklamowe we Wrocławiu to popularny format wśród deweloperów, galerii handlowych i organizatorów eventów. Intensywna przebudowa centrum, nowe inwestycje przy ul. Świdnickiej i Grabiszyńskiej oraz obiekty sportowe (Tarczyński Arena) tworzą liczne możliwości ekspozycji wielkoformatowych banerów. Format szczególnie efektywny przy kampaniach lokalnych, gdzie liczy się zasięg w konkretnej dzielnicy.',
    benefits: [
      'Liczne lokalizacje przy przebudowach i nowych inwestycjach w centrum',
      'Ekspozycja przy Tarczyński Arena podczas meczów Śląska Wrocław',
      'Elastyczny format dopasowany do dostępnej powierzchni budynku lub płotu',
      'Skuteczny przy kampaniach dzielnicowych i targetowanych lokalnie',
      'Dostępny w krótszych terminach niż klasyczne nośniki billboardowe'
    ]
  },

  'banery-poznan': {
    title: 'Banery reklamowe w Poznaniu – Wynajem Powierzchni Banerowych',
    description: 'Banery reklamowe w Poznaniu sprawdzają się wszędzie tam, gdzie nie ma miejsca na billboard — na ogrodzeniach Międzynarodowych Targów Poznańskich, przy obiektach sportowych (stadion Lech Poznań) i na elewacjach remontowanych budynków. W trakcie targów MTP ogrodzenia terenów wystawienniczych to wyjątkowo cenne lokalizacje banerowe, eksponowane na setki tysięcy gości biznesowych rocznie.',
    benefits: [
      'Unikalne lokalizacje przy ogrodzeniach MTP z ekspozycją na gości targowych',
      'Ekspozycja przy stadionie Lech Poznań podczas meczy i eventów',
      'Możliwość kampanii krótkoterminowych przy konkretnych wydarzeniach',
      'Elastyczny format dostępny szybciej niż tradycyjne nośniki',
      'Konkurencyjny koszt przy wysokiej widoczności w centrum miasta'
    ]
  },

  'banery-gdansk': {
    title: 'Banery reklamowe w Gdańsku – Wynajem Powierzchni Banerowych w Trójmieście',
    description: 'Banery reklamowe w Gdańsku to popularna forma ekspozycji przy nabrzeżach, obiektach portowych i licznych inwestycjach budowlanych rewitalizowanego Śródmieścia i Wyspy Spichrzów. W sezonie letnim banery przy plażach i przy Drodze Czerwonej osiągają jedne z najwyższych zasięgów wśród wszystkich formatów outdoor w Trójmieście. Format szczególnie polecany dla branży deweloperskiej i turystycznej.',
    benefits: [
      'Unikalne lokalizacje przy nabrzeżach i obiektach portowych',
      'Wysoki zasięg przy plażach i trasach turystycznych w sezonie letnim',
      'Ekspozycja przy licznych inwestycjach rewitalizowanego centrum Gdańska',
      'Idealne dla deweloperów, hoteli i firm z branży turystycznej',
      'Możliwość ekspozycji w miejscach niedostępnych dla tradycyjnych nośników'
    ]
  }
}
