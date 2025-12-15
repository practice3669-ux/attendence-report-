<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

logActivity('logout', 'users', getCurrentUserId());
logout();

header('Location: index.php');
exit();

