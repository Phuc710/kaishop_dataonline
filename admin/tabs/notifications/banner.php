<!-- ======================== BANNER TAB ======================== -->

<!-- Banner Templates -->
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-header" style="border-bottom:1px solid rgba(139,92,246,0.2);padding-bottom:1rem;margin-bottom:1rem">
        <h3 class="card-title"><i class="fas fa-magic"></i> Chọn Mẫu Banner</h3>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1rem">
        <!-- Template 1: Flash Sale -->
        <div class="banner-template" onclick="applyTemplate(1)" style="cursor:pointer;border-radius:12px;overflow:hidden;transition:all 0.3s;border:2px solid transparent" onmouseover="this.style.borderColor='#ef4444';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
            <div style="background:linear-gradient(135deg,#ef4444,#f97316);padding:1rem;color:white;text-align:center">
                <div style="font-size:1.5rem;margin-bottom:0.25rem">🔥 ⚡ 💥</div>
                <div style="font-weight:700">Flash Sale - Giảm sốc 50%!</div>
            </div>
            <div style="background:#1e293b;padding:0.75rem;text-align:center;font-size:0.85rem;color:#94a3b8">
                <span style="background:#ef4444;color:white;padding:2px 8px;border-radius:4px;font-size:0.75rem">HOT</span> Flash Sale
            </div>
        </div>
        
        <!-- Template 2: New Arrivals -->
        <div class="banner-template" onclick="applyTemplate(2)" style="cursor:pointer;border-radius:12px;overflow:hidden;transition:all 0.3s;border:2px solid transparent" onmouseover="this.style.borderColor='#a855f7';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
            <div style="background:linear-gradient(135deg,#8b5cf6,#ec4899);padding:1rem;color:white;text-align:center">
                <div style="font-size:1.5rem;margin-bottom:0.25rem">✨ 🆕 💎</div>
                <div style="font-weight:700">Sản phẩm mới - Khám phá ngay!</div>
            </div>
            <div style="background:#1e293b;padding:0.75rem;text-align:center;font-size:0.85rem;color:#94a3b8">
                <span style="background:#a855f7;color:white;padding:2px 8px;border-radius:4px;font-size:0.75rem">NEW</span> Sản phẩm mới
            </div>
        </div>
        
        <!-- Template 3: Free Shipping -->
        <div class="banner-template" onclick="applyTemplate(3)" style="cursor:pointer;border-radius:12px;overflow:hidden;transition:all 0.3s;border:2px solid transparent" onmouseover="this.style.borderColor='#10b981';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
            <div style="background:linear-gradient(135deg,#10b981,#06b6d4);padding:1rem;color:white;text-align:center">
                <div style="font-size:1.5rem;margin-bottom:0.25rem">🚚 📦 ✅</div>
                <div style="font-weight:700">Miễn phí vận chuyển đơn từ 500K!</div>
            </div>
            <div style="background:#1e293b;padding:0.75rem;text-align:center;font-size:0.85rem;color:#94a3b8">
                <span style="background:#10b981;color:white;padding:2px 8px;border-radius:4px;font-size:0.75rem">FREE</span> Vận chuyển
            </div>
        </div>
        
        <!-- Template 4: Special Event -->
        <div class="banner-template" onclick="applyTemplate(4)" style="cursor:pointer;border-radius:12px;overflow:hidden;transition:all 0.3s;border:2px solid transparent" onmouseover="this.style.borderColor='#3b82f6';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
            <div style="background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:1rem;color:white;text-align:center">
                <div style="font-size:1.5rem;margin-bottom:0.25rem">🎉 🎁 🎊</div>
                <div style="font-weight:700">Sự kiện đặc biệt - Ưu đãi lớn!</div>
            </div>
            <div style="background:#1e293b;padding:0.75rem;text-align:center;font-size:0.85rem;color:#94a3b8">
                <span style="background:#3b82f6;color:white;padding:2px 8px;border-radius:4px;font-size:0.75rem">EVENT</span> Sự kiện
            </div>
        </div>
    </div>
</div>

<div class="card">
    <form method="POST" id="bannerForm">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="type" value="banner">
        <div class="form-grid">
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Nội dung banner *</label>
                <input type="text" name="message" class="form-control" required placeholder="VD: 🎉 Giảm giá 50% tất cả sản phẩm!">
            </div>

            <div class="form-group">
                <label><i class="fas fa-icons"></i> Icon (Emoji)</label>
                <input type="text" name="icon" class="form-control" placeholder="🎉 🔥 ✨ 💎 ⚡">
            </div>

            <div class="form-group">
                <label><i class="fas fa-palette"></i> Màu nền 1</label>
                <div class="color-picker-group">
                    <input type="color" name="bg_color" id="bg-color-new" value="#7c3aed">
                    <div class="color-preview" id="bg-preview-new" style="background:#7c3aed"></div>
                    <span id="bg-value-new">#7c3aed</span>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-palette"></i> Màu nền 2 (Gradient)</label>
                <div class="color-picker-group">
                    <input type="color" name="bg_color_2" id="bg-color-2-new" value="#f97316">
                    <div class="color-preview" id="bg-preview-2-new" style="background:#f97316"></div>
                    <span id="bg-value-2-new">#f97316</span>
                </div>
                <small style="color:#94a3b8;margin-top:0.25rem;display:block">Để tạo gradient, nhập màu khác với Màu 1</small>
            </div>

            <div class="form-group">
                <label><i class="fas fa-font"></i> Màu chữ</label>
                <div class="color-picker-group">
                    <input type="color" name="text_color" id="text-color-new" value="#ffffff">
                    <div class="color-preview" id="text-preview-new" style="background:#ffffff"></div>
                    <span id="text-value-new">#ffffff</span>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-sort"></i> Thứ tự hiển thị</label>
                <input type="number" name="display_order" class="form-control" value="0" min="0">
            </div>

            <div class="form-group">
                <label><i class="fas fa-tachometer-alt"></i> Tốc độ chạy (1-100)</label>
                <input type="range" name="speed" id="speed-range" class="form-control" value="50" min="1" max="100" style="padding:0">
                <div style="display:flex;justify-content:space-between;margin-top:0.5rem">
                    <span style="color:#64748b;font-size:0.85rem">Chậm (1)</span>
                    <span id="speed-value" style="color:#8b5cf6;font-weight:600">50</span>
                    <span style="color:#64748b;font-size:0.85rem">Nhanh (100)</span>
                </div>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                    <label class="toggle">
                        <input type="checkbox" name="is_active" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    Kích hoạt
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm Banner
        </button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh Sách Banner (<?= count($notifications) ?>)</h3>
    </div>
    
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Xem trước</th>
                    <th>Nội dung</th>
                    <th>Thứ tự</th>
                    <th>Tốc độ</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:3rem;color:#64748b"><i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem"></i><p>Chưa có banner nào</p></td></tr>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <tr>
                            <td>#<?= $notif['id'] ?></td>
                            <td>
                                <div style="background:<?= $notif['bg_color'] ?>;color:<?= $notif['text_color'] ?>;padding:0.5rem 1rem;border-radius:8px;display:inline-block;font-size:0.85rem">
                                    <?= $notif['icon'] ?> <?= e(substr($notif['message'], 0, 30)) ?>...
                                </div>
                            </td>
                            <td>
                                <strong style="color:#f8fafc"><?= e($notif['message']) ?></strong>
                            </td>
                            <td><?= $notif['display_order'] ?></td>
                            <td>
                                <span class="badge badge-info"><?= $notif['speed'] ?? 50 ?></span>
                            </td>
                            <td>
                                <label class="toggle">
                                    <input type="checkbox" <?= $notif['is_active'] ? 'checked' : '' ?> onchange="toggleBanner(<?= $notif['id'] ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.5rem">
                                    <button onclick="editBanner(<?= htmlspecialchars(json_encode($notif)) ?>)" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteBanner(<?= $notif['id'] ?>)" class="btn btn-sm btn-danger">
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

<!-- Banner Edit Modal -->
<div id="editBannerModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;padding:2rem;overflow-y:auto">
    <div style="max-width:800px;margin:0 auto;background:linear-gradient(135deg,#1e293b,#0f172a);padding:2rem;border-radius:16px;border:1px solid rgba(139,92,246,0.3)">
        <h2 style="color:#f8fafc;margin-bottom:1.5rem"><i class="fas fa-edit"></i> Sửa Banner</h2>
        <form method="POST" id="editBannerForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="type" value="banner">
            <input type="hidden" name="id" id="edit-banner-id">
            
            <div class="form-group">
                <label>Nội dung</label>
                <input type="text" name="message" id="edit-banner-message" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Icon</label>
                <input type="text" name="icon" id="edit-banner-icon" class="form-control">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="form-group">
                    <label>Màu nền 1</label>
                    <input type="color" name="bg_color" id="edit-banner-bg" class="form-control">
                </div>
                <div class="form-group">
                    <label>Màu nền 2 (Gradient)</label>
                    <input type="color" name="bg_color_2" id="edit-banner-bg-2" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label>Màu chữ</label>
                <input type="color" name="text_color" id="edit-banner-text" class="form-control">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="form-group">
                    <label>Thứ tự</label>
                    <input type="number" name="display_order" id="edit-banner-order" class="form-control">
                </div>
                <div class="form-group">
                    <label>Tốc độ (1-100)</label>
                    <input type="number" name="speed" id="edit-banner-speed" class="form-control" min="1" max="100">
                </div>
            </div>

            <div class="form-group">
                <label class="toggle">
                    <input type="checkbox" name="is_active" id="edit-banner-active">
                    <span class="toggle-slider"></span>
                </label>
                <span style="margin-left:1rem">Kích hoạt</span>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                <button type="button" onclick="closeBannerModal()" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle banner active status
function toggleBanner(id, isActive) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="type" value="banner">
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="is_active" value="${isActive ? 1 : 0}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Speed range slider
if (document.getElementById('speed-range')) {
    document.getElementById('speed-range').addEventListener('input', e => {
        document.getElementById('speed-value').textContent = e.target.value;
    });
}

// Color pickers
if (document.getElementById('bg-color-new')) {
    document.getElementById('bg-color-new').addEventListener('input', e => {
        const color = e.target.value;
        document.getElementById('bg-preview-new').style.background = color;
        document.getElementById('bg-value-new').textContent = color;
    });
}

if (document.getElementById('bg-color-2-new')) {
    document.getElementById('bg-color-2-new').addEventListener('input', e => {
        const color = e.target.value;
        document.getElementById('bg-preview-2-new').style.background = color;
        document.getElementById('bg-value-2-new').textContent = color;
    });
}

if (document.getElementById('text-color-new')) {
    document.getElementById('text-color-new').addEventListener('input', e => {
        const color = e.target.value;
        document.getElementById('text-preview-new').style.background = color;
        document.getElementById('text-value-new').textContent = color;
    });
}

// Banner CRUD functions
function editBanner(data) {
    document.getElementById('edit-banner-id').value = data.id;
    document.getElementById('edit-banner-message').value = data.message;
    document.getElementById('edit-banner-icon').value = data.icon || '';
    document.getElementById('edit-banner-bg').value = data.bg_color || '#7c3aed';
    document.getElementById('edit-banner-bg-2').value = data.bg_color_2 || '#f97316';
    document.getElementById('edit-banner-text').value = data.text_color;
    document.getElementById('edit-banner-order').value = data.display_order;
    document.getElementById('edit-banner-speed').value = data.speed || 50;
    document.getElementById('edit-banner-active').checked = data.is_active == 1;
    document.getElementById('editBannerModal').style.display = 'block';
}

function closeBannerModal() {
    document.getElementById('editBannerModal').style.display = 'none';
}

async function deleteBanner(id) {
    const confirmed = await notify.confirm({
        title: 'Xác nhận xóa banner',
        message: 'Bạn có chắc muốn xóa banner này?',
        type: 'warning',
        confirmText: 'Xóa',
        cancelText: 'Hủy'
    });
    
    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="type" value="banner">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Banner Templates
const bannerTemplates = {
    1: { // Flash Sale
        message: '🔥 Flash Sale - Giảm sốc 50% tất cả sản phẩm! Nhanh tay kẻo lỡ!',
        icon: '🔥 ⚡ 💥',
        bg_color: '#ef4444',
        bg_color_2: '#f97316',
        text_color: '#ffffff',
        speed: 60
    },
    2: { // New Arrivals
        message: '✨ Sản phẩm mới về - Khám phá BST mới nhất ngay hôm nay!',
        icon: '✨ 🆕 💎',
        bg_color: '#8b5cf6',
        bg_color_2: '#ec4899',
        text_color: '#ffffff',
        speed: 50
    },
    3: { // Free Shipping
        message: '🚚 Miễn phí vận chuyển cho đơn hàng từ 500.000đ! Mua ngay!',
        icon: '🚚 📦 ✅',
        bg_color: '#10b981',
        bg_color_2: '#06b6d4',
        text_color: '#ffffff',
        speed: 45
    },
    4: { // Special Event
        message: '🎉 Sự kiện đặc biệt - Ưu đãi cực lớn chỉ trong hôm nay!',
        icon: '🎉 🎁 🎊',
        bg_color: '#3b82f6',
        bg_color_2: '#8b5cf6',
        text_color: '#ffffff',
        speed: 55
    }
};

function applyTemplate(templateId) {
    const template = bannerTemplates[templateId];
    if (!template) return;
    
    // Fill in form fields
    const form = document.getElementById('bannerForm');
    form.querySelector('[name="message"]').value = template.message;
    form.querySelector('[name="icon"]').value = template.icon;
    
    // Background color 1
    const bgColor = document.getElementById('bg-color-new');
    bgColor.value = template.bg_color;
    document.getElementById('bg-preview-new').style.background = template.bg_color;
    document.getElementById('bg-value-new').textContent = template.bg_color;
    
    // Background color 2
    const bgColor2 = document.getElementById('bg-color-2-new');
    bgColor2.value = template.bg_color_2;
    document.getElementById('bg-preview-2-new').style.background = template.bg_color_2;
    document.getElementById('bg-value-2-new').textContent = template.bg_color_2;
    
    // Text color
    const textColor = document.getElementById('text-color-new');
    textColor.value = template.text_color;
    document.getElementById('text-preview-new').style.background = template.text_color;
    document.getElementById('text-value-new').textContent = template.text_color;
    
    // Speed
    const speedRange = document.getElementById('speed-range');
    speedRange.value = template.speed;
    document.getElementById('speed-value').textContent = template.speed;
    
    // Scroll to form
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Flash effect to show template applied
    form.style.boxShadow = '0 0 20px rgba(139, 92, 246, 0.5)';
    setTimeout(() => form.style.boxShadow = '', 1000);
    
    notify.success('Đã áp dụng mẫu', 'Bạn có thể chỉnh sửa thêm trước khi thêm banner');
}
</script>
