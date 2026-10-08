<?php
$pageTitle = 'الدعم الفني — ' . SettingModel::get('store_name', APP_NAME);
$whatsapp  = SettingModel::get('whatsapp_number');
$supportPhone = SettingModel::get('support_phone') ?: SettingModel::get('store_phone');
$supportEmail = SettingModel::get('support_email') ?: SettingModel::get('store_email');
ob_start();
?>
<!-- 24/7 hero banner -->
<div style="background:var(--black);padding:56px 0;text-align:center">
  <div class="container">
    <div style="color:#fff;font-size:26px;font-weight:900;margin-bottom:12px">خدمة عملاء ودعم فني 24/7</div>
    <p style="color:rgba(255,255,255,.6);font-size:14px;max-width:520px;margin:0 auto">نلتزم بتقديم أفضل تجربة دعم، نعمل بسرعة وبخطوات واضحة للتنفيذ والمتابعة حتى انتهاء المشكلة أو الاستفسار.</p>
  </div>
</div>

<div class="container" style="padding-top:40px;padding-bottom:80px">

  <!-- Contact channels -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:48px">
    <?php if ($whatsapp): ?>
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$whatsapp) ?>" target="_blank" class="support-channel">
      <div class="support-channel-icon" style="background:#25D36620;color:#25D366"><?= svgIcon('message', 22) ?></div>
      <div class="support-channel-name">واتساب</div>
      <div class="support-channel-sub">رد سريع على مدار الساعة</div>
    </a>
    <?php endif; ?>
    <?php if ($supportPhone): ?>
    <a href="tel:<?= e($supportPhone) ?>" class="support-channel">
      <div class="support-channel-icon" style="background:var(--accent-bg);color:var(--accent)"><?= svgIcon('phone', 22) ?></div>
      <div class="support-channel-name">اتصل بينا</div>
      <div class="support-channel-sub"><?= e($supportPhone) ?></div>
    </a>
    <?php endif; ?>
    <?php if ($supportEmail): ?>
    <a href="mailto:<?= e($supportEmail) ?>" class="support-channel">
      <div class="support-channel-icon" style="background:var(--accent-bg);color:var(--accent)"><?= svgIcon('mail', 22) ?></div>
      <div class="support-channel-name">إيميل</div>
      <div class="support-channel-sub"><?= e($supportEmail) ?></div>
    </a>
    <?php endif; ?>
    <a href="<?= url('warranty') ?>" class="support-channel">
      <div class="support-channel-icon" style="background:var(--accent-bg);color:var(--accent)"><?= svgIcon('shield', 22) ?></div>
      <div class="support-channel-name">تفعيل الضمان</div>
      <div class="support-channel-sub">سجّل منتجك دلوقتي</div>
    </a>
  </div>

  <div class="page-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
    <!-- FAQ -->
    <div>
      <h2 style="font-size:19px;font-weight:800;margin-bottom:18px">الأسئلة الشائعة</h2>
      <?php if (empty($faqsByCategory)): ?>
        <p style="color:var(--text3);font-size:13px">لا توجد أسئلة شائعة حالياً</p>
      <?php else: foreach ($faqsByCategory as $cat => $items): ?>
        <div style="margin-bottom:20px">
          <?php foreach ($items as $f): ?>
            <details class="faq-item">
              <summary><?= e($f['question']) ?></summary>
              <p><?= e($f['answer']) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endforeach; endif; ?>

      <div style="margin-top:24px;padding:18px;background:var(--bg2);border-radius:14px;font-size:13px">
        عندك رقم تذكرة سابق؟ <a href="<?= url('support/track') ?>" style="color:var(--accent);font-weight:700">تابع حالتها من هنا ←</a>
      </div>
    </div>

    <!-- Ticket form -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:26px">
      <h2 style="font-size:17px;font-weight:800;margin-bottom:18px">أرسل طلب دعم</h2>
      <form method="POST" action="<?= url('support/submit') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">الاسم <span>*</span></label>
          <input type="text" name="name" class="form-input" required value="<?= isStoreLoggedIn() ? e(storeUser()['name']) : '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">البريد الإلكتروني <span>*</span></label>
          <input type="email" name="email" class="form-input" required value="<?= isStoreLoggedIn() ? e(storeUser()['email']) : '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">رقم الهاتف</label>
          <input type="tel" name="phone" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">نوع الطلب</label>
          <select name="category" class="form-select">
            <option value="general">استفسار عام</option>
            <option value="order">مشكلة في طلب</option>
            <option value="warranty">ضمان</option>
            <option value="product">استفسار عن منتج</option>
            <option value="complaint">شكوى</option>
            <option value="other">أخرى</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">رقم الطلب (لو الاستفسار عن طلب)</label>
          <input type="text" name="order_number" class="form-input" placeholder="مثال: ORD-12345">
        </div>
        <div class="form-group">
          <label class="form-label">الموضوع <span>*</span></label>
          <input type="text" name="subject" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">تفاصيل الطلب <span>*</span></label>
          <textarea name="message" class="form-textarea" rows="4" required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">إرفاق صورة (اختياري)</label>
          <input type="file" name="attachment" class="form-input" accept="image/*" style="padding:8px">
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">إرسال الطلب</button>
      </form>
    </div>
  </div>
</div>

<style>
.support-channel{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;text-align:center;transition:all .25s var(--ease-out,ease)}
.support-channel:hover{transform:translateY(-3px);box-shadow:var(--shadow-md,0 8px 24px rgba(0,0,0,.08))}
.support-channel-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
.support-channel-name{font-weight:700;font-size:14px}
.support-channel-sub{font-size:11.5px;color:var(--text3);margin-top:2px}
.faq-item{background:var(--bg2);border-radius:12px;padding:14px 16px;margin-bottom:8px}
.faq-item summary{font-weight:700;font-size:13.5px;cursor:pointer;list-style:none}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::before{content:'+';display:inline-block;margin-left:8px;font-weight:900;color:var(--accent)}
.faq-item[open] summary::before{content:'−'}
.faq-item p{font-size:13px;color:var(--text2);margin-top:10px;line-height:1.7}
@media(max-width:768px){.page-grid-2{grid-template-columns:1fr !important}}
</style>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
