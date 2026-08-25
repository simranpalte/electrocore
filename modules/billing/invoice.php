<?php

require_once '../../config/database.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

$saleId = (int) ($_GET["id"] ?? 0);

if ($saleId <= 0) {
    die("Invalid invoice ID.");
}

/*
|--------------------------------------------------------------------------
| GET SALE + CUSTOMER
|--------------------------------------------------------------------------
*/

$saleSql = "
    SELECT
        s.id,
        s.invoice_number,
        s.bill_type,
        s.sale_date,
        s.subtotal,
        s.discount,
        s.taxable_amount,
        s.cgst_amount,
        s.sgst_amount,
        s.igst_amount,
        s.total_gst,
        s.round_off,
        s.grand_total,
        s.paid_amount,
        s.due_amount,
        s.payment_status,
        s.notes,

        c.id AS customer_id,
        c.customer_code,
        c.full_name AS customer_name,
        c.phone AS customer_phone,
        c.email AS customer_email,
        c.address AS customer_address,
        c.city AS customer_city,
        c.state AS customer_state,
        c.pincode AS customer_pincode,
        c.gstin AS customer_gstin

    FROM sales s

    LEFT JOIN customers c
        ON c.id = s.customer_id

    WHERE s.id = :sale_id

    LIMIT 1
";

$stmt = $pdo->prepare($saleSql);

$stmt->execute([
    ':sale_id' => $saleId
]);

$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("Invoice not found.");
}

/*
|--------------------------------------------------------------------------
| GET SALE ITEMS
|--------------------------------------------------------------------------
*/

$itemSql = "
    SELECT
        si.id,
        si.quantity,
        si.unit_price,
        si.discount,
        si.gst_rate,
        si.taxable_amount,
        si.cgst_amount,
        si.sgst_amount,
        si.igst_amount,
        si.line_total,

        p.product_code,
        p.product_name,
        p.unit

    FROM sale_items si

    INNER JOIN products p
        ON p.id = si.product_id

    WHERE si.sale_id = :sale_id

    ORDER BY si.id ASC
";

$itemStmt = $pdo->prepare($itemSql);

$itemStmt->execute([
    ':sale_id' => $saleId
]);

$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function money($amount)
{
    return '₹ ' . number_format((float)$amount, 2);
}

function clean($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| COMPANY INFORMATION
|--------------------------------------------------------------------------
*/

$companyName = "ELECTROCORE";
$companyTagline = "Electrical Billing & Inventory Management";
$companyAddress = "Electrical Solutions & Supplies";
$companyCity = "India";
$companyPhone = "+91 XXXXX XXXXX";
$companyEmail = "support@electrocore.local";

/*
|--------------------------------------------------------------------------
| BILL TYPE
|--------------------------------------------------------------------------
*/

$isTaxInvoice = ($sale['bill_type'] === 'tax_invoice');

$invoiceTitle = $isTaxInvoice
    ? 'TAX INVOICE'
    : 'RETAIL RECEIPT';

$paymentStatus = strtoupper($sale['payment_status']);

/*
|--------------------------------------------------------------------------
| GST
|--------------------------------------------------------------------------
*/

$hasGst =
    ((float)$sale['total_gst'] > 0) ||
    ((float)$sale['cgst_amount'] > 0) ||
    ((float)$sale['sgst_amount'] > 0) ||
    ((float)$sale['igst_amount'] > 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    <?= clean($invoiceTitle) ?> -
    <?= clean($sale['invoice_number']) ?>
</title>

<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
}

body {
    background: #e9edf3;
    color: #172033;
    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


/* =========================================================
   ACTION BAR
========================================================= */

.action-bar {
    width: 210mm;
    max-width: calc(100% - 30px);
    margin: 25px auto 18px;

    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.action-btn {
    border: none;
    border-radius: 7px;

    padding: 11px 18px;

    font-size: 13px;
    font-weight: 700;

    cursor: pointer;
}

.back-btn {
    background: #ffffff;
    color: #172033;

    border: 1px solid #d4d9e1;
}

.print-btn {
    background: #172033;
    color: #ffffff;
}

.print-btn:hover {
    background: #0d1423;
}


/* =========================================================
   A4 INVOICE
========================================================= */

.invoice-page {

    width: 210mm;
    min-height: 297mm;

    margin: 0 auto 30px;

    background: #ffffff;

    padding: 16mm 17mm;

    box-shadow:
        0 12px 40px rgba(20, 30, 50, 0.16);
}


/* =========================================================
   TOP BRANDING
========================================================= */

.invoice-header {

    display: flex;

    justify-content: space-between;
    align-items: flex-start;

    padding-bottom: 18px;

    border-bottom: 3px solid #172033;
}


.brand-area {
    display: flex;
    align-items: center;
    gap: 14px;
}


/* ElectroCore logo */

.electro-logo {

    width: 58px;
    height: 58px;

    border-radius: 12px;

    background: #172033;

    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 28px;
    font-weight: 900;

    box-shadow:
        0 5px 14px rgba(23, 32, 51, 0.22);
}


.brand-name {

    font-size: 29px;
    font-weight: 900;

    letter-spacing: 2px;

    color: #172033;
}


.brand-tagline {

    margin-top: 4px;

    font-size: 10px;

    color: #687386;

    letter-spacing: 0.6px;
}


.company-details {

    margin-top: 10px;

    font-size: 9.5px;

    line-height: 1.55;

    color: #596579;
}


/* =========================================================
   INVOICE TITLE
========================================================= */

.invoice-heading {
    text-align: right;
}

.invoice-title {

    font-size: 25px;

    font-weight: 900;

    letter-spacing: 1px;

    color: #172033;
}

.invoice-number {

    margin-top: 8px;

    font-size: 11px;

    font-weight: 700;

    color: #263247;
}

.invoice-date {

    margin-top: 5px;

    font-size: 9.5px;

    color: #687386;
}


/* =========================================================
   CUSTOMER INFORMATION
========================================================= */

.info-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 18px;

    margin-top: 20px;
}

.info-box {

    border: 1px solid #dce1e8;

    border-radius: 8px;

    overflow: hidden;

    background: #ffffff;
}

.info-title {

    background: #f3f5f8;

    padding: 9px 12px;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: 0.8px;

    text-transform: uppercase;

    color: #526074;
}

.info-content {

    padding: 11px 12px;

    min-height: 88px;

    font-size: 10px;

    line-height: 1.6;

    color: #263247;
}

.customer-name {

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 3px;

    color: #172033;
}

.muted {
    color: #687386;
}


/* =========================================================
   ITEMS SECTION
========================================================= */

.items-section {

    margin-top: 24px;
}

.items-table {

    width: 100%;

    border-collapse: collapse;

    font-size: 9.5px;
}

.items-table thead {

    background: #172033;

    color: #ffffff;
}

.items-table th {

    padding: 10px 8px;

    font-size: 8.5px;

    font-weight: 800;

    letter-spacing: 0.5px;

    text-transform: uppercase;

    text-align: left;
}

.items-table td {

    padding: 11px 8px;

    border-bottom: 1px solid #e2e6ec;

    vertical-align: middle;
}

.items-table tbody tr:nth-child(even) {

    background: #fafbfd;
}

.items-table tbody tr:last-child td {

    border-bottom: 2px solid #172033;
}

.product-name {

    font-size: 10px;

    font-weight: 700;

    color: #172033;
}

.product-code {

    margin-top: 3px;

    font-size: 8px;

    color: #788397;
}

.text-right {
    text-align: right !important;
}

.text-center {
    text-align: center !important;
}


/* =========================================================
   LOWER AREA
========================================================= */

.lower-section {

    display: grid;

    grid-template-columns: 1fr 285px;

    gap: 35px;

    margin-top: 30px;
}


/* =========================================================
   NOTES
========================================================= */

.notes-box {

    border: 1px solid #dce1e8;

    border-radius: 8px;

    padding: 14px;

    min-height: 125px;

    background: #ffffff;
}

.notes-title {

    font-size: 9px;

    font-weight: 800;

    letter-spacing: 0.8px;

    text-transform: uppercase;

    color: #526074;

    padding-bottom: 8px;

    border-bottom: 1px solid #e4e7ec;

    margin-bottom: 9px;
}

.notes-text {

    font-size: 9px;

    line-height: 1.8;

    color: #697589;
}


/* =========================================================
   TOTALS
========================================================= */

.amount-table {

    width: 100%;

    border-collapse: collapse;

    font-size: 10px;
}

.amount-table td {

    padding: 6px 0;

    vertical-align: middle;
}

.amount-label {

    color: #687386;
}

.amount-value {

    text-align: right;

    font-weight: 700;

    color: #263247;
}

.total-row td {

    border-top: 2px solid #172033;

    border-bottom: 2px solid #172033;

    padding: 12px 0;

    font-size: 15px;

    font-weight: 900;

    color: #172033;
}

.paid {
    color: #19734d !important;
}

.due {
    color: #b42318 !important;
}


/* =========================================================
   PAYMENT STATUS
========================================================= */

.status {

    display: inline-block;

    padding: 4px 9px;

    border-radius: 20px;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 0.5px;
}

.status-paid {

    background: #e8f7ef;

    color: #18794e;
}

.status-partial {

    background: #fff4d6;

    color: #9a6700;
}

.status-pending {

    background: #fdecec;

    color: #b42318;
}


/* =========================================================
   FOOTER
========================================================= */

.invoice-footer {

    margin-top: 34px;

    padding-top: 14px;

    border-top: 1px solid #dce1e8;

    display: flex;

    justify-content: space-between;

    gap: 30px;

    font-size: 8.5px;

    line-height: 1.6;

    color: #748095;
}

.footer-right {
    text-align: right;
}

.footer-brand {

    font-weight: 800;

    color: #172033;
}


.thank-you {

    margin-top: 22px;

    text-align: center;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 0.3px;

    color: #172033;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 750px) {

    body {
        background: #ffffff;
    }

    .action-bar {

        width: 100%;
        max-width: none;

        margin: 0;

        padding: 12px;

        background: #e9edf3;
    }

    .invoice-page {

        width: 100%;

        min-height: auto;

        margin: 0;

        padding: 20px;

        box-shadow: none;
    }

    .invoice-header {

        flex-direction: column;

        gap: 20px;
    }

    .invoice-heading {

        text-align: left;
    }

    .info-grid {

        grid-template-columns: 1fr;
    }

    .lower-section {

        grid-template-columns: 1fr;

        gap: 25px;
    }

    .items-table {

        font-size: 8px;
    }

    .items-table th,
    .items-table td {

        padding: 7px 4px;
    }
}


/* =========================================================
   PRINT
========================================================= */

@media print {

    @page {

        size: A4 portrait;

        margin: 0;
    }

    html,
    body {

        width: 210mm;

        margin: 0;

        padding: 0;

        background: #ffffff;
    }

    .action-bar {

        display: none !important;
    }

    .invoice-page {

        width: 210mm;

        min-height: 297mm;

        margin: 0;

        padding: 15mm 17mm;

        box-shadow: none;
    }

    * {

        -webkit-print-color-adjust: exact !important;

        print-color-adjust: exact !important;
    }

    .items-table {

        page-break-inside: auto;
    }

    .items-table tr {

        page-break-inside: avoid;

        page-break-after: auto;
    }

    .info-box,
    .notes-box {

        page-break-inside: avoid;
    }

    .lower-section {

        page-break-inside: avoid;
    }

    .invoice-footer {

        page-break-inside: avoid;
    }
}

</style>

</head>


<body>


<!-- ======================================================
     ACTION BUTTONS
====================================================== -->

<div class="action-bar">

    <button
        type="button"
        class="action-btn back-btn"
        onclick="window.history.back();">

        ← Back

    </button>


    <button
        type="button"
        class="action-btn print-btn"
        onclick="window.print();">

        🖨 Print / Save PDF

    </button>

</div>



<!-- ======================================================
     INVOICE PAGE
====================================================== -->

<div class="invoice-page">


    <!-- ==================================================
         HEADER
    ================================================== -->

    <div class="invoice-header">

        <div>

            <div class="brand-area">

                <div class="electro-logo">
                    ⚡
                </div>

                <div>

                    <div class="brand-name">
                        ELECTROCORE
                    </div>

                    <div class="brand-tagline">
                        Electrical Billing & Inventory Management
                    </div>

                </div>

            </div>


            <div class="company-details">

                <?= clean($companyAddress) ?><br>

                <?= clean($companyCity) ?><br>

                Phone: <?= clean($companyPhone) ?>
                &nbsp; | &nbsp;
                Email: <?= clean($companyEmail) ?>

            </div>

        </div>


        <div class="invoice-heading">

            <div class="invoice-title">

                <?= clean($invoiceTitle) ?>

            </div>


            <div class="invoice-number">

                Invoice No:
                <?= clean($sale['invoice_number']) ?>

            </div>


            <div class="invoice-date">

                Date:
                <?= date(
                    'd M Y, h:i A',
                    strtotime($sale['sale_date'])
                ) ?>

            </div>

        </div>

    </div>



    <!-- ==================================================
         CUSTOMER / INVOICE DETAILS
    ================================================== -->

    <div class="info-grid">


        <!-- CUSTOMER -->

        <div class="info-box">

            <div class="info-title">

                Bill To

            </div>


            <div class="info-content">

                <div class="customer-name">

                    <?= clean(
                        $sale['customer_name']
                        ?: 'Walk-in Customer'
                    ) ?>

                </div>


                <?php if (!empty($sale['customer_code'])): ?>

                    <div class="muted">

                        Customer Code:
                        <?= clean($sale['customer_code']) ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($sale['customer_phone'])): ?>

                    <div>

                        Phone:
                        <?= clean($sale['customer_phone']) ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($sale['customer_email'])): ?>

                    <div>

                        Email:
                        <?= clean($sale['customer_email']) ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($sale['customer_address'])): ?>

                    <div>

                        <?= nl2br(
                            clean($sale['customer_address'])
                        ) ?>

                    </div>

                <?php endif; ?>


                <?php if (
                    !empty($sale['customer_city']) ||
                    !empty($sale['customer_state']) ||
                    !empty($sale['customer_pincode'])
                ): ?>

                    <div>

                        <?= clean($sale['customer_city']) ?>

                        <?php if (!empty($sale['customer_state'])): ?>

                            , <?= clean($sale['customer_state']) ?>

                        <?php endif; ?>


                        <?php if (!empty($sale['customer_pincode'])): ?>

                            - <?= clean($sale['customer_pincode']) ?>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


                <?php if (!empty($sale['customer_gstin'])): ?>

                    <div>

                        GSTIN:

                        <strong>
                            <?= clean($sale['customer_gstin']) ?>
                        </strong>

                    </div>

                <?php endif; ?>

            </div>

        </div>



        <!-- INVOICE DETAILS -->

        <div class="info-box">

            <div class="info-title">

                Invoice Details

            </div>


            <div class="info-content">

                <div>

                    <strong>Invoice:</strong>

                    <?= clean(
                        $sale['invoice_number']
                    ) ?>

                </div>


                <div>

                    <strong>Bill Type:</strong>

                    <?= $isTaxInvoice
                        ? 'Tax Invoice'
                        : 'Retail Receipt'
                    ?>

                </div>


                <div>

                    <strong>Payment:</strong>


                    <?php

                    $statusClass = 'status-pending';

                    if ($sale['payment_status'] === 'paid') {

                        $statusClass = 'status-paid';

                    } elseif (
                        $sale['payment_status'] === 'partial'
                    ) {

                        $statusClass = 'status-partial';

                    }

                    ?>


                    <span class="status <?= $statusClass ?>">

                        <?= clean($paymentStatus) ?>

                    </span>

                </div>


                <div>

                    <strong>Date:</strong>

                    <?= date(
                        'd M Y',
                        strtotime($sale['sale_date'])
                    ) ?>

                </div>

            </div>

        </div>

    </div>



    <!-- ==================================================
         ITEMS
    ================================================== -->

    <div class="items-section">

        <table class="items-table">


            <thead>

                <tr>

                    <th style="width: 35px;">
                        #
                    </th>

                    <th>
                        Product / Description
                    </th>

                    <th
                        class="text-center"
                        style="width: 55px;"
                    >
                        Qty
                    </th>

                    <th
                        class="text-right"
                        style="width: 80px;"
                    >
                        Rate
                    </th>

                    <th
                        class="text-right"
                        style="width: 75px;"
                    >
                        Discount
                    </th>


                    <?php if ($hasGst): ?>

                        <th
                            class="text-right"
                            style="width: 65px;"
                        >
                            GST
                        </th>

                    <?php endif; ?>


                    <th
                        class="text-right"
                        style="width: 95px;"
                    >
                        Amount
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (empty($items)): ?>

                <tr>

                    <td
                        colspan="<?= $hasGst ? 7 : 6 ?>"
                        class="text-center"
                        style="padding: 25px;"
                    >

                        No items found.

                    </td>

                </tr>


            <?php else: ?>


                <?php foreach ($items as $index => $item): ?>

                    <tr>

                        <td>

                            <?= $index + 1 ?>

                        </td>


                        <td>

                            <div class="product-name">

                                <?= clean(
                                    $item['product_name']
                                ) ?>

                            </div>


                            <div class="product-code">

                                <?= clean(
                                    $item['product_code']
                                ) ?>


                                <?php if (!empty($item['unit'])): ?>

                                    · <?= clean(
                                        $item['unit']
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        </td>


                        <td class="text-center">

                            <?= number_format(
                                (float)$item['quantity'],
                                2
                            ) ?>

                        </td>


                        <td class="text-right">

                            <?= money(
                                $item['unit_price']
                            ) ?>

                        </td>


                        <td class="text-right">

                            <?= money(
                                $item['discount']
                            ) ?>

                        </td>


                        <?php if ($hasGst): ?>

                            <td class="text-right">

                                <?= number_format(
                                    (float)$item['gst_rate'],
                                    2
                                ) ?>%

                            </td>

                        <?php endif; ?>


                        <td class="text-right">

                            <?= money(
                                $item['line_total']
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php endif; ?>


            </tbody>

        </table>

    </div>



    <!-- ==================================================
         NOTES + TOTALS
    ================================================== -->

    <div class="lower-section">


        <!-- NOTES -->

        <div>

            <div class="notes-box">

                <div class="notes-title">

                    Notes / Terms

                </div>


                <div class="notes-text">


                    <?php if (!empty($sale['notes'])): ?>

                        <?= nl2br(
                            clean($sale['notes'])
                        ) ?>


                    <?php else: ?>

                        • Goods once sold are subject to company terms.<br>

                        • Please verify the invoice before leaving the counter.<br>

                        • Thank you for choosing ElectroCore.

                    <?php endif; ?>


                </div>

            </div>

        </div>



        <!-- TOTALS -->

        <div>

            <table class="amount-table">


                <tr>

                    <td class="amount-label">

                        Subtotal

                    </td>

                    <td class="amount-value">

                        <?= money(
                            $sale['subtotal']
                        ) ?>

                    </td>

                </tr>


                <?php if (
                    (float)$sale['discount'] > 0
                ): ?>

                    <tr>

                        <td class="amount-label">

                            Discount

                        </td>

                        <td class="amount-value">

                            - <?= money(
                                $sale['discount']
                            ) ?>

                        </td>

                    </tr>

                <?php endif; ?>


                <tr>

                    <td class="amount-label">

                        Taxable Amount

                    </td>

                    <td class="amount-value">

                        <?= money(
                            $sale['taxable_amount']
                        ) ?>

                    </td>

                </tr>


                <?php if (
                    (float)$sale['cgst_amount'] > 0
                ): ?>

                    <tr>

                        <td class="amount-label">

                            CGST

                        </td>

                        <td class="amount-value">

                            <?= money(
                                $sale['cgst_amount']
                            ) ?>

                        </td>

                    </tr>

                <?php endif; ?>


                <?php if (
                    (float)$sale['sgst_amount'] > 0
                ): ?>

                    <tr>

                        <td class="amount-label">

                            SGST

                        </td>

                        <td class="amount-value">

                            <?= money(
                                $sale['sgst_amount']
                            ) ?>

                        </td>

                    </tr>

                <?php endif; ?>


                <?php if (
                    (float)$sale['igst_amount'] > 0
                ): ?>

                    <tr>

                        <td class="amount-label">

                            IGST

                        </td>

                        <td class="amount-value">

                            <?= money(
                                $sale['igst_amount']
                            ) ?>

                        </td>

                    </tr>

                <?php endif; ?>


                <?php if (
                    (float)$sale['round_off'] != 0
                ): ?>

                    <tr>

                        <td class="amount-label">

                            Round Off

                        </td>

                        <td class="amount-value">

                            <?= money(
                                $sale['round_off']
                            ) ?>

                        </td>

                    </tr>

                <?php endif; ?>


                <tr class="total-row">

                    <td>

                        GRAND TOTAL

                    </td>

                    <td class="text-right">

                        <?= money(
                            $sale['grand_total']
                        ) ?>

                    </td>

                </tr>


                <tr>

                    <td class="amount-label">

                        Paid

                    </td>

                    <td class="amount-value paid">

                        <?= money(
                            $sale['paid_amount']
                        ) ?>

                    </td>

                </tr>


                <tr>

                    <td class="amount-label">

                        Balance Due

                    </td>

                    <td class="amount-value due">

                        <?= money(
                            $sale['due_amount']
                        ) ?>

                    </td>

                </tr>


            </table>

        </div>

    </div>



    <!-- ==================================================
         FOOTER
    ================================================== -->

    <div class="invoice-footer">


        <div>

            <div class="footer-brand">

                ELECTROCORE

            </div>

            Computerized invoice — no signature required.

        </div>


        <div class="footer-right">

            Invoice:
            <?= clean(
                $sale['invoice_number']
            ) ?><br>

            Generated:
            <?= date(
                'd M Y, h:i A'
            ) ?>

        </div>


    </div>


    <div class="thank-you">

        Thank you for choosing ElectroCore.

    </div>


</div>



<script>

document.addEventListener(
    "keydown",
    function(event) {

        if (
            (event.ctrlKey || event.metaKey) &&
            event.key.toLowerCase() === "p"
        ) {

            event.preventDefault();

            window.print();

        }

    }
);

</script>


</body>

</html>