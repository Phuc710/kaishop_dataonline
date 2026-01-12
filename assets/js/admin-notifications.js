/**
 * Global Notification Management Functions
 * These functions are available across all admin pages
 */

// Make functions globally available
window.editNotification = function(notif) {
    const modal = document.getElementById('editModal');
    if (!modal) {
        console.error('Edit modal not found. This function only works on the notifications page.');
        return;
    }
    
    document.getElementById('edit-id').value = notif.id;
    document.getElementById('edit-message').value = notif.message;
    document.getElementById('edit-icon').value = notif.icon || '';
    document.getElementById('edit-bg').value = notif.bg_color;
    document.getElementById('edit-text').value = notif.text_color;
    document.getElementById('edit-order').value = notif.display_order;
    
    const speedInput = document.getElementById('edit-speed');
    if (speedInput) {
        speedInput.value = notif.speed || 50;
    }
    
    document.getElementById('edit-active').checked = notif.is_active == 1;
    modal.style.display = 'block';
}

window.closeEditModal = function() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

window.deleteNotification = async function(id) {
    if (!window.notify) {
        if (!confirm('Bạn có chắc muốn xóa thông báo này?')) {
            return;
        }
    } else {
        const confirmed = await notify.confirm({
            title: 'Xóa thông báo?', 
            message: 'Bạn có chắc muốn xóa thông báo này?', 
            type: 'warning'
        });
        
        if (!confirmed) {
            return;
        }
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}
