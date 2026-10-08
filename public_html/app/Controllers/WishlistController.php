<?php
class WishlistController extends Controller
{
    public function toggle(): void
    {
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }
        if (!isStoreLoggedIn()) { jsonResponse(false, 'يجب تسجيل الدخول', ['redirect' => url('login')]); }
        $customerId = storeUser()['id'];
        $productId  = (int)$this->post('product_id');
        $exists     = Database::fetch("SELECT id FROM `wishlists` WHERE customer_id=? AND product_id=?", [$customerId, $productId]);
        if ($exists) {
            Database::execute("DELETE FROM `wishlists` WHERE customer_id=? AND product_id=?", [$customerId, $productId]);
            jsonResponse(true, 'تم الحذف من المفضلة', ['inWishlist' => false]);
        } else {
            Database::insert("INSERT INTO `wishlists`(customer_id,product_id)VALUES(?,?)", [$customerId, $productId]);
            jsonResponse(true, 'تمت الإضافة للمفضلة', ['inWishlist' => true]);
        }
    }
}
