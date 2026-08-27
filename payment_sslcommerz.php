<?php
require 'config.php';
require_login();
$orderId = (int)($_GET['order'] ?? 0);
$stmt = db()->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=? AND o.user_id=? AND o.status='pending'");
$stmt->bind_param('ii', $orderId, $_SESSION['user']['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) exit('Payment session not found.');
$baseUrl = SSLCOMMERZ_SANDBOX ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
$payload = [
    'store_id' => SSLCOMMERZ_STORE_ID,
    'store_passwd' => SSLCOMMERZ_STORE_PASSWORD,
    'total_amount' => number_format((float)$order['total'], 2, '.', ''),
    'currency' => 'BDT',
    'tran_id' => $order['transaction_id'],
    'success_url' => APP_URL . '/payment_callback.php?status=success',
    'fail_url' => APP_URL . '/payment_callback.php?status=failed',
    'cancel_url' => APP_URL . '/payment_callback.php?status=cancelled',
    'ipn_url' => APP_URL . '/payment_callback.php?status=ipn',
    'cus_name' => $order['name'],
    'cus_email' => $order['email'],
    'shipping_method' => 'NO',
    'product_name' => APP_NAME . ' digital products',
    'product_category' => 'Digital goods',
    'product_profile' => 'general',
];
$curl = curl_init($baseUrl . '/gwprocess/v4/api.php');
curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($payload), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
$response = curl_exec($curl);
$curlError = curl_error($curl);
curl_close($curl);
$data = json_decode($response ?: '', true);
if ($curlError || empty($data['GatewayPageURL'])) {
    exit('Unable to start the SSLCOMMERZ payment session.');
}
redirect($data['GatewayPageURL']);
