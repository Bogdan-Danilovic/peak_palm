<?php
/**
 * partials/nav.php
 * Minimalisticka navigacija — Zima | Logo | Leto.
 *
 * Klik na "Zima" ili "Leto" vodi na index.php sa odgovarajucim ?sezona=
 * parametrom — to je signal i frontu (CSS varijable) i bazi (filter destinacija).
 */
$current_season = $current_season ?? (function_exists('get_season') ? get_season() : 'zima');
?>
<nav id="main-nav" class="<?php echo htmlspecialchars($current_season); ?>">

    <a href="index.php?sezona=zima"
       class="season-link peak<?php echo $current_season === 'zima' ? ' active' : ''; ?>">
        <span class="season-link-eyebrow">Zima</span>
        <span class="season-link-name">Peak</span>
    </a>

    <a href="index.php?sezona=<?php echo htmlspecialchars($current_season); ?>" class="logo">
        Peak<span>&amp;</span>Palm
    </a>

    <a href="index.php?sezona=leto"
       class="season-link palm<?php echo $current_season === 'leto' ? ' active' : ''; ?>">
        <span class="season-link-eyebrow">Leto</span>
        <span class="season-link-name">Palm</span>
    </a>

</nav>
