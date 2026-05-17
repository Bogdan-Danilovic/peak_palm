<?php
require_once 'db.php';

/* ---------------------------------------------------------------
   1. SEZONA — citamo iz URL parametra ?sezona=zima|leto.
   Sve dalje upite i CSS temu vodi ova jedna promenljiva.
   --------------------------------------------------------------- */
$current_season = get_season();

/* ---------------------------------------------------------------
   2. PODACI IZ BAZE — destinacije filtrirane po sezoni.
   LEFT JOIN sa ski_info da i dalje radi za zimske kartice
   (kolone su NULL za destinacije bez ski_info reda — npr. letnje).
   --------------------------------------------------------------- */
try {
    $stmt = $pdo->prepare("
        SELECT d.*, s.ukupno_staza_km, s.broj_zicara
        FROM destinacije d
        LEFT JOIN ski_info s ON d.id = s.destinacija_id
        WHERE d.sezona = ?
        ORDER BY d.id
    ");
    $stmt->execute([$current_season]);
    $destinacije = $stmt->fetchAll();
} catch (PDOException $e) {
    /* Ako kolona `sezona` jos ne postoji (stara baza), fallback bez filtera */
    try {
        $stmt = $pdo->query("SELECT d.*, s.ukupno_staza_km, s.broj_zicara
                             FROM destinacije d
                             LEFT JOIN ski_info s ON d.id = s.destinacija_id");
        $destinacije = $stmt->fetchAll();
    } catch (PDOException $e2) {
        die("Greska: " . $e2->getMessage());
    }
}

/* ---------------------------------------------------------------
   2. TICKER + RECENZIJE — direktno iz baze (admin panel ih popunjava)
   Soft-fail: ako tabele jos ne postoje, sekcije se prikazuju prazne
   umesto da cela stranica pukne.
   --------------------------------------------------------------- */
$ticker_items = [];
$recenzije    = [];

try {
    $stmt = $pdo->prepare("
        SELECT tekst
        FROM ticker_items
        WHERE aktivan = 1
        ORDER BY redosled, id
    ");
    $stmt->execute();
    $ticker_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    /* tabela jos ne postoji — pokrenuti migracija.sql */
}

try {
    $stmt = $pdo->prepare("
        SELECT ime, avatar, tekst, ocena,
               datum_prikaza AS datum,
               lokacija       AS dest
        FROM recenzije
        WHERE na_homepage = 1
        ORDER BY redosled, id
        LIMIT 8
    ");
    $stmt->execute();
    $recenzije = $stmt->fetchAll();
} catch (PDOException $e) {
    /* tabela jos ne postoji */
}

/* ---------------------------------------------------------------
   3. KONFIGURACIJA STRANICE — SABLON tekstova po sezoni.
   Ovo je sustinski "univerzalni sablon": ista HTML struktura,
   razliciti sadrzaji u zavisnosti od $current_season.
   --------------------------------------------------------------- */
$content_po_sezoni = [
    'zima' => [
        'page_title'      => 'Katalog Ski Destinacija | Peak and Palm',
        'hero_eyebrow'    => 'Premium Alpine Travel',
        'hero_title_1'    => 'Gde se završava',
        'hero_title_2'    => 'asfalt, tu počinje',
        'hero_title_em'   => 'avantura',
        'hero_subtitle'   => 'Direktno iz Beograda do najlepših ski centara Alpa. Organizacija, logistika i komfor — sve na jednom mestu.',
        'cta_primary'     => 'Istraži katalog',
        'cta_secondary'   => 'Planiranje rute →',
        'catalog_eyebrow' => 'Explore the Slopes',
        'catalog_title_1' => 'Katalog',
        'catalog_title_2' => 'Ski Destinacija',
        'catalog_intro'   => 'Izaberite destinaciju, pregledajte interaktivnu mapu staza i izračunajte troškove logistike iz Beograda.',
        'show_ski_sekcije'=> true,   /* ticker o snegu, partneri ski-brendovi, evropska mapa */
    ],
    'leto' => [
        'page_title'      => 'Katalog Letnjih Destinacija | Peak and Palm',
        'hero_eyebrow'    => 'Premium Mediterranean Travel',
        'hero_title_1'    => 'Gde se susreće',
        'hero_title_2'    => 'nebo i more, tu počinje',
        'hero_title_em'   => 'odmor',
        'hero_subtitle'   => 'Direktno iz Beograda do najlepših plaža i ostrva Mediterana. Sunce, more i komfor — sve na jednom mestu.',
        'cta_primary'     => 'Istraži destinacije',
        'cta_secondary'   => 'Planiranje puta →',
        'catalog_eyebrow' => 'Discover the Coast',
        'catalog_title_1' => 'Katalog',
        'catalog_title_2' => 'Letnjih Destinacija',
        'catalog_intro'   => 'Izaberite destinaciju, otkrijte staze, plaže i atrakcije, planirajte savršen letnji predah.',
        'show_ski_sekcije'=> false,  /* nema snega/skija/evropske ski mape u leto */
    ],
];
$c = $content_po_sezoni[$current_season];
$page_title = $c['page_title'];

include 'partials/head.php';
?>
<body>

<div class="fixed-bg"></div>

<?php include 'partials/nav.php'; ?>

<!-- ============================================================
     1. VIDEO HERO
     ============================================================ -->
<section class="video-hero" id="hero">

    <div class="vhero-video-wrap">
        <!--
            ZAMENA VIDEA:
            A) YouTube iframe:
               <iframe src="https://www.youtube.com/embed/VIDEO_ID?autoplay=1&mute=1&loop=1&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playlist=VIDEO_ID&rel=0" allow="autoplay" frameborder="0"></iframe>
            B) Lokalni fajl:
               <video autoplay muted loop playsinline><source src="videos/hero-ski.mp4" type="video/mp4"></video>
            Trenutno: CSS fallback animacija ispod.
        -->
        <div class="vhero-fallback"></div>
    </div>

    <div class="vhero-overlay"></div>

    <div class="vhero-content">
        <div class="vhero-eyebrow"><?php echo htmlspecialchars($c['hero_eyebrow']); ?></div>
        <h1 class="vhero-title">
            <?php echo htmlspecialchars($c['hero_title_1']); ?><br>
            <?php echo htmlspecialchars($c['hero_title_2']); ?> <em><?php echo htmlspecialchars($c['hero_title_em']); ?></em>
        </h1>
        <p class="vhero-subtitle">
            <?php echo htmlspecialchars($c['hero_subtitle']); ?>
        </p>
        <div class="vhero-cta-group">
            <a href="#katalog" class="vhero-cta-primary">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="7" cy="7" r="6"/><polyline points="7,4 7,7 9,9"/>
                </svg>
                <?php echo htmlspecialchars($c['cta_primary']); ?>
            </a>
            <a href="#katalog" class="vhero-cta-secondary">
                <?php echo htmlspecialchars($c['cta_secondary']); ?>
            </a>
        </div>
    </div>

    <div class="vhero-scroll">
        <span>Skroluj</span>
        <div class="scroll-line"></div>
    </div>

</section>

<!-- ============================================================
     2. LIVE TICKER  (samo ZIMA — info o snegu na planinama)
     ============================================================ -->
<?php if ($c['show_ski_sekcije']): ?>
<div class="ticker-section">
    <div class="ticker-inner">
        <div class="ticker-label">
            <div class="ticker-label-dot"></div>
            Live
        </div>
        <div class="ticker-track">
            <div class="ticker-tape" id="tickerTape">
                <?php
                /* Dupliramo niz da ticker animacija (translateX -50%) izgleda kao beskonacna petlja */
                $all_items = array_merge($ticker_items, $ticker_items);
                foreach ($all_items as $item): ?>
                    <span class="ticker-item"><?php echo htmlspecialchars($item); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; /* ticker zima only */ ?>

<!-- ============================================================
     3. QUICK ROUTE FINDER  (samo ZIMA — kalkulator distance + putarine)
     ============================================================ -->
<?php if ($c['show_ski_sekcije']): ?>
<section class="route-finder-section" id="route-finder">
    <div class="route-finder-wrap reveal">
        <div class="rf-header">
            <span class="rf-eyebrow">Planiranje puta</span>
            <h2 class="rf-title">Brzi kalkulator rute i troškova</h2>
        </div>
        <div class="rf-grid">
            <div class="rf-field">
                <label class="rf-label" for="rf-dest">Destinacija</label>
                <div class="rf-select-wrap">
                    <select class="rf-select" id="rf-dest">
                        <option value="">— Izaberite skijalište —</option>
                        <?php foreach ($destinacije as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>">
                                <?php echo htmlspecialchars($d['naziv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="rf-field">
                <label class="rf-label" for="rf-osobe">Broj osoba</label>
                <input type="number" class="rf-input" id="rf-osobe"
                       min="1" max="9" value="2" placeholder="npr. 4">
            </div>
            <div class="rf-field">
                <label class="rf-label" for="rf-dani">Trajanje (dana)</label>
                <input type="number" class="rf-input" id="rf-dani"
                       min="1" max="21" value="7" placeholder="npr. 7">
            </div>
            <div>
                <button type="button" class="rf-btn" id="rf-submit">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="7" y1="1" x2="7" y2="13"/>
                        <polyline points="3,9 7,13 11,9"/>
                    </svg>
                    Izračunaj
                </button>
            </div>
        </div>
    </div>
</section>
<?php endif; /* route finder zima only */ ?>

<!-- ============================================================
     4. PARTNERS  (samo ZIMA — ski brendovi)
     ============================================================ -->
<?php if ($c['show_ski_sekcije']): ?>
<section class="partners-section" id="partneri">
    <div class="partners-label">Premium Partneri &amp; Preporučena Oprema</div>
    <div class="partners-track">
        <a class="partner-item" data-category="Skije"
           href="https://www.elanskis.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">ELAN<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Skije"
           href="https://www.fischersports.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Fischer<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Skije &amp; Oprema"
           href="https://www.atomic.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Atomic<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Garderoba"
           href="https://www.salomon.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Salomon<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Garderoba"
           href="https://www.bogner.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Bogner<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Skije"
           href="https://www.voelkl.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">Völkl<span class="logo-tag">®</span></span>
        </a>
        <a class="partner-item" data-category="Prevoz"
           href="https://www.flixbus.com/" target="_blank" rel="noopener noreferrer">
            <span class="partner-logo">FlixBus<span class="logo-tag">®</span></span>
        </a>
    </div>
</section>
<?php endif; /* partners zima only */ ?>

<!-- ============================================================
     5. INTERAKTIVNA MAPA EVROPE  (samo ZIMA)
     ============================================================ -->
<?php if ($c['show_ski_sekcije']): ?>
<section class="europe-section" id="mapa">
    <div class="europe-header reveal">
        <span class="section-eyebrow">Logistika iz Beograda</span>
        <h2 class="section-heading">Naše <span>Destinacije</span> na mapi</h2>
    </div>

    <div class="europe-map-container reveal" id="europeMapContainer">

        <div class="map-tooltip" id="mapTooltip"></div>

        <svg viewBox="0 0 800 500" xmlns="http://www.w3.org/2000/svg"
             style="background: rgba(7,12,24,0.6); border-radius: 20px; border: 1px solid rgba(0,229,255,0.08);">

            <defs>
                <filter id="lineGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
                <filter id="dotGlow" x="-100%" y="-100%" width="300%" height="300%">
                    <feGaussianBlur stdDeviation="4" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
                <radialGradient id="bgGrad" cx="50%" cy="50%" r="50%">
                    <stop offset="0%"   stop-color="#0a1428" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#04060d" stop-opacity="1"/>
                </radialGradient>
                <!-- Suptilna mreza tackica koja zamenjuje rogobatne polygone zemalja -->
                <pattern id="dotGrid" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="0.7" fill="rgba(255,255,255,0.06)"/>
                </pattern>
            </defs>

            <rect width="800" height="500" fill="url(#bgGrad)" rx="20"/>
            <rect width="800" height="500" fill="url(#dotGrid)" rx="20"/>

            <!-- Animovane linije Beograd → destinacije -->
            <path d="M 480 300 Q 380 200 230 245"
                  stroke="rgba(0,229,255,0.45)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 4s linear infinite;"></path>
            <path d="M 480 300 Q 420 260 310 275"
                  stroke="rgba(0,229,255,0.40)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 3.5s linear infinite 0.6s;"></path>
            <path d="M 480 300 Q 445 255 370 228"
                  stroke="rgba(0,229,255,0.45)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 3s linear infinite 1.2s;"></path>
            <path d="M 480 300 Q 400 230 285 240"
                  stroke="rgba(0,229,255,0.35)" stroke-width="1.5" fill="none"
                  stroke-dasharray="6 4" filter="url(#lineGlow)"
                  style="animation: dash-flow 4.5s linear infinite 0.3s;"></path>

            <!-- Destination pins -->
            <g class="map-pin" data-dest="Chamonix / Les Orres" data-country="Francuska"
               data-km="1580" data-ski="280 km staza" transform="translate(228, 243)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Cortina d'Ampezzo" data-country="Italija"
               data-km="1190" data-ski="140 km staza" transform="translate(310, 273)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 0.5s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Innsbruck / Arlberg" data-country="Austrija"
               data-km="1025" data-ski="340 km staza" transform="translate(368, 226)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 1.0s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>
            <g class="map-pin" data-dest="Zermatt / Davos" data-country="Švajcarska"
               data-km="1350" data-ski="360 km staza" transform="translate(284, 238)">
                <circle r="14" fill="rgba(0,229,255,0.08)" style="animation: map-ping 2.4s ease-out infinite 1.5s;"/>
                <circle r="6"  fill="rgba(0,229,255,0.18)" stroke="rgba(0,229,255,0.5)" stroke-width="1"/>
                <circle r="3"  fill="#00e5ff" filter="url(#dotGlow)"/>
            </g>

            <!-- Beograd polazna tacka -->
            <g transform="translate(479, 300)">
                <circle r="18" fill="rgba(0,229,255,0.05)" style="animation: map-ping 2s ease-out infinite;"/>
                <circle r="10" fill="rgba(0,229,255,0.14)" stroke="rgba(0,229,255,0.6)" stroke-width="1.5"/>
                <circle r="5"  fill="#00e5ff" filter="url(#dotGlow)"/>
                <text y="-18" text-anchor="middle" font-family="'Outfit',sans-serif"
                      font-size="9.5" font-weight="600" letter-spacing="1"
                      fill="rgba(0,229,255,0.9)">BEOGRAD</text>
            </g>

            <text x="225" y="235" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">FR</text>
            <text x="310" y="315" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">IT</text>
            <text x="368" y="238" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(255,255,255,0.18)" letter-spacing="1">AT</text>
            <text x="480" y="318" text-anchor="middle" font-family="'Outfit',sans-serif"
                  font-size="8" fill="rgba(0,229,255,0.35)" letter-spacing="1">RS</text>

        </svg>
    </div>
</section>
<?php endif; /* europe map zima only */ ?>

<!-- ============================================================
     6. TESTIMONIALS CAROUSEL  (uvek se prikazuje — generic)
     ============================================================ -->
<section class="testimonials-section" id="utisci">
    <div class="testimonials-header reveal">
        <span class="section-eyebrow">Putnici o nama</span>
        <h2 class="section-heading">Pravi <span>Utisci</span></h2>
    </div>

    <div class="reviews-carousel reveal" id="reviewsCarousel">

        <button class="carousel-arrow prev" type="button" data-dir="-1" aria-label="Prethodni">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="10,3 5,8 10,13"/>
            </svg>
        </button>

        <div class="reviews-track" id="reviewsTrack">
            <?php foreach ($recenzije as $i => $rev): ?>
            <div class="review-slide">
                <div class="review-card-main<?php echo $i === 0 ? ' active-slide' : ''; ?>">
                    <div class="review-stars">
                        <?php for ($s = 0; $s < (int)$rev['ocena']; $s++): ?>
                            <span class="star">★</span>
                        <?php endfor; ?>
                    </div>
                    <p class="review-text-main">
                        "<?php echo htmlspecialchars($rev['tekst']); ?>"
                    </p>
                    <div class="review-author">
                        <div class="review-avatar-main"><?php echo htmlspecialchars($rev['avatar']); ?></div>
                        <div>
                            <div class="review-meta-name"><?php echo htmlspecialchars($rev['ime']); ?></div>
                            <div class="review-meta-dest"><?php echo htmlspecialchars($rev['dest']); ?></div>
                            <div class="review-meta-date"><?php echo htmlspecialchars($rev['datum']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button class="carousel-arrow next" type="button" data-dir="1" aria-label="Sledeći">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6,3 11,8 6,13"/>
            </svg>
        </button>

        <div class="carousel-nav" id="carouselNav">
            <?php foreach ($recenzije as $i => $rev): ?>
                <button class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>"
                        type="button" data-index="<?php echo (int)$i; ?>"
                        aria-label="Recenzija <?php echo (int)$i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     KATALOG GRID
     ============================================================ -->
<section class="catalog-section" id="katalog">

    <div class="catalog-header-new reveal">
        <span class="section-eyebrow"><?php echo htmlspecialchars($c['catalog_eyebrow']); ?></span>
        <h2 class="section-heading">
            <?php echo htmlspecialchars($c['catalog_title_1']); ?>
            <span><?php echo htmlspecialchars($c['catalog_title_2']); ?></span>
        </h2>
        <p class="catalog-intro">
            <?php echo htmlspecialchars($c['catalog_intro']); ?>
        </p>
        <div class="section-divider"></div>
    </div>

    <div class="dest-grid">
        <?php foreach ($destinacije as $d): ?>
        <div class="dest-card reveal">
            <div class="dest-img-container">
                <img src="https://images.unsplash.com/photo-1549294413-26f195200c16?q=80&w=800&auto=format&fit=crop"
                     class="dest-img"
                     alt="<?php echo htmlspecialchars($d['naziv']); ?>"
                     width="800" height="500"
                     loading="lazy" decoding="async">
            </div>
            <div class="dest-body">
                <h2 class="dest-title"><?php echo htmlspecialchars($d['naziv']); ?></h2>
                <p class="dest-desc">
                    <?php
                        $opis = htmlspecialchars($d['opis']);
                        echo (strlen($opis) > 120) ? substr($opis, 0, 115) . '...' : $opis;
                    ?>
                </p>
                <div class="dest-meta">
                    <?php if ($current_season === 'zima'): ?>
                        <div class="meta-item">
                            <span>Ukupno staza</span>
                            <strong><?php echo (int)($d['ukupno_staza_km'] ?? 0); ?> km</strong>
                        </div>
                        <div class="meta-item">
                            <span>Broj žičara</span>
                            <strong><?php echo (int)($d['broj_zicara'] ?? 0); ?></strong>
                        </div>
                        <div class="meta-item">
                            <span>Udaljenost</span>
                            <strong><?php echo (int)($d['distanca_od_bg_km'] ?? 0); ?> km</strong>
                        </div>
                    <?php else: /* LETO — drugaciji set podataka */ ?>
                        <div class="meta-item">
                            <span>Zemlja</span>
                            <strong><?php echo htmlspecialchars($d['zemlja'] ?? '—'); ?></strong>
                        </div>
                        <div class="meta-item">
                            <span>Region</span>
                            <strong><?php echo htmlspecialchars($d['region'] ?? '—'); ?></strong>
                        </div>
                        <div class="meta-item">
                            <span>Sezona</span>
                            <strong>Maj — Oktobar</strong>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="destinacija.php?id=<?php echo (int)$d['id']; ?>&amp;sezona=<?php echo htmlspecialchars($current_season); ?>" class="btn-view">
                    Pogledaj Detaljnije
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</section>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
/* ================================================================
   NAV — scroll efekat
   ================================================================ */
const mainNav = document.getElementById('main-nav');
window.addEventListener('scroll', () => {
    mainNav.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

/* ================================================================
   REVEAL ANIMACIJA (IntersectionObserver)
   ================================================================ */
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });

document.querySelectorAll('.reveal').forEach((el) => {
    /* Stepenasti delay za kartice u katalogu */
    const grid = el.closest('.dest-grid');
    if (grid) {
        const idx = Array.from(grid.children).indexOf(el);
        el.style.transitionDelay = (idx * 0.08) + 's';
    }
    revealObs.observe(el);
});

/* ================================================================
   ROUTE FINDER — postoji samo u ZIMA modu (guard ako elementi fale)
   ================================================================ */
const rfDest   = document.getElementById('rf-dest');
const rfOsobe  = document.getElementById('rf-osobe');
const rfDani   = document.getElementById('rf-dani');
const rfSubmit = document.getElementById('rf-submit');

if (rfDest && rfSubmit) {
    function routeFinderGo() {
        if (!rfDest.value) {
            rfDest.classList.add('is-error');
            setTimeout(() => rfDest.classList.remove('is-error'), 1800);
            return;
        }
        const params = new URLSearchParams({
            id:    rfDest.value,
            osobe: rfOsobe.value || 2,
            dani:  rfDani.value  || 7,
        });
        window.location.href = `destinacija.php?${params.toString()}#logistika`;
    }
    rfSubmit.addEventListener('click', routeFinderGo);
    [rfDest, rfOsobe, rfDani].forEach(el => {
        el.addEventListener('keydown', e => { if (e.key === 'Enter') routeFinderGo(); });
    });
}

/* ================================================================
   EUROPE MAP — tooltip (samo ZIMA — guard ako mapa ne postoji)
   ================================================================ */
const tooltip      = document.getElementById('mapTooltip');
const mapContainer = document.getElementById('europeMapContainer');

if (tooltip && mapContainer) {
    document.querySelectorAll('.map-pin').forEach(pin => {
        pin.style.cursor = 'pointer';

        pin.addEventListener('mouseenter', function() {
            const { dest, country, km, ski } = this.dataset;
            tooltip.innerHTML = `
                <div class="tt-dest">${dest}</div>
                <div class="tt-country">${country}</div>
                <div class="tt-km"><strong>${km} km</strong> od Beograda</div>
                <div class="tt-ski">${ski}</div>
            `;
            const containerRect = mapContainer.getBoundingClientRect();
            const pinRect       = this.getBoundingClientRect();
            const pinCenterX    = pinRect.left + pinRect.width / 2 - containerRect.left;
            const pinTopY       = pinRect.top  - containerRect.top;
            tooltip.style.left = pinCenterX + 'px';
            tooltip.style.top  = (pinTopY - tooltip.offsetHeight - 18) + 'px';
            tooltip.classList.add('visible');
        });

        pin.addEventListener('mouseleave', () => tooltip.classList.remove('visible'));
        pin.addEventListener('click', () => {
            document.getElementById('katalog').scrollIntoView({ behavior: 'smooth' });
        });
    });
}

/* ================================================================
   TESTIMONIALS CAROUSEL
   ================================================================ */
const carouselTotal = <?php echo (int)count($recenzije); ?>;
const carouselTrack = document.getElementById('reviewsTrack');
const carousel      = document.getElementById('reviewsCarousel');

if (carouselTotal > 0 && carousel) {
    let carouselCurrent = 0;
    let carouselTimer;

    function carouselGoTo(index) {
        carouselCurrent = (index + carouselTotal) % carouselTotal;
        carouselTrack.style.transform = `translateX(-${carouselCurrent * 100}%)`;

        document.querySelectorAll('.review-card-main').forEach((s, i) => {
            s.classList.toggle('active-slide', i === carouselCurrent);
        });
        document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === carouselCurrent);
        });
    }

    function carouselMove(dir) { carouselGoTo(carouselCurrent + dir); }

    /* Strelice + tackice (event delegation umesto inline onclick) */
    document.querySelectorAll('.carousel-arrow').forEach(btn => {
        btn.addEventListener('click', () => carouselMove(parseInt(btn.dataset.dir, 10)));
    });
    document.querySelectorAll('.carousel-dot').forEach(dot => {
        dot.addEventListener('click', () => carouselGoTo(parseInt(dot.dataset.index, 10)));
    });

    /* Auto-rotate */
    function startCarouselTimer() {
        carouselTimer = setInterval(() => carouselMove(1), 5000);
    }
    startCarouselTimer();

    carousel.addEventListener('mouseenter', () => clearInterval(carouselTimer));
    carousel.addEventListener('mouseleave', startCarouselTimer);

    /* Touch / swipe */
    let touchStartX = 0;
    carousel.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].clientX;
    }, { passive: true });
    carousel.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) carouselMove(diff > 0 ? 1 : -1);
    });
}
</script>

<?php include 'partials/footer.php'; ?>
