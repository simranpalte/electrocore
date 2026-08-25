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
| Fetch Inventory
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.product_code,
        p.product_name,
        p.brand,
        p.unit,
        p.current_stock,
        p.minimum_stock,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.product_name ASC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Inventory Statistics
|--------------------------------------------------------------------------
*/

$total_products = count($products);

$total_stock = 0;
$low_stock = 0;
$out_of_stock = 0;

foreach ($products as $product) {

    $stock = (float) $product["current_stock"];
    $minimum = (float) $product["minimum_stock"];

    $total_stock += $stock;

    if ($stock <= 0) {

        $out_of_stock++;

    } elseif ($stock <= $minimum) {

        $low_stock++;
    }
}


/*
|--------------------------------------------------------------------------
| Recent Stock Movements
|--------------------------------------------------------------------------
*/

$movement_stmt = $pdo->query("
    SELECT
        sm.id,
        sm.movement_type,
        sm.quantity,
        sm.stock_before,
        sm.stock_after,
        sm.reference_number,
        sm.notes,
        sm.created_at,
        p.product_name,
        p.unit
    FROM stock_movements sm
    INNER JOIN products p
        ON sm.product_id = p.id
    ORDER BY sm.id DESC
    LIMIT 10
");

$movements = $movement_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ElectroCore | Inventory</title>

    <link rel="stylesheet" href="../../assets/css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>

    <main class="main-content">

        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <h1>Inventory</h1>

                <p>
                    Monitor stock levels and stock movements
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


        <!-- INVENTORY STATISTICS -->

        <div style="
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:20px;
            margin-top:20px;
        ">


            <!-- TOTAL PRODUCTS -->

            <div class="stat-card">

                <h3>
                    Total Products
                </h3>

                <h2>
                    <?php echo $total_products; ?>
                </h2>

                <p>
                    Active products
                </p>

            </div>


            <!-- TOTAL STOCK -->

            <div class="stat-card">

                <h3>
                    Total Stock
                </h3>

                <h2>
                    <?php echo number_format($total_stock, 2); ?>
                </h2>

                <p>
                    Across all products
                </p>

            </div>


            <!-- LOW STOCK -->

            <div class="stat-card">

                <h3>
                    Low Stock
                </h3>

                <h2>
                    <?php echo $low_stock; ?>
                </h2>

                <p>
                    Products need attention
                </p>

            </div>


            <!-- OUT OF STOCK -->

            <div class="stat-card">

                <h3>
                    Out of Stock
                </h3>

                <h2>
                    <?php echo $out_of_stock; ?>
                </h2>

                <p>
                    Currently unavailable
                </p>

            </div>

        </div>


        <!-- CURRENT STOCK -->

        <div class="stat-card" style="margin-top:20px;">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
            ">

                <div>

                    <h2>
                        Current Stock
                    </h2>

                    <p>
                        Current inventory levels for active products.
                    </p>

                </div>

            </div>


            <div style="
                overflow-x:auto;
                margin-top:20px;
            ">

                <table style="
                    width:100%;
                    border-collapse:collapse;
                    text-align:left;
                ">

                    <thead>

                        <tr>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Code
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Product
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Category
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Stock
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Minimum
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($products as $product): ?>

                        <?php

                        $stock = (float) $product["current_stock"];
                        $minimum = (float) $product["minimum_stock"];

                        if ($stock <= 0) {

                            $status = "Out of Stock";

                        } elseif ($stock <= $minimum) {

                            $status = "Low Stock";

                        } else {

                            $status = "In Stock";

                        }

                        ?>


                        <tr>

                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo htmlspecialchars(
                                    $product["product_code"]
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $product["product_name"]
                                    );
                                    ?>
                                </strong>

                                <br>

                                <small>
                                    <?php
                                    echo htmlspecialchars(
                                        $product["brand"]
                                    );
                                    ?>
                                </small>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo htmlspecialchars(
                                    $product["category_name"]
                                    ?? "Uncategorized"
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                                font-weight:600;
                            ">

                                <?php echo number_format($stock, 2); ?>

                                <?php
                                echo htmlspecialchars(
                                    $product["unit"]
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php echo number_format($minimum, 2); ?>

                                <?php
                                echo htmlspecialchars(
                                    $product["unit"]
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php if ($status === "In Stock"): ?>

                                    <span style="
                                        background:#dcfce7;
                                        color:#166534;
                                        padding:6px 10px;
                                        border-radius:20px;
                                        font-size:12px;
                                        font-weight:600;
                                    ">
                                        In Stock
                                    </span>

                                <?php elseif ($status === "Low Stock"): ?>

                                    <span style="
                                        background:#fef3c7;
                                        color:#92400e;
                                        padding:6px 10px;
                                        border-radius:20px;
                                        font-size:12px;
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
                                        font-size:12px;
                                        font-weight:600;
                                    ">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                    <?php if (count($products) === 0): ?>

                        <tr>

                            <td
                                colspan="6"
                                style="
                                    padding:30px;
                                    text-align:center;
                                    color:#777;
                                "
                            >

                                No active products found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- RECENT STOCK MOVEMENTS -->

        <div class="stat-card" style="margin-top:20px;">

            <h2>
                Recent Stock Movements
            </h2>

            <p>
                Latest inventory activity.
            </p>


            <div style="
                overflow-x:auto;
                margin-top:20px;
            ">

                <table style="
                    width:100%;
                    border-collapse:collapse;
                    text-align:left;
                ">

                    <thead>

                        <tr>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Date
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Product
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Movement
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Quantity
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Before
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                After
                            </th>

                            <th style="padding:12px; border-bottom:1px solid #ddd;">
                                Reference
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($movements as $movement): ?>

                        <tr>

                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo htmlspecialchars(
                                    $movement["created_at"]
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo htmlspecialchars(
                                    $movement["product_name"]
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            "_",
                                            " ",
                                            $movement["movement_type"]
                                        )
                                    )
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo number_format(
                                    (float)$movement["quantity"],
                                    2
                                );
                                ?>

                                <?php
                                echo htmlspecialchars(
                                    $movement["unit"]
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo number_format(
                                    (float)$movement["stock_before"],
                                    2
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                                font-weight:600;
                            ">

                                <?php
                                echo number_format(
                                    (float)$movement["stock_after"],
                                    2
                                );
                                ?>

                            </td>


                            <td style="
                                padding:12px;
                                border-bottom:1px solid #eee;
                            ">

                                <?php
                                echo htmlspecialchars(
                                    $movement["reference_number"]
                                    ?? "-"
                                );
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (count($movements) === 0): ?>

                        <tr>

                            <td
                                colspan="7"
                                style="
                                    padding:30px;
                                    text-align:center;
                                    color:#777;
                                "
                            >

                                No stock movements recorded yet.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

</body>

</html>