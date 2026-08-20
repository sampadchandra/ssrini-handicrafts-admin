<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Ssrini handicrafts</title>
</head>
<body>

<h1>Welcome to Ssrini Handicrafts Admin Panel</h1>
    <p>
        Welcome,
        <?=  htmlspecialchars(
            $_SESSION['admin_name'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>

    <p>
        You are successfully logged in.
    </p>

    <a href="logout.php">
        Logout
    </a>
</body>
</html>