<?php
$titles = ['merchant'=>['store','تسجيل حساب تاجر','بيع منتجاتنا في محلك بأسعار جملة تنافسية'],'distributor'=>['truck','تسجيل حساب موزع','وزّع منتجاتنا لشبكة عملائك بأسعار خاصة'],'rep'=>['briefcase','تسجيل حساب مندوب','مثّلنا في السوق واكسب عمولة على كل فاتورة']];
[$icon,$title,$desc] = $titles[$type];
$pageTitle = $title . ' — ' . SettingModel::get('store_name', APP_NAME);
$isLoggedIn = isStoreLoggedIn();
$user = $isLoggedIn ? storeUser() : null;
$govOptions = ['القاهرة','الجيزة','الإسكندرية','القليوبية','المنوفية','الغربية','الدقهلية','الشرقية','البحيرة','كفر الشيخ','دمياط','بورسعيد','الإسماعيلية','السويس','شمال سيناء','جنوب سيناء','الفيوم','بني سويف','المنيا','أسيوط','سوهاج','قنا','الأقصر','أسوان','البحر الأحمر','مطروح','الوادي الجديد'];
ob_start();
?>
<div class="container biz-reg-page" style="padding-top:36px;padding-bottom:80px;max-width:600px;margin:0 auto">
  <div style="text-align:center;margin-bottom:28px">
    <div class="biz-reg-icon"><?= svgIcon($icon, 30, 1.6) ?></div>
    <h1 style="font-family:var(--font-body);font-size:25px;font-weight:900;margin-bottom:6px"><?= $title ?></h1>
    <p style="color:var(--text2);font-size:13.5px"><?= $desc ?></p>
  </div>

  <div class="biz-reg-card">
    <div style="background:var(--accent-bg);border:1px solid rgba(var(--accent-rgb),.25);border-radius:12px;padding:14px 18px;margin-bottom:22px;display:flex;align-items:center;gap:12px;font-size:12.5px;color:var(--text2)">
      <?= svgIcon('star', 17) ?> <span><b style="color:var(--text)">هدية تسجيل: 1000 جنيه رصيد مجاني</b> — هيتضاف تلقائي لحسابك كـ N&Credit بمجرد اعتماد طلبك من فريقنا</span>
    </div>
    <form method="POST" action="<?= url('business/register/'.$type) ?>">
      <?= csrf_field() ?>

      <div class="biz-reg-section-title">بياناتك الشخصية</div>
      <div class="form-group">
        <label class="form-label">الاسم الكامل <span>*</span></label>
        <input type="text" name="name" class="form-input" required value="<?= e($user['name'] ?? '') ?>">
      </div>
      <?php if (!$isLoggedIn): ?>
        <div class="biz-reg-row-2">
          <div class="form-group">
            <label class="form-label">البريد الإلكتروني <span>*</span></label>
            <input type="email" name="email" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">كلمة المرور <span>*</span></label>
            <input type="password" name="password" class="form-input" required minlength="8">
          </div>
        </div>
      <?php endif; ?>
      <div class="form-group">
        <label class="form-label">رقم الهاتف <span>*</span></label>
        <input type="tel" name="phone" class="form-input" required value="<?= e($user['phone'] ?? '') ?>" placeholder="01xxxxxxxxx">
      </div>

      <div class="biz-reg-section-title">الموقع</div>
      <div class="biz-reg-row-2">
        <div class="form-group">
          <label class="form-label">المحافظة <span>*</span></label>
          <select name="governorate" class="form-select" required>
            <option value="">اختر المحافظة</option>
            <?php foreach ($govOptions as $gov): ?><option value="<?= e($gov) ?>"><?= e($gov) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">المنطقة <span>*</span></label>
          <input type="text" name="area" class="form-input" required placeholder="مثال: مدينة نصر">
        </div>
      </div>

      <?php if ($type !== 'rep'): ?>
        <div class="biz-reg-section-title">النشاط التجاري</div>
        <div class="form-group">
          <label class="form-label">اسم المحل / الموقع <span>*</span></label>
          <input type="text" name="shop_name" class="form-input" required placeholder="اسم محلك أو نشاطك التجاري">
        </div>
        <div class="form-group">
          <label class="form-label">حجم المبيعات الشهرية المتوقع (تقريبي، بالجنيه)</label>
          <input type="number" name="expected_monthly_sales" class="form-input" min="0" step="100" placeholder="مثال: 20000">
          <span class="form-hint">بيساعدنا نحدد الشريحة السعرية المناسبة ليك من البداية — تقدير تقريبي يكفي</span>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:10px">إرسال الطلب</button>
      <p style="font-size:11.5px;color:var(--text3);text-align:center;margin-top:14px">هيتم مراجعة طلبك واعتماده من فريق نوتش قبل تفعيل الحساب</p>
    </form>
  </div>
</div>

<style>
.biz-reg-icon{width:64px;height:64px;border-radius:18px;background:var(--accent-bg);color:var(--accent);display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.biz-reg-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:30px}
.biz-reg-section-title{font-size:11px;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.06em;margin:22px 0 12px;padding-top:18px;border-top:1px solid var(--border)}
.biz-reg-card > form > .biz-reg-section-title:first-child{margin-top:0;padding-top:0;border-top:none}
.biz-reg-row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:560px){
  .biz-reg-card{padding:22px}
  .biz-reg-row-2{grid-template-columns:1fr}
}
</style>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
