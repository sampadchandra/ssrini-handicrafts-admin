<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

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

        if (
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

    <title>Admin Login | SsRini Handicrafts</title>


    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


<style>

/* =========================================================
   SsRini Handicrafts
   ADMIN LOGIN PAGE
   LIGHT BLUE + LIGHT PINK PREMIUM DESIGN
   ========================================================= */


/* =========================================================
   RESET
   ========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================================================
   VARIABLES
   ========================================================= */

:root {

    --blue-light: #eaf4ff;
    --blue-soft: #d8eaff;
    --blue-main: #6f9fe8;
    --blue-dark: #315b9e;

    --pink-light: #fff1f7;
    --pink-soft: #ffddea;
    --pink-main: #e789ae;
    --pink-dark: #d76591;

    --white: #ffffff;

    --text: #303746;
    --muted: #858c9c;

    --border: #dfe6f0;

    --shadow-blue: rgba(76, 116, 170, 0.13);
    --shadow-pink: rgba(215, 101, 145, 0.12);
}


/* =========================================================
   HTML + BODY
   ========================================================= */

html,
body {
    width: 100%;
    min-height: 100%;
}

body {

    min-height: 100vh;

    font-family:
        "Inter",
        Arial,
        sans-serif;

    color: var(--text);

    background:
        linear-gradient(
            135deg,
            #dcecff 0%,
            #edf6ff 38%,
            #fffafd 68%,
            #ffeaf3 100%
        );

    overflow-x: hidden;

    position: relative;
}


/* =========================================================
   BACKGROUND GLOW
   ========================================================= */

body::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

    background:

        radial-gradient(
            circle at 7% 25%,
            rgba(111, 159, 232, 0.14),
            transparent 25%
        ),

        radial-gradient(
            circle at 92% 72%,
            rgba(231, 137, 174, 0.14),
            transparent 26%
        );

    z-index: 0;
}


/* =========================================================
   MAIN PAGE
   ========================================================= */

.login-page {

    width: 100%;

    min-height: 100vh;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(430px, 520px);

    position: relative;

    z-index: 1;
}


/* =========================================================
   LEFT SHOWCASE
   ========================================================= */

.login-showcase {

    min-height: 100vh;

    padding: 55px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    align-items: center;

    text-align: center;

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #cfe5ff 0%,
            #dcecff 40%,
            #eef6ff 68%,
            #fdf0f8 100%
        );
}


/* =========================================================
   LARGE DECORATIVE CIRCLE
   ========================================================= */

.login-showcase::before {

    content: "";

    position: absolute;

    width: 640px;
    height: 640px;

    border-radius: 50%;

    border:
        1px solid rgba(255, 255, 255, 0.78);

    left: -325px;
    top: 50%;

    transform: translateY(-50%);

    box-shadow:

        0 0 0 35px
        rgba(255, 255, 255, 0.20),

        0 0 0 75px
        rgba(255, 255, 255, 0.11);

    pointer-events: none;
}


/* =========================================================
   BOTTOM PINK GLOW
   ========================================================= */

.login-showcase::after {

    content: "";

    position: absolute;

    width: 500px;
    height: 500px;

    border-radius: 50%;

    right: -280px;
    bottom: -300px;

    background:
        radial-gradient(
            circle,
            rgba(255, 190, 215, 0.38),
            rgba(255, 220, 235, 0.10) 60%,
            transparent 72%
        );

    border:
        1px solid rgba(255, 255, 255, 0.55);

    pointer-events: none;
}


/* =========================================================
   BRAND
   ========================================================= */

.brand {

    position: relative;

    z-index: 3;

    display: flex;

    flex-direction: column;

    align-items: center;

    margin-bottom: 25px;
}


/* =========================================================
   LOGO FRAME
   ========================================================= */

.brand-mark {

    width: 92px;
    height: 92px;

    padding: 6px;

    margin-bottom: 17px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    position: relative;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            #f8fbff
        );

    border:
        2px solid rgba(111, 159, 232, 0.55);

    box-shadow:

        0 14px 30px
        rgba(65, 99, 150, 0.16),

        0 0 0 7px
        rgba(255, 255, 255, 0.40);
}


/* Inner frame */

.brand-mark::before {

    content: "";

    position: absolute;

    inset: 5px;

    border-radius: 50%;

    border:
        2px solid rgba(231, 137, 174, 0.55);

    pointer-events: none;

    z-index: 2;
}


/* Decorative dots */

.brand-mark::after {

    content: "";

    position: absolute;

    width: 10px;
    height: 10px;

    border-radius: 50%;

    top: 0;
    right: 13px;

    background: var(--pink-main);

    box-shadow:
        -63px 60px 0 -2px var(--blue-main);

    z-index: 4;
}


/* Logo */

.brand-mark img {

    width: 100%;
    height: 100%;

    display: block;

    object-fit: contain;

    object-position: center;

    border-radius: 50%;

    position: relative;

    z-index: 3;
}


/* =========================================================
   BRAND TITLE
   ========================================================= */

.brand h1 {

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size:
        clamp(36px, 4vw, 58px);

    line-height: 1.05;

    letter-spacing: 0.3px;

    color: var(--blue-dark);

    margin-bottom: 10px;

    text-shadow:
        0 4px 15px
        rgba(49, 91, 158, 0.10);
}


/* Pink first letter */

.brand h1::first-letter {

    color: var(--pink-dark);
}


/* Subtitle */

.brand p {

    color: #52627b;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: 3px;

    text-transform: uppercase;
}


.brand p::before,
.brand p::after {

    content: "✦";

    color: var(--pink-main);

    margin: 0 9px;

    font-size: 10px;
}


/* =========================================================
   SHOWCASE CONTENT
   ========================================================= */

.showcase-content {

    width: 100%;

    max-width: 690px;

    position: relative;

    z-index: 3;
}


.showcase-content h2 {

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size:
        clamp(31px, 4vw, 48px);

    line-height: 1.15;

    color: var(--pink-dark);

    margin-bottom: 12px;

    text-shadow:
        0 4px 16px
        rgba(215, 101, 145, 0.12);
}


.showcase-content > p {

    max-width: 550px;

    margin: 0 auto;

    color: #4d5870;

    font-size: 14px;

    line-height: 1.7;
}


/* =========================================================
   AWARD LIST
   ========================================================= */

.achievement-list {

    width: 100%;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 17px;

    margin-top: 29px;
}


/* =========================================================
   AWARD CARD
   ========================================================= */

.achievement {

    min-width: 0;

    min-height: 170px;

    padding: 12px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: space-between;

    text-align: center;

    position: relative;

    overflow: hidden;

    background:
        rgba(255, 255, 255, 0.82);

    border:
        1px solid rgba(255, 255, 255, 0.90);

    border-radius: 18px;

    box-shadow:

        0 15px 32px
        var(--shadow-blue),

        0 5px 15px
        rgba(255, 255, 255, 0.45);

    backdrop-filter: blur(12px);

    -webkit-backdrop-filter: blur(12px);

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;
}


/* Card hover */

.achievement:hover {

    transform: translateY(-5px);

    box-shadow:

        0 20px 38px
        rgba(70, 95, 135, 0.16),

        0 5px 20px
        rgba(231, 137, 174, 0.08);
}


/* =========================================================
   AWARD IMAGE BOX
   ========================================================= */

.achievement-image {

    width: 100%;

    height: 108px;

    padding: 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            #f7fbff
        );

    border:
        1px solid #dce5f0;

    box-shadow:
        inset 0 0 18px
        rgba(111, 159, 232, 0.06);

    overflow: hidden;
}


/* Actual award image */

.achievement-image img {

    width: 100%;
    height: 100%;

    display: block;

    object-fit: contain;

    object-position: center;

    border-radius: 8px;
}


/* =========================================================
   AWARD TEXT
   ========================================================= */

.achievement strong {

    display: block;

    width: 100%;

    margin-top: 10px;

    color: #354052;

    font-size: 11px;

    line-height: 1.35;

    font-weight: 800;
}


.achievement strong::before {

    content: "✦";

    color: var(--pink-main);

    margin-right: 6px;
}


/* Hide old icons */

.achievement > i {

    display: none;
}


/* =========================================================
   RIGHT LOGIN AREA
   ========================================================= */

.login-area {

    min-height: 100vh;

    padding: 45px 35px;

    display: flex;

    align-items: center;

    justify-content: center;

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            145deg,
            rgba(255, 252, 254, 0.96),
            rgba(255, 255, 255, 0.98)
        );

    border-left:
        1px solid rgba(255, 255, 255, 0.90);
}


/* Pink glow */

.login-area::before {

    content: "";

    position: absolute;

    width: 480px;
    height: 480px;

    border-radius: 50%;

    top: -200px;
    right: -210px;

    background:
        radial-gradient(
            circle,
            rgba(255, 185, 211, 0.20),
            transparent 68%
        );

    pointer-events: none;
}


/* Blue glow */

.login-area::after {

    content: "";

    position: absolute;

    width: 380px;
    height: 380px;

    border-radius: 50%;

    bottom: -190px;
    left: -180px;

    background:
        radial-gradient(
            circle,
            rgba(169, 205, 255, 0.20),
            transparent 68%
        );

    pointer-events: none;
}


/* =========================================================
   LOGIN CARD
   ========================================================= */

.login-card {

    width: 100%;

    max-width: 410px;

    padding:
        42px 40px 34px;

    position: relative;

    z-index: 3;

    overflow: hidden;

    background:
        rgba(255, 255, 255, 0.96);

    border:
        1px solid rgba(218, 225, 237, 0.85);

    border-radius: 25px;

    box-shadow:

        0 30px 70px
        rgba(67, 83, 117, 0.13),

        0 8px 25px
        rgba(231, 137, 174, 0.07);
}


/* =========================================================
   CARD TOP LINE
   ========================================================= */

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
            #719fe7 0%,
            #a49ee2 48%,
            #e789ae 100%
        );
}


/* Card glow */

.login-card::after {

    content: "";

    position: absolute;

    width: 200px;
    height: 200px;

    border-radius: 50%;

    top: -105px;
    right: -105px;

    background:
        radial-gradient(
            circle,
            rgba(231, 137, 174, 0.08),
            transparent 70%
        );

    pointer-events: none;
}


/* =========================================================
   LOGIN HEADING
   ========================================================= */

.login-heading {

    text-align: center;

    position: relative;

    z-index: 2;

    margin-bottom: 28px;
}


.login-heading h2 {

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size: 30px;

    color: var(--blue-dark);

    margin-bottom: 7px;

    font-weight: 700;
}


.login-heading p {

    color: var(--muted);

    font-size: 12px;

    font-weight: 500;
}


/* =========================================================
   ERROR
   ========================================================= */

.error-message {

    position: relative;

    z-index: 3;

    display: flex;

    align-items: center;

    gap: 9px;

    padding: 12px 14px;

    margin-bottom: 19px;

    border-radius: 11px;

    border:
        1px solid #f0c5d6;

    background: #fff3f7;

    color: #b94d70;

    font-size: 12px;

    line-height: 1.5;
}


.error-message i {

    color: var(--pink-dark);
}


/* =========================================================
   FORM
   ========================================================= */

.login-form {

    position: relative;

    z-index: 3;
}


.form-group {

    margin-bottom: 20px;
}


.form-group label {

    display: block;

    margin-bottom: 8px;

    color: #41495a;

    font-size: 12px;

    font-weight: 800;
}


/* =========================================================
   INPUT WRAPPER
   ========================================================= */

.input-wrapper {

    position: relative;
}


/* Input icon */

.input-wrapper > i {

    position: absolute;

    left: 15px;

    top: 50%;

    transform: translateY(-50%);

    color: #a0a9b9;

    font-size: 13px;

    z-index: 2;

    pointer-events: none;

    transition: color 0.2s ease;
}


/* Input */

.input-wrapper input {

    width: 100%;

    height: 50px;

    padding:
        0 15px 0 43px;

    border:
        1px solid #dce3ed;

    border-radius: 12px;

    outline: none;

    background: #ffffff;

    color: var(--text);

    font-family: "Inter", sans-serif;

    font-size: 13px;

    box-shadow:
        0 2px 8px
        rgba(82, 91, 125, 0.03);

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        transform 0.2s ease;
}


/* Input hover */

.input-wrapper input:hover {

    border-color: #c6d2e3;
}


/* Input focus */

.input-wrapper input:focus {

    border-color: var(--pink-main);

    box-shadow:
        0 0 0 4px
        rgba(231, 137, 174, 0.10);
}


/* Icon focus */

.input-wrapper:focus-within > i {

    color: var(--pink-main);
}


/* Password bottom spacing */

.form-group:nth-of-type(2) {

    margin-bottom: 24px;
}


/* =========================================================
   LOGIN BUTTON
   ========================================================= */

.login-button {

    width: 100%;

    height: 53px;

    border: none;

    border-radius: 13px;

    position: relative;

    overflow: hidden;

    cursor: pointer;

    background:
        linear-gradient(
            100deg,
            #6e9ee7 0%,
            #929fe0 48%,
            #e486aa 100%
        );

    color: #ffffff;

    font-family:
        "Inter",
        Arial,
        sans-serif;

    font-size: 13px;

    font-weight: 800;

    letter-spacing: 0.4px;

    box-shadow:

        0 12px 25px
        rgba(111, 159, 232, 0.20),

        0 7px 17px
        rgba(231, 137, 174, 0.11);

    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease,
        filter 0.22s ease;
}


/* Button shine */

.login-button::before {

    content: "";

    position: absolute;

    top: 0;
    left: -100%;

    width: 60%;
    height: 100%;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,0.24),
            transparent
        );

    transform: skewX(-20deg);

    transition: left 0.55s ease;
}


/* Button hover */

.login-button:hover {

    transform: translateY(-2px);

    filter: brightness(1.03);

    box-shadow:

        0 16px 31px
        rgba(111, 159, 232, 0.25),

        0 9px 20px
        rgba(231, 137, 174, 0.15);
}


.login-button:hover::before {

    left: 130%;
}


/* Button click */

.login-button:active {

    transform: translateY(0);

    box-shadow:
        0 7px 15px
        rgba(111, 159, 232, 0.18);
}


/* Button icon */

.login-button i {

    margin-right: 7px;

    font-size: 12px;
}


/* =========================================================
   TRUST SECTION
   ========================================================= */

.trust-section {

    margin-top: 27px;

    padding-top: 22px;

    border-top:
        1px solid #eee9ee;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 8px;

    text-align: center;
}


/* Trust item */

.trust-item {

    color: #777e8e;

    font-size: 9px;

    line-height: 1.4;
}


/* Trust icon */

.trust-item i {

    width: 39px;
    height: 39px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 7px;

    border-radius: 50%;

    background:
        linear-gradient(
            145deg,
            #e8f3ff,
            #f8fbff
        );

    color: var(--blue-main);

    font-size: 15px;

    box-shadow:
        0 6px 14px
        rgba(111, 159, 232, 0.09);
}


/* Second icon */

.trust-item:nth-child(2) i {

    background:
        linear-gradient(
            145deg,
            #ffe8f1,
            #fff8fb
        );

    color: var(--pink-main);
}


/* Third icon */

.trust-item:nth-child(3) i {

    background:
        linear-gradient(
            145deg,
            #e8f3ff,
            #f8fbff
        );

    color: #668fd1;
}


/* Trust title */

.trust-item strong {

    display: block;

    color: #62697a;

    font-size: 9px;

    font-weight: 700;
}


/* =========================================================
   COPYRIGHT
   ========================================================= */

.copyright {

    text-align: center;

    margin-top: 22px;

    color: #9a9fac;

    font-size: 10px;
}


/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 950px) {

    .login-page {

        grid-template-columns: 1fr;
    }


    .login-showcase {

        min-height: auto;

        padding:
            55px 35px 45px;
    }


    .login-area {

        min-height: auto;

        padding:
            45px 25px 55px;

        border-left: none;
    }
}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 600px) {

    .login-showcase {

        padding:
            40px 20px 35px;
    }


    .brand {

        margin-bottom: 25px;
    }


    .brand-mark {

        width: 84px;
        height: 84px;

        margin-bottom: 13px;
    }


    .brand h1 {

        font-size: 35px;
    }


    .brand p {

        font-size: 9px;

        letter-spacing: 2px;
    }


    .showcase-content h2 {

        font-size: 31px;
    }


    .showcase-content > p {

        font-size: 13px;
    }


    .achievement-list {

        gap: 9px;
    }


    .achievement {

        min-height: 145px;

        padding: 9px;
    }


    .achievement-image {

        height: 82px;
    }


    .achievement strong {

        font-size: 9px;
    }


    .login-area {

        padding:
            25px 15px 40px;
    }


    .login-card {

        padding:
            35px 22px 27px;

        border-radius: 20px;
    }


    .login-heading h2 {

        font-size: 28px;
    }
}


/* =========================================================
   SMALL MOBILE
   ========================================================= */

@media (max-width: 400px) {

    .brand h1 {

        font-size: 31px;
    }


    .showcase-content h2 {

        font-size: 27px;
    }


    .achievement-list {

        grid-template-columns: 1fr;
    }


    .achievement {

        min-height: 105px;

        display: grid;

        grid-template-columns: 90px 1fr;

        align-items: center;

        text-align: left;

        gap: 10px;
    }


    .achievement-image {

        width: 90px;

        height: 80px;

        margin: 0;
    }


    .achievement strong {

        font-size: 11px;

        margin-top: 0;
    }


    .login-card {

        padding:
            32px 18px 25px;
    }


    .trust-item {

        font-size: 8px;
    }


    .trust-item strong {

        font-size: 8px;
    }
}

</style>

</head>


<body>


<div class="login-page">


    <!-- =====================================================
         LEFT SHOWCASE
         ===================================================== -->

    <section class="login-showcase">


        <!-- BRAND -->

        <div class="brand">


            <div class="brand-mark">

                <img
                    src="../assets/images/logo.jpg"
                    alt="SsRini Handicrafts Logo"
                >

            </div>


            <h1>
                SsRini Handicrafts
            </h1>


            <p>
                Excellence in Handicrafts
            </p>


        </div>



        <!-- SHOWCASE CONTENT -->

        <div class="showcase-content">


            <h2>
                Admin Login
            </h2>


            <p>
                Manage your store with confidence, quality and excellence.
            </p>



            <!-- =================================================
                 AWARDS
                 ================================================= -->

            <div class="achievement-list">


                <!-- IndiaMART -->

                <div class="achievement">

                    <div class="achievement-image">

                        <img
                            src="../assets/images/indiamart-award.png"
                            alt="IndiaMART TrustSEAL Award"
                        >

                    </div>


                    <strong>
                        IndiaMART TrustSEAL
                    </strong>

                </div>



                <!-- Award Winning -->

                <div class="achievement">

                    <div class="achievement-image">

                        <img
                            src="../assets/images/top-attendance-award.png"
                            alt="Award Winning"
                        >

                    </div>


                    <strong>
                        Award Winning
                    </strong>

                </div>



                <!-- Matrimaa -->

                <div class="achievement">

                    <div class="achievement-image">

                        <img
                            src="../assets/images/matrimaa-award.png"
                            alt="Top Attendance Award"
                        >

                    </div>


                    <strong>
                        Top Attendance
                    </strong>

                </div>


            </div>


        </div>


    </section>



    <!-- =====================================================
         RIGHT LOGIN AREA
         ===================================================== -->

    <section class="login-area">


        <div class="login-card">


            <!-- LOGIN HEADING -->

            <div class="login-heading">

                <h2>
                    SsRini Handicrafts
                </h2>

                <p>
                    Admin Login
                </p>

            </div>



            <!-- ERROR -->

            <?php if ($error !== ''): ?>

                <p class="error-message">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </p>

            <?php endif; ?>



            <!-- LOGIN FORM -->

            <form
                method="POST"
                action=""
                class="login-form"
            >


                <!-- EMAIL -->

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
                            placeholder=""
                        >

                    </div>

                </div>



                <!-- PASSWORD -->

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
                            required
                            autocomplete="current-password"
                            placeholder=""
                        >

                    </div>

                </div>



                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-button"
                >

                    <i class="fa-solid fa-lock"></i>

                    Login

                </button>


            </form>



            <!-- =================================================
                 TRUST
                 ================================================= -->

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



            <!-- COPYRIGHT -->

            <div class="copyright">

                SsRini Handicrafts

            </div>


        </div>


    </section>


</div>


</body>

</html>