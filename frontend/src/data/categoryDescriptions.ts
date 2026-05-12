export interface CategoryDescription {
  title: string
  description: string
  benefits: string[]
}

export const categoryDescriptions: Record<string, CategoryDescription> = {
  // Wszystkie powierzchnie
  '': {
    title: 'Powierzchnie reklamowe w Polsce – wynajem nośników OOH',
    description: 'Powierzchnie reklamowe w całej Polsce zebrane w jednym serwisie — od billboardów przy trasach po ekrany LED w centrach miast. ReklaMap agreguje ogłoszenia właścicieli nośników: billboardów, citylightów, ekranów LED, banerów, ścian reklamowych, totemów oraz reklamy w transporcie i mobilnej. Wyszukasz nośnik po lokalizacji, typie i budżecie, porównasz do pięciu ofert obok siebie według parametrów technicznych i skontaktujesz się bezpośrednio z wystawcą — bez prowizji i pośredników. Interaktywna mapa pokazuje, gdzie dokładnie stoi dany nośnik, a filtry zawężają wyniki po natężeniu ruchu, oświetleniu czy wymiarach. Baza obejmuje zarówno duże aglomeracje, jak i mniejsze miejscowości.',
    benefits: [
      'Wszystkie typy nośników OOH w jednym miejscu — bez obdzwaniania kilku operatorów',
      'Bezpośredni kontakt z właścicielem nośnika, bez prowizji i pośredników',
      'Interaktywna mapa pokazuje dokładną lokalizację każdego ogłoszenia',
      'Porównywarka zestawia do pięciu nośników obok siebie według parametrów technicznych',
      'Filtry zawężają wyniki po natężeniu ruchu, oświetleniu, wymiarach i typie powierzchni'
    ]
  },

  // Billboardy
  'billboardy': {
    title: 'Wynajem billboardów – reklama wielkoformatowa OOH',
    description: 'Wynajem billboardów to prosty sposób na duży zasięg w terenie — tablice 12 m² i 18 m² przy głównych arteriach, drogach krajowych i autostradach. Billboard reklamowy pracuje przez całą dobę i buduje rozpoznawalność wśród kierowców oraz pasażerów, którzy mijają go regularnie tą samą trasą. W ogłoszeniach znajdziesz tablice tradycyjne, podświetlane oraz prismatrony zmieniające trzy plansze. To nośnik pod kampanie wizerunkowe, wprowadzenia nowych produktów i komunikaty kierujące do punktów sprzedaży — sprawdza się też w pakietach kilku lokalizacji na kampanię o szerszym, regionalnym zasięgu. Stawka i dostępność zależą od lokalizacji, formatu i okresu wynajmu.',
    benefits: [
      'Duży zasięg wśród kierowców i pasażerów dzięki lokalizacjom przy ruchliwych trasach',
      'Podświetlane tablice pozostają czytelne po zmroku',
      'Sprawdza się w kampaniach wizerunkowych od kilku tygodni do kilkunastu miesięcy',
      'Pakiety kilku nośników pozwalają zbudować kampanię o szerszym, regionalnym zasięgu',
      'Wysoki wskaźnik OTS — ten sam odbiorca widzi reklamę wielokrotnie na codziennej trasie'
    ]
  },

  // Citylighty
  'citylighty': {
    title: 'Citylight reklamowy – reklama w centrum miasta',
    description: 'Citylight reklamowy to podświetlana gablota 120×180 cm na przystankach, deptakach i przy galeriach — na wysokości wzroku przechodnia. Pieszy albo pasażer czekający na autobus ma czas, żeby przeczytać więcej niż jedno hasło, więc citylight (city light) dobrze niesie oferty z konkretem: ceną, terminem, adresem punktu. Wewnętrzne podświetlenie sprawia, że plansza wygląda równie dobrze po zmroku. To nośnik dla branży fashion, beauty i retail, dla gastronomii oraz instytucji kultury promujących wydarzenia w danym mieście. Gęsta siatka lokalizacji w śródmieściu pozwala otoczyć reklamą wybraną dzielnicę albo ciąg pieszy.',
    benefits: [
      'Reklama na wysokości wzroku pieszego, w zasięgu ręki odbiorcy',
      'Długi czas kontaktu — pasażerowie i przechodnie mają chwilę, by przeczytać szczegóły',
      'Wewnętrzne podświetlenie utrzymuje czytelność po zmroku i w pochmurne dni',
      'Gęsta siatka lokalizacji pozwala otoczyć reklamą konkretną dzielnicę lub ciąg pieszy',
      'Sprawdza się przy ofertach lokalnych — promocji sklepu, salonu czy lokalu gastronomicznego'
    ]
  },

  // Ekrany LED
  'ekrany-led': {
    title: 'Ekran LED reklamowy – telebim i reklama cyfrowa DOOH',
    description: 'Ekran LED reklamowy to telebim emitujący ruchomy obraz — spoty wideo, animacje i plansze zmieniane w ciągu dnia bez kosztów druku. Treść można podmieniać zdalnie i dopasować ją do pory dnia, dnia tygodnia czy pogody, a wysoka jasność diod utrzymuje czytelność w pełnym słońcu i po zmroku. Billboard LED dzieli czas emisji między kilku reklamodawców, więc wejście jest tańsze niż wykup całego nośnika na miesiąc. To format dla marek technologicznych, motoryzacyjnych, sieci handlowych i premier kinowych — wszędzie tam, gdzie liczy się dynamiczny, nowoczesny przekaz. Sprawdza się też w krótkich kampaniach: tylko w weekendy albo w godzinach szczytu.',
    benefits: [
      'Ruchomy obraz przyciąga uwagę mocniej niż statyczna plansza',
      'Brak kosztów druku i montażu — kreację wgrywa się zdalnie',
      'Treść da się podmienić w trakcie kampanii i dopasować do pory dnia czy pogody',
      'Krótkie emisje są możliwe — np. tylko weekendy albo godziny szczytu',
      'Wysoka jasność diod utrzymuje czytelność w pełnym słońcu i po zmroku'
    ]
  },

  // Banery
  'banery': {
    title: 'Baner reklamowy i siatka mesh – tania reklama OOH',
    description: 'Baner reklamowy to najtańsze wejście w reklamę zewnętrzną — wydruk na winylu lub siatce mesh, montowany na ogrodzeniu, elewacji czy rusztowaniu. Produkcja jest szybka, a format dowolny, więc baner dopasujesz do nietypowej powierzchni, której nie obsłuży standardowa tablica. Siatka mesh przepuszcza wiatr, dzięki czemu sprawdza się na dużych elewacjach i konstrukcjach tymczasowych. To nośnik pod promocje lokalne, otwarcia, wyprzedaże, informację o inwestycjach deweloperskich oraz oznakowanie kierunkowe prowadzące do lokalu. Materiał znosi deszcz i promieniowanie UV, a w razie potrzeby przeniesiesz go w inne miejsce w trakcie kampanii.',
    benefits: [
      'Najniższy koszt wejścia spośród nośników reklamy zewnętrznej',
      'Dowolny format — wydruk powstaje na wymiar konkretnej powierzchni',
      'Siatka mesh przepuszcza wiatr i nadaje się na duże elewacje oraz konstrukcje tymczasowe',
      'Znosi deszcz, wiatr i promieniowanie UV bez szybkiej utraty kolorów',
      'Łatwy do przeniesienia w inne miejsce w trakcie trwającej kampanii'
    ]
  },

  // Ściany reklamowe
  'sciany-reklamowe': {
    title: 'Ściana reklamowa i mural – reklama na elewacji budynku',
    description: 'Ściana reklamowa to najbardziej okazały format OOH — mural albo wielkoformatowa siatka mesh na elewacji budynku, której trudno nie zauważyć. Powierzchnia liczona w setkach metrów kwadratowych daje efekt, jakiego nie osiągnie żaden standardowy nośnik, i mocno buduje prestiż marki w danej okolicy. Dobrze zaprojektowany mural reklamowy bywa fotografowany i sam wraca w social mediach, dorzucając zasięg poza samą lokalizacją. To format premium dla domów mody, branży gamingowej i technologicznej oraz dużych kampanii wizerunkowych, które chcą zdominować przestrzeń miasta. Reklama na elewacji zostaje w jednym punkcie na wiele tygodni lub miesięcy, więc pracuje długo.',
    benefits: [
      'Największa powierzchnia ekspozycji dostępna w reklamie zewnętrznej',
      'Efekt skali, którego nie osiągnie standardowy nośnik — silny sygnał prestiżu marki',
      'Dobry mural bywa fotografowany i wraca w social mediach, dokładając darmowy zasięg',
      'Reklama zostaje w jednym punkcie na wiele tygodni lub miesięcy',
      'Format premium dopasowany do dużych kampanii wizerunkowych w centrach miast'
    ]
  },

  // Totemy reklamowe
  'totemy-reklamowe': {
    title: 'Totem reklamowy – pylon przy galerii i stacji paliw',
    description: 'Totem reklamowy to wolnostojący pylon przy wjazdach do galerii, stacji paliw, biurowców i parkingów — widoczny z kilkudziesięciu metrów. Konstrukcja ma zwykle od trzech do około dziesięciu metrów wysokości i mieści kilka kaset, więc na jednym pylonie pokazuje się naraz kilku najemców lub produktów. Podświetlenie LED utrzymuje widoczność po zmroku i w gorszą pogodę. To nośnik dla sieci handlowych, gastronomii, aptek i salonów samochodowych — pracuje dokładnie tam, gdzie klient podejmuje decyzję, czyli przy samym punkcie sprzedaży. Pełni też funkcję nawigacyjną: pomaga kierowcom trafić do obiektu, a przy okazji utrwala markę w jego otoczeniu.',
    benefits: [
      'Widoczny z kilkudziesięciu metrów — dla kierowców i pieszych jednocześnie',
      'Jedna konstrukcja mieści kilka kaset — równoległa ekspozycja kilku marek lub produktów',
      'Podświetlenie LED utrzymuje widoczność po zmroku i w gorszych warunkach',
      'Pracuje przy samym punkcie sprzedaży, gdzie klient podejmuje decyzję zakupową',
      'Pełni funkcję nawigacyjną — kieruje do obiektu i utrwala markę w jego otoczeniu'
    ]
  },

  // Reklama w transporcie
  'reklama-w-transporcie': {
    title: 'Reklama w transporcie – autobusy i tramwaje',
    description: 'Reklama w transporcie jeździ po mieście razem z pojazdem — oklejone autobusy i tramwaje docierają tam, gdzie stoi mało stałych nośników. W ogłoszeniach znajdziesz pełne i częściowe oklejenia (full i half wrap), tablice wewnątrz pojazdów oraz ekrany w systemach informacji pasażerskiej. Ten sam pojazd kursuje stałą trasą wiele razy dziennie, więc reklama buduje wysoką częstotliwość kontaktu przy niskim koszcie dotarcia. Pasażer w drodze i tak szuka, na czym zawiesić wzrok — stąd dobra zauważalność reklamy wewnątrz. To nośnik pod kampanie rekrutacyjne, lokalne promocje i komunikaty do szerokiego, miejskiego odbiorcy; bywają dostępne konkretne linie obsługujące wybrane dzielnice.',
    benefits: [
      'Nośnik przemieszcza się po mieście i pokazuje reklamę w wielu punktach jednego dnia',
      'Wysoka częstotliwość kontaktu — pojazd kursuje stałą trasą wielokrotnie w ciągu dnia',
      'Niski koszt dotarcia (CPT) na tle innych mediów OOH',
      'Reklama wewnątrz pojazdu pracuje na uwadze pasażera przez cały przejazd',
      'Pasuje do kampanii rekrutacyjnych i lokalnych promocji adresowanych do mieszkańców'
    ]
  },

  // Reklama mobilna
  'reklama-mobilna': {
    title: 'Reklama mobilna – przyczepy i pojazdy reklamowe w ruchu',
    description: 'Reklama mobilna to przyczepa albo oklejony pojazd, który dowozisz dokładnie tam, gdzie chcesz — pod galerię, na event, w okolice konkurencji. Mobile billboard wjedzie w miejsca, których nie obsłużą stałe nośniki, a trasę i godziny przejazdu ustawiasz pod szczyty komunikacyjne albo pod konkretne wydarzenie. Sprawdza się przy otwarciach lokali, akcjach last-minute i kampaniach sezonowych, gdzie liczy się obecność tu i teraz. Nietypowy, ruchomy format sam zwraca uwagę, a część przyczep ma nagłośnienie wzmacniające przekaz. Dobrze działa też jako uzupełnienie kampanii billboardowej — domyka zasięg w miejscach, których plan stałych nośników nie objął.',
    benefits: [
      'Sam wybierasz trasę i godziny przejazdu — pod szczyty komunikacyjne lub konkretny event',
      'Wjeżdża w miejsca niedostępne dla stałych konstrukcji — parkingi, tereny eventowe, ścisłe centrum',
      'Nietypowy, ruchomy format zwraca uwagę przechodniów i kierowców',
      'Część konstrukcji pozwala dołożyć przekaz dźwiękowy obok wizualnego',
      'Dobre uzupełnienie kampanii outdoorowej — działa tam, gdzie plan mediów ma luki'
    ]
  },

  // Inne
  'inne': {
    title: 'Nietypowe powierzchnie reklamowe i ambient OOH',
    description: 'Nietypowe powierzchnie reklamowe to wszystko, co nie mieści się w pozostałych kategoriach — ambient, neony, instalacje i digital signage. Trafiają tu nośniki niestandardowe, które łatwiej opisać przez to, czym są w danym ogłoszeniu, niż z góry skatalogować. Takie formaty wybiera się wtedy, gdy kampania ma się wyróżnić czymś nieoczywistym i zostać zapamiętana. Zanim zaplanujesz na nich budżet, sprawdź konkretne ogłoszenia — dostępność bywa nieregularna, bo to z definicji rzeczy spoza standardu. Jeśli szukasz typowego nośnika, zacznij od kategorii billboardów, citylightów albo ekranów LED.',
    benefits: [
      'Formaty spoza standardu — pomagają wyróżnić kampanię czymś nieoczywistym',
      'Często mają potencjał na zauważenie i udostępnienie w social mediach',
      'Sprawdzają się w kampaniach nastawionych na zapamiętanie marki, nie tylko zasięg',
      'Wycena i dobór zależą od pomysłu na kreację — każde ogłoszenie ocenia się osobno',
      'Jeśli szukasz typowego nośnika, zacznij od billboardów, citylightów lub ekranów LED'
    ]
  }
}

export const cityDescriptions: Record<string, CategoryDescription> = {
  'warszawa': {
    title: 'Powierzchnie reklamowe w Warszawie – billboardy i OOH',
    description: 'Powierzchnie reklamowe w Warszawie to największy wybór nośników w kraju — od wielkoformatowych siatek w centrum po ekrany LED przy głównych rondach. Reklama w stolicy dociera jednocześnie do mieszkańców, dojeżdżających z aglomeracji i odwiedzających centra biznesowe na Woli, Mokotowie czy Służewcu. W bazie znajdziesz billboardy przy trasach wylotowych (S8, S2, A2), nośniki cyfrowe w okolicach ronda Dmowskiego oraz reklamę w komunikacji miejskiej, w tym w metrze. To rynek pod kampanie ogólnopolskie, premierowe i wizerunkowe, a także pod precyzyjne dotarcie do konkretnej dzielnicy. Gęstość nośników pozwala zbudować zarówno szeroki zasięg, jak i lokalną kampanię punktową.',
    benefits: [
      'Największy wybór nośników i najwyższa częstotliwość kontaktu spośród polskich miast',
      'Dotarcie do centrów biznesowych — Wola, Mokotów, Służewiec (tzw. Mordor)',
      'Billboardy przy trasach wylotowych S8, S2 i A2 — duży ruch tranzytowy i lokalny',
      'Reklama w metrze i komunikacji miejskiej pod kampanie kierowane do mieszkańców',
      'Gęsta siatka nośników pozwala zawęzić kampanię do jednej dzielnicy'
    ]
  },

  'krakow': {
    title: 'Powierzchnie reklamowe w Krakowie – billboardy i citylighty',
    description: 'Powierzchnie reklamowe w Krakowie docierają nie tylko do mieszkańców i studentów, ale też do tłumów turystów odwiedzających Stare Miasto i Kazimierz. To drugie co do wielkości miasto w kraju, z silnym sektorem usług i IT skupionym wokół takich miejsc jak Kraków Technology Park. W bazie znajdziesz billboardy przy obwodnicy A4 i trasach wlotowych od strony Katowic i Warszawy, citylighty w okolicach galerii handlowych oraz nośniki przy węzłach takich jak Rondo Mogilskie czy Matecznego. Kraków sprawdza się w kampaniach do młodego odbiorcy, w promocji usług oraz w działaniach łączących zasięg turystyczny z lokalnym. Bliskość lotniska Balice dokłada ekspozycję wśród podróżnych.',
    benefits: [
      'Dotarcie do turystów krajowych i zagranicznych obok stałych mieszkańców',
      'Duży ośrodek akademicki — kampanie do studentów i młodych dorosłych',
      'Billboardy przy obwodnicy A4 i trasach wlotowych od Katowic i Warszawy',
      'Citylighty w okolicach galerii handlowych pod kampanie prosprzedażowe',
      'Bliskość lotniska Balice — dodatkowy kontakt z podróżnymi'
    ]
  },

  'wroclaw': {
    title: 'Powierzchnie reklamowe we Wrocławiu – nośniki OOH',
    description: 'Powierzchnie reklamowe we Wrocławiu trafiają do młodej, wykształconej populacji jednego z najszybciej rosnących ośrodków gospodarczych regionu. Stolica Dolnego Śląska to setki biurowców i obecność firm międzynarodowych, więc miasto dobrze nadaje się do kampanii B2B i promocji usług premium. W bazie znajdziesz nośniki wielkoformatowe przy autostradzie A4, drodze S5 i Autostradowej Obwodnicy Wrocławia, ekrany LED w okolicach Sky Tower i Rynku oraz reklamę przy Dworcu Głównym i lotnisku. Wrocław leży na szlaku z zachodniej Europy, co dokłada ruch tranzytowy do lokalnego. To rynek dla marek technologicznych, finansowych i konsumenckich celujących w mieszkańców o rosnącej sile nabywczej.',
    benefits: [
      'Młoda, wykształcona grupa odbiorców i rosnąca siła nabywcza',
      'Setki biurowców i firm międzynarodowych — dobre tło dla kampanii B2B',
      'Duży ruch na Autostradowej Obwodnicy Wrocławia oraz A4 i S5',
      'Ekrany LED w okolicach Sky Tower i Rynku — prestiżowe lokalizacje śródmiejskie',
      'Ekspozycja przy Dworcu Głównym i lotnisku — kontakt z podróżnymi'
    ]
  },

  'poznan': {
    title: 'Powierzchnie reklamowe w Poznaniu – billboardy i OOH',
    description: 'Powierzchnie reklamowe w Poznaniu docierają do mieszkańców o wysokiej sile nabywczej oraz uczestników wydarzeń na Międzynarodowych Targach Poznańskich. Miasto przyciąga klienta biznesowego z całej Polski i z zagranicy, zwłaszcza w sezonie targowym. W bazie znajdziesz billboardy przy autostradzie A2 na szlaku Berlin–Warszawa, nośniki wzdłuż ulicy Głogowskiej oraz formaty OOH w okolicach Starego Browaru i dworca Poznań Główny. To dobra lokalizacja pod kampanie targowe, przemysłowe i motoryzacyjne, a także pod stałą obecność wśród lojalnego, lokalnego odbiorcy. Położenie na głównym szlaku wschód–zachód zwiększa udział ruchu tranzytowego.',
    benefits: [
      'Bezpośrednie dotarcie do uczestników targów MTP, zwłaszcza w sezonie targowym',
      'Mieszkańcy o wysokiej sile nabywczej i lojalności wobec lokalnych marek',
      'Billboardy przy A2 na szlaku Berlin–Warszawa — duży ruch tranzytowy',
      'Nośniki wzdłuż ulicy Głogowskiej i przy Starym Browarze — ekspozycja w centrum',
      'Dobre tło dla kampanii targowych, przemysłowych i motoryzacyjnych'
    ]
  },

  'gdansk': {
    title: 'Powierzchnie reklamowe w Gdańsku – reklama w Trójmieście',
    description: 'Powierzchnie reklamowe w Gdańsku docierają do mieszkańców Trójmiasta, kadry biurowej z Oliwy i — w sezonie — turystów nad Bałtykiem. Gdańsk to ważny port i jeden z mocniejszych ośrodków IT na północy kraju, a rynek ma tu wyraźną sezonowość — latem zasięg nośników rośnie wraz z napływem odwiedzających. Warto go rozpatrywać razem z Gdynią i Sopotem — kampania w aglomeracji łatwo obejmuje wszystkie trzy miasta. Sprawdza się tu zarówno reklama wizerunkowa i B2B, jak i promocje sezonowe oraz turystyczne. Konkretne formaty i lokalizacje znajdziesz w ofertach billboardów, citylightów, ekranów LED i banerów w Gdańsku.',
    benefits: [
      'Dotarcie do mieszkańców Trójmiasta, kadry biurowej i — w sezonie — turystów nad Bałtykiem',
      'Wyraźna sezonowość rynku — latem zasięg nośników rośnie wraz z napływem odwiedzających',
      'Ważny port i jeden z mocniejszych ośrodków IT na północy kraju',
      'Łatwe połączenie kampanii z Gdynią i Sopotem w obrębie jednej aglomeracji',
      'Dobrze działa zarówno w kampaniach wizerunkowych i B2B, jak i sezonowych oraz turystycznych'
    ]
  },

  'lodz': {
    title: 'Powierzchnie reklamowe w Łodzi – billboardy i murale',
    description: 'Powierzchnie reklamowe w Łodzi to między innymi murale reklamowe — wielkoformatowe malowidła na ścianach kamienic, które bywają miejskimi atrakcjami. Miasto przechodzi szeroką rewitalizację i umacnia się jako hub logistyczny w centrum siatki autostrad. Oferta obejmuje billboardy przy ulicy Piotrkowskiej, nośniki w okolicach Manufaktury oraz ekspozycję wzdłuż autostrady A1 i drogi ekspresowej S14. Łódź daje dobry stosunek kosztu dotarcia do zasięgu, więc sprawdza się w kampaniach świadomościowych z ograniczonym budżetem. Zrewitalizowane przestrzenie miejskie podnoszą uwagę odbiorcy, a ściany kamienic to materiał na kreacje, które same wracają w social mediach.',
    benefits: [
      'Murale reklamowe na elewacjach kamienic — format, który bywa miejską atrakcją',
      'Położenie w centrum siatki autostrad — billboardy przy A1 i S14',
      'Dobry stosunek kosztu dotarcia do zasięgu — sprzyja kampaniom z mniejszym budżetem',
      'Nośniki przy ulicy Piotrkowskiej i Manufakturze — ekspozycja w sercu miasta',
      'Zrewitalizowane przestrzenie miejskie podnoszą zauważalność reklamy'
    ]
  },

  'katowice': {
    title: 'Powierzchnie reklamowe w Katowicach i GZM',
    description: 'Powierzchnie reklamowe w Katowicach to wejście w całą Górnośląsko-Zagłębiowską Metropolię — kilkanaście miast połączonych w jeden organizm. Reklama tutaj obejmuje gęsto zaludniony obszar spięty Drogową Trasą Średnicową, z dużym ruchem lokalnym i tranzytowym. W bazie są billboardy przy autostradzie A4, ekrany LED w okolicach Spodka i Międzynarodowego Centrum Kongresowego oraz wielkoformatowe siatki mesh w centrum Katowic, a także nośniki w sąsiednich Chorzowie czy Sosnowcu. To rynek pod kampanie o szerokim zasięgu regionalnym, dotarcie do klienta biznesowego ze stref ekonomicznych i obecność przy dużych skupiskach handlu. Bliskość granicy czeskiej i słowackiej dokłada potencjał transgraniczny.',
    benefits: [
      'Jedna kampania pokrywa kilkanaście miast Górnośląsko-Zagłębiowskiej Metropolii',
      'Ciągła ekspozycja przy Drogowej Trasie Średnicowej — kluczowej arterii regionu',
      'Billboardy przy autostradzie A4 oraz nośniki w Chorzowie i Sosnowcu',
      'Ekrany LED przy Spodku i Międzynarodowym Centrum Kongresowym',
      'Bliskość granicy czeskiej i słowackiej — potencjał kampanii transgranicznych'
    ]
  },

  'szczecin': {
    title: 'Powierzchnie reklamowe w Szczecinie – nośniki OOH',
    description: 'Powierzchnie reklamowe w Szczecinie docierają do mieszkańców Pomorza Zachodniego oraz osób korzystających z bliskości granicy z Niemcami i portu. To stolica regionu o specyficznym położeniu — tuż przy granicy, z silnym sektorem morskim i logistycznym. W bazie znajdziesz billboardy przy trasach wjazdowych S3 i S6, nośniki w centrum przy Placu Rodła oraz powierzchnie przy centrach handlowych takich jak Galaxy czy Kaskada. Rewitalizacja Wałów Chrobrego i nabrzeży dokłada atrakcyjne lokalizacje pod kampanie sezonowe i turystyczne. Szczecin dobrze sprawdza się w kampaniach firm transportowych, logistycznych i handlowych celujących także w rynek niemiecki.',
    benefits: [
      'Bliskość granicy z Niemcami — kampanie celujące również w rynek niemiecki',
      'Dotarcie do pracowników sektora morskiego i logistycznego',
      'Billboardy przy trasach wjazdowych S3 i S6 — ruch tranzytowy i lokalny',
      'Nośniki przy Placu Rodła i centrach handlowych Galaxy oraz Kaskada',
      'Zrewitalizowane nabrzeża dokładają lokalizacje pod kampanie sezonowe'
    ]
  },

  'bydgoszcz': {
    title: 'Powierzchnie reklamowe w Bydgoszczy – billboardy i OOH',
    description: 'Powierzchnie reklamowe w Bydgoszczy docierają do mieszkańców i dojeżdżających w jednym z większych ośrodków przemysłowo-handlowych północnej Polski. Miasto jest ważnym węzłem komunikacyjnym, z dobrą widocznością przy drogach krajowych DK10 i DK25 oraz w punktach centrum, jak okolice Ronda Jagiellonów. W bazie znajdziesz billboardy przy trasach wlotowych, nośniki przy centrach handlowych — Focus Mall czy Zielone Arkady — oraz powierzchnie w strefach biznesu i na osiedlach mieszkaniowych. Obecność parków przemysłowych i technologicznych sprawia, że Bydgoszcz dobrze nadaje się pod kampanie rekrutacyjne i promocję usług dla biznesu. Stawki najmu bywają przy tym niższe niż w największych aglomeracjach przy porównywalnym zasięgu lokalnym.',
    benefits: [
      'Ważny węzeł komunikacyjny — stały dopływ odbiorców z regionu',
      'Widoczność przy drogach krajowych DK10 i DK25 oraz Rondzie Jagiellonów',
      'Nośniki przy centrach handlowych Focus Mall i Zielone Arkady',
      'Parki przemysłowe i technologiczne — dobre tło dla kampanii rekrutacyjnych',
      'Stawki najmu zwykle niższe niż w największych aglomeracjach przy podobnym zasięgu lokalnym'
    ]
  },

  'lublin': {
    title: 'Powierzchnie reklamowe w Lublinie – billboardy i OOH',
    description: 'Powierzchnie reklamowe w Lublinie trafiają do dużej grupy studentów i młodych dorosłych oraz do biznesu zorientowanego na rynki wschodnie. To największe miasto wschodniej Polski, prężny ośrodek akademicki i administracyjny. W bazie znajdziesz billboardy przy obwodnicy w ciągu dróg S12 i S17, nośniki wielkoformatowe przy Alei Spółdzielczości Pracy oraz powierzchnie w ścisłym centrum. Lublin leży na trasie w stronę przejść granicznych, więc do ruchu lokalnego dochodzi tranzyt. To dobra lokalizacja pod kampanie edukacyjne, retail i logistyczne, z wysoką częstotliwością kontaktu w okolicach uczelni i akademików. Rozwijająca się infrastruktura biznesowa i handlowa stale powiększa dostępną bazę nośników.',
    benefits: [
      'Duży ośrodek akademicki — wysoka częstotliwość kontaktu przy uczelniach i akademikach',
      'Billboardy przy obwodnicy w ciągu dróg ekspresowych S12 i S17',
      'Nośniki przy Alei Spółdzielczości Pracy i w ścisłym centrum',
      'Położenie na trasie ku przejściom granicznym — ruch tranzytowy obok lokalnego',
      'Dobre tło dla kampanii edukacyjnych, handlowych i logistycznych'
    ]
  },

  'bialystok': {
    title: 'Powierzchnie reklamowe w Białymstoku – nośniki OOH',
    description: 'Powierzchnie reklamowe w Białymstoku docierają do mieszkańców regionu oraz podróżnych na trasie Via Baltica w stronę krajów bałtyckich. To główne centrum gospodarcze i administracyjne północno-wschodniej Polski. W ofertach trafisz na nośniki przy drogach S8 i DK19, billboardy w kluczowych punktach miasta — jak okolice Alei Jana Pawła II — oraz powierzchnie przy centrach handlowych regionu. Mieszkańcy są dość przywiązani do lokalnych marek, więc kampanie nastawione na lokalny handel i usługi mają tu mocną pozycję. Rozbudowa infrastruktury miejskiej stopniowo powiększa ofertę nośników OOH, w tym formatów cyfrowych.',
    benefits: [
      'Dotarcie do największej aglomeracji północno-wschodniej Polski',
      'Ruch tranzytowy trasą Via Baltica w stronę Litwy i dalej',
      'Billboardy przy S8 i DK19 oraz w okolicach Alei Jana Pawła II',
      'Mieszkańcy przywiązani do lokalnych marek — sprzyja kampaniom lokalnego handlu',
      'Rosnąca oferta nośników OOH, w tym formatów cyfrowych'
    ]
  },

  'gdynia': {
    title: 'Powierzchnie reklamowe w Gdyni – reklama w Trójmieście',
    description: 'Powierzchnie reklamowe w Gdyni trafiają do zamożnych mieszkańców, pracowników nowoczesnych biurowców i turystów odwiedzających Skwer Kościuszki. Gdynia to mniej zabytkowej turystyki niż Gdańsk, a więcej biznesu — silny sektor morski i technologiczny skupiony m.in. wokół Pomorskiego Parku Naukowo-Technologicznego i biurowców przy ulicy Łużyckiej, a do tego duże wydarzenia jak Open’er podnoszące zasięg ponad lokalny. Oferta to billboardy przy Estakadzie Kwiatkowskiego, nośniki wielkoformatowe wzdłuż Drogi Gdyńskiej oraz powierzchnie w prestiżowym Orłowie. Miasto pasuje markom premium, usługom finansowym i firmom z branży morskiej oraz nowych technologii — a w razie potrzeby kampanię łatwo rozszerzyć na resztę Trójmiasta.',
    benefits: [
      'Zamożni mieszkańcy i kadra biurowców — dobre tło dla marek premium i usług finansowych',
      'Silny sektor morski i technologiczny — m.in. Pomorski Park Naukowo-Technologiczny',
      'Billboardy przy Estakadzie Kwiatkowskiego i wzdłuż Drogi Gdyńskiej',
      'Profil bardziej biznesowy niż turystyczny — mniej zabytkowego ruchu niż w Gdańsku, więcej korporacji',
      'W trakcie dużych wydarzeń, jak Open’er, zasięg wykracza poza lokalny'
    ]
  },

  'olsztyn': {
    title: 'Powierzchnie reklamowe w Olsztynie – reklama na Warmii',
    description: 'Powierzchnie reklamowe w Olsztynie docierają do mieszkańców stolicy Warmii i Mazur, dużej społeczności studenckiej Uniwersytetu Warmińsko-Mazurskiego oraz turystów, dla których miasto jest bramą do Krainy Wielkich Jezior. Olsztyn ma jedyną w regionie sieć tramwajową, więc do ruchu samochodowego dochodzi gęsty potok pasażerów komunikacji miejskiej. W bazie znajdziesz billboardy przy trasach wlotowych S51 i S16, nośniki wielkoformatowe wzdłuż al. Sikorskiego i ul. Sybiraków, citylighty przy przystankach w centrum oraz powierzchnie w okolicach Galerii Warmińskiej, Aury i kampusu w Kortowie. Latem zasięg rośnie o ruch wakacyjny w stronę Mazur. To dobry rynek pod kampanie edukacyjne, turystyczne, retailowe i komunikaty lokalnych usług.',
    benefits: [
      'Duża społeczność studencka UWM — mocne tło dla kampanii edukacyjnych i rekrutacyjnych',
      'Jedyna w regionie sieć tramwajowa — dodatkowy, gęsty potok pasażerów komunikacji',
      'Billboardy przy trasach wlotowych S51 i S16 oraz wzdłuż al. Sikorskiego',
      'Brama do Krainy Wielkich Jezior — latem zasięg rośnie o ruch turystyczny',
      'Okolice Galerii Warmińskiej, Aury i kampusu w Kortowie jako naturalne punkty handlu i ruchu'
    ]
  }
}

export const typeCityDescriptions: Record<string, CategoryDescription> = {

  // ── BILLBOARDY ────────────────────────────────────────────────────────────

  'billboardy-warszawa': {
    title: 'Billboardy w Warszawie – reklama wielkoformatowa',
    description: 'Billboardy w Warszawie to skuteczny sposób dotarcia do mieszkańców stolicy i osób codziennie dojeżdżających z aglomeracji do pracy. Największy popyt dotyczy lokalizacji przy trasach wylotowych (S8, A2, S2), Obwodnicy Śródmiejskiej oraz kluczowych węzłach komunikacyjnych — Rondo ONZ, Rondo Wiatraczna i Rondo Daszyńskiego. Standardowy format 12 m² zapewnia ekspozycję całą dobę w miejscach o najwyższym natężeniu ruchu w Polsce.',
    benefits: [
      'Bardzo duże zasięgi — mieszkańcy stolicy plus dzienny ruch wahadłowy z aglomeracji',
      'Kluczowe lokalizacje przy A2, S8, S2 i Obwodnicy Śródmiejskiej',
      'Koncentracja kampanii wizerunkowych i produktowych dla rynku ogólnopolskiego',
      'Dostęp do prestiżowych lokalizacji przy centrum biznesowym (Wola, Mokotów)',
      'Możliwość budowania zasięgu na całej trasie przejazdu przez miasto'
    ]
  },

  'billboardy-krakow': {
    title: 'Billboardy w Krakowie – reklama wielkoformatowa',
    description: 'Billboardy w Krakowie docierają jednocześnie do mieszkańców, dużej społeczności studenckiej i milionów turystów odwiedzających miasto każdego roku. Kluczowe lokalizacje to autostrada A4 (wjazd od Katowic i Tarnowa), Trasa Łagiewnicka, rondo Mogilskie oraz drogi prowadzące do lotniska Balice i Zakopanego. Kraków to skuteczny, choć zarazem jeden z bardziej konkurencyjnych rynków billboardowych w Polsce.',
    benefits: [
      'Dotarcie do turystów z całego świata odwiedzających Stare Miasto i Wawel',
      'Lokalizacje przy A4 — szlak Katowice–Kraków–Tarnów',
      'Duże skupisko studentów i środowisk akademickich (AGH, UJ, Politechnika)',
      'Dobra ekspozycja przy trasach do lotniska Balice i na południe kraju',
      'Połączenie odbiorców turystycznych, biznesowych i konsumenckich w jednym mieście'
    ]
  },

  'billboardy-wroclaw': {
    title: 'Billboardy we Wrocławiu – reklama wielkoformatowa',
    description: 'Billboardy we Wrocławiu zapewniają dostęp do jednego z prężnie rosnących rynków konsumenckich w Polsce. Najlepsze lokalizacje skupiają się przy Autostradowej Obwodnicy Wrocławia (AOW), autostradzie A4 i ul. Legnickiej prowadzącej do centrum. Wrocław przyciąga inwestorów i pracowników globalnych korporacji — billboard tutaj jest równie skuteczny w kampaniach konsumenckich, jak i B2B.',
    benefits: [
      'Prężnie rosnący rynek konsumencki i reklamowy',
      'Wysokie natężenie ruchu na AOW i A4 — szlak Berlin–Wrocław–Katowice',
      'Skupisko firm technologicznych i BPO/SSC (m.in. Nokia, Google, Volvo Group)',
      'Dostęp do prestiżowych lokalizacji w okolicach Sky Tower i centrum',
      'Dobra widoczność przy trasach prowadzących do lotniska Strachowice'
    ]
  },

  'billboardy-poznan': {
    title: 'Billboardy w Poznaniu – reklama wielkoformatowa',
    description: 'Billboardy w Poznaniu pracują na głównym szlaku między Berlinem a Warszawą — autostradzie A2. Miasto przyciąga rzesze gości biznesowych na Międzynarodowe Targi Poznańskie, co daje dobrą okazję dla kampanii B2B. Kluczowe lokalizacje billboardowe to okolice MTP, ul. Głogowska, Rondo Rataje i trasy dojazdowe z zachodniej granicy.',
    benefits: [
      'Położenie na autostradzie A2 między Berlinem a Warszawą',
      'Ekspozycja na uczestników targów MTP — rzesze gości biznesowych w sezonie targowym',
      'Silny rynek B2B — dobre tło dla branży przemysłowej i motoryzacyjnej',
      'Dostęp do zamożnych mieszkańców Jeżyc i Piątkowa',
      'Niższe stawki niż w Warszawie przy podobnym profilu odbiorcy biznesowego'
    ]
  },

  'billboardy-gdansk': {
    title: 'Billboardy w Gdańsku – reklama w Trójmieście',
    description: 'Billboardy w Gdańsku to przede wszystkim ruch tranzytowy — Obwodnica Trójmiasta (S6) i Trasa Słowackiego spinają aglomerację i prowadzą do portu, terminali kontenerowych oraz Pomorskiej Specjalnej Strefy Ekonomicznej. Wielkoformatowe tablice docierają tu do mieszkańców w codziennych dojazdach, kierowców zawodowych i kadry dużych trójmiejskich pracodawców. Lokalizacje przy węzłach obwodnicy i drogach dojazdowych do portu pracują przez całą dobę, niezależnie od sezonu. To nośnik pod kampanie wizerunkowe, logistyczne i B2B kierowane do całego rynku trójmiejskiego.',
    benefits: [
      'Duży ruch tranzytowy na Obwodnicy Trójmiasta (S6) i Trasie Słowackiego',
      'Lokalizacje przy porcie, terminalach kontenerowych i Pomorskiej Specjalnej Strefie Ekonomicznej',
      'Ekspozycja całą dobę i przez cały rok — niezależnie od sezonu turystycznego',
      'Dotarcie do mieszkańców w dojazdach i kadry dużych trójmiejskich pracodawców',
      'Dobre tło dla kampanii logistycznych, B2B i wizerunkowych'
    ]
  },

  // ── CITYLIGHTY ────────────────────────────────────────────────────────────

  'citylighty-warszawa': {
    title: 'Citylighty w Warszawie – reklama przy przystankach',
    description: 'Citylighty w Warszawie to format pod marki celujące w aktywnych mieszkańców stolicy — osoby dojeżdżające metrem, tramwajem i autobusami ZTM. Największa sieć nośników koncentruje się przy przystankach na Śródmieściu, Woli i Ursynowie, wzdłuż Al. Jerozolimskich i ul. Marszałkowskiej. Standardowy format 120×180 cm podświetlany od środka zapewnia czas kontaktu nawet kilka minut przy każdym oczekiwaniu na transport.',
    benefits: [
      'Wysoki czas kontaktu — pasażerowie czytają reklamy podczas oczekiwania na pojazd',
      'Gęsta sieć nośników na głównych ciągach pieszych i przystankach ZTM',
      'Dobre dla branży fashion, beauty, retail i promocji wydarzeń kulturalnych',
      'Podświetlenie LED gwarantuje pełną widoczność przez całą dobę',
      'Możliwość precyzyjnego targetowania na konkretne dzielnice i linie komunikacyjne'
    ]
  },

  'citylighty-krakow': {
    title: 'Citylighty w Krakowie – reklama przy przystankach',
    description: 'Citylighty w Krakowie docierają do pieszych i pasażerów komunikacji miejskiej w jednym z najbardziej turystycznych miast Polski. Kluczowe lokalizacje to okolice Dworca Głównego, al. Mickiewicza, Nowy Kleparz oraz Galeria Krakowska. Podświetlana forma nośnika sprawia, że reklama działa efektywnie zarówno w dzień, jak i wieczorem, gdy turyści i mieszkańcy najchętniej przemieszczają się pieszo.',
    benefits: [
      'Dotarcie do turystów w kluczowych punktach tranzytowych Krakowa',
      'Ekspozycja przy przystankach MPK o bardzo wysokim dziennym przepływie pasażerów',
      'Sprawdza się w kampaniach gastronomii, hotelarstwa i rozrywki',
      'Nośniki przy Dworcu Głównym z ekspozycją na przyjezdnych z całej Polski',
      'Format szczególnie skuteczny podczas Krakowskiego Festiwalu Filmowego i imprez masowych'
    ]
  },

  'citylighty-wroclaw': {
    title: 'Citylighty we Wrocławiu – reklama miejska',
    description: 'Citylighty we Wrocławiu sprawdzają się szczególnie w sąsiedztwie uczelni wyższych (Politechnika Wrocławska, Uniwersytet Wrocławski), centrów handlowych (Magnolia Park, Galeria Dominikańska) i przystanków MPK przy pl. Dominikańskim i ul. Świdnickiej. Wrocław ma wysoki udział komunikacji miejskiej w codziennych podróżach, co przekłada się na wysoki czas ekspozycji nośników.',
    benefits: [
      'Wysoki udział komunikacji miejskiej — pasażerowie MPK to Twoi stali odbiorcy',
      'Dostęp do środowisk studenckich — Wrocław to jeden z największych ośrodków akademickich w kraju',
      'Lokalizacje przy Magnolia Park i Galerii Dominikańskiej z dużym ruchem pieszych',
      'Sprawdza się przy kampaniach FMCG, telekomunikacji i usług finansowych',
      'Niższy koszt dotarcia niż w Warszawie przy zbliżonym profilu konsumenta'
    ]
  },

  'citylighty-poznan': {
    title: 'Citylighty w Poznaniu – reklama przy przystankach',
    description: 'Citylighty w Poznaniu docierają do dużej społeczności studenckiej i aktywnych mieszkańców centrum. Najlepsze lokalizacje to okolice Jeżyc, Starego Rynku, przystanki przy ul. Święty Marcin i Rondo Kaponiera. W trakcie targów MTP i dużych eventów ruch pieszych w centrum Poznania wyraźnie rośnie, znacząco podnosząc zasięg citylightów.',
    benefits: [
      'Duże skupisko studentów i młodych profesjonalistów w centrum',
      'Dobra ekspozycja przy Starym Rynku i w Jeżycach',
      'Wzrost zasięgu podczas targów MTP i eventów miejskich',
      'Dobre dla branży odzieżowej, gastronomicznej i usług miejskich',
      'Konkurencyjne stawki cenowe przy wysokiej jakości ekspozycji'
    ]
  },

  'citylighty-gdansk': {
    title: 'Citylighty w Gdańsku – reklama miejska w Trójmieście',
    description: 'Citylighty w Gdańsku pracują tam, gdzie ludzie poruszają się pieszo — wzdłuż ul. Długiej i Długiego Targu, na al. Grunwaldzkiej, przy Dworcu Głównym i przystankach SKM we Wrzeszczu oraz Oliwie. Pieszy albo pasażer czekający na kolejkę ma czas, żeby przeczytać szczegóły oferty, a latem ten ruch w Śródmieściu wyraźnie rośnie wraz z napływem turystów. Podświetlana gablota działa równie dobrze wieczorem, gdy mieszkańcy i odwiedzający najchętniej spacerują. To format dla gastronomii, handlu, hotelarstwa i wydarzeń kulturalnych adresowanych do gdańszczan i przyjezdnych.',
    benefits: [
      'Pracuje przy głównych ciągach pieszych — ul. Długa, Długi Targ, al. Grunwaldzka',
      'Ekspozycja przy przystankach SKM we Wrzeszczu i Oliwie oraz przy Dworcu Głównym',
      'Latem ruch pieszych w Śródmieściu rośnie wraz z napływem turystów',
      'Podświetlenie utrzymuje czytelność wieczorem, w porze największego ruchu spacerowego',
      'Najtańszy format pod kampanie obejmujące całe miasto, dzielnica po dzielnicy'
    ]
  },

  // ── EKRANY LED ────────────────────────────────────────────────────────────

  'ekrany-led-warszawa': {
    title: 'Ekrany LED w Warszawie – reklama cyfrowa DOOH',
    description: 'Ekrany LED w Warszawie to jeden z najlepiej rozwiniętych rynków reklamy cyfrowej (DOOH) w Polsce. Największe skupisko nośników LED znajdziesz przy Rondzie ONZ, ul. Marszałkowskiej, centrum Złote Tarasy i Dworcu Centralnym. Dzięki możliwości zmiany treści w czasie rzeczywistym ekrany LED w stolicy dobrze nadają się do kampanii reaktywnych, premier produktów i live marketingu podczas wydarzeń sportowych.',
    benefits: [
      'Największy rynek ekranów LED w Polsce — gęsta sieć lokalizacji w całym mieście',
      'Dynamiczny przekaz wideo wyróżnia się na tle nośników statycznych',
      'Możliwość targetowania czasowego — różne kreacje rano, w południe i wieczorem',
      'Brak kosztów druku i logistyki przy zmianie kampanii',
      'Sprawdza się w kampaniach real-time (pogoda, wyniki sportowe, odliczanie do eventu)'
    ]
  },

  'ekrany-led-krakow': {
    title: 'Ekrany LED w Krakowie – reklama cyfrowa DOOH',
    description: 'Ekrany LED w Krakowie koncentrują się w okolicach Galerii Krakowskiej, ronda Mogilskiego i głównych arterii miejskich. Format DOOH w Krakowie zyskuje szczególną wartość podczas masowych wydarzeń kulturalnych — Cracovia Maraton, Krakowski Festiwal Filmowy, Wianki — gdy miasto odwiedzają rzesze dodatkowych osób. Wysoka jasność i dynamiczny przekaz dają wysoki współczynnik zauważalności w centrum miasta.',
    benefits: [
      'Wysoka zauważalność na tle innych formatów OOH w centrum Krakowa',
      'Szczególna efektywność podczas licznych festiwali i wydarzeń masowych',
      'Możliwość emisji treści dostosowanych do turystów (w kilku językach)',
      'Koncentracja nośników przy Galerii Krakowskiej i Dworcu Głównym',
      'Elastyczność kampanii — możliwość emisji tylko w weekendy lub w godzinach szczytu'
    ]
  },

  'ekrany-led-wroclaw': {
    title: 'Ekrany LED we Wrocławiu – reklama cyfrowa DOOH',
    description: 'Ekrany LED we Wrocławiu to format rozwijający się najszybciej spośród wszystkich nośników OOH w mieście. Kluczowe lokalizacje to okolice Sky Tower, rondo Reagana i Dworzec Wrocław Główny. Podczas wydarzeń takich jak Brave Festival, Wratislavia Cantans czy Maraton Wrocław, ekrany LED przy głównych ciągach komunikacyjnych osiągają wyraźnie wyższe zasięgi w dni eventów.',
    benefits: [
      'Szybko rosnący segment OOH we Wrocławiu',
      'Prestiżowe lokalizacje przy Sky Tower i centrum biznesowym',
      'Wzmożony ruch podczas licznych imprez kulturalnych i sportowych',
      'Dotarcie do pracowników firm technologicznych i BPO/SSC',
      'Możliwość integracji kampanii z reklamą digital (retargeting po ekspozycji DOOH)'
    ]
  },

  'ekrany-led-poznan': {
    title: 'Ekrany LED w Poznaniu – reklama cyfrowa DOOH',
    description: 'Ekrany LED w Poznaniu najlepiej sprawdzają się w okolicach Starego Browaru, CH King Cross Marcelin i Ronda Kaponiera. W trakcie targów MTP i dużych eventów lokalne ekrany LED osiągają wyraźnie wyższe zasięgi niż w standardowe dni robocze. Format szczególnie polecany dla branży motoryzacyjnej, technologicznej i marek premium kierujących przekaz do decydentów biznesowych.',
    benefits: [
      'Lokalizacje przy Starym Browarze i centrum handlowym King Cross',
      'Wzmożone zasięgi podczas targów MTP — bezpośrednie dotarcie do gości biznesowych',
      'Rosnąca sieć nośników cyfrowych w centrum i przy głównych arteriach',
      'Dobry format pod kampanie premier, launchy produktów i eventów',
      'Możliwość elastycznej emisji dopasowanej do kalendarza targowego'
    ]
  },

  'ekrany-led-gdansk': {
    title: 'Ekrany LED w Gdańsku – DOOH w Trójmieście',
    description: 'Ekrany LED w Gdańsku najmocniej grają tym, czego nie potrafi statyczny nośnik — treść da się podmienić z dnia na dzień, więc latem łatwo przełączyć kampanię na wersję wielojęzyczną pod turystów, a poza sezonem wrócić do komunikatu lokalnego. Najaktywniejsze lokalizacje to okolice Forum Gdańsk, Targu Węglowego i ul. Długiej w Śródmieściu oraz węzeł przy Dworcu Głównym. Wysoka jasność diod utrzymuje czytelność w nadmorskim słońcu i po zmroku. To format dla marek turystycznych, kulturalnych i handlowych, które chcą reagować na kalendarz wydarzeń i pogodę.',
    benefits: [
      'Treść podmieniana z dnia na dzień — kampania nadąża za kalendarzem wydarzeń i pogodą',
      'Łatwe przełączenie na wersję wielojęzyczną pod sezon turystyczny',
      'Kluczowe lokalizacje przy Forum Gdańsk, Targu Węglowym i Dworcu Głównym',
      'Wysoka jasność diod utrzymuje czytelność w nadmorskim słońcu i po zmroku',
      'Rosnąca sieć nośników cyfrowych przy centrach handlowych i węzłach przesiadkowych'
    ]
  },

  // ── BILLBOARDY — Łódź i Katowice ─────────────────────────────────────────

  'billboardy-lodz': {
    title: 'Billboardy w Łodzi – reklama wielkoformatowa',
    description: 'Billboardy w Łodzi oferują jedne z najkorzystniejszych stawek na mapie dużych polskich miast przy zasięgu obejmującym całe miasto. Łódź leży na skrzyżowaniu autostrad A1 (północ–południe) i A2 (wschód–zachód), tworząc naturalne centrum logistyczne Polski. Billboardy przy węzłach autostradowych wokół miasta, al. Piłsudskiego i trasach wylotowych na Warszawę i Wrocław należą do najchętniej wybieranych lokalizacji w regionie.',
    benefits: [
      'Strategiczne położenie na skrzyżowaniu A1 i A2 — centrum logistyczne Polski',
      'Jedne z najniższych stawek CPT wśród dużych miast przy wysokim zasięgu',
      'Dostęp do mieszkańców całego miasta i dużego ruchu tranzytowego',
      'Kluczowe lokalizacje przy węźle autostradowym i al. Piłsudskiego',
      'Dobre pod kampanie ogólnopolskie z centrum dystrybucji w Łodzi'
    ]
  },

  'billboardy-katowice': {
    title: 'Billboardy w Katowicach – reklama na Śląsku',
    description: 'Billboardy w Katowicach to dostęp do mieszkańców Górnośląsko-Zagłębiowskiej Metropolii — największego skupiska miejskiego w kraju. Autostrada A4 przecinająca aglomerację śląską oraz DTŚ i DK86 generują jedne z najwyższych natężeń ruchu samochodowego w kraju. Reklama w Katowicach dociera jednocześnie do Gliwic, Chorzowa, Bytomia i Sosnowca, co daje korzystny koszt dotarcia przy szerokim zasięgu.',
    benefits: [
      'Zasięg na całą Metropolię GZM — kilkanaście połączonych miast',
      'Jedno z najwyższych natężeń ruchu w Polsce (A4, DTŚ, DK86)',
      'Niski koszt dotarcia (CPT) przy bardzo szerokim zasięgu geograficznym',
      'Skupisko przemysłu, logistyki i sektora usług — dobre tło dla B2B',
      'Możliwość łączenia kampanii z nośnikami w Gliwicach, Chorzowie i Sosnowcu'
    ]
  },

  // ── CITYLIGHTY — Łódź i Katowice ─────────────────────────────────────────

  'citylighty-lodz': {
    title: 'Citylighty w Łodzi – reklama przy przystankach',
    description: 'Citylighty w Łodzi skupiają się przy przystankach komunikacji miejskiej wzdłuż ul. Piotrkowskiej, al. Piłsudskiego i w okolicach dużych centrów handlowych (Manufaktura, Galeria Łódzka). Łódź dynamicznie się zmienia — rewitalizacja centrum i rosnąca liczba studentów sprawiają, że reklama w sercu miasta dociera do coraz bardziej aktywnej i zamożniejszej grupy odbiorców. Citylighty to najtańszy format dla kampanii citywide w dużym mieście.',
    benefits: [
      'Dostęp do studentów i mieszkańców rewitalizowanego centrum Łodzi',
      'Gęsta sieć nośników wzdłuż ul. Piotrkowskiej i głównych arterii',
      'Ekspozycja przy Manufakturze i Galerii Łódzkiej z wysokim ruchem pieszych',
      'Konkurencyjne stawki przy szerokim zasięgu miejskim',
      'Dobre pod kampanie lokalnych sieci handlowych i usługowych'
    ]
  },

  'citylighty-katowice': {
    title: 'Citylighty w Katowicach – reklama miejska',
    description: 'Citylighty w Katowicach docierają do aktywnych mieszkańców centrum i pasażerów ZTM Metropolii GZM — jednego z największych organizatorów komunikacji miejskiej w Polsce. Kluczowe lokalizacje to okolice Silesia City Center, przystanki przy ul. Francuskiej, rondo im. gen. Ziętka oraz okolice Spodka i NOSPR. Format szczególnie skuteczny podczas eventów masowych organizowanych w Spodku i MCK, przyciągających publiczność z całej aglomeracji.',
    benefits: [
      'Zasięg na pasażerów ZTM Metropolii GZM — jeden z największych systemów komunikacji w Polsce',
      'Kluczowe lokalizacje przy Silesia City Center i centrum kulturalnym (Spodek, NOSPR)',
      'Wzmożony ruch podczas eventów w Spodku i Międzynarodowym Centrum Kongresowym',
      'Dostęp do pracowników biurowych i kadry zarządzającej w centrum Katowic',
      'Możliwość kampanii obejmujących całą aglomerację śląską'
    ]
  },

  // ── EKRANY LED — Łódź i Katowice ─────────────────────────────────────────

  'ekrany-led-lodz': {
    title: 'Ekrany LED w Łodzi – reklama cyfrowa DOOH',
    description: 'Ekrany LED w Łodzi to wciąż rozwijający się, a przez to bardziej przystępny cenowo segment reklamy cyfrowej w mieście o strategicznym położeniu komunikacyjnym. Najaktywniejsze lokalizacje koncentrują się przy Manufakturze, al. Piłsudskiego i głównych skrzyżowaniach centrum. Łódź jako centrum logistyczne i e-commerce przyciąga coraz więcej inwestorów, co przekłada się na rosnące zapotrzebowanie na kampanie DOOH wśród lokalnych i ogólnopolskich marek.',
    benefits: [
      'Korzystniejsze stawki niż w Warszawie przy rosnącym wolumenie ekspozycji',
      'Strategiczne położenie dla kampanii centralnej Polski',
      'Rosnąca liczba lokalizacji przy Manufakturze i centrum handlowym',
      'Dobre dla e-commerce i firm logistycznych z centrum dystrybucji w Łodzi',
      'Dynamiczny format zwiększający zauważalność kampanii w centrum miasta'
    ]
  },

  'ekrany-led-katowice': {
    title: 'Ekrany LED w Katowicach – DOOH na Śląsku',
    description: 'Ekrany LED w Katowicach i całej Metropolii GZM oferują ekspozycję na jednym z największych skupisk miejskich w Polsce. Kluczowe lokalizacje to okolice Spodka, Silesia City Center i głównych skrzyżowań DTŚ. Format DOOH w Katowicach zyskuje na wartości podczas licznych eventów masowych — Tauron Nowa Muzyka, Siesta Festival, Śląski Festiwal Nauki — przyciągających rzesze uczestników z całego regionu.',
    benefits: [
      'Ekspozycja na mieszkańców całej Metropolii GZM dzięki lokalizacjom przy DTŚ',
      'Wzmożone zasięgi podczas eventów w Spodku i MCK Katowice',
      'Dynamiczny przekaz wideo wyróżniający się na tle tradycyjnych nośników OOH',
      'Dobre dla branży energetycznej, przemysłowej i usług B2B',
      'Rosnąca sieć ekranów przy centrach handlowych i węzłach komunikacyjnych'
    ]
  },

  // ── BANERY — top 5 miast ──────────────────────────────────────────────────

  'banery-warszawa': {
    title: 'Banery reklamowe w Warszawie – elastyczna reklama OOH',
    description: 'Banery reklamowe w Warszawie to elastyczny i kosztowo efektywny format outdoor, montowany na rusztowaniach budowlanych, ogrodzeniach placów budowy i elewacjach budynków. Stolica z intensywną zabudową i licznymi inwestycjami budowlanymi oferuje wiele atrakcyjnych lokalizacji banerowych w centrum i na Pradze. Format sprawdza się szczególnie przy długoterminowych kampaniach wizerunkowych i promocjach lokalnych.',
    benefits: [
      'Duże powierzchnie ekspozycji w najbardziej ruchliwych miejscach stolicy',
      'Niższy koszt produkcji i montażu niż nośniki wielkoformatowe',
      'Sprawdza się na ogrodzeniach placów budowy w centrum — długi czas ekspozycji',
      'Możliwość zamówienia niestandardowych wymiarów dopasowanych do lokalizacji',
      'Skuteczne przy kampaniach deweloperskich, handlowych i wizerunkowych'
    ]
  },

  'banery-krakow': {
    title: 'Banery reklamowe w Krakowie – elastyczna reklama OOH',
    description: 'Banery reklamowe w Krakowie pozwalają na ekspozycję w miejscach niedostępnych dla typowych nośników billboardowych — na płotach budowlanych przy inwestycjach w centrum, na elewacjach kamienic i przy obiektach sportowych. W mieście z rygorystyczną polityką estetyczną centrum historycznego baner bywa jedną z nielicznych możliwych form wielkoformatowej reklamy w pobliżu starówki.',
    benefits: [
      'Jedna z nielicznych form wielkoformatowej reklamy dopuszczalnych w okolicach starówki',
      'Możliwość ekspozycji przy licznych inwestycjach budowlanych w centrum',
      'Atrakcyjne lokalizacje przy obiektach sportowych (Wisła, Cracovia)',
      'Elastyczne wymiary i możliwość szybkiej zmiany kreacji',
      'Niższy koszt przy porównywalnym zasięgu do innych formatów OOH'
    ]
  },

  'banery-wroclaw': {
    title: 'Banery reklamowe we Wrocławiu – elastyczna reklama OOH',
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
    title: 'Banery reklamowe w Poznaniu – elastyczna reklama OOH',
    description: 'Banery reklamowe w Poznaniu sprawdzają się wszędzie tam, gdzie nie ma miejsca na billboard — na ogrodzeniach Międzynarodowych Targów Poznańskich, przy obiektach sportowych (Stadion Poznań – stadion Lecha) i na elewacjach remontowanych budynków. W trakcie targów MTP ogrodzenia terenów wystawienniczych to wyjątkowo cenne lokalizacje banerowe, eksponowane na rzesze gości biznesowych.',
    benefits: [
      'Lokalizacje przy ogrodzeniach MTP z ekspozycją na gości targowych',
      'Ekspozycja przy stadionie Lecha Poznań podczas meczów i eventów',
      'Możliwość kampanii krótkoterminowych przy konkretnych wydarzeniach',
      'Elastyczny format dostępny szybciej niż tradycyjne nośniki',
      'Konkurencyjny koszt przy wysokiej widoczności w centrum miasta'
    ]
  },

  'banery-gdansk': {
    title: 'Banery reklamowe w Gdańsku – reklama w Trójmieście',
    description: 'Banery reklamowe w Gdańsku najczęściej towarzyszą budowom — Wyspa Spichrzów, Młode Miasto i rewitalizowane Śródmieście to jeden wielki plac inwestycyjny, a ogrodzenia, rusztowania i elewacje remontowanych kamienic to gotowe powierzchnie pod wielki format. Baner postawisz szybko i na dowolny etap inwestycji: od „tu powstaje” po „wprowadź się”. To pierwszy wybór deweloperów i generalnych wykonawców, ale sprawdza się też przy nabrzeżach i obiektach portowych. Format niedrogi, robiony na wymiar i łatwy do wymiany w trakcie kampanii.',
    benefits: [
      'Mnóstwo lokalizacji przy budowach — Wyspa Spichrzów, Młode Miasto, rewitalizowane Śródmieście',
      'Ogrodzenia, rusztowania i elewacje remontowanych kamienic jako gotowe powierzchnie',
      'Można postawić szybko i na konkretny etap inwestycji — od „tu powstaje” po sprzedaż',
      'Pierwszy wybór deweloperów i generalnych wykonawców',
      'Niedrogi, robiony na wymiar i łatwy do wymiany w trakcie kampanii'
    ]
  },

  // ── CITYLIGHTY (kolejne miasta) ───────────────────────────────────────────

  'citylighty-olsztyn': {
    title: 'Citylighty w Olsztynie – reklama przy przystankach',
    description: 'Citylighty w Olsztynie to format pod marki celujące w mieszkańców i studentów codziennie korzystających z komunikacji — Olsztyn ma jedyną w regionie sieć tramwajową, a przystanki przy trasach tramwajowych i autobusowych skupiają potok pasażerów na głównych ciągach centrum: przy ul. 1 Maja, w okolicach Starego Miasta, dworca oraz kampusu UWM w Kortowie. Standardowy format 120×180 cm podświetlany od środka utrzymuje pełną widoczność po zmroku i daje kilka minut kontaktu przy każdym oczekiwaniu na pojazd. To nośnik dla retailu, usług lokalnych, branży edukacyjnej i promocji wydarzeń kulturalnych — a w sezonie wakacyjnym łapie też ruch turystyczny zmierzający na Mazury.',
    benefits: [
      'Przystanki tramwajowe i autobusowe na głównych ciągach centrum — duży potok pasażerów',
      'Wysoki czas kontaktu — kilka minut przy każdym oczekiwaniu na pojazd',
      'Podświetlenie od środka gwarantuje widoczność przez całą dobę',
      'Dobre dla retailu, usług lokalnych, branży edukacyjnej i wydarzeń kulturalnych',
      'Okolice dworca i kampusu UWM w Kortowie jako punkty wzmożonego ruchu pieszego'
    ]
  },

  'citylighty-gdynia': {
    title: 'Citylighty w Gdyni – reklama przy przystankach',
    description: 'Citylighty w Gdyni docierają do zamożnych mieszkańców, kadry nowoczesnych biurowców i turystów na głównych ciągach pieszych — przystanki ZKM i trasy trolejbusowe wzdłuż ul. Świętojańskiej, 10 Lutego, przy Skwerze Kościuszki, na Wzgórzu Św. Maksymiliana oraz w okolicach Orłowa i biurowców przy ul. Łużyckiej. Format 120×180 cm podświetlany od środka pracuje przy każdym oczekiwaniu na pojazd, a w sezonie i podczas dużych wydarzeń, jak Open’er, zasięg wykracza poza lokalny. To dobre tło dla marek premium, usług finansowych, gastronomii i retailu — a kampanię łatwo rozszerzyć na resztę Trójmiasta.',
    benefits: [
      'Główne ciągi piesze — Świętojańska, 10 Lutego, Skwer Kościuszki, Wzgórze Św. Maksymiliana',
      'Zamożni mieszkańcy i kadra biurowców — tło dla marek premium i usług finansowych',
      'Sieć ZKM i trolejbusów zapewnia gęstość nośników przy przystankach',
      'W sezonie i podczas dużych wydarzeń (Open’er) zasięg ponadlokalny',
      'Łatwe rozszerzenie kampanii na Gdańsk i Sopot — wspólny rynek trójmiejski'
    ]
  },

  'citylighty-bydgoszcz': {
    title: 'Citylighty w Bydgoszczy – reklama przy przystankach',
    description: 'Citylighty w Bydgoszczy to format pod dotarcie do mieszkańców w codziennych dojazdach i studentów — przystanki tramwajowe i autobusowe MZK na głównych ciągach: ul. Gdańskiej, Dworcowej, przy placu Wolności, w okolicach Wyspy Młyńskiej, dworca Bydgoszcz Główna oraz uczelni. Standardowy format 120×180 cm podświetlany od środka daje kilka minut kontaktu przy każdym oczekiwaniu na pojazd i pełną widoczność po zmroku. To nośnik dla retailu, usług lokalnych, gastronomii i promocji wydarzeń kulturalnych — w okolicach galerii (Galeria Pomorska, Focus Mall) i instytucji takich jak Opera Nova ruch pieszy jest wyraźnie większy.',
    benefits: [
      'Przystanki tramwajowe i autobusowe MZK na ul. Gdańskiej, Dworcowej i przy placu Wolności',
      'Wysoki czas kontaktu i widoczność przez całą dobę dzięki podświetleniu',
      'Okolice dworca Bydgoszcz Główna i uczelni jako punkty wzmożonego ruchu',
      'Dobre dla retailu, gastronomii, usług lokalnych i wydarzeń kulturalnych',
      'Większy ruch pieszy przy galeriach (Galeria Pomorska, Focus Mall) i Operze Nova'
    ]
  },

  // ── REKLAMA W TRANSPORCIE (miasta) ────────────────────────────────────────

  'reklama-w-transporcie-krakow': {
    title: 'Reklama w transporcie Kraków – autobusy i tramwaje MPK',
    description: 'Reklama w transporcie w Krakowie jeździ po mieście razem z pojazdami MPK — autobusami i tramwajami kursującymi przez Stare Miasto, Kazimierz, Podgórze, Nową Hutę i okolice kampusów AGH oraz UJ. W ofertach znajdziesz pełne i częściowe oklejenia pojazdów (full i half wrap), tablice wewnątrz oraz ekrany w systemie informacji pasażerskiej. Ten sam pojazd kursuje stałą trasą wiele razy dziennie, więc reklama buduje wysoką częstotliwość kontaktu przy niskim koszcie dotarcia. Kraków ma jeden z największych w Polsce rynków studenckich i ogromny ruch turystyczny — to nośnik pod kampanie rekrutacyjne, promocje lokalne i komunikaty do szerokiego, miejskiego odbiorcy. Bywają dostępne konkretne linie obsługujące wybrane dzielnice.',
    benefits: [
      'Pojazdy MPK kursujące przez Stare Miasto, Kazimierz, Podgórze, Nową Hutę i kampusy',
      'Pełne i częściowe oklejenia, tablice wewnątrz i ekrany w systemie informacji pasażerskiej',
      'Wysoka częstotliwość kontaktu — jeden pojazd przejeżdża trasę wiele razy dziennie',
      'Ogromny rynek studencki i ruch turystyczny — dobre pod rekrutację i promocje lokalne',
      'Możliwy wybór konkretnych linii obsługujących wybrane dzielnice'
    ]
  },

  'reklama-w-transporcie-poznan': {
    title: 'Reklama w transporcie Poznań – autobusy i tramwaje MPK',
    description: 'Reklama w transporcie w Poznaniu jeździ po mieście razem z pojazdami MPK — autobusami i tramwajami kursującymi przez Stary Rynek, Półwiejską, Jeżyce, Wildę, okolice dworca Poznań Główny oraz kampus UAM na Morasku. W ofertach znajdziesz pełne i częściowe oklejenia pojazdów (full i half wrap), tablice wewnątrz oraz ekrany w systemie informacji pasażerskiej. Ten sam pojazd kursuje stałą trasą wiele razy dziennie, więc reklama buduje wysoką częstotliwość kontaktu przy niskim koszcie dotarcia. W sezonie targowym do ruchu lokalnego dochodzą goście Międzynarodowych Targów Poznańskich — to nośnik pod kampanie targowe, rekrutacyjne i komunikaty do szerokiego, miejskiego odbiorcy. Bywają dostępne konkretne linie obsługujące wybrane dzielnice.',
    benefits: [
      'Pojazdy MPK kursujące przez Stary Rynek, Półwiejską, Jeżyce, Wildę i kampus UAM',
      'Pełne i częściowe oklejenia, tablice wewnątrz i ekrany informacji pasażerskiej',
      'Wysoka częstotliwość kontaktu — jeden pojazd przejeżdża trasę wiele razy dziennie',
      'W sezonie targowym zasięg rośnie o gości Międzynarodowych Targów Poznańskich',
      'Możliwy wybór konkretnych linii obsługujących wybrane dzielnice'
    ]
  },

  // ── REKLAMA MOBILNA (miasta) ──────────────────────────────────────────────

  'reklama-mobilna-warszawa': {
    title: 'Reklama mobilna Warszawa – przyczepy i mobile billboard',
    description: 'Reklama mobilna w Warszawie to przyczepa albo oklejony pojazd, który dowozisz dokładnie tam, gdzie chcesz — pod Złote Tarasy, Galerię Mokotów czy Arkadię, na Pole Mokotowskie, w okolice biurowców na Woli i Służewcu albo pod wydarzenia na PGE Narodowym. Trasę i godziny przejazdu ustawiasz pod szczyty komunikacyjne lub konkretny event, a mobile billboard wjedzie tam, gdzie nie ma stałych nośników — w ścisłe centrum i strefę płatnego parkowania. Sprawdza się przy otwarciach lokali, premierach, akcjach last-minute i kampaniach sezonowych. Dobrze domyka plan mediów: dociąga zasięg w miejscach, których siatka billboardów w stolicy nie objęła.',
    benefits: [
      'Dowieziesz nośnik pod konkretny adres — galerie, biurowce Woli i Służewca, PGE Narodowy',
      'Trasa i godziny pod szczyty komunikacyjne albo pod wybrane wydarzenie',
      'Wjeżdża w ścisłe centrum i strefę płatnego parkowania, gdzie brak stałych konstrukcji',
      'Dobre przy otwarciach, premierach i kampaniach last-minute',
      'Domyka plan mediów — dociąga zasięg tam, gdzie siatka billboardów ma luki'
    ]
  },

  'reklama-mobilna-krakow': {
    title: 'Reklama mobilna Kraków – przyczepy reklamowe w ruchu',
    description: 'Reklama mobilna w Krakowie to przyczepa albo oklejony pojazd dowieziony tam, gdzie chcesz — pod Galerię Krakowską, Bonarkę, w okolice placów handlowych, na tereny eventowe przy Tauron Arenie i Błoniach albo w pobliże kampusów. Trasę i godziny przejazdu układasz pod szczyty komunikacyjne lub konkretne wydarzenie, z uwzględnieniem ograniczeń ruchu w ścisłym centrum. To format pod otwarcia lokali, festiwale, akcje last-minute i kampanie sezonowe, gdzie liczy się obecność tu i teraz. Sprawdza się też jako uzupełnienie kampanii outdoorowej — dociąga zasięg w strefie ograniczonego ruchu i tam, gdzie stałych nośników brakuje.',
    benefits: [
      'Dowieziesz nośnik pod galerie, place handlowe i tereny eventowe (Tauron Arena, Błonia)',
      'Trasa i godziny pod szczyty komunikacyjne lub konkretne wydarzenie',
      'Działa w strefie ograniczonego ruchu w centrum, gdzie nie ma stałych konstrukcji',
      'Dobre przy otwarciach, festiwalach i kampaniach sezonowych',
      'Uzupełnia kampanię outdoorową — dociąga zasięg tam, gdzie plan mediów ma luki'
    ]
  },

  'reklama-mobilna-bydgoszcz': {
    title: 'Reklama mobilna Bydgoszcz – przyczepy reklamowe',
    description: 'Reklama mobilna w Bydgoszczy to przyczepa albo oklejony pojazd dowieziony dokładnie tam, gdzie chcesz — pod Galerię Pomorską, Focus Mall, w okolice ronda Jagiellonów i centrum, na tereny eventowe przy Operze Nova i Wyspie Młyńskiej albo do Fordonu. Trasę i godziny przejazdu ustawiasz pod szczyty komunikacyjne lub konkretne wydarzenie, a mobile billboard wjedzie w wąskie ulice śródmieścia i miejsca, których stałe nośniki nie obsłużą. Sprawdza się przy otwarciach lokali, akcjach last-minute i kampaniach sezonowych. Dobrze działa też jako uzupełnienie kampanii billboardowej — domyka zasięg tam, gdzie plan stałych nośników ma luki.',
    benefits: [
      'Dowieziesz nośnik pod galerie (Galeria Pomorska, Focus Mall), centrum i tereny eventowe',
      'Trasa i godziny pod szczyty komunikacyjne lub konkretne wydarzenie',
      'Wjeżdża w wąskie ulice śródmieścia i miejsca niedostępne dla stałych konstrukcji',
      'Dobre przy otwarciach lokali, akcjach last-minute i kampaniach sezonowych',
      'Uzupełnia kampanię billboardową — domyka zasięg w lukach planu mediów'
    ]
  },

  // ── TOTEMY REKLAMOWE (miasta) ─────────────────────────────────────────────

  'totemy-reklamowe-poznan': {
    title: 'Totemy reklamowe Poznań – pylony przy galeriach',
    description: 'Totem reklamowy w Poznaniu to wolnostojący pylon przy wjazdach do galerii (m.in. Posnania, Galeria Malta, okolice Starego Browaru), parków handlowych przy obwodnicy i autostradzie A2, stacji paliw na trasach wylotowych oraz biurowców na Grunwaldzie i przy ul. Głogowskiej. Konstrukcja ma zwykle od trzech do około dziesięciu metrów wysokości i mieści kilka kaset, więc na jednym pylonie pokazuje się naraz kilku najemców. Podświetlenie LED utrzymuje widoczność po zmroku i w gorszą pogodę. To nośnik dla sieci handlowych, gastronomii, aptek i salonów samochodowych — m.in. w okolicach Franowa — pracuje dokładnie tam, gdzie klient podejmuje decyzję, czyli przy punkcie sprzedaży, i pełni funkcję nawigacyjną kierując kierowców do obiektu.',
    benefits: [
      'Pylony przy wjazdach do galerii (Posnania, Galeria Malta) i parków handlowych przy A2',
      'Jedna konstrukcja mieści kilka kaset — równoległa ekspozycja kilku marek',
      'Podświetlenie LED utrzymuje widoczność po zmroku i w gorszych warunkach',
      'Dla sieci handlowych, gastronomii, aptek i salonów samochodowych (m.in. Franowo)',
      'Pełni funkcję nawigacyjną — kieruje kierowców do obiektu i utrwala markę w jego otoczeniu'
    ]
  },

  // ── BANERY (miasta) ───────────────────────────────────────────────────────

  'banery-lodz': {
    title: 'Banery reklamowe w Łodzi – wielki format na budowach',
    description: 'Banery reklamowe w Łodzi najczęściej towarzyszą rewitalizacji — Księży Młyn, Off Piotrkowska, okolice EC1 i Nowego Centrum Łodzi, a do tego setki remontowanych kamienic wzdłuż ul. Piotrkowskiej tworzą jeden wielki plac inwestycyjny. Ogrodzenia, rusztowania i elewacje przebudowywanych fabryk i kamienic to gotowe powierzchnie pod siatki mesh i banery wielkoformatowe. Baner postawisz szybko i na dowolny etap inwestycji: od „tu powstaje” po „wprowadź się”. To pierwszy wybór deweloperów i generalnych wykonawców, ale sprawdza się też przy obiektach handlowych i kulturalnych w centrum. Format niedrogi, robiony na wymiar i łatwy do wymiany w trakcie kampanii.',
    benefits: [
      'Mnóstwo lokalizacji przy rewitalizacji — Księży Młyn, Off Piotrkowska, EC1, Nowe Centrum Łodzi',
      'Remontowane kamienice wzdłuż ul. Piotrkowskiej jako gotowe powierzchnie pod wielki format',
      'Można postawić szybko i na konkretny etap inwestycji — od „tu powstaje” po sprzedaż',
      'Pierwszy wybór deweloperów i generalnych wykonawców',
      'Niedrogi, robiony na wymiar i łatwy do wymiany w trakcie kampanii'
    ]
  }
}
