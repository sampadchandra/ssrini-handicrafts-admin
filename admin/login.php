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

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --purple-dark: #16051f;
            --purple: #421052;
            --purple-main: #6d176f;
            --magenta: #a51c78;
            --gold: #d8a82e;
            --gold-light: #f2d477;
            --cream: #fffaf0;
            --white: #ffffff;
            --text: #302335;
            --muted: #8a7b8e;
            --border: #eadfe9;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            font-family:
                Inter,
                Arial,
                sans-serif;

            min-height: 100vh;

            background:
                radial-gradient(
                    circle at 15% 20%,
                    rgba(194, 95, 180, 0.20),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 85% 80%,
                    rgba(216, 168, 46, 0.13),
                    transparent 25%
                ),
                linear-gradient(
                    135deg,
                    #120418 0%,
                    #2b082f 42%,
                    #4b0d4d 72%,
                    #16051f 100%
                );

            color:
                var(--text);

            overflow-x:
                hidden;
        }

        body::before {
            content: "";

            position: fixed;

            inset: 0;

            pointer-events: none;

            background:
                radial-gradient(
                    circle at 50% 50%,
                    transparent 0%,
                    rgba(0, 0, 0, 0.16) 100%
                );

            z-index: 0;
        }

        body::after {
            content: "";

            position: fixed;

            width: 600px;
            height: 600px;

            border-radius: 50%;

            border:
                1px solid rgba(242, 212, 119, 0.12);

            top: -300px;
            right: -220px;

            box-shadow:
                0 0 0 45px rgba(242, 212, 119, 0.025),
                0 0 0 90px rgba(242, 212, 119, 0.018);

            pointer-events: none;

            z-index: 0;
        }

        .login-page {
            min-height: 100vh;

            width: 100%;

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(420px, 520px);

            position: relative;

            z-index: 1;
        }

        .login-showcase {
            position: relative;

            min-height: 100vh;

            padding: 55px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            overflow: hidden;
        }

        .login-showcase::before {
            content: "";

            position: absolute;

            width: 520px;
            height: 520px;

            border-radius: 50%;

            border:
                1px solid rgba(242, 212, 119, 0.16);

            left: -260px;
            top: 50%;

            transform:
                translateY(-50%);

            box-shadow:
                0 0 0 35px rgba(242, 212, 119, 0.025),
                0 0 0 70px rgba(242, 212, 119, 0.018);
        }

        .login-showcase::after {
            content: "";

            position: absolute;

            width: 300px;
            height: 300px;

            border-radius: 50%;

            border:
                1px solid rgba(242, 212, 119, 0.10);

            right: -150px;
            bottom: -150px;
        }

        .brand {
            position: relative;

            z-index: 2;

            margin-bottom: 38px;
        }

        .brand-mark {
            width: 72px;
            height: 72px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 18px;

            background:
                linear-gradient(
                    145deg,
                    #f5dc80,
                    #b27b18
                );

            color:
                #3b092f;

            font-family:
                "Playfair Display",
                serif;

            font-size:
                36px;

            font-weight:
                700;

            border:
                3px solid rgba(255, 255, 255, 0.55);

            box-shadow:
                0 12px 35px rgba(0, 0, 0, 0.35),
                0 0 0 8px rgba(216, 168, 46, 0.08);
        }

        .brand h1 {
            color:
                #ffffff;

            font-family:
                "Playfair Display",
                Georgia,
                serif;

            font-size:
                clamp(34px, 4vw, 58px);

            line-height:
                1.05;

            letter-spacing:
                1px;

            margin-bottom:
                13px;
        }

        .brand h1::first-letter {
            color:
                var(--gold-light);
        }

        .brand p {
            color:
                rgba(255, 255, 255, 0.68);

            font-size:
                14px;

            letter-spacing:
                3px;

            text-transform:
                uppercase;
        }

        .showcase-content {
            position: relative;

            z-index: 2;

            max-width:
                650px;
        }

        .showcase-content h2 {
            color:
                var(--gold-light);

            font-family:
                "Playfair Display",
                Georgia,
                serif;

            font-size:
                clamp(30px, 4vw, 52px);

            line-height:
                1.15;

            margin-bottom:
                18px;
        }

        .showcase-content > p {
            color:
                rgba(255, 255, 255, 0.70);

            font-size:
                15px;

            line-height:
                1.8;

            max-width:
                530px;
        }

        .achievement-list {
            display:
                flex;

            gap:
                13px;

            margin-top:
                34px;

            flex-wrap:
                wrap;
        }

        .achievement {
            min-width:
                145px;

            padding:
                15px 17px;

            border:
                1px solid rgba(242, 212, 119, 0.20);

            border-radius:
                14px;

            background:
                rgba(255, 255, 255, 0.045);

            backdrop-filter:
                blur(10px);

            color:
                rgba(255, 255, 255, 0.86);
        }

        .achievement i {
            color:
                var(--gold-light);

            font-size:
                17px;

            margin-bottom:
                9px;
        }

        .achievement strong {
            display:
                block;

            font-size:
                12px;

            line-height:
                1.45;

            font-weight:
                600;
        }

        .login-area {
            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 45px 35px;

            position: relative;

            background:
                linear-gradient(
                    135deg,
                    rgba(255, 250, 240, 0.98),
                    rgba(255, 255, 255, 0.97)
                );

            border-left:
                1px solid rgba(255, 255, 255, 0.08);
        }

        .login-card {
            width: 100%;

            max-width: 420px;

            padding:
                42px 40px 34px;

            background:
                rgba(255, 255, 255, 0.94);

            border:
                1px solid var(--border);

            border-radius:
                24px;

            box-shadow:
                0 30px 70px rgba(40, 11, 42, 0.14);

            position:
                relative;

            overflow:
                hidden;
        }

        .login-card::before {
            content: "";

            position: absolute;

            left: 0;
            right: 0;
            top: 0;

            height: 5px;

            background:
                linear-gradient(
                    90deg,
                    var(--purple-main),
                    var(--magenta),
                    var(--gold)
                );
        }

        .login-card::after {
            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(216, 168, 46, 0.08),
                    transparent 70%
                );

            top: -90px;
            right: -90px;

            pointer-events: none;
        }

        .login-heading {
            position:
                relative;

            z-index:
                2;

            margin-bottom:
                30px;

            text-align:
                center;
        }

        .login-heading h2 {
            font-family:
                "Playfair Display",
                Georgia,
                serif;

            font-size:
                31px;

            color:
                #401043;

            margin-bottom:
                8px;
        }

        .login-heading p {
            color:
                var(--muted);

            font-size:
                13px;
        }

        .error-message {
            position:
                relative;

            z-index:
                2;

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            padding:
                12px 14px;

            margin-bottom:
                20px;

            border:
                1px solid #f2c8d0;

            background:
                #fff1f4;

            color:
                #b52f49;

            border-radius:
                10px;

            font-size:
                12px;

            line-height:
                1.5;
        }

        .login-form {
            position:
                relative;

            z-index:
                2;
        }

        .form-group {
            margin-bottom:
                20px;
        }

        .form-group label {
            display:
                block;

            color:
                #4b3b4e;

            font-size:
                12px;

            font-weight:
                700;

            margin-bottom:
                8px;
        }

        .input-wrapper {
            position:
                relative;
        }

        .input-wrapper > i {
            position:
                absolute;

            left:
                15px;

            top:
                50%;

            transform:
                translateY(-50%);

            color:
                #a78ea9;

            font-size:
                13px;

            pointer-events:
                none;
        }

        .input-wrapper input {
            width:
                100%;

            height:
                48px;

            padding:
                0 15px 0 43px;

            border:
                1px solid var(--border);

            border-radius:
                11px;

            outline:
                none;

            background:
                #fffdfd;

            color:
                var(--text);

            font-size:
                13px;

            transition:
                all 0.22s ease;
        }

        .input-wrapper input:hover {
            border-color:
                #d9c7db;
        }

        .input-wrapper input:focus {
            border-color:
                var(--purple-main);

            background:
                #ffffff;

            box-shadow:
                0 0 0 4px rgba(109, 23, 111, 0.08);
        }

        .form-group:nth-of-type(2) {
            margin-bottom:
                25px;
        }

        .login-button {
            width:
                100%;

            height:
                49px;

            border:
                none;

            border-radius:
                11px;

            background:
                linear-gradient(
                    135deg,
                    #5b1465,
                    #a51c78
                );

            color:
                #ffffff;

            font-size:
                13px;

            font-weight:
                700;

            letter-spacing:
                0.3px;

            cursor:
                pointer;

            box-shadow:
                0 9px 22px rgba(109, 23, 111, 0.24);

            transition:
                all 0.22s ease;
        }

        .login-button:hover {
            transform:
                translateY(-2px);

            box-shadow:
                0 13px 27px rgba(109, 23, 111, 0.32);

            background:
                linear-gradient(
                    135deg,
                    #6d176f,
                    #b51d83
                );
        }

        .login-button:active {
            transform:
                translateY(0);
        }

        .login-button i {
            margin-right:
                7px;
        }

        .trust-section {
            margin-top:
                28px;

            padding-top:
                23px;

            border-top:
                1px solid #eee6ee;

            display:
                grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap:
                8px;

            text-align:
                center;
        }

        .trust-item {
            color:
                #806f82;

            font-size:
                9px;

            line-height:
                1.4;
        }

        .trust-item i {
            display:
                block;

            color:
                #c89424;

            font-size:
                17px;

            margin-bottom:
                7px;
        }

        .trust-item strong {
            display:
                block;

            color:
                #665568;

            font-size:
                9px;

            font-weight:
                700;
        }

        .copyright {
            text-align:
                center;

            color:
                #a59aa6;

            font-size:
                10px;

            margin-top:
                23px;
        }

        @media (max-width: 950px) {

            .login-page {
                grid-template-columns:
                    1fr;
            }

            .login-showcase {
                min-height:
                    auto;

                padding:
                    45px 35px;

                text-align:
                    center;

                align-items:
                    center;
            }

            .showcase-content {
                max-width:
                    700px;
            }

            .showcase-content > p {
                margin-left:
                    auto;

                margin-right:
                    auto;
            }

            .achievement-list {
                justify-content:
                    center;
            }

            .login-area {
                min-height:
                    auto;

                padding:
                    35px 20px 50px;

                border-left:
                    none;
            }
        }

        @media (max-width: 600px) {

            .login-showcase {
                padding:
                    38px 20px 32px;
            }

            .brand {
                margin-bottom:
                    27px;
            }

            .brand-mark {
                width:
                    62px;

                height:
                    62px;

                font-size:
                    30px;

                margin-left:
                    auto;

                margin-right:
                    auto;
            }

            .brand h1 {
                font-size:
                    35px;
            }

            .brand p {
                font-size:
                    10px;

                letter-spacing:
                    2px;
            }

            .showcase-content h2 {
                font-size:
                    31px;
            }

            .showcase-content > p {
                font-size:
                    13px;
            }

            .achievement {
                min-width:
                    135px;
            }

            .login-area {
                padding:
                    20px 14px 35px;
            }

            .login-card {
                padding:
                    35px 22px 27px;

                border-radius:
                    19px;
            }

            .login-heading h2 {
                font-size:
                    28px;
            }

            .trust-section {
                gap:
                    4px;
            }
        }

        @media (max-width: 400px) {

            .brand h1 {
                font-size:
                    31px;
            }

            .showcase-content h2 {
                font-size:
                    27px;
            }

            .achievement {
                min-width:
                    100%;

                text-align:
                    left;

                display:
                    flex;

                align-items:
                    center;

                gap:
                    10px;
            }

            .achievement i {
                margin-bottom:
                    0;
            }

            .login-card {
                padding:
                    32px 18px 25px;
            }

            .trust-item {
                font-size:
                    8px;
            }

            .trust-item strong {
                font-size:
                    8px;
            }
        }

    </style>
</head>

<body>

    <div class="login-page">

        <section class="login-showcase">

            <div class="brand">

                <div class="brand-mark">
                    S
                </div>

                <h1>
                    Ssrini Handicrafts
                </h1>

                <p>
                    Excellence in Handicrafts
                </p>

            </div>


            <div class="showcase-content">

                <h2>
                    Admin Login
                </h2>

                <p>
                    Manage your store with confidence, quality and excellence.
                </p>


                <div class="achievement-list">

                    <div class="achievement">

                        <i class="fa-solid fa-shield-halved"></i>

                        <strong>
                            IndiaMART TrustSEAL
                        </strong>

                    </div>


                    <div class="achievement">

                        <i class="fa-solid fa-trophy"></i>

                        <strong>
                            Award Winning
                        </strong>

                    </div>


                    <div class="achievement">

                        <i class="fa-solid fa-medal"></i>

                        <strong>
                            Top Attendance
                        </strong>

                    </div>

                </div>

            </div>

        </section>


        <section class="login-area">

            <div class="login-card">

                <div class="login-heading">

                    <h2>
                        Ssrini Handicrafts
                    </h2>

                    <p>
                        Admin Login
                    </p>

                </div>


                <?php if ($error !== ''): ?>

                    <p class="error-message">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <span>
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                    </p>

                <?php endif; ?>


                <form
                    method="POST"
                    action=""
                    class="login-form"
                >

                    <div class="form-group">

                        <label for="email">
                            Email
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-regular fa-envelope"></i>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                autocomplete="username"
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-lock"></i>

                            <input 
                                type="password"
                                id="password"
                                name="password"
                                requiredautocomplete="complete-password"
                            >

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="login-button"
                    >

                        <i class="fa-solid fa-lock"></i>

                        Login

                    </button>

                </form>


                <div class="trust-section">

                    <div class="trust-item">

                        <i class="fa-solid fa-shield-halved"></i>

                        <strong>
                            Trusted
                        </strong>

                    </div>


                    <div class="trust-item">

                        <i class="fa-solid fa-award"></i>

                        <strong>
                            Excellence
                        </strong>

                    </div>


                    <div class="trust-item">

                        <i class="fa-solid fa-trophy"></i>

                        <strong>
                            Quality
                        </strong>

                    </div>

                </div>


                <div class="copyright">
                    Ssrini Handicrafts
                </div>

            </div>

        </section>

    </div>

</body>
</html>