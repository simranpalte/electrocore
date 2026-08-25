<?php

require_once '../../config/database.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

$createdBy = (int) $_SESSION["user_id"];

$full_name = $_SESSION["full_name"];
$role = $_SESSION["role"];


/*
|--------------------------------------------------------------------------
| Fetch Customers
|--------------------------------------------------------------------------
*/

$customerStmt = $pdo->query("
    SELECT
        id,
        customer_code,
        full_name,
        phone,
        gstin
    FROM customers
    WHERE status = 'active'
    ORDER BY full_name ASC
");

$customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Fetch Products
|--------------------------------------------------------------------------
*/

$productStmt = $pdo->query("
    SELECT
        id,
        product_code,
        product_name,
        current_stock,
        selling_price
    FROM products
    WHERE status = 'active'
    ORDER BY product_name ASC
");

$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ElectroCore | Billing</title>

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css"
    >

    <style>

        /* =========================================================
           ELECTROCORE DARK BILLING THEME
        ========================================================= */

        :root {

            --ec-bg: #0b1120;
            --ec-bg-2: #111827;

            --ec-card: #151e2d;
            --ec-card-2: #1a2536;

            --ec-input: #0f172a;

            --ec-border: #263449;
            --ec-border-light: #334155;

            --ec-text: #f8fafc;
            --ec-text-2: #cbd5e1;
            --ec-muted: #94a3b8;

            --ec-blue: #3b82f6;
            --ec-blue-dark: #2563eb;
            --ec-blue-light: rgba(59,130,246,.12);

            --ec-green: #22c55e;
            --ec-green-bg: rgba(34,197,94,.10);

            --ec-red: #ef4444;
            --ec-red-bg: rgba(239,68,68,.10);

            --ec-yellow: #f59e0b;
            --ec-yellow-bg: rgba(245,158,11,.10);
        }


        /* =========================================================
           PAGE
        ========================================================= */

        body {

            background:
                radial-gradient(
                    circle at top right,
                    rgba(37,99,235,.08),
                    transparent 30%
                ),
                #0b1120;

            color: var(--ec-text);
        }


        .billing-page {

            width: 100%;
        }


        /* =========================================================
           HEADER
        ========================================================= */

        .billing-page-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .billing-title-area {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .billing-page-icon {

            width: 50px;

            height: 50px;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            font-weight: 800;

            box-shadow:
                0 8px 25px
                rgba(37,99,235,.25);
        }


        .billing-page-header h1 {

            margin: 0;

            color: #f8fafc;

            font-size: 25px;

            font-weight: 800;

            letter-spacing: -.4px;
        }


        .billing-page-header p {

            margin: 4px 0 0;

            color: #94a3b8;

            font-size: 13px;
        }


        .billing-back {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 10px 15px;

            background: #151e2d;

            border: 1px solid #263449;

            color: #cbd5e1;

            border-radius: 9px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: .2s ease;
        }


        .billing-back:hover {

            border-color: #3b82f6;

            color: #60a5fa;

            background: #182438;
        }


        /* =========================================================
           ALERT
        ========================================================= */

        .billing-alert {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 13px 15px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 13px;

            font-weight: 600;
        }


        .billing-alert-error {

            background: var(--ec-red-bg);

            border: 1px solid rgba(239,68,68,.30);

            color: #f87171;
        }


        .billing-alert-success {

            background: var(--ec-green-bg);

            border: 1px solid rgba(34,197,94,.30);

            color: #4ade80;
        }


        /* =========================================================
           MAIN CARD
        ========================================================= */

        .billing-card {

            background:
                linear-gradient(
                    145deg,
                    #151e2d,
                    #111a29
                );

            border: 1px solid var(--ec-border);

            border-radius: 16px;

            margin-bottom: 20px;

            overflow: hidden;

            box-shadow:
                0 15px 35px
                rgba(0,0,0,.20);
        }


        /* =========================================================
           CARD HEADER
        ========================================================= */

        .billing-card-header {

            padding: 21px 25px;

            border-bottom: 1px solid var(--ec-border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            background: #182234;
        }


        .billing-card-title {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .billing-card-icon {

            width: 38px;

            height: 38px;

            border-radius: 9px;

            background: rgba(59,130,246,.12);

            border: 1px solid rgba(59,130,246,.20);

            color: #60a5fa;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 16px;

            font-weight: 800;
        }


        .billing-card-title h2 {

            margin: 0;

            font-size: 16px;

            font-weight: 800;

            color: #f8fafc;
        }


        .billing-card-title p {

            margin: 3px 0 0;

            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================================================
           CARD BODY
        ========================================================= */

        .billing-card-body {

            padding: 25px;
        }


        /* =========================================================
           SECTION HEADING
        ========================================================= */

        .billing-section-heading {

            display: flex;

            align-items: center;

            gap: 9px;

            margin-bottom: 17px;
        }


        .billing-section-line {

            width: 4px;

            height: 19px;

            background: #3b82f6;

            border-radius: 4px;

            box-shadow:
                0 0 10px
                rgba(59,130,246,.40);
        }


        .billing-section-heading h3 {

            margin: 0;

            font-size: 14px;

            color: #e2e8f0;

            font-weight: 800;
        }


        .billing-section-heading span {

            font-size: 11px;

            color: #64748b;

            margin-left: 2px;
        }


        /* =========================================================
           FORM GRID
        ========================================================= */

        .billing-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 20px 22px;
        }


        .billing-form-group {

            display: flex;

            flex-direction: column;

            min-width: 0;
        }


        .billing-form-group label {

            margin-bottom: 7px;

            color: #cbd5e1;

            font-size: 12px;

            font-weight: 750;
        }


        /* =========================================================
           INPUTS
        ========================================================= */

        .billing-form-group input,
        .billing-form-group select,
        .billing-form-group textarea {

            width: 100%;

            height: 43px;

            padding: 0 13px;

            border: 1px solid #334155;

            background: #0f172a;

            border-radius: 9px;

            color: #f8fafc;

            font-family: inherit;

            font-size: 13px;

            transition: .18s ease;

            box-sizing: border-box;
        }


        .billing-form-group textarea {

            height: auto;

            padding: 12px 13px;
        }


        .billing-form-group input::placeholder,
        .billing-form-group textarea::placeholder {

            color: #64748b;
        }


        .billing-form-group input:hover,
        .billing-form-group select:hover,
        .billing-form-group textarea:hover {

            border-color: #475569;
        }


        .billing-form-group input:focus,
        .billing-form-group select:focus,
        .billing-form-group textarea:focus {

            outline: none;

            border-color: #3b82f6;

            box-shadow:
                0 0 0 3px
                rgba(59,130,246,.12);
        }


        .billing-form-group input[readonly] {

            background: #111827;

            color: #94a3b8;

            font-weight: 700;
        }


        /* SELECT ARROW */

        select option {

            background: #111827;

            color: #f8fafc;
        }


        /* =========================================================
           PRODUCTS HEADER
        ========================================================= */

        .billing-products-header {

            padding: 21px 25px;

            border-bottom: 1px solid var(--ec-border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            background: #182234;
        }


        .billing-products-title {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .billing-products-title h2 {

            margin: 0;

            font-size: 16px;

            font-weight: 800;

            color: #f8fafc;
        }


        .billing-products-title p {

            margin: 3px 0 0;

            font-size: 12px;

            color: #94a3b8;
        }


        /* =========================================================
           ADD BUTTON
        ========================================================= */

        .billing-btn-add {

            height: 40px;

            padding: 0 16px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            font-family: inherit;

            font-size: 12px;

            font-weight: 800;

            cursor: pointer;

            box-shadow:
                0 6px 18px
                rgba(37,99,235,.22);

            transition: .2s ease;
        }


        .billing-btn-add:hover {

            transform: translateY(-1px);

            box-shadow:
                0 8px 22px
                rgba(37,99,235,.35);
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .billing-table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        .billing-table {

            width: 100%;

            min-width: 950px;

            border-collapse: collapse;
        }


        .billing-table th {

            padding: 13px 12px;

            background: #101927;

            border-bottom: 1px solid #263449;

            color: #94a3b8;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: .4px;

            font-weight: 800;

            text-align: left;
        }


        .billing-table td {

            padding: 12px;

            border-bottom: 1px solid #202d40;

            font-size: 13px;

            color: #cbd5e1;

            vertical-align: middle;
        }


        .billing-table tbody tr {

            transition: .15s ease;
        }


        .billing-table tbody tr:hover {

            background: rgba(59,130,246,.035);
        }


        /* =========================================================
           TABLE INPUTS
        ========================================================= */

        .billing-table select,
        .billing-table input {

            width: 100%;

            height: 39px;

            padding: 0 10px;

            border: 1px solid #334155;

            border-radius: 8px;

            background: #0f172a;

            color: #f8fafc;

            font-family: inherit;

            font-size: 13px;

            box-sizing: border-box;

            transition: .18s ease;
        }


        .billing-table select:hover,
        .billing-table input:hover {

            border-color: #475569;
        }


        .billing-table select:focus,
        .billing-table input:focus {

            outline: none;

            border-color: #3b82f6;

            box-shadow:
                0 0 0 3px
                rgba(59,130,246,.10);
        }


        .billing-table .line-total {

            background: rgba(59,130,246,.08);

            border-color: rgba(59,130,246,.25);

            color: #60a5fa;

            font-weight: 800;
        }


        /* =========================================================
           STOCK
        ========================================================= */

        .stock-info {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 28px;

            padding: 4px 9px;

            border-radius: 7px;

            background: #1e293b;

            border: 1px solid #334155;

            color: #94a3b8;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =========================================================
           REMOVE
        ========================================================= */

        .billing-btn-remove {

            height: 34px;

            padding: 0 10px;

            border: 1px solid rgba(239,68,68,.30);

            border-radius: 8px;

            background: rgba(239,68,68,.08);

            color: #f87171;

            font-family: inherit;

            font-size: 11px;

            font-weight: 800;

            cursor: pointer;

            transition: .18s ease;
        }


        .billing-btn-remove:hover {

            background: rgba(239,68,68,.15);

            border-color: rgba(239,68,68,.50);
        }


        /* =========================================================
           DIVIDER
        ========================================================= */

        .billing-divider {

            height: 1px;

            background: #263449;

            margin: 28px 0;
        }


        /* =========================================================
           PAYMENT LAYOUT
        ========================================================= */

        .payment-layout {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                390px;

            gap: 25px;

            align-items: start;
        }


        /* =========================================================
           TOTALS
        ========================================================= */

        .billing-totals {

            background:
                linear-gradient(
                    145deg,
                    #101927,
                    #0d1522
                );

            border: 1px solid #263449;

            border-radius: 12px;

            padding: 18px;

            box-shadow:
                inset 0 1px 0
                rgba(255,255,255,.02);
        }


        .billing-total-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 8px 0;

            color: #94a3b8;

            font-size: 13px;
        }


        .billing-total-row strong {

            color: #e2e8f0;
        }


        .billing-grand-total {

            margin-top: 8px;

            padding: 15px 0;

            border-top: 2px solid #334155;

            font-size: 16px;

            font-weight: 800;

            color: #f8fafc;
        }


        .billing-grand-total strong {

            color: #60a5fa;

            font-size: 21px;

            text-shadow:
                0 0 15px
                rgba(59,130,246,.20);
        }


        .billing-due {

            margin-top: 6px;

            padding: 10px 12px;

            border-radius: 8px;

            background: rgba(239,68,68,.08);

            border: 1px solid rgba(239,68,68,.25);

            color: #f87171;

            font-weight: 800;
        }


        .billing-due strong {

            color: #f87171;
        }


        /* =========================================================
           NOTES
        ========================================================= */

        .billing-notes {

            min-height: 105px;

            resize: vertical;
        }


        /* =========================================================
           BOTTOM ACTIONS
        ========================================================= */

        .billing-bottom-actions {

            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 5px;

            margin-bottom: 25px;

            padding-top: 5px;
        }


        .billing-cancel {

            height: 42px;

            padding: 0 18px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 9px;

            border: 1px solid #334155;

            background: #151e2d;

            color: #cbd5e1;

            text-decoration: none;

            font-family: inherit;

            font-size: 12px;

            font-weight: 800;

            transition: .2s ease;
        }


        .billing-cancel:hover {

            background: #1e293b;

            border-color: #475569;

            color: #f8fafc;
        }


        .billing-save {

            height: 42px;

            padding: 0 20px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: #ffffff;

            font-family: inherit;

            font-size: 12px;

            font-weight: 800;

            cursor: pointer;

            box-shadow:
                0 6px 18px
                rgba(37,99,235,.22);

            transition: .2s ease;
        }


        .billing-save:hover {

            transform: translateY(-1px);

            box-shadow:
                0 8px 22px
                rgba(37,99,235,.35);
        }


        .billing-save:disabled {

            opacity: .6;

            cursor: not-allowed;

            transform: none;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .billing-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .payment-layout {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 800px) {

            .billing-page-header {

                align-items: flex-start;
            }


            .billing-back {

                display: none;
            }


            .billing-grid {

                grid-template-columns: 1fr;
            }


            .billing-card-body {

                padding: 20px;
            }


            .billing-card-header,
            .billing-products-header {

                padding: 18px 20px;
            }

        }


        @media (max-width: 500px) {

            .billing-page-header h1 {

                font-size: 21px;
            }


            .billing-page-icon {

                width: 42px;

                height: 42px;

                font-size: 19px;
            }


            .billing-card-body {

                padding: 16px;
            }


            .billing-card-header,
            .billing-products-header {

                padding: 16px;
            }


            .billing-bottom-actions {

                justify-content: stretch;
            }


            .billing-bottom-actions a,
            .billing-bottom-actions button {

                flex: 1;
            }

        }

    </style>

</head>


<body>


<div class="dashboard">


    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>


    <main class="main-content">


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="billing-page-header">

            <div class="billing-title-area">

                <div class="billing-page-icon">
                    ₹
                </div>

                <div>

                    <h1>
                        Billing
                    </h1>

                    <p>
                        Create and manage customer bills
                    </p>

                </div>

            </div>


            <a
                href="../../dashboard.php"
                class="billing-back"
            >
                ← Back to Dashboard
            </a>

        </div>


        <div class="billing-page">


            <div id="message"></div>


            <!-- =================================================
                 BILL INFORMATION
            ================================================== -->

            <div class="billing-card">

                <div class="billing-card-header">

                    <div class="billing-card-title">

                        <div class="billing-card-icon">
                            #
                        </div>

                        <div>

                            <h2>
                                Bill Information
                            </h2>

                            <p>
                                Customer and invoice details
                            </p>

                        </div>

                    </div>

                </div>


                <div class="billing-card-body">


                    <div class="billing-section-heading">

                        <div class="billing-section-line"></div>

                        <h3>
                            Invoice Details
                        </h3>

                        <span>
                            Basic billing information
                        </span>

                    </div>


                    <div class="billing-grid">


                        <div class="billing-form-group">

                            <label>
                                Invoice Number
                            </label>

                            <input
                                type="text"
                                id="invoice_number"
                                value="INV-<?= date('YmdHis') ?>"
                                readonly
                            >

                        </div>


                        <div class="billing-form-group">

                            <label>
                                Customer
                            </label>

                            <select id="customer_id">

                                <option value="">
                                    Walk-in Customer
                                </option>


                                <?php foreach ($customers as $customer): ?>

                                    <option
                                        value="<?= (int) $customer['id'] ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $customer['full_name']
                                        ) ?>

                                        (<?= htmlspecialchars(
                                            $customer['customer_code']
                                        ) ?>)

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="billing-form-group">

                            <label>
                                Bill Type
                            </label>

                            <select
                                id="bill_type"
                                onchange="billTypeChanged()"
                            >

                                <option value="tax_invoice">
                                    Tax Invoice
                                </option>

                                <option value="retail_receipt">
                                    Retail Receipt
                                </option>

                            </select>

                        </div>


                    </div>

                </div>

            </div>


            <!-- =================================================
                 PRODUCTS
            ================================================== -->

            <div class="billing-card">


                <div class="billing-products-header">

                    <div class="billing-products-title">

                        <div class="billing-card-icon">
                            +
                        </div>

                        <div>

                            <h2>
                                Products
                            </h2>

                            <p>
                                Add products to this customer bill
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="billing-btn-add"
                        onclick="addItem()"
                    >
                        + Add Product
                    </button>

                </div>


                <div class="billing-table-wrapper">

                    <table class="billing-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Stock
                                </th>

                                <th width="120">
                                    Quantity
                                </th>

                                <th width="140">
                                    Selling Price
                                </th>

                                <th
                                    width="120"
                                    id="gstHeader"
                                >
                                    GST %
                                </th>

                                <th width="140">
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


            <!-- =================================================
                 PAYMENT
            ================================================== -->

            <div class="billing-card">


                <div class="billing-card-header">

                    <div class="billing-card-title">

                        <div class="billing-card-icon">
                            ₹
                        </div>

                        <div>

                            <h2>
                                Payment Details
                            </h2>

                            <p>
                                Payment and bill summary
                            </p>

                        </div>

                    </div>

                </div>


                <div class="billing-card-body">


                    <div class="payment-layout">


                        <div>

                            <div class="billing-section-heading">

                                <div class="billing-section-line"></div>

                                <h3>
                                    Payment Information
                                </h3>

                                <span>
                                    Discount and payment
                                </span>

                            </div>


                            <div class="billing-grid">


                                <div class="billing-form-group">

                                    <label>
                                        Discount
                                    </label>

                                    <input
                                        type="number"
                                        id="discount"
                                        value="0"
                                        min="0"
                                        step="0.01"
                                        oninput="calculateTotals()"
                                    >

                                </div>


                                <div class="billing-form-group">

                                    <label>
                                        Paid Amount
                                    </label>

                                    <input
                                        type="number"
                                        id="paid_amount"
                                        value="0"
                                        min="0"
                                        step="0.01"
                                        oninput="calculateTotals()"
                                    >

                                </div>


                            </div>

                        </div>


                        <!-- TOTALS -->

                        <div class="billing-totals">


                            <div class="billing-total-row">

                                <span>
                                    Subtotal
                                </span>

                                <strong id="subtotal">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    Discount
                                </span>

                                <strong id="discountDisplay">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    Taxable Amount
                                </span>

                                <strong id="taxableAmount">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    CGST
                                </span>

                                <strong id="cgst">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    SGST
                                </span>

                                <strong id="sgst">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    IGST
                                </span>

                                <strong id="igst">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    Total GST
                                </span>

                                <strong id="totalGst">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    Round Off
                                </span>

                                <strong id="roundOff">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row billing-grand-total">

                                <span>
                                    Grand Total
                                </span>

                                <strong id="grandTotal">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row">

                                <span>
                                    Paid Amount
                                </span>

                                <strong id="paidDisplay">
                                    ₹0.00
                                </strong>

                            </div>


                            <div class="billing-total-row billing-due">

                                <span>
                                    Due Amount
                                </span>

                                <strong id="dueAmount">
                                    ₹0.00
                                </strong>

                            </div>


                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 NOTES
            ================================================== -->

            <div class="billing-card">


                <div class="billing-card-header">

                    <div class="billing-card-title">

                        <div class="billing-card-icon">
                            N
                        </div>

                        <div>

                            <h2>
                                Notes
                            </h2>

                            <p>
                                Additional information for this bill
                            </p>

                        </div>

                    </div>

                </div>


                <div class="billing-card-body">


                    <div class="billing-form-group">

                        <label>
                            Notes
                        </label>

                        <textarea
                            id="notes"
                            class="billing-notes"
                            rows="4"
                            placeholder="Optional notes..."
                        ></textarea>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 ACTIONS
            ================================================== -->

            <div class="billing-bottom-actions">


                <a
                    href="../../dashboard.php"
                    class="billing-cancel"
                >
                    Cancel
                </a>


                <button
                    type="button"
                    class="billing-save"
                    onclick="saveBill()"
                >
                    Save Bill
                </button>


            </div>


        </div>

    </main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Products From PHP
|--------------------------------------------------------------------------
*/

const products =
    <?= json_encode(
        $products,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;


/*
|--------------------------------------------------------------------------
| Bill Type Changed
|--------------------------------------------------------------------------
*/

function billTypeChanged() {

    const billType =
        document.getElementById("bill_type").value;

    const isRetail =
        billType === "retail_receipt";

    const gstHeader =
        document.getElementById("gstHeader");

    if (gstHeader) {

        gstHeader.style.display =
            isRetail ? "none" : "";

    }


    document.querySelectorAll(
        "#itemsBody .gst-cell"
    ).forEach(function(cell) {

        cell.style.display =
            isRetail ? "none" : "";

    });


    document.querySelectorAll(
        "#itemsBody .gst-rate"
    ).forEach(function(input) {

        if (isRetail) {

            input.value = "0";

            input.disabled = true;

        } else {

            input.disabled = false;

            if (parseFloat(input.value) === 0) {

                input.value = "18";

            }

        }

    });


    calculateTotals();

}


/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

function addItem(
    selectedProduct = "",
    quantity = 1
) {

    const tbody =
        document.getElementById("itemsBody");


    if (!tbody) {

        showError(
            "Product table could not be found."
        );

        return;

    }


    const row =
        document.createElement("tr");


    let productOptions =
        '<option value="">Select Product</option>';


    products.forEach(function(product) {

        const selected =
            String(product.id) ===
            String(selectedProduct)
                ? "selected"
                : "";


        productOptions += `

            <option
                value="${escapeHtml(product.id)}"
                data-stock="${escapeHtml(product.current_stock)}"
                data-price="${escapeHtml(product.selling_price)}"
                ${selected}
            >

                ${escapeHtml(product.product_name)}
                (${escapeHtml(product.product_code)})

            </option>

        `;

    });


    row.innerHTML = `

        <td>

            <select
                class="product-select"
                onchange="productChanged(this)"
                required
            >

                ${productOptions}

            </select>

        </td>


        <td>

            <span class="stock-info">
                -
            </span>

        </td>


        <td>

            <input
                type="number"
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
                class="unit-price"
                value="0"
                min="0"
                step="0.01"
                oninput="calculateTotals()"
                required
            >

        </td>


        <td class="gst-cell">

            <input
                type="number"
                class="gst-rate"
                value="18"
                min="0"
                step="0.01"
                oninput="calculateTotals()"
                required
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
                class="billing-btn-remove"
                onclick="removeItem(this)"
            >

                Remove

            </button>

        </td>

    `;


    tbody.appendChild(row);


    if (selectedProduct !== "") {

        productChanged(
            row.querySelector(".product-select")
        );

    }


    billTypeChanged();

    calculateTotals();

}


/*
|--------------------------------------------------------------------------
| Product Changed
|--------------------------------------------------------------------------
*/

function productChanged(select) {

    const row =
        select.closest("tr");


    if (!row) {

        return;

    }


    const option =
        select.options[
            select.selectedIndex
        ];


    if (!option || !option.value) {

        row.querySelector(
            ".stock-info"
        ).textContent = "-";


        row.querySelector(
            ".unit-price"
        ).value = "0";


        calculateTotals();

        return;

    }


    const price =
        parseFloat(
            option.dataset.price
        ) || 0;


    const stock =
        parseFloat(
            option.dataset.stock
        ) || 0;


    row.querySelector(
        ".unit-price"
    ).value =
        price.toFixed(2);


    row.querySelector(
        ".stock-info"
    ).textContent =
        stock.toFixed(2) + " piece";


    calculateTotals();

}


/*
|--------------------------------------------------------------------------
| Remove Product
|--------------------------------------------------------------------------
*/

function removeItem(button) {

    const row =
        button.closest("tr");


    if (row) {

        row.remove();

    }


    calculateTotals();

}


/*
|--------------------------------------------------------------------------
| Calculate Totals
|--------------------------------------------------------------------------
*/

function calculateTotals() {

    const billType =
        document.getElementById(
            "bill_type"
        ).value;


    const isRetail =
        billType === "retail_receipt";


    let subtotal = 0;

    let totalGst = 0;


    const rows =
        document.querySelectorAll(
            "#itemsBody tr"
        );


    rows.forEach(function(row) {

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


        let gstRate = 0;


        if (!isRetail) {

            gstRate =
                parseFloat(
                    row.querySelector(
                        ".gst-rate"
                    ).value
                ) || 0;

        }


        const baseAmount =
            quantity * unitPrice;


        let gstAmount = 0;


        if (!isRetail) {

            gstAmount =
                baseAmount *
                gstRate /
                100;

        }


        const lineTotal =
            baseAmount +
            gstAmount;


        subtotal += baseAmount;

        totalGst += gstAmount;


        row.querySelector(
            ".line-total"
        ).value =
            "₹" +
            lineTotal.toFixed(2);

    });


    let discount =
        parseFloat(
            document.getElementById(
                "discount"
            ).value
        ) || 0;


    if (discount < 0) {

        discount = 0;

    }


    if (discount > subtotal) {

        discount = subtotal;

    }


    let taxableAmount = 0;


    if (!isRetail) {

        taxableAmount =
            Math.max(
                subtotal - discount,
                0
            );

    }


    let cgst = 0;

    let sgst = 0;

    let igst = 0;


    if (!isRetail) {

        cgst =
            totalGst / 2;

        sgst =
            totalGst / 2;

    }


    let beforeRound = 0;


    if (isRetail) {

        beforeRound =
            Math.max(
                subtotal - discount,
                0
            );

    } else {

        beforeRound =
            taxableAmount +
            totalGst;

    }


    const grandTotal =
        Math.round(beforeRound);


    const roundOff =
        grandTotal -
        beforeRound;


    let paidAmount =
        parseFloat(
            document.getElementById(
                "paid_amount"
            ).value
        ) || 0;


    if (paidAmount < 0) {

        paidAmount = 0;

    }


    const dueAmount =
        Math.max(
            grandTotal -
            paidAmount,
            0
        );


    document.getElementById(
        "subtotal"
    ).textContent =
        "₹" + subtotal.toFixed(2);


    document.getElementById(
        "discountDisplay"
    ).textContent =
        "₹" + discount.toFixed(2);


    document.getElementById(
        "taxableAmount"
    ).textContent =
        "₹" + taxableAmount.toFixed(2);


    document.getElementById(
        "cgst"
    ).textContent =
        "₹" + cgst.toFixed(2);


    document.getElementById(
        "sgst"
    ).textContent =
        "₹" + sgst.toFixed(2);


    document.getElementById(
        "igst"
    ).textContent =
        "₹" + igst.toFixed(2);


    document.getElementById(
        "totalGst"
    ).textContent =
        "₹" + totalGst.toFixed(2);


    document.getElementById(
        "roundOff"
    ).textContent =
        "₹" + roundOff.toFixed(2);


    document.getElementById(
        "grandTotal"
    ).textContent =
        "₹" + grandTotal.toFixed(2);


    document.getElementById(
        "paidDisplay"
    ).textContent =
        "₹" + paidAmount.toFixed(2);


    document.getElementById(
        "dueAmount"
    ).textContent =
        "₹" + dueAmount.toFixed(2);

}


/*
|--------------------------------------------------------------------------
| Save Bill
|--------------------------------------------------------------------------
*/

function saveBill() {

    const rows =
        document.querySelectorAll(
            "#itemsBody tr"
        );


    if (rows.length === 0) {

        showError(
            "Please add at least one product."
        );

        return;

    }


    const billType =
        document.getElementById(
            "bill_type"
        ).value;


    const productIds = [];

    const quantities = [];

    const unitPrices = [];

    const gstRates = [];


    for (const row of rows) {

        const productSelect =
            row.querySelector(
                ".product-select"
            );


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


        let gstRate = 0;


        if (billType === "tax_invoice") {

            gstRate =
                parseFloat(
                    row.querySelector(
                        ".gst-rate"
                    ).value
                ) || 0;

        }


        if (!productSelect.value) {

            showError(
                "Please select a product."
            );

            return;

        }


        const selectedOption =
            productSelect.options[
                productSelect.selectedIndex
            ];


        const stock =
            parseFloat(
                selectedOption.dataset.stock
            ) || 0;


        if (quantity <= 0) {

            showError(
                "Quantity must be greater than zero."
            );

            return;

        }


        if (quantity > stock) {

            showError(
                "Insufficient stock for " +
                selectedOption.text.trim() +
                ". Available stock: " +
                stock
            );

            return;

        }


        if (unitPrice < 0) {

            showError(
                "Selling price cannot be negative."
            );

            return;

        }


        if (gstRate < 0) {

            showError(
                "GST rate cannot be negative."
            );

            return;

        }


        productIds.push(
            productSelect.value
        );


        quantities.push(
            quantity
        );


        unitPrices.push(
            unitPrice
        );


        gstRates.push(
            gstRate
        );

    }


    calculateTotals();


    const invoiceNumber =
        document.getElementById(
            "invoice_number"
        ).value;


    const customerId =
        document.getElementById(
            "customer_id"
        ).value;


    const discount =
        parseFloat(
            document.getElementById(
                "discount"
            ).value
        ) || 0;


    const paidAmount =
        parseFloat(
            document.getElementById(
                "paid_amount"
            ).value
        ) || 0;


    const notes =
        document.getElementById(
            "notes"
        ).value.trim();


    const grandTotal =
        parseFloat(
            document.getElementById(
                "grandTotal"
            ).textContent
                .replace("₹", "")
                .trim()
        ) || 0;


    if (paidAmount < 0) {

        showError(
            "Paid amount cannot be negative."
        );

        return;

    }


    if (paidAmount > grandTotal) {

        showError(
            "Paid amount cannot be greater than the grand total."
        );

        return;

    }


    const formData =
        new FormData();


    formData.append(
        "invoice_number",
        invoiceNumber
    );


    formData.append(
        "customer_id",
        customerId
    );


    formData.append(
        "bill_type",
        billType
    );


    formData.append(
        "discount",
        discount
    );


    formData.append(
        "paid_amount",
        paidAmount
    );


    formData.append(
        "notes",
        notes
    );


    productIds.forEach(function(value) {

        formData.append(
            "product_id[]",
            value
        );

    });


    quantities.forEach(function(value) {

        formData.append(
            "quantity[]",
            value
        );

    });


    unitPrices.forEach(function(value) {

        formData.append(
            "unit_price[]",
            value
        );

    });


    gstRates.forEach(function(value) {

        formData.append(
            "gst_rate[]",
            value
        );

    });


    const saveButton =
        document.querySelector(
            ".billing-save"
        );


    if (saveButton) {

        saveButton.disabled = true;

        saveButton.textContent =
            "Saving...";

    }


    fetch(
        "save.php",
        {
            method: "POST",
            body: formData
        }
    )

    .then(function(response) {

        return response.json();

    })

    .then(function(result) {

        if (result.success) {

            showSuccess(
                "Bill saved successfully."
            );


            if (result.sale_id) {

                setTimeout(function() {

                    window.location.href =
                        "invoice.php?id=" +
                        encodeURIComponent(
                            result.sale_id
                        );

                }, 500);

            } else {

                showError(
                    "Bill was saved, but sale ID was not returned."
                );


                if (saveButton) {

                    saveButton.disabled =
                        false;

                    saveButton.textContent =
                        "Save Bill";

                }

            }

        } else {

            showError(
                result.message ||
                "Unable to save the bill."
            );


            if (saveButton) {

                saveButton.disabled =
                    false;

                saveButton.textContent =
                    "Save Bill";

            }

        }

    })

    .catch(function(error) {

        console.error(
            "Save Bill Error:",
            error
        );


        showError(
            "Unable to connect to save.php."
        );


        if (saveButton) {

            saveButton.disabled =
                false;

            saveButton.textContent =
                "Save Bill";

        }

    });

}


/*
|--------------------------------------------------------------------------
| Error
|--------------------------------------------------------------------------
*/

function showError(message) {

    document.getElementById(
        "message"
    ).innerHTML = `

        <div class="
            billing-alert
            billing-alert-error
        ">

            <span>!</span>

            <span>
                ${escapeHtml(message)}
            </span>

        </div>

    `;

}


/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

function showSuccess(message) {

    document.getElementById(
        "message"
    ).innerHTML = `

        <div class="
            billing-alert
            billing-alert-success
        ">

            <span>✓</span>

            <span>
                ${escapeHtml(message)}
            </span>

        </div>

    `;

}


/*
|--------------------------------------------------------------------------
| Escape HTML
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
| FIRST PRODUCT ROW
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function() {

        addItem();

        billTypeChanged();

    }
);

</script>


</body>

</html>