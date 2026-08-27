<?php
require 'config.php';
require_login();
$user = current_user();
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid name and email address.';
        } else {
            try {
                $stmt = db()->prepare('UPDATE users SET name=?,email=? WHERE id=?');
                $stmt->bind_param('ssi', $name, $email, $user['id']);
                $stmt->execute();
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                $user = current_user();
                $message = 'Profile updated.';
            } catch (mysqli_sql_exception $exception) {
                $error = $exception->getCode() === 1062 ? 'That email is already in use.' : 'Profile could not be updated.';
            }
        }
    }
    if ($action === 'delete') {
        $password = $_POST['password'] ?? '';
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id=?');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $stored = $stmt->get_result()->fetch_assoc();
        if (!$stored || !password_verify($password, $stored['password_hash'])) {
            $error = 'Your password was incorrect, so the account was not deleted.';
        } else {
            $db = db();
            $db->begin_transaction();
            try {
                $stmt = $db->prepare('DELETE FROM tickets WHERE user_id=?');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $stmt = $db->prepare('DELETE oi FROM order_items oi INNER JOIN orders o ON o.id=oi.order_id WHERE o.user_id=?');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $stmt = $db->prepare('DELETE FROM orders WHERE user_id=?');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $stmt = $db->prepare('DELETE FROM users WHERE id=?');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                $db->commit();
                $_SESSION = [];
                session_destroy();
                redirect('index.php?account=deleted');
            } catch (Throwable $exception) {
                $db->rollback();
                $error = 'The account could not be deleted.';
            }
        }
    }
}
page_start('Profile settings');
?>
<section class="section-space"><div class="container"><div class="eyebrow">Account management</div><h1 class="display-5 mb-5">Your profile.</h1><div class="row g-4"><div class="col-lg-7"><form method="post" class="bg-white rounded-3 p-4"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="update"><h2 class="h5 mb-4">Profile information</h2><?php if($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><div class="form-outline mb-3"><input class="form-control" name="name" value="<?= e($user['name']) ?>" required><label class="form-label">Full name</label></div><div class="form-outline mb-4"><input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required><label class="form-label">Email address</label></div><button class="btn btn-dark btn-rounded">Save changes</button><a class="btn btn-link text-dark" href="change_password.php">Change password</a></form><div class="bg-white rounded-3 p-4 mt-4 border border-danger"><h2 class="h5 text-danger">Delete account</h2><p class="small text-muted">This permanently deletes your profile, orders, and support tickets.</p><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete"><div class="form-outline mb-3"><input class="form-control" type="password" name="password" required><label class="form-label">Confirm with your password</label></div><button class="btn btn-outline-danger btn-rounded">Delete my account</button></form></div></div></div></div></section>
+<?php page_end(); ?>