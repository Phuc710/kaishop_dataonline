<?php
/**
 * Notices Management - Important Notifications
 */
?>

<style>
    .card {
        background: rgba(30, 41, 59, 0.5);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .card h2 {
        margin: 0 0 1.5rem 0;
        color: #f8fafc;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #ffffffff;
        font-weight: 600;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 4px;
        color: #f8fafc;
        font-size: 1rem;
    }

    .form-control:focus {
        outline: none;
        border-color: #8b5cf6;
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .form-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-primary {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .btn-success {
        background: rgba(34, 197, 94, 0.2);
        color: #4ade80;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .btn-secondary {
        background: rgba(100, 116, 139, 0.2);
        color: #94a3b8;
        border: 1px solid rgba(100, 116, 139, 0.3);
    }

    .btn-danger {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-danger:hover {
        background: rgba(239, 68, 68, 0.3);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(30, 41, 59, 0.3);
        border-radius: 8px;
        overflow: hidden;
    }

    table th,
    table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(139, 92, 246, 0.2);
    }

    table th {
        background: rgba(139, 92, 246, 0.2);
        color: #cbd5e1;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    table td {
        color: #e2e8f0;
    }

    table tbody tr:hover {
        background: rgba(139, 92, 246, 0.1);
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .badge-info {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
    }

    .badge-warning {
        background: rgba(245, 158, 11, 0.2);
        color: #fbbf24;
    }

    .badge-danger {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    .badge-success {
        background: rgba(34, 197, 94, 0.2);
        color: #4ade80;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #64748b;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Create Form -->
<div class="card">
    <h2>
        <i class="fas fa-plus-circle"></i>
        Tạo Thông Báo Mới
    </h2>

    <form method="POST">
        <input type="hidden" name="notices_action" value="create">

        <div class="form-row">
            <div class="form-group">
                <label>Tiêu Đề *</label>
                <input type="text" name="title" class="form-control" required placeholder="VD: Bảo trì hệ thống">
            </div>

            <div class="form-group">
                <label>Loại Thông Báo</label>
                <select name="type" class="form-control">
                    <option value="info">💡 Thông Tin</option>
                    <option value="warning">⚠️ Cảnh Báo</option>
                    <option value="danger">🚨 Nguy Hiểm</option>
                    <option value="success">✅ Thành Công</option>
                </select>
            </div>

            <div class="form-group">
                <label>Gửi Đến</label>
                <select name="target_user_id" class="form-control">
                    <option value="">Tất cả users</option>
                    <?php foreach ($all_users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username'], ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Nội Dung *</label>
            <textarea name="content" class="form-control" required placeholder="Nhập nội dung thông báo..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i>
            Tạo Thông Báo
        </button>
    </form>
</div>

<!-- Notices List -->
<div class="card">
    <h2>
        <i class="fas fa-list"></i>
        Danh Sách Thông Báo (<?= count($notices) ?>)
    </h2>

    <?php if (empty($notices)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Chưa có thông báo nào</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tiêu Đề</th>
                    <th>Nội Dung</th>
                    <th>Loại</th>
                    <th>Người Nhận</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notices as $notice): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($notice['title'], ENT_QUOTES) ?></strong></td>
                        <td><?= htmlspecialchars(substr($notice['content'], 0, 60), ENT_QUOTES) ?>...</td>
                        <td>
                            <?php
                            $badges = ['info' => 'badge-info', 'warning' => 'badge-warning', 'danger' => 'badge-danger', 'success' => 'badge-success'];
                            $badge = $badges[$notice['type']] ?? 'badge-info';
                            ?>
                            <span class="badge <?= $badge ?>"><?= ucfirst($notice['type']) ?></span>
                        </td>
                        <td>
                            <?php if ($notice['target_user_id']): ?>
                                <?= htmlspecialchars($notice['username'], ENT_QUOTES) ?>
                            <?php else: ?>
                                <span style="color: #64748b;">Tất cả</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="notices_action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $notice['id'] ?>">
                                <button type="submit"
                                    class="btn btn-sm <?= $notice['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                    <?= $notice['is_active'] ? 'On' : 'Off' ?>
                                </button>
                            </form>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($notice['created_at'])) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" data-id="<?php echo (int) $notice['id']; ?>"
                                data-title='<?= htmlspecialchars($notice["title"], ENT_QUOTES) ?>'
                                onclick="deleteNotice(this.dataset.id, this.dataset.title)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    function deleteNotice(id, title) {
        if (!id || id === '' || id === '0') {
            alert('ID không hợp lệ');
            return;
        }

        var confirmMessage = title ?
            'Bạn có chắc muốn xóa thông báo "' + title + '"?' :
            'Bạn có chắc muốn xóa thông báo này?';

        if (confirm(confirmMessage)) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            var input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'notices_action';
            input1.value = 'delete';

            var input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'id';
            input2.value = String(id);

            form.appendChild(input1);
            form.appendChild(input2);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>