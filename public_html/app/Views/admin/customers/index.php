<?php
$pageTitle  = 'العملاء';
$breadcrumb = [['label' => 'العملاء']];
$a = ADMIN_PREFIX;
$data     = $paginator['data']         ?? [];
$total    = $paginator['total']        ?? 0;
$curPage  = $paginator['current_page'] ?? 1;
$lastPage = $paginator['last_page']    ?? 1;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>العملاء</h1><p><?= number_format($total) ?> عميل</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('bulk/customers') ?>" class="btn btn-secondary">✏️ تعديل بالجملة</a>
    <a href="<?= adminUrl('import/shopify-customers') ?>" class="btn btn-secondary"><?= svgIcon('upload', 15) ?> استيراد</a>
    <a href="<?= adminUrl('wholesale') ?>" class="btn btn-secondary">🏬 حسابات الجملة</a>
  </div>
</div>
<div class="card">
  <div style="padding:12px 14px;border-bottom:1px solid var(--bd)">
    <form method="GET" class="filter-bar">
      <div class="search-box">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="search" class="search-input" placeholder="بحث بالاسم، البريد، الهاتف..." value="<?= e($search??'') ?>">
      </div>
      <button type="submit" class="btn btn-secondary">بحث</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:36px"><input type="checkbox" style="cursor:pointer;accent-color:var(--ac)" disabled title="استخدم صفحة التعديل بالجملة للتحديد المتعدد"></th>
          <th>اسم العميل</th>
          <th>الموقع</th>
          <th>الطلبات</th>
          <th>الإجمالي المُنفَق</th>
          <th>رصيد N&</th>
          <th>الحالة</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="8"><div class="empty"><div class="empty-icon"><?= svgIcon('user', 32) ?></div><div class="empty-title">لا يوجد عملاء بعد</div></div></td></tr>
        <?php else: foreach ($data as $c): ?>
          <tr style="cursor:pointer" onclick="location.href='<?= adminUrl('customers/'.$c['id']) ?>'">
            <td onclick="event.stopPropagation()"><input type="checkbox" disabled style="cursor:not-allowed;opacity:.3"></td>
            <td>
              <a href="<?= adminUrl('customers/'.$c['id']) ?>" style="display:flex;align-items:center;gap:9px">
                <div style="width:30px;height:30px;border-radius:50%;background:var(--acb2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--ac);flex-shrink:0"><?= mb_substr($c['name'],0,1) ?></div>
                <div>
                  <div style="font-weight:600;color:var(--t1)">
                    <?= e($c['name']) ?>
                    <?php if (($c['account_type']??'retail')==='wholesale'): ?>
                      <span class="badge <?= match($c['wholesale_status']){'approved'=>'badge-ok','rejected'=>'badge-err',default=>'badge-warn'} ?>" style="margin-right:4px">🏬</span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:11px;color:var(--t3)"><?= e($c['email']) ?></div>
                </div>
              </a>
            </td>
            <td class="td-dim">
              <?php if ($c['addr_city']??null): ?>
                <?= e($c['addr_city']) ?><?= $c['addr_gov']?'، '.e($c['addr_gov']):'' ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-weight:600"><?= number_format($c['total_orders']??0) ?></td>
            <td style="font-weight:600"><?= money($c['total_spent']??0) ?></td>
            <td>
              <?php if (($c['store_credit']??0) > 0): ?>
                <span style="font-weight:700;color:var(--green)"><?= money($c['store_credit']) ?></span>
              <?php else: ?>
                <span class="td-dim">—</span>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= ($c['is_active']??0)?'badge-ok':'badge-err' ?>"><?= ($c['is_active']??0)?'نشط':'موقوف' ?></span></td>
            <td onclick="event.stopPropagation()"><a href="<?= adminUrl('customers/'.$c['id']) ?>" class="btn btn-secondary btn-sm">عرض</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($lastPage > 1): ?>
    <div class="pagination">
      <?php for ($i=1;$i<=$lastPage;$i++): ?>
        <a href="?page=<?=$i?>&search=<?=urlencode($search??'')?>" class="page-btn <?=$i===$curPage?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
