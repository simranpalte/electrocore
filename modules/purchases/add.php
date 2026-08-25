<?php

require_once '../../config/database.php';

session_start();

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

$createdBy = (int) $_SESSION["user_id"];

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| FETCH SUPPLIERS
|--------------------------------------------------------------------------
*/

$supplierStmt = $pdo->query("
    SELECT
        id,
        supplier_code,
        company_name
    FROM suppliers
    ORDER BY company_name ASC
");

$suppliers = $supplierStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS
|--------------------------------------------------------------------------
*/

$productStmt = $pdo->query("
    SELECT
        id,
        product_code,
        product_name,
        purchase_price
    FROM products
    ORDER BY product_name ASC
");

$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SAVE PURCHASE
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $purchaseNumber = trim(
        $_POST["purchase_number"] ?? ""
    );

    $supplierId = (int) (
        $_POST["supplier_id"] ?? 0
    );

    $purchaseDate = trim(
        $_POST["purchase_date"] ?? ""
    );

    $supplierInvoiceNumber = trim(
        $_POST["supplier_invoice_number"] ?? ""
    );

    $discount = (float) (
        $_POST["discount"] ?? 0
    );

    $otherCharges = (float) (
        $_POST["other_charges"] ?? 0
    );

    $paidAmount = (float) (
        $_POST["paid_amount"] ?? 0
    );

    $notes = trim(
        $_POST["notes"] ?? ""
    );


    $productIds = $_POST["product_id"] ?? [];

    $quantities = $_POST["quantity"] ?? [];

    $unitPrices = $_POST["unit_price"] ?? [];

    $gstRates = $_POST["gst_rate"] ?? [];


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($purchaseNumber === "") {

        $error = "Purchase number is required.";

    } elseif ($supplierId <= 0) {

        $error = "Please select a supplier.";

    } elseif ($purchaseDate === "") {

        $error = "Purchase date is required.";

    } elseif (empty($productIds)) {

        $error = "Please add at least one product.";

    } elseif (
        count($productIds) !== count($quantities) ||
        count($productIds) !== count($unitPrices) ||
        count($productIds) !== count($gstRates)
    ) {

        $error = "Invalid purchase item data.";
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE PURCHASE TOTALS
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $subtotal = 0;

        $gstAmount = 0;

        $items = [];


        for (
            $i = 0;
            $i < count($productIds);
            $i++
        ) {

            $productId = (int) $productIds[$i];

            $quantity = (float) $quantities[$i];

            $unitPrice = (float) $unitPrices[$i];

            $gstRate = (float) $gstRates[$i];


            if ($productId <= 0) {

                $error = "Please select a valid product.";

                break;
            }


            if ($quantity <= 0) {

                $error =
                    "Product quantity must be greater than zero.";

                break;
            }


            if ($unitPrice < 0) {

                $error =
                    "Unit price cannot be negative.";

                break;
            }


            if ($gstRate < 0) {

                $error =
                    "GST rate cannot be negative.";

                break;
            }


            $baseAmount =
                $quantity * $unitPrice;


            $itemGst =
                $baseAmount * $gstRate / 100;


            $lineTotal =
                $baseAmount + $itemGst;


            $subtotal += $baseAmount;

            $gstAmount += $itemGst;


            $items[] = [

                "product_id" =>
                    $productId,

                "quantity" =>
                    $quantity,

                "unit_price" =>
                    $unitPrice,

                "gst_rate" =>
                    $gstRate,

                "gst_amount" =>
                    $itemGst,

                "line_total" =>
                    $lineTotal
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FINAL CALCULATIONS
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $discount =
            max($discount, 0);


        $otherCharges =
            max($otherCharges, 0);


        $paidAmount =
            max($paidAmount, 0);


        $taxableAmount =
            max(
                $subtotal - $discount,
                0
            );


        $grandTotal =
            $taxableAmount +
            $gstAmount +
            $otherCharges;


        $dueAmount =
            max(
                $grandTotal - $paidAmount,
                0
            );


        /*
        |--------------------------------------------------------------------------
        | PAYMENT STATUS
        |--------------------------------------------------------------------------
        */

        if (
            $paidAmount >= $grandTotal &&
            $grandTotal > 0
        ) {

            $paymentStatus = "paid";

        } elseif ($paidAmount > 0) {

            $paymentStatus = "partial";

        } else {

            $paymentStatus = "pending";
        }


        /*
        |--------------------------------------------------------------------------
        | SAVE USING TRANSACTION
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | INSERT PURCHASE
            |--------------------------------------------------------------------------
            */

            $purchaseStmt = $pdo->prepare("
                INSERT INTO purchases (
                    purchase_number,
                    supplier_id,
                    supplier_invoice_number,
                    purchase_date,
                    subtotal,
                    discount,
                    gst_amount,
                    other_charges,
                    grand_total,
                    paid_amount,
                    due_amount,
                    payment_status,
                    notes,
                    created_by
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");


            $purchaseStmt->execute([

                $purchaseNumber,

                $supplierId,

                $supplierInvoiceNumber !== ""
                    ? $supplierInvoiceNumber
                    : null,

                date(
                    "Y-m-d H:i:s",
                    strtotime($purchaseDate)
                ),

                $subtotal,

                $discount,

                $gstAmount,

                $otherCharges,

                $grandTotal,

                $paidAmount,

                $dueAmount,

                $paymentStatus,

                $notes !== ""
                    ? $notes
                    : null,

                $createdBy
            ]);


            /*
            |--------------------------------------------------------------------------
            | GET PURCHASE ID
            |--------------------------------------------------------------------------
            */

            $purchaseId =
                $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | INSERT PURCHASE ITEMS
            |--------------------------------------------------------------------------
            */

            $itemStmt = $pdo->prepare("
                INSERT INTO purchase_items (
                    purchase_id,
                    product_id,
                    quantity,
                    unit_price,
                    discount,
                    gst_rate,
                    gst_amount,
                    line_total
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");


            /*
            |--------------------------------------------------------------------------
            | UPDATE PRODUCT STOCK
            |--------------------------------------------------------------------------
            */

            $stockStmt = $pdo->prepare("
                UPDATE products
                SET current_stock =
                    current_stock + ?
                WHERE id = ?
            ");


            foreach ($items as $item) {

                /*
                | Insert purchase item
                */

                $itemStmt->execute([

                    $purchaseId,

                    $item["product_id"],

                    $item["quantity"],

                    $item["unit_price"],

                    0,

                    $item["gst_rate"],

                    $item["gst_amount"],

                    $item["line_total"]
                ]);


                /*
                | Increase stock
                */

                $stockStmt->execute([

                    $item["quantity"],

                    $item["product_id"]
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                "Location: index.php?success=purchase_created"
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }


            $error =
                "Purchase could not be saved: " .
                $e->getMessage();
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

<title>
    Add Purchase | ElectroCore
</title>


<style>

/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/*
|--------------------------------------------------------------------------
| BODY
|--------------------------------------------------------------------------
*/

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #090d14;

    color:
        #e8edf5;

    min-height:
        100vh;
}


/*
|--------------------------------------------------------------------------
| MAIN CONTAINER
|--------------------------------------------------------------------------
*/

.container {

    max-width:
        1450px;

    margin:
        0 auto;

    padding:
        32px;
}


/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

.page-header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        flex-start;

    margin-bottom:
        30px;

    gap:
        20px;
}


.page-label {

    color:
        #f5b82e;

    font-size:
        11px;

    font-weight:
        700;

    letter-spacing:
        2px;

    margin-bottom:
        8px;
}


.page-header h1 {

    font-size:
        30px;

    font-weight:
        700;

    color:
        #ffffff;

    margin-bottom:
        7px;
}


.page-header p {

    color:
        #8993a5;

    font-size:
        14px;
}


.back-button {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        8px;

    padding:
        11px 17px;

    border:
        1px solid #273142;

    border-radius:
        8px;

    background:
        #111722;

    color:
        #cbd5e1;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        600;

    transition:
        0.2s;
}


.back-button:hover {

    border-color:
        #f5b82e;

    color:
        #f5b82e;

    background:
        #151b27;
}


/*
|--------------------------------------------------------------------------
| ALERT
|--------------------------------------------------------------------------
*/

.alert {

    padding:
        15px 18px;

    border-radius:
        9px;

    margin-bottom:
        22px;

    font-size:
        14px;

    font-weight:
        600;

    border:
        1px solid;
}


.alert-error {

    background:
        rgba(239, 68, 68, 0.10);

    border-color:
        rgba(239, 68, 68, 0.35);

    color:
        #fca5a5;
}


/*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/

.card {

    background:
        #111722;

    border:
        1px solid #202938;

    border-radius:
        12px;

    padding:
        25px;

    margin-bottom:
        20px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.18);
}


.card-header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        22px;
}


.card-title-area {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;
}


.card-icon {

    width:
        38px;

    height:
        38px;

    border-radius:
        9px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        rgba(245,184,46,0.10);

    border:
        1px solid
        rgba(245,184,46,0.18);

    color:
        #f5b82e;

    font-size:
        17px;

    font-weight:
        700;
}


.card h2 {

    color:
        #ffffff;

    font-size:
        17px;

    font-weight:
        700;
}


.card-subtitle {

    color:
        #6f7b8e;

    font-size:
        12px;

    margin-top:
        4px;
}


/*
|--------------------------------------------------------------------------
| FORM GRID
|--------------------------------------------------------------------------
*/

.grid {

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        20px;
}


.form-group {

    display:
        flex;

    flex-direction:
        column;
}


.form-group.full {

    grid-column:
        1 / -1;
}


label {

    color:
        #aeb8c8;

    font-size:
        12px;

    font-weight:
        600;

    margin-bottom:
        8px;
}


input,
select,
textarea {

    width:
        100%;

    padding:
        12px 13px;

    border:
        1px solid #293445;

    border-radius:
        8px;

    font-size:
        13px;

    background:
        #0c111a;

    color:
        #edf2f7;

    transition:
        0.2s;
}


input::placeholder,
textarea::placeholder {

    color:
        #566174;
}


input:focus,
select:focus,
textarea:focus {

    outline:
        none;

    border-color:
        #f5b82e;

    box-shadow:
        0 0 0 3px
        rgba(245,184,46,0.08);
}


select {

    cursor:
        pointer;
}


select option {

    background:
        #111722;

    color:
        #ffffff;
}


input[readonly] {

    background:
        #171d28;

    color:
        #f5b82e;

    font-weight:
        700;
}


/*
|--------------------------------------------------------------------------
| PURCHASE ITEMS HEADER
|--------------------------------------------------------------------------
*/

.items-header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        20px;
}


.btn {

    border:
        none;

    padding:
        10px 16px;

    border-radius:
        8px;

    cursor:
        pointer;

    font-weight:
        700;

    font-size:
        13px;

    transition:
        0.2s;
}


.btn-add {

    background:
        #f5b82e;

    color:
        #10151d;

    box-shadow:
        0 5px 15px
        rgba(245,184,46,0.15);
}


.btn-add:hover {

    background:
        #ffc84d;

    transform:
        translateY(-1px);
}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.table-wrapper {

    overflow-x:
        auto;

    border:
        1px solid #202938;

    border-radius:
        9px;
}


table {

    width:
        100%;

    border-collapse:
        collapse;

    min-width:
        950px;
}


th,
td {

    border-bottom:
        1px solid #202938;

    padding:
        12px;

    text-align:
        left;

    font-size:
        13px;
}


th {

    background:
        #171e2a;

    color:
        #9da8b8;

    font-size:
        11px;

    text-transform:
        uppercase;

    letter-spacing:
        0.7px;

    font-weight:
        700;
}


td {

    color:
        #d7deea;
}


tbody tr:last-child td {

    border-bottom:
        none;
}


tbody tr:hover td {

    background:
        #151c27;
}


table input,
table select {

    padding:
        9px 10px;

    border-radius:
        6px;

    font-size:
        12px;
}


.line-total {

    font-weight:
        700;

    color:
        #f5b82e !important;

    text-align:
        right;
}


/*
|--------------------------------------------------------------------------
| REMOVE BUTTON
|--------------------------------------------------------------------------
*/

.btn-remove {

    background:
        rgba(239,68,68,0.10);

    color:
        #fca5a5;

    border:
        1px solid
        rgba(239,68,68,0.20);

    padding:
        8px 11px;

    font-size:
        11px;
}


.btn-remove:hover {

    background:
        rgba(239,68,68,0.18);

    color:
        #fecaca;
}


/*
|--------------------------------------------------------------------------
| PAYMENT SECTION
|--------------------------------------------------------------------------
*/

.payment-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        20px;
}


/*
|--------------------------------------------------------------------------
| TOTALS
|--------------------------------------------------------------------------
*/

.totals {

    margin-top:
        28px;

    margin-left:
        auto;

    width:
        390px;

    background:
        #0c111a;

    border:
        1px solid #202938;

    border-radius:
        10px;

    padding:
        18px 20px;
}


.total-row {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    padding:
        9px 0;

    color:
        #9ca7b8;

    font-size:
        13px;
}


.total-row strong {

    color:
        #e8edf5;
}


.grand-total {

    border-top:
        1px solid #2b3545;

    border-bottom:
        1px solid #2b3545;

    margin:
        8px 0;

    padding:
        14px 0;

    color:
        #ffffff;

    font-size:
        17px;

    font-weight:
        700;
}


.grand-total strong {

    color:
        #f5b82e;

    font-size:
        20px;
}


.due-row strong {

    color:
        #f87171;
}


.paid-row strong {

    color:
        #4ade80;
}


/*
|--------------------------------------------------------------------------
| NOTES
|--------------------------------------------------------------------------
*/

textarea {

    resize:
        vertical;

    min-height:
        110px;

    line-height:
        1.5;
}


/*
|--------------------------------------------------------------------------
| ACTIONS
|--------------------------------------------------------------------------
*/

.actions {

    display:
        flex;

    justify-content:
        flex-end;

    align-items:
        center;

    gap:
        12px;

    margin-top:
        25px;

    padding-bottom:
        20px;
}


.btn-cancel {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        12px 20px;

    border:
        1px solid #293445;

    border-radius:
        8px;

    background:
        #111722;

    color:
        #aeb8c8;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        700;
}


.btn-cancel:hover {

    background:
        #181f2b;

    color:
        #ffffff;
}


.btn-save {

    background:
        #f5b82e;

    color:
        #10151d;

    padding:
        12px 24px;

    box-shadow:
        0 6px 18px
        rgba(245,184,46,0.16);
}


.btn-save:hover {

    background:
        #ffc84d;

    transform:
        translateY(-1px);
}


/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.page-footer {

    display:
        flex;

    justify-content:
        space-between;

    color:
        #566174;

    font-size:
        11px;

    padding:
        5px 2px 10px;
}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 1000px) {

    .grid,
    .payment-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 700px) {

    .container {

        padding:
            18px;
    }


    .page-header {

        flex-direction:
            column;
    }


    .grid,
    .payment-grid {

        grid-template-columns:
            1fr;
    }


    .form-group.full {

        grid-column:
            auto;
    }


    .totals {

        width:
            100%;
    }


    .items-header {

        align-items:
            flex-start;

        gap:
            12px;

        flex-direction:
            column;
    }


    .actions {

        flex-direction:
            column-reverse;
    }


    .btn-cancel,
    .btn-save {

        width:
            100%;
    }


    .page-footer {

        flex-direction:
            column;

        gap:
            5px;
    }

}

</style>

</head>


<body>


<div class="container">


    <!--
    |--------------------------------------------------------------------------
    | PAGE HEADER
    |--------------------------------------------------------------------------
    -->

    <div class="page-header">

        <div>

            <div class="page-label">
                PROCUREMENT
            </div>

            <h1>
                Add Purchase
            </h1>

            <p>
                Create a new supplier purchase and update inventory.
            </p>

        </div>


        <a
            href="index.php"
            class="back-button"
        >
            ← Back to Purchases
        </a>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | ERROR MESSAGE
    |--------------------------------------------------------------------------
    -->

    <?php if ($error !== ""): ?>

        <div class="alert alert-error">

            ⚠
            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <!--
        |--------------------------------------------------------------------------
        | PURCHASE INFORMATION
        |--------------------------------------------------------------------------
        -->

        <div class="card">

            <div class="card-header">

                <div class="card-title-area">

                    <div class="card-icon">
                        #
                    </div>

                    <div>

                        <h2>
                            Purchase Information
                        </h2>

                        <div class="card-subtitle">
                            Basic supplier and purchase details
                        </div>

                    </div>

                </div>

            </div>


            <div class="grid">


                <!-- Purchase Number -->

                <div class="form-group">

                    <label>
                        Purchase Number
                    </label>

                    <input
                        type="text"
                        name="purchase_number"
                        value="PUR-<?= date('YmdHis') ?>"
                        readonly
                    >

                </div>


                <!-- Supplier -->

                <div class="form-group">

                    <label>
                        Supplier *
                    </label>

                    <select
                        name="supplier_id"
                        required
                    >

                        <option value="">
                            Select Supplier
                        </option>


                        <?php foreach ($suppliers as $supplier): ?>

                            <option
                                value="<?= (int) $supplier['id'] ?>"
                                <?= (
                                    isset($_POST['supplier_id']) &&
                                    $_POST['supplier_id'] ==
                                    $supplier['id']
                                )
                                ? 'selected'
                                : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $supplier['company_name']
                                ) ?>

                                —
                                <?= htmlspecialchars(
                                    $supplier['supplier_code']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Purchase Date -->

                <div class="form-group">

                    <label>
                        Purchase Date *
                    </label>

                    <input
                        type="datetime-local"
                        name="purchase_date"
                        value="<?= htmlspecialchars(
                            $_POST['purchase_date']
                            ?? date('Y-m-d\TH:i')
                        ) ?>"
                        required
                    >

                </div>


                <!-- Supplier Invoice -->

                <div class="form-group">

                    <label>
                        Supplier Invoice Number
                    </label>

                    <input
                        type="text"
                        name="supplier_invoice_number"
                        placeholder="Optional invoice number"
                        value="<?= htmlspecialchars(
                            $_POST['supplier_invoice_number']
                            ?? ''
                        ) ?>"
                    >

                </div>


            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | PURCHASE ITEMS
        |--------------------------------------------------------------------------
        -->

        <div class="card">


            <div class="items-header">


                <div class="card-title-area">

                    <div class="card-icon">
                        ◈
                    </div>

                    <div>

                        <h2>
                            Purchase Items
                        </h2>

                        <div class="card-subtitle">
                            Add products received from the supplier
                        </div>

                    </div>

                </div>


                <button
                    type="button"
                    class="btn btn-add"
                    onclick="addItem()"
                >
                    + Add Product
                </button>


            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Product
                            </th>

                            <th width="120">
                                Quantity
                            </th>

                            <th width="140">
                                Unit Price
                            </th>

                            <th width="110">
                                GST %
                            </th>

                            <th width="150">
                                Line Total
                            </th>

                            <th width="90">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody id="itemsBody"></tbody>

                </table>

            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | PAYMENT DETAILS
        |--------------------------------------------------------------------------
        -->

        <div class="card">


            <div class="card-header">

                <div class="card-title-area">

                    <div class="card-icon">
                        ₹
                    </div>

                    <div>

                        <h2>
                            Payment Details
                        </h2>

                        <div class="card-subtitle">
                            Discounts, charges and payment information
                        </div>

                    </div>

                </div>

            </div>


            <div class="payment-grid">


                <!-- Discount -->

                <div class="form-group">

                    <label>
                        Discount
                    </label>

                    <input
                        type="number"
                        id="discount"
                        name="discount"
                        value="<?= htmlspecialchars(
                            $_POST['discount'] ?? '0'
                        ) ?>"
                        min="0"
                        step="0.01"
                        oninput="calculateTotals()"
                    >

                </div>


                <!-- Other Charges -->

                <div class="form-group">

                    <label>
                        Other Charges
                    </label>

                    <input
                        type="number"
                        id="other_charges"
                        name="other_charges"
                        value="<?= htmlspecialchars(
                            $_POST['other_charges'] ?? '0'
                        ) ?>"
                        min="0"
                        step="0.01"
                        oninput="calculateTotals()"
                    >

                </div>


                <!-- Paid Amount -->

                <div class="form-group">

                    <label>
                        Paid Amount
                    </label>

                    <input
                        type="number"
                        id="paid_amount"
                        name="paid_amount"
                        value="<?= htmlspecialchars(
                            $_POST['paid_amount'] ?? '0'
                        ) ?>"
                        min="0"
                        step="0.01"
                        oninput="calculateTotals()"
                    >

                </div>


            </div>


            <!-- TOTALS -->

            <div class="totals">


                <div class="total-row">

                    <span>
                        Subtotal
                    </span>

                    <strong id="subtotal">
                        ₹0.00
                    </strong>

                </div>


                <div class="total-row">

                    <span>
                        Discount
                    </span>

                    <strong id="discountDisplay">
                        ₹0.00
                    </strong>

                </div>


                <div class="total-row">

                    <span>
                        GST
                    </span>

                    <strong id="gstDisplay">
                        ₹0.00
                    </strong>

                </div>


                <div class="total-row">

                    <span>
                        Other Charges
                    </span>

                    <strong id="chargesDisplay">
                        ₹0.00
                    </strong>

                </div>


                <div class="total-row grand-total">

                    <span>
                        Grand Total
                    </span>

                    <strong id="grandTotal">
                        ₹0.00
                    </strong>

                </div>


                <div class="total-row paid-row">

                    <span>
                        Paid Amount
                    </span>

                    <strong id="paidDisplay">
                        ₹0.00
                    </strong>

                </div>


                <div class="total-row due-row">

                    <span>
                        Due Amount
                    </span>

                    <strong id="dueAmount">
                        ₹0.00
                    </strong>

                </div>


            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | NOTES
        |--------------------------------------------------------------------------
        -->

        <div class="card">


            <div class="card-header">

                <div class="card-title-area">

                    <div class="card-icon">
                        ≡
                    </div>

                    <div>

                        <h2>
                            Notes
                        </h2>

                        <div class="card-subtitle">
                            Add any additional purchase information
                        </div>

                    </div>

                </div>

            </div>


            <div class="form-group">

                <textarea
                    name="notes"
                    placeholder="Optional purchase notes..."
                ><?= htmlspecialchars(
                    $_POST['notes'] ?? ''
                ) ?></textarea>

            </div>


        </div>


        <!--
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        -->

        <div class="actions">


            <a
                href="index.php"
                class="btn-cancel"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="btn btn-save"
            >
                ✓ Save Purchase
            </button>


        </div>


    </form>


    <!-- FOOTER -->

    <div class="page-footer">

        <span>
            © <?= date("Y") ?> ElectroCore
        </span>

        <span>
            Billing & Inventory Management System
        </span>

    </div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| PRODUCTS FROM PHP
|--------------------------------------------------------------------------
*/

const products =
    <?= json_encode($products) ?>;


/*
|--------------------------------------------------------------------------
| POSTED ITEMS
|--------------------------------------------------------------------------
*/

const postedProductIds =
    <?= json_encode(
        $_POST['product_id'] ?? []
    ) ?>;


const postedQuantities =
    <?= json_encode(
        $_POST['quantity'] ?? []
    ) ?>;


const postedUnitPrices =
    <?= json_encode(
        $_POST['unit_price'] ?? []
    ) ?>;


const postedGstRates =
    <?= json_encode(
        $_POST['gst_rate'] ?? []
    ) ?>;


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value)

        .replace(
            /&/g,
            "&amp;"
        )

        .replace(
            /</g,
            "&lt;"
        )

        .replace(
            />/g,
            "&gt;"
        )

        .replace(
            /"/g,
            "&quot;"
        )

        .replace(
            /'/g,
            "&#039;"
        );
}


/*
|--------------------------------------------------------------------------
| ADD ITEM
|--------------------------------------------------------------------------
*/

function addItem(
    selectedProduct = "",
    quantity = 1,
    unitPrice = 0,
    gstRate = 18
) {

    const tbody =
        document.getElementById(
            "itemsBody"
        );


    const row =
        document.createElement("tr");


    let productOptions =
        '<option value="">Select Product</option>';


    products.forEach(
        product => {

            const selected =
                String(product.id) ===
                String(selectedProduct)
                    ? "selected"
                    : "";


            productOptions += `

                <option
                    value="${escapeHtml(product.id)}"
                    data-price="${escapeHtml(product.purchase_price)}"
                    ${selected}
                >

                    ${escapeHtml(product.product_name)}
                    (${escapeHtml(product.product_code)})

                </option>

            `;
        }
    );


    row.innerHTML = `

        <td>

            <select
                name="product_id[]"
                onchange="productChanged(this)"
                required
            >

                ${productOptions}

            </select>

        </td>


        <td>

            <input
                type="number"
                name="quantity[]"
                class="quantity"
                value="${escapeHtml(quantity)}"
                min="0.01"
                step="0.01"
                oninput="calculateTotals()"
                required
            >

        </td>


        <td>

            <input
                type="number"
                name="unit_price[]"
                class="unit-price"
                value="${escapeHtml(unitPrice)}"
                min="0"
                step="0.01"
                oninput="calculateTotals()"
                required
            >

        </td>


        <td>

            <input
                type="number"
                name="gst_rate[]"
                class="gst-rate"
                value="${escapeHtml(gstRate)}"
                min="0"
                step="0.01"
                oninput="calculateTotals()"
            >

        </td>


        <td>

            <input
                type="text"
                class="line-total"
                value="₹0.00"
                readonly
            >

        </td>


        <td>

            <button
                type="button"
                class="btn btn-remove"
                onclick="removeItem(this)"
            >
                Remove
            </button>

        </td>

    `;


    tbody.appendChild(row);


    calculateTotals();
}


/*
|--------------------------------------------------------------------------
| PRODUCT CHANGED
|--------------------------------------------------------------------------
*/

function productChanged(select) {

    const row =
        select.closest("tr");


    const selectedOption =
        select.options[
            select.selectedIndex
        ];


    const price =
        selectedOption.dataset.price ||
        0;


    row.querySelector(
        ".unit-price"
    ).value = price;


    calculateTotals();
}


/*
|--------------------------------------------------------------------------
| REMOVE ITEM
|--------------------------------------------------------------------------
*/

function removeItem(button) {

    const rows =
        document.querySelectorAll(
            "#itemsBody tr"
        );


    /*
    | Don't allow the last row
    | to disappear completely.
    */

    if (rows.length <= 1) {

        const row =
            button.closest("tr");

        row.querySelector(
            "select"
        ).value = "";

        row.querySelector(
            ".quantity"
        ).value = 1;

        row.querySelector(
            ".unit-price"
        ).value = 0;

        row.querySelector(
            ".gst-rate"
        ).value = 18;

        calculateTotals();

        return;
    }


    button
        .closest("tr")
        .remove();


    calculateTotals();
}


/*
|--------------------------------------------------------------------------
| CALCULATE TOTALS
|--------------------------------------------------------------------------
*/

function calculateTotals() {

    let subtotal = 0;

    let gstTotal = 0;


    document
        .querySelectorAll(
            "#itemsBody tr"
        )
        .forEach(
            row => {

                const quantity =
                    parseFloat(
                        row.querySelector(
                            ".quantity"
                        ).value
                    ) || 0;


                const unitPrice =
                    parseFloat(
                        row.querySelector(
                            ".unit-price"
                        ).value
                    ) || 0;


                const gstRate =
                    parseFloat(
                        row.querySelector(
                            ".gst-rate"
                        ).value
                    ) || 0;


                const baseAmount =
                    quantity *
                    unitPrice;


                const gstAmount =
                    baseAmount *
                    gstRate /
                    100;


                const lineTotal =
                    baseAmount +
                    gstAmount;


                subtotal +=
                    baseAmount;


                gstTotal +=
                    gstAmount;


                row.querySelector(
                    ".line-total"
                ).value =
                    "₹" +
                    lineTotal.toFixed(2);
            }
        );


    const discount =
        parseFloat(
            document.getElementById(
                "discount"
            ).value
        ) || 0;


    const otherCharges =
        parseFloat(
            document.getElementById(
                "other_charges"
            ).value
        ) || 0;


    const paidAmount =
        parseFloat(
            document.getElementById(
                "paid_amount"
            ).value
        ) || 0;


    const taxableAmount =
        Math.max(
            subtotal -
            discount,
            0
        );


    const grandTotal =
        taxableAmount +
        gstTotal +
        otherCharges;


    const dueAmount =
        Math.max(
            grandTotal -
            paidAmount,
            0
        );


    /*
    |--------------------------------------------------------------------------
    | DISPLAY TOTALS
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        "subtotal"
    ).textContent =
        "₹" +
        subtotal.toFixed(2);


    document.getElementById(
        "discountDisplay"
    ).textContent =
        "₹" +
        discount.toFixed(2);


    document.getElementById(
        "gstDisplay"
    ).textContent =
        "₹" +
        gstTotal.toFixed(2);


    document.getElementById(
        "chargesDisplay"
    ).textContent =
        "₹" +
        otherCharges.toFixed(2);


    document.getElementById(
        "grandTotal"
    ).textContent =
        "₹" +
        grandTotal.toFixed(2);


    document.getElementById(
        "paidDisplay"
    ).textContent =
        "₹" +
        paidAmount.toFixed(2);


    document.getElementById(
        "dueAmount"
    ).textContent =
        "₹" +
        dueAmount.toFixed(2);
}


/*
|--------------------------------------------------------------------------
| INITIAL PRODUCT ROW
|--------------------------------------------------------------------------
*/

if (
    postedProductIds.length > 0
) {

    for (
        let i = 0;
        i < postedProductIds.length;
        i++
    ) {

        addItem(

            postedProductIds[i],

            postedQuantities[i] ??
                1,

            postedUnitPrices[i] ??
                0,

            postedGstRates[i] ??
                18
        );
    }

} else {

    addItem();
}


/*
|--------------------------------------------------------------------------
| INITIAL CALCULATION
|--------------------------------------------------------------------------
*/

calculateTotals();

</script>


</body>

</html>