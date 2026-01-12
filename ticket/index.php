<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect(url('auth'));
}

// Redirect to user tickets page
redirect(url('user?tab=tickets'));
