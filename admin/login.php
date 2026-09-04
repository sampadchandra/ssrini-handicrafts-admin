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

    } else {

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

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];

            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

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
            --blue-dark: #172c72;
            --blue: #315ed8;
            --blue-light: #76a7ff;
            --pink: #f35b91;
            --pink-light: #ffb4cd;
            --pink-soft: #ffe8f0;
            --sky-soft: #eaf4ff;
            --blue-soft: #dceaff;
            --white: #ffffff;
            --cream: #fffdfd;
            --text: #28324a;
            --muted: #788196;
            --border: #dce3ef;
            --shadow: rgba(55, 83, 150, 0.14);
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            min-height: 100vh;

            font-family:
                Inter,
                Arial,
                sans-serif;

            color:
                var(--text);

            overflow-x:
                hidden;

            background:
                linear-gradient(
                    135deg,
                    #e9f4ff 0%,
                    #f2f7ff 30%,
                    #fffafd 62%,
                    #ffe8f2 100%
                );

            position:
                relative;
        }

        body::before {
            content: "";

            position: fixed;

            inset: 0;

            pointer-events: none;

            background:
                radial-gradient(
                    circle at 4% 8%,
                    rgba(74, 137, 255, 0.22),
                    transparent 22%
                ),
                radial-gradient(
                    circle at 96% 10%,
                    rgba(255, 116, 164, 0.20),
                    transparent 21%
                ),
                radial-gradient(
                    circle at 88% 90%,
                    rgba(255, 174, 204, 0.22),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 12% 90%,
                    rgba(94, 157, 255, 0.16),
                    transparent 24%
                );

            z-index:
                0;
        }

        body::after {
            content: "";

            position: fixed;

            width:
                720px;

            height:
                720px;

            border-radius:
                50%;

            border:
                1px solid rgba(81, 133, 225, 0.10);

            top:
                -360px;

            left:
                -360px;

            box-shadow:
                0 0 0 45px rgba(81, 133, 225, 0.035),
                0 0 0 90px rgba(81, 133, 225, 0.025),
                0 0 0 145px rgba(81, 133, 225, 0.018),
                0 0 0 205px rgba(81, 133, 225, 0.012);

            pointer-events:
                none;

            z-index:
                0;
        }

        .login-page {
            width:
                100%;

            min-height:
                100vh;

            display:
                grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(420px, 520px);

            position:
                relative;

            z-index:
                1;

            padding:
                8px;
        }

        /* =========================================
           LEFT SHOWCASE
        ========================================= */

        .login-showcase {
            min-height:
                calc(100vh - 16px);

            position:
                relative;

            overflow:
                hidden;

            display:
                flex;

            flex-direction:
                column;

            justify-content:
                center;

            padding:
                55px 65px;

            border-radius:
                24px 0 0 24px;

            background:
                linear-gradient(
                    135deg,
                    rgba(220, 237, 255, 0.98) 0%,
                    rgba(237, 246, 255, 0.97) 38%,
                    rgba(249, 249, 255, 0.96) 66%,
                    rgba(255, 238, 247, 0.95) 100%
                );

            border:
                1px solid rgba(255, 255, 255, 0.95);

            box-shadow:
                inset 0 0 100px rgba(255, 255, 255, 0.42),
                inset 0 1px 0 rgba(255, 255, 255, 0.90);
        }

        .login-showcase::before {
            content: "";

            position:
                absolute;

            width:
                650px;

            height:
                650px;

            border-radius:
                50%;

            border:
                1px solid rgba(75, 125, 220, 0.12);

            left:
                -365px;

            top:
                50%;

            transform:
                translateY(-50%);

            box-shadow:
                0 0 0 42px rgba(75, 125, 220, 0.035),
                0 0 0 85px rgba(75, 125, 220, 0.025),
                0 0 0 135px rgba(75, 125, 220, 0.018);

            pointer-events:
                none;
        }

        .login-showcase::after {
            content: "";

            position:
                absolute;

            width:
                500px;

            height:
                500px;

            border-radius:
                50%;

            right:
                -250px;

            bottom:
                -300px;

            background:
                radial-gradient(
                    circle,
                    rgba(255, 154, 192, 0.22),
                    rgba(255, 196, 218, 0.10) 38%,
                    transparent 70%
                );

            pointer-events:
                none;
        }

        /* Decorative soft curves */

        .login-showcase {
            isolation:
                isolate;
        }

        .brand {
            position:
                relative;

            z-index:
                3;

            margin-bottom:
                38px;
        }

        /*
         * LOGO AREA
         */

        .brand-mark {
            width:
                105px;

            height:
                105px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            margin-bottom:
                20px;

            border-radius:
                50%;

            position:
                relative;

            overflow:
                hidden;

            background:
                rgba(255, 255, 255, 0.78);

            border:
                5px solid rgba(255, 255, 255, 0.92);

            box-shadow:
                0 16px 38px rgba(71, 107, 181, 0.16),
                0 0 0 9px rgba(255, 255, 255, 0.30),
                inset 0 1px 0 rgba(255, 255, 255, 0.95);

            backdrop-filter:
                blur(12px);

            -webkit-backdrop-filter:
                blur(12px);
        }

        .brand-mark::before {
            content: "";

            position:
                absolute;

            inset:
                7px;

            border-radius:
                50%;

            border:
                1px solid rgba(243, 91, 145, 0.20);

            z-index:
                1;

            pointer-events:
                none;
        }

        .brand-mark img {
            width:
                100%;

            height:
                100%;

            object-fit:
                contain;

            object-position:
                center;

            display:
                block;

            padding:
                5px;

            border-radius:
                50%;

            position:
                relative;

            z-index:
                2;
        }

        .brand h1 {
            color:
                var(--blue-dark);

            font-family:
                "Playfair Display",
                Georgia,
                serif;

            font-size:
                clamp(35px, 4vw, 58px);

            line-height:
                1.05;

            letter-spacing:
                0.5px;

            margin-bottom:
                13px;

            text-shadow:
                0 4px 15px rgba(64, 92, 161, 0.08);
        }

        .brand h1::first-letter {
            color:
                var(--pink);
        }

        .brand p {
            color:
                #66718a;

            font-size:
                13px;

            font-weight:
                600;

            letter-spacing:
                3.5px;

            text-transform:
                uppercase;
        }

        /* =========================================
           SHOWCASE CONTENT
        ========================================= */

        .showcase-content {
            position:
                relative;

            z-index:
                3;

            max-width:
                650px;
        }

        .showcase-content h2 {
            color:
                var(--pink);

            font-family:
                "Playfair Display",
                Georgia,
                serif;

            font-size:
                clamp(31px, 4vw, 52px);

            line-height:
                1.15;

            margin-bottom:
                16px;

            text-shadow:
                0 5px 18px rgba(243, 91, 145, 0.10);
        }

        .showcase-content > p {
            color:
                #4e5870;

            font-size:
                15px;

            line-height:
                1.8;

            max-width:
                530px;
        }

        /* =========================================
           ACHIEVEMENTS
        ========================================= */

        .achievement-list {
            display:
                flex;

            gap:
                17px;

            margin-top:
                34px;

            flex-wrap:
                wrap;
        }

        .achievement {
            min-width:
                145px;

            padding:
                16px 18px;

            border:
                1px solid rgba(255, 255, 255, 0.92);

            border-radius:
                17px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.91),
                    rgba(241, 247, 255, 0.76)
                );

            box-shadow:
                0 13px 30px rgba(76, 108, 175, 0.10),
                inset 0 1px 0 rgba(255, 255, 255, 0.95);

            backdrop-filter:
                blur(15px);

            -webkit-backdrop-filter:
                blur(15px);

            color:
                #46516a;

            position:
                relative;

            overflow:
                hidden;

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }

        .achievement::before {
            content: "";

            position:
                absolute;

            left:
                0;

            right:
                0;

            top:
                0;

            height:
                3px;

            background:
                linear-gradient(
                    90deg,
                    var(--pink),
                    #c477d0,
                    var(--blue)
                );

            opacity:
                0.85;
        }

        .achievement::after {
            content: "";

            position:
                absolute;

            width:
                75px;

            height:
                75px;

            border-radius:
                50%;

            right:
                -38px;

            bottom:
                -42px;

            background:
                radial-gradient(
                    circle,
                    rgba(255, 174, 202, 0.16),
                    transparent 70%
                );

            pointer-events:
                none;
        }

        .achievement:hover {
            transform:
                translateY(-5px);

            box-shadow:
                0 20px 38px rgba(70, 101, 175, 0.15);

            border-color:
                rgba(243, 91, 145, 0.28);
        }

        .achievement i {
            color:
                var(--pink);

            font-size:
                18px;

            margin-bottom:
                9px;

            display:
                block;
        }

        .achievement strong {
            display:
                block;

            color:
                #3c4760;

            font-size:
                12px;

            line-height:
                1.45;

            font-weight:
                700;
        }

        /* =========================================
           RIGHT LOGIN AREA
        ========================================= */

        .login-area {
            min-height:
                calc(100vh - 16px);

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            padding:
                45px 35px;

            position:
                relative;

            overflow:
                hidden;

            border-radius:
                0 24px 24px 0;

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.99),
                    rgba(255, 251, 253, 0.98)
                );

            border:
                1px solid rgba(255, 255, 255, 0.95);

            box-shadow:
                -18px 0 55px rgba(84, 102, 160, 0.045);
        }

        .login-area::before {
            content: "";

            position:
                absolute;

            width:
                450px;

            height:
                450px;

            border-radius:
                50%;

            background:
                radial-gradient(
                    circle,
                    rgba(255, 170, 202, 0.20),
                    rgba(255, 207, 224, 0.08) 45%,
                    transparent 70%
                );

            right:
                -235px;

            top:
                -190px;

            pointer-events:
                none;
        }

        .login-area::after {
            content: "";

            position:
                absolute;

            width:
                390px;

            height:
                390px;

            border-radius:
                50%;

            background:
                radial-gradient(
                    circle,
                    rgba(111, 166, 255, 0.13),
                    rgba(185, 213, 255, 0.05) 45%,
                    transparent 70%
                );

            left:
                -225px;

            bottom:
                -205px;

            pointer-events:
                none;
        }

        /* =========================================
           LOGIN CARD
        ========================================= */

        .login-card {
            width:
                100%;

            max-width:
                420px;

            padding:
                42px 40px 34px;

            position:
                relative;

            overflow:
                hidden;

            z-index:
                2;

            border:
                1px solid rgba(215, 224, 239, 0.95);

            border-radius:
                26px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.96),
                    rgba(255, 252, 254, 0.93)
                );

            box-shadow:
                0 32px 75px rgba(71, 93, 150, 0.14),
                0 12px 28px rgba(243, 91, 145, 0.045),
                inset 0 1px 0 rgba(255, 255, 255, 0.98);

            backdrop-filter:
                blur(20px);

            -webkit-backdrop-filter:
                blur(20px);
        }

        .login-card::before {
            content: "";

            position:
                absolute;

            left:
                0;

            right:
                0;

            top:
                0;

            height:
                5px;

            background:
                linear-gradient(
                    90deg,
                    var(--pink),
                    #ca6bc1,
                    var(--blue)
                );
        }

        .login-card::after {
            content: "";

            position:
                absolute;

            width:
                230px;

            height:
                230px;

            border-radius:
                50%;

            background:
                radial-gradient(
                    circle,
                    rgba(255, 174, 202, 0.11),
                    transparent 70%
                );

            top:
                -115px;

            right:
                -115px;

            pointer-events:
                none;
        }

        /* =========================================
           LOGIN HEADING
        ========================================= */

        .login-heading {
            position:
                relative;

            z-index:
                3;

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
                var(--blue-dark);

            margin-bottom:
                8px;

            letter-spacing:
                0.2px;
        }

        .login-heading p {
            color:
                var(--muted);

            font-size:
                13px;

            font-weight:
                500;
        }

        /* =========================================
           ERROR
        ========================================= */

        .error-message {
            position:
                relative;

            z-index:
                3;

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
                1px solid #ffcbd9;

            background:
                #fff3f7;

            color:
                #c13c62;

            border-radius:
                11px;

            font-size:
                12px;

            line-height:
                1.5;

            box-shadow:
                0 7px 18px rgba(239, 94, 139, 0.07);
        }

        .error-message i {
            color:
                var(--pink);
        }

        /* =========================================
           FORM
        ========================================= */

        .login-form {
            position:
                relative;

            z-index:
                3;
        }

        .form-group {
            margin-bottom:
                20px;
        }

        .form-group label {
            display:
                block;

            color:
                #39445d;

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
                #9ba7bb;

            font-size:
                13px;

            pointer-events:
                none;

            transition:
                color 0.22s ease;
        }

        .input-wrapper input {
            width:
                100%;

            height:
                50px;

            padding:
                0 15px 0 43px;

            border:
                1px solid var(--border);

            border-radius:
                12px;

            outline:
                none;

            background:
                #fcfdff;

            color:
                var(--text);

            font-family:
                Inter,
                Arial,
                sans-serif;

            font-size:
                13px;

            transition:
                border-color 0.22s ease,
                box-shadow 0.22s ease,
                background 0.22s ease,
                transform 0.22s ease;
        }

        .input-wrapper input:hover {
            border-color:
                #c7d4eb;

            background:
                #ffffff;
        }

        .input-wrapper input:focus {
            border-color:
                var(--blue);

            background:
                #ffffff;

            box-shadow:
                0 0 0 4px rgba(73, 122, 221, 0.10);

            transform:
                translateY(-1px);
        }

        .input-wrapper:focus-within > i {
            color:
                var(--blue);
        }

        .form-group:nth-of-type(2) {
            margin-bottom:
                25px;
        }

        /* =========================================
           LOGIN BUTTON
        ========================================= */

        .login-button {
            width:
                100%;

            height:
                50px;

            border:
                none;

            border-radius:
                12px;

            background:
                linear-gradient(
                    100deg,
                    #f45188 0%,
                    #c85eb2 48%,
                    #4e83e5 100%
                );

            color:
                #ffffff;

            font-family:
                Inter,
                Arial,
                sans-serif;

            font-size:
                13px;

            font-weight:
                700;

            letter-spacing:
                0.3px;

            cursor:
                pointer;

            box-shadow:
                0 12px 25px rgba(94, 107, 204, 0.22);

            transition:
                transform 0.22s ease,
                box-shadow 0.22s ease,
                filter 0.22s ease;
        }

        .login-button:hover {
            transform:
                translateY(-2px);

            box-shadow:
                0 17px 32px rgba(82, 108, 204, 0.28);

            filter:
                brightness(1.03);
        }

        .login-button:active {
            transform:
                translateY(0);

            box-shadow:
                0 8px 18px rgba(82, 108, 204, 0.20);
        }

        .login-button i {
            margin-right:
                7px;
        }

        /* =========================================
           TRUST SECTION
        ========================================= */

        .trust-section {
            margin-top:
                28px;

            padding-top:
                23px;

            border-top:
                1px solid #e8edf5;

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
                #7b8497;

            font-size:
                9px;

            line-height:
                1.4;
        }

        .trust-item i {
            width:
                38px;

            height:
                38px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            margin:
                0 auto 7px;

            border-radius:
                50%;

            color:
                var(--pink);

            font-size:
                16px;

            background:
                linear-gradient(
                    145deg,
                    #fff0f5,
                    #eef5ff
                );

            box-shadow:
                0 7px 16px rgba(92, 114, 175, 0.08);

            border:
                1px solid rgba(255, 255, 255, 0.95);
        }

        .trust-item:nth-child(2) i {
            color:
                var(--blue);
        }

        .trust-item:nth-child(3) i {
            color:
                #e7a32b;
        }

        .trust-item strong {
            display:
                block;

            color:
                #59647a;

            font-size:
                9px;

            font-weight:
                700;
        }

        .copyright {
            text-align:
                center;

            color:
                #a1a9b8;

            font-size:
                10px;

            margin-top:
                23px;
        }

        /* =========================================
           TABLET
        ========================================= */

        @media (max-width: 1100px) {

            .login-page {
                grid-template-columns:
                    minmax(0, 1fr)
                    minmax(390px, 450px);
            }

            .login-showcase {
                padding:
                    45px;
            }

        }

        /* =========================================
           MOBILE / TABLET
        ========================================= */

        @media (max-width: 950px) {

            .login-page {
                grid-template-columns:
                    1fr;

                padding:
                    7px;
            }

            .login-showcase {
                min-height:
                    auto;

                padding:
                    55px 35px 45px;

                text-align:
                    center;

                align-items:
                    center;

                border-radius:
                    22px 22px 0 0;
            }

            .brand-mark {
                margin-left:
                    auto;

                margin-right:
                    auto;
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
                    45px 20px 55px;

                border-radius:
                    0 0 22px 22px;
            }

        }

        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 600px) {

            body {
                background:
                    linear-gradient(
                        145deg,
                        #edf6ff,
                        #fff7fb
                    );
            }

            .login-page {
                padding:
                    6px;
            }

            .login-showcase {
                padding:
                    42px 20px 35px;

                border-radius:
                    18px 18px 0 0;
            }

            .brand {
                margin-bottom:
                    28px;
            }

            .brand-mark {
                width:
                    82px;

                height:
                    82px;

                font-size:
                    31px;

                margin-bottom:
                    15px;

                border-width:
                    4px;

                box-shadow:
                    0 12px 28px rgba(71, 107, 181, 0.14),
                    0 0 0 7px rgba(255, 255, 255, 0.28);
            }

            .brand-mark img {
                padding:
                    4px;
            }

            .brand h1 {
                font-size:
                    35px;
            }

            .brand p {
                font-size:
                    9px;

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

                line-height:
                    1.7;
            }

            .achievement-list {
                width:
                    100%;

                gap:
                    10px;

                margin-top:
                    28px;
            }

            .achievement {
                flex:
                    1;

                min-width:
                    130px;

                padding:
                    14px 12px;
            }

            .login-area {
                padding:
                    25px 14px 38px;

                border-radius:
                    0 0 18px 18px;
            }

            .login-card {
                max-width:
                    430px;

                padding:
                    35px 22px 28px;

                border-radius:
                    20px;
            }

            .login-heading {
                margin-bottom:
                    27px;
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

        /* =========================================
           SMALL MOBILE
        ========================================= */

        @media (max-width: 400px) {

            .login-showcase {
                padding:
                    36px 16px 30px;
            }

            .brand-mark {
                width:
                    76px;

                height:
                    76px;
            }

            .brand h1 {
                font-size:
                    30px;
            }

            .brand p {
                font-size:
                    8px;

                letter-spacing:
                    1.7px;
            }

            .showcase-content h2 {
                font-size:
                    27px;
            }

            .achievement-list {
                flex-direction:
                    column;
            }

            .achievement {
                width:
                    100%;

                min-width:
                    100%;

                text-align:
                    left;

                display:
                    flex;

                align-items:
                    center;

                gap:
                    12px;
            }

            .achievement i {
                margin:
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
                    <img src="/../ssrini-handicrafts-admin/assets/images/logo.png" alt="Logo">
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