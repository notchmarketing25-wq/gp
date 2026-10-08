<?php
class Fawateerk
{
    private static function apiKey(): string
    {
        return SettingModel::get('fawateerk_api_key') ?: FAWATEERK_API_KEY;
    }

    public static function isActive(): bool
    {
        return (bool)SettingModel::get('fawateerk_active', '0') && self::apiKey();
    }

    public static function createInvoice(array $order): array
    {
        $payload = [
            'payment_method_id'  => 1, // Card payment
            'cartTotal'          => $order['total'],
            'currency'           => 'EGP',
            'customer'           => [
                'first_name' => explode(' ', $order['customer_name'])[0],
                'last_name'  => explode(' ', $order['customer_name'])[1] ?? '',
                'email'      => $order['customer_email'],
                'phone'      => $order['customer_phone'],
                'address'    => $order['shipping_address'],
            ],
            'order_id'            => $order['order_number'],
            'redirection_url'     => FAWATEERK_REDIRECT_URL,
            'notify_customer_via_email' => true,
        ];

        $response = self::post('/createInvoice', $payload);

        if (isset($response['url'])) {
            return ['success' => true, 'url' => $response['url'], 'ref' => $response['data']['invoice_key'] ?? ''];
        }

        return ['success' => false, 'message' => $response['message'] ?? 'حدث خطأ في البوابة'];
    }

    public static function verifyCallback(array $data): array
    {
        if (empty($data['invoice_key'])) {
            return ['success' => false, 'order_number' => ''];
        }

        $response = self::get('/getInvoice/' . $data['invoice_key']);

        $paid = isset($response['data']['status']) && $response['data']['status'] === 'paid';

        return [
            'success'      => $paid,
            'order_number' => $response['data']['order_id'] ?? '',
            'ref'          => $data['invoice_key'],
        ];
    }

    private static function post(string $endpoint, array $data): array
    {
        $ch = curl_init(FAWATEERK_API_URL . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::apiKey(),
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true) ?? [];
    }

    private static function get(string $endpoint): array
    {
        $ch = curl_init(FAWATEERK_API_URL . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . self::apiKey(),
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true) ?? [];
    }
}
