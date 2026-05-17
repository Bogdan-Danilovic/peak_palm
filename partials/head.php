<?php
/**
 * partials/head.php
 * Zajednicki <head> blok za sve stranice.
 *
 * Stranica pre `include` mora postaviti:
 *   $page_title       — naslov stranice
 *   $current_season   — 'zima' ili 'leto' (uobicajeno: $current_season = get_season())
 */
$__season = $current_season ?? 'zima';
?>
<!DOCTYPE html>
<html lang="sr" data-season="<?php echo htmlspecialchars($__season); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#04060d">
    <title><?php echo htmlspecialchars($page_title ?? 'Peak & Palm'); ?></title>

    <!-- Preconnect ubrzava Google Fonts za 100-300ms -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,600&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style 3.0.css">

    <?php if (!empty($page_extra_head)) echo $page_extra_head; ?>
</head>
