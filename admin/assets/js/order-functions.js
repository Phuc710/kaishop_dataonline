// Order Management Functions

async function viewOrder(order) {
    const modal = document.getElementById('viewModal');
    const details = document.getElementById('orderDetails');

    details.innerHTML = '<div style="text-align:center;padding:2rem;color:#64748b"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    modal.style.display = 'block';

    try {
        // Convert order.id to string to prevent JavaScript precision loss with large integers
        const orderId = String(order.id);
        const response = await fetch(`${window.APP_URL}/admin/api/get-order-details.php?order_id=${orderId}`);
        const data = await response.json();
        
        console.log('API Response:', data);
        console.log('Order ID (string):', orderId);

        if (!data.success) {
            details.innerHTML = `<div style="color:#ef4444">Lỗi: ${data.message}</div>`;
            return;
        }

        const items = data.items || [];
        console.log('Items count:', items.length);

        // Check for customer info in order note or items
        const customerInfo = order.note || items.find(i => i.customer_info)?.customer_info || '';
        const hasCustomerInfo = !!customerInfo;

        // Determine status config
        const statusConfig = {
            'pending': {badge: 'warning', text: 'Đang xử lý', color: '#f59e0b', bg: 'rgba(245,158,11,0.15)'},
            'completed': {badge: 'success', text: 'Hoàn thành', color: '#10b981', bg: 'rgba(16,185,129,0.15)'},
            'cancelled': {badge: 'danger', text: 'Đã hủy', color: '#ef4444', bg: 'rgba(239,68,68,0.15)'},
            'refunded': {badge: 'info', text: 'Hoàn tiền', color: '#06b6d4', bg: 'rgba(6,182,212,0.15)'}
        };
        const status = statusConfig[order.status] || statusConfig['pending'];
        
        let html = `
            <div style="background:rgba(139,92,246,0.1);padding:1rem;border-radius:8px;margin-bottom:1.5rem">
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:0.5rem">
                    <div><strong style="color:#8b5cf6">Mã đơn:</strong> ${order.order_number}</div>
                    <div><strong style="color:#8b5cf6">Khách hàng:</strong> ${order.username}</div>
                    <div><strong style="color:#8b5cf6">Trạng thái:</strong> 
                        <span class="badge" style="background:${status.bg};color:${status.color};font-weight:600;padding:0.4rem 0.8rem;border-radius:6px;text-transform:uppercase;font-size:0.75rem">${status.text}</span>
                    </div>
                    <div><strong style="color:#8b5cf6">Tổng tiền:</strong> ${formatMoney(order.total_amount_vnd)}đ</div>
                </div>
                ${order.cancellation_reason ? `
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(239,68,68,0.2)">
                        <div style="color:#ef4444;font-weight:600;margin-bottom:0.25rem">
                            <i class="fas fa-ban"></i> Lý do hủy đơn:
                        </div>
                        <div style="color:#f8fafc;font-size:1rem;background:rgba(239,68,68,0.1);padding:0.75rem;border-radius:6px;border:1px solid rgba(239,68,68,0.2)">
                            ${order.cancellation_reason}
                        </div>
                    </div>
                ` : ''}
                ${hasCustomerInfo ? `
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(139,92,246,0.2)">
                        <div style="color:#8b5cf6;font-weight:600;margin-bottom:0.25rem">
                            <i class="fas fa-user-edit"></i> Thông tin khách hàng:
                        </div>
                        <div style="color:#f8fafc;font-size:1rem;background:rgba(15,23,42,0.5);padding:0.75rem;border-radius:6px;border:1px solid rgba(139,92,246,0.2)">
                            ${customerInfo}
                        </div>
                    </div>
                ` : ''}
            </div>
            </div>
            
            <h4 style="color:#f8fafc;margin-bottom:1rem"><i class="fas fa-box"></i> Sản phẩm (${items.length})</h4>
        `;

        if (items.length === 0) {
            html += `
                <div style="background:rgba(239,68,68,0.1);padding:1.5rem;border-radius:8px;border:1px solid rgba(239,68,68,0.3);text-align:center">
                    <i class="fas fa-exclamation-triangle" style="color:#ef4444;font-size:2rem;margin-bottom:0.5rem"></i>
                    <p style="color:#ef4444;font-weight:600;margin-bottom:0.5rem">Không có dữ liệu sản phẩm</p>
                    <p style="color:#94a3b8;font-size:0.9rem">Đơn hàng này bị lỗi trong quá trình xử lý. Có thể do lỗi hệ thống hoặc database.</p>
                </div>
            `;
        }

        items.forEach((item, index) => {
            const itemId = String(item.id);
            const isDelivered = item.delivery_content && item.delivery_content.trim() !== '';
            
            // Item status badge: only 2 states - delivered or waiting
            let itemStatusHtml = '';
            if (isDelivered) {
                itemStatusHtml = `<span style="background:rgba(16,185,129,0.2);color:#10b981;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600"><i class="fas fa-check-circle"></i> Đã giao</span>`;
            } else {
                itemStatusHtml = `<span style="background:rgba(245,158,11,0.2);color:#f59e0b;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600"><i class="fas fa-clock"></i> Chờ Admin</span>`;
            }
            
            html += `
                <div style="background:rgba(15,23,42,0.5);padding:1rem;border-radius:8px;margin-bottom:1rem;border:1px solid ${isDelivered ? 'rgba(16,185,129,0.3)' : 'rgba(139,92,246,0.2)'}">
                    <div style="display:flex;gap:1rem;margin-bottom:0.5rem">
                        ${item.product_image ? `<img src="${window.APP_URL}/assets/images/uploads/${item.product_image}" style="width:60px;height:60px;border-radius:8px;object-fit:cover;border:2px solid rgba(139,92,246,0.3)">` : ''}
                        <div style="flex:1">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem">
                                <strong style="color:#f8fafc">${item.product_name}</strong>
                                <div style="display:flex;gap:0.5rem;align-items:center">
                                    ${itemStatusHtml}
                                    <span style="color:#10b981;font-weight:600">x${item.quantity}</span>
                                </div>
                            </div>
                            <div style="color:#64748b;font-size:0.9rem">
                                Đơn giá: ${formatMoney(item.price_vnd)}đ | Tổng: <strong style="color:#10b981">${formatMoney(item.subtotal_vnd)}đ</strong>
                            </div>
                        </div>
                    </div>
                    ${item.customer_info ? `
                        <div style="margin-top:0.5rem;padding:0.5rem;background:rgba(139,92,246,0.1);border-radius:4px">
                            <small style="color:#8b5cf6;font-weight:600"><i class="fas fa-user-edit"></i> Thông tin KH:</small>
                            <span style="color:#f8fafc;margin-left:0.5rem">${item.customer_info}</span>
                        </div>
                    ` : ''}
                    ${isDelivered ? `
                        <div style="margin-top:0.75rem;padding:0.75rem;background:rgba(16,185,129,0.1);border-left:3px solid #10b981;border-radius:4px">
                            <div style="color:#10b981;font-weight:600;margin-bottom:0.25rem">
                                <i class="fas fa-check-circle"></i> Nội dung đã giao:
                            </div>
                            <div style="color:#f8fafc;font-size:0.95rem;white-space:pre-wrap">${item.delivery_content}</div>
                        </div>
                    ` : (order.status === 'pending' ? `
                        <div style="margin-top:0.75rem;padding:0.75rem;background:rgba(245,158,11,0.1);border-left:3px solid #f59e0b;border-radius:4px">
                            <div style="color:#f59e0b;font-weight:600;margin-bottom:0.5rem">
                                <i class="fas fa-reply"></i> Giao hàng cho item này:
                            </div>
                            <textarea id="itemResponse_${itemId}" rows="2" class="form-control" 
                                placeholder="Nhập tài khoản, mã code, link..."
                                style="width:100%;padding:0.5rem;background:rgba(15,23,42,0.8);border:1px solid rgba(245,158,11,0.3);color:#f8fafc;border-radius:6px;resize:vertical;font-size:0.9rem"></textarea>
                            <div style="display:flex;gap:0.5rem;margin-top:0.5rem">
                                <button onclick="deliverItem('${itemId}')" class="btn btn-success" style="padding:0.5rem 1rem;font-size:0.85rem;flex:1">
                                    <i class="fas fa-paper-plane"></i> Giao Item
                                </button>
                                <button onclick="cancelItem('${itemId}', '${item.product_name}')" class="btn btn-danger" style="padding:0.5rem 1rem;font-size:0.85rem;flex:1">
                                    <i class="fas fa-times"></i> Hủy Item
                                </button>
                            </div>
                        </div>
                    ` : '')}
                </div>
            `;
        });

        // Add cancel entire order button if order is pending (only if needed)
        if (order.status === 'pending') {
            html += `
                <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(139,92,246,0.2);text-align:center">
                    <button onclick="cancelOrder(\`${orderId}\`, \`${order.order_number}\`)" class="btn btn-outline-danger" style="padding:0.75rem 2rem">
                        <i class="fas fa-times-circle"></i> Hủy Toàn Bộ Đơn Hàng
                    </button>
                </div>
            `;
        }

        details.innerHTML = html;

    } catch (error) {
        console.error('Error:', error);
        details.innerHTML = `<div style="color:#ef4444">Lỗi kết nối: ${error.message}</div>`;
    }
}

function updateStatus(orderId, status) {
    if (!confirm(`Xác nhận đổi trạng thái đơn hàng?`)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="order_id" value="${orderId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

async function cancelOrder(orderId, orderNumber) {
    // Prompt nhập lý do hủy
    const reason = prompt(`Nhập lý do hủy đơn ${orderNumber}:`);
    
    if (!reason || !reason.trim()) {
        if (window.notify) {
            notify.warning('Thiếu thông tin', 'Vui lòng nhập lý do hủy đơn');
        }
        return;
    }

    const confirmed = await notify.confirm({
        title: 'Xác nhận hủy đơn',
        message: `Hủy đơn hàng ${orderNumber}?\n\nLý do: ${reason.trim()}\n\nĐơn hàng sẽ bị hủy và hoàn tiền cho khách.`,
        confirmText: 'Xác Nhận Hủy',
        cancelText: 'Quay Lại',
        type: 'warning'
    });

    if (!confirmed) {
        return;
    }
    
    const reasonText = reason.trim();

    try {
        const response = await fetch(`${window.APP_URL}/admin/api/cancel-order.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                reason: reasonText
            })
        });

        const data = await response.json();

        if (data.success) {
            if (window.notify) {
                notify.success('Thành công!', data.message);
            }
            closeViewModal();
            // Reload page
            if (window.smoothReloadWithProgress) {
                smoothReloadWithProgress(800);
            } else {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            if (window.notify) {
                notify.error('Lỗi!', data.message);
            }
        }

    } catch (error) {
        console.error('Error:', error);
        if (window.notify) {
            notify.error('Lỗi!', 'Không thể hủy đơn hàng');
        }
    }
}

// Deliver single item by admin
async function deliverItem(itemId) {
    const responseInput = document.getElementById(`itemResponse_${itemId}`);
    const responseText = responseInput ? responseInput.value.trim() : '';

    if (!responseText) {
        if (window.notify) {
            notify.warning('Thiếu thông tin', 'Vui lòng nhập nội dung giao hàng (tài khoản, mã code, link...)');
        }
        return;
    }

    const confirmed = await notify.confirm({
        title: 'Xác nhận giao hàng',
        message: `Giao item này với nội dung:\n\n${responseText}`,
        confirmText: 'Giao Hàng',
        cancelText: 'Hủy',
        type: 'success'
    });

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch(`${window.APP_URL}/admin/api/deliver-item.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_item_id: itemId,
                admin_response: responseText
            })
        });

        const data = await response.json();

        if (data.success) {
            if (window.notify) {
                notify.success('Thành công!', data.message);
            }
            // Reload the modal to show updated status
            closeViewModal();
            if (window.smoothReloadWithProgress) {
                smoothReloadWithProgress(500);
            } else {
                setTimeout(() => location.reload(), 500);
            }
        } else {
            if (window.notify) {
                notify.error('Lỗi!', data.message);
            }
        }

    } catch (error) {
        console.error('Error:', error);
        if (window.notify) {
            notify.error('Lỗi!', 'Không thể giao hàng');
        }
    }
}

// Cancel single item by admin
async function cancelItem(itemId, productName) {
    const confirmed = await notify.confirm({
        title: 'Xác nhận hủy item',
        message: `Hủy item "${productName}"?\n\nItem này sẽ bị hủy và hoàn tiền cho khách.`,
        confirmText: 'Xác Nhận Hủy',
        cancelText: 'Quay Lại',
        type: 'warning'
    });

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch(`${window.APP_URL}/admin/api/cancel-item.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_item_id: itemId
            })
        });

        const data = await response.json();

        if (data.success) {
            if (window.notify) {
                notify.success('Thành công!', data.message);
            }
            closeViewModal();
            if (window.smoothReloadWithProgress) {
                smoothReloadWithProgress(500);
            } else {
                setTimeout(() => location.reload(), 500);
            }
        } else {
            if (window.notify) {
                notify.error('Lỗi!', data.message);
            }
        }

    } catch (error) {
        console.error('Error:', error);
        if (window.notify) {
            notify.error('Lỗi!', 'Không thể hủy item');
        }
    }
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Close modal on outside click
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('viewModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeViewModal();
            }
        });
    }
});
