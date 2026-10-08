<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px">
  <form method="POST" action="<?= url('wholesale/apply') ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label class="form-label">اسم الشركة / المحل <span>*</span></label>
      <input type="text" name="company_name" class="form-input" value="<?= e($customer['company_name'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">عنوان النشاط</label>
      <input type="text" name="company_address" class="form-input" value="<?= e($customer['company_address'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">الرقم الضريبي (اختياري)</label>
      <input type="text" name="tax_number" class="form-input" value="<?= e($customer['tax_number'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">ملاحظات إضافية</label>
      <textarea name="notes" class="form-textarea" rows="3" placeholder="نوع النشاط، الكميات المتوقعة، إلخ..."><?= e($customer['wholesale_notes'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg">إرسال الطلب</button>
  </form>
</div>
