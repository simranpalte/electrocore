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
        id,
        product_code,
        product_name,
        category_id,
        brand,
        unit,
        gst_rate,
        purchase_price,
        selling_price,
        minimum_stock
    FROM products
    WHERE id = ?
");

$stmt->execute([$product_id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

/*
|--------------------------------------------------------------------------
| Fetch Categories
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
| Update Product
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_code = trim($_POST["product_code"]);
    $product_name = trim($_POST["product_name"]);
    $category_id = (int) $_POST["category_id"];
    $brand = trim($_POST["brand"]);
    $unit = trim($_POST["unit"]);
    $gst_rate = (float) $_POST["gst_rate"];
    $purchase_price = (float) $_POST["purchase_price"];
    $selling_price = (float) $_POST["selling_price"];
    $minimum_stock = (int) $_POST["minimum_stock"];

    if (
        $product_code === "" ||
        $product_name === "" ||
        $category_id <= 0 ||
        $brand === "" ||
        $unit === ""
    ) {

        $error = "Please fill all required fields.";

    } else {

        $update = $pdo->prepare("
            UPDATE products
            SET
                product_code = ?,
                product_name = ?,
                category_id = ?,
                brand = ?,
                unit = ?,
                gst_rate = ?,
                purchase_price = ?,
                selling_price = ?,
                minimum_stock = ?
            WHERE id = ?
        ");

        $update->execute([
            $product_code,
            $product_name,
            $category_id,
            $brand,
            $unit,
            $gst_rate,
            $purchase_price,
            $selling_price,
            $minimum_stock,
            $product_id
        ]);

        header("Location: index.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ElectroCore | Edit Product</title>

    <link rel="stylesheet" href="../../assets/css/dashboard.css">

</head>

<body>

<div class="dashboard">

    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>

    <main class="main-content">

        <!-- TOPBAR -->

        <div class="topbar">

            <div class="page-title">

                <h1>Edit Product</h1>

                <p>
                    Update product information
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


        <!-- FORM -->

        <div class="stat-card" style="margin-top:20px;">

            <h2>Edit Product</h2>

            <p>
                Update product details without changing current stock.
            </p>


            <?php if (isset($error)): ?>

                <div style="
                    background:#fee2e2;
                    color:#991b1b;
                    padding:12px;
                    border-radius:8px;
                    margin:15px 0;
                ">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <form method="POST" style="margin-top:20px;">

                <!-- PRODUCT CODE -->

                <div style="margin-bottom:18px;">

                    <label>
                        Product Code
                    </label>

                    <input
                        type="text"
                        name="product_code"
                        value="<?php echo htmlspecialchars($product["product_code"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- PRODUCT NAME -->

                <div style="margin-bottom:18px;">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        value="<?php echo htmlspecialchars($product["product_name"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- CATEGORY -->

                <div style="margin-bottom:18px;">

                    <label>
                        Category
                    </label>

                    <select
                        name="category_id"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?php echo (int)$category["id"]; ?>"
                                <?php
                                echo (
                                    $product["category_id"] == $category["id"]
                                )
                                ? "selected"
                                : "";
                                ?>
                            >

                                <?php echo htmlspecialchars($category["name"]); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- BRAND -->

                <div style="margin-bottom:18px;">

                    <label>
                        Brand
                    </label>

                    <input
                        type="text"
                        name="brand"
                        value="<?php echo htmlspecialchars($product["brand"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- UNIT -->

                <div style="margin-bottom:18px;">

                    <label>
                        Unit
                    </label>

                    <input
                        type="text"
                        name="unit"
                        value="<?php echo htmlspecialchars($product["unit"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- GST -->

                <div style="margin-bottom:18px;">

                    <label>
                        GST Rate (%)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="gst_rate"
                        value="<?php echo htmlspecialchars($product["gst_rate"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- PURCHASE PRICE -->

                <div style="margin-bottom:18px;">

                    <label>
                        Purchase Price
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="purchase_price"
                        value="<?php echo htmlspecialchars($product["purchase_price"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- SELLING PRICE -->

                <div style="margin-bottom:18px;">

                    <label>
                        Selling Price
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="selling_price"
                        value="<?php echo htmlspecialchars($product["selling_price"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- MINIMUM STOCK -->

                <div style="margin-bottom:20px;">

                    <label>
                        Minimum Stock Level
                    </label>

                    <input
                        type="number"
                        name="minimum_stock"
                        value="<?php echo htmlspecialchars($product["minimum_stock"]); ?>"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            margin-top:6px;
                            border:1px solid #ccc;
                            border-radius:7px;
                        "
                    >

                </div>


                <!-- BUTTONS -->

                <button
                    type="submit"
                    style="
                        background:#0ea5e9;
                        color:white;
                        border:none;
                        padding:12px 20px;
                        border-radius:8px;
                        font-weight:600;
                        cursor:pointer;
                    "
                >
                    Save Changes
                </button>


                <a
                    href="index.php"
                    style="
                        margin-left:10px;
                        padding:12px 20px;
                        border-radius:8px;
                        text-decoration:none;
                        background:#e5e7eb;
                        color:#111827;
                        font-weight:600;
                    "
                >
                    Cancel
                </a>

            </form>

        </div>

    </main>

</div>

</body>

</html>