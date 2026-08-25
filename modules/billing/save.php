<?php

require_once '../../config/database.php';

session_start();

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);

    exit;
}

$createdBy = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Read Form Data
|--------------------------------------------------------------------------
*/

$invoiceNumber = trim($_POST["invoice_number"] ?? "");

$customerId = (int) ($_POST["customer_id"] ?? 0);

$billType = trim($_POST["bill_type"] ?? "tax_invoice");

$discount = (float) ($_POST["discount"] ?? 0);

$paidAmount = (float) ($_POST["paid_amount"] ?? 0);

$notes = trim($_POST["notes"] ?? "");


$productIds = $_POST["product_id"] ?? [];

$quantities = $_POST["quantity"] ?? [];

$unitPrices = $_POST["unit_price"] ?? [];

$gstRates = $_POST["gst_rate"] ?? [];


/*
|--------------------------------------------------------------------------
| Basic Validation
|--------------------------------------------------------------------------
*/

if ($invoiceNumber === "") {

    echo json_encode([
        "success" => false,
        "message" => "Invoice number is required."
    ]);

    exit;
}


if (
    $billType !== "tax_invoice" &&
    $billType !== "retail_receipt"
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid bill type."
    ]);

    exit;
}


if (empty($productIds)) {

    echo json_encode([
        "success" => false,
        "message" => "Please add at least one product."
    ]);

    exit;
}


if (
    count($productIds) !== count($quantities) ||
    count($productIds) !== count($unitPrices) ||
    count($productIds) !== count($gstRates)
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid product data."
    ]);

    exit;
}


if ($discount < 0) {

    $discount = 0;
}


if ($paidAmount < 0) {

    $paidAmount = 0;
}


/*
|--------------------------------------------------------------------------
| Prepare Items
|--------------------------------------------------------------------------
*/

$items = [];

$subtotal = 0;


for ($i = 0; $i < count($productIds); $i++) {

    $productId = (int) $productIds[$i];

    $quantity = (float) $quantities[$i];

    $unitPrice = (float) $unitPrices[$i];

    $gstRate = (float) $gstRates[$i];


    /*
    |--------------------------------------------------------------------------
    | Validate Product
    |--------------------------------------------------------------------------
    */

    if ($productId <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Please select a valid product."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Quantity
    |--------------------------------------------------------------------------
    */

    if ($quantity <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Product quantity must be greater than zero."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Selling Price
    |--------------------------------------------------------------------------
    */

    if ($unitPrice < 0) {

        echo json_encode([
            "success" => false,
            "message" => "Selling price cannot be negative."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate GST Rate
    |--------------------------------------------------------------------------
    */

    if ($gstRate < 0) {

        echo json_encode([
            "success" => false,
            "message" => "GST rate cannot be negative."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Retail Receipt = Non-GST
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | The product's original GST rate in the products table
    | is NOT changed.
    |
    | We only store 0% GST for THIS sale transaction.
    |
    */

    if ($billType === "retail_receipt") {

        $gstRate = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Base Amount
    |--------------------------------------------------------------------------
    */

    $baseAmount =
        $quantity
        * $unitPrice;


    $subtotal += $baseAmount;


    /*
    |--------------------------------------------------------------------------
    | Store Item
    |--------------------------------------------------------------------------
    */

    $items[] = [

        "product_id" => $productId,

        "quantity" => $quantity,

        "unit_price" => $unitPrice,

        "gst_rate" => $gstRate,

        "base_amount" => $baseAmount
    ];
}


/*
|--------------------------------------------------------------------------
| Discount Cannot Exceed Subtotal
|--------------------------------------------------------------------------
*/

if ($discount > $subtotal) {

    $discount = $subtotal;
}


/*
|--------------------------------------------------------------------------
| Transaction
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Check Customer
    |--------------------------------------------------------------------------
    */

    $customerIdToSave = null;


    if ($customerId > 0) {

        $customerStmt = $pdo->prepare("
            SELECT id
            FROM customers
            WHERE id = ?
            LIMIT 1
        ");

        $customerStmt->execute([
            $customerId
        ]);


        $customerExists =
            $customerStmt->fetchColumn();


        if (!$customerExists) {

            throw new Exception(
                "Selected customer does not exist."
            );
        }


        $customerIdToSave = $customerId;
    }


    /*
    |--------------------------------------------------------------------------
    | Sale GST Totals
    |--------------------------------------------------------------------------
    */

    $taxableAmount = 0;

    $cgstAmount = 0;

    $sgstAmount = 0;

    $igstAmount = 0;


    /*
    |--------------------------------------------------------------------------
    | Recalculate Items
    |--------------------------------------------------------------------------
    |
    | Discount is distributed proportionally
    | across all products.
    |
    */

    foreach ($items as &$item) {


        $itemDiscount = 0;


        if (
            $subtotal > 0 &&
            $discount > 0
        ) {

            $itemDiscount =
                (
                    $item["base_amount"]
                    / $subtotal
                )
                * $discount;
        }


        /*
        |--------------------------------------------------------------------------
        | Taxable Amount
        |--------------------------------------------------------------------------
        */

        $itemTaxable =
            max(
                $item["base_amount"]
                - $itemDiscount,
                0
            );


        /*
        |--------------------------------------------------------------------------
        | GST Calculation
        |--------------------------------------------------------------------------
        */

        if ($billType === "tax_invoice") {


            /*
            |--------------------------------------------------------------------------
            | Calculate GST
            |--------------------------------------------------------------------------
            */

            $itemGst =
                $itemTaxable
                * $item["gst_rate"]
                / 100;


            /*
            |--------------------------------------------------------------------------
            | Split GST
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | 18% GST
            | = 9% CGST
            | + 9% SGST
            |
            */

            $itemCgst =
                $itemGst / 2;


            $itemSgst =
                $itemGst / 2;


            /*
            |--------------------------------------------------------------------------
            | IGST
            |--------------------------------------------------------------------------
            |
            | ElectroCore currently uses local GST.
            | Therefore IGST remains zero.
            |
            */

            $itemIgst = 0;


        } else {


            /*
            |--------------------------------------------------------------------------
            | Retail Receipt
            |--------------------------------------------------------------------------
            | NON-GST
            |--------------------------------------------------------------------------
            */

            $itemGst = 0;

            $itemCgst = 0;

            $itemSgst = 0;

            $itemIgst = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Line Total
        |--------------------------------------------------------------------------
        */

        $lineTotal =
            $itemTaxable
            + $itemGst;


        /*
        |--------------------------------------------------------------------------
        | Save Item Calculations
        |--------------------------------------------------------------------------
        */

        $item["discount"] =
            $itemDiscount;

        $item["taxable_amount"] =
            $itemTaxable;

        $item["cgst_amount"] =
            $itemCgst;

        $item["sgst_amount"] =
            $itemSgst;

        $item["igst_amount"] =
            $itemIgst;

        $item["line_total"] =
            $lineTotal;


        /*
        |--------------------------------------------------------------------------
        | Add To Sale Totals
        |--------------------------------------------------------------------------
        */

        $taxableAmount +=
            $itemTaxable;

        $cgstAmount +=
            $itemCgst;

        $sgstAmount +=
            $itemSgst;

        $igstAmount +=
            $itemIgst;
    }

    unset($item);


    /*
    |--------------------------------------------------------------------------
    | Total GST
    |--------------------------------------------------------------------------
    */

    $totalGst =
        $cgstAmount
        + $sgstAmount
        + $igstAmount;


    /*
    |--------------------------------------------------------------------------
    | Total Before Round Off
    |--------------------------------------------------------------------------
    */

    $totalBeforeRound =
        $taxableAmount
        + $totalGst;


    /*
    |--------------------------------------------------------------------------
    | Round Off
    |--------------------------------------------------------------------------
    */

    $grandTotal =
        round($totalBeforeRound);


    $roundOff =
        $grandTotal
        - $totalBeforeRound;


    /*
    |--------------------------------------------------------------------------
    | Due Amount
    |--------------------------------------------------------------------------
    */

    $dueAmount =
        max(
            $grandTotal
            - $paidAmount,
            0
        );


    /*
    |--------------------------------------------------------------------------
    | Payment Status
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
    | Insert Sale
    |--------------------------------------------------------------------------
    */

    $saleStmt = $pdo->prepare("

        INSERT INTO sales (

            invoice_number,

            customer_id,

            bill_type,

            sale_date,

            subtotal,

            discount,

            taxable_amount,

            cgst_amount,

            sgst_amount,

            igst_amount,

            total_gst,

            round_off,

            grand_total,

            paid_amount,

            due_amount,

            payment_status,

            notes,

            created_by

        )

        VALUES (

            ?, ?, ?, NOW(),
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?

        )

    ");


    $saleStmt->execute([

        $invoiceNumber,

        $customerIdToSave,

        $billType,

        $subtotal,

        $discount,

        $taxableAmount,

        $cgstAmount,

        $sgstAmount,

        $igstAmount,

        $totalGst,

        $roundOff,

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
    | Get Sale ID
    |--------------------------------------------------------------------------
    */

    $saleId =
        (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Prepare Sale Item Insert
    |--------------------------------------------------------------------------
    */

    $itemStmt = $pdo->prepare("

        INSERT INTO sale_items (

            sale_id,

            product_id,

            quantity,

            unit_price,

            discount,

            gst_rate,

            taxable_amount,

            cgst_amount,

            sgst_amount,

            igst_amount,

            line_total

        )

        VALUES (

            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?

        )

    ");


    /*
    |--------------------------------------------------------------------------
    | Prepare Stock Update
    |--------------------------------------------------------------------------
    */

    $stockStmt = $pdo->prepare("

        UPDATE products

        SET current_stock =
            current_stock - ?

        WHERE id = ?

        AND current_stock >= ?

    ");


    /*
    |--------------------------------------------------------------------------
    | Save Items + Reduce Stock
    |--------------------------------------------------------------------------
    */

    foreach ($items as $item) {


        /*
        |--------------------------------------------------------------------------
        | Check Product Stock
        |--------------------------------------------------------------------------
        */

        $productStmt = $pdo->prepare("

            SELECT
                product_name,
                current_stock

            FROM products

            WHERE id = ?

            FOR UPDATE

        ");


        $productStmt->execute([

            $item["product_id"]

        ]);


        $product =
            $productStmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$product) {

            throw new Exception(

                "Product ID "
                . $item["product_id"]
                . " does not exist."

            );
        }


        /*
        |--------------------------------------------------------------------------
        | Check Available Stock
        |--------------------------------------------------------------------------
        */

        if (
            (float) $product["current_stock"]
            < $item["quantity"]
        ) {

            throw new Exception(

                "Insufficient stock for "
                . $product["product_name"]
                . ". Available stock: "
                . $product["current_stock"]

            );
        }


        /*
        |--------------------------------------------------------------------------
        | Insert Sale Item
        |--------------------------------------------------------------------------
        */

        $itemStmt->execute([

            $saleId,

            $item["product_id"],

            $item["quantity"],

            $item["unit_price"],

            $item["discount"],

            /*
            |------------------------------------------------------------------
            | IMPORTANT:
            | For Retail Receipt this is already 0.
            | For Tax Invoice this contains the actual product GST rate.
            |------------------------------------------------------------------
            */

            $item["gst_rate"],

            $item["taxable_amount"],

            $item["cgst_amount"],

            $item["sgst_amount"],

            $item["igst_amount"],

            $item["line_total"]

        ]);


        /*
        |--------------------------------------------------------------------------
        | Reduce Product Stock
        |--------------------------------------------------------------------------
        */

        $stockStmt->execute([

            $item["quantity"],

            $item["product_id"],

            $item["quantity"]

        ]);


        if ($stockStmt->rowCount() !== 1) {

            throw new Exception(

                "Could not update stock for "
                . $product["product_name"]

            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Commit Transaction
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => true,

        "message" =>
            "Bill saved successfully.",

        "sale_id" =>
            $saleId,

        "invoice_number" =>
            $invoiceNumber,

        "grand_total" =>
            number_format(
                $grandTotal,
                2,
                ".",
                ""
            ),

        "redirect" =>
            "invoice.php?id=" . $saleId

    ]);

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | Error Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => false,

        "message" =>
            "Bill could not be saved: "
            . $e->getMessage()

    ]);

    exit;
}