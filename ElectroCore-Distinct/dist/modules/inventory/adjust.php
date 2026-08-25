<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$full_name = $_SESSION["full_name"];
$role = $_SESSION["role"];

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Handle Stock Adjustment
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = isset($_POST["product_id"])
        ? (int) $_POST["product_id"]
        : 0;

    $movement_type = $_POST["movement_type"] ?? "";

    $quantity = isset($_POST["quantity"])
        ? (float) $_POST["quantity"]
        : 0;

    $reference_number = trim(
        $_POST["reference_number"] ?? ""
    );

    $notes = trim(
        $_POST["notes"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $allowed_types = [
        "opening_stock",
        "purchase",
        "adjustment_in",
        "adjustment_out",
        "damage",
        "sale_return"
    ];

    if ($product_id <= 0) {

        $error = "Please select a product.";

    } elseif (!in_array($movement_type, $allowed_types, true)) {

        $error = "Please select a valid movement type.";

    } elseif ($quantity <= 0) {

        $error = "Quantity must be greater than zero.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Start Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Get Current Stock
            |--------------------------------------------------------------------------
            */

            $product_stmt = $pdo->prepare("
                SELECT
                    id,
                    product_name,
                    current_stock
                FROM products
                WHERE id = ?
                  AND status = 'active'
                FOR UPDATE
            ");

            $product_stmt->execute([
                $product_id
            ]);

            $product = $product_stmt->fetch(
                PDO::FETCH_ASSOC
            );


            if (!$product) {

                throw new Exception(
                    "Product not found or inactive."
                );

            }


            $stock_before = (float) $product["current_stock"];


            /*
            |--------------------------------------------------------------------------
            | Determine Stock Change
            |--------------------------------------------------------------------------
            */

            $increase_types = [
                "opening_stock",
                "purchase",
                "adjustment_in",
                "sale_return"
            ];

            $decrease_types = [
                "adjustment_out",
                "damage"
            ];


            if (in_array(
                $movement_type,
                $increase_types,
                true
            )) {

                $stock_after =
                    $stock_before + $quantity;

            } else {

                $stock_after =
                    $stock_before - $quantity;

            }


            /*
            |--------------------------------------------------------------------------
            | Prevent Negative Stock
            |--------------------------------------------------------------------------
            */

            if ($stock_after < 0) {

                throw new Exception(
                    "Insufficient stock. Current stock is "
                    . number_format($stock_before, 2)
                    . "."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Update Product Stock
            |--------------------------------------------------------------------------
            */

            $update_stmt = $pdo->prepare("
                UPDATE products
                SET current_stock = ?
                WHERE id = ?
            ");

            $update_stmt->execute([
                $stock_after,
                $product_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Record Stock Movement
            |--------------------------------------------------------------------------
            */

            $movement_stmt = $pdo->prepare("
                INSERT INTO stock_movements
                (
                    product_id,
                    movement_type,
                    reference_id,
                    reference_number,
                    quantity,
                    stock_before,
                    stock_after,
                    notes,
                    created_by
                )
                VALUES
                (
                    ?,
                    ?,
                    NULL,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $movement_stmt->execute([
                $product_id,
                $movement_type,
                $reference_number !== ""
                    ? $reference_number
                    : null,
                $quantity,
                $stock_before,
                $stock_after,
                $notes !== ""
                    ? $notes
                    : null,
                $_SESSION["user_id"]
            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            $message =
                "Stock updated successfully. New stock: "
                . number_format($stock_after, 2);


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch Active Products
|--------------------------------------------------------------------------
*/

$product_stmt = $pdo->query("
    SELECT
        id,
        product_code,
        product_name,
        current_stock,
        unit
    FROM products
    WHERE status = 'active'
    ORDER BY product_name ASC
");

$products = $product_stmt->fetchAll(
    PDO::FETCH_ASSOC
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

    <title>ElectroCore | Stock Adjustment</title>

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css"
    >

</head>


<body>

<div class="dashboard">

    <?php
    require_once __DIR__ . "/../../includes/sidebar.php";
    ?>


    <main class="main-content">


        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <h1>
                    Stock Adjustment
                </h1>

                <p>
                    Add or remove inventory stock
                </p>

            </div>


            <div class="user-info">

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $full_name
                    );
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


        <!-- SUCCESS MESSAGE -->

        <?php if ($message !== ""): ?>

            <div style="
                background:#dcfce7;
                color:#166534;
                padding:15px 18px;
                border-radius:8px;
                margin-top:20px;
                font-weight:600;
            ">

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if ($error !== ""): ?>

            <div style="
                background:#fee2e2;
                color:#991b1b;
                padding:15px 18px;
                border-radius:8px;
                margin-top:20px;
                font-weight:600;
            ">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <div
            class="stat-card"
            style="margin-top:20px;"
        >

            <h2>
                Adjust Stock
            </h2>

            <p>
                Select a product and record the inventory movement.
            </p>


            <form
                method="POST"
                style="
                    margin-top:25px;
                    max-width:700px;
                "
            >


                <!-- PRODUCT -->

                <div style="margin-bottom:18px;">

                    <label
                        for="product_id"
                        style="
                            display:block;
                            font-weight:600;
                            margin-bottom:7px;
                        "
                    >
                        Product
                    </label>


                    <select
                        name="product_id"
                        id="product_id"
                        required
                        style="
                            width:100%;
                            padding:12px;
                            border:1px solid #ddd;
                            border-radius:8px;
                        "
                    >

                        <option value="">
                            Select Product
                        </option>


                        <?php foreach ($products as $product): ?>

                            <option
                                value="<?php echo (int)$product["id"]; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $product["product_code"]
                                );
                                ?>

                                -
                                <?php
                                echo htmlspecialchars(
                                    $product["product_name"]
                                );
                                ?>

                                (
                                <?php
                                echo number_format(
                                    (float)$product["current_stock"],
                                    2
                                );
                                ?>

                                <?php
                                echo htmlspecialchars(
                                    $product["unit"]
                                );
                                ?>

                                )

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- MOVEMENT TYPE -->

                <div style="margin-bottom:18px;">

                    <label
                        for="movement_type"
                        style="
                            display:block;
                            font-weight:600;
                            margin-bottom:7px;
                        "
                    >
                        Movement Type
                    </label>


                    <select
                        name="movement_type"
                        id="movement_type"
                        required
                        style="
                            width:100%;
                            padding:12px;
                            border:1px solid #ddd;
                            border-radius:8px;
                        "
                    >

                        <option value="">
                            Select Movement
                        </option>

                        <option value="purchase">
                            Purchase — Stock In
                        </option>

                        <option value="adjustment_in">
                            Stock Adjustment In
                        </option>

                        <option value="adjustment_out">
                            Stock Adjustment Out
                        </option>

                        <option value="damage">
                            Damaged Stock — Stock Out
                        </option>

                        <option value="sale_return">
                            Sale Return — Stock In
                        </option>

                        <option value="opening_stock">
                            Opening Stock
                        </option>

                    </select>

                </div>


                <!-- QUANTITY -->

                <div style="margin-bottom:18px;">

                    <label
                        for="quantity"
                        style="
                            display:block;
                            font-weight:600;
                            margin-bottom:7px;
                        "
                    >
                        Quantity
                    </label>


                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        step="0.01"
                        min="0.01"
                        required
                        placeholder="Enter quantity"
                        style="
                            width:100%;
                            padding:12px;
                            border:1px solid #ddd;
                            border-radius:8px;
                        "
                    >

                </div>


                <!-- REFERENCE -->

                <div style="margin-bottom:18px;">

                    <label
                        for="reference_number"
                        style="
                            display:block;
                            font-weight:600;
                            margin-bottom:7px;
                        "
                    >
                        Reference Number
                    </label>


                    <input
                        type="text"
                        name="reference_number"
                        id="reference_number"
                        maxlength="50"
                        placeholder="Example: PO-0001"
                        style="
                            width:100%;
                            padding:12px;
                            border:1px solid #ddd;
                            border-radius:8px;
                        "
                    >

                </div>


                <!-- NOTES -->

                <div style="margin-bottom:22px;">

                    <label
                        for="notes"
                        style="
                            display:block;
                            font-weight:600;
                            margin-bottom:7px;
                        "
                    >
                        Notes
                    </label>


                    <textarea
                        name="notes"
                        id="notes"
                        rows="4"
                        maxlength="255"
                        placeholder="Optional notes"
                        style="
                            width:100%;
                            padding:12px;
                            border:1px solid #ddd;
                            border-radius:8px;
                            resize:vertical;
                        "
                    ></textarea>

                </div>


                <!-- BUTTONS -->

                <div
                    style="
                        display:flex;
                        gap:10px;
                    "
                >

                    <button
                        type="submit"
                        style="
                            background:#0ea5e9;
                            color:white;
                            padding:12px 20px;
                            border:none;
                            border-radius:8px;
                            cursor:pointer;
                            font-weight:600;
                        "
                    >
                        Update Stock
                    </button>


                    <a
                        href="index.php"
                        style="
                            background:#e5e7eb;
                            color:#111827;
                            padding:12px 20px;
                            border-radius:8px;
                            text-decoration:none;
                            font-weight:600;
                        "
                    >
                        Cancel
                    </a>

                </div>


            </form>

        </div>

    </main>

</div>

</body>

</html>