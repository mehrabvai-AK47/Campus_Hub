<?php
require 'config.php';
require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id=?');
    $stmt->bind_param('i', $_SESSION['user']['id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || !password_verify($current, $user['password_hash'])) $error = 'Your current password is incorrect.';
    elseif (strlen($new) < 8) $error = 'Your new password must be at least 8 characters.';
    elseif ($new !== $confirm) $error = 'The new passwords do not match.';
    else { $hash=password_hash($new,PASSWORD_DEFAULT);$stmt=db()->prepare('UPDATE users SET password_hash=? WHERE id=?');$stmt->bind_param('si',$hash,$_SESSION['user']['id']);$stmt->execute();redirect('profile.php?password=changed'); }
}
page_start('Change password');
?><section class="section-space"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5"><div class="eyebrow">Account security</div><h1 class="display-6 mb-4">Change password.</h1><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post" class="bg-white rounded-3 p-4"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><?php foreach([['current_password','Current password'],['new_password','New password'],['confirm_password','Confirm new password']] as $field): ?><div class="form-outline mb-3"><input class="form-control" type="password" name="<?= $field[0] ?>" minlength="8" required><label class="form-label"><?= $field[1] ?></label></div><?php endforeach; ?><button class="btn btn-dark btn-rounded">Update password</button><a href="profile.php" class="btn btn-link text-dark">Cancel</a></form></div></div></div></section><?php page_end(); ?>