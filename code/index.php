<?php
require_once 'classes/AuthManager.php';
$auth = new AuthManager();

if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
