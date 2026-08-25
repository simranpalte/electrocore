<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$product_id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Check Product Exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, product_name
    FROM products
    WHERE id = ?
");

$stmt->execute([$product_id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Deactivate Product
|--------------------------------------------------------------------------
*/

$update = $pdo->prepare("
    UPDATE products
    SET status = 'inactive'
    WHERE id = ?
");

$update->execute([$product_id]);


/*
|--------------------------------------------------------------------------
| Return to Products
|--------------------------------------------------------------------------
*/

header("Location: index.php");
exit;

?>