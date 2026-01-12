<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ!';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $user_id]);

                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                $success = 'Cập nhật thông tin thành công!';
            } catch (Exception $e) {
                $error = 'Có lỗi xảy ra khi cập nhật!';
            }
        }
    }
}
?>

<style>
    /* Mobile Responsive for Settings */
    .danger-zone-btn {
        width: 20%;
    }

    @media (max-width: 768px) {
        .danger-zone-btn {
            width: 100% !important;
        }

        .danger-zone-box {
            padding: 1.5rem !important;
        }

        .danger-zone-box h3 {
            font-size: 1rem !important;
        }

        .danger-zone-box p {
            font-size: 0.875rem !important;
        }
    }
</style>

<div class="page-header fade-in">
    <div>
        <h1><i class="fas fa-cog"></i> Thông Tin Cá Nhân</h1>
        <p>Quản lý thông tin tài khoản của bạn</p>
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

<!-- User Info Card -->
<div class="card fade-in">
    <div
        style="display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--border);">
        <div class="user-avatar" style="width: 100px; height: 100px; font-size: 2.5rem; overflow: hidden;">
            <img src="<?= getUserAvatar($user) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div style="flex: 1;">
            <h2 style="color: var(--text-primary); font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">
                <?= e($user['username']) ?>
            </h2>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <span class="user-role <?= $user['role'] === 'admin' ? 'admin' : 'member' ?>">
                    <i class="fas fa-<?= $user['role'] === 'admin' ? 'crown' : 'star' ?>"></i>
                    <?= $user['role'] === 'admin' ? 'ADMIN' : 'MEMBER' ?>
                </span>
                <span style="color: var(--text-secondary); font-size: 0.95rem;">
                    <i class="fas fa-calendar-alt"></i>
                    Tham gia: <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                </span>
            </div>
        </div>
    </div>

    <form method="POST" style="display: grid; gap: 2rem;">
        <input type="hidden" name="action" value="update_profile">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- Username -->
            <div class="form-group">
                <label>
                    <i class="fas fa-user"></i> Tên Đăng Nhập
                </label>
                <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    Không thể thay đổi tên đăng nhập
                </small>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label>
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    Email dùng để nhận thông tin đơn hàng
                </small>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- Balance -->
            <div class="form-group">
                <label>
                    <i class="fas fa-wallet"></i> Số Dư Tài Khoản
                </label>
                <?php
                $current_currency = $_COOKIE['currency'] ?? 'VND';
                // Get exchange rate from DB
                $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
                $exchange_rate = floatval($stmt->fetchColumn() ?? 25000);
                ?>
                <input type="text" class="form-control" value="<?php
                if ($current_currency === 'USD') {
                    echo '$' . number_format(($user['balance_vnd'] ?? 0) / $exchange_rate, 2);
                } else {
                    echo formatVND($user['balance_vnd'] ?? 0);
                }
                ?>" disabled>
                <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    <span
                        style="background: linear-gradient(135deg, #10b981, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700;">Tỷ
                        giá: 1 USD = <?= number_format($exchange_rate) ?>đ</span>
                </small>
            </div>

            <!-- Created Date -->
            <div class="form-group">
                <label>
                    <i class="fas fa-calendar"></i> Ngày Tham Gia
                </label>
                <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>"
                    disabled>
                <small style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    Thành viên từ <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                </small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save"></i> Cập Nhật Thông Tin
        </button>
    </form>
</div>

<!-- Danger Zone - Full Width -->
<div class="card fade-in">
    <div class="danger-zone-box"
        style="padding: 2rem; background: rgba(239, 68, 68, 0.05); border: 2px solid rgba(239, 68, 68, 0.3); border-radius: 16px;">
        <h3 style="color: var(--danger); font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fas fa-exclamation-triangle"></i> Vùng Nguy Hiểm
        </h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            <strong>CẢNH BÁO:</strong> Tài khoản sẽ xóa <span style="color: red;font-size:17px;font-weight:bolder;">vĩnh
                viễn</span> tất cả dữ liệu của bạn bao gồm: <u>đơn hàng, số dư và lịch sử giao dịch </u>.<b> KHÔNG THỂ
                HOÀN TÁC !!!</b>
        </p>
        <button onclick="confirmDeleteAccount()" class="btn btn-danger danger-zone-btn">
            <i class="fas fa-user-times"></i> Xóa Tài Khoản
        </button>
    </div>
</div>

<script>
    function confirmDeleteAccount() {
        Swal.fire({
            title: 'Xóa Tài Khoản Vĩnh Viễn?',
            html: `
            <div style="text-align: left; padding: 1rem;">
                <p style="color: #ef4444; font-weight: 700; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i> CẢNH BÁO NGUY HIỂM!
                </p>
                <p style="margin-bottom: 0.5rem;">Hành động này sẽ:</p>
                <ul style="list-style: none; padding-left: 1rem; color: #94a3b8;">
                    <li style="margin-bottom: 0.5rem;">❌ Xóa vĩnh viễn tài khoản của bạn</li>
                    <li style="margin-bottom: 0.5rem;">❌ Xóa tất cả đơn hàng và lịch sử</li>
                    <li style="margin-bottom: 0.5rem;">❌ Mất toàn bộ số dư (VND/USD)</li>
                    <li style="margin-bottom: 0.5rem;">❌ Không thể khôi phục dữ liệu</li>
                </ul>
                <p style="margin-top: 1rem; font-weight: 600; color: #f8fafc;">
                    Nhập "<span style="color: #ef4444;">XOA TAI KHOAN</span>" để xác nhận:
                </p>
            </div>
        `,
            input: 'text',
            inputPlaceholder: 'Nhập: XOA TAI KHOAN',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa Tài Khoản',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            preConfirm: (value) => {
                if (value !== 'XOA TAI KHOAN') {
                    Swal.showValidationMessage('Vui lòng nhập chính xác "XOA TAI KHOAN"');
                    return false;
                }
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                deleteAccount();
            }
        });
    }

    async function deleteAccount() {
        Swal.fire({
            title: 'Đang xóa tài khoản...',
            html: 'Vui lòng chờ trong giây lát',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch('<?= url("api/delete-account.php") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Tài khoản đã bị xóa',
                    text: 'Tài khoản của bạn đã bị xóa vĩnh viễn.',
                    confirmButtonColor: '#8b5cf6',
                    allowOutsideClick: false
                });
                window.location.href = '<?= url("") ?>';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: result.message || 'Không thể xóa tài khoản',
                    confirmButtonColor: '#ef4444'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Có lỗi xảy ra khi kết nối server',
                confirmButtonColor: '#ef4444'
            });
        }
    }
</script>