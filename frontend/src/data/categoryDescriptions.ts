export interface CategoryDescription {
  title: string
  description: string
  benefits: string[]
}

export const categoryDescriptions: Record<string, CategoryDescription> = {
  // Wszystkie powierzchnie
  '': {
    title: 'Powierzchnie reklamowe w całej Polsce – Wynajem i Sprzedaż',
    description: 'Witamy na ReklaMap – największej platformie agregującej powierzchnie reklamowe w Polsce. Nasz serwis to kompleksowe narzędzie dla firm i osób prywatnych, które chcą skutecznie promować swój biznes w przestrzeni publicznej. Oferujemy dostęp do tysięcy nośników: od tradycyjnych billboardów przy autostradach, przez eleganckie citylighty w centrach handlowych, po nowoczesne, cyfrowe ekrany LED (DOOH). Dzięki nam znajdziesz idealną lokalizację dla swojej kampanii, porównasz ceny i skontaktujesz się bezpośrednio z właścicielem nośnika, pomijając zbędnych pośredników. Nasza baza obejmuje zarówno duże aglomeracje, jak i mniejsze miejscowości, zapewniając pełne pokrycie ogólnopolskie.',
    benefits: [
      'Największa i najbardziej aktualna baza powierzchni reklamowych w Polsce',
      'Bezpośredni kontakt z właścicielami – brak ukrytych prowizji',
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
      'Preстиżowy wygląd dzięki przeszklonym obudowom i podświetleniu',
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
