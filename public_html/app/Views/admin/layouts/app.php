<?php
if (!function_exists('_st')) {
    function _st(string $k, string $d = ''): string {
        try { return SettingModel::get($k, $d); } catch (\Throwable $e) { return $d; }
    }
}
if (!function_exists('_au')) {
    function _au(): ?array {
        try { return Session::get('admin_user'); } catch (\Throwable $e) { return null; }
    }
}
if (!function_exists('_hexRgb')) {
    function _hexRgb(string $hex): string {
        $hex = ltrim($hex,'#');
        if(strlen($hex)===3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        return hexdec(substr($hex,0,2)).','.hexdec(substr($hex,2,2)).','.hexdec(substr($hex,4,2));
    }
}
if (!function_exists('nav_active')) {
    function nav_active(string $match, string $uri): string {
        return preg_match('#'.$match.'#', $uri) ? 'active' : '';
    }
}
$_name  = _st('store_name', defined('APP_NAME') ? APP_NAME : 'Notch');
$_user  = _au() ?? ['name'=>'Admin','role'=>''];
$_pfx   = defined('ADMIN_PREFIX') ? ADMIN_PREFIX : 'admin';
$_uri   = $_SERVER['REQUEST_URI'] ?? '';
$_pend  = 0;
try { $_pend = (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE status='pending'")['c'] ?? 0); } catch (\Throwable $e) {}
$_shipping = 0;
try { $_shipping = (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE status='shipped'")['c'] ?? 0); } catch (\Throwable $e) {}
$_lowstock = 0;
try { $_lowstock = (int)(Database::fetch("SELECT COUNT(*) c FROM products WHERE stock<=5 AND track_stock=1 AND status='active'")['c'] ?? 0); } catch (\Throwable $e) {}
$_wsPending = 0;
try { $_wsPending = (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE account_type='wholesale' AND wholesale_status='pending'")['c'] ?? 0); } catch (\Throwable $e) {}
$_logo  = _st('store_logo');
$_c     = _st('primary_color','#0057FF') ?: '#0057FF';
$_rgb   = _hexRgb($_c);
$_dark  = _st('theme_mode','light') === 'dark';
$_fav   = _st('store_favicon');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $_dark?'dark':'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'لوحة التحكم') ?> · <?= htmlspecialchars($_name) ?></title>
<?php if ($_fav): ?>
  <link rel="icon" href="<?= uploadUrl($_fav) ?>">
<?php else: ?>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{height:100%;-webkit-text-size-adjust:100%}
body{font-family:'Cairo',system-ui,sans-serif;background:var(--bg);color:var(--t1);font-size:14px;line-height:1.6;min-height:100%;direction:rtl;transition:background .2s,color .2s}
a{text-decoration:none;color:inherit}
button{font-family:inherit;cursor:pointer;border:none;background:none}
img{display:block;max-width:100%}
input,select,textarea{font-family:inherit}
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--bd2);border-radius:10px}

/* ── TOKENS ── */
:root{
  --ac:    <?= $_c ?>;
  --acr:   <?= $_rgb ?>;
  --acb:   rgba(<?= $_rgb ?>,.1);
  --acb2:  rgba(<?= $_rgb ?>,.18);
  --green: #00A86B; --red:#FF3B3B; --yellow:#B87000; --blue:#0057FF; --purple:#7B2FBE; --orange:#FF6B35;
  --radius:8px; --radius-s:8px; --radius-xs:8px;
  --sidebar:240px; --topbar:60px;
  --font-num:'Barlow Condensed',sans-serif;
  --shadow-sm:0 1px 2px rgba(0,0,0,.04);
  --shadow:0 1px 2px rgba(0,0,0,.04);
  --shadow-lg:0 8px 24px rgba(0,0,0,.14);
}
[data-theme="light"]{
  --bg:#F1F2F4; --bg1:#ffffff; --bg2:#ffffff; --bg3:#F1F2F4;
  --bd:#D4D6D9; --bd2:#B8BABE;
  --t1:#1A1C1D; --t2:#5C5F62; --t3:#8C9196;
  --sidebar-bg:#0f0f0f; --sidebar-bd:rgba(255,255,255,.07);
}
[data-theme="dark"]{
  --bg:#0A0A0A; --bg1:#131315; --bg2:#17171a; --bg3:#1e1e22;
  --bd:#28282e; --bd2:#34343c;
  --t1:#f2f2f5; --t2:#9a9aa5; --t3:#55555f;
  --sidebar-bg:#000000; --sidebar-bd:rgba(255,255,255,.07);
}

/* ══════════ LAYOUT ══════════ */
.layout{display:flex;min-height:100vh}

/* ── SIDEBAR (always dark — brand identity) ── */
.sidebar{
  width:var(--sidebar);flex-shrink:0;
  background:var(--sidebar-bg);
  position:fixed;top:0;right:0;bottom:0;
  display:flex;flex-direction:column;
  z-index:300;overflow:hidden;
  transition:transform .25s cubic-bezier(.4,0,.2,1);
}
.sb-logo{
  height:var(--topbar);min-height:var(--topbar);
  padding:0 18px;
  border-bottom:1px solid var(--sidebar-bd);
  display:flex;align-items:center;gap:10px;
  flex-shrink:0;
}
.sb-logo img{max-height:26px;max-width:140px;object-fit:contain}
.sb-logo-icon{
  width:28px;height:28px;border-radius:8px;flex-shrink:0;
  background:var(--ac);
  display:flex;align-items:center;justify-content:center;font-size:14px;
}
.sb-logo-name{font-family:var(--font-num);font-size:16px;font-weight:800;color:#fff;letter-spacing:.01em}
.sb-badge{background:var(--ac);color:#fff;font-size:9px;font-weight:800;padding:2px 7px;border-radius:5px;letter-spacing:.06em;text-transform:uppercase;margin-right:auto}

.sb-nav{flex:1;overflow-y:auto;padding:8px;scrollbar-width:none}
.sb-nav::-webkit-scrollbar{display:none}
.sb-sep{font-size:9.5px;font-weight:800;color:rgba(255,255,255,.25);letter-spacing:.12em;text-transform:uppercase;padding:16px 12px 6px}
.sb-link{
  display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:9px;
  font-size:13px;font-weight:600;color:rgba(255,255,255,.55);
  margin-bottom:1px;cursor:pointer;
  transition:background .15s,color .15s;position:relative;
}
.sb-link:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.9)}
.sb-link.on{background:var(--acb2);color:#fff}
.sb-link.on::before{
  content:'';position:absolute;right:-8px;top:50%;transform:translateY(-50%);
  width:3px;height:20px;border-radius:3px 0 0 3px;background:var(--ac);
}
.sb-ico{font-size:15px;width:20px;text-align:center;flex-shrink:0;opacity:.9}
.sb-badge-count{margin-right:auto;background:var(--ac);color:#fff;font-size:10px;font-weight:800;padding:1px 7px;border-radius:10px;min-width:19px;text-align:center}
.sb-badge-count.orange{background:var(--orange)}
.sb-badge-count.red{background:var(--red)}

.sb-foot{padding:12px 8px;border-top:1px solid var(--sidebar-bd);flex-shrink:0}
.sb-user{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;transition:background .15s;cursor:default}
.sb-user:hover{background:rgba(255,255,255,.06)}
.sb-av{width:30px;height:30px;border-radius:50%;background:var(--ac);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0}
.sb-uname{font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-urole{font-size:11px;color:rgba(255,255,255,.35)}
.sb-exit{margin-right:auto;color:rgba(255,255,255,.35);font-size:16px;transition:color .15s;padding:2px}
.sb-exit:hover{color:var(--red)}

/* ── MAIN ── */
.main{margin-right:var(--sidebar);flex:1;min-width:0;display:flex;flex-direction:column;min-height:100vh}

/* ── TOPBAR ── */
.topbar{
  height:var(--topbar);background:var(--bg1);
  border-bottom:1px solid var(--bd);
  padding:0 28px;display:flex;align-items:center;gap:14px;
  position:sticky;top:0;z-index:200;
}
.topbar-ham{display:none;color:var(--t2);font-size:20px;padding:4px}
.tt-sun{display:none}.tt-moon{display:flex}
[data-theme="dark"] .tt-sun{display:flex}
[data-theme="dark"] .tt-moon{display:none}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--t3)}
.breadcrumb a{color:var(--t2);transition:color .1s}
.breadcrumb a:hover{color:var(--t1)}
.bc-sep{color:var(--bd2)}
.bc-cur{color:var(--t1);font-weight:700}
.tb-right{margin-right:auto;display:flex;align-items:center;gap:8px}
.tb-btn{
  display:flex;align-items:center;gap:6px;height:36px;padding:0 14px;
  border-radius:var(--radius-xs);background:var(--black,#0A0A0A);color:#fff;
  font-size:12.5px;font-weight:700;transition:background .15s;cursor:pointer;
}
.tb-btn:hover{background:var(--ac)}
.tb-theme{width:36px;height:36px;padding:0;justify-content:center;background:var(--bg3);color:var(--t2);font-size:15px;border-radius:var(--radius-xs)}
.tb-theme:hover{background:var(--ac);color:#fff}

/* ── PAGE ── */
.page{padding:24px 28px;flex:1}

/* ── FLASH ── */
.flash{display:flex;align-items:center;gap:10px;padding:11px 15px;border-radius:var(--radius-s);font-size:13px;font-weight:600;margin-bottom:16px;animation:slideDown .2s ease}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
.flash-ok {background:#E8FBF4;border:1px solid rgba(0,168,107,.2);color:var(--green)}
.flash-err{background:#FFF0F0;border:1px solid rgba(255,59,59,.2);color:var(--red)}
.flash-x{margin-right:auto;opacity:.5;font-size:18px;line-height:1;transition:opacity .1s}
.flash-x:hover{opacity:1}

/* ══ COMPONENTS ══ */
.ph{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:22px;flex-wrap:wrap}
.ph h1{font-family:var(--font-num);font-size:26px;font-weight:800;color:var(--t1);letter-spacing:.01em;text-transform:uppercase}
.ph p,.ph-sub{font-size:12px;color:var(--t3);margin-top:3px}
.ph-right,.ph-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.ph-left h1{font-family:var(--font-num);font-size:26px;font-weight:800;color:var(--t1);text-transform:uppercase}
.ph-left p{font-size:12px;color:var(--t3);margin-top:3px}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:22px;flex-wrap:wrap}
.page-header-left h1{font-family:var(--font-num);font-size:26px;font-weight:800;color:var(--t1);text-transform:uppercase}
.page-header-left p{font-size:12px;color:var(--t3);margin-top:3px}
.page-header-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}

.card{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--radius);overflow:hidden}
.card-header{padding:16px 20px 14px;border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between;gap:10px}
.card-title{font-size:14px;font-weight:700;color:var(--t1)}
.card-body{padding:20px}

.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--radius);padding:20px;transition:box-shadow .2s;cursor:default;display:flex;flex-direction:column;gap:10px}
.stat:hover{box-shadow:var(--shadow)}
.stat-top{display:flex;align-items:center;justify-content:space-between}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px}
.stat-label{font-size:11px;color:var(--t3);text-transform:uppercase;letter-spacing:.05em;font-weight:600}
.stat-value{font-family:var(--font-num);font-size:28px;font-weight:900;letter-spacing:-.01em;color:var(--t1);line-height:1}
.stat-sub{font-size:11px;color:var(--t3)}
.stat-change{font-size:10.5px;font-weight:700;display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:20px}
.stat-change.up{background:#E8FBF4;color:var(--green)}
.stat-change.down{background:#FFF0F0;color:var(--red)}
.stat-change.neutral{background:var(--bg3);color:var(--t3)}
.stat-bar{height:3px;background:var(--bg3);border-radius:2px;overflow:hidden}
.stat-bar-fill{height:100%;border-radius:2px;background:var(--ac)}

.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{padding:9px 18px;font-size:10.5px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.05em;text-align:right;border-bottom:1px solid var(--bd);white-space:nowrap;background:var(--bg3)}
tbody tr{border-bottom:1px solid var(--bd);transition:background .1s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:var(--bg3)}
td{padding:11px 18px;font-size:13px;color:var(--t1);vertical-align:middle}
.td-dim{color:var(--t2)}
.td-mono{font-family:'SF Mono',Consolas,monospace;font-size:12px;direction:ltr;unicode-bidi:embed}

.prod-cell{display:flex;align-items:center;gap:10px}
.prod-img{width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid var(--bd);background:var(--bg3);flex-shrink:0}
.prod-img-ph{width:36px;height:36px;border-radius:8px;border:1px solid var(--bd);background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.prod-name{font-size:13px;font-weight:600;color:var(--t1);unicode-bidi:plaintext}
.prod-sku{font-size:11px;color:var(--t3);margin-top:2px;font-family:monospace;direction:ltr;unicode-bidi:embed}

.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}
.badge-ok    {background:#E8FBF4;color:var(--green)}
.badge-err   {background:#FFF0F0;color:var(--red)}
.badge-warn  {background:#FFF8E8;color:var(--yellow)}
.badge-info  {background:#E8F0FF;color:var(--blue)}
.badge-purple{background:#F0E8FF;color:var(--purple)}
.badge-dim   {background:var(--bg3);color:var(--t2)}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 18px;border-radius:var(--radius-xs);font-size:13px;font-weight:700;border:1px solid transparent;transition:all .15s;cursor:pointer;white-space:nowrap}
.btn-primary{background:var(--ac);color:#fff;border-color:var(--ac)}
.btn-primary:hover{filter:brightness(1.1)}
.btn-secondary{background:var(--bg3);color:var(--t1);border-color:var(--bd)}
.btn-secondary:hover{border-color:var(--bd2)}
.btn-danger{background:#FFF0F0;color:var(--red);border-color:rgba(255,59,59,.2)}
.btn-danger:hover{background:var(--red);color:#fff;border-color:var(--red)}
.btn-ghost{background:transparent;color:var(--t2);border-color:transparent}
.btn-ghost:hover{background:var(--bg3);color:var(--t1)}
.btn-sm{padding:6px 12px;font-size:12px;border-radius:8px}
.btn-xs{padding:3px 8px;font-size:11px;border-radius:6px}
.btn-full{width:100%}
.btn-icon{width:34px;height:34px;padding:0;border-radius:var(--radius-xs)}

.form-grid{display:grid;gap:16px}
.form-row-2{grid-template-columns:1fr 1fr}
.form-row-3{grid-template-columns:1fr 1fr 1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-label{font-size:12px;font-weight:600;color:var(--t2)}
.form-label .req,.form-label span{color:var(--red)}
.form-hint{font-size:11px;color:var(--t3);line-height:1.5}
.form-input,.form-select,.form-textarea{background:var(--bg1);border:1px solid var(--bd);border-radius:var(--radius-xs);color:var(--t1);padding:9px 12px;font-size:13px;font-family:inherit;outline:none;width:100%;transition:border-color .15s,box-shadow .15s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--ac);box-shadow:0 0 0 3px rgba(<?= $_rgb ?>,.12)}
.form-input::placeholder,.form-textarea::placeholder{color:var(--t3)}
.form-textarea{resize:vertical;min-height:90px;line-height:1.6}
.form-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23999' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 10px center;padding-left:32px}
.toggle-wrap{display:flex;align-items:center;gap:10px}
.toggle{position:relative;width:38px;height:21px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0;position:absolute}
.toggle-track,.toggle-slider{position:absolute;inset:0;background:var(--bd2);border-radius:21px;cursor:pointer;transition:background .2s}
.toggle-track::before,.toggle-slider::before{content:'';position:absolute;width:15px;height:15px;right:3px;top:3px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.toggle input:checked + .toggle-track,.toggle input:checked + .toggle-slider{background:var(--ac)}
.toggle input:checked + .toggle-track::before,.toggle input:checked + .toggle-slider::before{transform:translateX(-17px)}

.filter-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.search-box{position:relative;flex:1;min-width:180px}
.search-box svg{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:var(--t3);pointer-events:none}
.search-input{width:100%;background:var(--bg1);border:1px solid var(--bd);border-radius:var(--radius-xs);color:var(--t1);padding:8px 34px 8px 12px;font-size:13px;outline:none;transition:border-color .15s}
.search-input:focus{border-color:var(--ac)}
.search-input::placeholder{color:var(--t3)}
.filter-select{background:var(--bg1);border:1px solid var(--bd);border-radius:var(--radius-xs);color:var(--t2);padding:8px 12px;font-size:13px;outline:none;cursor:pointer;transition:border-color .15s}
.filter-select:focus{border-color:var(--ac)}

.pagination{display:flex;justify-content:center;gap:4px;padding:16px}
.page-btn{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius-xs);font-size:12px;font-weight:600;background:var(--bg3);border:1px solid var(--bd);color:var(--t2);transition:all .15s;text-decoration:none;cursor:pointer}
.page-btn:hover{border-color:var(--ac);color:var(--ac)}
.page-btn.active{background:var(--ac);border-color:var(--ac);color:#fff}

.empty{text-align:center;padding:56px 20px}
.empty-icon{font-size:48px;margin-bottom:16px;opacity:.5}
.empty-title{font-size:16px;font-weight:700;color:var(--t1);margin-bottom:6px}
.empty-desc{font-size:13px;color:var(--t3);margin-bottom:20px}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:600;align-items:center;justify-content:center;backdrop-filter:blur(4px);padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--bg2);border:1px solid var(--bd);border-radius:16px;padding:28px;width:100%;max-width:420px;max-height:calc(100vh - 40px);overflow-y:auto;animation:popIn .18s ease;box-shadow:var(--shadow-lg)}
.modal-lg{max-width:760px}
.modal-xl{max-width:920px}
.modal-head{position:sticky;top:-28px;background:var(--bg2);z-index:2;margin:-28px -28px 18px;padding:22px 28px 14px;display:flex;align-items:center;justify-content:space-between}
@keyframes popIn{from{opacity:0;transform:scale(.94) translateY(8px)}to{opacity:1;transform:none}}
.modal-head h3{font-size:16px;font-weight:700;color:var(--t1)}
.modal-icon{font-size:36px;margin-bottom:12px}
.modal p{font-size:13px;color:var(--t2);margin-bottom:22px}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin:22px -28px -28px;padding:16px 28px;position:sticky;bottom:-28px;background:var(--bg2);border-top:1px solid var(--bd)}

.tab-bar{display:flex;gap:2px;border-bottom:1px solid var(--bd);margin-bottom:22px;overflow-x:auto}
.tab-btn{padding:9px 18px;font-size:13px;font-weight:600;color:var(--t2);background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:color .15s,border-color .15s;margin-bottom:-1px;white-space:nowrap}
.tab-btn:hover{color:var(--t1)}
.tab-btn.active{color:var(--ac);border-bottom-color:var(--ac);font-weight:700}

.divider{height:1px;background:var(--bd);margin:20px 0}
.img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px}
.img-thumb{aspect-ratio:1;border-radius:var(--radius-xs);overflow:hidden;border:2px solid var(--bd);cursor:pointer;transition:border-color .15s;position:relative}
.img-thumb img,.img-thumb video{width:100%;height:100%;object-fit:cover}
.img-thumb:hover{border-color:var(--ac)}
.img-thumb.selected{border-color:var(--ac)}
.img-thumb.selected::after{content:'✓';position:absolute;top:4px;left:4px;background:var(--ac);color:#fff;width:18px;height:18px;border-radius:50%;font-size:11px;display:flex;align-items:center;justify-content:center;font-weight:700}

/* ── Dashboard-specific: mini chart, donut, activity, quick actions ── */
.mini-chart{padding:0 4px;display:flex;align-items:flex-end;gap:4px;height:80px}
.mc-bar{flex:1;border-radius:3px 3px 0 0;transition:opacity .2s;cursor:pointer;background:var(--bd2)}
.mc-bar:hover{opacity:.75}
.qa-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.qa-btn{background:var(--bg3);border:1px solid var(--bd);border-radius:10px;padding:12px;display:flex;align-items:center;gap:8px;cursor:pointer;transition:all .15s;font-size:12.5px;font-weight:600;color:var(--t1)}
.qa-btn:hover{border-color:var(--ac);background:var(--acb);color:var(--ac)}
.donut-wrap{display:flex;align-items:center;gap:20px}
.donut-legend{display:flex;flex-direction:column;gap:8px;flex:1}
.donut-leg-item{display:flex;align-items:center;gap:8px;font-size:12px}
.donut-leg-dot{width:10px;height:10px;border-radius:2px;flex-shrink:0}
.donut-leg-label{color:var(--t2);flex:1}
.donut-leg-val{font-weight:700;color:var(--t1)}
.ship-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--bd)}
.ship-item:last-child{border-bottom:none}
.ship-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px}
.ship-progress{margin-top:6px;height:2px;background:var(--bg3);border-radius:2px;overflow:hidden}
.ship-progress-fill{height:100%;border-radius:2px}

@media(max-width:960px){
  .sidebar{transform:translateX(110%)}
  .sidebar.open{transform:translateX(0);box-shadow:-8px 0 32px rgba(0,0,0,.3)}
  .main{margin-right:0}
  .topbar-ham{display:flex}
  .stats{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:600px){
  .stats{grid-template-columns:1fr 1fr}
  .page{padding:14px}
  .form-row-2,.form-row-3{grid-template-columns:1fr}
  /* Mobile keeps a plain white page background instead of the slightly warmer Polaris gray —
     on a small screen the extra contrast reads as "dingy" rather than purposeful, since there's
     less surrounding card/border structure visible at once to justify it. Scoped to light theme
     only so dark mode on mobile isn't affected. */
  [data-theme="light"]{--bg:#ffffff}
}

/* ── Polaris-inspired admin skin — flat, border-driven, sharp corners instead of
   pills/soft-shadows. Color reserved for active/status states only. ── */
:root{--ease-out:cubic-bezier(.16,1,.3,1)}
.btn{border-radius:var(--radius-xs) !important;transition:background .12s,border-color .12s;box-shadow:none}
.btn-primary{box-shadow:0 1px 0 rgba(0,0,0,.05)}
.btn-primary:hover{filter:brightness(1.06)}
.btn-secondary{background:var(--bg2);border-color:var(--bd2) !important}
.btn-secondary:hover{background:var(--bg3)}
.card{border-radius:var(--radius);box-shadow:none;border:1px solid var(--bd)}
.form-input,.form-select,.form-textarea{border-radius:var(--radius-xs);transition:border-color .15s,box-shadow .15s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--ac);box-shadow:0 0 0 1px var(--ac);outline:none}
.badge{border-radius:100px}
.modal{border-radius:var(--radius);box-shadow:0 8px 24px rgba(0,0,0,.16)}
table tbody tr{transition:background .1s}
.sb-link{border-radius:var(--radius-xs);transition:background .12s}
.stat{border-radius:var(--radius);box-shadow:none !important;border:1px solid var(--bd)}
.stat:hover{border-color:var(--bd2);box-shadow:none !important}
/* Page header — bolder, denser type matching Polaris's "Page" component */
.ph h1{font-weight:700;letter-spacing:-.01em}
thead th{background:var(--bg3);font-weight:600}
@media(max-width:600px){
  td,th{padding:8px 9px !important;font-size:12px}
  .ph h1{font-size:19px}
  .ph{flex-wrap:wrap;gap:10px}
  .ph-right{width:100%;display:flex;gap:8px;flex-wrap:wrap}
  .ph-right .btn{flex:1;justify-content:center;min-width:120px}
}
.bm-dropzone.drag{border-color:var(--ac) !important;background:var(--acb2) !important}
#bmMediaImageLabel:has(#bmMediaImage:checked),#bmMediaVideoLabel:has(#bmMediaVideo:checked){border-color:var(--ac) !important;background:var(--acb2) !important}/* Channel tabs (products list) — Polaris-style: flat neutral tabs, color reserved for the
   active state only, never used as decoration. */
.chan-tabs{display:flex;gap:2px;padding:0 14px;border-bottom:1px solid var(--bd);overflow-x:auto}
.chan-tab{display:flex;align-items:center;gap:6px;padding:12px 14px;font-size:13px;font-weight:600;color:var(--t2);border-bottom:2px solid transparent;white-space:nowrap;transition:color .15s,border-color .15s}
.chan-tab:hover{color:var(--t1)}
.chan-tab.active{color:var(--ac);border-color:var(--ac)}
.chan-tab-count{background:var(--bg3);color:var(--t3);border-radius:100px;padding:1px 8px;font-size:11px;font-weight:700}
.chan-tab.active .chan-tab-count{background:var(--acb);color:var(--ac)}
</style>
<?php if(!empty($extraHead)) echo $extraHead; ?>
</head>
<body>

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal">
    <div class="modal-icon">🗑️</div>
    <h3 id="confirm-title">هل أنت متأكد؟</h3>
    <p id="confirm-desc">لا يمكن التراجع عن هذه العملية.</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeConfirm()">إلغاء</button>
      <form id="confirm-form" method="POST" style="display:inline">
        <input type="hidden" name="_csrf" value="<?php try{echo Session::csrf();}catch(\Throwable $e){} ?>">
        <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
      </form>
    </div>
  </div>
</div>

<!-- MEDIA LIBRARY MODAL -->
<div class="modal-overlay modal-lg" id="media-modal">
  <div class="modal modal-lg" style="max-height:90vh;display:flex;flex-direction:column">
    <div class="modal-head">
      <h3>📸 مكتبة الصور</h3>
      <button class="btn btn-ghost btn-icon" onclick="closeMedia()" style="font-size:18px">×</button>
    </div>
    <div style="display:flex;gap:10px;margin-bottom:14px;flex-shrink:0">
      <label class="btn btn-primary" style="cursor:pointer">
        📤 رفع صورة جديدة
        <input type="file" id="mediaUploadInput" accept="image/*" multiple style="display:none" onchange="uploadMediaFiles(this)">
      </label>
      <input type="text" id="mediaSearch" class="form-input" placeholder="بحث..." style="max-width:200px" oninput="filterMedia(this.value)">
    </div>
    <div id="mediaGrid" class="img-grid" style="flex:1;overflow-y:auto;align-content:start"></div>
    <div class="modal-actions" style="flex-shrink:0">
      <button class="btn btn-secondary" onclick="closeMedia()">إلغاء</button>
      <button class="btn btn-primary" id="mediaConfirm" onclick="confirmMedia()" disabled>اختيار</button>
    </div>
  </div>
</div>

<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sb-logo">
    <?php if (logoUrl('dark')): ?>
      <img src="<?= logoUrl('dark') ?>" alt="<?= e($_name) ?>">
    <?php else: ?>
      <div class="sb-logo-icon">⚡</div>
      <div class="sb-logo-name"><?= e($_name) ?></div>
    <?php endif; ?>
    <div class="sb-badge">Admin</div>
  </div>

  <nav class="sb-nav">
    <?php
    $pfx = $_pfx;
    $nav = [
      ['sep'=>'الرئيسية'],
      ['url'=>"/$pfx",           'ico'=>'home','lbl'=>'لوحة التحكم',  'pat'=>"/$pfx(/dashboard)?$"],
      ['url'=>"/$pfx/orders",     'ico'=>'cart','lbl'=>'الطلبات',     'pat'=>"/$pfx/orders",'badge'=>$_pend,'module'=>'orders'],
      ['url'=>"/$pfx/shipping",   'ico'=>'truck','lbl'=>'الشحنات',     'pat'=>"/$pfx/shipping",'module'=>'orders'],
      ['url'=>"/$pfx/products",   'ico'=>'package','lbl'=>'المنتجات',    'pat'=>"/$pfx/products",'module'=>'products'],
      ['url'=>"/$pfx/warehouse",  'ico'=>'package','lbl'=>'المخزن',      'pat'=>"/$pfx/warehouse",'badge'=>$_lowstock,'badgeColor'=>'red','module'=>'products'],
      ['url'=>"/$pfx/inventory",  'ico'=>'receipt','lbl'=>'أذونات المخزون', 'pat'=>"/$pfx/inventory",'module'=>'products'],
      ['sep'=>'المتجر'],
      ['url'=>"/$pfx/collections",'ico'=>'grid','lbl'=>'التصنيفات',   'pat'=>"/$pfx/collections",'module'=>'products'],
      ['url'=>"/$pfx/brands",     'ico'=>'star','lbl'=>'الماركات',    'pat'=>"/$pfx/brands",'module'=>'products'],
      ['url'=>"/$pfx/media",      'ico'=>'grid','lbl'=>'الميديا', 'pat'=>"/$pfx/media",'module'=>'marketing'],
      ['url'=>"/$pfx/banners",    'ico'=>'grid','lbl'=>'البانرات',    'pat'=>"/$pfx/banners",'module'=>'marketing'],
      ['url'=>"/$pfx/menu",       'ico'=>'menu','lbl'=>'إدارة المنيو',  'pat'=>"/$pfx/menu",'module'=>'marketing'],
      ['url'=>"/$pfx/retailers",  'ico'=>'store','lbl'=>'الموزعون',     'pat'=>"/$pfx/retailers",'module'=>'warranties'],
      ['sep'=>'التجارة'],
      ['url'=>"/$pfx/customers",  'ico'=>'user','lbl'=>'العملاء',     'pat'=>"/$pfx/customers",'module'=>'customers'],
      ['url'=>"/$pfx/wholesale",  'ico'=>'store','lbl'=>'الجملة',      'pat'=>"/$pfx/wholesale",'badge'=>$_wsPending,'badgeColor'=>'orange','module'=>'business'],
      ['url'=>"/$pfx/business",   'ico'=>'briefcase','lbl'=>'Business',    'pat'=>"/$pfx/business",'module'=>'business'],
      ['url'=>"/$pfx/emails/templates", 'ico'=>'shield','lbl'=>'الإيميلات', 'pat'=>"/$pfx/emails",'module'=>'settings'],
      ['url'=>"/$pfx/discounts",  'ico'=>'star','lbl'=>'الخصومات',    'pat'=>"/$pfx/discounts",'module'=>'marketing'],
      ['url'=>"/$pfx/reviews",    'ico'=>'star','lbl'=>'التقييمات',   'pat'=>"/$pfx/reviews",'module'=>'marketing'],
      ['url'=>"/$pfx/warranties", 'ico'=>'shield','lbl'=>'الضمانات',    'pat'=>"/$pfx/warranties",'module'=>'warranties'],
      ['url'=>"/$pfx/bulk/orders",'ico'=>'package','lbl'=>'جملة الطلبات','pat'=>"/$pfx/bulk",'module'=>'business'],
      ['sep'=>'التحليلات'],
      ['url'=>"/$pfx/analytics", 'ico'=>'grid','lbl'=>'التحليلات',   'pat'=>"/$pfx/analytics",'module'=>'analytics'],
      ['url'=>"/$pfx/finance",   'ico'=>'chart','lbl'=>'صافي الدخل', 'pat'=>"/$pfx/finance",'module'=>'analytics'],
      ['url'=>"/$pfx/expenses",  'ico'=>'receipt','lbl'=>'المصروفات', 'pat'=>"/$pfx/expenses",'module'=>'analytics'],
      ['sep'=>'النظام'],
      ['url'=>"/$pfx/settings",   'ico'=>'grid','lbl'=>'الإعدادات',   'pat'=>"/$pfx/settings",'module'=>'settings'],
      ['url'=>"/$pfx/updater",    'ico'=>'truck','lbl'=>'التحديثات',   'pat'=>"/$pfx/updater",'module'=>'settings'],
      ['url'=>"/$pfx/settings/api", 'ico'=>'settings','lbl'=>'إعدادات API', 'pat'=>"/$pfx/settings/api",'module'=>'settings'],
      ['url'=>"/$pfx/settings/rewards", 'ico'=>'star','lbl'=>'قواعد المكافآت', 'pat'=>"/$pfx/settings/rewards",'module'=>'settings'],
      ['url'=>"/$pfx/sync/supabase", 'ico'=>'grid','lbl'=>'مزامنة Supabase', 'pat'=>"/$pfx/sync",'module'=>'settings'],
    ];
    if (($_user['role'] ?? '') === 'superadmin') {
      $nav[] = ['url'=>"/$pfx/users", 'ico'=>'user', 'lbl'=>'مستخدمو الأدمن', 'pat'=>"/$pfx/users"];
    }
    $nav[] = ['url'=>"/$pfx/support", 'ico'=>'message', 'lbl'=>'دعم العملاء', 'pat'=>"/$pfx/support",'module'=>'support'];
    $nav[] = ['url'=>"/$pfx/instagram", 'ico'=>'image', 'lbl'=>'فيديوهات انستجرام', 'pat'=>"/$pfx/instagram",'module'=>'marketing'];

    // Hide items the logged-in admin has no permission for (superadmin always sees everything).
    // Items with no 'module' key (dashboard, users — already role-gated) are always visible.
    $nav = array_values(array_filter($nav, function($n) {
        if (isset($n['sep'])) return true; // sections filtered out below once empty
        if (!isset($n['module'])) return true;
        return adminCan($n['module']);
    }));
    // Drop any section separator that ended up with no visible items following it
    $nav2 = [];
    foreach ($nav as $i => $n) {
        if (isset($n['sep'])) {
            $hasNext = false;
            for ($j = $i + 1; $j < count($nav); $j++) {
                if (isset($nav[$j]['sep'])) break;
                $hasNext = true; break;
            }
            if (!$hasNext) continue;
        }
        $nav2[] = $n;
    }
    $nav = $nav2;

    foreach ($nav as $n):
      if (isset($n['sep'])): ?>
        <div class="sb-sep"><?= $n['sep'] ?></div>
      <?php continue; endif;
      $on = nav_active($n['pat'], $_uri);
    ?>
      <a href="<?= $n['url'] ?>" class="sb-link <?= $on?'on':'' ?>">
        <span class="sb-ico"><?= svgIcon($n['ico'], 18) ?></span>
        <?= htmlspecialchars($n['lbl']) ?>
        <?php if (!empty($n['badge']) && $n['badge'] > 0): ?>
          <span class="sb-badge-count <?= $n['badgeColor']??'' ?>"><?= $n['badge'] ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sb-foot">
    <div class="sb-user">
      <div class="sb-av"><?= mb_substr($_user['name']??'A',0,1) ?></div>
      <div>
        <div class="sb-uname"><?= e($_user['name']??'Admin') ?></div>
        <div class="sb-urole"><?= e($_user['role']??'') ?></div>
      </div>
      <a href="/<?= $pfx ?>/logout" class="sb-exit" title="تسجيل الخروج">⎋</a>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <button class="topbar-ham tb-btn btn-icon" style="background:var(--bg3);color:var(--t2)" onclick="toggleSB()" title="القائمة">☰</button>
    <nav class="breadcrumb">
      <a href="/<?= $pfx ?>">الرئيسية</a>
      <?php if (!empty($breadcrumb)): foreach ($breadcrumb as $i => $cr): ?>
        <span class="bc-sep">/</span>
        <?php if ($i === count($breadcrumb)-1): ?>
          <span class="bc-cur"><?= e($cr['label']) ?></span>
        <?php else: ?>
          <a href="<?= e($cr['url']??'#') ?>"><?= e($cr['label']) ?></a>
        <?php endif; ?>
      <?php endforeach; endif; ?>
    </nav>
    <div class="tb-right">
      <?php if (adminCan('settings')): ?>
      <form method="POST" action="<?= adminUrl('cache/clear') ?>" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="tb-theme" title="تنظيف الكاش" onclick="this.disabled=true;this.innerHTML='<?= addslashes(svgIcon('clock',16)) ?>';"><?= svgIcon('trash', 16) ?></button>
      </form>
      <?php endif; ?>
      <button class="tb-theme" onclick="toggleTheme()" title="تغيير المظهر" id="themeBtn">
        <span class="tt-moon"><?= svgIcon('moon', 16) ?></span><span class="tt-sun"><?= svgIcon('sun', 16) ?></span>
      </button>
      <a href="/" target="_blank" class="tb-btn" style="background:var(--bg3);color:var(--t1)">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        المتجر
      </a>
    </div>
  </header>

  <main class="page">
    <?php
    try {
      if ($msg = Session::flash('success')): ?>
        <div class="flash flash-ok">✅ <?= e($msg) ?><button class="flash-x" onclick="this.parentElement.remove()">×</button></div>
      <?php endif;
      if ($msg = Session::flash('error')): ?>
        <div class="flash flash-err">⚠️ <?= e($msg) ?><button class="flash-x" onclick="this.parentElement.remove()">×</button></div>
      <?php endif;
    } catch (\Throwable $e) {}
    if (!empty($content)) echo $content;
    ?>
  </main>
</div>
</div>

<script>
function toggleSB(){document.getElementById('sidebar').classList.toggle('open')}

// Generic mobile fix: any inline 2-column grid with a fixed-px sidebar column
// (e.g. "1fr 340px") collapses to a single column below 960px, and restores on desktop.
function applyMobileGridFix(){
  const isMobile = window.innerWidth <= 960;
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
window.addEventListener('resize', applyMobileGridFix);
document.addEventListener('DOMContentLoaded', applyMobileGridFix);
document.addEventListener('click',e=>{
  const sb=document.getElementById('sidebar');
  if(window.innerWidth<=960&&sb.classList.contains('open')&&!sb.contains(e.target)&&!e.target.closest('.topbar-ham'))
    sb.classList.remove('open');
});

function toggleTheme(){
  const html=document.documentElement;
  const isDark=html.dataset.theme==='dark';
  const next=isDark?'light':'dark';
  html.dataset.theme=next;
  fetch('/<?= $pfx ?>/settings/theme',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'theme='+next+'&_csrf='+encodeURIComponent('<?= addslashes(Session::csrf()) ?>')});
}

// Global safety net for ALL modals: click the dark overlay (outside the modal box) or press
// Escape to close whichever modal is open — so a tall/broken modal is never a dead end.
document.addEventListener('click', function(e){
  if (e.target.classList && e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});
function confirmDelete(url,title,desc){
  document.getElementById('confirm-title').textContent=title||'هل أنت متأكد؟';
  document.getElementById('confirm-desc').textContent=desc||'لا يمكن التراجع.';
  document.getElementById('confirm-form').action=url;
  document.getElementById('confirm-modal').classList.add('open');
}
function closeConfirm(){document.getElementById('confirm-modal').classList.remove('open')}
document.getElementById('confirm-modal').addEventListener('click',e=>{
  if(e.target===document.getElementById('confirm-modal'))closeConfirm();
});

setTimeout(()=>document.querySelectorAll('.flash').forEach(el=>el.remove()),5000);

let _mediaCallback=null,_mediaSelected=null,_mediaAll=[];
function openMedia(callback){
  _mediaCallback=callback;_mediaSelected=null;
  document.getElementById('mediaConfirm').disabled=true;
  document.getElementById('mediaSearch').value='';
  document.getElementById('media-modal').classList.add('open');
  loadMediaGrid();
}
function closeMedia(){document.getElementById('media-modal').classList.remove('open')}
document.getElementById('media-modal').addEventListener('click',e=>{
  if(e.target===document.getElementById('media-modal'))closeMedia();
});
async function loadMediaGrid(q=''){
  const grid=document.getElementById('mediaGrid');
  grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--t3)">جاري التحميل...</div>';
  try {
    const nonce = Date.now() + Math.random().toString(36).slice(2);
    const r=await fetch('/<?= $pfx ?>/media/list/'+nonce+'?q='+encodeURIComponent(q), {cache:'no-store', headers:{'Cache-Control':'no-cache'}});
    if (!r.ok) { grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--red)">فشل تحميل الصور (HTTP '+r.status+') — جرب تاني</div>'; return; }
    const data=await r.json();
    _mediaAll=data.files||[];
    renderMediaGrid(_mediaAll);
  } catch(e) {
    grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--red)">تعذر الاتصال بالسيرفر</div>';
  }
}
function renderMediaGrid(files){
  const grid=document.getElementById('mediaGrid');
  if(!files.length){grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--t3)">لا توجد صور</div>';return;}
  grid.innerHTML=files.map(f=>`
    <div class="img-thumb" onclick="selectMedia('${f.url}','${f.name}',this)" title="${f.name}">
      <img src="${f.url}" alt="${f.name}" loading="lazy">
    </div>`).join('');
}
function selectMedia(url,name,el){
  document.querySelectorAll('#mediaGrid .img-thumb').forEach(t=>t.classList.remove('selected'));
  el.classList.add('selected');
  _mediaSelected={url,name};
  document.getElementById('mediaConfirm').disabled=false;
}
function confirmMedia(){
  if(_mediaSelected&&_mediaCallback){_mediaCallback(_mediaSelected);closeMedia();}
}
function filterMedia(q){
  const f=_mediaAll.filter(x=>x.name.toLowerCase().includes(q.toLowerCase()));
  renderMediaGrid(f);
}
async function uploadMediaFiles(input){
  if (!input.files || !input.files.length) return;
  const grid=document.getElementById('mediaGrid');
  grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--t2)">⏳ جاري رفع '+input.files.length+' صورة...</div>';
  const fd=new FormData();
  for(const f of input.files)fd.append('images[]',f);
  fd.append('_csrf','<?= addslashes(Session::csrf()) ?>');
  try {
    const nonce = Date.now() + Math.random().toString(36).slice(2);
    const r = await fetch('/<?= $pfx ?>/media/upload/'+nonce,{method:'POST',body:fd, cache:'no-store'});
    if (!r.ok) { alert('فشل الرفع (HTTP '+r.status+') — راجع سجل الأخطاء أو جرب تاني'); loadMediaGrid(); return; }
    const d = await r.json();
    if (d.errors && d.errors.length) { alert('تنبيهات:\\n' + d.errors.join('\\n')); }
    else if (!d.ok) { alert('لم يتم رفع أي صورة — تأكد إن الملفات بصيغة jpg/png/gif/webp وحجمها أقل من 5 ميجا'); }
    input.value='';
    loadMediaGrid(document.getElementById('mediaSearch').value);
  } catch(e) {
    alert('تعذر الاتصال بالسيرفر أثناء الرفع');
    loadMediaGrid();
  }
}

function mkSlug(v){return v.trim().toLowerCase().replace(/\s+/g,'-').replace(/[^a-z0-9\-]/g,'').replace(/--+/g,'-').replace(/^-+|-+$/g,'')}
<?php if(!empty($extraScript)) echo $extraScript; ?>
</script>
</body>
</html>
