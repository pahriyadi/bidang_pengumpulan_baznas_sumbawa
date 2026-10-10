<?php
require_once __DIR__ . '/config/helpers.php';
logout_user();
header('Location: ' . url('login.php'));
exit;
