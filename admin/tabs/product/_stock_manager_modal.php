<div id="stockManagerModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 1200px;">
        <div class="modal-header">
            <h3 style="margin: 0;"><i class="fas fa-database" style="color: #8b5cf6;"></i> Quản Lý Kho Tài Khoản</h3>
            <button type="button" class="modal-close" onclick="closeStockManager()"><i
                    class="fas fa-times"></i></button>
        </div>

        <div class="modal-body">
            <!-- Tabs -->
            <div
                style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid rgba(139, 92, 246, 0.2); padding-bottom: 1rem;">
                <button type="button" id="tabAvailable" class="stock-tab active" onclick="switchStockTab('available')">
                    <i class="fas fa-box"></i> Tồn Kho <span id="availableCount" class="tab-badge">0</span>
                </button>
                <button type="button" id="tabSold" class="stock-tab" onclick="switchStockTab('sold')">
                    <i class="fas fa-shopping-cart"></i> Đã Bán <span id="soldCount" class="tab-badge sold">0</span>
                </button>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <span style="color: var(--text-muted);">Tổng:</span>
                        <span id="stockCount"
                            style="background: #8b5cf6; color: #fff; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 700; margin-left: 0.5rem;">0</span>
                    </div>
                    <div id="stockActions" style="display: flex; gap: 0.5rem;">
                        <input type="file" id="stockFileInput" accept=".txt" style="display: none;"
                            onchange="handleStockFileUpload(event)">
                        <button type="button" class="btn" onclick="document.getElementById('stockFileInput').click()"
                            style="background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; color: #22c55e; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.background='#22c55e'; this.style.color='#fff';"
                            onmouseout="this.style.background='rgba(34, 197, 94, 0.1)'; this.style.color='#22c55e';">
                            <i class="fas fa-upload"></i> Upload File .txt
                        </button>
                        <button type="button" class="btn" onclick="clearAllStock()"
                            style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.background='#ef4444'; this.style.color='#fff';"
                            onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';">
                            <i class="fas fa-trash-alt"></i> Xóa All
                        </button>
                        <button type="button" class="btn btn-primary" onclick="addStockRow()">
                            <i class="fas fa-plus"></i> Thêm
                        </button>
                    </div>
                </div>

                <div style="position: relative;">
                    <input type="text" id="stockSearchInput" placeholder="Tìm kiếm username hoặc password..."
                        style="width: 100%; padding: 0.5rem 2.5rem 0.5rem 0.75rem; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 8px; color: var(--text-primary); font-size: 0.9rem;"
                        oninput="searchStock(this.value)">
                    <button type="button" id="clearStockSearchBtn" onclick="clearStockSearch()"
                        style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 0.25rem 0.5rem; border-radius: 6px; cursor: pointer; display: none; transition: all 0.2s;"
                        onmouseover="this.style.background='#ef4444'; this.style.color='#fff';"
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';"
                        title="Xóa tìm kiếm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th style="text-align: center; min-width: 120px;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody"></tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeStockManager()">
                <i class="fas fa-times"></i> Đóng
            </button>
            <button type="button" class="btn btn-primary" onclick="saveStockManager()">
                <i class="fas fa-save"></i> Lưu
            </button>
        </div>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 2rem;
    }

    .modal-container {
        background: rgba(15, 23, 42, 0.95);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 16px;
        width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        color: #ef4444;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .modal-close:hover {
        background: #ef4444;
        color: #fff;
    }

    .modal-body {
        padding: 2rem;
        overflow-y: auto;
        flex: 1;
    }

    .modal-footer {
        padding: 1.5rem 2rem;
        border-top: 2px solid rgba(139, 92, 246, 0.2);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    .account-table {
        width: 100%;
        border-collapse: collapse;
    }

    .account-table thead {
        background: rgba(139, 92, 246, 0.15);
    }

    .account-table th {
        padding: 1rem;
        text-align: left;
        color: var(--text-primary);
        font-weight: 600;
        border-bottom: 2px solid rgba(139, 92, 246, 0.3);
    }

    .account-table tbody tr {
        border-bottom: 1px solid rgba(139, 92, 246, 0.1);
    }

    .account-table tbody tr:hover {
        background: rgba(139, 92, 246, 0.05);
    }

    .account-table td {
        padding: 0.75rem 1rem;
    }

    .account-table input {
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        color: var(--text-primary);
        width: 100%;
        font-family: 'Courier New', monospace;
    }

    .account-table input:focus {
        outline: none;
        border-color: #8b5cf6;
    }

    .btn-icon {
        padding: 0.5rem;
        width: 36px;
        height: 36px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-icon.btn-delete {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        color: #ef4444;
    }

    .btn-icon.btn-delete:hover {
        background: #ef4444;
        color: #fff;
    }

    .btn-icon.btn-sold {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid #10b981;
        color: #10b981;
    }

    .btn-icon.btn-sold:hover {
        background: #10b981;
        color: #fff;
    }

    .btn-secondary {
        background: rgba(100, 116, 139, 0.2);
        border: 1px solid #64748b;
        color: #cbd5e1;
    }

    .btn-secondary:hover {
        background: rgba(100, 116, 139, 0.3);
        border-color: #94a3b8;
    }

    /* Stock Tabs */
    .stock-tab {
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        color: var(--text-muted);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .stock-tab:hover {
        background: rgba(139, 92, 246, 0.1);
        border-color: rgba(139, 92, 246, 0.5);
    }

    .stock-tab.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: #8b5cf6;
        color: #fff;
    }

    .tab-badge {
        background: #8b5cf6;
        color: #fff;
        padding: 0.15rem 0.5rem;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .tab-badge.sold {
        background: #10b981;
    }

    /* Sold row styling */
    .sold-row {
        background: rgba(16, 185, 129, 0.1) !important;
    }

    .sold-row td {
        color: #10b981;
    }

    .sold-badge {
        background: #10b981;
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        margin-left: 8px;
    }

    .order-link {
        color: #3b82f6;
        text-decoration: none;
        font-size: 0.8rem;
    }

    .order-link:hover {
        text-decoration: underline;
    }

    /* Action buttons container for available tab */
    #stockActions {
        display: flex;
        gap: 0.5rem;
    }
</style>