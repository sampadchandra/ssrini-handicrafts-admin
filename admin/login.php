<?php

require_once __DIR__ . '/../config/database.php';

if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;

}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if($email === '' || $password === ''){
    $error = 'Please enter your and password.';

} else{

$stmt = $pdo->prepare(
    'SELECT id, name, email, password, role, status
    FROM admins
    WHERE email = :email
    LIMIT 1'
);

$stmt->execute([
    'email' => $email
    ]);
    
    $admin = $stmt->fetch();

    if(
    $admin && 
    $admin['status'] === 'active' &&
    password_verify($password, $admin['password'])
    ) {
    session_regenerate_id(true);

    $_SESSION ['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];

    header('Location: index.php');
    exit;
    } else{
        $error = 'Invalid email or password.';

    }
}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SSRINI HANDICRAFTS</title>
</head>
<body>
    
    <h1>Ssrini Handicrafts</h1>

    <h2>Admin Login</h2>

<?php if ($error !== ''): ?>

<p>
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</p>

<?php endif; ?>

<form method="POST" action="">
    <div>
        <label for="email">Email</label>

        <input
        type="email"
        id="email"
        name="email"
        required
        autocomplete="username"
        >


</div>


<div>  
<br>
</div>
<div>
    <label for="password">Password</label></label>

    <input 
    type="password"
    id="password"
    name="password"
    requiredautocomplete="complete-password"
    >
</div>

<br>
<button type="submit">
    Login
</button>

</form>
</body>
</html>
