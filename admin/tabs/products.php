<?php
/**
 * Products Management 
 */

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    $id = $_POST['product_id'] ?? '';
    if ($id) {
        $stmt = $pdo->prepare("SELECT name, image FROM products WHERE id=?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();

        if ($prod) {
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            if ($prod['image']) {
                $img = __DIR__ . '/assets/images/uploads/' . $prod['image'];
                if (file_exists($img))
                    @unlink($img);
            }
            $_SESSION['prod_msg'] = 'Đã xóa sản phẩm thành công!';
            header('Location: ?tab=products');
            exit;
        }
    }
}

$msg = $_SESSION['prod_msg'] ?? '';
unset($_SESSION['prod_msg']);

// Filters
$search = $_GET['search'] ?? '';
$cat = $_GET['category'] ?? '';
$label = $_GET['label'] ?? '';
$stock = $_GET['stock'] ?? '';
$view = $_GET['view'] ?? 'table';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 24;
$offset = ($page - 1) * $limit;

// Build WHERE
$where = [];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($cat) {
    $where[] = "p.category_id = ?";
    $params[] = $cat;
}
if ($label) {
    $where[] = "p.label = ?";
    $params[] = $label;
}
if ($stock === 'in')
    $where[] = "p.stock > 0";
elseif ($stock === 'out')
    $where[] = "p.stock = 0";
elseif ($stock === 'low')
    $where[] = "p.stock > 0 AND p.stock <= 10";

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where_sql");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$pages = ceil($total / $limit);

// Get products
$query = "
    SELECT p.*, c.name as cat_name,
        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as vars
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $where_sql 
    ORDER BY p.is_pinned DESC, p.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'in' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0")->fetchColumn(),
    'out' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn(),
    'hot' => $pdo->query("SELECT COUNT(*) FROM products WHERE label = 'HOT'")->fetchColumn(),
];

// Build query string helper
function buildUrl($params)
{
    $base = '?tab=products';
    foreach ($params as $k => $v) {
        if ($v !== '')
            $base .= "&$k=" . urlencode($v);
    }
    return $base;
}
$qry = ['search' => $search, 'category' => $cat, 'label' => $label, 'stock' => $stock];
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-box"></i> Quản Lý Sản Phẩm</h1>
        <p style="color:#94a3b8;margin-top:0.5rem"><?= number_format($stats['total']) ?> sản phẩm</p>
    </div>
    <a href="?tab=product_add" class="btn btn-primary">
        <i class="fas fa-plus"></i> Thêm Mới
    </a>
</div>

<?php if ($msg): ?>
    <script>if (window.notify) notify.success('Thành công!', '<?= addslashes($msg) ?>');</script>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(109,40,217,0.05));border-left:4px solid #8b5cf6">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#8b5cf6"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Tổng</div>
            </div>
            <div class="stat-icon" style="background:#8b5cf6"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(5,150,105,0.05));border-left:4px solid #10b981">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#10b981"><?= number_format($stats['in']) ?></div>
                <div class="stat-label">Còn Hàng</div>
            </div>
            <div class="stat-icon" style="background:#10b981"><i class="fas fa-check"></i></div>
        </div>
    </div>
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(239,68,68,0.15),rgba(220,38,38,0.05));border-left:4px solid #ef4444">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#ef4444"><?= number_format($stats['out']) ?></div>
                <div class="stat-label">Hết Hàng</div>
            </div>
            <div class="stat-icon" style="background:#ef4444"><i class="fas fa-times"></i></div>
        </div>
    </div>
    <div class="stat-card"
        style="background:linear-gradient(135deg,rgba(245,158,11,0.15),rgba(217,119,6,0.05));border-left:4px solid #f59e0b">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" style="color:#f59e0b"><?= number_format($stats['hot']) ?></div>
                <div class="stat-label">HOT</div>
            </div>
            <div class="stat-icon" style="background:#f59e0b"><i class="fas fa-fire"></i></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <form method="GET"
        style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;padding:1.5rem">
        <input type="hidden" name="tab" value="products">
        <input type="hidden" name="view" value="<?= $view ?>">

        <div class="form-group" style="margin:0">
            <label style="color:#f8fafc;font-size:0.9rem;margin-bottom:0.5rem"><i class="fas fa-search"></i> Tìm
                Kiếm</label>
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>"
                placeholder="Tên sản phẩm...">
        </div>

        <div class="form-group" style="margin:0">
            <label style="color:#f8fafc;font-size:0.9rem;margin-bottom:0.5rem"><i class="fas fa-folder"></i> Danh
                Mục</label>
            <select name="category" class="form-control">
                <option value="">Tất cả</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0">
            <label style="color:#f8fafc;font-size:0.9rem;margin-bottom:0.5rem"><i class="fas fa-tag"></i> Nhãn</label>
            <select name="label" class="form-control">
                <option value="">Tất cả</option>
                <option value="NORMAL" <?= $label == 'NORMAL' ? 'selected' : '' ?>>NORMAL</option>
                <option value="HOT" <?= $label == 'HOT' ? 'selected' : '' ?>>HOT</option>
                <option value="NEW" <?= $label == 'NEW' ? 'selected' : '' ?>>NEW</option>
                <option value="SOLD_OUT" <?= $label == 'SOLD_OUT' ? 'selected' : '' ?>>CHÁY HÀNG</option>
            </select>
        </div>

        <div class="form-group" style="margin:0">
            <label style="color:#f8fafc;font-size:0.9rem;margin-bottom:0.5rem"><i class="fas fa-boxes"></i> Tồn
                Kho</label>
            <select name="stock" class="form-control">
                <option value="">Tất cả</option>
                <option value="in" <?= $stock == 'in' ? 'selected' : '' ?>>Còn hàng</option>
                <option value="low" <?= $stock == 'low' ? 'selected' : '' ?>>Sắp hết</option>
                <option value="out" <?= $stock == 'out' ? 'selected' : '' ?>>Hết hàng</option>
            </select>
        </div>

        <div style="display:flex;align-items:flex-end;gap:0.5rem">
            <button type="submit" class="btn btn-primary" style="flex:1"><i class="fas fa-filter"></i> Lọc</button>
            <a href="?tab=products&view=<?= $view ?>" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
        </div>
    </form>
</div>

<!-- View Toggle -->
<div style="display:flex;justify-content:space-between;align-items:center;padding:0 0.5rem;margin-bottom:1rem">
    <div style="color:#94a3b8;font-size:0.9rem">
        <?php if ($total > 0): ?>
            Hiển thị <?= $offset + 1 ?>-<?= min($offset + $limit, $total) ?> / <?= number_format($total) ?>
        <?php else: ?>
            Không tìm thấy
        <?php endif; ?>
    </div>
    <div style="display:flex;gap:0.5rem">
        <a href="<?= buildUrl(array_merge($qry, ['view' => 'table'])) ?>"
            class="btn btn-sm <?= $view === 'table' ? 'btn-primary' : 'btn-secondary' ?>">
            <i class="fas fa-table"></i>
        </a>
        <a href="<?= buildUrl(array_merge($qry, ['view' => 'grid'])) ?>"
            class="btn btn-sm <?= $view === 'grid' ? 'btn-primary' : 'btn-secondary' ?>">
            <i class="fas fa-th"></i>
        </a>
    </div>
</div>

<!-- Products Display -->
<?php if ($view === 'grid'): ?>
    <!-- Grid View -->
    <?php if (empty($products)): ?>
        <div class="card" style="padding:5rem;text-align:center">
            <i class="fas fa-box-open" style="font-size:4rem;color:#64748b;margin-bottom:1rem"></i>
            <p style="color:#94a3b8;font-size:1.1rem">Không có sản phẩm</p>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem">
            <?php foreach ($products as $p): ?>
                <div class="card" style="padding:0;overflow:hidden;transition:transform 0.2s"
                    onmouseenter="this.style.transform='translateY(-4px)'" onmouseleave="this.style.transform='translateY(0)'">
                    <div style="position:relative">
                        <img src="<?= asset('images/uploads/' . $p['image']) ?>" style="width:100%;height:200px;object-fit:cover">
                        <div style="position:absolute;top:0.75rem;right:0.75rem">
                            <span class="badge"
                                style="background:<?= $p['label_color'] ?? '#6b7280' ?>;font-size:0.75rem;padding:0.4rem 0.8rem">
                                <?= $p['label'] ?>
                            </span>
                        </div>
                        <?php if ($p['vars'] > 0): ?>
                            <div style="position:absolute;top:0.75rem;left:0.75rem">
                                <span
                                    style="background:rgba(139,92,246,0.9);color:white;font-size:0.75rem;padding:0.4rem 0.8rem;border-radius:6px;font-weight:600">
                                    <i class="fas fa-layer-group"></i> <?= $p['vars'] ?> variants
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="padding:1.25rem">
                        <h3
                            style="color:#f8fafc;font-size:1rem;margin-bottom:0.75rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?= e($p['name']) ?>
                        </h3>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                            <div>
                                <div style="color:#10b981;font-size:1.3rem;font-weight:700"><?= formatVND($p['final_price_vnd']) ?>
                                </div>
                                <?php if ($p['discount_percent'] > 0): ?>
                                    <div style="color:#64748b;font-size:0.85rem;text-decoration:line-through">
                                        <?= formatVND($p['price_vnd']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right">
                                <?php if (in_array($p['product_type'], ['source', 'book'])): ?>
                                    <div style="color:#3b82f6;font-weight:600;font-size:1.5rem">∞</div>
                                    <div style="color:#64748b;font-size:0.8rem">Unlimited</div>
                                <?php else: ?>
                                    <div style="color:<?= $p['stock'] > 0 ? '#10b981' : '#ef4444' ?>;font-weight:600;font-size:0.95rem">
                                        <i class="fas fa-box"></i> <?= $p['stock'] ?>
                                    </div>
                                    <div style="color:#64748b;font-size:0.8rem"><?= $p['stock'] > 0 ? 'Còn hàng' : 'Hết hàng' ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex;gap:0.5rem">
                            <a href="?tab=product_manage&product_id=<?= $p['id'] ?>" class="btn btn-primary"
                                style="flex:1;font-size:0.9rem">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                            <button onclick="deleteProduct('<?= $p['id'] ?>', '<?= e($p['name']) ?>')" class="btn btn-danger"
                                style="font-size:0.9rem">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Table View -->
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:80px">Ảnh</th>
                        <th>Sản Phẩm</th>
                        <th>Danh Mục</th>
                        <th>Giá</th>
                        <th>Tồn Kho</th>
                        <th>Nhãn</th>
                        <th style="width:120px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:3rem;color:#64748b">
                                <i class="fas fa-inbox" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
                                Không có sản phẩm
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr style="<?= ($p['is_hidden'] ?? 0) ? 'opacity: 0.5;' : '' ?>">
                                <td>
                                    <img src="<?= asset('images/uploads/' . $p['image']) ?>"
                                        style="width:60px;height:60px;object-fit:cover;border-radius:8px">
                                </td>
                                <td>
                                    <?php if ($p['is_pinned'] ?? 0): ?>
                                        <span style="color:#f59e0b;margin-right:0.5rem" title="Sản phẩm đã ghim">📌</span>
                                    <?php endif; ?>
                                    <?php if ($p['is_hidden'] ?? 0): ?>
                                        <i class="fas fa-shield-alt" style="color:#64748b;margin-right:0.5rem"
                                            title="Sản phẩm bị ẩn (Chỉ hiện trong Admin)"></i>
                                    <?php endif; ?>
                                    <a href="?tab=product_manage&product_id=<?= $p['id'] ?>"
                                        style="color:#8b5cf6;text-decoration:none;font-weight:600">
                                        <?= e($p['name']) ?>
                                    </a>
                                    <?php if ($p['vars'] > 0): ?>
                                        <span style="color:#64748b;font-size:0.8rem;margin-left:0.5rem">
                                            <i class="fas fa-layer-group"></i> <?= $p['vars'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <br><small style="color:#64748b">#<?= substr($p['id'], -8) ?></small>
                                </td>
                                <td><?= e($p['cat_name'] ?? 'N/A') ?></td>
                                <td>
                                    <strong style="color:#10b981;font-size:1.05rem"><?= formatVND($p['final_price_vnd']) ?></strong>
                                    <?php if ($p['discount_percent'] > 0): ?>
                                        <br><small
                                            style="color:#64748b;text-decoration:line-through"><?= formatVND($p['price_vnd']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (in_array($p['product_type'], ['source', 'book'])): ?>
                                        <span style="color:#3b82f6;font-weight:600;font-size:1.5rem">∞</span>
                                    <?php elseif ($p['stock'] == 0): ?>
                                        <span style="color:#ef4444;font-weight:600">Hết hàng</span>
                                    <?php elseif ($p['stock'] <= 10): ?>
                                        <span style="color:#f59e0b;font-weight:600"><?= $p['stock'] ?> (Sắp hết)</span>
                                    <?php else: ?>
                                        <span style="color:#10b981;font-weight:600"><?= $p['stock'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge"
                                        style="background:<?= $p['label_color'] ?? '#6b7280' ?>"><?= $p['label'] ?></span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:0.5rem">
                                        <button onclick="togglePin('<?= $p['id'] ?>', <?= $p['is_pinned'] ?? 0 ?>)"
                                            class="btn btn-sm <?= ($p['is_pinned'] ?? 0) ? 'btn-warning' : 'btn-secondary' ?>"
                                            id="pin-btn-<?= $p['id'] ?>"
                                            title="<?= ($p['is_pinned'] ?? 0) ? 'Bỏ ghim' : 'Ghim sản phẩm' ?>">
                                            <i class="fas fa-thumbtack"></i>
                                        </button>
                                        <button onclick="toggleVisibility('<?= $p['id'] ?>', <?= $p['is_hidden'] ?? 0 ?>)"
                                            class="btn btn-sm <?= ($p['is_hidden'] ?? 0) ? 'btn-secondary' : 'btn-success' ?>"
                                            id="visibility-btn-<?= $p['id'] ?>"
                                            title="<?= ($p['is_hidden'] ?? 0) ? 'Hiện sản phẩm' : 'Ẩn sản phẩm' ?>">
                                            <i class="fas <?= ($p['is_hidden'] ?? 0) ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                        </button>
                                        <a href="?tab=product_manage&product_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary"
                                            title="Chỉnh sửa chi tiết">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button onclick="deleteProduct('<?= $p['id'] ?>', '<?= e($p['name']) ?>')"
                                            class="btn btn-sm btn-danger">
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
<?php endif; ?>

<!-- Pagination -->
<?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= buildUrl(array_merge($qry, ['view' => $view, 'page' => $page - 1])) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        if ($start > 1): ?>
            <a href="<?= buildUrl(array_merge($qry, ['view' => $view, 'page' => 1])) ?>">1</a>
            <?php if ($start > 2): ?><span>...</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= buildUrl(array_merge($qry, ['view' => $view, 'page' => $i])) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
            <?php if ($end < $pages - 1): ?><span>...</span><?php endif; ?>
            <a href="<?= buildUrl(array_merge($qry, ['view' => $view, 'page' => $pages])) ?>"><?= $pages ?></a>
        <?php endif; ?>

        <?php if ($page < $pages): ?>
            <a href="<?= buildUrl(array_merge($qry, ['view' => $view, 'page' => $page + 1])) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    async function deleteProduct(id, name) {
        const ok = await adminModal.confirm({
            title: 'Xóa sản phẩm?',
            message: `Bạn có chắc muốn xóa "${name}"?\nHành động này không thể hoàn tác!`,
            type: 'danger'
        });

        if (ok) {
            adminModal.showLoading('Đang xóa...', 'Vui lòng đợi');
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="product_id" value="${id}">
        `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    async function togglePin(productId, currentStatus) {
        try {
            const response = await fetch('<?= url('admin/api/toggle-pin-product.php') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_id: productId })
            });

            const data = await response.json();

            if (data.success) {
                // Update button appearance
                const btn = document.getElementById('pin-btn-' + productId);
                if (btn) {
                    if (data.is_pinned) {
                        btn.classList.remove('btn-secondary');
                        btn.classList.add('btn-warning');
                        btn.title = 'Bỏ ghim';
                    } else {
                        btn.classList.remove('btn-warning');
                        btn.classList.add('btn-secondary');
                        btn.title = 'Ghim sản phẩm';
                    }
                }

                // Show notification
                if (window.notify) {
                    notify.success('Thành công!', data.message);
                }

                // Reload page after 1 second to show new order
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                if (window.notify) {
                    notify.error('Lỗi!', data.message);
                }
            }
        } catch (error) {
            console.error('Error toggling pin:', error);
            if (window.notify) {
                notify.error('Lỗi!', 'Không thể ghim sản phẩm');
            }
        }
    }

    async function toggleVisibility(productId, currentStatus) {
    try {
        const response = await fetch('<?= url('admin/api/toggle-product-visibility.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ product_id: productId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update button appearance
            const btn = document.getElementById('visibility-btn-' + productId);
            const row = btn.closest('tr');
            
            if (btn) {
                if (data.is_hidden) {
                    // Product is now HIDDEN
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-secondary');
                    btn.title = 'Hiện sản phẩm';
                    btn.querySelector('i').className = 'fas fa-eye';
                    row.style.opacity = '0.5';

                    // Add shield icon if not exists
                    let nameCell = row.cells[1];
                    if (!nameCell.querySelector('.fa-shield-alt')) {
                         let shield = document.createElement('i');
                         shield.className = 'fas fa-shield-alt';
                         shield.style.color = '#64748b';
                         shield.style.marginRight = '0.5rem';
                         shield.title = 'Sản phẩm bị ẩn (Chỉ hiện trong Admin)';
                         
                         // Insert before the name link
                         let link = nameCell.querySelector('a');
                         if(link) {
                             nameCell.insertBefore(shield, link);
                         }
                    }

                } else {
                    // Product is now VISIBLE
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-success');
                    btn.title = 'Ẩn sản phẩm';
                    btn.querySelector('i').className = 'fas fa-eye-slash';
                    row.style.opacity = '1';

                    // Remove shield icon
                    let shield = row.querySelector('.fa-shield-alt');
                    if(shield) shield.remove();
                }
            }
            
            // Show notification
            if (window.notify) {
                notify.success('Thành công!', data.message);
            }
        } else {
            if (window.notify) {
                notify.error('Lỗi!', data.message);
            }
        }
    } catch (error) {
        console.error('Error toggling visibility:', error);
        if (window.notify) {
            notify.error('Lỗi!', 'Không thể thay đổi trạng thái sản phẩm');
        }
    }
}
</script>