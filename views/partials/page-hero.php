<?php
/**
 * Shared page hero — full-width image + dark overlay + white text.
 * Matches the home page hero style.
 *
 * Set before requiring this file:
 *   $heroTitle     string  Main heading (required)
 *   $heroSubtitle  string  Sub-heading (optional)
 *   $heroImage     string  Override image URL (optional, falls back to site setting)
 *   $heroMinHeight int     Min-height px (optional, default 200)
 *   $heroBefore    string  Raw HTML rendered above the h1 (e.g. category badge)
 *   $heroAfter     string  Raw HTML rendered below subtitle (e.g. byline)
 */
use ASOC\Core\App;
$_img  = (!empty($heroImage)) ? $heroImage : App::settings()->get('site_hero_image', '');
$_minH = (int)($heroMinHeight ?? 200);
$_bgStyle = $_img
    ? 'background-image:url(' . htmlspecialchars($_img) . ');background-size:cover;background-position:center;'
    : 'background:var(--dark);';
?>
<section class="site-page-hero" style="min-height:<?= $_minH ?>px;<?= $_bgStyle ?>">
    <div class="site-page-hero__overlay"></div>
    <div class="container site-page-hero__inner">
        <?php if (!empty($heroBefore)) echo $heroBefore; ?>
        <h1><?= htmlspecialchars($heroTitle ?? '') ?></h1>
        <?php if (!empty($heroSubtitle)): ?>
        <p><?= htmlspecialchars($heroSubtitle) ?></p>
        <?php endif; ?>
        <?php if (!empty($heroAfter)) echo $heroAfter; ?>
    </div>
</section>
<?php
// Reset so these don't bleed into the next require
unset($heroTitle, $heroSubtitle, $heroImage, $heroMinHeight, $heroBefore, $heroAfter, $_img, $_minH, $_bgStyle);
?>
<style>
.site-page-hero{position:relative;display:flex;align-items:center;overflow:hidden;}
.site-page-hero__overlay{position:absolute;inset:0;background:rgba(15,23,42,.55);}
.site-page-hero__inner{position:relative;z-index:1;text-align:center;color:white;padding:2.5rem 1.5rem;width:100%;}
.site-page-hero__inner h1{font-size:clamp(1.75rem,4vw,3rem);font-weight:800;letter-spacing:-.02em;margin-bottom:.5rem;text-shadow:0 2px 12px rgba(0,0,0,.4);}
.site-page-hero__inner p{font-size:1.1rem;color:rgba(255,255,255,.8);font-weight:500;text-shadow:0 1px 6px rgba(0,0,0,.3);margin:0;}
</style>
