<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

$current_page = 'products';

require_once __DIR__ . "/../../config/database.php";

$full_name = $_SESSION["full_name"];
$role = $_SESSION["role"];

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

$category_stmt = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 'active'
    ORDER BY name ASC
");

$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| HANDLE PRODUCT SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_code = trim($_POST["product_code"] ?? "");
    $product_name = trim($_POST["product_name"] ?? "");
    $category_id = (int) ($_POST["category_id"] ?? 0);
    $brand = trim($_POST["brand"] ?? "");
    $unit = trim($_POST["unit"] ?? "");
    $purchase_price = (float) ($_POST["purchase_price"] ?? 0);
    $selling_price = (float) ($_POST["selling_price"] ?? 0);
    $gst_rate = (float) ($_POST["gst_rate"] ?? 0);
    $opening_stock = (float) ($_POST["opening_stock"] ?? 0);
    $minimum_stock = (float) ($_POST["minimum_stock"] ?? 0);

    if (
        $product_code === "" ||
        $product_name === "" ||
        $category_id <= 0 ||
        $unit === ""
    ) {

        $error = "Please fill all required fields.";

    } elseif ($purchase_price < 0 || $selling_price < 0) {

        $error = "Prices cannot be negative.";

    } elseif ($opening_stock < 0 || $minimum_stock < 0) {

        $error = "Stock values cannot be negative.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE PRODUCT CODE
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM products
                WHERE product_code = ?
                LIMIT 1
            ");

            $check->execute([$product_code]);

            if ($check->fetch()) {

                $error = "Product code already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | INSERT PRODUCT
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO products
                    (
                        product_code,
                        product_name,
                        category_id,
                        brand,
                        unit,
                        purchase_price,
                        selling_price,
                        gst_rate,
                        current_stock,
                        minimum_stock,
                        status
                    )
                    VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");

                $stmt->execute([
                    $product_code,
                    $product_name,
                    $category_id,
                    $brand,
                    $unit,
                    $purchase_price,
                    $selling_price,
                    $gst_rate,
                    $opening_stock,
                    $minimum_stock
                ]);

                $product_id = $pdo->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | OPENING STOCK MOVEMENT
                |--------------------------------------------------------------------------
                */

                if ($opening_stock > 0) {

                    $movement = $pdo->prepare("
                        INSERT INTO stock_movements
                        (
                            product_id,
                            movement_type,
                            quantity,
                            stock_before,
                            stock_after,
                            notes,
                            created_by
                        )
                        VALUES
                        (?, 'opening_stock', ?, 0, ?, 'Opening stock', ?)
                    ");

                    $movement->execute([
                        $product_id,
                        $opening_stock,
                        $opening_stock,
                        $_SESSION["user_id"]
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | ACTIVITY LOG
                |--------------------------------------------------------------------------
                */

                $log = $pdo->prepare("
                    INSERT INTO activity_logs
                    (
                        user_id,
                        action,
                        module,
                        description,
                        reference_type,
                        reference_id,
                        ip_address
                    )
                    VALUES
                    (?, 'CREATE', 'Products', ?, 'product', ?, ?)
                ");

                $log->execute([
                    $_SESSION["user_id"],
                    "Created product: " . $product_name,
                    $product_id,
                    $_SERVER["REMOTE_ADDR"] ?? null
                ]);

                $message = "Product added successfully.";

            }

        } catch (PDOException $e) {

            $error = "Unable to save product. Please try again.";

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

    <title>ElectroCore | Add Product</title>

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css"
    >

    <style>

        /* =========================================================
           ELECTROCORE ADD PRODUCT
           DARK DASHBOARD THEME
        ========================================================= */

        .product-page {
            width: 100%;
        }


        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .product-page-header {

            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 24px;

        }


        .product-page-title {

            display: flex;
            align-items: center;
            gap: 14px;

        }


        .page-icon {

            width: 46px;
            height: 46px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    var(--blue),
                    var(--blue-dark)
                );

            color: white;

            font-size: 22px;
            font-weight: 700;

            box-shadow:
                0 8px 25px
                rgba(18, 184, 255, 0.20);

        }


        .product-page-title h1 {

            margin: 0 0 5px;

            font-size: 25px;
            font-weight: 650;

            color: var(--text);

            letter-spacing: -0.5px;

        }


        .product-page-title p {

            margin: 0;

            color: var(--text-secondary);

            font-size: 11px;

        }


        .back-products {

            display: inline-flex;

            align-items: center;
            gap: 7px;

            padding: 10px 15px;

            border-radius: 9px;

            color: #9eb0c2;

            background:
                rgba(255,255,255,0.025);

            border:
                1px solid
                rgba(255,255,255,0.07);

            text-decoration: none;

            font-size: 10px;
            font-weight: 600;

            transition: .2s ease;

        }


        .back-products:hover {

            color: var(--blue);

            border-color:
                rgba(18,184,255,0.25);

            background:
                rgba(18,184,255,0.05);

        }


        /* =========================================================
           ALERTS
        ========================================================= */

        .product-alert {

            display: flex;
            align-items: center;
            gap: 10px;

            padding: 12px 15px;

            margin-bottom: 18px;

            border-radius: 10px;

            font-size: 10px;
            font-weight: 600;

        }


        .product-alert.success {

            color: #39d99d;

            background:
                rgba(33,217,154,0.07);

            border:
                1px solid
                rgba(33,217,154,0.15);

        }


        .product-alert.error {

            color: #ff8585;

            background:
                rgba(255,80,80,0.07);

            border:
                1px solid
                rgba(255,80,80,0.15);

        }


        .alert-symbol {

            width: 22px;
            height: 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.05);

            font-size: 11px;

        }


        /* =========================================================
           FORM CARD
        ========================================================= */

        .product-form-card {

            background:
                linear-gradient(
                    145deg,
                    #0b1726,
                    #09131f
                );

            border:
                1px solid
                var(--border);

            border-radius: 14px;

            overflow: hidden;

        }


        /* =========================================================
           FORM CARD HEADER
        ========================================================= */

        .form-card-header {

            padding: 20px 23px;

            display: flex;

            align-items: center;
            justify-content: space-between;

            border-bottom:
                1px solid
                rgba(255,255,255,0.055);

        }


        .form-card-heading {

            display: flex;
            align-items: center;
            gap: 12px;

        }


        .form-section-icon {

            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            color: var(--blue);

            background:
                rgba(18,184,255,0.08);

            border:
                1px solid
                rgba(18,184,255,0.10);

            font-size: 15px;

        }


        .form-card-header h2 {

            margin: 0 0 4px;

            color: var(--text);

            font-size: 14px;
            font-weight: 650;

        }


        .form-card-header p {

            margin: 0;

            color: #60758a;

            font-size: 9px;

        }


        .required-note {

            color: #60758a;

            font-size: 9px;

        }


        .required-note span {

            color: #ff7777;

        }


        /* =========================================================
           FORM BODY
        ========================================================= */

        .product-form-body {

            padding: 24px;

        }


        /* =========================================================
           SECTION HEADINGS
        ========================================================= */

        .section-heading {

            display: flex;
            align-items: center;

            gap: 9px;

            margin-bottom: 17px;

        }


        .section-heading-line {

            width: 3px;
            height: 17px;

            border-radius: 4px;

            background:
                var(--blue);

            box-shadow:
                0 0 10px
                rgba(18,184,255,0.35);

        }


        .section-heading h3 {

            margin: 0;

            color: #dce7f1;

            font-size: 12px;
            font-weight: 650;

        }


        .section-heading span {

            color: #536a7f;

            font-size: 8px;

        }


        /* =========================================================
           FORM GRID
        ========================================================= */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px 20px;

        }


        .form-group {

            display: flex;
            flex-direction: column;

        }


        .form-group.full {

            grid-column: 1 / -1;

        }


        .form-group label {

            margin-bottom: 7px;

            color: #9eb0c1;

            font-size: 9px;

            font-weight: 600;

        }


        .required {

            color: #ff7777;

            margin-left: 2px;

        }


        .field-hint {

            margin-top: 5px;

            color: #52677b;

            font-size: 8px;

        }


        /* =========================================================
           INPUTS
        ========================================================= */

        .product-form input,
        .product-form select {

            width: 100%;

            height: 42px;

            padding: 0 12px;

            border-radius: 8px;

            border:
                1px solid
                rgba(255,255,255,0.08);

            background:
                rgba(255,255,255,0.025);

            color: #dce7f1;

            font-family: inherit;

            font-size: 10px;

            transition: .18s ease;

        }


        .product-form input::placeholder {

            color: #506579;

        }


        .product-form input:hover,
        .product-form select:hover {

            border-color:
                rgba(18,184,255,0.20);

        }


        .product-form input:focus,
        .product-form select:focus {

            outline: none;

            border-color:
                rgba(18,184,255,0.55);

            background:
                rgba(18,184,255,0.035);

            box-shadow:
                0 0 0 3px
                rgba(18,184,255,0.08);

        }


        .product-form select option {

            background: #0b1726;

            color: #eaf3fb;

        }


        /* =========================================================
           PRICE INPUT
        ========================================================= */

        .input-wrap {

            position: relative;

        }


        .input-prefix {

            position: absolute;

            left: 13px;
            top: 50%;

            transform:
                translateY(-50%);

            color: #61768a;

            font-size: 10px;
            font-weight: 600;

            pointer-events: none;

        }


        .price-input {

            padding-left: 29px !important;

        }


        /* =========================================================
           DIVIDER
        ========================================================= */

        .form-divider {

            height: 1px;

            background:
                rgba(255,255,255,0.055);

            margin: 26px 0;

        }


        /* =========================================================
           PRICE / STOCK BOXES
        ========================================================= */

        .price-grid,
        .stock-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 16px;

        }


        .price-box,
        .stock-card {

            padding: 16px;

            border-radius: 10px;

            background:
                rgba(255,255,255,0.022);

            border:
                1px solid
                rgba(255,255,255,0.055);

        }


        .price-box label,
        .stock-card label {

            display: block;

            margin-bottom: 8px;

            color: #91a5b8;

            font-size: 9px;

            font-weight: 600;

        }


        /* =========================================================
           ACTION AREA
        ========================================================= */

        .form-actions {

            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-top: 28px;

            padding-top: 20px;

            border-top:
                1px solid
                rgba(255,255,255,0.055);

        }


        .action-info {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #60758a;

            font-size: 9px;

        }


        .action-info strong {

            color: #8da2b5;

        }


        .action-info-icon {

            width: 24px;
            height: 24px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            color: #28d79a;

            background:
                rgba(33,217,154,0.07);

            font-size: 10px;

        }


        .action-buttons {

            display: flex;

            gap: 9px;

        }


        .btn {

            height: 40px;

            padding: 0 17px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            border-radius: 8px;

            font-family: inherit;

            font-size: 10px;

            font-weight: 650;

            text-decoration: none;

            cursor: pointer;

            transition: .2s ease;

        }


        .btn-cancel {

            color: #8296a9;

            background:
                rgba(255,255,255,0.025);

            border:
                1px solid
                rgba(255,255,255,0.08);

        }


        .btn-cancel:hover {

            color: #d5e1eb;

            background:
                rgba(255,255,255,0.045);

        }


        .btn-save {

            border: none;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    var(--blue),
                    var(--blue-dark)
                );

            box-shadow:
                0 6px 18px
                rgba(18,184,255,0.18);

        }


        .btn-save:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 8px 22px
                rgba(18,184,255,0.25);

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 800px) {

            .product-page-header {

                align-items: flex-start;

            }


            .back-products {

                display: none;

            }


            .form-grid,
            .price-grid,
            .stock-grid {

                grid-template-columns: 1fr;

            }


            .form-group.full {

                grid-column: auto;

            }


            .product-form-body {

                padding: 20px;

            }


            .form-card-header {

                padding: 18px 20px;

            }


            .form-actions {

                align-items: flex-start;

                flex-direction: column;

            }


            .action-buttons {

                width: 100%;

            }


            .action-buttons .btn {

                flex: 1;

            }

        }


        @media (max-width: 500px) {

            .product-page-title h1 {

                font-size: 21px;

            }


            .page-icon {

                width: 42px;
                height: 42px;

            }


            .product-form-body {

                padding: 16px;

            }


            .form-card-header {

                padding: 16px;

            }

        }

    </style>

</head>


<body>

<div class="dashboard">


    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>


    <main class="main-content">


        <div class="product-page">


            <!-- =====================================================
                 PAGE HEADER
            ====================================================== -->

            <div class="product-page-header">

                <div class="product-page-title">

                    <div class="page-icon">
                        +
                    </div>

                    <div>

                        <h1>
                            Add Product
                        </h1>

                        <p>
                            Create a new product and add it to your ElectroCore inventory.
                        </p>

                    </div>

                </div>


                <a
                    href="index.php"
                    class="back-products"
                >
                    ← Back to Products
                </a>

            </div>


            <!-- =====================================================
                 SUCCESS MESSAGE
            ====================================================== -->

            <?php if ($message): ?>

                <div class="product-alert success">

                    <span class="alert-symbol">
                        ✓
                    </span>

                    <span>
                        <?= htmlspecialchars($message) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =====================================================
                 ERROR MESSAGE
            ====================================================== -->

            <?php if ($error): ?>

                <div class="product-alert error">

                    <span class="alert-symbol">
                        !
                    </span>

                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- =====================================================
                 FORM CARD
            ====================================================== -->

            <div class="product-form-card">


                <!-- CARD HEADER -->

                <div class="form-card-header">

                    <div class="form-card-heading">

                        <div class="form-section-icon">
                            ⚡
                        </div>

                        <div>

                            <h2>
                                Product Information
                            </h2>

                            <p>
                                Enter the basic details of the electrical product.
                            </p>

                        </div>

                    </div>


                    <div class="required-note">

                        <span>*</span>
                        Required fields

                    </div>

                </div>


                <!-- FORM BODY -->

                <div class="product-form-body">


                    <form
                        method="POST"
                        class="product-form"
                    >


                        <!-- =================================================
                             BASIC INFORMATION
                        ================================================== -->

                        <div class="section-heading">

                            <div class="section-heading-line"></div>

                            <h3>
                                Basic Information
                            </h3>

                            <span>
                                Product identification
                            </span>

                        </div>


                        <div class="form-grid">


                            <!-- PRODUCT CODE -->

                            <div class="form-group">

                                <label>

                                    Product Code

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    name="product_code"
                                    placeholder="Example: LED-12W-001"
                                    value="<?= htmlspecialchars($_POST['product_code'] ?? '') ?>"
                                    required
                                >

                                <div class="field-hint">
                                    Unique code used to identify the product.
                                </div>

                            </div>


                            <!-- PRODUCT NAME -->

                            <div class="form-group">

                                <label>

                                    Product Name

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    name="product_name"
                                    placeholder="Example: LED Bulb 12W"
                                    value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>"
                                    required
                                >

                            </div>


                            <!-- CATEGORY -->

                            <div class="form-group">

                                <label>

                                    Category

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    name="category_id"
                                    required
                                >

                                    <option value="">
                                        Select Category
                                    </option>

                                    <?php foreach ($categories as $category): ?>

                                        <option
                                            value="<?= $category['id'] ?>"
                                            <?= (
                                                isset($_POST['category_id']) &&
                                                $_POST['category_id'] == $category['id']
                                            ) ? 'selected' : '' ?>
                                        >

                                            <?= htmlspecialchars($category['name']) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- BRAND -->

                            <div class="form-group">

                                <label>
                                    Brand
                                </label>

                                <input
                                    type="text"
                                    name="brand"
                                    placeholder="Example: Philips"
                                    value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>"
                                >

                            </div>


                            <!-- UNIT -->

                            <div class="form-group">

                                <label>

                                    Unit

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    name="unit"
                                    required
                                >

                                    <option value="">
                                        Select Unit
                                    </option>

                                    <option
                                        value="Piece"
                                        <?= (($_POST['unit'] ?? '') === 'Piece') ? 'selected' : '' ?>
                                    >
                                        Piece
                                    </option>

                                    <option
                                        value="Box"
                                        <?= (($_POST['unit'] ?? '') === 'Box') ? 'selected' : '' ?>
                                    >
                                        Box
                                    </option>

                                    <option
                                        value="Meter"
                                        <?= (($_POST['unit'] ?? '') === 'Meter') ? 'selected' : '' ?>
                                    >
                                        Meter
                                    </option>

                                    <option
                                        value="Roll"
                                        <?= (($_POST['unit'] ?? '') === 'Roll') ? 'selected' : '' ?>
                                    >
                                        Roll
                                    </option>

                                    <option
                                        value="Set"
                                        <?= (($_POST['unit'] ?? '') === 'Set') ? 'selected' : '' ?>
                                    >
                                        Set
                                    </option>

                                    <option
                                        value="Packet"
                                        <?= (($_POST['unit'] ?? '') === 'Packet') ? 'selected' : '' ?>
                                    >
                                        Packet
                                    </option>

                                </select>

                            </div>


                            <!-- GST -->

                            <div class="form-group">

                                <label>
                                    GST Rate
                                </label>

                                <select name="gst_rate">

                                    <option value="0">
                                        0%
                                    </option>

                                    <option value="5">
                                        5%
                                    </option>

                                    <option value="12">
                                        12%
                                    </option>

                                    <option
                                        value="18"
                                        <?= (($_POST['gst_rate'] ?? '18') == '18') ? 'selected' : '' ?>
                                    >
                                        18%
                                    </option>

                                    <option value="28">
                                        28%
                                    </option>

                                </select>

                                <div class="field-hint">
                                    Used automatically when generating tax invoices.
                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             PRICING
                        ================================================== -->

                        <div class="form-divider"></div>


                        <div class="section-heading">

                            <div class="section-heading-line"></div>

                            <h3>
                                Pricing
                            </h3>

                            <span>
                                Purchase and selling rates
                            </span>

                        </div>


                        <div class="price-grid">


                            <!-- PURCHASE -->

                            <div class="price-box">

                                <label>
                                    Purchase Price
                                </label>

                                <div class="input-wrap">

                                    <span class="input-prefix">
                                        ₹
                                    </span>

                                    <input
                                        class="price-input"
                                        type="number"
                                        name="purchase_price"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        value="<?= htmlspecialchars($_POST['purchase_price'] ?? '') ?>"
                                    >

                                </div>

                                <div class="field-hint">
                                    Cost price at which the product is purchased.
                                </div>

                            </div>


                            <!-- SELLING -->

                            <div class="price-box">

                                <label>
                                    Selling Price
                                </label>

                                <div class="input-wrap">

                                    <span class="input-prefix">
                                        ₹
                                    </span>

                                    <input
                                        class="price-input"
                                        type="number"
                                        name="selling_price"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        value="<?= htmlspecialchars($_POST['selling_price'] ?? '') ?>"
                                    >

                                </div>

                                <div class="field-hint">
                                    Customer-facing selling price.
                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             INVENTORY
                        ================================================== -->

                        <div class="form-divider"></div>


                        <div class="section-heading">

                            <div class="section-heading-line"></div>

                            <h3>
                                Inventory Settings
                            </h3>

                            <span>
                                Stock management
                            </span>

                        </div>


                        <div class="stock-grid">


                            <!-- OPENING STOCK -->

                            <div class="stock-card">

                                <label>
                                    Opening Stock
                                </label>

                                <input
                                    type="number"
                                    name="opening_stock"
                                    step="0.01"
                                    min="0"
                                    value="<?= htmlspecialchars($_POST['opening_stock'] ?? '0') ?>"
                                >

                                <div class="field-hint">
                                    Initial quantity available in your store.
                                </div>

                            </div>


                            <!-- MINIMUM STOCK -->

                            <div class="stock-card">

                                <label>
                                    Minimum Stock Level
                                </label>

                                <input
                                    type="number"
                                    name="minimum_stock"
                                    step="0.01"
                                    min="0"
                                    value="<?= htmlspecialchars($_POST['minimum_stock'] ?? '5') ?>"
                                >

                                <div class="field-hint">
                                    Used for low-stock monitoring.
                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             ACTIONS
                        ================================================== -->

                        <div class="form-actions">


                            <div class="action-info">

                                <div class="action-info-icon">
                                    ✓
                                </div>

                                <span>
                                    Product will be added as
                                    <strong>Active</strong>.
                                </span>

                            </div>


                            <div class="action-buttons">

                                <a
                                    href="index.php"
                                    class="btn btn-cancel"
                                >
                                    Cancel
                                </a>


                                <button
                                    type="submit"
                                    class="btn btn-save"
                                >

                                    <span>
                                        +
                                    </span>

                                    Save Product

                                </button>

                            </div>

                        </div>


                    </form>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>