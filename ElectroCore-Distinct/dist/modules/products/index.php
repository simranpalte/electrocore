<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$full_name = $_SESSION["full_name"];
$role = $_SESSION["role"];

/*
|--------------------------------------------------------------------------
| Fetch Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.product_code,
        p.product_name,
        p.brand,
        p.unit,
        p.gst_rate,
        p.purchase_price,
        p.selling_price,
        p.current_stock,
        p.minimum_stock,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ElectroCore | Products</title>

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css"
    >

    <style>

        /* =========================================================
           ELECTROCORE PRODUCTS PAGE
        ========================================================= */

        .products-page {
            width: 100%;
        }


        /* =========================================================
           PRODUCT HEADER CARD
        ========================================================= */

        .products-header-card {

            position: relative;

            padding: 24px 26px;

            margin-bottom: 18px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            border-radius: 13px;

            background:
                linear-gradient(
                    145deg,
                    #0c1929,
                    #091421
                );

            border: 1px solid var(--border);

            overflow: hidden;

        }


        .products-header-card::after {

            content: "";

            position: absolute;

            width: 220px;
            height: 220px;

            right: -100px;
            top: -110px;

            border-radius: 50%;

            border: 1px solid
                rgba(18, 184, 255, 0.07);

            pointer-events: none;

        }


        .products-header-content {

            position: relative;

            z-index: 2;

        }


        .products-label {

            color: var(--blue);

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 1.8px;

            margin-bottom: 7px;

        }


        .products-header-content h2 {

            font-size: 19px;

            font-weight: 600;

            margin-bottom: 6px;

        }


        .products-header-content p {

            color: var(--text-secondary);

            font-size: 11px;

        }


        /* =========================================================
           ADD PRODUCT BUTTON
        ========================================================= */

        .add-product-button {

            position: relative;

            z-index: 3;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 17px;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    var(--blue),
                    var(--blue-dark)
                );

            color: white;

            text-decoration: none;

            font-size: 11px;

            font-weight: 600;

            border: 1px solid
                rgba(18, 184, 255, 0.25);

            box-shadow:
                0 8px 22px
                rgba(0, 140, 255, 0.14);

            transition: 0.2s ease;

            white-space: nowrap;

        }


        .add-product-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 10px 26px
                rgba(0, 140, 255, 0.22);

        }


        /* =========================================================
           PRODUCTS TABLE PANEL
        ========================================================= */

        .products-panel {

            padding: 23px;

            border-radius: 13px;

            background:
                linear-gradient(
                    145deg,
                    #0b1726,
                    #09131f
                );

            border: 1px solid var(--border);

            overflow: hidden;

        }


        .products-panel-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;

        }


        .products-panel-title {

            display: flex;

            flex-direction: column;

            gap: 5px;

        }


        .products-panel-title span {

            color: #526a80;

            font-size: 8px;

            font-weight: 700;

            letter-spacing: 1.5px;

        }


        .products-panel-title h3 {

            font-size: 15px;

            font-weight: 600;

        }


        .product-count {

            color: #52677c;

            font-size: 9px;

        }


        /* =========================================================
           TABLE WRAPPER
        ========================================================= */

        .products-table-wrapper {

            width: 100%;

            overflow-x: auto;

            border-radius: 10px;

            border: 1px solid
                rgba(255, 255, 255, 0.045);

        }


        /* =========================================================
           TABLE
        ========================================================= */

        .products-table {

            width: 100%;

            min-width: 950px;

            border-collapse: collapse;

            text-align: left;

        }


        .products-table thead {

            background:
                rgba(255, 255, 255, 0.025);

        }


        .products-table th {

            padding: 13px 14px;

            color: #5d7287;

            font-size: 8px;

            font-weight: 700;

            letter-spacing: 1px;

            text-transform: uppercase;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.06);

            white-space: nowrap;

        }


        .products-table td {

            padding: 14px;

            color: #9badbe;

            font-size: 10px;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.045);

            vertical-align: middle;

        }


        .products-table tbody tr {

            transition: 0.18s ease;

        }


        .products-table tbody tr:hover {

            background:
                rgba(18, 184, 255, 0.025);

        }


        .products-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =========================================================
           PRODUCT NAME
        ========================================================= */

        .product-name-cell {

            display: flex;

            flex-direction: column;

            gap: 4px;

        }


        .product-name {

            color: #d8e3ed;

            font-size: 10px;

            font-weight: 600;

        }


        .product-code-small {

            color: #52677b;

            font-size: 8px;

        }


        /* =========================================================
           CODE
        ========================================================= */

        .product-code {

            color: var(--blue);

            font-size: 9px;

            font-weight: 600;

            letter-spacing: 0.3px;

        }


        /* =========================================================
           CATEGORY
        ========================================================= */

        .category-badge {

            display: inline-flex;

            align-items: center;

            padding: 5px 8px;

            border-radius: 6px;

            background:
                rgba(18, 184, 255, 0.07);

            border:
                1px solid
                rgba(18, 184, 255, 0.08);

            color: #8ca4b9;

            font-size: 8px;

        }


        /* =========================================================
           BRAND
        ========================================================= */

        .brand-text {

            color: #91a4b6;

        }


        .muted-text {

            color: #465b70;

        }


        /* =========================================================
           PRICE
        ========================================================= */

        .price {

            color: #dce7ef;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;

        }


        /* =========================================================
           GST
        ========================================================= */

        .gst-badge {

            color: #8097aa;

            font-size: 9px;

        }


        /* =========================================================
           STOCK
        ========================================================= */

        .stock-cell {

            display: flex;

            flex-direction: column;

            gap: 4px;

        }


        .stock-number {

            color: #dce7ef;

            font-size: 10px;

            font-weight: 600;

        }


        .stock-unit {

            color: #52677b;

            font-size: 8px;

        }


        /* =========================================================
           STATUS
        ========================================================= */

        .stock-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 5px 8px;

            border-radius: 20px;

            font-size: 8px;

            font-weight: 600;

            white-space: nowrap;

        }


        .stock-status::before {

            content: "";

            width: 5px;

            height: 5px;

            border-radius: 50%;

        }


        .status-in {

            color: #32d79c;

            background:
                rgba(33, 217, 154, 0.08);

            border:
                1px solid
                rgba(33, 217, 154, 0.10);

        }


        .status-in::before {

            background: #32d79c;

            box-shadow:
                0 0 7px
                rgba(33, 217, 154, 0.65);

        }


        .status-low {

            color: var(--warning);

            background:
                rgba(255, 184, 77, 0.08);

            border:
                1px solid
                rgba(255, 184, 77, 0.10);

        }


        .status-low::before {

            background: var(--warning);

            box-shadow:
                0 0 7px
                rgba(255, 184, 77, 0.55);

        }


        .status-out {

            color: #ff7777;

            background:
                rgba(255, 80, 80, 0.08);

            border:
                1px solid
                rgba(255, 80, 80, 0.10);

        }


        .status-out::before {

            background: #ff7777;

            box-shadow:
                0 0 7px
                rgba(255, 80, 80, 0.55);

        }


        /* =========================================================
           ACTION BUTTONS
        ========================================================= */

        .action-buttons {

            display: flex;

            align-items: center;

            gap: 6px;

            white-space: nowrap;

        }


        .action-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            height: 29px;

            padding: 0 9px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 8px;

            font-weight: 600;

            border: 1px solid transparent;

            transition: 0.18s ease;

        }


        .action-view {

            color: var(--blue);

            background:
                rgba(18, 184, 255, 0.07);

            border-color:
                rgba(18, 184, 255, 0.10);

        }


        .action-view:hover {

            background:
                rgba(18, 184, 255, 0.13);

        }


        .action-edit {

            color: var(--warning);

            background:
                rgba(255, 184, 77, 0.07);

            border-color:
                rgba(255, 184, 77, 0.10);

        }


        .action-edit:hover {

            background:
                rgba(255, 184, 77, 0.13);

        }


        .action-delete {

            color: #ff7777;

            background:
                rgba(255, 80, 80, 0.07);

            border-color:
                rgba(255, 80, 80, 0.10);

        }


        .action-delete:hover {

            background:
                rgba(255, 80, 80, 0.13);

        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .products-empty {

            padding: 60px 20px;

            text-align: center;

        }


        .products-empty-icon {

            width: 46px;

            height: 46px;

            margin: 0 auto 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            color: #526a7f;

            background:
                rgba(255, 255, 255, 0.03);

            font-size: 18px;

        }


        .products-empty strong {

            display: block;

            color: #73879a;

            font-size: 10px;

            margin-bottom: 5px;

        }


        .products-empty span {

            color: #465b70;

            font-size: 8px;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 700px) {

            .products-header-card {

                align-items: flex-start;

                flex-direction: column;

            }

            .add-product-button {

                width: 100%;

            }

            .products-panel {

                padding: 16px;

            }

        }

    </style>

</head>


<body>

<div class="dashboard">


    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>


    <main class="main-content">


        <!-- =====================================================
             TOPBAR
        ====================================================== -->

        <div class="topbar">

            <div class="page-heading">

                <div class="page-label">
                    INVENTORY
                </div>

                <h1>
                    Products
                </h1>

                <p>
                    Manage electrical products, pricing and stock
                </p>

            </div>


            <div class="topbar-right">

                <div class="date-display">

                    <span class="date-icon">
                        ◷
                    </span>

                    <?php echo date("d M Y"); ?>

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
                            echo htmlspecialchars(
                                ucwords(
                                    str_replace(
                                        "_",
                                        " ",
                                        $role
                                    )
                                )
                            );
                            ?>

                        </span>

                    </div>

                </div>

            </div>

        </div>


        <div class="products-page">


            <!-- =================================================
                 PRODUCT MANAGEMENT HEADER
            ================================================== -->

            <section class="products-header-card">

                <div class="products-header-content">

                    <div class="products-label">
                        PRODUCT MANAGEMENT
                    </div>

                    <h2>
                        Electrical Product Inventory
                    </h2>

                    <p>
                        Manage products, pricing, GST and available stock.
                    </p>

                </div>


                <a
                    href="add.php"
                    class="add-product-button"
                >
                    <span>＋</span>
                    Add Product
                </a>

            </section>


            <!-- =================================================
                 PRODUCTS TABLE
            ================================================== -->

            <section class="products-panel">


                <div class="products-panel-header">

                    <div class="products-panel-title">

                        <span>
                            INVENTORY LIST
                        </span>

                        <h3>
                            All Products
                        </h3>

                    </div>


                    <div class="product-count">

                        <?php
                        echo count($products);
                        ?>
                        product<?php echo count($products) == 1 ? "" : "s"; ?>

                    </div>

                </div>


                <div class="products-table-wrapper">


                    <?php if (count($products) > 0): ?>


                        <table class="products-table">


                            <thead>

                                <tr>

                                    <th>
                                        Code
                                    </th>

                                    <th>
                                        Product
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Brand
                                    </th>

                                    <th>
                                        Selling Price
                                    </th>

                                    <th>
                                        GST
                                    </th>

                                    <th>
                                        Stock
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach ($products as $product): ?>


                                <?php

                                $stock = (float) $product["current_stock"];

                                $minimum = (float) $product["minimum_stock"];


                                if ($stock <= 0) {

                                    $stock_status = "Out of Stock";

                                    $status_class = "status-out";

                                } elseif ($stock <= $minimum) {

                                    $stock_status = "Low Stock";

                                    $status_class = "status-low";

                                } else {

                                    $stock_status = "In Stock";

                                    $status_class = "status-in";

                                }

                                ?>


                                <tr>


                                    <!-- CODE -->

                                    <td>

                                        <span class="product-code">

                                            <?php
                                            echo htmlspecialchars(
                                                $product["product_code"]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <div class="product-name-cell">

                                            <span class="product-name">

                                                <?php
                                                echo htmlspecialchars(
                                                    $product["product_name"]
                                                );
                                                ?>

                                            </span>

                                            <span class="product-code-small">

                                                ID #<?php
                                                echo (int) $product["id"];
                                                ?>

                                            </span>

                                        </div>

                                    </td>


                                    <!-- CATEGORY -->

                                    <td>

                                        <span class="category-badge">

                                            <?php
                                            echo htmlspecialchars(
                                                $product["category_name"]
                                                    ?? "Uncategorized"
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- BRAND -->

                                    <td>

                                        <?php if (!empty($product["brand"])): ?>

                                            <span class="brand-text">

                                                <?php
                                                echo htmlspecialchars(
                                                    $product["brand"]
                                                );
                                                ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="muted-text">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- SELLING PRICE -->

                                    <td>

                                        <span class="price">

                                            ₹<?php
                                            echo number_format(
                                                (float) $product["selling_price"],
                                                2
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- GST -->

                                    <td>

                                        <span class="gst-badge">

                                            <?php
                                            echo htmlspecialchars(
                                                $product["gst_rate"]
                                            );
                                            ?>%

                                        </span>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <div class="stock-cell">

                                            <span class="stock-number">

                                                <?php
                                                echo rtrim(
                                                    rtrim(
                                                        number_format(
                                                            $stock,
                                                            2
                                                        ),
                                                        "0"
                                                    ),
                                                    "."
                                                );
                                                ?>

                                            </span>

                                            <span class="stock-unit">

                                                <?php
                                                echo htmlspecialchars(
                                                    $product["unit"]
                                                );
                                                ?>

                                            </span>

                                        </div>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="stock-status <?php echo $status_class; ?>"
                                        >

                                            <?php
                                            echo $stock_status;
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="action-buttons">


                                            <a
                                                href="view.php?id=<?php echo (int) $product["id"]; ?>"
                                                class="action-button action-view"
                                            >
                                                View
                                            </a>


                                            <a
                                                href="edit.php?id=<?php echo (int) $product["id"]; ?>"
                                                class="action-button action-edit"
                                            >
                                                Edit
                                            </a>


                                            <a
                                                href="delete.php?id=<?php echo (int) $product["id"]; ?>"
                                                class="action-button action-delete"
                                                onclick="return confirm('Are you sure you want to deactivate this product?');"
                                            >
                                                Delete
                                            </a>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    <?php else: ?>


                        <div class="products-empty">

                            <div class="products-empty-icon">
                                ▣
                            </div>

                            <strong>
                                No Products Found
                            </strong>

                            <span>
                                Add your first electrical product to begin managing inventory.
                            </span>

                        </div>


                    <?php endif; ?>


                </div>


            </section>


        </div>


        <!-- FOOTER -->

        <div class="dashboard-footer">

            <span>
                © <?php echo date("Y"); ?> ElectroCore
            </span>

            <span>
                Electrical Billing & Inventory Management
            </span>

        </div>


    </main>

</div>

</body>

</html>