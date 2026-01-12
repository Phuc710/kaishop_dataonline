<?php
// Admin Label Management Tab - Simplified (Name + Image Only)
if (!defined('SITE_NAME'))
    exit;

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_label'])) {
        $name = trim($_POST['name']);
        $image_filename = '';

        if (isset($_FILES['image_file'])) {
            $upload = $_FILES['image_file'];
            if ($upload['error'] === 0) {
                $ext = pathinfo($upload['name'], PATHINFO_EXTENSION);
                $filename = 'label_' . time() . '_' . uniqid() . '.' . $ext;
                $target = __DIR__ . '/../../assets/images/uploads/' . $filename;
                
                // Ensure directory exists
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }

                if (move_uploaded_file($upload['tmp_name'], $target)) {
                    $image_filename = $filename;
                }
            }
        }

        if (!empty($name) && !empty($image_filename)) {
            $stmt = $pdo->prepare("INSERT INTO product_labels (name, image_url) VALUES (?, ?)");
            if ($stmt->execute([$name, $image_filename])) {
                $message = '<div class="alert alert-success">Đã thêm nhãn mới thành công!</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Vui lòng nhập tên và tải lên ảnh nhãn.</div>';
        }
    }

    if (isset($_POST['delete_label'])) {
        $id = $_POST['label_id'];
        // Optional: delete actual file from uploads
        $stmt = $pdo->prepare("SELECT image_url FROM product_labels WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img) {
            $file_path = __DIR__ . '/../../assets/images/uploads/' . $img;
            if (file_exists($file_path)) unlink($file_path);
        }

        $stmt = $pdo->prepare("DELETE FROM product_labels WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Đã xóa nhãn!</div>';
        }
    }
}

// Fetch all labels
$labels = $pdo->query("SELECT * FROM product_labels ORDER BY created_at DESC")->fetchAll();
?>

<div class="admin-tab-container">
    <?= $message ?>

    <div class="admin-grid">
        <!-- Add New Label -->
        <div class="admin-card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Tạo Nhãn Mới</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Tên dùng để tìm kiếm, Ảnh là nhãn hiển thị.</p>
            </div>
            <div class="card-body">
                <form action="?tab=labels" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Tên Nhãn (Nội bộ / Tìm kiếm)</label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Sale 50%, Hot Trend..." required>
                    </div>

                    <div class="form-group">
                        <label>Ảnh Nhãn (File ảnh PNG/JPG/WebP)</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*" required onchange="previewImage(this)">
                        <div id="image-preview" style="margin-top: 15px; display: none;">
                            <p style="font-size: 11px; color: var(--text-muted); margin-bottom: 5px;">Xem trước:</p>
                            <img id="preview-img" src="#" style="max-height: 50px; border-radius: 4px; border: 1px dashed rgba(139, 92, 246, 0.3); padding: 5px;">
                        </div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" name="add_label" class="btn btn-primary w-100">
                            <i class="fas fa-cloud-upload-alt"></i> Tải Lên & Lưu Nhãn
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Labels List -->
        <div class="admin-card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Danh Sách Nhãn</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Thông tin nhãn</th>
                                <th style="text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($labels)): ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                                        <i class="fas fa-image" style="font-size: 2rem; display: block; margin-bottom: 1rem; opacity: 0.2;"></i>
                                        Chưa có nhãn nào được tạo.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($labels as $lbl): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div class="label-img-box">
                                                    <img src="<?= asset('images/uploads/' . $lbl['image_url']) ?>" alt="">
                                                </div>
                                                <div>
                                                    <div style="font-weight: 700; color: #f8fafc; font-size: 14px;">
                                                        <?= htmlspecialchars($lbl['name']) ?>
                                                    </div>
                                                    <div style="font-size: 11px; color: var(--text-muted);">ID: <?= $lbl['id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: right;">
                                            <form action="?tab=labels" method="POST" onsubmit="return confirm('Xóa nhãn này?');" style="display: inline-block;">
                                                <input type="hidden" name="label_id" value="<?= $lbl['id'] ?>">
                                                <button type="submit" name="delete_label" class="btn-delete-icon">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>

<style>
    .admin-card {
        background: rgba(30, 41, 59, 0.25);
        border: 1px solid rgba(148, 163, 184, 0.08);
        border-radius: 20px;
        backdrop-filter: blur(10px);
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .card-header h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #8b5cf6;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-body {
        padding: 1.5rem;
    }

    .admin-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    @media (max-width: 992px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
    }

    .label-img-box {
        width: 60px;
        height: 40px;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(139, 92, 246, 0.15);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
    }

    .label-img-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .btn-delete-icon {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.15);
        color: #ef4444;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-delete-icon:hover {
        background: #ef4444;
        color: #fff;
        transform: rotate(8deg) scale(1.1);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .admin-table th {
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 1.5px;
        color: #64748b;
        padding: 1rem;
    }
    
    .admin-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.05);
    }

    .form-control {
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(148, 163, 184, 0.1);
        color: #fff;
        padding: 0.8rem 1rem;
        border-radius: 12px;
        width: 100%;
        transition: all 0.3s;
    }

    .form-control:focus {
        background: rgba(15, 23, 42, 0.6);
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        outline: none;
    }
</style>