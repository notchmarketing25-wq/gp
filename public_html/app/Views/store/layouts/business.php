<?php
if (!function_exists('_bs')) {
    function _bs(string $k, string $d=''): string { try { return SettingModel::get($k,$d); } catch(\Throwable $e) { return $d; } }
}
if (!function_exists('_hexRgbBiz')) {
    function _hexRgbBiz(string $hex): string {
        $hex = ltrim($hex,'#');
        if(strlen($hex)===3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        return hexdec(substr($hex,0,2)).','.hexdec(substr($hex,2,2)).','.hexdec(substr($hex,4,2));
    }
}
$_c    = _bs('primary_color','#0057FF') ?: '#0057FF';
$_rgb  = _hexRgbBiz($_c);
$_logo = _bs('store_logo');
$_user = storeUser();
$_bt   = $customer['business_type'] ?? 'none';
$_btLabel = ['merchant'=>'تاجر','distributor'=>'موزع','rep'=>'مندوب'][$_bt] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'Notch Business') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo',sans-serif;background:#F8F8F8;color:#0A0A0A;font-size:14px;line-height:1.6}
a{text-decoration:none;color:inherit}
button{font-family:inherit;cursor:pointer;border:none;background:none}
:root{
  --ac:<?= $_c ?>; --acr:<?= $_rgb ?>; --acb:rgba(<?= $_rgb ?>,.1);
  --bg:#F8F8F8;--bg1:#fff;--bg2:#fff;--bg3:#F0F0F0;--bd:#E4E4E4;--bd2:#D0D0D0;
  --t1:#0A0A0A;--t2:#737373;--t3:#B0B0B0;
  --green:#00A86B;--red:#FF3B3B;--yellow:#B87000;
  --radius:14px;--radius-s:10px;--sidebar:230px;--topbar:58px;
  --font-num:'Barlow Condensed',sans-serif;
}
.layout{display:flex;min-height:100vh}
.sidebar{width:var(--sidebar);flex-shrink:0;background:#0A0A0A;position:fixed;top:0;right:0;bottom:0;display:flex;flex-direction:column;z-index:200}
.sb-logo{height:var(--topbar);padding:0 18px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:10px}
.sb-logo img{max-height:26px}
.sb-logo-mark{width:28px;height:28px;border-radius:8px;background:var(--ac);display:flex;align-items:center;justify-content:center;font-size:14px}
.sb-logo-txt{color:#fff;font-family:var(--font-num);font-weight:800;font-size:15px}
.sb-badge{background:var(--ac);color:#fff;font-size:9px;font-weight:800;padding:2px 7px;border-radius:5px;margin-right:auto}
.sb-nav{flex:1;padding:10px 8px;overflow-y:auto}
.sb-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;font-size:13px;font-weight:600;color:rgba(255,255,255,.55);margin-bottom:2px;transition:all .15s}
.sb-link:hover{background:rgba(255,255,255,.06);color:#fff}
.sb-link.on{background:rgba(<?= $_rgb ?>,.2);color:#fff}
.sb-foot{padding:12px 8px;border-top:1px solid rgba(255,255,255,.08)}
.sb-user{display:flex;align-items:center;gap:9px;padding:9px 12px}
.sb-av{width:30px;height:30px;border-radius:50%;background:var(--ac);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff}
.sb-uname{font-size:12.5px;font-weight:700;color:#fff}
.sb-urole{font-size:10.5px;color:rgba(255,255,255,.4)}
.sb-exit{margin-right:auto;color:rgba(255,255,255,.4);font-size:16px}
.main{margin-right:var(--sidebar);flex:1;min-width:0}
.topbar{height:var(--topbar);background:#fff;border-bottom:1px solid var(--bd);display:flex;align-items:center;padding:0 26px;position:sticky;top:0;z-index:100}
.topbar-logo{display:none}
.topbar-logo img{max-height:24px}
.topbar-logo-mark{width:26px;height:26px;border-radius:7px;background:var(--ac);color:#fff;display:flex;align-items:center;justify-content:center}
@media(max-width:900px){.topbar-logo{display:flex;align-items:center;margin-left:10px;padding-left:10px;border-left:1px solid var(--bd)}}
.topbar-title{font-weight:700;font-size:15px}
.page{padding:24px 26px}
.card{background:#fff;border:1px solid var(--bd);border-radius:var(--radius);overflow:hidden}
.card-header{padding:15px 20px;border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between}
.card-title{font-size:14px;font-weight:700}
.card-body{padding:20px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.stat{background:#fff;border:1px solid var(--bd);border-radius:var(--radius);padding:18px}
.stat-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:10px}
.stat-value{font-family:var(--font-num);font-size:24px;font-weight:900}
.stat-label{font-size:11px;color:var(--t2);margin-top:3px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 18px;border-radius:9px;font-size:13px;font-weight:700;transition:all .15s;cursor:pointer}
.btn-primary{background:var(--ac);color:#fff}
.btn-primary:hover{filter:brightness(1.1)}
.btn-secondary{background:#F0F0F0;color:#0A0A0A;border:1px solid var(--bd)}
.btn-danger{background:#FFF0F0;color:var(--red)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-full{width:100%}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
.badge-ok{background:#E8FBF4;color:var(--green)}
.badge-err{background:#FFF0F0;color:var(--red)}
.badge-warn{background:#FFF8E8;color:var(--yellow)}
.badge-dim{background:#F0F0F0;color:var(--t2)}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--t2);margin-bottom:6px}
.form-label span{color:var(--red)}
.form-input,.form-select,.form-textarea{width:100%;background:#fff;border:1px solid var(--bd);border-radius:9px;color:var(--t1);padding:10px 13px;font-size:13px;font-family:inherit;outline:none}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--ac);box-shadow:0 0 0 3px var(--acb)}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{padding:9px 18px;font-size:10.5px;font-weight:700;color:var(--t2);text-transform:uppercase;text-align:right;border-bottom:1px solid var(--bd);background:var(--bg3)}
tbody tr{border-bottom:1px solid var(--bd)}
tbody tr:last-child{border-bottom:none}
td{padding:11px 18px;font-size:13px}
.flash{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:16px}
.flash-ok{background:#E8FBF4;color:var(--green);border:1px solid rgba(0,168,107,.2)}
.flash-err{background:#FFF0F0;color:var(--red);border:1px solid rgba(255,59,59,.2)}
@media(max-width:900px){
  .sidebar{transform:translateX(110%);transition:transform .25s cubic-bezier(.4,0,.2,1);z-index:300;box-shadow:-10px 0 30px rgba(0,0,0,.3)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-right:0}
  .stats{grid-template-columns:1fr 1fr}
  .topbar-ham{display:flex !important}
}
.topbar-ham{display:none;align-items:center;justify-content:center;width:36px;height:36px;border-radius:9px;background:var(--bg3);color:var(--t1);font-size:18px;margin-left:12px;flex-shrink:0}
.sb-close-mobile{display:none;color:rgba(255,255,255,.5);font-size:22px;line-height:1;margin-right:8px}
@media(max-width:900px){.sb-close-mobile{display:block}}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:250}
.sidebar-overlay.open{display:block}
@media(max-width:600px){
  .page{padding:16px}
  .stats{grid-template-columns:1fr}
  h1{font-size:20px !important}
}
/* ── Modern skin 2.0 ── */
:root{--ease-out:cubic-bezier(.16,1,.3,1)}
.btn{border-radius:100px !important;transition:all .25s var(--ease-out)}
.btn:active{transform:scale(.97)}
.btn-primary{box-shadow:0 6px 20px rgba(var(--ac-rgb,0,87,255),.25)}
.btn-primary:hover{transform:translateY(-1px)}
.card{border-radius:18px;border-color:transparent;box-shadow:0 1px 3px rgba(0,0,0,.05),0 6px 18px rgba(0,0,0,.05);transition:box-shadow .3s var(--ease-out)}
.card:hover{box-shadow:0 2px 6px rgba(0,0,0,.06),0 12px 32px rgba(0,0,0,.08)}
.form-input,.form-select,textarea{border-radius:12px !important;transition:border-color .2s,box-shadow .2s}
.form-input:focus,.form-select:focus,textarea:focus{border-color:var(--ac);box-shadow:0 0 0 4px rgba(0,87,255,.1);outline:none}
.badge{border-radius:100px}
.stat-card,.stats > div{border-radius:16px}
table th{font-size:11px;letter-spacing:.04em;text-transform:uppercase}
table tr{transition:background .15s}
@media(max-width:600px){
  .card-body{padding:14px}
  table{font-size:12px}
  td,th{padding:8px 10px !important}
}
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sb-logo">
      <?php if (logoUrl('dark')): ?><img src="<?= logoUrl('dark') ?>"><?php else: ?><div class="sb-logo-mark">⚡</div><div class="sb-logo-txt">Business</div><?php endif; ?>
      <div class="sb-badge"><?= e($_btLabel) ?></div>
      <button class="sb-close-mobile" onclick="closeBizSidebar()" aria-label="إغلاق">×</button>
    </div>
    <nav class="sb-nav">
      <?php $path = trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/'); ?>
      <a href="<?= url('business') ?>" class="sb-link <?= $path==='business'?'on':'' ?>"><?= svgIcon('home',15) ?> الرئيسية</a>
      <a href="<?= url('business/orders') ?>" class="sb-link <?= str_starts_with($path,'business/orders')&&$path!=='business/orders/create'?'on':'' ?>"><?= svgIcon('package',15) ?> طلباتي وحالتها</a>
      <a href="<?= url('business/products') ?>" class="sb-link <?= str_starts_with($path,'business/products')?'on':'' ?>"><?= svgIcon('grid',15) ?> المنتجات وأسعاري</a>
      <a href="<?= url('business/orders/create') ?>" class="sb-link <?= str_starts_with($path,'business/orders')?'on':'' ?>"><?= svgIcon('plus',15) ?> طلب جديد</a>
      <a href="<?= url('business/catalog') ?>" class="sb-link"><?= svgIcon('package',15) ?> تحميل الكاتالوج (Excel)</a>
      <a href="<?= url('business/catalog/print') ?>" target="_blank" class="sb-link"><?= svgIcon('package',15) ?> كاتالوج للطباعة (PDF)</a>
      <a href="<?= url('business/credit') ?>" class="sb-link <?= str_starts_with($path,'business/credit')?'on':'' ?>"><?= svgIcon('star',15) ?> N&Credit</a>
      <?php if ($_bt === 'rep'): ?>
        <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,.3);letter-spacing:.08em;padding:16px 12px 6px;text-transform:uppercase">أدوات المندوب</div>
        <a href="<?= url('business/expenses') ?>" class="sb-link <?= str_starts_with($path,'business/expenses')?'on':'' ?>"><?= svgIcon('truck',15) ?> مصروفات المواصلات</a>
        <a href="<?= url('business/samples') ?>" class="sb-link <?= str_starts_with($path,'business/samples')?'on':'' ?>"><?= svgIcon('package',15) ?> طلب عينات</a>
        <a href="<?= url('business/add-merchant') ?>" class="sb-link <?= str_starts_with($path,'business/add-merchant')?'on':'' ?>"><?= svgIcon('plus',15) ?> تسجيل تاجر جديد</a>
      <?php endif; ?>
    </nav>
    <div class="sb-foot">
      <div class="sb-user">
        <div class="sb-av"><?= mb_substr($_user['name']??'B',0,1) ?></div>
        <div><div class="sb-uname"><?= e($_user['name']??'') ?></div><div class="sb-urole"><?= e($customer['shop_name'] ?? '') ?></div></div>
        <a href="<?= url('logout') ?>" class="sb-exit">⎋</a>
      </div>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <button class="topbar-ham" onclick="openBizSidebar()" aria-label="القائمة"><?= svgIcon('menu', 20) ?></button>
      <div class="topbar-logo">
        <?php if (logoUrl('light')): ?><img src="<?= logoUrl('light') ?>" alt="<?= e(SettingModel::get('store_name','')) ?>"><?php else: ?><div class="topbar-logo-mark"><?= svgIcon('briefcase', 15) ?></div><?php endif; ?>
      </div>
      <div class="topbar-title"><?= e($pageTitle ?? '') ?></div>
    </header>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeBizSidebar()"></div>
    <main class="page">
      <?php if ($s = flash('success')): ?><div class="flash flash-ok">✅ <?= e($s) ?></div><?php endif; ?>
      <?php if ($e = flash('error')): ?><div class="flash flash-err">⚠️ <?= e($e) ?></div><?php endif; ?>
      <?php if (!empty($content)) echo $content; ?>
    </main>
  </div>
</div>
<script>
function openBizSidebar(){
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebarOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeBizSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
// Generic mobile fix: any inline 2-column grid with a fixed-px sidebar column
// (e.g. "1fr 320px") collapses to a single column below 900px, and restores on desktop.
function applyBizMobileGridFix(){
  const isMobile = window.innerWidth <= 900;
  document.querySelectorAll('[style*="grid-template-columns"]').forEach(el=>{
    const gtc = el.style.gridTemplateColumns;
    if (!gtc || !/\d+px/.test(gtc)) return;
    if (isMobile) {
      if (!el.dataset.mgfOriginal) el.dataset.mgfOriginal = gtc;
      el.style.gridTemplateColumns = '1fr';
    } else if (el.dataset.mgfOriginal) {
      el.style.gridTemplateColumns = el.dataset.mgfOriginal;
      delete el.dataset.mgfOriginal;
    }
  });
}
window.addEventListener('resize', applyBizMobileGridFix);
document.addEventListener('DOMContentLoaded', applyBizMobileGridFix);
</script>
</body>
</html>
