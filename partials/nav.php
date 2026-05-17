<?php
/**
 * partials/nav.php
 * Zajednicka navigacija + Zima/Leto toggle.
 *
 * Toggle koristi CSS varijable: kad se promeni `data-season` na <html>,
 * CSS automatski "prebacuje" celu temu (logo &, hover boje, glow itd.).
 *
 * Stranica pre `include` mora postaviti niz $nav_links.
 */
$nav_links = $nav_links ?? [];
?>
<nav id="main-nav">
    <a href="index.php" class="logo">Peak<span>&amp;</span>Palm</a>

    <div class="nav-links">
        <?php foreach ($nav_links as $link): ?>
            <a href="<?php echo htmlspecialchars($link['href']); ?>"<?php echo !empty($link['active']) ? ' class="active"' : ''; ?>>
                <?php echo htmlspecialchars($link['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="season-toggle" role="group" aria-label="Promena sezone">
        <button type="button" class="season-btn" data-season="winter">Zima</button>
        <button type="button" class="season-btn" data-season="summer">Leto</button>
    </div>
</nav>

<script>
/* Inicijalizacija toggle dugmadi + persistencija u localStorage.
   data-season je vec postavljen u <head> preboot skripti — ovde samo
   sinhronizujemo aktivno dugme i prikacujemo handler. */
(function(){
    var root = document.documentElement;
    var KEY  = 'peak_palm_season';
    var current = root.getAttribute('data-season') || 'winter';

    document.querySelectorAll('.season-btn').forEach(function(btn){
        if (btn.dataset.season === current) btn.classList.add('active');

        btn.addEventListener('click', function(){
            var s = btn.dataset.season;
            root.setAttribute('data-season', s);
            try { localStorage.setItem(KEY, s); } catch(e) {}
            document.querySelectorAll('.season-btn').forEach(function(b){
                b.classList.toggle('active', b.dataset.season === s);
            });
        });
    });
})();
</script>
