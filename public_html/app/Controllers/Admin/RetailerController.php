<?php
class RetailerController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('warranties'); }

    public function index(): void
    {
        $retailers = Database::fetchAll("SELECT * FROM retailers ORDER BY sort_order, name");
        $this->view('admin.retailers.index', compact('retailers'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('retailers')); }
        $logo = null;
        if (!empty($_FILES['logo']['name'])) {
            $up = uploadFile($_FILES['logo'], 'retailers');
            if (!$up) { flashError(uploadFileError() ?? 'فشل رفع الشعار'); $this->redirect(adminUrl('retailers')); }
            $logo = $up;
        }
        Database::insert(
            "INSERT INTO retailers(name,logo,phone,address,governorate,map_link,latitude,longitude,website,type,sort_order,is_active,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [
                trim($this->post('name','')), $logo, trim($this->post('phone','')),
                trim($this->post('address','')), trim($this->post('governorate','')),
                trim($this->post('map_link','')),
                $this->post('latitude') !== '' ? (float)$this->post('latitude') : null,
                $this->post('longitude') !== '' ? (float)$this->post('longitude') : null,
                trim($this->post('website','')),
                in_array($this->post('type'), ['store','online'], true) ? $this->post('type') : 'store',
                (int)$this->post('sort_order',0), isset($_POST['is_active'])?1:0,
            ]
        );
        flashSuccess('تم إضافة المتجر ✅');
        $this->redirect(adminUrl('retailers'));
    }

    public function update(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('retailers')); }
        $r = Database::fetch("SELECT * FROM retailers WHERE id=?", [(int)$id]);
        if (!$r) { flashError('غير موجود'); $this->redirect(adminUrl('retailers')); }

        $logo = $r['logo'];
        if (!empty($_FILES['logo']['name'])) {
            $up = uploadFile($_FILES['logo'], 'retailers');
            if (!$up) { flashError(uploadFileError() ?? 'فشل رفع الشعار'); $this->redirect(adminUrl('retailers')); }
            $logo = $up;
        }

        Database::execute(
            "UPDATE retailers SET name=?,logo=?,phone=?,address=?,governorate=?,map_link=?,latitude=?,longitude=?,website=?,type=?,sort_order=?,is_active=? WHERE id=?",
            [
                trim($this->post('name','')), $logo, trim($this->post('phone','')),
                trim($this->post('address','')), trim($this->post('governorate','')),
                trim($this->post('map_link','')),
                $this->post('latitude') !== '' ? (float)$this->post('latitude') : null,
                $this->post('longitude') !== '' ? (float)$this->post('longitude') : null,
                trim($this->post('website','')),
                in_array($this->post('type'), ['store','online'], true) ? $this->post('type') : 'store',
                (int)$this->post('sort_order',0), isset($_POST['is_active'])?1:0, (int)$id,
            ]
        );
        flashSuccess('تم التحديث ✅');
        $this->redirect(adminUrl('retailers'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('retailers')); }
        Database::execute("DELETE FROM retailers WHERE id=?", [(int)$id]);
        flashSuccess('تم الحذف');
        $this->redirect(adminUrl('retailers'));
    }
}
