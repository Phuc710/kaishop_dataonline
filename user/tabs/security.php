<?php
// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            $success = 'Đổi mật khẩu thành công!';
        } else {
            $error = 'Mật khẩu hiện tại không đúng!';
        }
    }
}
?>

<div class="page-header fade-in">
    <div>
        <h1><i class="fas fa-shield-alt"></i> Bảo Mật</h1>
        <p>Quản lý mật khẩu và cài đặt bảo mật tài khoản</p>
    </div>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success fade-in">
    <i class="fas fa-check-circle"></i>
    <div><?= $success ?></div>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger fade-in">
    <i class="fas fa-exclamation-circle"></i>
    <div><?= $error ?></div>
</div>
<?php endif; ?>

<div class="card fade-in">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-lock"></i> Đổi Mật Khẩu</h2>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="change_password">
        
        <div class="form-group">
            <label><i class="fas fa-key"></i> Mật Khẩu Hiện Tại</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> Mật Khẩu Mới</label>
            <input type="password" name="new_password" class="form-control" minlength="6" required>
            <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                Mật khẩu phải có ít nhất 6 ký tự
            </small>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> Xác Nhận Mật Khẩu Mới</label>
            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save"></i> Đổi Mật Khẩu
        </button>
    </form>
</div>
