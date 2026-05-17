<?php
/**
 * partials/nav.php
 * Minimalisticka navigacija: Peak | Peak&Palm | Palm
 * Klik na Peak/Palm vodi na index.php sa odgovarajucim ?sezona= parametrom.
 */
$current_season = $current_season ?? (function_exists('get_season') ? get_season() : 'zima');
?>
<nav id="main-nav">
    <a href="index.php?sezona=zima"
       class="season-link peak<?php echo $current_season === 'zima' ? ' active' : ''; ?>">
        Peak
    </a>

    <a href="index.php?sezona=<?php echo htmlspecialchars($current_season); ?>" class="logo">
        Peak<span>&amp;</span>Palm
    </a>

    <a href="index.php?sezona=leto"
       class="season-link palm<?php echo $current_season === 'leto' ? ' active' : ''; ?>">
        Palm
    </a>
</nav>
