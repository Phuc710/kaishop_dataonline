// Password Toggle Function
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
}

function switchTab(tab) {
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);

    // Update tabs
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

    if (tab === 'login') {
        document.querySelectorAll('.tab')[0].classList.add('active');
        document.getElementById('login-tab').classList.add('active');
    } else {
        document.querySelectorAll('.tab')[1].classList.add('active');
        document.getElementById('register-tab').classList.add('active');
    }
}

// Google Sign-In Handler (Turnstile verification handled server-side)
async function handleGoogleSignIn() {
    const btn = event.target.closest('.btn-google');
    if (!btn) return;

    try {
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-icon-inline"></span> Đang xử lý...';

        // Proceed with Google Sign-In
        const provider = new window.GoogleAuthProvider();
        const result = await window.signInWithPopup(window.firebaseAuth, provider);
        const user = result.user;

        // Prepare payload with full Google profile
        const payload = {
            email: user.email,
            displayName: user.displayName,
            photoURL: user.photoURL,
            idToken: await user.getIdToken()
        };

        // Send token to backend
        const googleLoginUrl = `${window.APP_CONFIG.baseUrl}/auth/google-login.php`;
        const response = await fetch(googleLoginUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            notify.success('Thành công!', 'Đăng nhập Google thành công!');
            setTimeout(() => {
                window.location.href = window.APP_CONFIG.baseUrl;
            }, 500);
        } else {
            throw new Error(data.message || 'Lỗi đăng nhập');
        }
    } catch (error) {
        console.error('Google Sign-In Error:', error);

        let errorMessage = 'Đăng nhập Google thất bại';

        if (error.code === 'auth/popup-blocked') {
            errorMessage = 'Trình duyệt chặn popup. Vui lòng cho phép popup và thử lại.';
        } else if (error.code === 'auth/popup-closed-by-user') {
            errorMessage = 'Bạn đã đóng popup đăng nhập.';
        } else if (error.code === 'auth/network-request-failed') {
            errorMessage = 'Lỗi kết nối mạng. Vui lòng kiểm tra internet.';
        } else if (error.message) {
            errorMessage = error.message;
        }

        notify.error('Lỗi!', errorMessage);

        // Reset button
        btn.disabled = false;
        const isLogin = btn.id === 'googleLoginBtn';
        btn.innerHTML = `
            <svg class="google-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
            </svg>
            ${isLogin ? 'Đăng nhập bằng Google' : 'Đăng ký bằng Google'}
        `;
    }
}

// Attach event listeners
document.addEventListener('DOMContentLoaded', function () {
    const googleLoginBtn = document.getElementById('googleLoginBtn');
    const googleRegisterBtn = document.getElementById('googleRegisterBtn');

    if (googleLoginBtn) {
        googleLoginBtn.addEventListener('click', handleGoogleSignIn);
    }
    if (googleRegisterBtn) {
        googleRegisterBtn.addEventListener('click', handleGoogleSignIn);
    }
});
