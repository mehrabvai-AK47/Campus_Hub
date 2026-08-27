<?php
require 'config.php';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$hash = hash('sha256', $token);
$stmt = db()->prepare('SELECT id,user_id FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
$stmt->bind_param('s', $hash);
$stmt->execute();
$reset = $stmt->get_result()->fetch_assoc();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$reset) $error = 'This reset link is invalid or expired.';
    elseif (strlen($password) < 8 || $password !== $confirm) $error = 'Use matching passwords of at least 8 characters.';
    else { $db=db();$db->begin_transaction();try{$newHash=password_hash($password,PASSWORD_DEFAULT);$stmt=$db->prepare('UPDATE users SET password_hash=? WHERE id=?');$stmt->bind_param('si',$newHash,$reset['user_id']);$stmt->execute();$stmt=$db->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?');$stmt->bind_param('i',$reset['id']);$stmt->execute();$db->commit();redirect('login.php?password=reset');}catch(Throwable $exception){$db->rollback();$error='Password could not be reset.';}}
}
page_start('Set new password');
?><section class="section-space"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-4"><div class="eyebrow">Account recovery</div><h1 class="display-6 mb-4">Set new password.</h1><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if($reset): ?><form method="post" class="bg-white rounded-3 p-4"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="token" value="<?= e($token) ?>"><div class="form-outline mb-3"><input class="form-control" type="password" name="password" minlength="8" required><label class="form-label">New password</label></div><div class="form-outline mb-4"><input class="form-control" type="password" name="confirm_password" minlength="8" required><label class="form-label">Confirm password</label></div><button class="btn btn-dark btn-rounded w-100">Save new password</button></form><?php elseif(!$error): ?><div class="alert alert-danger">This reset link is invalid or expired.</div><?php endif; ?></div></div></div></section><?php page_end(); ?>