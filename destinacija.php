<?php
/**
 * destinacija.php — UNIVERZALNI ŠABLON ZA DESTINACIJU
 * --------------------------------------------------------------
 * Maturski rad: Peak & Palm
 *
 * Filozofija:
 *   • Sve sadržaje povlači iz baze po ID-u iz URL-a: ?id=1
 *   • Nijedna sekcija nije hardkodovana — promena = INSERT u phpMyAdmin
 *   • SVG staze: koordinate u `staze_putanje.svg_d_putanja`,
 *     CSS klasa u `tip_klasa` koja se direktno lepi na <path> element.
 *     CSS sam "prepoznaje" klasu i daje boju + hover sjaj.
 *   • Šablon radi i za skijanje i za letovanje — samo se menjaju podaci.
 */

require_once 'db.php';

/* ============================================================
   POMOĆNA FUNKCIJA — vraća inline SVG ikonu za tip transporta.
   Ako kasnije dodaš nov tip (npr. 'brod'), samo ovde dodaj case.
   ============================================================ */
function svg_ikona_transport(string $tip): string {
    switch ($tip) {
        case 'bus':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                        <rect x="4" y="4" width="16" height="13" rx="2"/>
                        <line x1="4" y1="11" x2="20" y2="11"/>
                        <circle cx="8" cy="19" r="1.4"/>
                        <circle cx="16" cy="19" r="1.4"/>
                        <line x1="8" y1="7" x2="8" y2="9"/>
                        <line x1="16" y1="7" x2="16" y2="9"/>
                    </svg>';
        case 'avion':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                        <path d="M3 13l3 1.5L11 9l8.5-3.5a1.5 1.5 0 0 1 2 2L18 16l-5.5 5-1.5-3-3-1.5L3 13z"/>
                    </svg>';
        case 'auto':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                        <path d="M4 14l1.5-5a2 2 0 0 1 2-1.5h9a2 2 0 0 1 2 1.5L20 14"/>
                        <rect x="3" y="14" width="18" height="5" rx="1.5"/>
                        <circle cx="7.5" cy="19" r="1.4"/>
                        <circle cx="16.5" cy="19" r="1.4"/>
                    </svg>';
        default:
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" width="28" height="28">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="12" y1="8" x2="12" y2="13"/>
                        <circle cx="12" cy="16" r="0.6" fill="currentColor"/>
                    </svg>';
    }
}

/* ============================================================
   1. ID iz URL-a + osnovni podaci (sa LEFT JOIN na granicni prelaz)
   ============================================================ */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$stmt = $pdo->prepare("
    SELECT  d.*,
            gp.naziv     AS prelaz_naziv,
            gp.u_drzavu  AS prelaz_u_drzavu
    FROM    destinacije d
    LEFT JOIN granicni_prelazi gp ON gp.id = d.granicni_prelaz_id
    WHERE   d.id = ?
");
$stmt->execute([$id]);
$dest = $stmt->fetch();

if (!$dest) {
    http_response_code(404);
    die("Destinacija nije pronađena.");
}

/* ============================================================
   2. POMOĆNA FUNKCIJA — fetch svih redova po destinaciji
   ============================================================ */
function fetchByDest(PDO $pdo, string $sql, int $id): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

/* ============================================================
   3. SLIKE — grupisanje po tipu
   ============================================================ */
$sve_slike = fetchByDest($pdo,
    "SELECT tip, url, alt FROM destinacije_slike
     WHERE destinacija_id = ? ORDER BY tip, redosled, id",
    $id
);
$slike = ['hero' => [], 'mapa_staza' => [], 'gallery' => []];
foreach ($sve_slike as $s) {
    $slike[$s['tip']][] = $s;
}
$mapa_staza_url = $slike['mapa_staza'][0]['url'] ?? null;

/* ============================================================
   4. STAZE PUTANJE — SVG element + lista za hover
   ============================================================ */
$staze = fetchByDest($pdo,
    "SELECT tip_klasa, naziv, svg_d_putanja, duzina_km FROM staze_putanje
     WHERE destinacija_id = ? ORDER BY redosled, id",
    $id
);

/* Grupisanje po tip_klasi za listu — zbira duzine svih staza istog tipa. */
$staze_po_tipu = [];
foreach ($staze as $s) {
    $tip = $s['tip_klasa'];
    if (!isset($staze_po_tipu[$tip])) {
        $staze_po_tipu[$tip] = [
            'naziv_grupe' => 'Staze',
            'duzina'      => 0.0,
        ];
        /* Prvi naziv kao reprezentativan za celu grupu: "Plave staze", "Crvene staze"... */
        switch ($tip) {
            case 'plava':  $staze_po_tipu[$tip]['naziv_grupe'] = 'Plave staze';  break;
            case 'crvena': $staze_po_tipu[$tip]['naziv_grupe'] = 'Crvene staze'; break;
            case 'crna':   $staze_po_tipu[$tip]['naziv_grupe'] = 'Crne staze';   break;
            default:       $staze_po_tipu[$tip]['naziv_grupe'] = ucfirst($tip) . ' staze';
        }
    }
    $staze_po_tipu[$tip]['duzina'] += (float)$s['duzina_km'];
}

/* ============================================================
   5. TRANSPORT OPCIJE — JSON parsiranje
   ============================================================ */
$transport = fetchByDest($pdo,
    "SELECT * FROM transport_opcije
     WHERE destinacija_id = ? ORDER BY redosled, id",
    $id
);
foreach ($transport as &$t) {
    /* stavke_json = [{label, vrednost}, ...] */
    $t['stavke'] = $t['stavke_json']
        ? (json_decode($t['stavke_json'], true) ?: [])
        : [];
}
unset($t);

/* ============================================================
   6. OPREMA PAKETI
   ============================================================ */
$oprema = fetchByDest($pdo,
    "SELECT * FROM oprema_paketi
     WHERE destinacija_id = ? ORDER BY redosled, id",
    $id
);
foreach ($oprema as &$o) {
    $o['includes'] = $o['includes_json']
        ? (json_decode($o['includes_json'], true) ?: [])
        : [];
}
unset($o);

/* ============================================================
   7. ŠKOLA PAKETI
   ============================================================ */
$skola = fetchByDest($pdo,
    "SELECT * FROM skola_paketi
     WHERE destinacija_id = ? ORDER BY redosled, id",
    $id
);

/* ============================================================
   8. PARAMETRI ZA partials/head.php i partials/nav.php
   ============================================================
   Sezona ide prvo iz baze (sama destinacija zna kojoj sezoni pripada),
   pa preko URL-a, pa default. Tako da otvaranje letnje destinacije
   automatski daje narandzastu temu, bez obzira kako je linkovan. */
$current_season = !empty($dest['sezona'])
    ? $dest['sezona']
    : get_season();

$page_title = htmlspecialchars($dest['naziv']) . ' | Peak and Palm';

include 'partials/head.php';
?>
<body>

<div class="fixed-bg"></div>

<?php include 'partials/nav.php'; ?>

<div class="main-content">

    <!-- ============================================================
         HERO  —  slika mape + dinamicki SVG path-ovi
         ============================================================ -->
    <section class="hero-section" id="hero">

        <div class="prava-mapa-container" id="mapa">
            <svg viewBox="0 0 640 346" width="100%" height="auto" xmlns="http://www.w3.org/2000/svg">

                <?php if ($mapa_staza_url): ?>
                    <image href="<?php echo htmlspecialchars($mapa_staza_url); ?>"
                           width="640" height="346" />
                <?php endif; ?>

                <g>
                    <?php /* Srce sablona: CSS klasa dolazi direktno iz baze. */ ?>
                    <?php foreach ($staze as $s): ?>
                        <path class="staza <?php echo htmlspecialchars($s['tip_klasa']); ?>"
                              d="<?php echo htmlspecialchars($s['svg_d_putanja']); ?>" />
                    <?php endforeach; ?>
                </g>

            </svg>
        </div>

        <div class="info-side">
            <span class="hero-label">
                <?php
                $bredkramb = array_filter([$dest['zemlja'] ?? null, $dest['region'] ?? null]);
                echo $bredkramb
                    ? htmlspecialchars(implode(' · ', $bredkramb))
                    : 'Destinacija';
                ?>
            </span>
            <h1 class="hero-title"><?php echo htmlspecialchars($dest['naziv']); ?></h1>
            <p class="hero-desc"><?php echo htmlspecialchars($dest['opis'] ?? ''); ?></p>

            <div class="interactive-list">
                <?php foreach ($staze_po_tipu as $tip => $info): ?>
                <div class="slope-item" data-tip="<?php echo htmlspecialchars($tip); ?>">
                    <div>
                        <span class="slope-name"><?php echo htmlspecialchars($info['naziv_grupe']); ?></span>
                    </div>
                    <strong class="slope-km <?php echo htmlspecialchars($tip); ?>">
                        <?php echo number_format($info['duzina'], 1, ',', ''); ?> km
                    </strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </section>

    <!-- ============================================================
         GLAVNI SCROLL SADRZAJ
         ============================================================ -->
    <section class="scroll-content">
        <div class="container-wide">

            <!-- TRANSPORT OPCIJE -->
            <?php if (!empty($transport)): ?>
            <div class="reveal section-block" id="prevoz">
                <span class="section-eyebrow">Iz Beograda</span>
                <h2 class="section-title">Opcije <span>Prevoza</span></h2>
                <div class="section-divider"></div>

                <div class="transport-grid">
                    <?php foreach ($transport as $t): ?>
                    <div class="transport-card <?php echo htmlspecialchars($t['tip']); ?> reveal">
                        <div class="transport-accent"></div>
                        <div class="transport-body">
                            <div class="transport-icon-wrap"><?php echo svg_ikona_transport($t['tip']); ?></div>
                            <h3 class="transport-title"><?php echo htmlspecialchars($t['naziv']); ?></h3>

                            <?php if (!empty($t['podnaslov'])): ?>
                                <p class="transport-subtitle"><?php echo htmlspecialchars($t['podnaslov']); ?></p>
                            <?php endif; ?>

                            <ul class="transport-info-list">
                                <?php foreach ($t['stavke'] as $stavka): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($stavka['label'] ?? ''); ?></span>
                                    <strong><?php echo htmlspecialchars($stavka['vrednost'] ?? ''); ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- OPREMA + SKOLA  (sa istom section-eyebrow) -->
            <?php if (!empty($oprema) || !empty($skola)): ?>
            <div class="reveal section-block" id="paketi">
                <span class="section-eyebrow">Agencijski paketi</span>
                <h2 class="section-title">Oprema <span>&amp; Škola</span></h2>
                <div class="section-divider"></div>

                <div class="equipment-section-grid">

                    <?php if (!empty($oprema)): ?>
                    <div>
                        <p class="equipment-intro">
                            Kompletna oprema direktno kroz agenciju — bez čekanja u redu na destinaciji.
                        </p>
                        <div class="equipment-cards">
                            <?php foreach ($oprema as $o): ?>
                            <div class="equipment-card reveal">
                                <h3 class="equipment-name"><?php echo htmlspecialchars($o['naziv']); ?></h3>

                                <?php if (!empty($o['opis'])): ?>
                                    <p class="equipment-desc"><?php echo htmlspecialchars($o['opis']); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($o['includes'])): ?>
                                <ul class="equipment-includes">
                                    <?php foreach ($o['includes'] as $line): ?>
                                        <li><?php echo htmlspecialchars($line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>

                                <div class="equipment-price-row">
                                    <div>
                                        <div class="equipment-price">€<?php echo (int)$o['cena_eur']; ?></div>
                                        <div class="equipment-period">po danu / po osobi</div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($skola)): ?>
                    <div class="ski-school-panel reveal">
                        <span class="section-eyebrow eyebrow-tight">Škola</span>
                        <h3 class="ski-school-title">Čas sa <span>instruktorom</span></h3>
                        <p class="ski-school-intro">
                            Licencirani instruktori. Rezervacija minimum 48h unapred.
                        </p>

                        <div class="school-packages">
                            <?php foreach ($skola as $sk): ?>
                            <div class="school-package">
                                <div>
                                    <div class="school-package-name"><?php echo htmlspecialchars($sk['naziv']); ?></div>
                                    <?php if (!empty($sk['opis'])): ?>
                                        <div class="school-package-desc"><?php echo htmlspecialchars($sk['opis']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="school-package-price">
                                    <strong>€<?php echo (int)$sk['cena_eur']; ?></strong>
                                    <span>/ <?php echo htmlspecialchars($sk['jedinica']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

            <!-- GALERIJA SLIKA -->
            <?php if (!empty($slike['gallery'])): ?>
            <div class="reveal section-block" id="galerija">
                <span class="section-eyebrow">Atmosfera</span>
                <h2 class="section-title">Galerija <span>Slika</span></h2>
                <div class="section-divider"></div>

                <div class="dest-grid">
                    <?php foreach ($slike['gallery'] as $g): ?>
                    <div class="dest-card reveal">
                        <div class="dest-img-container">
                            <img src="<?php echo htmlspecialchars($g['url']); ?>"
                                 alt="<?php echo htmlspecialchars($g['alt'] ?? ''); ?>"
                                 class="dest-img"
                                 loading="lazy" decoding="async">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /container-wide -->
    </section><!-- /scroll-content -->

</div><!-- /main-content -->

<script>
/* =================================================================
   SRCE SABLONA NA FRONTU:
   Hover na ime staze u listi → svi SVG path-ovi sa istom CSS klasom
   dobijaju .active stanje (sjaj + jaca linija).
   Ako sutra dodas novi tip_klasa 'freeride', NULA dodatnog JS koda
   — samo CSS pravilo i podaci u bazi.
   ================================================================= */
document.querySelectorAll('.slope-item').forEach(item => {
    const klasa = item.dataset.tip;
    const paths = document.querySelectorAll('.staza.' + klasa);
    item.addEventListener('mouseenter', () => paths.forEach(p => p.classList.add('active')));
    item.addEventListener('mouseleave', () => paths.forEach(p => p.classList.remove('active')));
});

/* Reveal-on-scroll (IntersectionObserver — daleko efikasniji od scroll listener-a) */
const revealObs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -80px 0px' });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

/* Nav scrolled + hero fade na scroll (throttled preko rAF) */
const hero = document.getElementById('hero');
const nav  = document.getElementById('main-nav');
let scrollTicking = false;
window.addEventListener('scroll', () => {
    if (scrollTicking) return;
    scrollTicking = true;
    requestAnimationFrame(() => {
        const y = window.scrollY;
        nav?.classList.toggle('scrolled',  y > 40);
        hero?.classList.toggle('scrolled', y > 80);
        scrollTicking = false;
    });
}, { passive: true });
</script>

<?php include 'partials/footer.php'; ?>
