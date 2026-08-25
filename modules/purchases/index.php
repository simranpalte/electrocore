<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$full_name = $_SESSION["full_name"] ?? "User";
$role = $_SESSION["role"] ?? "user";

$display_role = ucwords(
    str_replace("_", " ", $role)
);

$today_date = date("l, d F Y");


/*
|--------------------------------------------------------------------------
| FETCH PURCHASES
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.purchase_number,
            p.purchase_date,
            p.subtotal,
            p.discount,
            p.gst_amount,
            p.other_charges,
            p.grand_total,
            p.paid_amount,
            p.due_amount,
            p.payment_status,
            s.company_name
        FROM purchases p
        LEFT JOIN suppliers s 
            ON p.supplier_id = s.id
        ORDER BY p.id DESC
    ");

    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());

}


/*
|--------------------------------------------------------------------------
| PURCHASE STATISTICS
|--------------------------------------------------------------------------
*/

$totalPurchases = count($purchases);

$totalPurchaseValue = 0;
$totalPaid = 0;
$totalDue = 0;

foreach ($purchases as $purchase) {

    $totalPurchaseValue += (float)$purchase["grand_total"];
    $totalPaid += (float)$purchase["paid_amount"];
    $totalDue += (float)$purchase["due_amount"];

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

    <title>ElectroCore | Purchases</title>


    <!-- DASHBOARD CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | PURCHASES PAGE
        |--------------------------------------------------------------------------
        */

        .purchase-page {
            padding-bottom: 30px;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGE HEADER
        |--------------------------------------------------------------------------
        */

        .purchase-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-end;

            gap: 20px;

            margin-bottom: 25px;

        }


        .purchase-heading .page-label {

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 1.5px;

            color: #5b8def;

            margin-bottom: 7px;

        }


        .purchase-heading h1 {

            font-size: 30px;

            font-weight: 700;

            margin: 0;

        }


        .purchase-heading p {

            margin-top: 7px;

            color: #8d98aa;

            font-size: 14px;

        }


        /*
        |--------------------------------------------------------------------------
        | ADD PURCHASE BUTTON
        |--------------------------------------------------------------------------
        */

        .add-purchase-btn {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 12px 18px;

            background: #2563eb;

            color: #ffffff;

            text-decoration: none;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 700;

            border: 1px solid #3b82f6;

            transition: all .2s ease;

            white-space: nowrap;

        }


        .add-purchase-btn:hover {

            background: #1d4ed8;

            transform: translateY(-1px);

        }


        .add-icon {

            font-size: 18px;

            line-height: 1;

        }


        /*
        |--------------------------------------------------------------------------
        | STAT CARDS
        |--------------------------------------------------------------------------
        */

        .purchase-stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;

            margin-bottom: 22px;

        }


        .purchase-stat {

            background: #111827;

            border: 1px solid #202a3a;

            border-radius: 12px;

            padding: 20px;

            position: relative;

            overflow: hidden;

        }


        .purchase-stat::after {

            content: "";

            position: absolute;

            width: 90px;

            height: 90px;

            right: -35px;

            top: -35px;

            border-radius: 50%;

            background: rgba(37, 99, 235, .08);

        }


        .purchase-stat-label {

            font-size: 11px;

            letter-spacing: 1px;

            text-transform: uppercase;

            color: #7f8ba0;

            font-weight: 700;

        }


        .purchase-stat-value {

            margin-top: 9px;

            font-size: 25px;

            font-weight: 700;

            color: #ffffff;

        }


        .purchase-stat-sub {

            margin-top: 7px;

            color: #7f8ba0;

            font-size: 12px;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN PURCHASE PANEL
        |--------------------------------------------------------------------------
        */

        .purchase-panel {

            background: #111827;

            border: 1px solid #202a3a;

            border-radius: 12px;

            overflow: hidden;

        }


        /*
        |--------------------------------------------------------------------------
        | PANEL HEADER
        |--------------------------------------------------------------------------
        */

        .purchase-panel-header {

            padding: 20px 22px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            border-bottom: 1px solid #202a3a;

        }


        .panel-title-area {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .panel-title-icon {

            width: 42px;

            height: 42px;

            border-radius: 10px;

            background: rgba(37, 99, 235, .12);

            color: #60a5fa;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            font-weight: 700;

        }


        .panel-title-area h2 {

            font-size: 17px;

            color: #ffffff;

            margin: 0;

        }


        .panel-title-area span {

            display: block;

            margin-top: 4px;

            font-size: 12px;

            color: #778398;

        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        .purchase-search {

            width: 260px;

            position: relative;

        }


        .purchase-search input {

            width: 100%;

            padding: 10px 13px 10px 38px;

            background: #0b1220;

            border: 1px solid #293449;

            color: #ffffff;

            border-radius: 8px;

            outline: none;

            font-size: 13px;

        }


        .purchase-search input::placeholder {

            color: #68758a;

        }


        .purchase-search input:focus {

            border-color: #3b82f6;

        }


        .search-icon {

            position: absolute;

            left: 13px;

            top: 50%;

            transform: translateY(-50%);

            color: #718096;

            font-size: 15px;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .purchase-table-wrapper {

            overflow-x: auto;

        }


        .purchase-table {

            width: 100%;

            min-width: 1050px;

            border-collapse: collapse;

        }


        .purchase-table th {

            padding: 14px 16px;

            text-align: left;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .8px;

            color: #718096;

            background: #0d1523;

            border-bottom: 1px solid #202a3a;

            white-space: nowrap;

        }


        .purchase-table td {

            padding: 15px 16px;

            color: #c8d0dc;

            font-size: 13px;

            border-bottom: 1px solid #1c2635;

            white-space: nowrap;

        }


        .purchase-table tbody tr {

            transition: background .15s ease;

        }


        .purchase-table tbody tr:hover {

            background: #151f2e;

        }


        .purchase-table tbody tr:last-child td {

            border-bottom: none;

        }


        /*
        |--------------------------------------------------------------------------
        | PURCHASE NUMBER
        |--------------------------------------------------------------------------
        */

        .purchase-number {

            color: #60a5fa;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | SUPPLIER
        |--------------------------------------------------------------------------
        */

        .supplier-name {

            color: #ffffff;

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        .purchase-date {

            color: #9aa6b8;

        }


        /*
        |--------------------------------------------------------------------------
        | AMOUNTS
        |--------------------------------------------------------------------------
        */

        .amount {

            font-weight: 600;

            color: #d8dee8;

        }


        .grand-total {

            font-weight: 700;

            color: #ffffff;

        }


        .paid-amount {

            color: #4ade80;

            font-weight: 600;

        }


        .due-amount {

            color: #fbbf24;

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .purchase-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            text-transform: capitalize;

        }


        .purchase-status::before {

            content: "";

            width: 6px;

            height: 6px;

            border-radius: 50%;

        }


        .purchase-status.paid {

            background: rgba(34,197,94,.12);

            color: #4ade80;

        }


        .purchase-status.paid::before {

            background: #4ade80;

        }


        .purchase-status.partial {

            background: rgba(245,158,11,.12);

            color: #fbbf24;

        }


        .purchase-status.partial::before {

            background: #fbbf24;

        }


        .purchase-status.pending {

            background: rgba(239,68,68,.12);

            color: #f87171;

        }


        .purchase-status.pending::before {

            background: #f87171;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .purchase-empty {

            text-align: center;

            padding: 65px 20px;

            color: #7d899b;

        }


        .purchase-empty-icon {

            width: 58px;

            height: 58px;

            margin: 0 auto 15px;

            border-radius: 14px;

            background: #172235;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #5b8def;

            font-size: 25px;

        }


        .purchase-empty h3 {

            color: #d8dee8;

            font-size: 16px;

            margin-bottom: 7px;

        }


        .purchase-empty p {

            font-size: 13px;

        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .purchase-stats {

                grid-template-columns: 1fr;

            }


            .purchase-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .purchase-search {

                width: 100%;

            }


            .purchase-panel-header {

                align-items: flex-start;

                flex-direction: column;

            }

        }


        @media (max-width: 600px) {

            .purchase-heading h1 {

                font-size: 25px;

            }


            .add-purchase-btn {

                width: 100%;

                justify-content: center;

            }

        }

    </style>

</head>


<body>


<div class="dashboard">


    <!--
    |--------------------------------------------------------------------------
    | SIDEBAR
    |--------------------------------------------------------------------------
    -->

    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>


    <!--
    |--------------------------------------------------------------------------
    | MAIN CONTENT
    |--------------------------------------------------------------------------
    -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="page-heading">

                <div class="page-label">
                    PROCUREMENT
                </div>

                <h1>
                    Purchases
                </h1>

                <p>
                    Manage supplier purchases and payments.
                </p>

            </div>


            <div class="topbar-right">


                <div class="date-display">

                    <span class="date-icon">
                        ◷
                    </span>

                    <span>
                        <?= htmlspecialchars($today_date) ?>
                    </span>

                </div>


                <div class="user-profile">

                    <div class="user-avatar">

                        <?= strtoupper(
                            substr($full_name, 0, 1)
                        ) ?>

                    </div>


                    <div class="user-details">

                        <strong>
                            <?= htmlspecialchars($full_name) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($display_role) ?>
                        </span>

                    </div>

                </div>

            </div>

        </header>


        <!-- PURCHASE PAGE -->

        <section class="purchase-page">


            <!-- PAGE HEADER -->

            <div class="purchase-header">

                <div class="purchase-heading">

                    <div class="page-label">
                        PURCHASE MANAGEMENT
                    </div>

                    <h1>
                        Supplier Purchases
                    </h1>

                    <p>
                        Track purchases, supplier payments and outstanding dues.
                    </p>

                </div>


                <a
                    href="add.php"
                    class="add-purchase-btn"
                >

                    <span class="add-icon">
                        +
                    </span>

                    Add Purchase

                </a>

            </div>


            <!--
            |--------------------------------------------------------------------------
            | STATISTICS
            |--------------------------------------------------------------------------
            -->

            <div class="purchase-stats">


                <div class="purchase-stat">

                    <div class="purchase-stat-label">
                        Total Purchases
                    </div>

                    <div class="purchase-stat-value">
                        <?= number_format($totalPurchases) ?>
                    </div>

                    <div class="purchase-stat-sub">
                        Purchase records
                    </div>

                </div>


                <div class="purchase-stat">

                    <div class="purchase-stat-label">
                        Purchase Value
                    </div>

                    <div class="purchase-stat-value">
                        ₹<?= number_format($totalPurchaseValue, 2) ?>
                    </div>

                    <div class="purchase-stat-sub">
                        Total supplier purchases
                    </div>

                </div>


                <div class="purchase-stat">

                    <div class="purchase-stat-label">
                        Outstanding Due
                    </div>

                    <div class="purchase-stat-value">
                        ₹<?= number_format($totalDue, 2) ?>
                    </div>

                    <div class="purchase-stat-sub">
                        Pending supplier payments
                    </div>

                </div>


            </div>


            <!--
            |--------------------------------------------------------------------------
            | PURCHASE PANEL
            |--------------------------------------------------------------------------
            -->

            <div class="purchase-panel">


                <div class="purchase-panel-header">


                    <div class="panel-title-area">

                        <div class="panel-title-icon">
                            ↓
                        </div>


                        <div>

                            <h2>
                                Purchase Records
                            </h2>

                            <span>
                                Complete history of supplier purchases
                            </span>

                        </div>

                    </div>


                    <div class="purchase-search">

                        <span class="search-icon">
                            ⌕
                        </span>

                        <input
                            type="text"
                            id="purchaseSearch"
                            placeholder="Search purchases..."
                            oninput="searchPurchases()"
                        >

                    </div>

                </div>


                <?php if (empty($purchases)): ?>


                    <!-- EMPTY -->

                    <div class="purchase-empty">

                        <div class="purchase-empty-icon">
                            ↓
                        </div>

                        <h3>
                            No Purchases Found
                        </h3>

                        <p>
                            Start by adding your first supplier purchase.
                        </p>

                    </div>


                <?php else: ?>


                    <!-- TABLE -->

                    <div class="purchase-table-wrapper">

                        <table class="purchase-table">


                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Purchase No.
                                    </th>

                                    <th>
                                        Supplier
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Subtotal
                                    </th>

                                    <th>
                                        GST
                                    </th>

                                    <th>
                                        Grand Total
                                    </th>

                                    <th>
                                        Paid
                                    </th>

                                    <th>
                                        Due
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="purchaseTableBody">


                                <?php foreach ($purchases as $purchase): ?>


                                    <?php

                                    $status =
                                        strtolower(
                                            trim(
                                                $purchase["payment_status"]
                                            )
                                        );

                                    ?>


                                    <tr
                                        class="purchase-row"
                                        data-search="<?= htmlspecialchars(
                                            strtolower(
                                                ($purchase["purchase_number"] ?? "") . " " .
                                                ($purchase["company_name"] ?? "") . " " .
                                                ($purchase["purchase_date"] ?? "") . " " .
                                                $status
                                            )
                                        ) ?>"
                                    >


                                        <td>

                                            <?= htmlspecialchars(
                                                $purchase["id"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <span class="purchase-number">

                                                <?= htmlspecialchars(
                                                    $purchase["purchase_number"]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="supplier-name">

                                                <?= htmlspecialchars(
                                                    $purchase["company_name"]
                                                    ?? "Unknown Supplier"
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="purchase-date">

                                                <?= date(
                                                    "d M Y",
                                                    strtotime(
                                                        $purchase["purchase_date"]
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="amount">

                                                ₹<?= number_format(
                                                    (float)$purchase["subtotal"],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="amount">

                                                ₹<?= number_format(
                                                    (float)$purchase["gst_amount"],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="grand-total">

                                                ₹<?= number_format(
                                                    (float)$purchase["grand_total"],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="paid-amount">

                                                ₹<?= number_format(
                                                    (float)$purchase["paid_amount"],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="due-amount">

                                                ₹<?= number_format(
                                                    (float)$purchase["due_amount"],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="purchase-status <?= htmlspecialchars($status) ?>"
                                            >

                                                <?= ucfirst(
                                                    htmlspecialchars($status)
                                                ) ?>

                                            </span>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>

                    </div>


                <?php endif; ?>


            </div>


        </section>


        <!-- FOOTER -->

        <footer class="dashboard-footer">

            <span>
                © <?= date("Y") ?> ElectroCore
            </span>

            <span>
                Billing & Inventory Management System
            </span>

        </footer>


    </main>


</div>



<script>


/*
|--------------------------------------------------------------------------
| PURCHASE SEARCH
|--------------------------------------------------------------------------
*/

function searchPurchases() {

    const input =
        document.getElementById("purchaseSearch");

    const search =
        input.value
            .toLowerCase()
            .trim();


    const rows =
        document.querySelectorAll(
            ".purchase-row"
        );


    rows.forEach(function(row) {

        const data =
            row.dataset.search || "";


        if (
            search === "" ||
            data.includes(search)
        ) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

}


</script>


</body>

</html>