<?php
require 'config.php';
$status = $_GET['status'] ?? 'failed';
$transaction = trim($_POST['tran_id'] ?? $_GET['tran_id'] ?? '');
$validationId = trim($_POST['val_id'] ?? $_GET['val_id'] ?? '');
if (!$transaction) exit('Invalid payment response.');
$stmt = db()->prepare("SELECT id,total,status FROM orders WHERE transaction_id=? AND payment_gateway='sslcommerz' LIMIT 1");
$stmt->bind_param('s', $transaction);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) exit('Order not found.');
$paid = false;
if (in_array($status, ['success', 'ipn'], true) && $validationId) {
    $baseUrl = SSLCOMMERZ_SANDBOX ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    $url = $baseUrl . '/validator/api/validationserverAPI.php?' . http_build_query(['val_id' => $validationId, 'store_id' => SSLCOMMERZ_STORE_ID, 'store_passwd' => SSLCOMMERZ_STORE_PASSWORD, 'format' => 'json']);
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $response = curl_exec($curl);
    curl_close($curl);
    $data = json_decode($response ?: '', true);
    $paid = ($data['status'] ?? '') === 'VALID' && ($data['tran_id'] ?? '') === $transaction && abs((float)($data['amount'] ?? 0) - (float)$order['total']) < 0.01 && ($data['currency'] ?? '') === 'BDT';
}
if ($paid) {
    $update = db()->prepare("UPDATE orders SET status='paid' WHERE id=? AND status='pending'");
    $update->bind_param('i', $order['id']);
    $update->execute();
    redirect('account.php?payment=success');
}
if ($status === 'cancelled') {
    $update = db()->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND status='pending'");
    $update->bind_param('i', $order['id']);
    $update->execute();
}
redirect('account.php?payment=failed');
