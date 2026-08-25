<?php

require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id = $_POST['id'] ?? null;

$payment_number = trim($_POST['payment_number'] ?? '');
$payment_type = $_POST['payment_type'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';

$customer_id = !empty($_POST['customer_id'])
    ? (int) $_POST['customer_id']
    : null;

$supplier_id = !empty($_POST['supplier_id'])
    ? (int) $_POST['supplier_id']
    : null;

$sale_id = !empty($_POST['sale_id'])
    ? (int) $_POST['sale_id']
    : null;

$purchase_id = !empty($_POST['purchase_id'])
    ? (int) $_POST['purchase_id']
    : null;

$amount = (float) ($_POST['amount'] ?? 0);

$reference_number = trim(
    $_POST['reference_number'] ?? ''
);

$payment_date = $_POST['payment_date'] ?? date('Y-m-d H:i:s');

$notes = trim(
    $_POST['notes'] ?? ''
);


/*
|--------------------------------------------------------------------------
| Basic Validation
|--------------------------------------------------------------------------
*/

if (
    empty($payment_number) ||
    empty($payment_type) ||
    empty($payment_method) ||
    $amount <= 0
) {

    die("Please fill all required payment fields.");

}


/*
|--------------------------------------------------------------------------
| Convert Date
|--------------------------------------------------------------------------
*/

$payment_date = date(
    'Y-m-d H:i:s',
    strtotime($payment_date)
);


/*
|--------------------------------------------------------------------------
| Insert / Update
|--------------------------------------------------------------------------
*/

try {

    if ($id) {

        /*
        |--------------------------------------------------------------------------
        | Update Existing Payment
        |--------------------------------------------------------------------------
        */

        $sql = "
            UPDATE payments
            SET
                payment_number = ?,
                sale_id = ?,
                purchase_id = ?,
                customer_id = ?,
                supplier_id = ?,
                payment_type = ?,
                payment_method = ?,
                amount = ?,
                reference_number = ?,
                payment_date = ?,
                notes = ?
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $payment_number,
            $sale_id,
            $purchase_id,
            $customer_id,
            $supplier_id,
            $payment_type,
            $payment_method,
            $amount,
            $reference_number ?: null,
            $payment_date,
            $notes ?: null,
            $id
        ]);

    } else {

        /*
        |--------------------------------------------------------------------------
        | Insert New Payment
        |--------------------------------------------------------------------------
        |
        | received_by is required in your database.
        | We use the logged-in user if available.
        |
        */

        $received_by = $_SESSION['user_id'] ?? 1;

        $sql = "
            INSERT INTO payments
            (
                payment_number,
                sale_id,
                purchase_id,
                customer_id,
                supplier_id,
                payment_type,
                payment_method,
                amount,
                reference_number,
                payment_date,
                notes,
                received_by
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $payment_number,
            $sale_id,
            $purchase_id,
            $customer_id,
            $supplier_id,
            $payment_type,
            $payment_method,
            $amount,
            $reference_number ?: null,
            $payment_date,
            $notes ?: null,
            $received_by
        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    header("Location: index.php");
    exit;


} catch (PDOException $e) {

    die(
        "Database Error: " .
        htmlspecialchars($e->getMessage())
    );

}