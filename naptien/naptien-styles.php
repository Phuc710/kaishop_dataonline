<style>
    /* ============================================
   KaiShop Deposit Page - Clean Dark Theme
   Simple & Professional
============================================ */

    :root {
        --bg-main: #020617;
        --bg-card: rgba(30, 41, 59, 0.5);
        --bg-card-hover: rgba(30, 41, 59, 0.8);
        --primary: #8b5cf6;
        --primary-light: #a78bfa;
        --secondary: #ec4899;
        --accent: #f59e0b;
        --success: #10b981;
        --cyan: #06b6d4;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --border-color: rgba(148, 163, 184, 0.15);
        --radius-md: 16px;
        --radius-lg: 20px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        background-color: var(--bg-main);
        color: var(--text-main);
        position: relative;
        overflow-x: hidden;
    }

    /* Premium Background Effects */
    body::before {
        content: "";
        position: fixed;
        inset: 0;
        background-image:
            linear-gradient(to right, rgba(148, 163, 184, 0.03) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(148, 163, 184, 0.03) 1px, transparent 1px);
        background-size: 60px 60px;
        z-index: -1;
        pointer-events: none;
    }

    body::after {
        content: "";
        position: fixed;
        inset: 0;
        background:
            radial-gradient(ellipse 80% 60% at 50% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 60%),
            radial-gradient(ellipse 50% 40% at 80% 80%, rgba(236, 72, 153, 0.1) 0%, transparent 50%);
        z-index: -1;
        pointer-events: none;
    }

    .deposit-page {
        padding: 60px 0 80px;
        min-height: 80vh;
    }

    .content-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Page Header */
    .page-header-section {
        text-align: center;
        margin-bottom: 3rem;
    }

    .page-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 100px;
        color: var(--primary-light);
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .page-title {
        font-size: clamp(2rem, 4vw, 2.5rem);
        font-weight: 800;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .page-subtitle {
        color: var(--text-muted);
        font-size: 1rem;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    /* Feature Cards */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .feature-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        transition: var(--transition);
    }

    .feature-card:hover {
        border-color: rgba(139, 92, 246, 0.5);
        transform: translateY(-2px);
    }

    .feature-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 1rem;
    }

    .feature-title {
        color: var(--text-main);
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .feature-desc {
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Payment Card */
    .payment-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 2rem;
        backdrop-filter: blur(10px);
    }

    .payment-card label {
        display: block;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    /* Quick Amounts */
    .quick-amounts-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    @media (min-width: 768px) {
        .quick-amounts-grid {
            grid-template-columns: repeat(6, 1fr);
        }
    }

    .amount-btn {
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        color: var(--text-muted);
        padding: 0.875rem 0.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .amount-btn:hover {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
        color: var(--text-main);
    }

    .amount-btn.active {
        background: rgba(139, 92, 246, 0.2);
        border-color: var(--primary);
        color: var(--primary-light);
    }

    /* Input Area */
    .input-area {
        margin-bottom: 1.5rem;
    }

    .currency-input-wrapper {
        position: relative;
    }

    .currency-input {
        width: 100%;
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        padding: 1rem 4rem 1rem 1.5rem;
        color: var(--text-main);
        font-size: 1.3rem;
        font-weight: 700;
        transition: var(--transition);
    }

    .currency-input:focus {
        outline: none;
        border-color: var(--primary);
        background: rgba(30, 41, 59, 0.8);
    }

    .currency-suffix {
        position: absolute;
        right: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-weight: 700;
        font-size: 1.1rem;
    }

    /* Submit Button */
    .btn-submit {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Payment Details */
    .payment-details {
        display: none;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border-color);
    }

    .details-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    @media (min-width: 768px) {
        .details-grid {
            grid-template-columns: 280px 1fr;
        }
    }

    /* QR Code */
    .qr-wrapper {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        text-align: center;
    }

    .qr-img {
        width: 100%;
        max-width: 240px;
        display: block;
        margin: 0 auto;
    }

    /* Info Rows */
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: rgba(30, 41, 59, 0.5);
        border: 1px solid rgba(139, 92, 246, 0.1);
        border-radius: 10px;
        margin-bottom: 0.75rem;
    }

    .info-label {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .info-value {
        color: var(--text-main);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-copy {
        background: rgba(139, 92, 246, 0.15);
        color: var(--primary);
        border: none;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-copy:hover {
        background: var(--primary);
        color: white;
    }

    /* Timer */
    .timer-box {
        text-align: center;
        margin-bottom: 1.5rem;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 10px;
        padding: 1rem;
    }

    .timer-text {
        font-size: 1.5rem;
        font-weight: 800;
        font-family: monospace;
    }

    /* Code Highlight */
    .code-highlight {
        font-family: 'Courier New', monospace;
        color: var(--primary-light);
        font-weight: 800;
        font-size: 1rem;
    }

    /* Download QR Button */
    .btn-download-qr {
        width: 100%;
        margin-top: 1rem;
        padding: 0.75rem;
        background: var(--success);
        border: none;
        border-radius: 10px;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-download-qr:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    /* Cancel Button */
    .cancel-btn {
        width: 100%;
        padding: 0.875rem;
        background: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: white;
        border-radius: 10px;
        font-weight: 600;
        margin-top: 1rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .cancel-btn:hover {
    }

    /* Responsive */
    @media (max-width: 640px) {
        .payment-card {
            padding: 1.5rem;
        }

        .info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .info-value {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>