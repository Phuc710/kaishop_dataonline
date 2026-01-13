// Product Account JS
const exchangeRate = 25000;
let variantIndex = 0;
let accountsData = {
    one: [],
    multi: {}
};
let currentMode = 'one';

// Toggle customer info label
document.getElementById('requiresCustomerInfo')?.addEventListener('change', function () {
    const labelGroup = document.getElementById('customerInfoLabelGroup');
    labelGroup.style.display = this.checked ? 'block' : 'none';
});

// Switch option mode
function switchOptionMode(mode) {
    document.querySelectorAll('.option-mode-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-mode="${mode}"]`).classList.add('active');

    document.getElementById('oneOptionContent').classList.toggle('active', mode === 'one');
    document.getElementById('multiOptionContent').classList.toggle('active', mode === 'multi');
    document.getElementById('optionMode').value = mode;

    if (mode === 'multi' && document.getElementById('variantsList').children.length === 0) {
        addVariant();
        addVariant();
    }
}

// Calculate one option prices
function calculateOneOption() {
    const priceVnd = parseFloat(document.querySelector('input[name="price_vnd"]').value) || 0;
    const discount = parseFloat(document.querySelector('input[name="discount_percent"]').value) || 0;

    const priceUsd = (priceVnd / exchangeRate).toFixed(2);
    const finalVnd = Math.round(priceVnd * (1 - discount / 100));

    document.getElementById('oneOptionUsd').textContent = `USD: $${priceUsd}`;
    document.getElementById('oneOptionFinal').textContent = `Giá cuối: ${finalVnd.toLocaleString('vi-VN')}đ`;
}

// Validate one option max
function validateOneOptionMax() {
    const stock = parseInt(document.getElementById('oneOptionStock').value) || 0;
    const maxInput = document.getElementById('oneOptionMax');
    const max = parseInt(maxInput.value) || 0;

    if (max > stock) {
        maxInput.value = stock;
        maxInput.style.borderColor = '#ef4444';
        setTimeout(() => {
            maxInput.style.borderColor = '';
        }, 2000);

        if (window.notify) {
            notify.error(`❌ Mua Max (${max}) phải ≤ Tồn Kho (${stock})`);
        } else {
            alert(`Max mua phải ≤ ${stock}`);
        }
    }
}

// Add variant
function addVariant() {
    variantIndex++;
    const html = `
        <div class="variant-item" id="variant-${variantIndex}">
            <div class="variant-header">
                <h5><i class="fas fa-layer-group"></i> Variant #${variantIndex}</h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeVariant(${variantIndex})">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
            
            <div class="variant-body">
                <div class="variant-section">
                    <h6 style="color: #60a5fa; margin-bottom: 1rem;">
                        <i class="fas fa-info-circle"></i> Thông Tin Variant
                    </h6>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-tag"></i>
                            Tên Variant <span style="color: #ef4444;">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="variant_name[]" 
                            class="form-control" 
                            required
                        >
                    </div>
                </div>
                
                <div class="variant-section">
                    <h6 style="color: #10b981; margin-bottom: 1rem;">
                        <i class="fas fa-dollar-sign"></i> Giá & Giảm Giá
                    </h6>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-money-bill-wave"></i>
                                Giá Gốc (VND) <span style="color: #ef4444;">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="variant_price[]" 
                                class="form-control variant-price" 
                                required
                                data-variant="${variantIndex}"
                                oninput="calculateVariant(${variantIndex})"
                            >
                            <small class="variant-usd-${variantIndex}" style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                                USD: $0.00
                            </small>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-percent"></i>
                                Giảm Giá (%) <span style="color: #ef4444;">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="variant_discount[]" 
                                class="form-control" 
                                min="0"
                                max="100"
                                value="0"
                                oninput="calculateVariant(${variantIndex})"
                            >
                            <small class="variant-final-${variantIndex}" style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                                Giá cuối: 0đ
                            </small>
                        </div>
                    </div>

                    <!-- Customer Info Requirement for Variant -->
                    <div style="border-top: 1px solid rgba(139, 92, 246, 0.2); padding-top: 1rem; margin-top: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                                <input type="checkbox" name="variant_requires_customer_info[]" value="1" class="variant-customer-info-${variantIndex}"
                                    style="width: 20px; height: 20px;" onchange="toggleVariantFileUpload(${variantIndex})">
                                <span style="color: var(--text-primary); font-weight: 600;">
                                    <i class="fas fa-user-circle" style="color: #8b5cf6;"></i>
                                    Yêu cầu khách hàng nhập thông tin khi mua
                                </span>
                            </label>
                            <small style="color: var(--text-muted); display: block; margin-left: 2.25rem;">
                                <i class="fas fa-info-circle"></i>
                                Dành cho sản phẩm dịch vụ
                            </small>
                        </div>

                        <div id="variantCustomerInfoLabel-${variantIndex}"
                            style="display: none; margin-top: 1rem; padding-left: 2rem; border-left: 3px solid #8b5cf6;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>
                                    <i class="fas fa-tag"></i>
                                    Nhãn yêu cầu
                                </label>
                                <textarea name="variant_customer_info_label[]" class="form-control" rows="3"
                                    placeholder="VD: Nhập email và số điện thoại của bạn"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="variant-section">
                    <h6 style="color: #a78bfa; margin-bottom: 1rem;">
                        <i class="fas fa-upload"></i> Upload Tài Khoản Vào Kho
                    </h6>
                    
                    <div class="form-group file-upload-section">
                        <label>
                            <i class="fas fa-file-upload"></i>
                            File Tài Khoản (.txt) <span style="color: #ef4444;">*</span>
                        </label>
                        <input 
                            type="file" 
                            name="variant_file_${variantIndex}" 
                            class="form-control" 
                            accept=".txt"
                            onchange="parseAccountFile(this, ${variantIndex})"
                            style="padding: 0.5rem;"
                        >
                        <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> 
                            Format: <code style="background: rgba(139, 92, 246, 0.2); padding: 0.2rem 0.5rem; border-radius: 4px;">TK|MK</code>
                        </small>
                    </div>
                    
                    <div id="accountPreview-${variantIndex}" class="file-upload-section" style="display: none; margin-top: 1rem;">
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <span style="color: #10b981; font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> File hợp lệ
                                    </span>
                                    <span id="accountCount-${variantIndex}" style="background: #10b981; color: #fff; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 700; margin-left: 0.75rem;"></span>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm stock-manager-btn" onclick="openAccountManager(${variantIndex})">
                                    <i class="fas fa-cog"></i> Quản Lý Kho
                                </button>
                            </div>
                            <div id="accountList-${variantIndex}" style="max-height: 100px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.85rem; color: var(--text-secondary);"></div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>
                            <i class="fas fa-boxes"></i>
                            Tồn Kho <span style="color: #ef4444;">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="variant_stock[]" 
                            class="form-control variant-stock-${variantIndex}" 
                            required
                            min="0"
                            value="0"
                            readonly
                            style="background: rgba(139, 92, 246, 0.1); cursor: not-allowed;"
                            oninput="validateVariantMax(${variantIndex})"
                        >
                        <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> 
                            Tự động tính từ file .txt
                        </small>
                    </div>
                    
                    <div class="form-grid-2" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-arrow-down"></i>
                                Mua Min
                            </label>
                            <input 
                                type="number" 
                                name="variant_min[]" 
                                class="form-control" 
                                min="1"
                                value="1"
                            >
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-arrow-up"></i>
                                Mua Max
                            </label>
                            <input 
                                type="number" 
                                name="variant_max[]" 
                                class="form-control variant-max-${variantIndex}" 
                                min="1"
                                value="2"
                                oninput="validateVariantMax(${variantIndex})"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('variantsList').insertAdjacentHTML('beforeend', html);

    // Initialize empty data for this variant
    accountsData.multi[variantIndex] = [];
}

// Remove variant
function removeVariant(index) {
    const variant = document.getElementById(`variant-${index}`);
    if (variant) {
        variant.style.opacity = '0';
        variant.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            variant.remove();
            // Clean up data
            if (accountsData.multi[index]) {
                delete accountsData.multi[index];
            }
        }, 300);
    }
}

// Calculate variant prices
function calculateVariant(index) {
    const priceInput = document.querySelector(`.variant-price[data-variant="${index}"]`);
    const discountInput = priceInput.closest('.variant-body').querySelector('input[name="variant_discount[]"]');

    const priceVnd = parseFloat(priceInput.value) || 0;
    const discount = parseFloat(discountInput.value) || 0;

    const priceUsd = (priceVnd / exchangeRate).toFixed(2);
    const finalVnd = Math.round(priceVnd * (1 - discount / 100));

    document.querySelector(`.variant-usd-${index}`).textContent = `USD: $${priceUsd}`;
    document.querySelector(`.variant-final-${index}`).textContent = `Giá cuối: ${finalVnd.toLocaleString('vi-VN')}đ`;
}

// Validate variant max
function validateVariantMax(index) {
    const stockInput = document.querySelector(`.variant-stock-${index}`);
    const maxInput = document.querySelector(`.variant-max-${index}`);

    if (!stockInput || !maxInput) return;

    const stock = parseInt(stockInput.value) || 0;
    const max = parseInt(maxInput.value) || 0;

    if (max > stock) {
        maxInput.value = stock;
        maxInput.style.borderColor = '#ef4444';
        setTimeout(() => {
            maxInput.style.borderColor = '';
        }, 2000);

        if (window.notify) {
            notify.error(`❌ Variant #${index}: Mua Max (${max}) phải ≤ Tồn Kho (${stock})`);
        } else {
            alert(`Variant #${index}: Max mua phải ≤ ${stock}`);
        }
    }
}

// Toggle variant file upload visibility
function toggleVariantFileUpload(variantIndex) {
    const checkbox = document.querySelector(`.variant-customer-info-${variantIndex}`);
    const isChecked = checkbox.checked;

    // Show/hide customer info label
    const labelGroup = document.getElementById(`variantCustomerInfoLabel-${variantIndex}`);
    if (labelGroup) {
        labelGroup.style.display = isChecked ? 'block' : 'none';
    }

    // Hide/show file upload sections in this variant
    const variantElement = document.getElementById(`variant-${variantIndex}`);
    if (variantElement) {
        const fileUploadSections = variantElement.querySelectorAll('.file-upload-section');
        fileUploadSections.forEach(section => {
            section.style.display = isChecked ? 'none' : 'block';
        });

        // Make stock input editable when checked
        const stockInput = variantElement.querySelector(`.variant-stock-${variantIndex}`);
        if (stockInput) {
            if (isChecked) {
                stockInput.removeAttribute('readonly');
                stockInput.style.background = '';
                stockInput.style.cursor = '';
            } else {
                stockInput.setAttribute('readonly', 'readonly');
                stockInput.style.background = 'rgba(139, 92, 246, 0.1)';
                stockInput.style.cursor = 'not-allowed';
            }
        }

        // Hide/show stock manager button
        const stockManagerBtn = variantElement.querySelector('.stock-manager-btn');
        if (stockManagerBtn) {
            stockManagerBtn.style.display = isChecked ? 'none' : 'block';
        }
    }
}

// Parse account file
function parseAccountFile(input, mode) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function (e) {
        const content = e.target.result;
        const lines = content.split('\n').filter(line => line.trim() !== '');

        const accounts = [];
        lines.forEach((line, idx) => {
            const trimmedLine = line.trim();
            if (!trimmedLine) return;

            accounts.push({
                id: Date.now() + idx,
                content: trimmedLine
            });
        });

        if (accounts.length === 0) {
            if (window.notify) {
                notify.error('File không có tài khoản hợp lệ! Format đúng: TK|MK');
            }
            input.value = '';
            return;
        }

        // Store data
        if (mode === 'one') {
            accountsData.one = accounts;
            currentMode = 'one';

            // Update UI
            document.getElementById('accountCountOne').textContent = `${accounts.length} tài khoản`;
            document.getElementById('oneOptionStock').value = accounts.length;

            // Show preview
            const listDiv = document.getElementById('accountListOne');
            listDiv.innerHTML = accounts.slice(0, 5).map((acc, i) => {
                return `<div style="padding: 0.25rem 0; border-bottom: 1px solid rgba(139, 92, 246, 0.1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${i + 1}. ${acc.content}
                </div>`;
            }).join('');

            if (accounts.length > 5) {
                listDiv.innerHTML += `<div style="padding: 0.5rem; text-align: center; color: #8b5cf6; font-weight: 600;">
                    ... và ${accounts.length - 5} tài khoản khác
                </div>`;
            }

            document.getElementById('accountPreviewOne').style.display = 'block';
        } else {
            // Multi variant mode
            const variantIdx = mode;
            accountsData.multi[variantIdx] = accounts;
            currentMode = variantIdx;

            // Update UI
            document.getElementById(`accountCount-${variantIdx}`).textContent = `${accounts.length} tài khoản`;
            document.querySelector(`.variant-stock-${variantIdx}`).value = accounts.length;

            // Show preview
            const listDiv = document.getElementById(`accountList-${variantIdx}`);
            listDiv.innerHTML = accounts.slice(0, 3).map((acc, i) => {
                return `<div style="padding: 0.25rem 0; border-bottom: 1px solid rgba(139, 92, 246, 0.1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${i + 1}. ${acc.content}
                </div>`;
            }).join('');

            if (accounts.length > 3) {
                listDiv.innerHTML += `<div style="padding: 0.5rem; text-align: center; color: #8b5cf6; font-weight: 600;">
                    ... và ${accounts.length - 3} tài khoản khác
                </div>`;
            }

            document.getElementById(`accountPreview-${variantIdx}`).style.display = 'block';
        }

        if (window.notify) {
            notify.success(`Đã load ${accounts.length} tài khoản thành công!`);
        }
    };

    reader.readAsText(file);
}

// Open account manager modal
function openAccountManager(mode) {
    currentMode = mode;
    const accounts = mode === 'one' ? accountsData.one : (accountsData.multi[mode] || []);

    if (accounts.length === 0) {
        if (window.notify) {
            notify.warning('Chưa có tài khoản nào trong kho!');
        }
        return;
    }

    // Render table
    const tbody = document.getElementById('accountTableBody');
    tbody.innerHTML = accounts.map((acc, idx) => {
        return `
            <tr data-id="${acc.id}">
                <td style="text-align: center; font-weight: 600;">${idx + 1}</td>
                <td>
                    <input 
                        type="text" 
                        value="${acc.content}" 
                        data-field="content"
                        onchange="updateAccountField(${acc.id}, 'content', this.value)"
                        placeholder="Nội dung tài khoản..."
                    >
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn-icon btn-delete" onclick="deleteAccountRow(${acc.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    document.getElementById('modalAccountCount').textContent = `${accounts.length} tài khoản`;
    document.getElementById('accountManagerModal').style.display = 'flex';
}

// Close account manager
function closeAccountManager() {
    document.getElementById('accountManagerModal').style.display = 'none';
}

// Update account field
function updateAccountField(id, field, value) {
    if (currentMode === 'one') {
        const account = accountsData.one.find(a => a.id === id);
        if (account) {
            account[field] = value;
        }
    } else {
        const account = accountsData.multi[currentMode]?.find(a => a.id === id);
        if (account) {
            account[field] = value;
        }
    }
}

// Delete account row
function deleteAccountRow(id) {
    if (!confirm('Xóa tài khoản này?')) return;

    if (currentMode === 'one') {
        accountsData.one = accountsData.one.filter(a => a.id !== id);

        // Re-render
        openAccountManager('one');

        // Update stock
        document.getElementById('oneOptionStock').value = accountsData.one.length;
        document.getElementById('accountCountOne').textContent = `${accountsData.one.length} tài khoản`;
    } else {
        accountsData.multi[currentMode] = accountsData.multi[currentMode].filter(a => a.id !== id);

        // Re-render
        openAccountManager(currentMode);

        // Update stock
        const newCount = accountsData.multi[currentMode].length;
        document.querySelector(`.variant-stock-${currentMode}`).value = newCount;
        document.getElementById(`accountCount-${currentMode}`).textContent = `${newCount} tài khoản`;
    }

    if (window.notify) {
        notify.success('Đã xóa tài khoản!');
    }
}

// Add account row
function addAccountRow() {
    const newAccount = {
        id: Date.now(),
        content: ''
    };

    if (currentMode === 'one') {
        accountsData.one.push(newAccount);
        openAccountManager('one');

        // Update stock
        document.getElementById('oneOptionStock').value = accountsData.one.length;
        document.getElementById('accountCountOne').textContent = `${accountsData.one.length} tài khoản`;
    } else {
        if (!accountsData.multi[currentMode]) {
            accountsData.multi[currentMode] = [];
        }
        accountsData.multi[currentMode].push(newAccount);
        openAccountManager(currentMode);

        // Update stock
        const newCount = accountsData.multi[currentMode].length;
        document.querySelector(`.variant-stock-${currentMode}`).value = newCount;
        document.getElementById(`accountCount-${currentMode}`).textContent = `${newCount} tài khoản`;
    }
}

// Save account manager
function saveAccountManager() {
    // Validate all accounts
    let hasError = false;
    const accounts = currentMode === 'one' ? accountsData.one : (accountsData.multi[currentMode] || []);

    accounts.forEach((acc, idx) => {
        if (!acc.content) {
            hasError = true;
        }
    });

    if (hasError) {
        if (window.notify) {
            notify.error('Vui lòng điền đầy đủ thông tin cho tất cả tài khoản!');
        }
        return;
    }

    if (window.notify) {
        notify.success(`Đã lưu ${accounts.length} tài khoản!`);
    }

    closeAccountManager();
}

// Search accounts in modal
function searchAccounts(query) {
    const searchBtn = document.getElementById('clearSearchBtn');
    const tbody = document.getElementById('accountTableBody');
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
        const content = row.querySelector('[data-field="content"]')?.value.toLowerCase() || '';

        if (content.includes(searchLower)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Update count
    const accounts = currentMode === 'one' ? accountsData.one : (accountsData.multi[currentMode] || []);
    if (query.trim()) {
        document.getElementById('modalAccountCount').textContent = `${visibleCount}/${accounts.length} tài khoản`;
    } else {
        document.getElementById('modalAccountCount').textContent = `${accounts.length} tài khoản`;
    }
}

// Clear account search
function clearAccountSearch() {
    const searchInput = document.getElementById('accountSearchInput');
    searchInput.value = '';
    searchAccounts('');
    searchInput.focus();
}

// Form submit handler - Serialize accountsData to JSON
document.getElementById('productForm')?.addEventListener('submit', function (e) {
    const accountsDataInput = document.getElementById('accountsDataInput');
    if (accountsDataInput) {
        accountsDataInput.value = JSON.stringify(accountsData);
    }
});
