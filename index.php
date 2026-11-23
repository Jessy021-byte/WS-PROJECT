<?php
require_once __DIR__ . '/includes/auth.php';
redirect(is_logged_in() ? 'pages/home.php' : 'pages/login.php');