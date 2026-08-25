<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/config/database.php";

$full_name = $_SESSION["full_name"];
$role = $_SESSION["role"];


/*
|--------------------------------------------------------------------------
| TODAY'S SALES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(grand_total), 0)
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
");

$today_sales = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TODAY'S PURCHASES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COALESCE(SUM(grand_total), 0)
    FROM purchases
    WHERE DATE(purchase_date) = CURDATE()
");

$today_purchases = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL ACTIVE PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'active'
");

$total_products = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| LOW STOCK PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'active'
    AND current_stock <= minimum_stock
    AND current_stock > 0
");

$low_stock = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| OUT OF STOCK PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'active'
    AND current_stock = 0
");

$out_of_stock = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL CUSTOMERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM customers
");

$total_customers = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TODAY'S SALES COUNT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
");

$today_sales_count = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TODAY'S PURCHASE COUNT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM purchases
    WHERE DATE(purchase_date) = CURDATE()
");

$today_purchase_count = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| RECENT SALES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT id, grand_total, sale_date
    FROM sales
    ORDER BY sale_date DESC
    LIMIT 5
");

$recent_sales = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| RECENT PURCHASES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT id, grand_total, purchase_date
    FROM purchases
    ORDER BY purchase_date DESC
    LIMIT 5
");

$recent_purchases = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| DATE
|--------------------------------------------------------------------------
*/

$today_date = date("l, d F Y");


/*
|--------------------------------------------------------------------------
| ROLE DISPLAY
|--------------------------------------------------------------------------
*/

$display_role = ucwords(
    str_replace("_", " ", $role)
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ElectroCore | Dashboard</title>

    <link
        rel="stylesheet"
        href="assets/css/dashboard.css"
    >

</head>


<body>

<div class="dashboard">


    <!-- SIDEBAR -->

    <?php require_once __DIR__ . "/includes/sidebar.php"; ?>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- TOP BAR -->

        <header class="topbar">

            <div class="page-heading">

                <div class="page-label">
                    OVERVIEW
                </div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Here's what's happening with your electrical store today.
                </p>

            </div>


            <div class="topbar-right">

                <div class="date-display">

                    <span class="date-icon">
                        ◷
                    </span>

                    <span>
                        <?php echo htmlspecialchars($today_date); ?>
                    </span>

                </div>


                <div class="user-profile">

                    <div class="user-avatar">
                        <?php
                        echo strtoupper(
                            substr($full_name, 0, 1)
                        );
                        ?>
                    </div>

                    <div class="user-details">

                        <strong>
                            <?php
                            echo htmlspecialchars($full_name);
                            ?>
                        </strong>

                        <span>
                            <?php
                            echo htmlspecialchars($display_role);
                            ?>
                        </span>

                    </div>

                </div>

            </div>

        </header>



        <!-- WELCOME BANNER -->

        <section class="welcome-banner">

            <div class="welcome-content">

                <div class="welcome-small">
                    WELCOME BACK
                </div>

                <h2>
                    Hello,
                    <?php echo htmlspecialchars($full_name); ?> 👋
                </h2>

                <p>
                    Manage your billing, inventory and business
                    operations from one place.
                </p>

            </div>


            <div class="welcome-icon">
                ⚡
            </div>

        </section>



        <!-- STATISTICS -->

        <section class="stats-grid">


            <!-- SALES -->

            <div class="stat-card sales-card">

                <div class="stat-top">

                    <div class="stat-icon">
                        ₹
                    </div>

                    <span class="stat-label">
                        TODAY
                    </span>

                </div>

                <div class="stat-title">
                    Today's Sales
                </div>

                <div class="stat-value">
                    ₹<?php echo number_format($today_sales, 2); ?>
                </div>

                <div class="stat-bottom">
                    <?php echo $today_sales_count; ?> transaction<?php echo $today_sales_count == 1 ? '' : 's'; ?> today
                </div>

            </div>



            <!-- PURCHASES -->

            <div class="stat-card purchase-card">

                <div class="stat-top">

                    <div class="stat-icon">
                        ↓
                    </div>

                    <span class="stat-label">
                        TODAY
                    </span>

                </div>

                <div class="stat-title">
                    Today's Purchases
                </div>

                <div class="stat-value">
                    ₹<?php echo number_format($today_purchases, 2); ?>
                </div>

                <div class="stat-bottom">
                    <?php echo $today_purchase_count; ?> purchase<?php echo $today_purchase_count == 1 ? '' : 's'; ?> today
                </div>

            </div>



            <!-- PRODUCTS -->

            <div class="stat-card product-card">

                <div class="stat-top">

                    <div class="stat-icon">
                        ◈
                    </div>

                    <span class="stat-label">
                        ACTIVE
                    </span>

                </div>

                <div class="stat-title">
                    Total Products
                </div>

                <div class="stat-value">
                    <?php echo number_format($total_products); ?>
                </div>

                <div class="stat-bottom">
                    Products currently active
                </div>

            </div>



            <!-- LOW STOCK -->

            <div class="stat-card stock-card">

                <div class="stat-top">

                    <div class="stat-icon">
                        !
                    </div>

                    <span class="stat-label warning">
                        ATTENTION
                    </span>

                </div>

                <div class="stat-title">
                    Low Stock Items
                </div>

                <div class="stat-value">
                    <?php echo number_format($low_stock); ?>
                </div>

                <div class="stat-bottom">
                    <?php echo number_format($out_of_stock); ?> out of stock
                </div>

            </div>

        </section>



        <!-- LOWER CONTENT -->

        <section class="dashboard-grid">


            <!-- RECENT SALES -->

            <div class="panel">

                <div class="panel-header">

                    <div>

                        <span class="panel-label">
                            TRANSACTIONS
                        </span>

                        <h3>
                            Recent Sales
                        </h3>

                    </div>

                    <a
                        href="/electrocore/modules/billing/index.php"
                        class="view-link"
                    >
                        View Billing →
                    </a>

                </div>


                <?php if (count($recent_sales) > 0): ?>

                    <div class="transaction-list">

                        <?php foreach ($recent_sales as $sale): ?>

                            <div class="transaction">

                                <div class="transaction-icon sales-icon">
                                    ↑
                                </div>

                                <div class="transaction-info">

                                    <strong>
                                        Sale #<?php echo htmlspecialchars($sale["id"]); ?>
                                    </strong>

                                    <span>
                                        <?php
                                        echo date(
                                            "d M Y, h:i A",
                                            strtotime($sale["sale_date"])
                                        );
                                        ?>
                                    </span>

                                </div>

                                <div class="transaction-amount">
                                    +₹<?php echo number_format($sale["grand_total"], 2); ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            ◌
                        </div>

                        <strong>
                            No sales yet
                        </strong>

                        <span>
                            Your recent sales will appear here.
                        </span>

                    </div>

                <?php endif; ?>

            </div>



            <!-- RECENT PURCHASES -->

            <div class="panel">

                <div class="panel-header">

                    <div>

                        <span class="panel-label">
                            PROCUREMENT
                        </span>

                        <h3>
                            Recent Purchases
                        </h3>

                    </div>

                    <a
                        href="/electrocore/modules/purchases/"
                        class="view-link"
                    >
                        View Purchases →
                    </a>

                </div>


                <?php if (count($recent_purchases) > 0): ?>

                    <div class="transaction-list">

                        <?php foreach ($recent_purchases as $purchase): ?>

                            <div class="transaction">

                                <div class="transaction-icon purchase-icon">
                                    ↓
                                </div>

                                <div class="transaction-info">

                                    <strong>
                                        Purchase #<?php echo htmlspecialchars($purchase["id"]); ?>
                                    </strong>

                                    <span>
                                        <?php
                                        echo date(
                                            "d M Y, h:i A",
                                            strtotime($purchase["purchase_date"])
                                        );
                                        ?>
                                    </span>

                                </div>

                                <div class="transaction-amount purchase-amount">
                                    -₹<?php echo number_format($purchase["grand_total"], 2); ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            ◌
                        </div>

                        <strong>
                            No purchases yet
                        </strong>

                        <span>
                            Your recent purchases will appear here.
                        </span>

                    </div>

                <?php endif; ?>

            </div>



            <!-- INVENTORY STATUS -->

            <div class="panel inventory-panel">

                <div class="panel-header">

                    <div>

                        <span class="panel-label">
                            INVENTORY
                        </span>

                        <h3>
                            Stock Overview
                        </h3>

                    </div>

                    <a
                        href="/electrocore/modules/inventory/"
                        class="view-link"
                    >
                        View Inventory →
                    </a>

                </div>


                <div class="inventory-summary">


                    <div class="inventory-item">

                        <div class="inventory-icon product">
                            ◈
                        </div>

                        <div>

                            <strong>
                                <?php echo number_format($total_products); ?>
                            </strong>

                            <span>
                                Active Products
                            </span>

                        </div>

                    </div>


                    <div class="inventory-item">

                        <div class="inventory-icon low">
                            !
                        </div>

                        <div>

                            <strong>
                                <?php echo number_format($low_stock); ?>
                            </strong>

                            <span>
                                Low Stock
                            </span>

                        </div>

                    </div>


                    <div class="inventory-item">

                        <div class="inventory-icon out">
                            ×
                        </div>

                        <div>

                            <strong>
                                <?php echo number_format($out_of_stock); ?>
                            </strong>

                            <span>
                                Out of Stock
                            </span>

                        </div>

                    </div>

                </div>

            </div>



            <!-- BUSINESS SUMMARY -->

            <div class="panel summary-panel">

                <div class="panel-header">

                    <div>

                        <span class="panel-label">
                            BUSINESS
                        </span>

                        <h3>
                            Quick Summary
                        </h3>

                    </div>

                </div>


                <div class="summary-list">


                    <div class="summary-row">

                        <span>
                            Today's Revenue
                        </span>

                        <strong>
                            ₹<?php echo number_format($today_sales, 2); ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Today's Purchases
                        </span>

                        <strong>
                            ₹<?php echo number_format($today_purchases, 2); ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Active Products
                        </span>

                        <strong>
                            <?php echo number_format($total_products); ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Total Customers
                        </span>

                        <strong>
                            <?php echo number_format($total_customers); ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Low Stock Alerts
                        </span>

                        <strong class="warning-text">
                            <?php echo number_format($low_stock); ?>
                        </strong>

                    </div>

                </div>

            </div>

        </section>



        <!-- FOOTER -->

        <footer class="dashboard-footer">

            <span>
                © <?php echo date("Y"); ?> ElectroCore
            </span>

            <span>
                Billing & Inventory Management System
            </span>

        </footer>


    </main>

</div>

</body>
</html>