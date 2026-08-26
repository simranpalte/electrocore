<?php
require_once __DIR__ . '/../config/app.php';

/*
|--------------------------------------------------------------------------
| ELECTROCORE SIDEBAR
|--------------------------------------------------------------------------
| Centralized sidebar navigation
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDirectory = basename(dirname($_SERVER['PHP_SELF']));

?>

<style>

/* =========================================================
   ELECTROCORE SIDEBAR
========================================================= */

.sidebar {

    width: 255px;
    min-width: 255px;
    height: 100vh;

    position: fixed;

    left: 0;
    top: 0;
    bottom: 0;

    display: flex;
    flex-direction: column;

    padding: 28px 16px 18px;

    background:
        linear-gradient(
            180deg,
            #071321 0%,
            #050d17 100%
        );

    border-right: 1px solid rgba(255,255,255,0.07);

    z-index: 9999;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

}


/* =========================================================
   BRAND
========================================================= */

.sidebar-brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 0 12px;

    margin-bottom: 38px;

}


.sidebar-logo {

    width: 40px;
    height: 40px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #12b8ff,
            #0878ff
        );

    box-shadow:
        0 8px 25px
        rgba(0,140,255,0.22);

    color: white;

    font-size: 21px;

}


.sidebar-name {

    color: #f3f7fb;

    font-size: 17px;

    font-weight: 750;

    letter-spacing: 1.4px;

}


.sidebar-name span {

    color: #12b8ff;

}


/* =========================================================
   NAVIGATION SECTION
========================================================= */

.nav-section {

    margin-bottom: 25px;

}


.nav-title {

    padding: 0 13px;

    margin-bottom: 10px;

    color: #50667c;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1.5px;

}


/* =========================================================
   NAV MENU
========================================================= */

.nav-menu {

    list-style: none;

    margin: 0;
    padding: 0;

    display: flex;

    flex-direction: column;

    gap: 4px;

}


.nav-menu li {

    position: relative;

    list-style: none;

    margin: 0;
    padding: 0;

}


.nav-menu li a {

    height: 45px;

    width: 100%;

    padding: 0 13px;

    display: flex;

    align-items: center;

    gap: 13px;

    border-radius: 9px;

    color: #8093a7;

    background: transparent;

    text-decoration: none;

    font-size: 13px;

    font-weight: 500;

    transition:
        background 0.2s ease,
        color 0.2s ease;

}


.nav-menu li a:hover {

    color: #dce8f3;

    background:
        rgba(255,255,255,0.035);

}


/* =========================================================
   ACTIVE ITEM
========================================================= */

.nav-menu li.active a {

    color: #ffffff;

    background:
        linear-gradient(
            90deg,
            rgba(18,184,255,0.15),
            rgba(18,184,255,0.04)
        );

}


.nav-menu li.active::before {

    content: "";

    position: absolute;

    left: -16px;

    top: 8px;

    bottom: 8px;

    width: 3px;

    border-radius:
        0 4px 4px 0;

    background: #12b8ff;

    box-shadow:
        0 0 12px
        rgba(18,184,255,0.7);

}


/* =========================================================
   ICON
========================================================= */

.nav-icon {

    width: 21px;

    min-width: 21px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    text-align: center;

    color: #657b90;

    font-size: 14px;

}


.nav-menu li.active .nav-icon {

    color: #12b8ff;

}


/* =========================================================
   SIDEBAR BOTTOM
========================================================= */

.sidebar-bottom {

    margin-top: auto;

}


/* =========================================================
   SYSTEM STATUS
========================================================= */

.system-status {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 12px;

    margin-bottom: 10px;

    border-radius: 10px;

    background:
        rgba(255,255,255,0.025);

    border:
        1px solid
        rgba(255,255,255,0.04);

}


.status-light {

    width: 7px;
    height: 7px;

    flex-shrink: 0;

    border-radius: 50%;

    background: #26d68a;

    box-shadow:
        0 0 10px
        rgba(38,214,138,0.7);

}


.system-status div {

    display: flex;

    flex-direction: column;

    gap: 2px;

}


.system-status strong {

    color: #aab9c8;

    font-size: 10px;

}


.system-status small {

    color: #53677b;

    font-size: 8px;

}


/* =========================================================
   LOGOUT
========================================================= */

.logout-button {

    height: 43px;

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    border-radius: 9px;

    color: #8093a6;

    background: transparent;

    text-decoration: none;

    font-size: 11px;

    border:
        1px solid
        rgba(255,255,255,0.06);

    transition:
        color 0.2s ease,
        background 0.2s ease,
        border-color 0.2s ease;

}


.logout-button:hover {

    color: #ff8585;

    border-color:
        rgba(255,80,80,0.18);

    background:
        rgba(255,60,60,0.05);

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 750px) {

    .sidebar {

        position: relative;

        width: 100%;
        min-width: 100%;

        height: auto;
        min-height: auto;

    }

}

</style>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


    <!-- =====================================================
         BRAND
    ====================================================== -->

    <div class="sidebar-brand">

        <div class="sidebar-logo">
            ⚡
        </div>

        <div class="sidebar-name">
            ELECTRO<span>CORE</span>
        </div>

    </div>


    <!-- =====================================================
         MAIN NAVIGATION
    ====================================================== -->

    <div class="nav-section">

        <div class="nav-title">
            MAIN
        </div>


        <ul class="nav-menu">


            <!-- DASHBOARD -->

            <li class="<?= (
                $currentPage === 'dashboard.php' ||
                (
                    $currentPage === 'index.php' &&
                    $currentDirectory === 'electrocore'
                )
            ) ? 'active' : '' ?>">

                <a href="<?= BASE_URL ?>/dashboard.php">

                    <span class="nav-icon">
                        ◈
                    </span>

                    <span>
                        Dashboard
                    </span>

                </a>

            </li>


            <!-- PRODUCTS -->

            <li class="<?= (
                $currentDirectory === 'products'
            ) ? 'active' : '' ?>">

                <a href="<?= BASE_URL ?>/modules/products/index.php">

                    <span class="nav-icon">
                        ▣
                    </span>

                    <span>
                        Products
                    </span>

                </a>

            </li>


            <!-- CUSTOMERS -->

            <li class="<?= (
                $currentDirectory === 'customers'
            ) ? 'active' : '' ?>">

                <a href="<?= BASE_URL ?>/modules/customers/index.php">

                    <span class="nav-icon">
                        ◉
                    </span>

                    <span>
                        Customers
                    </span>

                </a>

            </li>


            <!-- BILLING -->

            <li class="<?= (
                $currentDirectory === 'billing'
            ) ? 'active' : '' ?>">

                <a href="<?= BASE_URL ?>/modules/billing/index.php">

                    <span class="nav-icon">
                        ▤
                    </span>

                    <span>
                        Billing
                    </span>

                </a>

            </li>


        </ul>

    </div>


    <!-- =====================================================
         MANAGEMENT
    ====================================================== -->

    <div class="nav-section">

        <div class="nav-title">
            MANAGEMENT
        </div>


        <ul class="nav-menu">


            <!-- PURCHASES -->

            <li class="<?= (
                $currentDirectory === 'purchases'
            ) ? 'active' : '' ?>">

                <a href="<?= BASE_URL ?>/modules/purchases/index.php">

                    <span class="nav-icon">
                        ◫
                    </span>

                    <span>
                        Purchases
                    </span>

                </a>

            </li>


            <!-- PAYMENTS -->

            <li class="<?= (
                $currentDirectory === 'payments'
            ) ? 'active' : '' ?>">

                <a href="<?= BASE_URL ?>/modules/payments/index.php">

                    <span class="nav-icon">
                        ₹
                    </span>

                    <span>
                        Payments
                    </span>

                </a>

            </li>


            <!-- INVENTORY -->

            <li class="<?= (
                $currentDirectory === 'inventory'
            ) ? 'active' : '' ?>">

                <a href="<?= BASE_URL ?>/modules/inventory/index.php">

                    <span class="nav-icon">
                        ◩
                    </span>

                    <span>
                        Inventory
                    </span>

                </a>

            </li>


        </ul>

    </div>


    <!-- =====================================================
         SIDEBAR BOTTOM
    ====================================================== -->

    <div class="sidebar-bottom">


        <!-- SYSTEM STATUS -->

        <div class="system-status">

            <span class="status-light"></span>

            <div>

                <strong>
                    System Online
                </strong>

                <small>
                    ElectroCore Core Services
                </small>

            </div>

        </div>


        <!-- LOGOUT -->

        <a
            href="<?= BASE_URL ?>/logout.php"
            class="logout-button"
        >

            <span>
                ⇥
            </span>

            Logout

        </a>


    </div>


</aside>