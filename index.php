<?php
session_start();

$error = $_GET["error"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ElectroCore | Login</title>

    <style>
        /* ==============================
           RESET
        ============================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-main: #050b14;
            --bg-secondary: #081321;
            --card: rgba(12, 24, 40, 0.88);

            --blue: #12b8ff;
            --blue-dark: #0877ff;
            --blue-light: #67d5ff;

            --text: #f4f8fc;
            --text-secondary: #9aabbc;
            --text-muted: #63768a;

            --border: rgba(255, 255, 255, 0.08);
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background: var(--bg-main);

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
        }


        /* ==============================
           BACKGROUND
        ============================== */

        .background {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.18;
        }

        .glow-one {
            width: 450px;
            height: 450px;
            background: #008cff;
            top: -200px;
            left: -150px;
        }

        .glow-two {
            width: 400px;
            height: 400px;
            background: #0055ff;
            bottom: -220px;
            right: -120px;
        }

        .grid {
            position: absolute;
            inset: 0;

            background-image:
                linear-gradient(
                    rgba(255,255,255,0.025) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255,255,255,0.025) 1px,
                    transparent 1px
                );

            background-size: 55px 55px;

            mask-image: linear-gradient(
                to bottom,
                transparent,
                black 20%,
                black 80%,
                transparent
            );
        }


        /* ==============================
           MAIN WRAPPER
        ============================== */

        .page {
            position: relative;
            z-index: 2;

            width: 1080px;
            max-width: 92%;

            min-height: 650px;

            display: grid;
            grid-template-columns: 1.1fr 0.9fr;

            background: var(--card);

            border: 1px solid var(--border);

            border-radius: 28px;

            overflow: hidden;

            box-shadow:
                0 40px 100px rgba(0, 0, 0, 0.55),
                0 0 80px rgba(0, 132, 255, 0.07);

            backdrop-filter: blur(20px);
        }


        /* ==============================
           LEFT SIDE
        ============================== */

        .brand-panel {
            position: relative;

            padding: 65px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            background:
                linear-gradient(
                    145deg,
                    rgba(8, 41, 70, 0.85),
                    rgba(5, 14, 25, 0.55)
                );

            border-right: 1px solid var(--border);

            overflow: hidden;
        }

        /* Decorative circle */

        .brand-panel::before {
            content: "";

            position: absolute;

            width: 500px;
            height: 500px;

            border: 1px solid rgba(18, 184, 255, 0.08);

            border-radius: 50%;

            right: -260px;
            bottom: -220px;
        }

        .brand-panel::after {
            content: "";

            position: absolute;

            width: 300px;
            height: 300px;

            border: 1px solid rgba(18, 184, 255, 0.06);

            border-radius: 50%;

            right: -150px;
            bottom: -120px;
        }


        /* ==============================
           LOGO
        ============================== */

        .brand {
            position: relative;
            z-index: 2;

            display: flex;
            align-items: center;
            gap: 14px;

            margin-bottom: 55px;
        }

        .logo {
            width: 52px;
            height: 52px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 14px;

            background:
                linear-gradient(
                    135deg,
                    var(--blue),
                    var(--blue-dark)
                );

            box-shadow:
                0 10px 35px rgba(0, 150, 255, 0.28);

            font-size: 27px;

            color: white;
        }

        .brand-name {
            font-size: 21px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .brand-name span {
            color: var(--blue);
        }


        /* ==============================
           HERO TEXT
        ============================== */

        .hero {
            position: relative;
            z-index: 2;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            margin-bottom: 20px;

            color: var(--blue-light);

            font-size: 12px;
            font-weight: 600;

            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .status-dot {
            width: 7px;
            height: 7px;

            background: var(--blue);

            border-radius: 50%;

            box-shadow:
                0 0 12px var(--blue);
        }

        .hero h1 {
            max-width: 520px;

            font-size: 53px;
            line-height: 1.08;

            letter-spacing: -2px;

            margin-bottom: 24px;
        }

        .hero h1 span {
            color: var(--blue);
        }

        .hero-description {
            max-width: 450px;

            color: var(--text-secondary);

            font-size: 15px;
            line-height: 1.8;

            margin-bottom: 45px;
        }


        /* ==============================
           FEATURES
        ============================== */

        .features {
            position: relative;
            z-index: 2;

            display: flex;
            gap: 30px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 10px;

            color: #b8c6d4;

            font-size: 12px;
        }

        .feature-icon {
            width: 28px;
            height: 28px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: rgba(18, 184, 255, 0.09);

            color: var(--blue);

            font-size: 12px;
        }


        /* ==============================
           RIGHT SIDE
        ============================== */

        .login-panel {
            padding: 65px 60px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            background:
                linear-gradient(
                    160deg,
                    rgba(10, 23, 39, 0.95),
                    rgba(6, 14, 25, 0.95)
                );
        }


        /* ==============================
           LOGIN HEADER
        ============================== */

        .login-header {
            margin-bottom: 38px;
        }

        .login-header h2 {
            font-size: 31px;
            font-weight: 650;

            margin-bottom: 10px;

            letter-spacing: -0.5px;
        }

        .login-header p {
            color: var(--text-secondary);

            font-size: 13px;

            line-height: 1.6;
        }


        /* ==============================
           ERROR
        ============================== */

        .error-message {
            padding: 13px 15px;

            margin-bottom: 22px;

            border-radius: 10px;

            background: rgba(255, 70, 70, 0.08);

            border: 1px solid rgba(255, 80, 80, 0.20);

            color: #ff9b9b;

            font-size: 12px;

            line-height: 1.5;
        }


        /* ==============================
           INPUTS
        ============================== */

        .input-group {
            margin-bottom: 22px;
        }

        .input-group label {
            display: block;

            margin-bottom: 9px;

            color: #c6d2de;

            font-size: 12px;

            font-weight: 600;

            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;

            left: 16px;
            top: 50%;

            transform: translateY(-50%);

            color: #62778c;

            font-size: 14px;

            pointer-events: none;

            transition: 0.2s ease;
        }

        .input-wrapper input {
            width: 100%;
            height: 54px;

            padding: 0 48px;

            border-radius: 11px;

            border: 1px solid rgba(255, 255, 255, 0.08);

            outline: none;

            background: rgba(2, 9, 18, 0.72);

            color: white;

            font-size: 13px;

            transition: 0.25s ease;
        }

        .input-wrapper input::placeholder {
            color: #506276;
        }

        .input-wrapper input:hover {
            border-color: rgba(255, 255, 255, 0.14);
        }

        .input-wrapper input:focus {
            border-color: var(--blue);

            box-shadow:
                0 0 0 3px rgba(18, 184, 255, 0.08);
        }

        .input-wrapper input:focus + .input-icon {
            color: var(--blue);
        }


        /* ==============================
           PASSWORD BUTTON
        ============================== */

        .password-toggle {
            position: absolute;

            right: 15px;
            top: 50%;

            transform: translateY(-50%);

            width: 28px;
            height: 28px;

            border: none;

            background: transparent;

            color: #607489;

            cursor: pointer;

            border-radius: 6px;

            transition: 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--blue);
            background: rgba(18, 184, 255, 0.08);
        }


        /* ==============================
           LOGIN BUTTON
        ============================== */

        .login-button {
            width: 100%;
            height: 54px;

            margin-top: 8px;

            border: none;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #12b8ff,
                    #0878ff
                );

            color: white;

            font-size: 13px;
            font-weight: 700;

            letter-spacing: 1px;

            cursor: pointer;

            box-shadow:
                0 12px 30px rgba(0, 120, 255, 0.20);

            transition: all 0.25s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);

            box-shadow:
                0 17px 38px rgba(0, 130, 255, 0.32);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button.loading {
            pointer-events: none;
            opacity: 0.75;
        }


        /* ==============================
           SECURITY TEXT
        ============================== */

        .security {
            margin-top: 28px;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            color: var(--text-muted);

            font-size: 11px;
        }

        .security-icon {
            color: var(--blue);

            font-size: 12px;
        }


        /* ==============================
           FOOTER
        ============================== */

        .footer {
            margin-top: 42px;

            text-align: center;

            color: #4f6378;

            font-size: 10px;
        }

        .footer span {
            color: #72869a;
        }


        /* ==============================
           RESPONSIVE
        ============================== */

        @media (max-width: 900px) {

            body {
                overflow: auto;
                padding: 30px 0;
            }

            .page {
                grid-template-columns: 1fr;

                min-height: auto;

                max-width: 94%;
            }

            .brand-panel {
                padding: 45px;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .login-panel {
                padding: 45px;
            }

            .hero h1 {
                font-size: 42px;
            }

            .brand {
                margin-bottom: 35px;
            }
        }


        @media (max-width: 550px) {

            .page {
                border-radius: 20px;
            }

            .brand-panel,
            .login-panel {
                padding: 32px 25px;
            }

            .hero h1 {
                font-size: 34px;
                letter-spacing: -1px;
            }

            .hero-description {
                font-size: 13px;
            }

            .features {
                flex-direction: column;
                gap: 12px;
            }

            .login-header h2 {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

    <!-- BACKGROUND -->

    <div class="background">

        <div class="glow glow-one"></div>
        <div class="glow glow-two"></div>

        <div class="grid"></div>

    </div>


    <!-- MAIN PAGE -->

    <main class="page">


        <!-- ==========================
             BRAND PANEL
        =========================== -->

        <section class="brand-panel">

            <div class="brand">

                <div class="logo">
                    &#9889;
                </div>

                <div class="brand-name">
                    ELECTRO<span>CORE</span>
                </div>

            </div>


            <div class="hero">

                <div class="hero-label">

                    <span class="status-dot"></span>

                    BUSINESS MANAGEMENT SYSTEM

                </div>


                <h1>
                    Powering your
                    <span>business</span>
                    smarter.
                </h1>


                <p class="hero-description">

                    A centralized electrical billing and inventory
                    management system built to simplify everyday
                    business operations.

                </p>


                <div class="features">

                    <div class="feature">

                        <div class="feature-icon">
                            &#10003;
                        </div>

                        <span>Billing</span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            &#10003;
                        </div>

                        <span>Inventory</span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            &#10003;
                        </div>

                        <span>Customers</span>

                    </div>

                </div>

            </div>

        </section>



        <!-- ==========================
             LOGIN PANEL
        =========================== -->

        <section class="login-panel">


            <div class="login-header">

                <h2>
                    Welcome back
                </h2>

                <p>
                    Sign in to continue to your ElectroCore dashboard.
                </p>

            </div>


            <!-- ERROR MESSAGE -->

            <?php if ($error === "empty"): ?>

                <div class="error-message">
                    Please enter your username and password.
                </div>

            <?php elseif ($error === "invalid"): ?>

                <div class="error-message">
                    Invalid username or password.
                </div>

            <?php elseif ($error === "inactive"): ?>

                <div class="error-message">
                    Your account is currently inactive.
                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
                method="POST"
                action="login.php"
                id="loginForm"
            >


                <!-- USERNAME -->

                <div class="input-group">

                    <label for="username">
                        Username
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            &#9670;
                        </span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>



                <!-- PASSWORD -->

                <div class="input-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            &#9670;
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >
                            Ã°Å¸â€˜Â
                        </button>

                    </div>

                </div>



                <!-- LOGIN -->

                <button
                    type="submit"
                    class="login-button"
                    id="loginButton"
                >
                    SIGN IN
                </button>


            </form>


            <!-- SECURITY -->

            <div class="security">

                <span class="security-icon">
                    &#128274;
                </span>

                Secure access to ElectroCore

            </div>


            <!-- FOOTER -->

            <div class="footer">

                Ã‚Â© <?php echo date("Y"); ?>

                <span>ElectroCore</span>

                Ã‚Â· Billing & Inventory Management System

            </div>


        </section>

    </main>



    <script>

        /* ==========================
           PASSWORD VISIBILITY
        =========================== */

        const password =
            document.getElementById("password");

        const passwordToggle =
            document.getElementById("passwordToggle");


        passwordToggle.addEventListener("click", function () {

            if (password.type === "password") {

                password.type = "text";

                passwordToggle.textContent = "Ã°Å¸â„¢Ë†";

            } else {

                password.type = "password";

                passwordToggle.textContent = "Ã°Å¸â€˜Â";

            }

        });



        /* ==========================
           LOGIN BUTTON
        =========================== */

        const loginForm =
            document.getElementById("loginForm");

        const loginButton =
            document.getElementById("loginButton");


        loginForm.addEventListener("submit", function () {

            loginButton.classList.add("loading");

            loginButton.textContent = "SIGNING IN...";

        });

    </script>

</body>
</html>