/**
 * Cart Helper - Giỏ hàng helper functions
 * Hỗ trợ thêm sản phẩm vào giỏ và cập nhật badge
 */

(function () {
    'use strict';

    // Get base URL from config or fallback
    const baseUrl = window.APP_CONFIG?.baseUrl || '';

    // Update cart badge count
    function updateCartCount() {
        fetch(`${baseUrl}/api/cart_count.php`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.kai-badge');
                    const cartBtn = document.querySelector('.kai-cart-btn');

                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count;
                        } else if (cartBtn) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'kai-badge';
                            newBadge.textContent = data.count;
                            cartBtn.appendChild(newBadge);
                        }
                    } else {
                        if (badge) {
                            badge.remove();
                        }
                    }
                }
            })
            .catch(err => console.error('Cart count error:', err));
    }

    // Add product to cart
    function addToCart(productId, quantity = 1, variantId = null) {
        const requestData = {
            product_id: productId,
            quantity: quantity
        };

        // Add variant_id if provided
        if (variantId) {
            requestData.variant_id = variantId;
        }

        return fetch(`${baseUrl}/giohang/add.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update cart badge
                    updateCartCount();

                    // Dispatch custom event
                    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: data }));

                    // Show success notification
                    if (typeof notify !== 'undefined') {
                        notify.success('Thành công!', data.message);
                    }
                } else {
                    // Show error notification
                    if (typeof notify !== 'undefined') {
                        notify.error('Lỗi!', data.message);
                    }
                }
                return data;
            })
            .catch(err => {
                console.error('Add to cart error:', err);
                if (typeof notify !== 'undefined') {
                    notify.error('Lỗi!', 'Không thể thêm vào giỏ hàng');
                }
                return { success: false, message: 'Network error' };
            });
    }

    // Quick add to cart from product cards
    function quickAddToCart(productId, buttonElement, variantId = null) {
        let originalText = '';

        if (buttonElement) {
            originalText = buttonElement.innerHTML;
            buttonElement.innerHTML = '<span class="loading-icon-inline" style="margin-right: 0;"></span>';
            buttonElement.disabled = true;
        }

        addToCart(productId, 1, variantId).finally(() => {
            if (buttonElement && originalText) {
                buttonElement.innerHTML = originalText;
                buttonElement.disabled = false;
            }
        });
    }

    // Expose functions to global scope for inline onclick handlers
    window.updateCartCount = updateCartCount;
    window.addToCart = addToCart;
    window.quickAddToCart = quickAddToCart;

    // Initialize cart on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Update cart count if cart button exists
        if (document.querySelector('.kai-cart-btn')) {
            updateCartCount();
        }
    });

    // Log removed

})();
