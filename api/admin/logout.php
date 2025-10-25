<?php
require_once '../config.php';
require_once '../models/Auth.php';

$auth = new Auth();
$auth->logout();

// Redirect to login page
header('Location: ../../admin/login.html');
exit;
