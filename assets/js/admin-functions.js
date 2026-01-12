/**
 * Admin Global Functions
 * Các functions này hoạt động sau khi AJAX load content
 */

// ==================== USER BALANCE FUNCTIONS ====================

window.editBalance = function (user) {
    const modal = document.getElementById('balanceModal');
    if (!modal) {
        console.error('Balance modal not found');
        return;
    }

    document.getElementById('balance-user-id').value = user.id;
    document.getElementById('balance-username').textContent = user.username;
    document.getElementById('balance-user-id-display').textContent = '#' + String(user.id).substr(-8);
    // Get exchange rate (assume 25000 as fallback)
    const exchangeRate = 25000;
    const usdAmount = (user.balance_vnd / exchangeRate).toFixed(2);

    document.getElementById('balance-current').innerHTML = `
        <div style="font-size:1.3rem">${new Intl.NumberFormat('vi-VN').format(user.balance_vnd)}đ</div>
        <div style="font-size:0.95rem;color:#64748b;margin-top:0.25rem">≈ $${usdAmount}</div>
    `;

    // Reset form
    const form = document.getElementById('balanceForm');
    if (form) form.reset();
    const amountInput = document.getElementById('amount');
    if (amountInput) amountInput.value = '';

    // Set default: Add money, VND
    document.querySelectorAll('.checkbox-option, .currency-option').forEach(l => {
        l.style.borderWidth = '2px';
        l.style.boxShadow = 'none';
        l.style.transform = 'scale(1)';
    });

    // Set default transaction type: Add
    const addOption = document.querySelector('input[value="admin_add"]');
    if (addOption) {
        addOption.checked = true;
        addOption.parentElement.style.borderWidth = '3px';
        addOption.parentElement.style.boxShadow = '0 0 20px rgba(139, 92, 246, 0.4)';
        addOption.parentElement.style.transform = 'scale(1.02)';
    }

    // Set default currency: VND
    const vndOption = document.querySelector('input[name="currency"][value="VND"]');
    if (vndOption) {
        vndOption.checked = true;
        vndOption.parentElement.style.borderWidth = '3px';
        vndOption.parentElement.style.boxShadow = '0 0 20px rgba(139, 92, 246, 0.4)';
        vndOption.parentElement.style.transform = 'scale(1.02)';
    }

    modal.style.display = 'block';
}

window.closeBalanceModal = function () {
    const modal = document.getElementById('balanceModal');
    if (modal) {
        modal.style.display = 'none';
    }
    const form = document.getElementById('balanceForm');
    if (form) form.reset();
}

window.changeRole = async function (userId, currentRole) {
    const newRole = currentRole == 'admin' ? 'user' : 'admin';
    const confirmed = await adminModal.confirm({
        title: 'Thay đổi vai trò?',
        message: `Chuyển sang: ${newRole.toUpperCase()}`,
        type: 'warning'
    });

    if (confirmed) {
        adminModal.showLoading('Đang cập nhật vai trò...', 'Vui lòng đợi');
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="role" value="${newRole}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

window.toggleStatus = async function (userId, isActive) {
    const action = isActive ? 'Mở khóa' : 'Khóa';
    const confirmed = await adminModal.confirm({
        title: action + ' tài khoản?',
        message: 'Bạn có chắc?',
        type: 'warning'
    });

    if (confirmed) {
        adminModal.showLoading(action + ' tài khoản...', 'Đang xử lý yêu cầu');
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="is_active" value="${isActive}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ==================== ORDER FUNCTIONS ====================

window.viewOrder = function (order) {
    const details = `
        <div style="background:rgba(30,41,59,0.6);padding:1.5rem;border-radius:12px;border:1px solid rgba(139,92,246,0.2)">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                <div>
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.25rem">Mã đơn:</div>
                    <div style="color:#f8fafc;font-weight:600">${order.order_number}</div>
                </div>
                <div>
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.25rem">Khách hàng:</div>
                    <div style="color:#f8fafc;font-weight:600">${order.username}</div>
                </div>
                <div>
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.25rem">Tổng tiền:</div>
                    <div style="color:#10b981;font-weight:700;font-size:1.2rem">
                        ${order.currency == 'VND' ? new Intl.NumberFormat('vi-VN').format(order.total_amount_vnd) + 'đ' : '$' + order.total_amount_usd}
                    </div>
                </div>
                <div>
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.25rem">Trạng thái:</div>
                    <div>${getStatusBadge(order.status)}</div>
                </div>
                <div>
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.25rem">Ngày tạo:</div>
                    <div style="color:#f8fafc">${new Date(order.created_at).toLocaleString('vi-VN')}</div>
                </div>
                <div>
                    <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.25rem">IP:</div>
                    <div style="color:#f8fafc">${order.ip_address || 'N/A'}</div>
                </div>
            </div>
            ${order.note ? `<div style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(139,92,246,0.2)">
                <div style="color:#94a3b8;font-size:0.9rem;margin-bottom:0.5rem">Ghi chú:</div>
                <div style="color:#f8fafc">${order.note}</div>
            </div>` : ''}
        </div>
    `;

    const detailsEl = document.getElementById('orderDetails');
    const modalEl = document.getElementById('viewModal');
    if (detailsEl) detailsEl.innerHTML = details;
    if (modalEl) modalEl.style.display = 'block';
}

window.closeViewModal = function () {
    const modal = document.getElementById('viewModal');
    if (modal) modal.style.display = 'none';
}

window.getStatusBadge = function (status) {
    const badges = {
        'pending': '<span class="badge badge-warning">Chờ xử lý</span>',
        'completed': '<span class="badge badge-success">Hoàn thành</span>',
        'cancelled': '<span class="badge badge-danger">Đã hủy</span>',
        'refunded': '<span class="badge badge-info">Hoàn tiền</span>'
    };
    return badges[status] || status;
}

window.updateStatus = async function (orderId, newStatus) {
    const statusText = {
        'completed': 'duyệt',
        'cancelled': 'hủy',
        'refunded': 'hoàn tiền'
    };

    const confirmed = await adminModal.confirm({
        title: 'Cập nhật trạng thái?',
        message: `Bạn có chắc muốn ${statusText[newStatus]} đơn hàng này?`,
        type: 'warning'
    });

    if (confirmed) {
        adminModal.showLoading('Đang cập nhật trạng thái...', 'Đợi xử lý đơn hàng');
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="${orderId}">
            <input type="hidden" name="status" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ==================== LOADING MODAL (Legacy) ====================
// Giữ lại cho tương thích ngược, nhưng nên dùng adminModal

window.showLoadingModal = function (message = 'Đang xử lý...') {
    if (window.adminModal) {
        adminModal.showLoading(message, 'Vui lòng đợi...');
    }
}

window.hideLoadingModal = function () {
    if (window.adminModal) {
        adminModal.close();
    }
}

// ==================== INIT HANDLERS AFTER AJAX LOAD ====================

window.initAdminHandlers = function () {
    // Transaction type handlers
    document.querySelectorAll('.transaction-type-option').forEach(label => {
        label.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // Currency handlers
    document.querySelectorAll('.currency-select-option').forEach(label => {
        label.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            // Update hint based on currency
            const amountHint = document.getElementById('amount-hint');
            if (amountHint) {
                if (radio.value === 'VND') {
                    amountHint.textContent = 'VND: Phải chia hết cho 1,000 (10,000 | 23,300 | 100,000)';
                    amountHint.style.color = '#64748b';
                } else {
                    amountHint.textContent = 'USD: Nhập số thập phân (10 | 10.50 | 100)';
                    amountHint.style.color = '#64748b';
                }
            }
        });
    });

    // Balance form submit handler
    const balanceForm = document.getElementById('balanceForm');
    if (balanceForm) {
        // Remove existing listeners
        const newForm = balanceForm.cloneNode(true);
        balanceForm.parentNode.replaceChild(newForm, balanceForm);

        newForm.addEventListener('submit', function (e) {
            const btn = document.getElementById('submitBalanceBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="loading-icon-inline"></span> Đang xử lý...';
            }
            adminModal.showLoading('Đang cập nhật số dư...', 'Vui lòng chờ xử lý giao dịch');
        });
    }

    // Product form handlers
    const priceVnd = document.getElementById('price-vnd');
    const discount = document.getElementById('discount');
    const labelColor = document.getElementById('label-color');
    const categorySelect = document.getElementById('category-select');
    const newCategoryInput = document.getElementById('new-category-input');
    const newCategoryIcon = document.getElementById('new-category-icon');

    if (priceVnd) priceVnd.addEventListener('input', updateProductPrices);
    if (discount) discount.addEventListener('input', updateProductPrices);

    // Color picker
    if (labelColor) {
        labelColor.addEventListener('input', function (e) {
            const color = e.target.value;
            const colorPreview = document.getElementById('color-preview');
            const colorValue = document.getElementById('color-value');
            if (colorPreview) colorPreview.style.background = color;
            if (colorValue) colorValue.textContent = color;
        });
    }

    // Category selector - clear new category when selecting existing
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            if (this.value && newCategoryInput) {
                newCategoryInput.value = '';
                if (newCategoryIcon) newCategoryIcon.value = '';
            }
        });
    }

    // Clear category select when typing new category
    if (newCategoryInput) {
        newCategoryInput.addEventListener('input', function () {
            if (this.value.trim() && categorySelect) {
                categorySelect.value = '';
            }
        });
    }

    if (newCategoryIcon) {
        newCategoryIcon.addEventListener('input', function () {
            if (this.value.trim() && categorySelect) {
                categorySelect.value = '';
            }
        });
    }

    // Image upload hover effect
    const imageUpload = document.querySelector('.image-upload');
    if (imageUpload) {
        imageUpload.addEventListener('mouseenter', function () {
            this.style.borderColor = '#8b5cf6';
            this.style.background = 'rgba(139,92,246,0.05)';
        });

        imageUpload.addEventListener('mouseleave', function () {
            this.style.borderColor = 'rgba(139,92,246,0.3)';
            this.style.background = 'transparent';
        });
    }
}

// ==================== PRODUCT FUNCTIONS ====================

window.previewImage = function (event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

window.triggerImageUpload = function () {
    const input = document.getElementById('imageInput');
    if (input) input.click();
}

// Product form price calculator
window.updateProductPrices = function () {
    const vndInput = document.getElementById('price-vnd');
    const discountInput = document.getElementById('discount');
    const usdDisplay = document.getElementById('price-usd-display');
    const finalPricesDiv = document.getElementById('final-prices');

    if (!vndInput || !discountInput) return;

    // Get exchange rate from data attribute or default
    const exchangeRate = parseFloat(document.body.dataset.exchangeRate) || 24000;

    const vnd = parseFloat(vndInput.value) || 0;
    const discount = parseFloat(discountInput.value) || 0;

    // Calculate USD
    const usd = vnd / exchangeRate;
    if (usdDisplay) {
        usdDisplay.value = '$' + usd.toFixed(2);
    }

    // Calculate discount
    const discountVnd = vnd * discount / 100;
    const discountUsd = usd * discount / 100;

    const finalVnd = vnd - discountVnd;
    const finalUsd = usd - discountUsd;

    // Update display
    if (finalPricesDiv) {
        if (discount > 0) {
            finalPricesDiv.innerHTML = `
                <div style="text-decoration:line-through;color:#64748b;margin-bottom:0.5rem">
                    ${new Intl.NumberFormat('vi-VN').format(vnd)}đ ($${usd.toFixed(2)})
                </div>
                <div style="font-size:1.3rem;font-weight:700;color:#10b981">
                    ${new Intl.NumberFormat('vi-VN').format(finalVnd)}đ
                </div>
                <div style="color:#64748b;font-size:0.9rem">
                    $${finalUsd.toFixed(2)} <span style="color:#ef4444">(-${discount}%)</span>
                </div>
            `;
        } else {
            finalPricesDiv.innerHTML = `
                <div style="font-size:1.3rem;font-weight:700;color:#10b981">${new Intl.NumberFormat('vi-VN').format(finalVnd)}đ</div>
                <div style="color:#64748b;font-size:0.9rem">$${finalUsd.toFixed(2)}</div>
            `;
        }
    }
}

// Auto-init on page load
document.addEventListener('DOMContentLoaded', initAdminHandlers);

// Auto-init after AJAX content load (call this from admin/index.php)
window.addEventListener('contentLoaded', initAdminHandlers);
