/**
 * Stock Manager JS - for Account Product Edit
 */

let currentStockMode = 'single';
let currentVariantId = null;
let stockData = [];
let currentTab = 'available';
let allStockItems = [];
let availableItems = [];
let soldItems = [];

// Initialize
function initStockManager(initialData) {
    stockData = initialData || [];
}

// Open modal and load from API
async function openStockManager(mode, variantId) {
    currentStockMode = mode;
    currentVariantId = variantId;
    currentTab = 'available';
    
    // Reset tabs UI
    document.getElementById('tabAvailable').classList.add('active');
    document.getElementById('tabSold').classList.remove('active');
    document.getElementById('stockActions').style.display = 'flex';
    
    // Load from API
    const productId = new URLSearchParams(window.location.search).get('product_id');
    
    try {
        const formData = new FormData();
        formData.append('action', 'load_stock');
        formData.append('product_id', productId);
        formData.append('variant_id', variantId || '');
        formData.append('include_sold', '1');
        
        const response = await fetch(`${window.API_URL}/admin-stock-manager.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            allStockItems = result.items || [];
            availableItems = allStockItems.filter(s => !s.is_used || s.is_used == 0);
            soldItems = allStockItems.filter(s => s.is_used == 1);
            
            // Update tab badges
            document.getElementById('availableCount').textContent = availableItems.length;
            document.getElementById('soldCount').textContent = soldItems.length;
            document.getElementById('stockCount').textContent = availableItems.length;
            
            renderStockTable(availableItems);
        }
    } catch (e) {
        console.error('Load stock error:', e);
        // Fallback to local data
        let filteredStock = stockData.filter(s => {
            if (mode === 'single') return !s.variant_id;
            return s.variant_id === variantId;
        });
        availableItems = filteredStock.filter(s => !s.is_used || s.is_used == 0);
        soldItems = filteredStock.filter(s => s.is_used == 1);
        renderStockTable(availableItems);
    }
    
    document.getElementById('stockManagerModal').style.display = 'flex';
}

// Switch between tabs
function switchStockTab(tab) {
    currentTab = tab;
    
    // Update tab UI
    document.getElementById('tabAvailable').classList.toggle('active', tab === 'available');
    document.getElementById('tabSold').classList.toggle('active', tab === 'sold');
    
    // Show/hide actions (only for available tab)
    document.getElementById('stockActions').style.display = tab === 'available' ? 'flex' : 'none';
    
    // Render appropriate items
    if (tab === 'available') {
        document.getElementById('stockCount').textContent = availableItems.length;
        renderStockTable(availableItems);
    } else {
        document.getElementById('stockCount').textContent = soldItems.length;
        renderSoldTable(soldItems);
    }
}

// Render sold items table (read-only with order info)
function renderSoldTable(items) {
    const tbody = document.getElementById('stockTableBody');
    
    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Chưa có tài khoản nào được bán
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = items.map((item, idx) => {
        const [user, pass] = (item.content || '|').split('|');
        const orderNumber = item.order_number || 'N/A';
        const soldAt = item.sold_at ? new Date(item.sold_at).toLocaleString('vi-VN') : '';
        
        return `
            <tr class="sold-row">
                <td style="text-align: center;">
                    ${idx + 1}
                </td>
                <td>
                    <input type="text" value="${user || ''}" class="form-control" style="background:rgba(16,185,129,0.1);color:#10b981;border-color:#10b981;" readonly>
                </td>
                <td>
                    <input type="text" value="${pass || ''}" class="form-control" style="background:rgba(16,185,129,0.1);color:#10b981;border-color:#10b981;" readonly>
                </td>
                <td style="text-align: center;">
                    <div style="font-size: 0.8rem;">
                        <a href="?tab=orders&view=${orderNumber}" class="order-link" title="Xem đơn hàng">
                            <i class="fas fa-receipt"></i> ${orderNumber}
                        </a>
                        ${soldAt ? `<div style="color:#64748b;font-size:0.7rem;margin-top:2px;">${soldAt}</div>` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Close modal
function closeStockManager() {
    document.getElementById('stockManagerModal').style.display = 'none';
}

// Render table (available items only)
function renderStockTable(items) {
    const tbody = document.getElementById('stockTableBody');
    
    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                    <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Kho trống - Thêm tài khoản mới hoặc Upload file .txt
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = items.map((item, idx) => {
        const [user, pass] = (item.content || '|').split('|');
        
        return `
            <tr data-id="${item.id}">
                <td style="text-align: center;">${idx + 1}</td>
                <td><input type="text" value="${user || ''}" data-field="username" class="form-control"></td>
                <td><input type="text" value="${pass || ''}" data-field="password" class="form-control"></td>
                <td style="text-align: center;">
                    <div style="display: flex; gap: 4px; justify-content: center;">
                        <button type="button" class="btn-icon btn-sold" onclick="markAsSold('${item.id}')" title="Chuyển sang Đã Bán"><i class="fas fa-check"></i></button>
                        <button type="button" class="btn-icon btn-delete" onclick="deleteStockRow('${item.id}')" title="Xóa"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Add row
function addStockRow() {
    const tbody = document.getElementById('stockTableBody');
    const newId = 'new-' + Date.now();
    tbody.insertAdjacentHTML('beforeend', `
        <tr data-id="${newId}">
            <td style="text-align: center;">${tbody.children.length + 1}</td>
            <td><input type="text" value="" data-field="username" class="form-control"></td>
            <td><input type="text" value="" data-field="password" class="form-control"></td>
            <td style="text-align: center;">
                <div style="display: flex; gap: 4px; justify-content: center;">
                    <button type="button" class="btn-icon btn-sold" onclick="markAsSold('${newId}')" title="Chuyển sang Đã Bán">
                        <i class="fas fa-check"></i>
                    </button>
                    <button type="button" class="btn-icon btn-delete" onclick="deleteStockRow('${newId}')" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `);
    updateStockCount();
}

// Mark account as sold (move to sold tab)
async function markAsSold(id) {
    console.log('markAsSold called with id:', id);
    
    if (!confirm('Chuyển tài khoản này sang \"\u0110ã Bán\"?')) return;
    
    // For new items (not saved yet), just remove from available
    if (id && id.toString().startsWith('new-')) {
        document.querySelector(`tr[data-id="${id}"]`)?.remove();
        updateStockCount();
        if (window.notify) {
            notify.warning('Tài khoản chưa lưu, đã xóa khỏi danh sách');
        } else {
            alert('Tài khoản chưa lưu, đã xóa khỏi danh sách');
        }
        return;
    }
    
    const productId = new URLSearchParams(window.location.search).get('product_id');
    const formData = new FormData();
    formData.append('action', 'mark_as_sold');
    formData.append('id', id);
    formData.append('product_id', productId);
    formData.append('variant_id', currentVariantId || '');
    
    console.log('Sending markAsSold request to:', `${window.API_URL}/admin-stock-manager.php`);
    console.log('FormData:', Object.fromEntries(formData));
    
    try {
        const response = await fetch(`${window.API_URL}/admin-stock-manager.php`, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('markAsSold API response:', result);
        
        if (result.success) {
            // Remove from table
            document.querySelector(`tr[data-id="${id}"]`)?.remove();
            
            // Update counts
            const availableCount = document.querySelectorAll('#stockTableBody tr[data-id]').length;
            document.getElementById('stockCount').textContent = availableCount;
            document.getElementById('availableCount').textContent = availableCount;
            
            // Increment sold count
            const soldCountEl = document.getElementById('soldCount');
            soldCountEl.textContent = parseInt(soldCountEl.textContent) + 1;
            
            // Re-number rows
            document.querySelectorAll('#stockTableBody tr').forEach((row, idx) => {
                row.querySelector('td:first-child').textContent = idx + 1;
            });
            
            if (window.notify) {
                notify.success('Đã chuyển sang \"\u0110ã Bán\"!');
            }
        } else {
            if (window.notify) {
                notify.error(result.message || 'Có lỗi xảy ra');
            } else {
                alert(result.message || 'Có lỗi xảy ra');
            }
        }
    } catch (e) {
        console.error('Mark as sold error:', e);
        if (window.notify) {
            notify.error('Lỗi kết nối: ' + e.message);
        } else {
            alert('Lỗi kết nối: ' + e.message);
        }
    }
}

// Helper function to ensure markAsSold is properly called
window.markAsSold = markAsSold;

// Delete row
async function deleteStockRow(id) {
    if (!confirm('Xóa tài khoản này?')) return;
    
    if (id && !id.startsWith('new-')) {
        const formData = new FormData();
        formData.append('action', 'delete_stock');
        formData.append('id', id);
        
        try {
            await fetch(`${window.API_URL}/admin-stock-manager.php`, {
                method: 'POST',
                body: formData
            });
        } catch (e) {
            console.error('Delete error:', e);
        }
    }
    
    document.querySelector(`tr[data-id="${id}"]`)?.remove();
    updateStockCount();
    
    document.querySelectorAll('#stockTableBody tr').forEach((row, idx) => {
        row.querySelector('td:first-child').textContent = idx + 1;
    });
}

// Update count
function updateStockCount() {
    const count = document.querySelectorAll('#stockTableBody tr[data-id]').length;
    document.getElementById('stockCount').textContent = count;
    
    // Update available tab badge if on available tab
    if (currentTab === 'available') {
        document.getElementById('availableCount').textContent = count;
    }
}

// Save
async function saveStockManager() {
    const rows = document.querySelectorAll('#stockTableBody tr[data-id]');
    const items = [];
    
    rows.forEach(row => {
        const userInput = row.querySelector('[data-field="username"]');
        const passInput = row.querySelector('[data-field="password"]');
        
        if (!userInput || !passInput) return;
        
        const user = userInput.value.trim();
        const pass = passInput.value.trim();
        
        if (user && pass) {
            items.push({ username: user, password: pass });
        }
    });
    
    const productId = new URLSearchParams(window.location.search).get('product_id');
    
    const formData = new FormData();
    formData.append('action', 'save_stock');
    formData.append('product_id', productId);
    formData.append('variant_id', currentVariantId || '');
    formData.append('items', JSON.stringify(items));
    
    try {
        const response = await fetch(`${window.API_URL}/admin-stock-manager.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (window.notify) {
                notify.success('Đã lưu thay đổi!');
            } else {
                alert('Đã lưu thay đổi!');
            }
            closeStockManager();
            location.reload();
        } else {
            if (window.notify) {
                notify.error(result.message || 'Có lỗi xảy ra');
            } else {
                alert(result.message || 'Có lỗi xảy ra');
            }
        }
    } catch (e) {
        console.error('Save error:', e);
        alert('Lỗi kết nối');
    }
}

// Delete variant
async function deleteVariant(variantId) {
    if (!confirm('Xóa variant này? Tất cả tài khoản trong kho sẽ bị xóa!')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_variant');
    formData.append('variant_id', variantId);
    
    try {
        const response = await fetch(`${window.API_URL}/admin-stock-manager.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (window.notify) {
                notify.success('Đã xóa variant!');
            }
            location.reload();
        } else {
            if (window.notify) {
                notify.error(result.message || 'Có lỗi xảy ra');
            }
        }
    } catch (e) {
        console.error('Delete variant error:', e);
    }
}

// Search stock
function searchStock(query) {
    const searchBtn = document.getElementById('clearStockSearchBtn');
    const tbody = document.getElementById('stockTableBody');
    const rows = tbody.querySelectorAll('tr');
    
    // Show/hide clear button
    if (query.trim()) {
        searchBtn.style.display = 'block';
    } else {
        searchBtn.style.display = 'none';
    }
    
    const searchLower = query.toLowerCase().trim();
    let visibleCount = 0;
    
    rows.forEach(row => {
        const username = row.querySelector('[data-field="username"]')?.value.toLowerCase() || '';
        const password = row.querySelector('[data-field="password"]')?.value.toLowerCase() || '';
        
        if (username.includes(searchLower) || password.includes(searchLower)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update count
    const totalCount = rows.length;
    if (query.trim()) {
        document.getElementById('stockCount').textContent = `${visibleCount}/${totalCount}`;
    } else {
        document.getElementById('stockCount').textContent = totalCount;
    }
}

// Clear search
function clearStockSearch() {
    const searchInput = document.getElementById('stockSearchInput');
    searchInput.value = '';
    searchStock('');
    searchInput.focus();
}

// Clear all stock (delete all accounts in current view)
async function clearAllStock() {
    const count = document.querySelectorAll('#stockTableBody tr').length;
    
    if (count === 0) {
        if (window.notify) {
            notify.warning('Kho đã trống!');
        } else {
            alert('Kho đã trống!');
        }
        return;
    }
    
    if (!confirm(`⚠️ XÓA TẤT CẢ ${count} TÀI KHOẢN?\n\nHành động này sẽ dọn sạch kho. Bạn có thể upload file .txt mới sau khi xóa.\n\nKhông thể hoàn tác!`)) {
        return;
    }
    
    // Double confirmation
    if (!confirm('Bạn CHẮC CHẮN muốn xóa tất cả?')) {
        return;
    }
    
    const productId = new URLSearchParams(window.location.search).get('product_id');
    const formData = new FormData();
    formData.append('action', 'clear_all_stock');
    formData.append('product_id', productId);
    formData.append('variant_id', currentVariantId || '');
    
    try {
        if (window.showLoading) showLoading();
        
        const response = await fetch(`${window.API_URL}/admin-stock-manager.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (window.hideLoading) hideLoading();
        
        if (result.success) {
            if (window.notify) {
                notify.success(`✅ Đã xóa ${count} tài khoản!`);
            } else {
                alert(`Đã xóa ${count} tài khoản!`);
            }
            closeStockManager();
            location.reload();
        } else {
            if (window.notify) {
                notify.error(result.message || 'Có lỗi xảy ra');
            } else {
                alert(result.message || 'Có lỗi xảy ra');
            }
        }
    } catch (e) {
        if (window.hideLoading) hideLoading();
        console.error('Clear all error:', e);
        if (window.notify) {
            notify.error('Lỗi kết nối: ' + e.message);
        } else {
            alert('Lỗi kết nối: ' + e.message);
        }
    }
}

// Upload file .txt
function handleStockFileUpload(event) {
    const file = event.target.files[0];
    
    if (!file) return;
    
    // Check file extension
    if (!file.name.endsWith('.txt')) {
        if (window.notify) {
            notify.error('Chỉ chấp nhận file .txt');
        } else {
            alert('Chỉ chấp nhận file .txt');
        }
        event.target.value = '';
        return;
    }
    
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const content = e.target.result;
        const lines = content.split('\n').map(line => line.trim()).filter(line => line);
        
        let addedCount = 0;
        const tbody = document.getElementById('stockTableBody');
        
        lines.forEach(line => {
            // Format: username|password
            if (line.includes('|')) {
                const [username, password] = line.split('|').map(s => s.trim());
                
                if (username && password) {
                    const newId = 'new-' + Date.now() + '-' + Math.random();
                    const rowIndex = tbody.children.length + 1;
                    
                    tbody.insertAdjacentHTML('beforeend', `
                        <tr data-id="${newId}">
                            <td style="text-align: center;">${rowIndex}</td>
                            <td><input type="text" value="${username}" data-field="username" class="form-control"></td>
                            <td><input type="text" value="${password}" data-field="password" class="form-control"></td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 4px; justify-content: center;">
                                    <button type="button" class="btn-icon btn-sold" onclick="markAsSold('${newId}')" title="Chuyển sang Đã Bán">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn-icon btn-delete" onclick="deleteStockRow('${newId}')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                    
                    addedCount++;
                }
            }
        });
        
        // Reset file input
        event.target.value = '';
        
        // Update count
        updateStockCount();
        
        // Show notification
        if (addedCount > 0) {
            if (window.notify) {
                notify.success(`✅ Đã thêm ${addedCount} tài khoản từ file!`);
            } else {
                alert(`Đã thêm ${addedCount} tài khoản từ file!`);
            }
        } else {
            if (window.notify) {
                notify.warning('Không tìm thấy tài khoản hợp lệ trong file!\nĐịnh dạng đúng: username|password');
            } else {
                alert('Không tìm thấy tài khoản hợp lệ trong file!\nĐịnh dạng đúng: username|password');
            }
        }
    };
    
    reader.onerror = function() {
        if (window.notify) {
            notify.error('Lỗi đọc file!');
        } else {
            alert('Lỗi đọc file!');
        }
    };
    
    reader.readAsText(file);
}
