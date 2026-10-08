<?php
$pageTitle  = 'الإعدادات';
$breadcrumb = [['label' => 'الإعدادات']];
$a   = ADMIN_PREFIX;
$tab = $_GET['tab'] ?? 'general';
$s   = $settings ?? [];

// Helper: get setting value
$v = fn(string $k, string $d='') => htmlspecialchars($s[$k] ?? $d, ENT_QUOTES);

function _imgField(string $field, string $label, array $s, string $hint = ''): void {
    $has = !empty($s[$field]);
    echo '<div class="form-group">';
    echo '<label class="form-label">'.$label.'</label>';
    echo '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">';
    echo '<div id="prev_'.$field.'" style="width:64px;height:64px;border-radius:8px;overflow:hidden;border:1px solid var(--bd);background:var(--bg3);display:flex;align-items:center;justify-content:center;flex-shrink:0">';
    if ($has) echo '<img src="'.uploadUrl($s[$field]).'" style="width:100%;height:100%;object-fit:contain">';
    else echo '<span style="font-size:22px">&#127760;</span>';
    echo '</div>';
    echo '<div style="display:flex;flex-direction:column;gap:6px;flex:1">';
    echo '<div style="display:flex;gap:6px">';
    echo '<label class="btn btn-secondary btn-sm" style="cursor:pointer;flex:1;justify-content:center">&#128228; رفع<input type="file" name="'.$field.'" accept="image/*" style="display:none" onchange="previewSettingImg(this,\''.$field.'\')"></label>';
    echo '<button type="button" class="btn btn-secondary btn-sm" style="flex:1" onclick="openMedia(function(f){setSettingImgFromMedia(\''.$field.'\',f.url)})">&#128247; المكتبة</button>';
    echo '</div>';
    if ($has) echo '<button type="button" class="btn btn-danger btn-sm" onclick="removeSettingImg(\''.$field.'\')">&#128465;&#65039; حذف الصورة</button>';
    echo '</div></div>';
    echo '<input type="hidden" name="'.$field.'_url" id="url_'.$field.'">';
    echo '<input type="hidden" name="remove_'.$field.'" id="remove_'.$field.'" value="">';
    if ($hint) echo '<span class="form-hint">'.$hint.'</span>';
    echo '</div>';
}
ob_start();
?>

<!-- Tab Bar -->
<div class="ph">
  <div class="ph-left"><h1>إعدادات النظام</h1></div>
</div>

<div style="display:flex;gap:2px;border-bottom:1px solid var(--bd);margin-bottom:22px;overflow-x:auto">
  <?php foreach([
    'general'    => ['store', 'المتجر'],
    'homepage'   => ['home', 'الصفحة الرئيسية'],
    'appearance' => ['image', 'المظهر'],
    'payment'    => ['star', 'الدفع'],
    'shipping'   => ['truck', 'الشحن'],
    'social'     => ['phone', 'السوشيال'],
    'seo'        => ['search', 'SEO'],
    'system'     => ['settings', 'النظام'],
  ] as $k=>$v_tab): ?>
    <a href="?tab=<?=$k?>" style="text-decoration:none">
      <button style="display:flex;align-items:center;gap:6px;padding:9px 16px;font-size:13px;font-weight:500;background:none;border:none;border-bottom:2px solid <?=$tab===$k?'var(--ac)':'transparent'?>;color:<?=$tab===$k?'var(--ac)':'var(--t2)'?>;cursor:pointer;white-space:nowrap;transition:color .12s,border-color .12s;font-family:inherit">
        <?= svgIcon($v_tab[0], 15) ?> <?= $v_tab[1] ?>
      </button>
    </a>
  <?php endforeach; ?>
</div>

<form method="POST" action="/<?=$a?>/settings" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="_tab" value="<?= $tab ?>">

<?php // ══════════════════════════════════════════════════════════════
// TAB: GENERAL
// ══════════════════════════════════════════════════════════════
if ($tab === 'general'): ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">
    <div style="display:flex;flex-direction:column;gap:14px">

      <div class="card">
        <div class="card-header"><span class="card-title"><?= svgIcon('store', 15) ?> معلومات المتجر الأساسية</span></div>
        <div class="card-body">
          <div class="form-grid form-row-2">
            <div class="form-group"><label class="form-label">اسم المتجر <span>*</span></label><input type="text" name="store_name" class="form-input" value="<?= $v('store_name') ?>" required></div>
            <div class="form-group"><label class="form-label">البريد الإلكتروني <span>*</span></label><input type="email" name="store_email" class="form-input" value="<?= $v('store_email') ?>"></div>
            <div class="form-group"><label class="form-label">رقم الهاتف</label><input type="text" name="store_phone" class="form-input" value="<?= $v('store_phone') ?>" placeholder="01xxxxxxxxx"></div>
            <div class="form-group"><label class="form-label">رقم هاتف إضافي</label><input type="text" name="store_phone2" class="form-input" value="<?= $v('store_phone2') ?>"></div>
            <div class="form-group" style="grid-column:span 2"><label class="form-label">العنوان</label><input type="text" name="store_address" class="form-input" value="<?= $v('store_address') ?>"></div>
            <div class="form-group" style="grid-column:span 2"><label class="form-label">وصف المتجر</label><textarea name="store_description" class="form-textarea" rows="3"><?= $v('store_description') ?></textarea><span class="form-hint">يظهر في الفوتر وصفحة من نحن</span></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title"><?= svgIcon('map-pin', 15) ?> معلومات إضافية</span></div>
        <div class="card-body">
          <div class="form-grid form-row-2">
            <div class="form-group"><label class="form-label">واتساب</label><input type="text" name="contact_whatsapp" class="form-input" value="<?= $v('contact_whatsapp') ?>" placeholder="201xxxxxxxxx"></div>
            <div class="form-group"><label class="form-label">العملة</label>
              <select name="store_currency" class="form-select">
                <option value="EGP" <?= ($s['store_currency']??'EGP')==='EGP'?'selected':'' ?>>ج.م — جنيه مصري</option>
                <option value="USD" <?= ($s['store_currency']??'')==='USD'?'selected':'' ?>>$ — دولار</option>
                <option value="SAR" <?= ($s['store_currency']??'')==='SAR'?'selected':'' ?>>ر.س — ريال سعودي</option>
                <option value="AED" <?= ($s['store_currency']??'')==='AED'?'selected':'' ?>>د.إ — درهم إماراتي</option>
              </select>
            </div>
            <div class="form-group" style="grid-column:span 2"><label class="form-label">رابط خريطة Google Maps</label><input type="url" name="contact_map_url" class="form-input" value="<?= $v('contact_map_url') ?>" placeholder="https://maps.google.com/..."></div>
          </div>
        </div>
      </div>

    </div>

    <!-- Right column: Logo + Favicon -->
    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card">
        <div class="card-header"><span class="card-title"><?= svgIcon('image', 15) ?> صور المتجر</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
          <?php _imgField('store_logo', 'شعار المتجر (Logo) — للخلفيات الفاتحة', $s, 'PNG شفاف مُفضل — 300×80px — يُستخدم على خلفية بيضاء'); ?>
          <?php _imgField('store_logo_white', 'شعار أبيض — للخلفيات الداكنة', $s, 'نسخة بيضاء من الشعار — تُستخدم تلقائياً على الفوتر، السايدبار، والإيميلات (خلفية سوداء). لو مش موجود، هيتم استخدام الشعار العادي.'); ?>
          <?php _imgField('store_favicon', 'Favicon', $s, '32×32 أو 64×64'); ?>
          <?php _imgField('store_og_image', 'صورة المشاركة (OG Image)', $s, '1200×630px — تظهر عند مشاركة رابط المتجر'); ?>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="padding:12px"><?= svgIcon('check', 15) ?> حفظ الإعدادات</button>
    </div>
  </div>

<?php // ══════════════════════════════════════════════════════════════
// TAB: APPEARANCE
// ══════════════════════════════════════════════════════════════
elseif ($tab === 'homepage'): ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('home', 15) ?> محتوى الصفحة الرئيسية (Hero)</span></div>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">العنوان الرئيسي</label><input type="text" name="hero_title" class="form-input" value="<?= $v('hero_title','أحدث التقنيات بين يديك') ?>"></div>
          <div class="form-group"><label class="form-label">العنوان الفرعي</label><input type="text" name="hero_subtitle" class="form-input" value="<?= $v('hero_subtitle') ?>"></div>
          <div class="form-row-2 form-grid" style="margin:0">
            <div class="form-group"><label class="form-label">نص زر CTA</label><input type="text" name="hero_btn_text" class="form-input" value="<?= $v('hero_btn_text','تسوق الآن') ?>"></div>
            <div class="form-group"><label class="form-label">رابط زر CTA</label><input type="text" name="hero_btn_url" class="form-input" value="<?= $v('hero_btn_url','products') ?>" placeholder="products"></div>
          </div>
          <?php _imgField('hero_image', 'صورة/منتج الـ Hero (تظهر لو لا يوجد سلايدر بانرات)', $s, 'مربعة أو عمودية — تظهر بجانب العنوان'); ?>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:4px">
          <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
        </div>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card">
        <div class="card-header"><span class="card-title"><?= svgIcon('image', 15) ?> السلايدر والبانرات</span></div>
        <div class="card-body">
          <p style="font-size:12.5px;color:var(--t2);line-height:1.8;margin-bottom:14px">
            سلايدر الصفحة الرئيسية والبانرات المتوسطة والصغيرة تُدار من صفحة مستقلة — لو فيه سلايدر نشط هيظهر بدل الـ Hero الافتراضي فوق.
          </p>
          <a href="<?= adminUrl('banners') ?>" class="btn btn-secondary btn-full"><?= svgIcon('image', 15) ?> إدارة البانرات والسلايدر ←</a>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">ℹ️ ترتيب الظهور</span></div>
        <div class="card-body">
          <div style="font-size:12px;color:var(--t2);line-height:2">
            1. سلايدر البانرات (إن وُجد)<br>
            2. شريط الثقة<br>
            3. بانرات متوسطة<br>
            4. التصنيفات<br>
            5. المنتجات المميزة<br>
            6. الأكثر تميزاً (Spotlight)<br>
            7. الماركات · وصل حديثاً · لماذا نحن
          </div>
        </div>
      </div>
    </div>
  </div>

elseif ($tab === 'appearance'): ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('image', 15) ?> الألوان والخطوط</span></div>
      <div class="card-body">
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">اللون الأساسي</label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="color" name="primary_color" value="<?= $s['primary_color']??'#6d5acd' ?>" style="width:44px;height:38px;border:1px solid var(--bd);border-radius:7px;background:var(--bg);cursor:pointer;padding:2px">
            <input type="text" id="colorHex" class="form-input" value="<?= $v('primary_color','#6d5acd') ?>" oninput="document.querySelector('[name=primary_color]').value=this.value" style="font-family:monospace">
          </div>
          <span class="form-hint">لون الأزرار والروابط والعناصر الرئيسية</span>
        </div>
        <div class="form-group">
          <label class="form-label">نوع الخط</label>
          <select name="store_font" class="form-select">
            <?php foreach(['Inter'=>'Inter (افتراضي)','Cairo'=>'Cairo (عربي)','Tajawal'=>'Tajawal (عربي)','Almarai'=>'Almarai (عربي)','Roboto'=>'Roboto'] as $f=>$l): ?>
              <option value="<?=$f?>" <?= ($s['store_font']??'Inter')===$f?'selected':'' ?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-top:4px">
          <label class="form-label">وضع العرض</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px">
            <?php foreach(['dark'=>['moon','داكن (Dark)'],'light'=>['sun','فاتح (Light)']] as $m=>[$mi,$ml]): ?>
              <label style="display:flex;align-items:center;gap:10px;background:var(--bg3);border:2px solid <?= ($s['theme_mode']??'dark')===$m?'var(--ac)':' var(--bd)' ?>;border-radius:8px;padding:12px 14px;cursor:pointer;transition:border-color .15s">
                <input type="radio" name="theme_mode" value="<?=$m?>" <?= ($s['theme_mode']??'dark')===$m?'checked':'' ?> style="accent-color:var(--ac)">
                <span style="display:flex"><?= svgIcon($mi, 17) ?></span>
                <span style="font-size:13px;font-weight:500"><?=$ml?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('search', 15) ?> معاينة</span></div>
      <div class="card-body" style="text-align:center">
        <div style="background:var(--bg3);border-radius:9px;padding:24px">
          <div style="width:120px;height:120px;background:<?= $s['primary_color']??'#6d5acd' ?>;border-radius:12px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;color:#fff" id="colorPreview"><?= svgIcon('zap', 44, 1.3) ?></div>
          <div style="font-weight:700;margin-bottom:6px"><?= $v('store_name','Notch Technology') ?></div>
          <button style="background:<?= $s['primary_color']??'#6d5acd' ?>;color:#fff;border:none;padding:8px 20px;border-radius:8px;font-size:13px;cursor:pointer" id="btnPreview">تسوق الآن</button>
        </div>
      </div>
    </div>
  </div>
  <div style="display:flex;justify-content:flex-end;margin-top:14px">
    <button type="submit" class="btn btn-primary" style="padding:11px 28px"><?= svgIcon('check', 15) ?> حفظ</button>
  </div>

<?php // ══════════════════════════════════════════════════════════════
// TAB: PAYMENT
// ══════════════════════════════════════════════════════════════
elseif ($tab === 'payment'): ?>

  <div style="display:flex;flex-direction:column;gap:14px">

    <!-- COD -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('star', 15) ?> الدفع عند الاستلام (Cash on Delivery)</span>
        <div class="toggle-wrap">
          <label class="toggle"><input type="checkbox" name="cod_active" value="1" <?= ($s['cod_active']??'1')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
          <span style="font-size:12px;color:var(--t2)">تفعيل</span>
        </div>
      </div>
      <div class="card-body">
        <div class="form-grid form-row-2">
          <div class="form-group"><label class="form-label">اسم طريقة الدفع</label><input type="text" name="cod_label" class="form-input" value="<?= $v('cod_label','الدفع عند الاستلام') ?>"></div>
          <div class="form-group"><label class="form-label">رسوم الدفع عند الاستلام (ج.م)</label><input type="number" name="cod_fee" class="form-input" value="<?= $v('cod_fee','0') ?>" step="0.01" min="0" placeholder="0"><span class="form-hint">0 = مجاني</span></div>
        </div>
      </div>
    </div>

    <!-- Fawateerk -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('star', 15) ?> فواتيرك (Fawateerk)</span>
        <div class="toggle-wrap">
          <label class="toggle"><input type="checkbox" name="fawateerk_active" value="1" <?= ($s['fawateerk_active']??'0')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
          <span style="font-size:12px;color:var(--t2)">تفعيل</span>
        </div>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">API Key</label>
          <input type="text" name="fawateerk_api_key" class="form-input" value="<?= $v('fawateerk_api_key') ?>" placeholder="أدخل مفتاح API من لوحة تحكم فواتيرك">
          <span class="form-hint"><a href="https://app.fawaterk.com" target="_blank" style="color:var(--ac)">احصل على المفتاح من هنا</a> — فيزا / ميزة / فوري / محافظ إلكترونية</span>
        </div>
      </div>
    </div>

    <!-- InstaPay -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('phone', 15) ?> InstaPay</span>
        <div class="toggle-wrap">
          <label class="toggle"><input type="checkbox" name="instapay_active" value="1" <?= ($s['instapay_active']??'0')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
          <span style="font-size:12px;color:var(--t2)">تفعيل</span>
        </div>
      </div>
      <div class="card-body">
        <div class="form-grid form-row-2">
          <div class="form-group"><label class="form-label">رقم InstaPay / الهاتف</label><input type="text" name="instapay_number" class="form-input" value="<?= $v('instapay_number') ?>" placeholder="01xxxxxxxxx"></div>
          <div class="form-group"><label class="form-label">اسم صاحب الحساب</label><input type="text" name="instapay_name" class="form-input" value="<?= $v('instapay_name') ?>"></div>
        </div>
      </div>
    </div>

    <!-- Bank Transfer -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('building', 15) ?> تحويل بنكي</span>
        <div class="toggle-wrap">
          <label class="toggle"><input type="checkbox" name="bank_transfer_active" value="1" <?= ($s['bank_transfer_active']??'0')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
          <span style="font-size:12px;color:var(--t2)">تفعيل</span>
        </div>
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">تفاصيل الحساب البنكي</label>
          <textarea name="bank_transfer_details" class="form-textarea" rows="4" placeholder="اسم البنك:&#10;رقم الحساب:&#10;رقم IBAN:&#10;اسم صاحب الحساب:"><?= $v('bank_transfer_details') ?></textarea>
          <span class="form-hint">يظهر للعميل بعد إتمام الطلب</span>
        </div>
      </div>
    </div>

    <!-- Order limits -->
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('settings', 15) ?> إعدادات الطلبات</span></div>
      <div class="card-body">
        <div class="form-grid form-row-2">
          <div class="form-group"><label class="form-label">الحد الأدنى لقيمة الطلب (ج.م)</label><input type="number" name="min_order_amount" class="form-input" value="<?= $v('min_order_amount','0') ?>" step="0.01" min="0"></div>
          <div class="form-group"><label class="form-label">شحن مجاني من (ج.م)</label><input type="number" name="free_shipping_above" class="form-input" value="<?= $v('free_shipping_above','0') ?>" step="0.01" min="0" placeholder="0 = غير مفعل"></div>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end">
      <button type="submit" class="btn btn-primary" style="padding:11px 28px"><?= svgIcon('check', 15) ?> حفظ إعدادات الدفع</button>
    </div>
  </div>

<?php // ══════════════════════════════════════════════════════════════
// TAB: SHIPPING — redirect to dedicated page
// ══════════════════════════════════════════════════════════════
elseif ($tab === 'shipping'): ?>
  <div style="text-align:center;padding:40px">
    <div style="margin-bottom:14px;display:flex;justify-content:center"><?= svgIcon('truck', 44, 1.3) ?></div>
    <p style="color:var(--t2);margin-bottom:18px">إعدادات الشحن والمناطق في صفحة مستقلة</p>
    <a href="<?= adminUrl('settings/shipping') ?>" class="btn btn-primary">الذهاب لإعدادات الشحن ←</a>
  </div>

<?php // ══════════════════════════════════════════════════════════════
// TAB: SOCIAL
// ══════════════════════════════════════════════════════════════
elseif ($tab === 'social'): ?>

  <div class="card">
    <div class="card-header"><span class="card-title"><?= svgIcon('phone', 15) ?> روابط التواصل الاجتماعي</span></div>
    <div class="card-body">
      <div class="form-grid form-row-2">
        <?php foreach([
          'facebook'  => ['🔵','Facebook','https://facebook.com/...'],
          'instagram' => ['🟣','Instagram','https://instagram.com/...'],
          'twitter'   => ['⚫','X (Twitter)','https://x.com/...'],
          'youtube'   => ['🔴','YouTube','https://youtube.com/...'],
          'tiktok'    => ['⚫','TikTok','https://tiktok.com/@...'],
          'snapchat'  => ['🟡','Snapchat','https://snapchat.com/add/...'],
          'linkedin'  => ['🔷','LinkedIn','https://linkedin.com/company/...'],
        ] as $k=>[$icon,$label,$ph]): ?>
          <div class="form-group">
            <label class="form-label"><?=$icon?> <?=$label?></label>
            <input type="url" name="social_<?=$k?>" class="form-input" value="<?= $v("social_$k") ?>" placeholder="<?=$ph?>">
          </div>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:4px">
        <button type="submit" class="btn btn-primary" style="padding:11px 28px"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </div>
  </div>

<?php // ══════════════════════════════════════════════════════════════
// TAB: SEO
// ══════════════════════════════════════════════════════════════
elseif ($tab === 'seo'): ?>

  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('search', 15) ?> بيانات الصفحة الرئيسية</span></div>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group"><label class="form-label">Meta Title</label><input type="text" name="meta_title" class="form-input" value="<?= $v('meta_title') ?>" placeholder="عنوان الموقع في Google"><span class="form-hint">الطول المثالي: 50-60 حرف</span></div>
          <div class="form-group"><label class="form-label">Meta Description</label><textarea name="meta_description" class="form-textarea" rows="3" placeholder="وصف موجز للموقع يظهر في نتائج البحث..."><?= $v('meta_description') ?></textarea><span class="form-hint">الطول المثالي: 150-160 حرف</span></div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('chart', 15) ?> أدوات التحليل والتتبع</span></div>
      <div class="card-body">
        <div class="form-grid form-row-2">
          <div class="form-group"><label class="form-label">Google Analytics 4</label><input type="text" name="google_analytics" class="form-input" value="<?= $v('google_analytics') ?>" placeholder="G-XXXXXXXXXX"></div>
          <div class="form-group"><label class="form-label">Facebook Pixel ID</label><input type="text" name="facebook_pixel" class="form-input" value="<?= $v('facebook_pixel') ?>" placeholder="123456789012345"></div>
          <div class="form-group"><label class="form-label">Google Search Console</label><input type="text" name="google_search_console" class="form-input" value="<?= $v('google_search_console') ?>" placeholder="كود التحقق"></div>
        </div>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end">
      <button type="submit" class="btn btn-primary" style="padding:11px 28px"><?= svgIcon('check', 15) ?> حفظ</button>
    </div>
  </div>

<?php // ══════════════════════════════════════════════════════════════
// TAB: SYSTEM
// ══════════════════════════════════════════════════════════════
elseif ($tab === 'system'): ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('settings', 15) ?> إعدادات النظام</span></div>
      <div class="card-body">
        <div class="form-grid" style="gap:14px">
          <div class="form-group">
            <div class="toggle-wrap">
              <label class="toggle"><input type="checkbox" name="maintenance_mode" value="1" <?= ($s['maintenance_mode']??'0')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
              <div><span style="font-size:13px;font-weight:500">وضع الصيانة</span><div style="font-size:11px;color:var(--t3)">إخفاء المتجر عن الزوار مؤقتاً</div></div>
            </div>
          </div>
          <div class="form-group"><label class="form-label">رسالة الصيانة</label><textarea name="maintenance_message" class="form-textarea" rows="2" placeholder="الموقع تحت الصيانة، سنعود قريباً..."><?= $v('maintenance_message') ?></textarea></div>
          <div class="form-group"><label class="form-label">عدد المنتجات في الصفحة</label>
            <select name="items_per_page" class="form-select">
              <?php foreach([12,16,20,24,36,48] as $n): ?><option value="<?=$n?>" <?= ($s['items_per_page']??'20')===(string)$n?'selected':'' ?>><?=$n?> منتج</option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">حد تنبيه المخزون المنخفض</label><input type="number" name="low_stock_threshold" class="form-input" value="<?= $v('low_stock_threshold','5') ?>" min="1"></div>
          <div class="form-group"><label class="form-label">بادئة رقم الطلب</label><input type="text" name="order_prefix" class="form-input" value="<?= $v('order_prefix','ORD-') ?>" placeholder="ORD-"></div>
          <div class="form-group"><label class="form-label">بادئة رقم الفاتورة</label><input type="text" name="invoice_prefix" class="form-input" value="<?= $v('invoice_prefix','INV-') ?>" placeholder="INV-"></div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('mail', 15) ?> الإشعارات</span></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:12px">
          <?php foreach([
            ['email_notifications','إشعارات البريد الإلكتروني','إرسال إيميل عند كل طلب جديد'],
            ['sms_notifications','إشعارات SMS','إرسال رسالة نصية عند كل طلب'],
          ] as [$k,$t,$d]): ?>
            <div style="padding:12px;background:var(--bg3);border-radius:8px;border:1px solid var(--bd)">
              <div class="toggle-wrap"><label class="toggle"><input type="checkbox" name="<?=$k?>" value="1" <?= ($s[$k]??'0')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
              <div><div style="font-size:13px;font-weight:500"><?=$t?></div><div style="font-size:11px;color:var(--t3)"><?=$d?></div></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div style="margin-top:14px">
    <div class="card">
      <div class="card-header"><span class="card-title">ℹ️ معلومات النظام</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
          <?php foreach([
            ['الإصدار',VERSION],['PHP',PHP_VERSION],
            ['قاعدة البيانات',DB_NAME],['السيرفر',$_SERVER['SERVER_SOFTWARE']??'N/A'],
            ['مساحة متاحة',round(disk_free_space(ROOT_PATH)/1024/1024).' MB'],
            ['التاريخ',date('d/m/Y H:i')],
          ] as [$k,$vv]): ?>
            <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:8px;padding:12px">
              <div style="font-size:11px;color:var(--t3);margin-bottom:3px"><?=$k?></div>
              <div style="font-size:13px;font-weight:600;font-family:monospace"><?= e($vv) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div style="display:flex;justify-content:flex-end;margin-top:14px">
    <button type="submit" class="btn btn-primary" style="padding:11px 28px"><?= svgIcon('check', 15) ?> حفظ</button>
  </div>

<?php endif; ?>
</form>

<?php
$extraScript = "
document.querySelector('[name=primary_color]')?.addEventListener('input',function(){
  document.getElementById('colorHex').value=this.value;
  document.getElementById('colorPreview').style.background=this.value;
  document.getElementById('btnPreview').style.background=this.value;
});

function previewSettingImg(input, field){
  if(input.files && input.files[0]){
    document.getElementById('remove_'+field).value='';
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('prev_'+field).innerHTML = '<img src=\'' + e.target.result + '\' style=\'width:100%;height:100%;object-fit:contain\'>';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
function setSettingImgFromMedia(field, url){
  document.getElementById('url_'+field).value = url;
  document.getElementById('remove_'+field).value = '';
  document.getElementById('prev_'+field).innerHTML = '<img src=\'' + url + '\' style=\'width:100%;height:100%;object-fit:contain\'>';
}
function removeSettingImg(field){
  if(!confirm('حذف هذه الصورة؟ سيتم حفظ الحذف عند الضغط على زر الحفظ.')) return;
  document.getElementById('remove_'+field).value = '1';
  document.getElementById('url_'+field).value = '';
  document.getElementById('prev_'+field).innerHTML = '<span style=\'font-size:22px\'>🖼️</span>';
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
