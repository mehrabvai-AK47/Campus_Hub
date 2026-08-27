<?php
require 'config.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email=? AND status=\'active\' LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $stmt = db()->prepare('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(), INTERVAL 1 HOUR))');
            $stmt->bind_param('is', $user['id'], $hash);
            $stmt->execute();
            $link = APP_URL . '/reset_password.php?token=' . urlencode($token);
            @mail($email, APP_NAME . ' password reset', "Reset your password within one hour: $link");
        }
    }
    $message = 'If an active account uses that email, a password reset link has been sent.';
}
page_start('Reset password');
?><section class="section-space"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-4"><div class="eyebrow">Account recovery</div><h1 class="display-6 mb-4">Reset password.</h1><?php if($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><form method="post" class="bg-white rounded-3 p-4"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><div class="form-outline mb-4"><input class="form-control" type="email" name="email" required><label class="form-label">Account email</label></div><button class="btn btn-dark btn-rounded w-100">Send reset link</button></form><p class="small text-muted mt-3"><a href="login.php">Back to sign in</a></p></div></div></div></section><?php page_end(); ?>