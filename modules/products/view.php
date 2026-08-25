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
| Check Product ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$product_id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Fetch Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
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
        p.created_at,
        p.updated_at,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
");

$stmt->execute([$product_id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}


/*
|--------------------------------------------------------------------------
| Stock Status
|--------------------------------------------------------------------------
*/

$stock = (int) $product["current_stock"];
$minimum = (int) $product["minimum_stock"];

if ($stock <= 0) {

    $stock_status = "Out of Stock";

} elseif ($stock <= $minimum) {

    $stock_status = "Low Stock";

} else {

    $stock_status = "In Stock";

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ElectroCore | View Product</title>

    <link rel="stylesheet" href="../../assets/css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>

    <main class="main-content">

        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <h1>View Product</h1>

                <p>
                    Product details and inventory information
                </p>

            </div>

            <div class="user-info">

                <strong>
                    <?php echo htmlspecialchars($full_name); ?>
                </strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        ucwords(
                            str_replace("_", " ", $role)
                        )
                    );
                    ?>
                </span>

            </div>

        </div>


        <!-- PRODUCT DETAILS -->

        <div class="stat-card" style="margin-top:20px;">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:20px;
            ">

                <div>

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $product["product_name"]
                        );
                        ?>
                    </h2>

                    <p>
                        Product Code:
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $product["product_code"]
                            );
                            ?>
                        </strong>
                    </p>

                </div>

                <div>

                    <a
                        href="edit.php?id=<?php echo $product_id; ?>"
                        style="
                            background:#f59e0b;
                            color:white;
                            padding:10px 15px;
                            border-radius:7px;
                            text-decoration:none;
                            font-weight:600;
                        "
                    >
                        Edit Product
                    </a>

                    <a
                        href="index.php"
                        style="
                            margin-left:8px;
                            background:#e5e7eb;
                            color:#111827;
                            padding:10px 15px;
                            border-radius:7px;
                            text-decoration:none;
                            font-weight:600;
                        "
                    >
                        Back
                    </a>

                </div>

            </div>


            <!-- BASIC INFORMATION -->

            <h3 style="margin-top:20px;">
                Basic Information
            </h3>

            <div style="
                display:grid;
                grid-template-columns:repeat(2, 1fr);
                gap:15px;
                margin-top:15px;
            ">

                <div>
                    <strong>Category</strong>
                    <p>
                        <?php
                        echo htmlspecialchars(
                            $product["category_name"] ?? "Uncategorized"
                        );
                        ?>
                    </p>
                </div>

                <div>
                    <strong>Brand</strong>
                    <p>
                        <?php
                        echo htmlspecialchars(
                            $product["brand"]
                        );
                        ?>
                    </p>
                </div>

                <div>
                    <strong>Unit</strong>
                    <p>
                        <?php
                        echo htmlspecialchars(
                            $product["unit"]
                        );
                        ?>
                    </p>
                </div>

                <div>
                    <strong>GST Rate</strong>
                    <p>
                        <?php
                        echo htmlspecialchars(
                            $product["gst_rate"]
                        );
                        ?>%
                    </p>
                </div>

            </div>


            <!-- PRICING -->

            <h3 style="margin-top:30px;">
                Pricing
            </h3>

            <div style="
                display:grid;
                grid-template-columns:repeat(2, 1fr);
                gap:15px;
                margin-top:15px;
            ">

                <div>

                    <strong>
                        Purchase Price
                    </strong>

                    <p>
                        ₹<?php
                        echo number_format(
                            (float)$product["purchase_price"],
                            2
                        );
                        ?>
                    </p>

                </div>

                <div>

                    <strong>
                        Selling Price
                    </strong>

                    <p>
                        ₹<?php
                        echo number_format(
                            (float)$product["selling_price"],
                            2
                        );
                        ?>
                    </p>

                </div>

            </div>


            <!-- INVENTORY -->

            <h3 style="margin-top:30px;">
                Inventory
            </h3>

            <div style="
                display:grid;
                grid-template-columns:repeat(3, 1fr);
                gap:15px;
                margin-top:15px;
            ">

                <div>

                    <strong>
                        Current Stock
                    </strong>

                    <p>
                        <?php echo $stock; ?>
                        <?php
                        echo htmlspecialchars(
                            $product["unit"]
                        );
                        ?>
                    </p>

                </div>

                <div>

                    <strong>
                        Minimum Stock
                    </strong>

                    <p>
                        <?php echo $minimum; ?>
                        <?php
                        echo htmlspecialchars(
                            $product["unit"]
                        );
                        ?>
                    </p>

                </div>

                <div>

                    <strong>
                        Stock Status
                    </strong>

                    <p style="margin-top:8px;">

                        <?php if ($stock_status === "In Stock"): ?>

                            <span style="
                                background:#dcfce7;
                                color:#166534;
                                padding:6px 10px;
                                border-radius:20px;
                                font-size:13px;
                                font-weight:600;
                            ">
                                In Stock
                            </span>

                        <?php elseif ($stock_status === "Low Stock"): ?>

                            <span style="
                                background:#fef3c7;
                                color:#92400e;
                                padding:6px 10px;
                                border-radius:20px;
                                font-size:13px;
                                font-weight:600;
                            ">
                                Low Stock
                            </span>

                        <?php else: ?>

                            <span style="
                                background:#fee2e2;
                                color:#991b1b;
                                padding:6px 10px;
                                border-radius:20px;
                                font-size:13px;
                                font-weight:600;
                            ">
                                Out of Stock
                            </span>

                        <?php endif; ?>

                    </p>

                </div>

            </div>


            <!-- RECORD INFORMATION -->

            <h3 style="margin-top:30px;">
                Record Information
            </h3>

            <div style="
                display:grid;
                grid-template-columns:repeat(2, 1fr);
                gap:15px;
                margin-top:15px;
            ">

                <div>

                    <strong>
                        Created At
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $product["created_at"]
                        );
                        ?>
                    </p>

                </div>

                <div>

                    <strong>
                        Last Updated
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $product["updated_at"]
                        );
                        ?>
                    </p>

                </div>

            </div>

        </div>

    </main>

</div>

</body>

</html>