<?php
class CheckoutController extends Controller
{
    public function index(): void
    {
        if (Cart::isEmpty()) { $this->redirect(url('cart')); }
        $this->view('store.pages.checkout');
    }

    public function process(): void
    {
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect(url('checkout')); }
        if (Cart::isEmpty()) { $this->redirect(url('cart')); }

        $orderModel = new OrderModel();
        $discount   = Cart::getDiscount();
        $shipping   = (float)$this->post('shipping_price', 0);
        $summary    = Cart::summary($shipping);

        // Store credit (N& Credit) — only for logged-in customers, capped at their balance and the order total
        $creditUsed = 0;
        $customerModel = new CustomerModel();
        if (isStoreLoggedIn() && $this->post('use_credit')) {
            $customer = $customerModel->find(storeUser()['id']);
            $available = (float)($customer['store_credit'] ?? 0);
            $creditUsed = min($available, max(0, $summary['total']));
        }

        $orderData = [
            'order_number'     => $orderModel->generateOrderNumber(),
            'customer_id'      => isStoreLoggedIn() ? storeUser()['id'] : null,
            'order_type'       => Cart::hasWholesaleItems() ? 'wholesale' : 'retail',
            'customer_name'    => trim($this->post('customer_name', '')),
            'customer_email'   => trim($this->post('customer_email', '')),
            'customer_phone'   => trim($this->post('customer_phone', '')),
            'shipping_address' => trim($this->post('shipping_address', '')),
            'shipping_city'    => trim($this->post('shipping_city', '')),
            'shipping_gov'     => trim($this->post('shipping_gov', '')),
            'shipping_price'   => $shipping,
            'subtotal'         => $summary['subtotal'],
            'discount_code'    => $discount['code'],
            'discount_amount'  => $discount['amount'],
            'credit_used'      => $creditUsed,
            'total'            => max(0, $summary['total'] - $creditUsed),
            'payment_method'   => $creditUsed >= $summary['total'] ? 'credit' : $this->post('payment_method', 'cod'),
            'payment_status'   => $creditUsed >= $summary['total'] ? 'paid' : 'unpaid',
            'status'           => 'pending',
            'notes'            => trim($this->post('notes', '')),
        ];

        $items = [];
        foreach (Cart::get() as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'name'       => $item['name'],
                'variant'    => $item['variant'],
                'sku'        => $item['sku'],
                'price'      => $item['price'],
                'qty'        => $item['qty'],
                'image'      => $item['image'],
            ];
        }

        try {
            $orderId = $orderModel->createWithItems($orderData, $items);

            // If N&Credit fully covered the order, it's already payment_status='paid' at this
            // point (see $orderData above) — trigger the same automatic stock-out voucher used
            // for gateway/manual payments, since this order will never pass through either of
            // those other two trigger points.
            if ($orderData['payment_status'] === 'paid') {
                createIssueVoucherForOrder($orderId);
            }

            // Deduct used store credit and log the transaction
            if ($creditUsed > 0 && isStoreLoggedIn()) {
                $customerModel->adjustCredit(storeUser()['id'], -$creditUsed, 'استخدام في الطلب #' . $orderData['order_number'], $orderId);
            }

            // Increment discount usage
            if ($discount['id']) {
                (new DiscountModel())->incrementUsed($discount['id']);
            }

            // Update customer stats
            if (isStoreLoggedIn()) {
                $customerModel->updateStats(storeUser()['id']);
            }

            $orderNumber = $orderData['order_number'];

            try {
                EmailService::send('order_confirmation', $orderData['customer_email'], $orderData['customer_name'],
                    ['name'=>$orderData['customer_name'], 'order_number'=>$orderNumber, 'total'=>money($orderData['total'])],
                    'order', $orderId);
            } catch (\Throwable $e) {}

            // Fawateerk redirect
            if ($orderData['payment_method'] === 'fawateerk' && Fawateerk::isActive()) {
                $result = Fawateerk::createInvoice(array_merge($orderData, ['id' => $orderId]));
                if ($result['success']) {
                    Database::execute("UPDATE `orders` SET `payment_ref`=? WHERE `id`=?", [$result['ref'], $orderId]);
                    Cart::clear();
                    header('Location: ' . $result['url']);
                    exit;
                }
                // Payment gateway failed — the order is saved (unpaid) but we must NOT clear the
                // cart or show a success page, since the customer never actually paid.
                Session::set('_checkout_old_input', $_POST);
                flashError('تعذر إتمام الدفع الإلكتروني: ' . ($result['message'] ?? 'خطأ من بوابة الدفع') . ' — طلبك محفوظ برقم ' . $orderData['order_number'] . '، تقدر تجرب تاني أو تختار الدفع عند الاستلام.');
                $this->redirect(url('checkout'));
            }

            Cart::clear();
            Session::remove('_checkout_old_input');
            $this->redirect(url('checkout/success/' . $orderNumber));
        } catch (\RuntimeException $e) {
            // Thrown by OrderModel::createWithItems() when stock ran out for an item between
            // add-to-cart and checkout — show the specific, actionable message instead of a
            // generic failure so the customer knows to adjust their cart, not just retry.
            Session::set('_checkout_old_input', $_POST);
            flashError($e->getMessage());
            $this->redirect(url('cart'));
        } catch (\Throwable $e) {
            Session::set('_checkout_old_input', $_POST);
            error_log('[Checkout] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            flashError('حدث خطأ أثناء معالجة الطلب. بياناتك محفوظة، حاول مرة أخرى، ولو استمرت المشكلة تواصل معنا.');
            $this->redirect(url('checkout'));
        }
    }

    public function success(string $orderNumber): void
    {
        $order = (new OrderModel())->getByNumber($orderNumber);
        if (!$order) { $this->redirect(url()); }
        $this->view('store.pages.success', compact('order'));
    }
}
