<?php

declare(strict_types=1);

namespace BaseMgmt\Modules\Weather;

defined('ABSPATH') || exit;

/**
 * Synchronises IMGW meteorological warnings.
 * API: GET https://danepubliczne.imgw.pl/api/data/warningsmeteo
 * Filtering: by voivodeship (TERYT prefix) or specific county (exact TERYT code).
 */
final class ImgwAlertsSync {

    private const SETTINGS_KEY     = 'basemgmt_imgw_settings';
    private const API_URL          = 'https://danepubliczne.imgw.pl/api/data/warningsmeteo';
    private const LAST_SYNC_OPTION = 'bm_imgw_last_sync';
    private const SYNC_LOG_OPTION  = 'bm_imgw_last_sync_log';

    /** GUS TERYT 2-digit voivodeship codes */
    private const VOIVODESHIP_TERYT = [
        'dolnoslaskie'        => '02',
        'kujawsko-pomorskie'  => '04',
        'lubelskie'           => '06',
        'lubuskie'            => '08',
        'lodzkie'             => '10',
        'malopolskie'         => '12',
        'mazowieckie'         => '14',
        'opolskie'            => '16',
        'podkarpackie'        => '18',
        'podlaskie'           => '20',
        'pomorskie'           => '22',
        'slaskie'             => '24',
        'swietokrzyskie'      => '26',
        'warminsko-mazurskie' => '28',
        'wielkopolskie'       => '30',
        'zachodniopomorskie'  => '32',
    ];

    /**
     * Full TERYT county list: code => [voivodeship_key, display_name]
     * Codes are 4-digit (first 2 = voivodeship, last 2 = county within voivodeship).
     */
    public const COUNTIES = [
        // Dolnośląskie (02)
        '0201' => ['dolnoslaskie',        'Bolesławiecki'],
        '0202' => ['dolnoslaskie',        'Dzierżoniowski'],
        '0203' => ['dolnoslaskie',        'Głogowski'],
        '0204' => ['dolnoslaskie',        'Górowski'],
        '0205' => ['dolnoslaskie',        'Jaworski'],
        '0206' => ['dolnoslaskie',        'Jeleniogórski'],
        '0207' => ['dolnoslaskie',        'Kamiennogórski'],
        '0208' => ['dolnoslaskie',        'Kłodzki'],
        '0209' => ['dolnoslaskie',        'Legnicki'],
        '0210' => ['dolnoslaskie',        'Lubański'],
        '0211' => ['dolnoslaskie',        'Lubiński'],
        '0212' => ['dolnoslaskie',        'Lwówecki'],
        '0213' => ['dolnoslaskie',        'Milicki'],
        '0214' => ['dolnoslaskie',        'Oleśnicki'],
        '0215' => ['dolnoslaskie',        'Oławski'],
        '0216' => ['dolnoslaskie',        'Polkowicki'],
        '0217' => ['dolnoslaskie',        'Strzeliński'],
        '0218' => ['dolnoslaskie',        'Średzki'],
        '0219' => ['dolnoslaskie',        'Świdnicki'],
        '0220' => ['dolnoslaskie',        'Trzebnicki'],
        '0221' => ['dolnoslaskie',        'Wałbrzyski'],
        '0222' => ['dolnoslaskie',        'Wołowski'],
        '0223' => ['dolnoslaskie',        'Wrocławski'],
        '0224' => ['dolnoslaskie',        'Ząbkowicki'],
        '0225' => ['dolnoslaskie',        'Zgorzelecki'],
        '0226' => ['dolnoslaskie',        'Złotoryjski'],
        '0261' => ['dolnoslaskie',        'Jelenia Góra (m.)'],
        '0262' => ['dolnoslaskie',        'Legnica (m.)'],
        '0264' => ['dolnoslaskie',        'Wrocław (m.)'],
        '0265' => ['dolnoslaskie',        'Wałbrzych (m.)'],
        // Kujawsko-Pomorskie (04)
        '0401' => ['kujawsko-pomorskie',  'Aleksandrowski'],
        '0402' => ['kujawsko-pomorskie',  'Brodnicki'],
        '0403' => ['kujawsko-pomorskie',  'Bydgoski'],
        '0404' => ['kujawsko-pomorskie',  'Chełmiński'],
        '0405' => ['kujawsko-pomorskie',  'Golubsko-Dobrzyński'],
        '0406' => ['kujawsko-pomorskie',  'Grudziądzki'],
        '0407' => ['kujawsko-pomorskie',  'Inowrocławski'],
        '0408' => ['kujawsko-pomorskie',  'Lipnowski'],
        '0409' => ['kujawsko-pomorskie',  'Mogileński'],
        '0410' => ['kujawsko-pomorskie',  'Nakielski'],
        '0411' => ['kujawsko-pomorskie',  'Radziejowski'],
        '0412' => ['kujawsko-pomorskie',  'Rypiński'],
        '0413' => ['kujawsko-pomorskie',  'Sępoleński'],
        '0414' => ['kujawsko-pomorskie',  'Świecki'],
        '0415' => ['kujawsko-pomorskie',  'Toruński'],
        '0416' => ['kujawsko-pomorskie',  'Tucholski'],
        '0417' => ['kujawsko-pomorskie',  'Wąbrzeski'],
        '0418' => ['kujawsko-pomorskie',  'Włocławski'],
        '0419' => ['kujawsko-pomorskie',  'Żniński'],
        '0461' => ['kujawsko-pomorskie',  'Bydgoszcz (m.)'],
        '0462' => ['kujawsko-pomorskie',  'Grudziądz (m.)'],
        '0463' => ['kujawsko-pomorskie',  'Toruń (m.)'],
        '0464' => ['kujawsko-pomorskie',  'Włocławek (m.)'],
        // Lubelskie (06)
        '0601' => ['lubelskie',           'Bialski'],
        '0602' => ['lubelskie',           'Biłgorajski'],
        '0603' => ['lubelskie',           'Chełmski'],
        '0604' => ['lubelskie',           'Hrubieszowski'],
        '0605' => ['lubelskie',           'Janowski'],
        '0606' => ['lubelskie',           'Krasnostawski'],
        '0607' => ['lubelskie',           'Kraśnicki'],
        '0608' => ['lubelskie',           'Lubartowski'],
        '0609' => ['lubelskie',           'Lubelski'],
        '0610' => ['lubelskie',           'Łęczyński'],
        '0611' => ['lubelskie',           'Łukowski'],
        '0612' => ['lubelskie',           'Opolski'],
        '0613' => ['lubelskie',           'Parczewski'],
        '0614' => ['lubelskie',           'Puławski'],
        '0615' => ['lubelskie',           'Radzyński'],
        '0616' => ['lubelskie',           'Rycki'],
        '0617' => ['lubelskie',           'Świdnicki'],
        '0618' => ['lubelskie',           'Tomaszowski'],
        '0619' => ['lubelskie',           'Włodawski'],
        '0620' => ['lubelskie',           'Zamojski'],
        '0661' => ['lubelskie',           'Biała Podlaska (m.)'],
        '0662' => ['lubelskie',           'Chełm (m.)'],
        '0663' => ['lubelskie',           'Lublin (m.)'],
        '0664' => ['lubelskie',           'Zamość (m.)'],
        // Lubuskie (08)
        '0801' => ['lubuskie',            'Gorzowski'],
        '0802' => ['lubuskie',            'Krośnieński'],
        '0803' => ['lubuskie',            'Międzyrzecki'],
        '0804' => ['lubuskie',            'Nowosolski'],
        '0805' => ['lubuskie',            'Słubicki'],
        '0806' => ['lubuskie',            'Strzelecko-Drezdenecki'],
        '0807' => ['lubuskie',            'Sulęciński'],
        '0808' => ['lubuskie',            'Świebodziński'],
        '0809' => ['lubuskie',            'Wschowski'],
        '0810' => ['lubuskie',            'Zielonogórski'],
        '0811' => ['lubuskie',            'Żagański'],
        '0812' => ['lubuskie',            'Żarski'],
        '0861' => ['lubuskie',            'Gorzów Wielkopolski (m.)'],
        '0862' => ['lubuskie',            'Zielona Góra (m.)'],
        // Łódzkie (10)
        '1001' => ['lodzkie',             'Bełchatowski'],
        '1002' => ['lodzkie',             'Brzeziński'],
        '1003' => ['lodzkie',             'Kutnowski'],
        '1004' => ['lodzkie',             'Łaski'],
        '1005' => ['lodzkie',             'Łęczycki'],
        '1006' => ['lodzkie',             'Łowicki'],
        '1007' => ['lodzkie',             'Łódzki Wschodni'],
        '1008' => ['lodzkie',             'Makowski'],
        '1009' => ['lodzkie',             'Opoczyński'],
        '1010' => ['lodzkie',             'Pabianicki'],
        '1011' => ['lodzkie',             'Pajęczański'],
        '1012' => ['lodzkie',             'Piotrkowski'],
        '1013' => ['lodzkie',             'Poddębicki'],
        '1014' => ['lodzkie',             'Radomszczański'],
        '1015' => ['lodzkie',             'Rawski'],
        '1016' => ['lodzkie',             'Sieradzki'],
        '1017' => ['lodzkie',             'Skierniewicki'],
        '1018' => ['lodzkie',             'Tomaszowski'],
        '1019' => ['lodzkie',             'Wieluński'],
        '1020' => ['lodzkie',             'Wieruszowski'],
        '1021' => ['lodzkie',             'Zduńskowolski'],
        '1022' => ['lodzkie',             'Zgierski'],
        '1061' => ['lodzkie',             'Łódź (m.)'],
        '1062' => ['lodzkie',             'Piotrków Trybunalski (m.)'],
        '1063' => ['lodzkie',             'Skierniewice (m.)'],
        // Małopolskie (12)
        '1201' => ['malopolskie',         'Bocheński'],
        '1202' => ['malopolskie',         'Brzeski'],
        '1203' => ['malopolskie',         'Chrzanowski'],
        '1204' => ['malopolskie',         'Dąbrowski'],
        '1205' => ['malopolskie',         'Gorlicki'],
        '1206' => ['malopolskie',         'Krakowski'],
        '1207' => ['malopolskie',         'Limanowski'],
        '1208' => ['malopolskie',         'Miechowski'],
        '1209' => ['malopolskie',         'Myślenicki'],
        '1210' => ['malopolskie',         'Nowosądecki'],
        '1211' => ['malopolskie',         'Nowotarski'],
        '1212' => ['malopolskie',         'Olkuski'],
        '1213' => ['malopolskie',         'Oświęcimski'],
        '1214' => ['malopolskie',         'Proszowicki'],
        '1215' => ['malopolskie',         'Suski'],
        '1216' => ['malopolskie',         'Tarnowski'],
        '1217' => ['malopolskie',         'Tatrzański'],
        '1218' => ['malopolskie',         'Wadowicki'],
        '1219' => ['malopolskie',         'Wielicki'],
        '1261' => ['malopolskie',         'Kraków (m.)'],
        '1262' => ['malopolskie',         'Nowy Sącz (m.)'],
        '1263' => ['malopolskie',         'Tarnów (m.)'],
        // Mazowieckie (14)
        '1401' => ['mazowieckie',         'Białobrzeski'],
        '1402' => ['mazowieckie',         'Ciechanowski'],
        '1403' => ['mazowieckie',         'Garwoliński'],
        '1404' => ['mazowieckie',         'Gostyniński'],
        '1405' => ['mazowieckie',         'Grodziski'],
        '1406' => ['mazowieckie',         'Grójecki'],
        '1407' => ['mazowieckie',         'Kozienicki'],
        '1408' => ['mazowieckie',         'Legionowski'],
        '1409' => ['mazowieckie',         'Lipski'],
        '1410' => ['mazowieckie',         'Łosicki'],
        '1411' => ['mazowieckie',         'Makowski'],
        '1412' => ['mazowieckie',         'Miński'],
        '1413' => ['mazowieckie',         'Mławski'],
        '1414' => ['mazowieckie',         'Nowodworski'],
        '1415' => ['mazowieckie',         'Ostrołęcki'],
        '1416' => ['mazowieckie',         'Ostrowski'],
        '1417' => ['mazowieckie',         'Otwocki'],
        '1418' => ['mazowieckie',         'Piaseczyński'],
        '1419' => ['mazowieckie',         'Płocki'],
        '1420' => ['mazowieckie',         'Płoński'],
        '1421' => ['mazowieckie',         'Pruszkowski'],
        '1422' => ['mazowieckie',         'Przasnyski'],
        '1423' => ['mazowieckie',         'Przysuski'],
        '1424' => ['mazowieckie',         'Pułtuski'],
        '1425' => ['mazowieckie',         'Radomski'],
        '1426' => ['mazowieckie',         'Siedlecki'],
        '1427' => ['mazowieckie',         'Sierpecki'],
        '1428' => ['mazowieckie',         'Sochaczewski'],
        '1429' => ['mazowieckie',         'Sokołowski'],
        '1430' => ['mazowieckie',         'Szydłowiecki'],
        '1431' => ['mazowieckie',         'Warszawski Zachodni'],
        '1432' => ['mazowieckie',         'Węgrowski'],
        '1433' => ['mazowieckie',         'Wołomiński'],
        '1434' => ['mazowieckie',         'Wyszkowski'],
        '1435' => ['mazowieckie',         'Zwoleński'],
        '1436' => ['mazowieckie',         'Żuromiński'],
        '1437' => ['mazowieckie',         'Żyrardowski'],
        '1461' => ['mazowieckie',         'Ostrołęka (m.)'],
        '1462' => ['mazowieckie',         'Płock (m.)'],
        '1463' => ['mazowieckie',         'Radom (m.)'],
        '1464' => ['mazowieckie',         'Siedlce (m.)'],
        '1465' => ['mazowieckie',         'Warszawa (m.)'],
        // Opolskie (16)
        '1601' => ['opolskie',            'Brzeski'],
        '1602' => ['opolskie',            'Głubczycki'],
        '1603' => ['opolskie',            'Kędzierzyńsko-Kozielski'],
        '1604' => ['opolskie',            'Kluczborski'],
        '1605' => ['opolskie',            'Krapkowicki'],
        '1606' => ['opolskie',            'Namysłowski'],
        '1607' => ['opolskie',            'Nyski'],
        '1608' => ['opolskie',            'Oleski'],
        '1609' => ['opolskie',            'Opolski'],
        '1610' => ['opolskie',            'Prudnicki'],
        '1611' => ['opolskie',            'Strzelecki'],
        '1661' => ['opolskie',            'Opole (m.)'],
        // Podkarpackie (18)
        '1801' => ['podkarpackie',        'Bieszczadzki'],
        '1802' => ['podkarpackie',        'Brzozowski'],
        '1803' => ['podkarpackie',        'Dębicki'],
        '1804' => ['podkarpackie',        'Jarosławski'],
        '1805' => ['podkarpackie',        'Jasielski'],
        '1806' => ['podkarpackie',        'Kolbuszowski'],
        '1807' => ['podkarpackie',        'Krośnieński'],
        '1808' => ['podkarpackie',        'Leski'],
        '1809' => ['podkarpackie',        'Leżajski'],
        '1810' => ['podkarpackie',        'Lubaczowski'],
        '1811' => ['podkarpackie',        'Łańcucki'],
        '1812' => ['podkarpackie',        'Mielecki'],
        '1813' => ['podkarpackie',        'Niżański'],
        '1814' => ['podkarpackie',        'Przemyski'],
        '1815' => ['podkarpackie',        'Przeworski'],
        '1816' => ['podkarpackie',        'Ropczycko-Sędziszowski'],
        '1817' => ['podkarpackie',        'Rzeszowski'],
        '1818' => ['podkarpackie',        'Sanocki'],
        '1819' => ['podkarpackie',        'Stalowowolski'],
        '1820' => ['podkarpackie',        'Strzyżowski'],
        '1821' => ['podkarpackie',        'Tarnobrzeski'],
        '1861' => ['podkarpackie',        'Krosno (m.)'],
        '1862' => ['podkarpackie',        'Przemyśl (m.)'],
        '1863' => ['podkarpackie',        'Rzeszów (m.)'],
        '1864' => ['podkarpackie',        'Tarnobrzeg (m.)'],
        // Podlaskie (20)
        '2001' => ['podlaskie',           'Augustowski'],
        '2002' => ['podlaskie',           'Białostocki'],
        '2003' => ['podlaskie',           'Bielski'],
        '2004' => ['podlaskie',           'Grajewski'],
        '2005' => ['podlaskie',           'Hajnowski'],
        '2006' => ['podlaskie',           'Kolneński'],
        '2007' => ['podlaskie',           'Łomżyński'],
        '2008' => ['podlaskie',           'Moniecki'],
        '2009' => ['podlaskie',           'Sejneński'],
        '2010' => ['podlaskie',           'Siemiatycki'],
        '2011' => ['podlaskie',           'Sokólski'],
        '2012' => ['podlaskie',           'Suwalski'],
        '2013' => ['podlaskie',           'Wysokomazowiecki'],
        '2014' => ['podlaskie',           'Zambrowski'],
        '2061' => ['podlaskie',           'Białystok (m.)'],
        '2062' => ['podlaskie',           'Łomża (m.)'],
        '2063' => ['podlaskie',           'Suwałki (m.)'],
        // Pomorskie (22)
        '2201' => ['pomorskie',           'Bytowski'],
        '2202' => ['pomorskie',           'Chojnicki'],
        '2203' => ['pomorskie',           'Człuchowski'],
        '2204' => ['pomorskie',           'Gdański'],
        '2205' => ['pomorskie',           'Kartuski'],
        '2206' => ['pomorskie',           'Kościerski'],
        '2207' => ['pomorskie',           'Kwidzyński'],
        '2208' => ['pomorskie',           'Lęborski'],
        '2209' => ['pomorskie',           'Malborski'],
        '2210' => ['pomorskie',           'Nowodworski'],
        '2211' => ['pomorskie',           'Pucki'],
        '2212' => ['pomorskie',           'Słupski'],
        '2213' => ['pomorskie',           'Starogardzki'],
        '2214' => ['pomorskie',           'Sztumski'],
        '2215' => ['pomorskie',           'Tczewski'],
        '2216' => ['pomorskie',           'Wejherowski'],
        '2261' => ['pomorskie',           'Gdańsk (m.)'],
        '2262' => ['pomorskie',           'Gdynia (m.)'],
        '2263' => ['pomorskie',           'Słupsk (m.)'],
        '2264' => ['pomorskie',           'Sopot (m.)'],
        // Śląskie (24)
        '2401' => ['slaskie',             'Będziński'],
        '2402' => ['slaskie',             'Bielski'],
        '2403' => ['slaskie',             'Cieszyński'],
        '2404' => ['slaskie',             'Częstochowski'],
        '2405' => ['slaskie',             'Gliwicki'],
        '2406' => ['slaskie',             'Kłobucki'],
        '2407' => ['slaskie',             'Lubliniecki'],
        '2408' => ['slaskie',             'Mikołowski'],
        '2409' => ['slaskie',             'Myszkowski'],
        '2410' => ['slaskie',             'Pszczyński'],
        '2411' => ['slaskie',             'Raciborski'],
        '2412' => ['slaskie',             'Rybnicki'],
        '2413' => ['slaskie',             'Tarnogórski'],
        '2414' => ['slaskie',             'Wodzisławski'],
        '2415' => ['slaskie',             'Zawierciański'],
        '2416' => ['slaskie',             'Żywiecki'],
        '2461' => ['slaskie',             'Bielsko-Biała (m.)'],
        '2462' => ['slaskie',             'Bytom (m.)'],
        '2463' => ['slaskie',             'Chorzów (m.)'],
        '2464' => ['slaskie',             'Częstochowa (m.)'],
        '2465' => ['slaskie',             'Dąbrowa Górnicza (m.)'],
        '2466' => ['slaskie',             'Gliwice (m.)'],
        '2467' => ['slaskie',             'Jastrzębie-Zdrój (m.)'],
        '2468' => ['slaskie',             'Jaworzno (m.)'],
        '2469' => ['slaskie',             'Katowice (m.)'],
        '2470' => ['slaskie',             'Mysłowice (m.)'],
        '2471' => ['slaskie',             'Piekary Śląskie (m.)'],
        '2472' => ['slaskie',             'Ruda Śląska (m.)'],
        '2473' => ['slaskie',             'Rybnik (m.)'],
        '2474' => ['slaskie',             'Siemianowice Śląskie (m.)'],
        '2475' => ['slaskie',             'Sosnowiec (m.)'],
        '2476' => ['slaskie',             'Świętochłowice (m.)'],
        '2477' => ['slaskie',             'Tychy (m.)'],
        '2478' => ['slaskie',             'Zabrze (m.)'],
        '2479' => ['slaskie',             'Żory (m.)'],
        // Świętokrzyskie (26)
        '2601' => ['swietokrzyskie',      'Buski'],
        '2602' => ['swietokrzyskie',      'Jędrzejowski'],
        '2603' => ['swietokrzyskie',      'Kazimierski'],
        '2604' => ['swietokrzyskie',      'Kielecki'],
        '2605' => ['swietokrzyskie',      'Konecki'],
        '2606' => ['swietokrzyskie',      'Opatowski'],
        '2607' => ['swietokrzyskie',      'Ostrowiecki'],
        '2608' => ['swietokrzyskie',      'Pińczowski'],
        '2609' => ['swietokrzyskie',      'Sandomierski'],
        '2610' => ['swietokrzyskie',      'Skarżyski'],
        '2611' => ['swietokrzyskie',      'Starachowicki'],
        '2612' => ['swietokrzyskie',      'Staszowski'],
        '2613' => ['swietokrzyskie',      'Włoszczowski'],
        '2661' => ['swietokrzyskie',      'Kielce (m.)'],
        // Warmińsko-Mazurskie (28)
        '2801' => ['warminsko-mazurskie', 'Bartoszycki'],
        '2802' => ['warminsko-mazurskie', 'Braniewski'],
        '2803' => ['warminsko-mazurskie', 'Działdowski'],
        '2804' => ['warminsko-mazurskie', 'Elbląski'],
        '2805' => ['warminsko-mazurskie', 'Ełcki'],
        '2806' => ['warminsko-mazurskie', 'Giżycki'],
        '2807' => ['warminsko-mazurskie', 'Gołdapski'],
        '2808' => ['warminsko-mazurskie', 'Iławski'],
        '2809' => ['warminsko-mazurskie', 'Kętrzyński'],
        '2810' => ['warminsko-mazurskie', 'Lidzbarski'],
        '2811' => ['warminsko-mazurskie', 'Mrągowski'],
        '2812' => ['warminsko-mazurskie', 'Nidzicki'],
        '2813' => ['warminsko-mazurskie', 'Nowomiejski'],
        '2814' => ['warminsko-mazurskie', 'Olecki'],
        '2815' => ['warminsko-mazurskie', 'Olsztyński'],
        '2816' => ['warminsko-mazurskie', 'Ostródzki'],
        '2817' => ['warminsko-mazurskie', 'Piski'],
        '2818' => ['warminsko-mazurskie', 'Szczycieński'],
        '2819' => ['warminsko-mazurskie', 'Węgorzewski'],
        '2861' => ['warminsko-mazurskie', 'Elbląg (m.)'],
        '2862' => ['warminsko-mazurskie', 'Olsztyn (m.)'],
        // Wielkopolskie (30)
        '3001' => ['wielkopolskie',       'Chodzieski'],
        '3002' => ['wielkopolskie',       'Czarnkowsko-Trzcianecki'],
        '3003' => ['wielkopolskie',       'Gnieźnieński'],
        '3004' => ['wielkopolskie',       'Gostyński'],
        '3005' => ['wielkopolskie',       'Grodziski'],
        '3006' => ['wielkopolskie',       'Jarociński'],
        '3007' => ['wielkopolskie',       'Kaliski'],
        '3008' => ['wielkopolskie',       'Kępiński'],
        '3009' => ['wielkopolskie',       'Kolski'],
        '3010' => ['wielkopolskie',       'Koniński'],
        '3011' => ['wielkopolskie',       'Kościański'],
        '3012' => ['wielkopolskie',       'Krotoszyński'],
        '3013' => ['wielkopolskie',       'Leszczyński'],
        '3014' => ['wielkopolskie',       'Międzychodzki'],
        '3015' => ['wielkopolskie',       'Nowotomyski'],
        '3016' => ['wielkopolskie',       'Obornicki'],
        '3017' => ['wielkopolskie',       'Ostrowski'],
        '3018' => ['wielkopolskie',       'Ostrzeszowski'],
        '3019' => ['wielkopolskie',       'Pilski'],
        '3020' => ['wielkopolskie',       'Pleszewski'],
        '3021' => ['wielkopolskie',       'Poznański'],
        '3022' => ['wielkopolskie',       'Rawicki'],
        '3023' => ['wielkopolskie',       'Słupecki'],
        '3024' => ['wielkopolskie',       'Szamotulski'],
        '3025' => ['wielkopolskie',       'Średzki'],
        '3026' => ['wielkopolskie',       'Śremski'],
        '3027' => ['wielkopolskie',       'Turecki'],
        '3028' => ['wielkopolskie',       'Wągrowiecki'],
        '3029' => ['wielkopolskie',       'Wolsztyński'],
        '3030' => ['wielkopolskie',       'Wrzesiński'],
        '3031' => ['wielkopolskie',       'Złotowski'],
        '3061' => ['wielkopolskie',       'Kalisz (m.)'],
        '3062' => ['wielkopolskie',       'Konin (m.)'],
        '3063' => ['wielkopolskie',       'Leszno (m.)'],
        '3064' => ['wielkopolskie',       'Poznań (m.)'],
        // Zachodniopomorskie (32)
        '3201' => ['zachodniopomorskie',  'Białogardzki'],
        '3202' => ['zachodniopomorskie',  'Choszczeński'],
        '3203' => ['zachodniopomorskie',  'Drawski'],
        '3204' => ['zachodniopomorskie',  'Goleniowski'],
        '3205' => ['zachodniopomorskie',  'Gryficki'],
        '3206' => ['zachodniopomorskie',  'Gryfiński'],
        '3207' => ['zachodniopomorskie',  'Kamieński'],
        '3208' => ['zachodniopomorskie',  'Kołobrzeski'],
        '3209' => ['zachodniopomorskie',  'Koszaliński'],
        '3210' => ['zachodniopomorskie',  'Łobeski'],
        '3211' => ['zachodniopomorskie',  'Myśliborski'],
        '3212' => ['zachodniopomorskie',  'Policki'],
        '3213' => ['zachodniopomorskie',  'Pyrzycki'],
        '3214' => ['zachodniopomorskie',  'Sławieński'],
        '3215' => ['zachodniopomorskie',  'Stargardzki'],
        '3216' => ['zachodniopomorskie',  'Szczecinecki'],
        '3217' => ['zachodniopomorskie',  'Świdwiński'],
        '3218' => ['zachodniopomorskie',  'Wałecki'],
        '3261' => ['zachodniopomorskie',  'Koszalin (m.)'],
        '3262' => ['zachodniopomorskie',  'Szczecin (m.)'],
        '3263' => ['zachodniopomorskie',  'Świnoujście (m.)'],
    ];

    // ── Settings ──────────────────────────────────────────────────────────────

    public static function get_settings(): array {
        $defaults = [
            'enabled'        => false,
            'voivodeship'    => '',
            'county_teryt'   => '',
            'sync_interval'  => 'hourly',
            'custom_api_url' => '',
        ];
        return wp_parse_args(get_option(self::SETTINGS_KEY, []), $defaults);
    }

    public static function save_settings(array $data): void {
        update_option(self::SETTINGS_KEY, [
            'enabled'        => (bool) ($data['imgw_enabled']    ?? false),
            'voivodeship'    => sanitize_key($data['voivodeship'] ?? ''),
            'county_teryt'   => sanitize_text_field($data['county_teryt'] ?? ''),
            'sync_interval'  => sanitize_key($data['sync_interval'] ?? 'hourly'),
            'custom_api_url' => esc_url_raw($data['custom_api_url'] ?? ''),
        ]);
    }

    public static function is_enabled(): bool {
        return (bool) self::get_settings()['enabled'];
    }

    public static function voivodeships(): array {
        return [
            ''                    => '— cała Polska —',
            'dolnoslaskie'        => 'Dolnośląskie',
            'kujawsko-pomorskie'  => 'Kujawsko-Pomorskie',
            'lubelskie'           => 'Lubelskie',
            'lubuskie'            => 'Lubuskie',
            'lodzkie'             => 'Łódzkie',
            'malopolskie'         => 'Małopolskie',
            'mazowieckie'         => 'Mazowieckie',
            'opolskie'            => 'Opolskie',
            'podkarpackie'        => 'Podkarpackie',
            'podlaskie'           => 'Podlaskie',
            'pomorskie'           => 'Pomorskie',
            'slaskie'             => 'Śląskie',
            'swietokrzyskie'      => 'Świętokrzyskie',
            'warminsko-mazurskie' => 'Warmińsko-Mazurskie',
            'wielkopolskie'       => 'Wielkopolskie',
            'zachodniopomorskie'  => 'Zachodniopomorskie',
        ];
    }

    /**
     * Get counties for a given voivodeship key, or all if empty.
     * Returns [teryt_code => display_name].
     */
    public static function counties_for_voivodeship(string $voivodeship = ''): array {
        $result = ['' => '— cały region —'];
        foreach ( self::COUNTIES as $code => [$voiv, $name] ) {
            if ( $voivodeship === '' || $voiv === $voivodeship ) {
                $result[$code] = $name;
            }
        }
        return $result;
    }

    // ── Sync ──────────────────────────────────────────────────────────────────

    public function sync(): array {
        $settings = self::get_settings();
        $log      = ['fetched' => 0, 'inserted' => 0, 'updated' => 0, 'deactivated' => 0, 'error' => ''];

        if ( ! $settings['enabled'] ) {
            $log['error'] = 'Sync IMGW wyłączony w ustawieniach.';
            return $log;
        }

        $api_url = $settings['custom_api_url'] ?: self::API_URL;
        if ( ! self::is_safe_api_url($api_url) ) {
            $log['error'] = 'Nieprawidłowy lub niedozwolony URL API IMGW.';
            $this->save_log($log);
            return $log;
        }

        $response = wp_safe_remote_get($api_url, [
            'timeout'            => 15,
            'sslverify'          => true,
            'reject_unsafe_urls' => true,
            'headers'            => ['Accept' => 'application/json'],
        ]);

        if ( is_wp_error($response) ) {
            $log['error'] = $response->get_error_message();
            $this->save_log($log);
            return $log;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ( $code !== 200 ) {
            $log['error'] = "Błąd HTTP: {$code}";
            $this->save_log($log);
            return $log;
        }

        $body = wp_remote_retrieve_body($response);

        // IMGW returns empty body when no warnings are active.
        if ( trim($body) === '' ) {
            WeatherAlertRepository::deactivate_stale_imgw([]);
            update_option(self::LAST_SYNC_OPTION, gmdate('Y-m-d H:i:s'));
            $this->save_log($log);
            do_action('bm_imgw_sync_complete', $log);
            return $log;
        }

        $raw = json_decode($body, true);
        if ( ! is_array($raw) ) {
            $log['error'] = 'Nieprawidłowa odpowiedź API IMGW (oczekiwano JSON).';
            $this->save_log($log);
            return $log;
        }

        $log['fetched']      = count($raw);
        $active_external_ids = [];

        // Build TERYT filter: county takes priority over voivodeship.
        $county_teryt = $settings['county_teryt'] ?? '';
        $teryt_prefix = '';
        if ( ! $county_teryt && $settings['voivodeship'] ) {
            $teryt_prefix = self::VOIVODESHIP_TERYT[ $settings['voivodeship'] ] ?? '';
        }

        foreach ( $raw as $w ) {
            if ( $county_teryt ) {
                if ( ! $this->warning_has_exact_teryt($w, $county_teryt) ) {
                    continue;
                }
            } elseif ( $teryt_prefix ) {
                if ( ! $this->warning_matches_prefix($w, $teryt_prefix) ) {
                    continue;
                }
            }

            $external_id = 'imgw_' . sanitize_key($w['id'] ?? '');
            if ( $external_id === 'imgw_' ) {
                continue;
            }

            $alert_data = $this->map_to_alert($w, $external_id);
            if ( ! $alert_data ) {
                continue;
            }

            $existing = WeatherAlertRepository::get_by_external_id($external_id);
            $result   = WeatherAlertRepository::upsert_imgw($alert_data);

            if ( $result ) {
                $active_external_ids[] = $external_id;
                $log[ $existing ? 'updated' : 'inserted' ]++;
            }
        }

        WeatherAlertRepository::deactivate_stale_imgw($active_external_ids);
        update_option(self::LAST_SYNC_OPTION, gmdate('Y-m-d H:i:s'));
        $this->save_log($log);
        do_action('bm_imgw_sync_complete', $log);

        return $log;
    }

    public static function get_last_sync(): ?string {
        return get_option(self::LAST_SYNC_OPTION, null) ?: null;
    }

    public static function get_last_log(): array {
        return get_option(self::SYNC_LOG_OPTION, []);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function save_log(array $log): void {
        update_option(self::SYNC_LOG_OPTION, $log);
    }

    private static function is_safe_api_url(string $url): bool {
        $url = trim($url);
        if ( $url === '' || ! wp_http_validate_url($url) ) {
            return false;
        }

        $parts = wp_parse_url($url);
        if ( ! is_array($parts) ) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host   = strtolower((string) ($parts['host'] ?? ''));

        if ( $scheme !== 'https' || $host === '' ) {
            return false;
        }

        return true;
    }

    private function warning_has_exact_teryt(array $w, string $teryt): bool {
        $list = $w['teryt'] ?? [];
        if ( empty($list) ) {
            return true;
        }
        return in_array($teryt, $list, true) || in_array((string)(int)$teryt, $list, true);
    }

    private function warning_matches_prefix(array $w, string $prefix): bool {
        $list = $w['teryt'] ?? [];
        if ( empty($list) ) {
            return true;
        }
        foreach ( $list as $teryt ) {
            if ( str_starts_with((string) $teryt, $prefix) ) {
                return true;
            }
        }
        return false;
    }

    private function map_to_alert(array $w, string $external_id): ?array {
        $level     = (int) ($w['stopien'] ?? 1);
        $phenomenon = $w['nazwa_zdarzenia'] ?? 'Ostrzeżenie meteorologiczne';
        $prob      = (int) ($w['prawdopodobienstwo'] ?? 0);
        $content   = $w['tresc']     ?? '';
        $comment   = $w['komentarz'] ?? '';
        $office    = $w['biuro']     ?? '';

        $type = match (true) {
            $level >= 3 => WeatherAlertRepository::TYPE_DANGER,
            $level >= 2 => WeatherAlertRepository::TYPE_WARNING,
            default     => WeatherAlertRepository::TYPE_INFO,
        };

        $title = "IMGW – {$phenomenon} (stopień {$level}/3)";

        $message_parts = [ $content ];
        if ( $prob > 0 ) {
            $message_parts[] = "Prawdopodobieństwo: {$prob}%";
        }
        if ( $comment ) {
            $message_parts[] = $comment;
        }
        if ( $office ) {
            $message_parts[] = "Źródło: {$office}";
        }

        return [
            'external_id' => $external_id,
            'title'       => $title,
            'message'     => implode("\n", array_filter($message_parts)),
            'type'        => $type,
            'is_urgent'   => $level >= 3 ? 1 : 0,
            'valid_from'  => $w['obowiazuje_od'] ?? null,
            'valid_until' => $w['obowiazuje_do'] ?? null,
        ];
    }
}
