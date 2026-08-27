<?php
require 'config.php';
require_login();
if (empty($_SESSION['cart'])) redirect('cart.php');
$ids = array_keys($_SESSION['cart']);
$marks = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT * FROM products WHERE id IN ($marks) AND is_active=1");
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$result = $stmt->get_result();
$subtotal = 0;
$items = [];
while ($row = $result->fetch_assoc()) {
    $row['qty'] = $_SESSION['cart'][$row['id']];
    $subtotal += $row['price'] * $row['qty'];
    $items[] = $row;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $gateway = $_POST['gateway'] ?? 'sslcommerz';
    if (!in_array($gateway, ['sslcommerz', 'bank_transfer'], true)) {
        $error = 'Please choose a valid payment method.';
    } elseif ($gateway === 'sslcommerz' && (!SSLCOMMERZ_STORE_ID || !SSLCOMMERZ_STORE_PASSWORD)) {
        $error = 'SSLCOMMERZ is not configured yet. Add the store credentials in config.php before accepting payments.';
    } else {
        $db = db();
        $db->begin_transaction();
        try {
            $transaction = 'CDH-' . strtoupper(bin2hex(random_bytes(6)));
            $status = 'pending';
            $stmt = $db->prepare('INSERT INTO orders(user_id,total,status,payment_gateway,transaction_id) VALUES(?,?,?,?,?)');
            $stmt->bind_param('idsss', $_SESSION['user']['id'], $subtotal, $status, $gateway, $transaction);
            $stmt->execute();
            $orderId = $db->insert_id;
            $itemStmt = $db->prepare('INSERT INTO order_items(order_id,product_id,price) VALUES(?,?,?)');
            foreach ($items as $item) {
                $itemStmt->bind_param('iid', $orderId, $item['id'], $item['price']);
                $itemStmt->execute();
            }
            $db->commit();
            $_SESSION['cart'] = [];
            redirect($gateway === 'sslcommerz' ? 'payment_sslcommerz.php?order=' . $orderId : 'account.php?payment=pending');
        } catch (Throwable $exception) {
            $db->rollback();
            $error = 'Checkout could not be completed.';
        }
    }
}
page_start('Checkout');
?>
<section class="section-space"><div class="container"><div class="eyebrow">Bangladesh checkout</div><h1 class="display-5 mb-5">Almost yours.</h1><div class="row g-5"><div class="col-lg-7"><form method="post" class="bg-white p-4 rounded-3"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><h2 class="h5 mb-4">Choose payment method</h2><div class="form-check mb-3"><input class="form-check-input" type="radio" name="gateway" value="sslcommerz" checked><label class="form-check-label"><strong>SSLCOMMERZ</strong><br><span class="text-muted small">Visa, Mastercard, bKash, Nagad, Rocket and supported Bangladesh banking channels</span></label></div><div class="form-check mb-4"><input class="form-check-input" type="radio" name="gateway" value="bank_transfer"><label class="form-check-label"><strong>Bank transfer</strong><br><span class="text-muted small">Order stays pending until an administrator verifies the transfer</span></label></div><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><button class="btn btn-dark btn-lg btn-rounded">Continue securely · <?= money((float)$subtotal) ?></button><p class="small text-muted mt-3 mb-0">Payments are processed in BDT through the selected Bangladesh payment channel.</p></form></div><div class="col-lg-5"><div class="bg-white p-4 rounded-3"><h2 class="h5">Order summary</h2><?php foreach($items as $item): ?><div class="d-flex justify-content-between py-2 small"><span><?= e($item['title']) ?> × <?= $item['qty'] ?></span><strong><?= money((float)($item['price']*$item['qty'])) ?></strong></div><?php endforeach; ?><div class="border-top mt-3 pt-3 d-flex justify-content-between"><strong>Total</strong><strong><?= money((float)$subtotal) ?></strong></div></div></div></div></div></section>
<?php page_end(); ?>