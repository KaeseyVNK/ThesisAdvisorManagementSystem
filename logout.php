<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Destroy the session
session_start();
session_unset();
session_destroy();

// Redirect to login page
setFlashMessage('Đã đăng xuất thành công!', 'success');
redirect('login.php');
?> 