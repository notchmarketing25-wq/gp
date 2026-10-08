<?php
if (!function_exists("_s")) {
    function _s(string $key, string $default = ""): string {
        try { return SettingModel::get($key, $default); }
        catch(\Throwable $e) { return $default; }
    }
}
if (!function_exists('_hexRgbStore')) {
    function _hexRgbStore(string $hex): string {
        $hex = ltrim($hex,'#');
        if(strlen($hex)===3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        return hexdec(substr($hex,0,2)).','.hexdec(substr($hex,2,2)).','.hexdec(substr($hex,4,2));
    }
}
if (!isset($collections)) {
    try { $collections = (new CollectionModel())->getActive(); }
    catch(\Throwable $e) { $collections = []; }
}
$_c    = _s('primary_color','#0057FF') ?: '#0057FF';
$_rgb  = _hexRgbStore($_c);
$_logo = _s('store_logo');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<script>
// Must run synchronously, before the browser paints anything — this is what actually prevents
// the light-theme flash on page navigation while in dark mode. Moving this to the bottom of the
// page (where it lived before) meant the browser painted the default light theme first and only
// corrected it after the whole page had loaded, causing a visible white flash every navigation.
(function(){
  try {
    var saved = localStorage.getItem('nt_theme') || 'light';
    document.documentElement.dataset.theme = saved;
  } catch(e) { document.documentElement.dataset.theme = 'light'; }
})();
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? _s('meta_title', APP_NAME)) ?></title>
<meta name="description" content="<?= e($pageDesc ?? _s('meta_description', '')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Barlow:wght@400;500;600;700&family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<?php if ($fav = _s('store_favicon')): ?><link rel="icon" href="<?= uploadUrl($fav) ?>"><?php endif; ?>
<?php
$fbPixel = _s('facebook_pixel'); $gaId = _s('google_analytics');
if ($fbPixel): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($fbPixel) ?>');fbq('track','PageView');</script>
<?php endif; if ($gaId): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($gaId) ?>');</script>
<?php endif; ?>
<style>
/* ══════════════════════════════════════════════════════
   NOTCH — Bold Tech Brand System
   White/Black/Blue — Barlow Condensed + Cairo
   ══════════════════════════════════════════════════════ */
:root{
  --accent:     <?= $_c ?>;
  --accent-rgb: <?= $_rgb ?>;
  --accent-dark:color-mix(in srgb, <?= $_c ?> 82%, black);
  --accent-bg:  rgba(<?= $_rgb ?>,.1);
  --black:      #0A0A0A;
  --green:#16a34a; --red:#e5342a; --yellow:#f59e0b;
  --radius:16px; --radius-sm:10px;
  --nav-h:68px;
  --font-en:'Barlow Condensed',sans-serif;
  --font-body:'Cairo',sans-serif;
  --transition:.2s cubic-bezier(.4,0,.2,1);
}
[data-theme="light"]{
  --bg:#ffffff; --bg2:#F8F8F8; --bg3:#F0F0F0;
  --surface:#ffffff; --surface2:#F8F8F8;
  --border:#E5E5E5; --border2:#D0D0D0;
  --text:#0A0A0A; --text2:#5C5C5C; --text3:#9E9E9E;
  --nav-bg:rgba(255,255,255,.85);
}
[data-theme="dark"]{
  --bg:#0A0A0A; --bg2:#111114; --bg3:#18181c;
  --surface:#141417; --surface2:#1b1b20;
  --border:#26262c; --border2:#333339;
  --text:#f5f5f7; --text2:#a0a0ab; --text3:#5c5c68;
  --nav-bg:rgba(10,10,10,.8);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font-body);background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden;transition:background .25s,color .25s}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}
button{font-family:inherit;cursor:pointer;border:none}
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

/* ── ANNOUNCEMENT BAR ── */
.ann-bar{background:var(--accent);color:#fff;text-align:center;padding:9px 20px;font-size:12.5px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:28px;flex-wrap:wrap}
.ann-bar span{display:flex;align-items:center;gap:6px}
.ann-sep{opacity:.4}
@media(max-width:768px){.ann-bar span:nth-child(3),.ann-sep:nth-child(4){display:none}}

/* ── NAV ── */
.nav{background:var(--nav-bg);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
.nav-inner{max-width:1360px;margin:0 auto;display:flex;align-items:center;gap:6px;padding:0 28px;height:var(--nav-h)}
.nav-logo{flex-shrink:0;display:flex;align-items:center;gap:9px;margin-left:38px;font-family:var(--font-en);font-weight:800;font-size:19px;letter-spacing:.02em}
.logo-dark-mode{display:none}
[data-theme="dark"] .logo-light-mode{display:none}
[data-theme="dark"] .logo-dark-mode{display:block}
.tt-sun{display:none}.tt-moon{display:flex}
[data-theme="dark"] .tt-sun{display:flex}
[data-theme="dark"] .tt-moon{display:none}
.nav-logo img{height:30px;width:auto;object-fit:contain}
.nav-logo-mark{width:32px;height:32px;background:linear-gradient(135deg,var(--accent),var(--accent-dark));border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px}
.nav-links{display:flex;align-items:center;gap:2px;flex:1}
.nav-link{padding:0 15px;height:var(--nav-h);display:flex;align-items:center;font-size:14px;font-weight:600;color:var(--text2);letter-spacing:.01em;transition:color .15s;border-bottom:2px solid transparent;white-space:nowrap;position:relative}
.nav-link:hover,.nav-link.active{color:var(--text);border-bottom-color:var(--accent)}
.nav-drop{position:relative}
.nav-drop-menu{position:absolute;top:100%;right:0;margin-top:0;background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:0 16px 40px rgba(0,0,0,.18);padding:8px;min-width:220px;opacity:0;visibility:hidden;transform:translateY(6px);transition:all .18s ease;z-index:50}
.nav-drop:hover .nav-drop-menu{opacity:1;visibility:visible;transform:translateY(0)}
.nav-drop-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--text2);transition:background .1s}
.nav-drop-item:hover{background:var(--surface2);color:var(--text)}
.nav-actions{display:flex;align-items:center;gap:8px;margin-right:auto}
.nav-divider{width:1px;height:22px;background:var(--border);margin:0 2px;flex-shrink:0}
.nav-utils{display:flex;align-items:center;gap:6px}
.nav-search{display:flex;align-items:center;gap:8px;background:var(--bg3);border-radius:9px;padding:8px 12px;width:180px;transition:width var(--transition);cursor:text}
.nav-search:focus-within{width:230px}
.nav-search input{background:none;border:none;outline:none;font-size:13px;color:var(--text);width:100%;font-family:inherit}
.nav-search input::placeholder{color:var(--text3)}
.nav-btn{width:38px;height:38px;border-radius:9px;background:var(--bg3);display:flex;align-items:center;justify-content:center;color:var(--text2);font-size:16px;position:relative;transition:all var(--transition);flex-shrink:0}
.nav-btn:hover{background:var(--accent);color:#fff}
.nav-badge{position:absolute;top:-4px;left:-4px;width:17px;height:17px;background:var(--accent);border-radius:50%;font-size:10px;font-weight:800;color:#fff;display:flex;align-items:center;justify-content:center}
@keyframes badgeBump{0%{transform:scale(1)}35%{transform:scale(1.5)}60%{transform:scale(.85)}100%{transform:scale(1)}}
.nav-badge.bump{animation:badgeBump .4s ease}
@keyframes cartIconBump{0%,100%{transform:scale(1)}30%{transform:scale(1.25) rotate(-8deg)}55%{transform:scale(.95) rotate(4deg)}}
.nav-btn.bump{animation:cartIconBump .45s ease}
@keyframes btnSuccessPop{0%{transform:scale(1)}40%{transform:scale(1.06)}100%{transform:scale(1)}}
.btn.added{animation:btnSuccessPop .3s ease}
.nav-cta{background:var(--black);color:#fff;border-radius:9px;padding:9px 18px;font-size:13px;font-weight:700;letter-spacing:.02em;transition:background .15s;white-space:nowrap}
.nav-business-btn{display:flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--accent),color-mix(in srgb,var(--accent) 70%,#7B2FBE));color:#fff;border-radius:10px;padding:7px 14px 7px 10px;transition:all .2s;box-shadow:0 2px 10px rgba(var(--accent-rgb),.35);flex-shrink:0}
.nav-business-btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(var(--accent-rgb),.45)}
.nav-business-icon{font-size:16px;line-height:1}
.nav-business-text{display:flex;flex-direction:column;align-items:flex-start;line-height:1.15}
.nav-business-en{font-family:var(--font-en);font-size:12.5px;font-weight:800;letter-spacing:.03em}
.nav-business-ar{font-size:9.5px;font-weight:600;opacity:.85}
@media(max-width:768px){.nav-business-btn{padding:6px 10px}.nav-business-text{display:none}}
.nav-warranty-btn{display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);color:var(--text);border-radius:10px;padding:7px 14px 7px 10px;transition:all .2s;flex-shrink:0}
.nav-warranty-btn:hover{border-color:var(--accent);color:var(--accent)}
.nav-warranty-btn .nav-business-icon{font-size:16px;line-height:1}
@media(max-width:768px){.nav-warranty-btn{padding:6px 10px}.nav-warranty-btn .nav-business-text{display:none}}
@media(max-width:600px){.nav-warranty-btn{display:none}.nav-divider{display:none}}
.nav-cta:hover{background:var(--accent)}
.theme-toggle{width:38px;height:38px;border-radius:9px;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:15px;transition:all .2s;flex-shrink:0}
.theme-toggle:hover{background:var(--accent);color:#fff}
.nav-hamburger{display:none;background:none;color:var(--text);font-size:22px}

/* Mobile menu */
.mobile-menu{display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.6);backdrop-filter:blur(6px)}
.mobile-menu.open{display:flex}
.mobile-menu-inner{background:var(--surface);width:290px;max-width:85vw;height:100%;padding:24px 16px;display:flex;flex-direction:column;gap:4px;animation:slideIn .2s ease;margin-right:auto;border-left:1px solid var(--border);overflow-x:hidden;overflow-y:auto}
@keyframes slideIn{from{transform:translateX(100%)}to{transform:none}}
.mobile-close{align-self:flex-end;background:var(--surface);color:var(--text2);font-size:22px;margin-bottom:16px;position:sticky;top:0;z-index:2}
.mobile-link{display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:9px;font-size:14px;font-weight:600;color:var(--text2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.mobile-link-icon{flex-shrink:0;display:inline-flex;width:18px;height:18px;align-items:center;justify-content:center;font-size:15px;line-height:1}
.mobile-link:hover{background:var(--bg3);color:var(--text)}

/* ── CONTAINER / SECTION ── */
.container{max-width:1360px;margin:0 auto;padding:0 28px}
.container-sm{max-width:900px;margin:0 auto;padding:0 24px}
.section{max-width:1360px;margin:0 auto;padding:64px 28px}
.section-sm{padding:40px 0}
.section-header{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:32px;gap:12px;flex-wrap:wrap}
.section-label{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px}
.section-title{font-family:var(--font-en);font-size:34px;font-weight:900;color:var(--text);letter-spacing:.01em;line-height:1.1}
.section-title span{color:var(--accent)}
.section-sub{font-size:14px;color:var(--text2);margin-top:8px}
.sec-link{font-size:13px;font-weight:700;color:var(--accent);display:flex;align-items:center;gap:4px;white-space:nowrap}

/* ── HERO (brand-black band, theme-independent) ── */
.hero{background:var(--black);color:#fff;min-height:520px;display:grid;grid-template-columns:1.15fr 1fr;overflow:hidden;position:relative}

/* ── HERO CAROUSEL (multi-slide, rotating) ── */
.hero-carousel{position:relative;background:var(--black);overflow:hidden;height:620px}
.hc-track{position:relative;height:100%}
.hc-slide{position:absolute;inset:0;opacity:0;visibility:hidden;transition:opacity .5s ease;color:#fff}
.hc-slide.active{opacity:1;visibility:visible;z-index:1}
/* Full-bleed background media — fills the ENTIRE slide, not just a side column */
.hc-slide-bg{position:absolute;inset:0;z-index:0}
.hc-slide-bg img,.hc-slide-bg video{width:100%;height:100%;object-fit:cover;object-position:center}
.hc-slide-shade{position:absolute;inset:0;z-index:1;background:linear-gradient(90deg,rgba(0,0,0,.78) 0%,rgba(0,0,0,.5) 38%,rgba(0,0,0,.15) 62%,transparent 100%);pointer-events:none}
[dir="ltr"] .hc-slide-shade,html:not([dir]) .hc-slide-shade{background:linear-gradient(270deg,rgba(0,0,0,.78) 0%,rgba(0,0,0,.5) 38%,rgba(0,0,0,.15) 62%,transparent 100%)}
.hc-arrow{position:absolute;top:50%;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;z-index:5}
.hc-arrow:hover{background:rgba(255,255,255,.2)}
.hc-arrow-prev{right:20px}
.hc-arrow-next{left:20px}
.hc-dots{position:absolute;bottom:22px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:5}
.hc-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.3);border:none;cursor:pointer;transition:all .25s;padding:0}
.hc-dot.active{background:var(--accent);width:22px;border-radius:5px}
@media(max-width:1100px){
  .hero-carousel{height:480px}
  .hc-slide-shade{background:linear-gradient(0deg,rgba(0,0,0,.82) 0%,rgba(0,0,0,.45) 45%,rgba(0,0,0,.15) 70%,transparent 100%)}
  .hero-left{padding:0 24px 34px;justify-content:flex-end;height:100%}
  .hero-subtitle{display:none}
  .hc-arrow{width:34px;height:34px}
}
@media(max-width:600px){
  .hero-carousel{height:440px}
}

/* ── MEDIUM / SMALL / MIDDLE AD BANNERS ── */
.mb-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.mb-item{position:relative;border-radius:var(--radius);overflow:hidden;aspect-ratio:16/9;display:block;background:var(--bg2);border:1px solid var(--border)}
.mb-item img,.mb-item video{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.mb-item:hover img,.mb-item:hover video{transform:scale(1.05)}
.mb-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,.68) 100%);display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-end;padding:22px;color:#fff}
.mb-overlay h3{font-size:18px;font-weight:800}
.mb-overlay p{font-size:12px;opacity:.85;margin-top:2px}
.mb-cta{margin-top:12px;display:inline-flex;align-items:center;gap:6px;border:1.5px solid rgba(255,255,255,.75);border-radius:100px;padding:7px 16px;font-size:11.5px;font-weight:700;color:#fff;transition:all .25s var(--ease-out, ease);backdrop-filter:blur(4px)}
.mb-item:hover .mb-cta{background:#fff;color:#0A0A0A;border-color:#fff}

.sb-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px}
.sb-item{position:relative;border-radius:var(--radius-sm);overflow:hidden;aspect-ratio:1;display:block;background:var(--bg2);border:1px solid var(--border)}
.sb-item img{width:100%;height:100%;object-fit:cover;transition:transform .35s ease}
.sb-item:hover img{transform:scale(1.06)}

.ad-banner{position:relative;display:block;border-radius:var(--radius);overflow:hidden;aspect-ratio:21/6;background:var(--bg2);border:1px solid var(--border)}
.ad-banner img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.ad-banner:hover img{transform:scale(1.03)}
.ad-banner-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,.72) 0%,rgba(0,0,0,.3) 55%,transparent 100%);display:flex;flex-direction:column;justify-content:center;padding:0 40px;color:#fff}
.ad-banner-overlay h3{font-size:24px;font-weight:900;font-family:var(--font-en)}
.ad-banner-overlay p{font-size:13px;opacity:.85;margin-top:4px;max-width:360px}
@media(max-width:768px){
  .ad-banner{aspect-ratio:16/10}
  .ad-banner-overlay{padding:0 20px}
  .ad-banner-overlay h3{font-size:18px}
  .mb-grid{grid-template-columns:1fr}
  .sb-grid{grid-template-columns:repeat(2,1fr)}
}
.hero-left{padding:64px 44px;display:flex;flex-direction:column;justify-content:center;position:relative;z-index:2;height:100%;max-width:640px}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(var(--accent-rgb),.15);border:1px solid rgba(var(--accent-rgb),.3);border-radius:100px;padding:6px 14px;font-size:11px;font-weight:700;color:#7aa6ff;letter-spacing:.06em;margin-bottom:22px;width:fit-content}
.hero-badge-dot{width:6px;height:6px;background:var(--accent);border-radius:50%;animation:blink 1.4s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
.hero-title{font-family:var(--font-body);font-size:clamp(30px,4.2vw,52px);font-weight:900;line-height:1.15;letter-spacing:-.01em;margin-bottom:14px;text-shadow:0 2px 20px rgba(0,0,0,.3)}
.hero-title .blue{color:var(--accent)}
.hero-subtitle{font-size:16px;font-weight:500;color:rgba(255,255,255,.7);line-height:1.7;margin-bottom:28px;max-width:440px}
.hero-ctas{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
/* Fallback hero (shown only when no slider banners are configured) keeps the original
   floating-product-cutout layout — scoped with .hero so it never affects the .hc-slide carousel. */
.hero .hero-right{display:flex;align-items:center;justify-content:center;padding:30px;position:relative}
.hero .hero-right::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 70% at 55% 50%,rgba(var(--accent-rgb),.15),transparent);z-index:1;pointer-events:none}
.hero .hero-product-img{width:100%;max-width:420px;object-fit:contain;filter:drop-shadow(0 30px 60px rgba(0,0,0,.6));position:relative;z-index:1;transition:transform .6s var(--ease-out, ease)}
.hero:hover .hero-product-img{transform:scale(1.03)}

/* ── TRUST STRIP ── */
.trust-strip{background:var(--bg2);border-bottom:1px solid var(--border)}
.trust-inner{max-width:1360px;margin:0 auto;display:flex;align-items:stretch;justify-content:center;flex-wrap:wrap}
.trust-item{display:flex;align-items:center;gap:10px;padding:16px 26px;border-left:1px solid var(--border);flex:1;justify-content:center;min-width:150px}
.trust-item:last-child{border-left:none}
.trust-icon{width:32px;height:32px;background:var(--accent-bg);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent)}
.trust-text{display:flex;flex-direction:column;gap:1px}
.trust-label{font-size:10.5px;font-weight:700;color:var(--text3);letter-spacing:.04em;text-transform:uppercase}
.trust-val{font-family:var(--font-en);font-size:15px;font-weight:800;color:var(--text)}

/* ── CATEGORY CAROUSEL (Ugreen-style: plain card, centered image, label below) ── */
.cat-carousel-wrap{position:relative;display:flex;align-items:center;gap:10px}
.cat-carousel{display:flex;gap:16px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;-webkit-overflow-scrolling:touch;padding:2px}
.cat-carousel::-webkit-scrollbar{display:none}
.cat-tile{flex:0 0 auto;width:200px;display:flex;flex-direction:column;text-align:center}
.cat-tile-img{width:100%;aspect-ratio:1;background:var(--bg2);border-radius:16px;display:flex;align-items:center;justify-content:center;overflow:hidden;transition:all .3s var(--ease-out, ease);margin-bottom:14px;color:var(--text3)}
.cat-tile-img img{width:100%;height:100%;object-fit:contain;padding:22px;transition:transform .4s var(--ease-out, ease)}
.cat-tile:hover .cat-tile-img{background:var(--bg3);transform:translateY(-2px)}
.cat-tile:hover .cat-tile-img img{transform:scale(1.06)}
.cat-tile-name{font-size:14.5px;font-weight:700;color:var(--text)}
.cat-arrow{flex-shrink:0;width:40px;height:40px;border-radius:100px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text);transition:all .2s;box-shadow:var(--shadow-sm, 0 1px 3px rgba(0,0,0,.06))}
.cat-arrow:hover{background:var(--black);color:#fff;border-color:var(--black)}
@media(max-width:640px){
  .cat-arrow{display:none}
  .cat-tile{width:128px}
  .cat-tile-name{font-size:12.5px}
}

/* Instagram reels carousel — reuses the cat-arrow button style */
.ig-carousel-wrap{position:relative;display:flex;align-items:center;gap:10px}
.ig-carousel{display:flex;gap:16px;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;-webkit-overflow-scrolling:touch;padding:2px}
.ig-carousel::-webkit-scrollbar{display:none}
/* Instagram's embed script needs a minimum ~326px to render without internally clipping itself —
   giving it less (or clipping the tile with overflow:hidden) causes the card to get visually cut off. */
.ig-tile{flex:0 0 auto;width:360px;max-width:92vw;min-height:600px;border-radius:16px;background:var(--bg2);box-shadow:var(--shadow-sm,0 1px 3px rgba(0,0,0,.06));overflow:hidden;position:relative}
.ig-tile::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,var(--bg2) 25%,var(--bg3) 50%,var(--bg2) 75%);background-size:200% 100%;animation:igShimmer 1.5s infinite}
.ig-tile.loaded::before{display:none}
@keyframes igShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.ig-tile iframe{position:relative;z-index:1}
.ig-tile iframe{border-radius:16px !important}
@media(max-width:640px){
  .ig-arrow-prev,.ig-arrow-next{display:none}
  .ig-tile{width:88vw;min-height:520px}
}

/* Legacy icon-box category style (kept for anywhere still referencing it) */
.cat-icon-box{width:56px;height:56px;background:var(--black);border-radius:13px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;overflow:hidden}
.cat-icon-box img{width:100%;height:100%;object-fit:cover}
.cat-name{font-size:13px;font-weight:700;color:var(--text)}
.cat-count{font-size:11px;font-weight:500;color:var(--text3)}

/* ── PRODUCT CARD (bold Notch style) ── */
.products-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.product-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:all .2s;position:relative;display:flex;flex-direction:column}
.product-card:hover{box-shadow:0 10px 32px rgba(0,0,0,.1);transform:translateY(-3px);border-color:var(--border2)}
.product-card-img{aspect-ratio:1;background:var(--bg2);overflow:hidden;position:relative}
.product-card-img img{width:100%;height:100%;object-fit:contain;padding:14px;transition:transform .35s ease}
.product-card:hover .product-card-img img{transform:scale(1.06)}
.product-card-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:44px;color:var(--text3)}
.product-card-badge{position:absolute;top:11px;right:11px;background:var(--accent);color:#fff;font-size:10px;font-weight:800;padding:4px 9px;border-radius:6px;letter-spacing:.04em}
.product-card-wishlist{position:absolute;top:11px;left:11px;width:32px;height:32px;border-radius:50%;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;font-size:14px;opacity:0;transition:opacity var(--transition);cursor:pointer}
.product-card:hover .product-card-wishlist{opacity:1}
.product-card-body{padding:16px;flex:1;display:flex;flex-direction:column;gap:7px}
.product-card-brand{font-size:10px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.1em;unicode-bidi:plaintext}
.product-card-name{font-size:14.5px;font-weight:700;color:var(--text);line-height:1.35;unicode-bidi:plaintext}
.product-card-rating{display:flex;align-items:center;gap:4px;font-size:11px;color:var(--yellow)}
.product-card-rating span{color:var(--text3)}
.product-card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:12px;border-top:1px solid var(--border)}
.product-card-price{font-family:var(--font-en);font-size:20px;font-weight:900;color:var(--text)}
.product-card-old-price{font-size:11px;color:var(--text3);text-decoration:line-through}
.product-card-add{width:34px;height:34px;border-radius:9px;background:var(--black);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;transition:background var(--transition),transform var(--transition)}
.product-card-add:hover{background:var(--accent);transform:scale(1.08)}

/* ── SPOTLIGHT ── */
.spotlight{background:var(--black);color:#fff;padding:64px 0;overflow:hidden}
.spot-inner{max-width:1360px;margin:0 auto;padding:0 60px;display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center}
.spot-tag{display:inline-block;background:var(--accent);color:#fff;font-size:10px;font-weight:800;padding:5px 12px;border-radius:6px;letter-spacing:.08em;margin-bottom:18px}
.spot-title{font-family:var(--font-body);font-size:clamp(26px,3.6vw,40px);font-weight:900;line-height:1.2;margin-bottom:14px;unicode-bidi:plaintext}
.spot-desc{font-size:14.5px;color:rgba(255,255,255,.55);line-height:1.7;margin-bottom:28px;max-width:440px}
.spot-specs-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:30px}
.spot-spec{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:16px}
.spot-spec-num{font-family:var(--font-en);font-size:26px;font-weight:900;color:#fff}
.spot-spec-num span{font-size:15px;color:var(--accent)}
.spot-spec-desc{font-size:11.5px;color:rgba(255,255,255,.45);font-weight:500;margin-top:2px}
.spot-img{position:relative;display:flex;align-items:center;justify-content:center}
.spot-img img{width:100%;max-width:400px;object-fit:contain;filter:drop-shadow(0 24px 50px rgba(0,0,0,.7))}
.spot-img-ring{position:absolute;width:400px;height:400px;border-radius:50%;border:1px solid rgba(var(--accent-rgb),.18);animation:spin-slow 20s linear infinite}
.spot-img-ring2{position:absolute;width:320px;height:320px;border-radius:50%;border:1px dashed rgba(var(--accent-rgb),.12);animation:spin-slow 15s linear infinite reverse}
@keyframes spin-slow{to{transform:rotate(360deg)}}

/* ── WHY US ── */
.why-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.why-card{border-radius:var(--radius);border:1px solid var(--border);padding:28px 24px;position:relative;overflow:hidden;transition:all .2s}
.why-card:hover{border-color:var(--accent);box-shadow:0 4px 24px rgba(var(--accent-rgb),.08)}
.why-card::before{content:'';position:absolute;top:0;right:0;left:0;height:3px;background:var(--accent);transform:scaleX(0);transform-origin:right;transition:transform .3s}
.why-card:hover::before{transform:scaleX(1)}
.why-num{font-family:var(--font-en);font-size:52px;font-weight:900;color:var(--bg3);line-height:1;margin-bottom:14px}
.why-icon{width:42px;height:42px;background:var(--accent-bg);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:var(--accent)}
.why-title{font-size:16px;font-weight:700;margin-bottom:6px;color:var(--text)}
.why-desc{font-size:13px;color:var(--text2);line-height:1.6}

/* ── WHOLESALE CTA ── */
.wholesale-section{background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.wholesale-inner{max-width:1360px;margin:0 auto;padding:56px 60px;display:grid;grid-template-columns:1.1fr 1fr;gap:56px;align-items:center}
.wholesale-label{font-size:11px;font-weight:800;color:var(--accent);letter-spacing:.1em;margin-bottom:10px}
.wholesale-title{font-family:var(--font-body);font-size:clamp(22px,3vw,32px);font-weight:900;line-height:1.25;margin-bottom:16px;color:var(--text)}
.wholesale-desc{font-size:14px;color:var(--text2);line-height:1.8;margin-bottom:26px;max-width:440px}
.wholesale-perks{display:flex;flex-direction:column;gap:12px;margin-bottom:26px}
.wholesale-perk{display:flex;align-items:center;gap:10px;font-size:13.5px;font-weight:600;color:var(--text)}
.wholesale-perk-dot{width:19px;height:19px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff}
.wholesale-card{background:var(--black);color:#fff;border-radius:var(--radius);padding:28px}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:11px;font-size:14px;font-weight:700;border:1px solid transparent;transition:all var(--transition);cursor:pointer;white-space:nowrap}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent);box-shadow:0 2px 10px rgba(var(--accent-rgb),.3)}
.btn-primary:hover{background:var(--accent-dark);transform:translateY(-1px)}
.btn-outline{background:transparent;color:var(--text);border-color:var(--border)}
.btn-outline:hover{border-color:var(--accent);color:var(--accent)}
.btn-ghost{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.14)}
.btn-ghost:hover{background:rgba(255,255,255,.14)}
.btn-danger{background:var(--red);color:#fff;border-color:var(--red)}
.btn-lg{padding:15px 30px;font-size:15px}
.btn-sm{padding:8px 15px;font-size:12.5px}
.btn-full{width:100%}

/* ── BADGE ── */
.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:20px;font-size:11px;font-weight:700}
.badge-new{background:var(--accent-bg);color:var(--accent)}
.badge-sale{background:rgba(229,52,42,.12);color:var(--red)}
.badge-out{background:rgba(120,120,140,.15);color:var(--text3)}

/* ── BREADCRUMB ── */
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text3);padding:20px 0}
.breadcrumb a:hover{color:var(--text)}
.breadcrumb-sep{color:var(--border2)}
.breadcrumb-current{color:var(--text2)}

/* ── FILTER SIDEBAR ── */
.filter-sidebar{width:250px;flex-shrink:0}
.filter-group{border-bottom:1px solid var(--border);padding-bottom:18px;margin-bottom:18px}
.filter-group:last-child{border-bottom:none}
.filter-title{font-size:11.5px;font-weight:800;color:var(--text);text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px}
.filter-item{display:flex;align-items:center;gap:9px;padding:7px 9px;margin:0 -9px;border-radius:8px;font-size:13px;color:var(--text2);cursor:pointer;transition:all .15s}
.filter-item:hover{background:var(--bg2);color:var(--text)}
.filter-item.active{background:var(--accent-bg);color:var(--accent);font-weight:700}
.filter-item input{width:15px;height:15px;accent-color:var(--accent);flex-shrink:0;cursor:pointer}
.filter-item-label{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.filter-item-count{font-size:10.5px;font-weight:700;color:var(--text3);background:var(--bg3);padding:1px 7px;border-radius:20px;flex-shrink:0}
.filter-item.active .filter-item-count{background:rgba(var(--accent-rgb),.15);color:var(--accent)}

/* ── FORMS ── */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px}
.form-label span{color:var(--red)}
.form-input,.form-select,.form-textarea{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);padding:11px 14px;font-size:14px;font-family:inherit;outline:none;transition:border-color var(--transition),box-shadow var(--transition)}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(var(--accent-rgb),.12)}
.form-input::placeholder{color:var(--text3)}
.form-textarea{resize:vertical;min-height:100px}
.form-select{appearance:none}
.form-error{font-size:12px;color:var(--red);margin-top:4px}

/* ── FLASH ── */
.flash-container{max-width:1360px;margin:0 auto;padding:12px 28px 0}
.flash{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:11px;font-size:13.5px;font-weight:600;animation:fadeSlide .2s ease}
@keyframes fadeSlide{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.flash-success{background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.2);color:var(--green)}
.flash-error{background:rgba(229,52,42,.1);border:1px solid rgba(229,52,42,.2);color:var(--red)}

/* ── FOOTER (always brand black) ── */
.footer{background:var(--black);color:rgba(255,255,255,.6);padding:56px 0 28px}
.footer-inner{max-width:1360px;margin:0 auto;padding:0 28px}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:48px}
.footer-brand{display:flex;align-items:center;gap:9px;margin-bottom:14px;font-family:var(--font-en);font-weight:800;font-size:18px;color:#fff}
.footer-brand img{height:26px;width:auto;object-fit:contain}
.footer-desc{font-size:13px;line-height:1.8;max-width:260px;margin-bottom:18px}
.footer-social{display:flex;gap:8px;margin-top:4px}
.footer-social a{width:34px;height:34px;background:rgba(255,255,255,.08);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;color:rgba(255,255,255,.7);transition:background .15s}
.footer-social a:hover{background:var(--accent);color:#fff}
.footer-col-title{font-size:11px;font-weight:800;color:rgba(255,255,255,.9);letter-spacing:.08em;text-transform:uppercase;margin-bottom:16px}
.footer-link{display:block;font-size:13px;color:rgba(255,255,255,.5);padding:4px 0;transition:color var(--transition)}
.footer-link:hover{color:#fff}
.footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding-top:22px;display:flex;align-items:center;justify-content:space-between;font-size:12px;color:rgba(255,255,255,.35);flex-wrap:wrap;gap:8px}
.footer-badges{display:flex;gap:16px}

/* ── RESPONSIVE ── */
@media(max-width:1100px){
  .products-grid{grid-template-columns:repeat(3,1fr)}
  .hero{grid-template-columns:1fr}
  .hero .hero-right{height:260px;padding:16px}
  .footer-top{grid-template-columns:1fr 1fr}
  .spot-inner,.wholesale-inner{grid-template-columns:1fr;gap:32px;padding:0 32px}
  .spot-img{max-width:320px;margin:0 auto}
}
@media(max-width:600px){
  /* The spotlight ("الأكثر تميزاً") section was keeping 60px of side padding from the desktop
     rule even on small phones, squeezing the tag/title/specs into a narrow, cramped, hard-to-
     read column — that's what made it feel "unclear" on mobile, not the tag styling itself. */
  .spotlight{padding:40px 0}
  .spot-inner{padding:0 20px;gap:24px}
  .spot-title{font-size:24px}
  .spot-specs-grid{grid-template-columns:1fr 1fr}
  .spot-img{max-width:220px}
}
@media(max-width:768px){
  .nav-links,.nav-search{display:none}
  .nav-hamburger{display:flex}
  /* Two-row mobile header: logo + hamburger on top, utility icons on their own row below —
     replaces the old single cramped row where everything squeezed to one side of the logo. */
  .nav-inner{flex-wrap:wrap;row-gap:10px;padding:10px 16px}
  .nav-logo{order:2;margin-left:0;flex:1;justify-content:center}
  .nav-hamburger{order:1;margin-right:0;flex-shrink:0;width:38px;height:38px;background:var(--bg2,var(--bg3));border-radius:10px;display:flex;align-items:center;justify-content:center}
  .nav-hamburger-spacer{order:3;flex-shrink:0;width:38px;height:38px}
  .nav-actions{order:4;flex-basis:100%;justify-content:center;margin:0}
  .nav-business-btn,.nav-warranty-btn,.nav-divider{display:none}
  .nav-utils{gap:14px}
  .products-grid{grid-template-columns:repeat(2,1fr);gap:12px}
  .section{padding:40px 20px}
  .section-title{font-size:24px}
  .footer-top{grid-template-columns:1fr}
  .why-grid{grid-template-columns:1fr 1fr}
  /* Trust badges — horizontal scroll strip on mobile instead of wrapping into a 2-column grid,
     which was taking up much more vertical space than needed right below the hero. */
  .trust-inner{flex-wrap:nowrap;overflow-x:auto;justify-content:flex-start;scrollbar-width:none;-webkit-overflow-scrolling:touch}
  .trust-inner::-webkit-scrollbar{display:none}
  .trust-item{flex:0 0 auto;min-width:0;border-left:1px solid var(--border);border-bottom:none;padding:14px 20px}
  /* Generic safety-net: any page-level 2-column layout collapses to one column */
  .page-grid-2,.page-grid-sidebar{grid-template-columns:1fr !important;gap:16px !important}
  .page-grid-form-2{grid-template-columns:1fr !important}
  table{font-size:12.5px}
  .btn-lg{padding:12px 20px;font-size:14px}
  h1{font-size:22px !important}
}
@media(max-width:480px){
  .container{padding:0 16px}
  .products-grid{grid-template-columns:repeat(2,1fr);gap:10px}
  .why-grid{grid-template-columns:1fr}
}

/* ══════════════════════════════════════════════════════
   MODERN SKIN 2.0 — Ugreen/Anker-inspired refinement layer
   ══════════════════════════════════════════════════════ */
:root{
  --radius:20px; --radius-sm:12px;
  --shadow-sm:0 1px 3px rgba(0,0,0,.04),0 4px 12px rgba(0,0,0,.04);
  --shadow-md:0 4px 12px rgba(0,0,0,.05),0 16px 40px rgba(0,0,0,.08);
  --shadow-accent:0 8px 28px rgba(var(--accent-rgb),.28);
  --ease-out:cubic-bezier(.16,1,.3,1);
}
[data-theme="dark"]{
  --shadow-sm:0 1px 3px rgba(0,0,0,.3),0 4px 12px rgba(0,0,0,.25);
  --shadow-md:0 4px 12px rgba(0,0,0,.35),0 16px 40px rgba(0,0,0,.45);
}
::selection{background:rgba(var(--accent-rgb),.25)}
body{-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}

/* Buttons — Anker-style pill CTAs with press feedback */
.btn{border-radius:100px !important;font-weight:700;letter-spacing:.01em;transition:all .25s var(--ease-out);position:relative;overflow:hidden}
/* Tab-select buttons (product page "الوصف"/"التقييمات" etc.) must stay flat with a straight
   underline — the global pill-button rule above was rounding them into a pill shape, which
   turned their border-bottom active-indicator into a curved smile shape instead of a line. */
.btn.tab-btn{border-radius:0 !important}
.btn:active{transform:scale(.97)}
.btn-primary{box-shadow:var(--shadow-accent)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 12px 32px rgba(var(--accent-rgb),.4)}
.btn-secondary:hover{border-color:var(--text);transform:translateY(-1px)}
.nav-business-btn,.nav-warranty-btn{border-radius:100px}

/* Product cards — lighter, airier, image-forward */
.products-grid{gap:22px}
.product-card{border-radius:var(--radius);border-color:transparent;box-shadow:var(--shadow-sm);transition:all .35s var(--ease-out)}
.product-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-md);border-color:transparent}
.product-card-img{background:linear-gradient(160deg,var(--bg2),var(--bg3))}
.product-card-img img{padding:20px;transition:transform .5s var(--ease-out)}
.product-card:hover .product-card-img img{transform:scale(1.08) translateY(-3px)}
.product-card-badge{border-radius:100px;padding:5px 12px;box-shadow:0 2px 10px rgba(var(--accent-rgb),.35)}
.product-card-add{border-radius:100px;width:38px;height:38px;box-shadow:0 3px 12px rgba(0,0,0,.2)}
.product-card-add:hover{transform:scale(1.12) rotate(90deg)}
.product-card-wishlist{background:rgba(255,255,255,.9);color:#0A0A0A;box-shadow:0 2px 10px rgba(0,0,0,.12)}
[data-theme="dark"] .product-card-wishlist{background:rgba(30,30,36,.9);color:#fff}
.product-card-price{letter-spacing:-.02em}

/* Section headers — tech-brand editorial feel */
.section-title{letter-spacing:-.02em}
.section-head{margin-bottom:28px}
.section-link{font-weight:700;border-radius:100px;padding:8px 18px;border:1.5px solid var(--border);transition:all .25s var(--ease-out)}
.section-link:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-bg)}

/* Collection detail banner — uses the collection's own uploaded image */
.collection-banner{position:relative;border-radius:var(--radius);overflow:hidden;height:180px;margin-bottom:24px;box-shadow:var(--shadow-sm)}
.collection-banner-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.collection-banner-shade{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75),rgba(0,0,0,.2) 60%,rgba(0,0,0,.1))}
.collection-banner-text{position:absolute;bottom:0;right:0;left:0;padding:24px}
.collection-banner-title{color:#fff;font-size:24px;font-weight:900;letter-spacing:-.01em}
.collection-banner-desc{color:rgba(255,255,255,.8);font-size:13px;margin-top:6px;max-width:520px}
@media(max-width:600px){.collection-banner{height:130px}.collection-banner-title{font-size:19px}}

/* Hero — refined depth */
.hero-carousel,.hc-slide{border-radius:var(--radius)}
.hero-badge{backdrop-filter:blur(8px)}
.hc-slide-bg img,.hc-slide-bg video{transition:transform 6s linear}
.hc-slide.active .hc-slide-bg img,.hc-slide.active .hc-slide-bg video{transform:scale(1.06)}
.hc-arrow{border-radius:100px;backdrop-filter:blur(8px);transition:all .25s var(--ease-out)}
.hc-arrow:hover{transform:scale(1.1)}
.hc-dot{transition:all .3s var(--ease-out)}
.hc-dot.on{width:22px;border-radius:100px}

/* Trust strip — quieter, integrated */
.trust-strip{border-top:1px solid var(--border)}
.trust-icon{border-radius:10px}

/* Nav — glass refinement */
.nav{backdrop-filter:blur(20px) saturate(1.6);-webkit-backdrop-filter:blur(20px) saturate(1.6)}
.nav-link{position:relative;transition:color .2s}
.nav-link::after{content:'';position:absolute;bottom:-4px;right:0;left:100%;height:2px;background:var(--accent);border-radius:2px;transition:left .25s var(--ease-out)}
.nav-link:hover::after,.nav-link.active::after{left:0}
.nav-drop-menu{border-radius:var(--radius-sm);box-shadow:var(--shadow-md);border-color:var(--border)}
.nav-search{border-radius:100px}
/* Search overlay — simple, centered, opened by the nav search icon */
.search-overlay{display:none;position:fixed;inset:0;z-index:500;background:rgba(0,0,0,.5);backdrop-filter:blur(6px);align-items:flex-start;justify-content:center;padding-top:14vh}
.search-overlay.open{display:flex;animation:fadeIn .15s ease}
.search-overlay-inner{width:100%;max-width:560px;padding:0 20px}
.search-overlay-form{display:flex;align-items:center;gap:12px;background:var(--surface);border-radius:18px;padding:16px 20px;box-shadow:0 20px 60px rgba(0,0,0,.35);color:var(--text3)}
.search-overlay-form input{flex:1;background:none;border:none;outline:none;font-size:16px;color:var(--text);font-family:inherit}
.search-overlay-form input::placeholder{color:var(--text3)}
.search-overlay-close{color:var(--text3);display:flex;padding:4px;border-radius:100px;transition:background .15s}
.search-overlay-close:hover{background:var(--bg3)}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@media(max-width:600px){.search-overlay{padding-top:10vh}}

/* Inputs — softer focus */
.form-input,.form-select,.form-textarea{border-radius:12px;transition:border-color .2s,box-shadow .2s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 4px rgba(var(--accent-rgb),.12);outline:none}

/* Spotlight & dark sections */
.spotlight{border-radius:0;position:relative;overflow:hidden}
.spotlight::before{content:'';position:absolute;top:-30%;left:-10%;width:60%;height:120%;background:radial-gradient(ellipse,rgba(var(--accent-rgb),.16),transparent 65%);pointer-events:none}
.spot-tag{border-radius:100px}
.spot-img img{animation:spotFloat 5s ease-in-out infinite;filter:drop-shadow(0 40px 60px rgba(0,0,0,.55))}
@keyframes spotFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
@media(prefers-reduced-motion:reduce){.spot-img img{animation:none}}
.spot-spec-num{letter-spacing:-.02em}

/* Footer polish */
.footer-social a{border-radius:100px;transition:all .25s var(--ease-out)}
.footer-social a:hover{transform:translateY(-2px);background:var(--accent);border-color:var(--accent)}

/* Ad banners — img zoom + legibility gradients */
.ad-banner,.mb-item,.sb-item{box-shadow:var(--shadow-sm);transition:all .3s var(--ease-out)}
.ad-banner:hover,.mb-item:hover,.sb-item:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.ad-banner img,.mb-item img,.sb-item img{transition:transform .6s var(--ease-out)}
.ad-banner:hover img,.mb-item:hover img,.mb-item:hover video,.sb-item:hover img{transform:scale(1.05)}
.mb-overlay,.ad-banner-overlay{background:linear-gradient(to top,rgba(0,0,0,.55),rgba(0,0,0,.05) 55%,transparent)}
.hc-slide img.hc-bg{transition:transform 6s linear}
.hc-slide.on img.hc-bg{transform:scale(1.06)}

/* Scroll-in reveal */
@media(prefers-reduced-motion:no-preference){
  .reveal{opacity:0;transform:translateY(18px);transition:opacity .55s var(--ease-out),transform .55s var(--ease-out)}
  .reveal.in{opacity:1;transform:none}
}

/* Toast/flash refinement */
.flash{border-radius:14px;box-shadow:var(--shadow-md)}

/* ── Sticky buy bar (shown via JS once the person scrolls past the main buy box) ── */
.mobile-buy-bar{position:fixed;bottom:0;right:0;left:0;z-index:150;background:var(--surface);border-top:1px solid var(--border);padding:10px 16px;display:none;align-items:center;gap:14px;box-shadow:0 -8px 24px rgba(0,0,0,.08);transform:translateY(100%);transition:transform .25s var(--ease-out,ease)}
.mobile-buy-bar.visible{display:flex;transform:translateY(0)}
.mobile-buy-bar .mb-price{font-size:17px;font-weight:900;color:var(--text)}
.mobile-buy-bar .btn{flex:1;display:flex;align-items:center;justify-content:center;gap:8px}
@media(min-width:769px){.mobile-buy-bar{max-width:600px;margin:0 auto;border-radius:16px 16px 0 0;right:50%;left:auto;transform:translate(50%,100%)}.mobile-buy-bar.visible{transform:translate(50%,0)}}

/* ── Mobile app-style bottom tab bar ── */
.bottom-tabs{display:none}
@media(max-width:768px){
  .bottom-tabs{display:flex;position:fixed;bottom:0;right:0;left:0;z-index:95;background:var(--nav-bg);backdrop-filter:blur(20px) saturate(1.6);-webkit-backdrop-filter:blur(20px) saturate(1.6);border-top:1px solid var(--border);padding:6px 4px calc(6px + env(safe-area-inset-bottom))}
  .bottom-tab{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:5px 2px;color:var(--text3);transition:color .2s;position:relative}
  .bottom-tab.active{color:var(--accent)}
  .bottom-tab.active .bt-icon{transform:translateY(-1px)}
  .bt-icon{position:relative;display:flex;transition:transform .2s var(--ease-out)}
  .bt-label{font-size:9.5px;font-weight:700}
  .bt-badge{position:absolute;top:-5px;left:-8px;background:var(--red);color:#fff;font-size:9px;font-weight:800;min-width:15px;height:15px;border-radius:100px;display:flex;align-items:center;justify-content:center;padding:0 4px}
  body{padding-bottom:calc(62px + env(safe-area-inset-bottom))}
  .footer{padding-bottom:20px}
  /* Product page already adds its own sticky buy bar — lift it above the tabs */
  .mobile-buy-bar.visible{transform:translateY(calc(-1 * (58px + env(safe-area-inset-bottom))))}
}

/* ── Products page: Ugreen-style mobile filters ── */
.filter-chips{display:none}
.filter-toggle-btn{display:none}
.filter-sheet-head{display:none}
@media(max-width:768px){
  .filter-sidebar{display:none;position:fixed;inset:auto 0 0 0;max-height:78vh;overflow-y:auto;background:var(--surface);border-radius:22px 22px 0 0;box-shadow:0 -10px 40px rgba(0,0,0,.25);padding:20px;z-index:200;width:auto}
  .filter-sidebar.open{display:block;animation:sheetUp .3s var(--ease-out)}
  @keyframes sheetUp{from{transform:translateY(40px);opacity:0}to{transform:none;opacity:1}}
  .filter-sheet-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:190}
  .filter-sheet-overlay.open{display:block}
  .filter-sheet-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;position:sticky;top:-20px;background:var(--surface);padding:6px 0;z-index:1}
  .filter-toggle-btn{display:inline-flex;align-items:center;gap:6px;border:1.5px solid var(--border);border-radius:100px;padding:8px 16px;font-size:12.5px;font-weight:700;background:var(--surface);color:var(--text)}
  .filter-chips{display:flex;gap:8px;overflow-x:auto;padding:2px 0 10px;scrollbar-width:none;-webkit-overflow-scrolling:touch}
  .filter-chips::-webkit-scrollbar{display:none}
  .filter-chip{flex-shrink:0;border:1.5px solid var(--border);border-radius:100px;padding:7px 16px;font-size:12.5px;font-weight:600;color:var(--text2);background:var(--surface);white-space:nowrap;transition:all .2s}
  .filter-chip.active{background:var(--black);border-color:var(--black);color:#fff}
  [data-theme="dark"] .filter-chip.active{background:#fff;border-color:#fff;color:#0A0A0A}
}
</style>
<?php if(!empty($extraHead)) echo $extraHead; ?>
</head>
<body>

<!-- Mobile menu -->
<div class="mobile-menu" id="mobileMenu" onclick="if(event.target===this)closeMobileMenu()">
  <div class="mobile-menu-inner">
    <button class="mobile-close" onclick="closeMobileMenu()">×</button>
    <div class="footer-brand" style="color:var(--text);margin-bottom:20px">
      <?php if ($_logo): ?><img src="<?= uploadUrl($_logo) ?>" alt=""><?php else: ?><div class="nav-logo-mark">⚡</div><?= e(_s('store_name','NOTCH')) ?><?php endif; ?>
    </div>
    <?php
    // Dynamic mobile menu from the DB menu builder (main items + children flattened)
    $_mobMenuItems = [];
    try { $_mobMenuItems = Database::fetchAll("SELECT * FROM nav_menu_items WHERE location='main' AND is_active=1 ORDER BY parent_id IS NULL DESC, sort_order, id"); } catch (\Throwable $e) {}
    if (!empty($_mobMenuItems)):
        foreach ($_mobMenuItems as $_mi):
            if (!empty($_mi['parent_id'])) continue;
            $_miHref = filter_var($_mi['url'], FILTER_VALIDATE_URL) ? $_mi['url'] : url(ltrim($_mi['url'],'/'));
    ?>
      <a href="<?= e($_miHref) ?>" class="mobile-link"><?php if($_mi['icon']): ?><span class="mobile-link-icon"><?= e($_mi['icon']) ?></span><?php endif; ?> <?= e($_mi['label']) ?></a>
      <?php foreach ($_mobMenuItems as $_mc): if (($_mc['parent_id']??null) != $_mi['id']) continue;
          $_mcHref = filter_var($_mc['url'], FILTER_VALIDATE_URL) ? $_mc['url'] : url(ltrim($_mc['url'],'/')); ?>
        <a href="<?= e($_mcHref) ?>" class="mobile-link" style="padding-right:32px;font-size:13px;color:var(--text3)">↳ <?= e($_mc['label']) ?></a>
      <?php endforeach; ?>
    <?php endforeach; else: ?>
      <a href="<?= url() ?>" class="mobile-link">الرئيسية</a>
      <a href="<?= url('products') ?>" class="mobile-link">كل المنتجات</a>
      <a href="<?= url('about') ?>" class="mobile-link">من نحن</a>
      <a href="<?= url('contact') ?>" class="mobile-link">تواصل معنا</a>
    <?php endif; ?>
    <?php foreach (array_slice($collections, 0, 8) as $c): ?>
      <a href="<?= url('collections/' . $c['slug']) ?>" class="mobile-link" style="font-size:13px;color:var(--text3)"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
    <?php
    $_bizHrefM = url('business/register'); $_bizLabelM = 'Business — الأعمال';
    if (isStoreLoggedIn()) {
        try { $_bc = (new CustomerModel())->find(storeUser()['id']); if(($_bc['business_type']??'none')!=='none'){ $_bizHrefM = url('business'); $_bizLabelM = 'لوحتي — Business'; } } catch(\Throwable $e) {}
    }
    ?>
    <a href="<?= $_bizHrefM ?>" class="mobile-link" style="background:linear-gradient(135deg,var(--accent),color-mix(in srgb,var(--accent) 70%,#7B2FBE));color:#fff;font-weight:700;margin-top:6px"><span class="mobile-link-icon"><?= svgIcon('briefcase', 16) ?></span> <?= e($_bizLabelM) ?></a>
    <a href="<?= url('warranty') ?>" class="mobile-link"><span class="mobile-link-icon"><?= svgIcon('shield', 16) ?></span> تفعيل الضمان</a>
    <div style="margin-top:auto;padding-top:20px;border-top:1px solid var(--border)">
      <?php if (isStoreLoggedIn()): ?>
        <a href="<?= url('account') ?>" class="mobile-link">حسابي</a>
        <a href="<?= url('logout') ?>" class="mobile-link">تسجيل الخروج</a>
      <?php else: ?>
        <a href="<?= url('login') ?>" class="mobile-link">تسجيل الدخول</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Announcement bar -->
<div class="ann-bar">
  <span>
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    <?php $freeShip = (float)_s('free_shipping_above','0'); ?>
    <?= $freeShip > 0 ? 'شحن مجاني للطلبات فوق ' . money($freeShip) : 'اطلب الآن واستمتع بأفضل الأسعار' ?>
  </span>
  <span class="ann-sep">|</span>
  <span><?= svgIcon('star', 13) ?> فواتيرك · <?= svgIcon('cart', 13) ?> الدفع عند الاستلام</span>
  <?php if ($wa = _s('contact_whatsapp')): ?>
  <span class="ann-sep">|</span>
  <span>
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
    <?= e($wa) ?>
  </span>
  <?php endif; ?>
</div>

<!-- Navbar -->
<nav class="nav">
  <div class="nav-inner">
    <a href="<?= url() ?>" class="nav-logo">
      <?php $_logoLight = logoUrl('light'); $_logoDarkNav = logoUrl('dark'); ?>
      <?php if ($_logoLight || $_logoDarkNav): ?>
        <?php if ($_logoLight): ?><img src="<?= $_logoLight ?>" alt="logo" class="logo-light-mode"><?php endif; ?>
        <?php if ($_logoDarkNav): ?><img src="<?= $_logoDarkNav ?>" alt="logo" class="logo-dark-mode"><?php endif; ?>
      <?php else: ?>
        <div class="nav-logo-mark">⚡</div><?= e(_s('store_name', 'NOTCH')) ?>
      <?php endif; ?>
    </a>

    <div class="nav-links">
      <?php
      $currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
      $mainMenuItems = [];
      try { $mainMenuItems = Database::fetchAll("SELECT * FROM nav_menu_items WHERE location='main' AND parent_id IS NULL AND is_active=1 ORDER BY sort_order, id"); } catch (\Throwable $e) {}
      ?>
      <?php foreach ($mainMenuItems as $item):
          $itemPath = trim(parse_url($item['url'], PHP_URL_PATH), '/');
          $isActive = $itemPath === '' ? $currentPath === '' : str_starts_with($currentPath, $itemPath);
          $children = [];
          try { $children = Database::fetchAll("SELECT * FROM nav_menu_items WHERE parent_id=? AND is_active=1 ORDER BY sort_order, id", [$item['id']]); } catch (\Throwable $e) {}
      ?>
        <?php if (!empty($children)): ?>
          <div class="nav-drop">
            <span class="nav-link <?= $isActive?'active':'' ?>" style="cursor:pointer"><?= e($item['icon']?:'') ?> <?= e($item['label']) ?> ▾</span>
            <div class="nav-drop-menu">
              <?php foreach ($children as $child): ?>
                <a href="<?= filter_var($child['url'],FILTER_VALIDATE_URL) ? e($child['url']) : url(ltrim($child['url'],'/')) ?>" class="nav-drop-item"><?= e($child['icon']?:'🔗') ?> <?= e($child['label']) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= filter_var($item['url'],FILTER_VALIDATE_URL) ? e($item['url']) : url(ltrim($item['url'],'/')) ?>" class="nav-link <?= $isActive?'active':'' ?>"><?= e($item['icon']?:'') ?> <?= e($item['label']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (!empty($collections)): ?>
      <div class="nav-drop">
        <span class="nav-link" style="cursor:pointer">التصنيفات ▾</span>
        <div class="nav-drop-menu">
          <?php foreach (array_slice($collections,0,8) as $c): ?>
            <a href="<?= url('collections/'.$c['slug']) ?>" class="nav-drop-item">
              <?php if($c['image']??null): ?><img src="<?= uploadUrl($c['image']) ?>" style="width:24px;height:24px;border-radius:5px;object-fit:cover"><?php else: ?><?= svgIcon('grid', 15) ?><?php endif; ?>
              <?= e($c['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="nav-actions">
      <?php
      $_bizHref = url('business/register');
      $_bizLabel = ['en'=>'Business','ar'=>'الأعمال'];
      if (isStoreLoggedIn()) {
          try {
              $_bizCust = (new CustomerModel())->find(storeUser()['id']);
              if (($_bizCust['business_type'] ?? 'none') !== 'none') { $_bizHref = url('business'); $_bizLabel = ['en'=>'Business','ar'=>'لوحتي']; }
          } catch (\Throwable $e) {}
      }
      ?>
      <a href="<?= $_bizHref ?>" class="nav-business-btn" title="Notch Business">
        <span class="nav-business-icon"><?= svgIcon('briefcase', 16) ?></span>
        <span class="nav-business-text"><span class="nav-business-en"><?= $_bizLabel['en'] ?></span><span class="nav-business-ar"><?= $_bizLabel['ar'] ?></span></span>
      </a>
      <a href="<?= url('warranty') ?>" class="nav-warranty-btn" title="تفعيل الضمان">
        <span class="nav-business-icon"><?= svgIcon('shield', 16) ?></span>
        <span class="nav-business-text"><span class="nav-business-en">Warranty</span><span class="nav-business-ar">الضمان</span></span>
      </a>
      <span class="nav-divider"></span>
      <div class="nav-utils">
      <button type="button" class="nav-btn" onclick="openSearchOverlay()" title="بحث" aria-label="بحث">
        <?= svgIcon('search', 19) ?>
      </button>
      <button class="theme-toggle" onclick="toggleStoreTheme()" id="themeToggleBtn" title="تغيير المظهر"><span class="tt-moon"><?= svgIcon('moon', 17) ?></span><span class="tt-sun"><?= svgIcon('sun', 17) ?></span></button>
      <?php if (isStoreLoggedIn()): ?>
        <a href="<?= url('account') ?>" class="nav-btn" title="حسابي"><?= svgIcon('user', 19) ?></a>
      <?php else: ?>
        <a href="<?= url('login') ?>" class="nav-btn" title="تسجيل الدخول"><?= svgIcon('user', 19) ?></a>
      <?php endif; ?>
      <a href="<?= url('cart') ?>" class="nav-btn" title="السلة" id="cartBtn">
        <?= svgIcon('cart', 19) ?>
        <?php $cartCount = Cart::count(); ?>
        <?php if ($cartCount > 0): ?><span class="nav-badge" id="cartBadge"><?= $cartCount ?></span><?php endif; ?>
      </a>
      </div>
    </div>
    <button class="nav-hamburger" onclick="openMobileMenu()" aria-label="القائمة"><?= svgIcon('menu', 20) ?></button>
    <span class="nav-hamburger-spacer" aria-hidden="true"></span>
  </div>
</nav>

<!-- Search overlay — opened by the nav search icon, kept simple: one input, submits to /search -->
<div class="search-overlay" id="searchOverlay">
  <div class="search-overlay-inner">
    <form action="<?= url('search') ?>" method="GET" class="search-overlay-form">
      <?= svgIcon('search', 22) ?>
      <input type="text" name="q" id="searchOverlayInput" placeholder="دور على منتج..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
      <button type="button" class="search-overlay-close" onclick="closeSearchOverlay()" aria-label="إغلاق"><?= svgIcon('close', 20) ?></button>
    </form>
  </div>
</div>

<!-- Flash messages -->
<?php if ($s = flash('success')): ?><div class="flash-container"><div class="flash flash-success">✅ <?= e($s) ?></div></div><?php endif; ?>
<?php if ($e = flash('error')): ?><div class="flash-container"><div class="flash flash-error">⚠️ <?= e($e) ?></div></div><?php endif; ?>

<!-- Page Content -->
<?php if (!empty($content)) echo $content; ?>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div>
        <div class="footer-brand">
          <?php $_logoDark = logoUrl('dark'); ?>
          <?php if ($_logoDark): ?><img src="<?= $_logoDark ?>" alt=""><?php else: ?><div class="nav-logo-mark">⚡</div><?= e(_s('store_name','NOTCH')) ?><?php endif; ?>
        </div>
        <div class="footer-desc"><?= e(_s('store_description', 'متجر الإلكترونيات الرائد في مصر — منتجات أصلية، جودة موثوقة، وضمان حقيقي.')) ?></div>
        <div class="footer-social">
          <?php foreach (['facebook'=>'f','instagram'=>'📷','twitter'=>'𝕏','youtube'=>'▶','tiktok'=>'♪'] as $k=>$icon): ?>
            <?php $link=_s("social_{$k}"); if ($link): ?><a href="<?= e($link) ?>" target="_blank"><?= $icon ?></a><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <div class="footer-col-title">تسوق</div>
        <a href="<?= url('products') ?>" class="footer-link">كل المنتجات</a>
        <?php foreach (array_slice($collections ?? [], 0, 5) as $c): ?>
          <a href="<?= url('collections/'.$c['slug']) ?>" class="footer-link"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <div>
        <div class="footer-col-title">الشركة</div>
        <?php
        $footerLinks = [];
        try { $footerLinks = Database::fetchAll("SELECT * FROM nav_menu_items WHERE location='footer' AND is_active=1 ORDER BY sort_order, id"); } catch (\Throwable $e) {}
        ?>
        <?php if (!empty($footerLinks)): foreach ($footerLinks as $fl): ?>
          <a href="<?= filter_var($fl['url'],FILTER_VALIDATE_URL) ? e($fl['url']) : url(ltrim($fl['url'],'/')) ?>" class="footer-link"><?= e($fl['icon']?:'') ?> <?= e($fl['label']) ?></a>
        <?php endforeach; else: ?>
          <a href="<?= url('about') ?>" class="footer-link">من نحن</a>
          <a href="<?= url('contact') ?>" class="footer-link">تواصل معنا</a>
        <?php endif; ?>
      </div>
      <div>
        <div class="footer-col-title">الدعم</div>
        <a href="<?= url('support') ?>" class="footer-link">مركز المساعدة</a>
        <a href="<?= url('warranty') ?>" class="footer-link">تفعيل الضمان</a>
        <a href="<?= url('account/orders') ?>" class="footer-link">تتبع طلبك</a>
        <a href="<?= url('support/track') ?>" class="footer-link">تتبع تذكرة دعم</a>
        <?php if ($phone = _s('store_phone')): ?><a href="tel:<?= e($phone) ?>" class="footer-link"><?= svgIcon('phone', 12) ?> <?= e($phone) ?></a><?php endif; ?>
        <?php if ($email = _s('store_email')): ?><a href="mailto:<?= e($email) ?>" class="footer-link"><?= svgIcon('mail', 12) ?> <?= e($email) ?></a><?php endif; ?>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= e(_s('store_name', 'Notch Technology')) ?> — جميع الحقوق محفوظة</span>
      <div class="footer-badges">
        <span>💳 فواتيرك</span><span>💵 الدفع عند الاستلام</span><span>🔒 دفع آمن</span>
      </div>
    </div>
  </div>
</footer>

<script>
// ── Hero carousel (autoplay + manual nav) ──
let hcIndex = 0, hcTimer = null;
function hcSlideCount(){ return document.querySelectorAll('.hc-slide').length; }
function hcGo(i){
  const slides = document.querySelectorAll('.hc-slide');
  const dots = document.querySelectorAll('.hc-dot');
  if (!slides.length) return;
  hcIndex = (i + slides.length) % slides.length;
  slides.forEach((s,idx) => s.classList.toggle('active', idx===hcIndex));
  dots.forEach((d,idx) => d.classList.toggle('active', idx===hcIndex));
  hcResetTimer();
}
function hcMove(dir){ hcGo(hcIndex + dir); }
function hcResetTimer(){
  if (hcTimer) clearInterval(hcTimer);
  if (hcSlideCount() > 1) hcTimer = setInterval(() => hcGo(hcIndex+1), 5000);
}
document.addEventListener('DOMContentLoaded', () => { if (hcSlideCount() > 1) hcResetTimer(); });

function toggleStoreTheme(){
  var html=document.documentElement;
  var next = html.dataset.theme==='dark' ? 'light' : 'dark';
  html.dataset.theme = next;
  localStorage.setItem('nt_theme', next);
}
document.addEventListener('DOMContentLoaded', function(){
  // Scroll-in reveal: auto-tag cards & sections, then animate on first view
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    document.querySelectorAll('.product-card,.cat-card,.section-head,.ad-banner,.mb-item,.sb-item').forEach(function(el,i){
      el.classList.add('reveal');
      el.style.transitionDelay = (Math.min(i % 8, 5) * 45) + 'ms';
    });
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){ if(en.isIntersecting){ en.target.classList.add('in'); io.unobserve(en.target); } });
    }, {rootMargin:'0px 0px -6% 0px', threshold:.06});
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  }
});
function openMobileMenu()  { document.getElementById('mobileMenu').classList.add('open'); document.body.style.overflow='hidden'; }
function openSearchOverlay(){
  const ov = document.getElementById('searchOverlay');
  ov.classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('searchOverlayInput').focus(), 50);
}
function closeSearchOverlay(){
  document.getElementById('searchOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('searchOverlay')?.addEventListener('click', function(e){
  if (e.target === this) closeSearchOverlay();
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') closeSearchOverlay();
  // Quick-open with "/" like many modern sites, unless typing in a field already
  if (e.key === '/' && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) {
    e.preventDefault();
    openSearchOverlay();
  }
});
function closeMobileMenu() { document.getElementById('mobileMenu').classList.remove('open'); document.body.style.overflow=''; }
function flyToCart(startEl) {
  const cartBtn = document.getElementById('cartBtn');
  if (!startEl || !cartBtn) return;
  const startRect = startEl.getBoundingClientRect();
  const endRect = cartBtn.getBoundingClientRect();
  const dot = document.createElement('div');
  dot.style.cssText = `position:fixed;z-index:9998;width:14px;height:14px;border-radius:50%;
    background:var(--accent);pointer-events:none;
    left:${startRect.left + startRect.width/2 - 7}px;top:${startRect.top + startRect.height/2 - 7}px;
    transition:all .55s cubic-bezier(.2,.8,.3,1);box-shadow:0 2px 8px rgba(var(--accent-rgb),.5);`;
  document.body.appendChild(dot);
  requestAnimationFrame(() => {
    dot.style.left = (endRect.left + endRect.width/2 - 5) + 'px';
    dot.style.top  = (endRect.top + endRect.height/2 - 5) + 'px';
    dot.style.width = '10px'; dot.style.height = '10px';
    dot.style.opacity = '.3';
  });
  setTimeout(() => dot.remove(), 600);
}
function updateCartBadge(count) {
  const badge = document.getElementById('cartBadge');
  const btn   = document.getElementById('cartBtn');
  if (count > 0) {
    if (!badge) { const b=document.createElement('span'); b.className='nav-badge'; b.id='cartBadge'; b.textContent=count; btn.appendChild(b); }
    else { badge.textContent = count; }
  } else if (badge) { badge.remove(); }
  // Bottom tab badge (mobile)
  const btIcon = document.querySelector('#bottomCartTab .bt-icon');
  let btBadge = document.getElementById('bottomCartBadge');
  if (count > 0 && btIcon) {
    if (!btBadge) { btBadge=document.createElement('span'); btBadge.className='bt-badge'; btBadge.id='bottomCartBadge'; btIcon.appendChild(btBadge); }
    btBadge.textContent = count;
  } else if (btBadge) { btBadge.remove(); }
  // Bump animation on the cart icon + badge
  btn.classList.remove('bump'); void btn.offsetWidth; btn.classList.add('bump');
  const freshBadge = document.getElementById('cartBadge');
  if (freshBadge) { freshBadge.classList.remove('bump'); void freshBadge.offsetWidth; freshBadge.classList.add('bump'); }
}
function addToCart(productId, variantId = null, qty = 1, btnEl = null) {
  if (!btnEl) btnEl = event?.target?.closest('.product-card-add');
  if (btnEl) { flyToCart(btnEl); btnEl.style.transform = 'scale(.8)'; btnEl.disabled = true; }
  const form = new FormData();
  form.append('product_id', productId);
  form.append('qty', qty);
  if (variantId) form.append('variant_id', variantId);
  form.append('_csrf', '<?= csrf_token() ?>');
  fetch('<?= url('cart/add') ?>', { method: 'POST', body: form })
    .then(r => r.json())
    .then(d => {
      if (btnEl) { btnEl.style.transform = ''; btnEl.disabled = false; }
      if (d.success) {
        if (btnEl) { const orig = btnEl.innerHTML; btnEl.innerHTML = '✓'; btnEl.classList.add('added'); setTimeout(() => { btnEl.innerHTML = orig; btnEl.classList.remove('added'); }, 900); }
        updateCartBadge(d.data.count);
        showToast('✅ تمت الإضافة للسلة');
      } else { showToast('⚠️ ' + (d.message || 'حدث خطأ'), 'error'); }
    })
    .catch(() => { if (btnEl) { btnEl.style.transform = ''; btnEl.disabled = false; } showToast('⚠️ حدث خطأ، حاول مرة أخرى', 'error'); });
}
function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:9999;
    background:${type==='error'?'rgba(229,52,42,.95)':'rgba(22,163,74,.95)'};
    color:#fff;padding:12px 20px;border-radius:11px;font-size:13.5px;font-weight:600;
    box-shadow:0 8px 24px rgba(0,0,0,.4);animation:fadeSlide .2s ease;white-space:nowrap;`;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2800);
}
function toggleWishlist(productId, btn) {
  const form = new FormData();
  form.append('product_id', productId);
  form.append('_csrf', '<?= csrf_token() ?>');
  fetch('<?= url('wishlist/toggle') ?>', { method: 'POST', body: form })
    .then(r => r.json())
    .then(d => { if (d.success) { btn.textContent = d.inWishlist ? '❤️' : '🤍'; showToast(d.inWishlist ? '❤️ تمت الإضافة للمفضلة' : '🤍 تم الحذف من المفضلة'); } });
}
setTimeout(() => {
  document.querySelectorAll('.flash').forEach(el => el.style.opacity = '0');
  setTimeout(() => document.querySelectorAll('.flash-container').forEach(el => el.remove()), 300);
}, 4000);
<?php if (!empty($extraScript)) echo $extraScript; ?>
</script>
<!-- Mobile bottom tab bar (app-style) -->
<?php
$_navPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$_tabs = [
    ['icon'=>'home',  'label'=>'الرئيسية', 'href'=>url(),           'active'=>$_navPath===''],
    ['icon'=>'grid',  'label'=>'المنتجات', 'href'=>url('products'), 'active'=>str_starts_with($_navPath,'products')||str_starts_with($_navPath,'collections')||str_starts_with($_navPath,'brands')],
    ['icon'=>'cart',  'label'=>'السلة',    'href'=>url('cart'),     'active'=>str_starts_with($_navPath,'cart'), 'badge'=>Cart::count()],
    ['icon'=>'shield','label'=>'الضمان',   'href'=>url('warranty'), 'active'=>str_starts_with($_navPath,'warranty')],
    ['icon'=>'user',  'label'=>'حسابي',    'href'=>isStoreLoggedIn()?url('account'):url('login'), 'active'=>str_starts_with($_navPath,'account')||str_starts_with($_navPath,'login')],
];
?>
<nav class="bottom-tabs">
  <?php foreach ($_tabs as $t): ?>
    <a href="<?= $t['href'] ?>" class="bottom-tab <?= $t['active']?'active':'' ?>" <?= $t['icon']==='cart'?'id="bottomCartTab"':'' ?>>
      <span class="bt-icon">
        <?= svgIcon($t['icon'], 21) ?>
        <?php if (!empty($t['badge'])): ?><span class="bt-badge" id="bottomCartBadge"><?= $t['badge'] ?></span><?php endif; ?>
      </span>
      <span class="bt-label"><?= $t['label'] ?></span>
    </a>
  <?php endforeach; ?>
</nav>

</body>
</html>
