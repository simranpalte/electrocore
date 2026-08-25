<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: /electrocore/index.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";


/*
|--------------------------------------------------------------------------
| FETCH PAYMENTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.payment_number,
        p.sale_id,
        p.purchase_id,
        p.customer_id,
        p.supplier_id,
        p.payment_type,
        p.payment_method,
        p.amount,
        p.reference_number,
        p.payment_date,
        p.notes,

        c.full_name AS customer_name,
        s.company_name AS supplier_name

    FROM payments p

    LEFT JOIN customers c
        ON p.customer_id = c.id

    LEFT JOIN suppliers s
        ON p.supplier_id = s.id

    ORDER BY p.id DESC
";

$stmt = $pdo->query($sql);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ElectroCore | Payments</title>


    <!-- =====================================================
         ELECTROCORE DASHBOARD CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="/electrocore/assets/css/dashboard.css"
    >


    <style>

        /* =====================================================
           PAYMENTS PAGE
        ====================================================== */

        .payments-page {
            width: 100%;
        }


        /* =====================================================
           PAGE HEADER
        ====================================================== */

        .payments-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 28px;

        }


        .payments-label {

            color: #12b8ff;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 2px;

            margin-bottom: 8px;

        }


        .payments-header h1 {

            color: #ffffff;

            font-size: 30px;

            font-weight: 700;

            margin: 0 0 7px 0;

        }


        .payments-header p {

            color: #96a8bb;

            font-size: 14px;

            margin: 0;

        }


        /* =====================================================
           ADD PAYMENT BUTTON
        ====================================================== */

        .add-payment-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 18px;

            background: #0878ff;

            border: 1px solid #0878ff;

            border-radius: 8px;

            color: #ffffff;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

            transition: 0.2s ease;

            box-shadow:
                0 6px 18px
                rgba(8, 120, 255, 0.18);

        }


        .add-payment-btn:hover {

            background: #12b8ff;

            border-color: #12b8ff;

            transform: translateY(-1px);

        }


        /* =====================================================
           MAIN PAYMENT PANEL
        ====================================================== */

        .payments-panel {

            background:
                linear-gradient(
                    145deg,
                    #0b1726,
                    #09131f
                );

            border: 1px solid rgba(255, 255, 255, 0.07);

            border-radius: 13px;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.18);

            overflow: hidden;

        }


        /* =====================================================
           PANEL HEADER
        ====================================================== */

        .payments-panel-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 22px;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.07);

        }


        .payments-title {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .payments-icon {

            width: 40px;

            height: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                rgba(18, 184, 255, 0.10);

            border:
                1px solid
                rgba(18, 184, 255, 0.22);

            color: #12b8ff;

            font-size: 18px;

            font-weight: 700;

        }


        .payments-title h2 {

            color: #ffffff;

            font-size: 17px;

            margin: 0 0 4px 0;

        }


        .payments-title p {

            color: #60758a;

            font-size: 12px;

            margin: 0;

        }


        /* =====================================================
           TABLE WRAPPER
        ====================================================== */

        .payments-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        /* =====================================================
           TABLE
        ====================================================== */

        .payments-table {

            width: 100%;

            min-width: 1100px;

            border-collapse: collapse;

        }


        .payments-table thead th {

            background: #0e1c2d;

            color: #96a8bb;

            padding: 13px 14px;

            text-align: left;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.7px;

            font-weight: 700;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.08);

            white-space: nowrap;

        }


        .payments-table tbody td {

            padding: 14px;

            border-bottom:
                1px solid
                rgba(255, 255, 255, 0.06);

            color: #d7deea;

            font-size: 13px;

            white-space: nowrap;

        }


        .payments-table tbody tr {

            transition: 0.2s ease;

        }


        .payments-table tbody tr:hover td {

            background:
                rgba(18, 184, 255, 0.025);

        }


        .payments-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =====================================================
           PAYMENT NUMBER
        ====================================================== */

        .payment-number {

            color: #ffffff;

            font-weight: 700;

        }


        /* =====================================================
           PAYMENT TYPE
        ====================================================== */

        .payment-type {

            display: inline-flex;

            align-items: center;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            border: 1px solid;

        }


        .type-sale {

            background:
                rgba(18, 184, 255, 0.10);

            color: #60cfff;

            border-color:
                rgba(18, 184, 255, 0.25);

        }


        .type-purchase {

            background:
                rgba(168, 85, 247, 0.10);

            color: #c084fc;

            border-color:
                rgba(168, 85, 247, 0.25);

        }


        .type-customer {

            background:
                rgba(34, 197, 94, 0.10);

            color: #4ade80;

            border-color:
                rgba(34, 197, 94, 0.22);

        }


        .type-supplier {

            background:
                rgba(245, 184, 46, 0.10);

            color: #f5b82e;

            border-color:
                rgba(245, 184, 46, 0.22);

        }


        /* =====================================================
           PARTY
        ====================================================== */

        .party-name {

            color: #edf2f7;

            font-weight: 600;

        }


        /* =====================================================
           AMOUNT
        ====================================================== */

        .payment-amount {

            color: #12b8ff;

            font-weight: 700;

        }


        /* =====================================================
           METHOD
        ====================================================== */

        .payment-method {

            color: #b7c2d3;

        }


        /* =====================================================
           REFERENCE
        ====================================================== */

        .payment-reference {

            color: #8ea0b8;

        }


        /* =====================================================
           DATE
        ====================================================== */

        .payment-date {

            color: #9aa6b8;

            font-size: 12px;

        }


        /* =====================================================
           ACTIONS
        ====================================================== */

        .payment-actions {

            display: flex;

            align-items: center;

            gap: 7px;

        }


        .payment-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 7px 10px;

            background: #07111e;

            border:
                1px solid
                rgba(255, 255, 255, 0.08);

            border-radius: 6px;

            color: #8fa0b7;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

            transition: 0.2s ease;

        }


        .payment-action:hover {

            border-color: #12b8ff;

            color: #12b8ff;

            background:
                rgba(18, 184, 255, 0.08);

        }


        .payment-delete:hover {

            border-color:
                rgba(239, 68, 68, 0.40);

            color: #f87171;

            background:
                rgba(239, 68, 68, 0.08);

        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .payments-empty {

            text-align: center;

            padding: 65px 20px !important;

        }


        .payments-empty-icon {

            width: 50px;

            height: 50px;

            margin: 0 auto 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background:
                rgba(18, 184, 255, 0.08);

            border:
                1px solid
                rgba(18, 184, 255, 0.16);

            color: #12b8ff;

            font-size: 21px;

        }


        .payments-empty strong {

            display: block;

            color: #aeb8c8;

            font-size: 14px;

            margin-bottom: 5px;

        }


        .payments-empty span {

            color: #667387;

            font-size: 12px;

        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .payments-footer {

            display: flex;

            justify-content: space-between;

            color: #566174;

            font-size: 11px;

            padding: 18px 2px 5px;

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 900px) {

            .payments-header {

                flex-direction: column;

            }


            .add-payment-btn {

                width: 100%;

            }

        }


        @media (max-width: 600px) {

            .payments-header h1 {

                font-size: 25px;

            }


            .payments-panel-header {

                padding: 17px;

            }


            .payments-footer {

                flex-direction: column;

                gap: 6px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     ELECTROCORE MAIN LAYOUT
========================================================== -->

<div class="dashboard">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php
        require_once __DIR__ . "/../../includes/sidebar.php";
    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <div class="payments-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <header class="payments-header">

                <div>

                    <div class="payments-label">
                        FINANCE
                    </div>

                    <h1>
                        Payments
                    </h1>

                    <p>
                        Manage customer and supplier payments.
                    </p>

                </div>


                <a
                    href="add.php"
                    class="add-payment-btn"
                >

                    <span>+</span>

                    Add Payment

                </a>

            </header>


            <!-- =================================================
                 PAYMENT PANEL
            ================================================== -->

            <section class="payments-panel">


                <div class="payments-panel-header">

                    <div class="payments-title">

                        <div class="payments-icon">
                            ₹
                        </div>

                        <div>

                            <h2>
                                Payment Records
                            </h2>

                            <p>
                                View and manage all recorded payments
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     PAYMENT TABLE
                ================================================== -->

                <div class="payments-table-wrapper">

                    <table class="payments-table">

                        <thead>

                            <tr>

                                <th>
                                    Payment No.
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Party
                                </th>

                                <th>
                                    Sale / Purchase
                                </th>

                                <th>
                                    Payment Method
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Reference No.
                                </th>

                                <th>
                                    Payment Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (!empty($payments)): ?>


                            <?php foreach ($payments as $payment): ?>


                                <?php

                                /* PAYMENT TYPE */

                                $type =
                                    $payment["payment_type"] ?? "";


                                $typeLabel =
                                    ucwords(
                                        str_replace(
                                            "_",
                                            " ",
                                            $type
                                        )
                                    );


                                $typeClass =
                                    "type-sale";


                                if (
                                    strpos(
                                        $type,
                                        "purchase"
                                    ) !== false
                                ) {

                                    $typeClass =
                                        "type-purchase";

                                }
                                elseif (
                                    strpos(
                                        $type,
                                        "customer"
                                    ) !== false
                                ) {

                                    $typeClass =
                                        "type-customer";

                                }
                                elseif (
                                    strpos(
                                        $type,
                                        "supplier"
                                    ) !== false
                                ) {

                                    $typeClass =
                                        "type-supplier";

                                }


                                /* PARTY */

                                if (
                                    !empty(
                                        $payment["customer_name"]
                                    )
                                ) {

                                    $party =
                                        $payment["customer_name"];

                                }
                                elseif (
                                    !empty(
                                        $payment["supplier_name"]
                                    )
                                ) {

                                    $party =
                                        $payment["supplier_name"];

                                }
                                else {

                                    $party = "—";

                                }


                                /* TRANSACTION */

                                if (
                                    !empty(
                                        $payment["sale_id"]
                                    )
                                ) {

                                    $transaction =
                                        "Sale #" .
                                        $payment["sale_id"];

                                }
                                elseif (
                                    !empty(
                                        $payment["purchase_id"]
                                    )
                                ) {

                                    $transaction =
                                        "Purchase #" .
                                        $payment["purchase_id"];

                                }
                                else {

                                    $transaction = "—";

                                }


                                /* PAYMENT METHOD */

                                $paymentMethod =
                                    ucwords(
                                        str_replace(
                                            "_",
                                            " ",
                                            $payment[
                                                "payment_method"
                                            ] ?? ""
                                        )
                                    );


                                /* REFERENCE */

                                $reference =
                                    !empty(
                                        $payment[
                                            "reference_number"
                                        ]
                                    )
                                    ?
                                    $payment[
                                        "reference_number"
                                    ]
                                    :
                                    "—";

                                ?>


                                <tr>


                                    <!-- PAYMENT NUMBER -->

                                    <td>

                                        <span class="payment-number">

                                            <?= htmlspecialchars(
                                                $payment[
                                                    "payment_number"
                                                ]
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- TYPE -->

                                    <td>

                                        <span
                                            class="
                                                payment-type
                                                <?= htmlspecialchars(
                                                    $typeClass
                                                ) ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $typeLabel
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PARTY -->

                                    <td>

                                        <span class="party-name">

                                            <?= htmlspecialchars(
                                                $party
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- TRANSACTION -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $transaction
                                        ) ?>

                                    </td>


                                    <!-- PAYMENT METHOD -->

                                    <td>

                                        <span class="payment-method">

                                            <?= htmlspecialchars(
                                                $paymentMethod
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- AMOUNT -->

                                    <td>

                                        <span class="payment-amount">

                                            ₹<?= number_format(
                                                (float)
                                                $payment["amount"],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- REFERENCE -->

                                    <td>

                                        <span class="payment-reference">

                                            <?= htmlspecialchars(
                                                $reference
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <span class="payment-date">

                                            <?= !empty(
                                                $payment["payment_date"]
                                            )
                                            ?
                                            date(
                                                "d M Y, h:i A",
                                                strtotime(
                                                    $payment[
                                                        "payment_date"
                                                    ]
                                                )
                                            )
                                            :
                                            "—"
                                            ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="payment-actions">


                                            <a
                                                href="add.php?id=<?= (int) $payment["id"] ?>"
                                                class="payment-action"
                                            >
                                                Edit
                                            </a>


                                            <a
                                                href="delete.php?id=<?= (int) $payment["id"] ?>"
                                                class="
                                                    payment-action
                                                    payment-delete
                                                "
                                                onclick="
                                                    return confirm(
                                                        'Are you sure you want to delete this payment?'
                                                    );
                                                "
                                            >
                                                Delete
                                            </a>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="9"
                                    class="payments-empty"
                                >

                                    <div class="payments-empty-icon">
                                        ₹
                                    </div>

                                    <strong>
                                        No payments found
                                    </strong>

                                    <span>
                                        Payments will appear here once they are recorded.
                                    </span>

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


            </section>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <footer class="payments-footer">

                <span>
                    © <?= date("Y") ?> ElectroCore
                </span>

                <span>
                    Billing & Inventory Management System
                </span>

            </footer>


        </div>


    </main>


</div>


</body>

</html>