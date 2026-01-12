<?php
/**
 * 500 Error Page - Internal Server Error
 * Style: Light Theme (Apidog Style) + Retro TV
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/favicon_helper.php';

$pageTitle = '500 - Lỗi Máy Chủ';
http_response_code(500);

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= defined('SITE_NAME') ? SITE_NAME : 'Error' ?></title>

    <?php echo render_favicon_tags(); ?>

    <link rel="stylesheet" href="<?= function_exists('asset') ? asset('css/style.css') : '#' ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        /* --- 1. Cấu trúc cơ bản --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #ffffff;
            color: #334155;
            overflow: hidden;
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* --- 2. Số 500 Khổng Lồ --- */
        .text_bg_number {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            gap: 2rem;
            z-index: 0;
            pointer-events: none;
            user-select: none;
            width: 100%;
            justify-content: center;
            align-items: center;
        }

        .text_bg_number div {
            font-size: 25vw;
            font-weight: 900;
            line-height: 1;
            color: #02006dff;
            /* Màu xám rõ ràng (Slate-300) */
            opacity: 0.6;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.8);
            transform: scaleY(1.3);
        }

        /* --- 3. Hiệu ứng nền Blob --- */
        .error-bg-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
            background: radial-gradient(circle at 50% 50%, #ffffff 0%, #f1f5f9 100%);
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 20s ease-in-out infinite;
        }

        /* Đổi màu blob sang tông đỏ/cam cho hợp lỗi 500 nhưng vẫn nhẹ nhàng */
        .blob-1 {
            width: 600px;
            height: 600px;
            background: #fecaca;
            top: -10%;
            right: -10%;
        }

        /* Đỏ nhạt */
        .blob-2 {
            width: 500px;
            height: 500px;
            background: #fed7aa;
            bottom: -10%;
            left: -10%;
            animation-delay: -10s;
        }

        /* Cam nhạt */

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(50px, -50px) rotate(10deg);
            }

            66% {
                transform: translate(-30px, 20px) rotate(-5deg);
            }
        }

        /* --- 4. Container Chính --- */
        .error-container {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* --- 5. TV Retro Graphic --- */
        .main_wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24em;
            height: 24em;
            position: relative;
            transform: scale(0.9);
        }

        .main {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 5em;
        }

        /* Antenna & Ears */
        .antenna {
            width: 5em;
            height: 5em;
            border-radius: 50%;
            border: 2px solid #334155;
            background-color: #ef4444;
            margin-bottom: -6em;
            z-index: -1;
            animation: spin 4s linear infinite;
        }

        .antenna_shadow {
            position: absolute;
            width: 50px;
            height: 56px;
            margin-left: 1.68em;
            border-radius: 45%;
            transform: rotate(140deg);
            border: 4px solid transparent;
            box-shadow: inset 0 16px #b91c1c, inset 0 16px 1px 1px #b91c1c;
        }

        .antenna::after,
        .antenna::before {
            content: "";
            position: absolute;
            border-radius: 50%;
            background-color: #fca5a5;
        }

        .antenna::after {
            margin-top: -9.4em;
            margin-left: 0.4em;
            transform: rotate(-25deg);
            width: 1em;
            height: 0.5em;
        }

        .antenna::before {
            margin-top: 0.2em;
            margin-left: 1.25em;
            transform: rotate(-20deg);
            width: 1.5em;
            height: 0.8em;
        }

        @keyframes spin {
            0% {
                transform: rotate(0);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .a1,
        .a2 {
            background-image: linear-gradient(#334155, #334155, #475569);
        }

        .a1 {
            position: relative;
            top: -102%;
            left: -130%;
            width: 12em;
            height: 5.5em;
            border-radius: 50px;
            transform: rotate(-29deg);
            clip-path: polygon(50% 0, 49% 100%, 52% 100%);
        }

        .a2 {
            position: relative;
            top: -210%;
            left: -10%;
            width: 12em;
            height: 4em;
            border-radius: 50px;
            margin-right: 5em;
            clip-path: polygon(47% 0, 47% 0, 34% 34%, 54% 25%, 32% 100%, 29% 96%, 49% 32%, 30% 38%);
            transform: rotate(-8deg);
        }

        .a1d,
        .a2d {
            position: relative;
            width: 0.5em;
            height: 0.5em;
            border-radius: 50%;
            border: 2px solid #334155;
            background-color: #cbd5e1;
            z-index: 99;
        }

        .a1d {
            top: -211%;
            left: -35%;
            transform: rotate(45deg);
        }

        .a2d {
            top: -294%;
            left: 94%;
        }

        /* TV Body - Màu Đỏ cam cho lỗi 500 */
        .tv {
            width: 17em;
            height: 9em;
            margin-top: 3em;
            border-radius: 15px;
            background-color: #ef4444;
            display: flex;
            justify-content: center;
            border: 2px solid #334155;
            box-shadow: 8px 8px 0 rgba(0, 0, 0, 0.05);
        }

        .tv::after {
            content: "";
            position: absolute;
            width: 17em;
            height: 9em;
            border-radius: 15px;
            background: repeating-radial-gradient(#dc2626 0 0.0001%, #00000010 0 0.0002%) 50% 0/2500px 2500px;
            opacity: 0.1;
        }

        .curve_svg {
            position: absolute;
            margin-top: 0.25em;
            margin-left: -0.25em;
            height: 12px;
            width: 12px;
            fill: #334155;
        }

        /* Screen */
        .display_div {
            display: flex;
            align-items: center;
            align-self: center;
            justify-content: center;
            border-radius: 15px;
            box-shadow: 3.5px 3.5px 0 #b91c1c;
        }

        .screen_out1 {
            width: 11em;
            height: 7.75em;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #334155;
        }

        .screen {
            width: 13em;
            height: 7.85em;
            font-family: Montserrat;
            border: 2px solid #1d0e01;
            background: repeating-radial-gradient(#000 0 0.0001%, #fff 0 0.0002%) 50% 0/2500px 2500px, repeating-conic-gradient(#000 0 0.0001%, #fff 0 0.0002%) 60% 60%/2500px 2500px;
            background-blend-mode: difference;
            animation: b 0.2s infinite alternate;
            border-radius: 10px;
            z-index: 99;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #252525;
            letter-spacing: 0.15em;
            text-align: center;
        }

        /* Screen Mobile (Màu mè glitch) */
        .screenM {
            width: 13em;
            height: 7.85em;
            position: relative;
            font-family: Montserrat;
            background: linear-gradient(to right, #ef4444, #f97316, #3a3a3a, #fff);
            /* Đổi sang tông đỏ */
            border-radius: 10px;
            border: 2px solid #000;
            z-index: 99;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #252525;
            letter-spacing: 0.15em;
            text-align: center;
            overflow: hidden;
        }

        .error_text {
            background-color: #000;
            padding: 0.3em;
            font-size: 0.75em;
            color: #fff;
            border-radius: 5px;
            z-index: 10;
        }

        /* Buttons */
        .buttons_div {
            width: 4.25em;
            align-self: center;
            height: 8em;
            background-color: #fca5a5;
            border: 2px solid #334155;
            padding: 0.6em;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            row-gap: 0.75em;
            box-shadow: 3px 3px 0 #b91c1c;
        }

        .b1,
        .b2 {
            width: 1.65em;
            height: 1.65em;
            border-radius: 50%;
            background-color: #b91c1c;
            border: 2px solid #000;
            position: relative;
        }

        .b1::before,
        .b1::after,
        .b1 div,
        .b2::before,
        .b2::after {
            background-color: #000;
        }

        .lines {
            display: flex;
            column-gap: 0.1em;
            align-self: flex-end;
        }

        .line1,
        .line3 {
            width: 2px;
            height: 0.5em;
            background-color: #334155;
            border-radius: 25px 25px 0 0;
            margin-top: 0.5em;
        }

        .line2 {
            flex-grow: 1;
            width: 2px;
            height: 1em;
            background-color: #334155;
            border-radius: 25px 25px 0 0;
        }

        .speakers {
            display: flex;
            flex-direction: column;
            row-gap: 0.5em;
        }

        .speakers .g1 {
            display: flex;
            column-gap: 0.25em;
        }

        .speakers .g1 div {
            width: 0.65em;
            height: 0.65em;
            border-radius: 50%;
            background-color: #b91c1c;
            border: 2px solid #000;
        }

        .speakers .g {
            width: auto;
            height: 2px;
            background-color: #334155;
        }

        .bottom {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            column-gap: 8.7em;
        }

        .base1,
        .base2 {
            height: 1em;
            width: 2em;
            border: 2px solid #334155;
            background-color: #64748b;
            margin-top: -0.15em;
            z-index: -1;
        }

        .base3 {
            position: absolute;
            height: 0.15em;
            width: 17.5em;
            background-color: #334155;
            margin-top: 0.8em;
        }

        /* --- 6. Info Text & Button --- */
        .error-info {
            text-align: center;
            max-width: 600px;
            margin-top: -20px;
        }

        .error-info h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ef4444, #f97316);
            /* Đỏ cam gradient */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-info p {
            color: #ef4444;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 15px 35px;
            background: #ef4444;
            /* Đỏ chủ đạo */
            color: #fff;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(239, 68, 68, 0.5);
            background: #dc2626;
        }

        /* Responsive Screen Switch */
        @media only screen and (max-width: 1024px) {
            .screenM {
                display: flex;
            }

            .screen {
                display: none;
            }
        }

        @media only screen and (min-width: 1025px) {
            .screen {
                display: flex;
            }

            .screenM {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="error-bg-animated">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="text_bg_number">
        <div>5</div>
        <div>0</div>
        <div>0</div>
    </div>

    <div class="error-container">
        <div class="main_wrapper">
            <div class="main">
                <div class="antenna">
                    <div class="antenna_shadow"></div>
                    <div class="a1"></div>
                    <div class="a1d"></div>
                    <div class="a2"></div>
                    <div class="a2d"></div>
                </div>
                <div class="tv">
                    <div class="cruve">
                        <svg class="curve_svg" viewBox="0 0 189.929 189.929">
                            <path
                                d="M70.343,70.343c-30.554,30.553-44.806,72.7-39.102,115.635l-29.738,3.951C-5.442,137.659,11.917,86.34,49.129,49.13 C86.34,11.918,137.664-5.445,189.928,1.502l-3.95,29.738C143.041,25.54,100.895,39.789,70.343,70.343z">
                            </path>
                        </svg>
                    </div>
                    <div class="display_div">
                        <div class="screen_out">
                            <div class="screen_out1">
                                <div class="screen"><span class="error_text">SERVER ERROR</span></div>
                                <div class="screenM"><span class="error_text">SERVER ERROR</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="buttons_div">
                        <div class="b1">
                            <div></div>
                        </div>
                        <div class="b2"></div>
                        <div class="speakers">
                            <div class="g1">
                                <div class="g11"></div>
                                <div class="g12"></div>
                                <div class="g13"></div>
                            </div>
                            <div class="g"></div>
                            <div class="g"></div>
                        </div>
                    </div>
                </div>
                <div class="lines">
                    <div class="line1"></div>
                    <div class="line2"></div>
                    <div class="line3"></div>
                </div>
            </div>
            <div class="bottom">
                <div class="base1"></div>
                <div class="base2"></div>
                <div class="base3"></div>
            </div>
        </div>

        <div class="error-info">
            <h1>Oops! Lỗi máy chủ</h1>
            <p>Đã xảy ra lỗi nội bộ trên máy chủ. Chúng tôi đang khắc phục sự cố. Vui lòng thử lại sau.</p>
            <a href="<?= function_exists('url') ? url('') : '/' ?>" class="btn-home" style="cursor:pointer;">
                <i class="fas fa-home"></i> Về Trang Chủ
            </a>
        </div>
    </div>
</body>

</html>