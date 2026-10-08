<?php
class WarrantyController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('warranties'); }

    public function index(): void
    {
        $search = trim($this->get('search',''));
        $status = trim($this->get('status',''));
        $page   = max(1,(int)$this->get('page',1));
        $limit  = 20; $offset = ($page-1)*$limit;

        $where=['1=1']; $params=[];
        if ($search) { $where[]='(w.serial_number LIKE ? OR w.customer_name LIKE ? OR w.customer_phone LIKE ? OR w.barcode LIKE ?)'; $s="%$search%"; $params=array_merge($params,[$s,$s,$s,$s]); }
        if ($status) { $where[]='w.status=?'; $params[]=$status; }
        $w=implode(' AND ',$where);

        $total = (int)(Database::fetch("SELECT COUNT(*) c FROM warranties w WHERE $w",$params)['c']??0);
        $warranties = Database::fetchAll("SELECT w.*,p.name product_name, r.name retailer_name FROM warranties w LEFT JOIN products p ON p.id=w.product_id LEFT JOIN retailers r ON r.id=w.retailer_id WHERE $w ORDER BY w.created_at DESC LIMIT $limit OFFSET $offset",$params);
        $lastPage = (int)ceil($total/$limit);
        $this->view('admin.warranties.index', compact('warranties','total','page','lastPage','search','status'));
    }

    public function check(): void
    {
        $serial = trim($this->post('serial','') ?: $this->get('serial',''));
        if (!$serial) { $this->json(['ok'=>false,'msg'=>'أدخل الرقم التسلسلي']); }
        $w = Database::fetch("SELECT w.*,p.name product_name,p.thumbnail FROM warranties w LEFT JOIN products p ON p.id=w.product_id WHERE w.serial_number=? OR w.barcode=?",[$serial,$serial]);
        if (!$w) { $this->json(['ok'=>false,'msg'=>'لم يتم العثور على ضمان لهذا المنتج']); }
        // Auto update status
        if ($w['status']==='active' && strtotime($w['expiry_date']) < time()) {
            Database::execute("UPDATE warranties SET status='expired' WHERE id=?",[$w['id']]);
            $w['status']='expired';
        }
        $this->json(['ok'=>true,'warranty'=>$w]);
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('warranties')); }
        $serial = trim($this->post('serial_number',''));
        if (!$serial) { flashError('الرقم التسلسلي مطلوب'); $this->redirect(adminUrl('warranties')); }
        $period = trim($this->post('warranty_period','1 سنة'));
        $purchase = $this->post('purchase_date', date('Y-m-d'));
        // Calculate expiry
        $expiry = date('Y-m-d', strtotime($purchase . ' + ' . $this->periodToDays($period) . ' days'));
        try {
            Database::insert("INSERT INTO warranties(product_id,order_id,serial_number,barcode,customer_name,customer_phone,customer_email,purchase_date,expiry_date,warranty_period,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?)",[
                (int)$this->post('product_id',0),
                $this->post('order_id',null) ?: null,
                $serial,
                trim($this->post('barcode','')),
                trim($this->post('customer_name','')),
                trim($this->post('customer_phone','')),
                trim($this->post('customer_email','')),
                $purchase,$expiry,$period,
                trim($this->post('notes','')),
            ]);
            flashSuccess('تم تسجيل الضمان بنجاح');
        } catch (\Throwable $e) {
            flashError('الرقم التسلسلي مسجل مسبقاً');
        }
        $this->redirect(adminUrl('warranties'));
    }

    public function update(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('warranties')); }
        Database::execute("UPDATE warranties SET status=?,notes=? WHERE id=?",[$this->post('status','active'),trim($this->post('notes','')), (int)$id]);
        flashSuccess('تم التحديث');
        $this->redirect(adminUrl('warranties'));
    }

    private function periodToDays(string $p): int
    {
        if (str_contains($p,'سنة')||str_contains($p,'year')) return (int)$p * 365;
        if (str_contains($p,'شهر')||str_contains($p,'month')) return (int)$p * 30;
        return 365;
    }
}
