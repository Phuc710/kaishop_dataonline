<?php
require_once __DIR__ . '/config/config.php';

session_destroy();
setFlash('success', 'Đã đăng xuất thành công');
redirect(url(''));
?>
