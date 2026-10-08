<?php
class WarrantyPublicController extends Controller
{
    // ── Activation form + status check ───────────────────────────────
    public function form(): void
    {
        $products = Database::fetchAll("SELECT id, name FROM products WHERE status='active' ORDER BY name");
        $retailers = Database::fetchAll("SELECT id, name FROM retailers WHERE is_active=1 ORDER BY sort_order, name");
        $this->view('store.pages.warranty-activate', compact('products', 'retailers'));
    }

    public function activate(): void
    {
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect(url('warranty')); }

        $serial = trim($this->post('serial_number',''));
        $productId = (int)$this->post('product_id', 0);
        $name = trim($this->post('customer_name',''));
        $phone = trim($this->post('customer_phone',''));

        if (!$serial || !$productId || !$name || !$phone) {
            flashError('من فضلك املأ كل الحقول المطلوبة');
            $this->redirect(url('warranty'));
        }

        $existing = Database::fetch("SELECT id FROM warranties WHERE serial_number=?", [$serial]);
        if ($existing) {
            flashError('الرقم التسلسلي ده متسجل بالفعل — لو ده منتجك، تواصل معنا للتأكد');
            $this->redirect(url('warranty'));
        }

        $product = Database::fetch("SELECT * FROM products WHERE id=?", [$productId]);
        if (!$product) { flashError('المنتج غير موجود'); $this->redirect(url('warranty')); }

        $purchaseDate = $this->post('purchase_date') ?: date('Y-m-d');
        $periodText   = $product['warranty_period'] ?: '1 سنة';
        $months = (int)preg_replace('/[^0-9]/', '', $periodText) ?: 12;
        if (str_contains($periodText, 'سنة') || str_contains($periodText, 'سنوات')) $months *= 12;
        $expiryDate = date('Y-m-d', strtotime($purchaseDate . " +{$months} months"));

        // Optional supporting documents
        $receiptImg = $invoiceImg = $cardImg = null;
        if (!empty($_FILES['receipt_image']['name'])) {
            $up = uploadFile($_FILES['receipt_image'], 'warranties');
            if ($up) $receiptImg = $up;
        }
        if (!empty($_FILES['invoice_image']['name'])) {
            $up = uploadFile($_FILES['invoice_image'], 'warranties');
            if ($up) $invoiceImg = $up;
        }
        if (!empty($_FILES['warranty_card_image']['name'])) {
            $up = uploadFile($_FILES['warranty_card_image'], 'warranties');
            if ($up) $cardImg = $up;
        }

        $customerId = isStoreLoggedIn() ? storeUser()['id'] : null;

        Database::insert(
            "INSERT INTO warranties(product_id,retailer_id,customer_id,serial_number,barcode,customer_name,customer_phone,customer_email,purchase_date,expiry_date,warranty_period,status,receipt_image,invoice_image,warranty_card_image,notes,created_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [
                $productId, (int)$this->post('retailer_id', 0) ?: null, $customerId,
                $serial, trim($this->post('barcode','')) ?: null,
                $name, $phone, trim($this->post('customer_email','')) ?: null,
                $purchaseDate, $expiryDate, $periodText, 'active',
                $receiptImg, $invoiceImg, $cardImg,
                'تم التفعيل ذاتياً بواسطة العميل عبر الموقع',
            ]
        );

        flashSuccess('تم تفعيل الضمان بنجاح ✅ — الضمان ساري حتى ' . $expiryDate);
        $this->redirect(url('warranty'));
    }

    // ── Check warranty status by serial number ───────────────────────
    public function check(): void
    {
        $serial = trim($this->post('check_serial','') ?: $this->get('serial',''));
        if (!$serial) { $this->json(['ok'=>false,'msg'=>'أدخل الرقم التسلسلي']); }

        $w = Database::fetch(
            "SELECT w.*, p.name product_name, p.thumbnail FROM warranties w LEFT JOIN products p ON p.id=w.product_id WHERE w.serial_number=? OR w.barcode=?",
            [$serial, $serial]
        );
        if (!$w) { $this->json(['ok'=>false,'msg'=>'لم يتم العثور على ضمان مسجّل بهذا الرقم']); }

        if ($w['status'] === 'active' && strtotime($w['expiry_date']) < time()) {
            Database::execute("UPDATE warranties SET status='expired' WHERE id=?", [$w['id']]);
            $w['status'] = 'expired';
        }
        $this->json(['ok'=>true,'warranty'=>$w]);
    }
}
