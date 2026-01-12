<?php
// Categories Management Tab
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if ($search) {
    $where[] = "(c.name LIKE ? OR c.slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count_query = "SELECT COUNT(*) FROM categories $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

$query = "SELECT c.*, COUNT(p.id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.id = p.category_id 
          $where_sql 
          GROUP BY c.id 
          ORDER BY c.name 
          LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Handle form submission for adding/editing categories
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'add') {
            $name = trim($_POST['name']);
            $slug = trim($_POST['slug']);
            $icon_type = $_POST['icon_type'] ?? 'emoji';
            $icon_value = trim($_POST['icon_value'] ?? '📦');

            if (empty($name) || empty($slug)) {
                throw new Exception('Tên và slug không được để trống');
            }

            // Handle icon image upload
            if ($icon_type === 'image' && !empty($_FILES['icon_image']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $ext = strtolower(pathinfo($_FILES['icon_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed) && $_FILES['icon_image']['size'] <= 2 * 1024 * 1024) {
                    $icon_value = 'cat_' . uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['icon_image']['tmp_name'], __DIR__ . '/../../assets/images/uploads/' . $icon_value);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_type, icon_value) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $icon_type, $icon_value]);

            $_SESSION['success'] = 'Đã thêm danh mục thành công!';
            header('Location: ?tab=categories');
            exit;

        } elseif ($action === 'edit') {
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $slug = trim($_POST['slug']);
            $icon_type = $_POST['icon_type'] ?? 'emoji';
            $icon_value = trim($_POST['icon_value'] ?? '📦');

            if (empty($name) || empty($slug)) {
                throw new Exception('Tên và slug không được để trống');
            }

            // Handle icon image upload
            if ($icon_type === 'image' && !empty($_FILES['icon_image']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                $ext = strtolower(pathinfo($_FILES['icon_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed) && $_FILES['icon_image']['size'] <= 2 * 1024 * 1024) {
                    $icon_value = 'cat_' . uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['icon_image']['tmp_name'], __DIR__ . '/../../assets/images/uploads/' . $icon_value);
                }
            }

            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon_type = ?, icon_value = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $icon_type, $icon_value, $id]);

            $_SESSION['success'] = 'Đã cập nhật danh mục thành công!';
            header('Location: ?tab=categories');
            exit;

        } elseif ($action === 'delete') {
            $id = $_POST['id'];

            // Check if category has products
            $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $count->execute([$id]);
            $product_count = $count->fetchColumn();

            if ($product_count > 0) {
                throw new Exception("Không thể xóa danh mục có $product_count sản phẩm. Vui lòng chuyển sản phẩm sang danh mục khác trước.");
            }

            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = 'Đã xóa danh mục thành công!';
            header('Location: ?tab=categories');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_category = $stmt->fetch();
}
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-layer-group"></i> Quản Lý Danh Mục</h1>
        <p>Tổng: <?= $total ?> danh mục</p>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        if (window.notify) {
            notify.success('Thành công!', '<?= $_SESSION['success'] ?>');
        }
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        if (window.notify) {
            notify.error('Lỗi!', '<?= $_SESSION['error'] ?>');
        }
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-<?= $edit_category ? 'edit' : 'plus' ?>"></i>
            <?= $edit_category ? 'Chỉnh Sửa Danh Mục' : 'Thêm Danh Mục Mới' ?>
        </h3>
    </div>
    <form method="POST" enctype="multipart/form-data" style="padding:1.5rem">
        <input type="hidden" name="action" value="<?= $edit_category ? 'edit' : 'add' ?>">
        <?php if ($edit_category): ?>
            <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Tên Danh Mục *</label>
                <input type="text" name="name" class="form-control" required
                    value="<?= $edit_category ? e($edit_category['name']) : '' ?>"
                    placeholder="VD: Game, Tài Khoản, Dịch Vụ...">
            </div>

            <div class="form-group">
                <label><i class="fas fa-link"></i> Slug (URL) *</label>
                <input type="text" name="slug" class="form-control" required
                    value="<?= $edit_category ? e($edit_category['slug']) : '' ?>"
                    placeholder="VD: game, tai-khoan, dich-vu...">
                <small style="color:#64748b">Chỉ dùng chữ thường, số và dấu gạch ngang</small>
            </div>
        </div>

        <!-- Icon Section -->
        <div class="form-group" style="margin-top:1rem">
            <label><i class="fas fa-icons"></i> Icon Danh Mục</label>
            <div style="display:flex;gap:1rem;align-items:center;margin-top:0.5rem">
                <select name="icon_type" id="icon_type" class="form-control" style="width:auto"
                    onchange="toggleIconInput()">
                    <option value="emoji" <?= ($edit_category['icon_type'] ?? 'emoji') === 'emoji' ? 'selected' : '' ?>>
                        Emoji</option>
                    <option value="image" <?= ($edit_category['icon_type'] ?? '') === 'image' ? 'selected' : '' ?>>Hình ảnh
                    </option>
                </select>

                <div id="emoji_input" style="flex:1">
                    <input type="text" name="icon_value" class="form-control"
                        value="<?= e($edit_category['icon_value'] ?? '📦') ?>" placeholder="Nhập emoji: 🎮 📱 💻...">
                </div>

                <div id="image_input" style="flex:1;display:none">
                    <input type="file" name="icon_image" class="form-control" accept="image/*">
                </div>

                <?php if ($edit_category && !empty($edit_category['icon_value'])): ?>
                    <div style="padding:0.5rem;background:rgba(139,92,246,0.1);border-radius:8px">
                        <strong style="color:#64748b;font-size:0.8rem">Hiện tại:</strong>
                        <?php if (($edit_category['icon_type'] ?? '') === 'image'): ?>
                            <img src="<?= asset('images/uploads/' . $edit_category['icon_value']) ?>"
                                style="width:32px;height:32px;border-radius:4px">
                        <?php else: ?>
                            <span style="font-size:1.5rem"><?= $edit_category['icon_value'] ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group" style="display:flex;align-items:flex-end;gap:0.5rem;margin-top:1rem">
            <button type="submit" class="btn btn-primary" style="flex:1">
                <i class="fas fa-<?= $edit_category ? 'save' : 'plus' ?>"></i>
                <?= $edit_category ? 'Cập Nhật' : 'Thêm Mới' ?>
            </button>
            <?php if ($edit_category): ?>
                <a href="?tab=categories" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                </a>
            <?php endif; ?>
        </div>
    </form>

    <script>
        function toggleIconInput() {
            const type = document.getElementById('icon_type').value;
            document.getElementById('emoji_input').style.display = type === 'emoji' ? 'block' : 'none';
            document.getElementById('image_input').style.display = type === 'image' ? 'block' : 'none';
        }
        toggleIconInput();
    </script>
</div>


<!-- Search -->
<div class="card">
    <form method="GET" class="form-grid">
        <input type="hidden" name="tab" value="categories">
        <div class="form-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"
                placeholder="Tên, slug...">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;gap:0.5rem">
            <button type="submit" class="btn btn-primary" style="flex:1">
                <i class="fas fa-filter"></i> Lọc
            </button>
            <a href="?tab=categories" class="btn btn-secondary">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Categories List -->
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:60px">Icon</th>
                    <th>Tên Danh Mục</th>
                    <th>Slug</th>
                    <th>Số Sản Phẩm</th>
                    <th style="width:150px">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:3rem;color:#64748b">
                            <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem"></i>
                            <p>Chưa có danh mục nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td style="text-align:center">
                                <?php
                                $iconValue = $cat['icon_value'] ?? '📦';
                                $iconType = $cat['icon_type'] ?? 'emoji';
                                if ($iconType === 'image'): ?>
                                    <img src="<?= asset('images/uploads/' . $iconValue) ?>"
                                        style="width:32px;height:32px;border-radius:6px">
                                <?php else: ?>
                                    <span style="font-size:1.5rem"><?= $iconValue ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color:#f8fafc"><?= e($cat['name']) ?></strong>
                            </td>
                            <td>
                                <code style="color:#10b981"><?= e($cat['slug']) ?></code>
                            </td>
                            <td>
                                <a href="?tab=products&category=<?= $cat['id'] ?>" style="color:#8b5cf6;text-decoration:none">
                                    <strong><?= $cat['product_count'] ?></strong> sản phẩm
                                </a>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.5rem;justify-content:center">
                                    <a href="?tab=categories&edit=<?= $cat['id'] ?>" class="btn btn-sm btn-primary"
                                        title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button
                                        onclick="deleteCategory(<?= $cat['id'] ?>, '<?= e($cat['name']) ?>', <?= $cat['product_count'] ?>)"
                                        class="btn btn-sm btn-danger" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?tab=categories&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?tab=categories&page=<?= $i ?>&search=<?= urlencode($search) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?tab=categories&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: #10b981;
    }

    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }
</style>

<script>
    async function deleteCategory(id, name, productCount) {
        if (productCount > 0) {
            notify.error('Không thể xóa!', `Danh mục "${name}" có ${productCount} sản phẩm. Vui lòng chuyển sản phẩm sang danh mục khác trước.`);
            return;
        }

        const confirmed = await notify.confirm({
            title: 'Xóa danh mục?',
            message: `Bạn có chắc muốn xóa danh mục "${name}"?`,
            type: 'warning'
        });

        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>