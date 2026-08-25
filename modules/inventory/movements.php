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
| Fetch Stock Movements
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        sm.id,
        sm.created_at,
        sm.movement_type,
        sm.reference_number,
        sm.quantity,
        sm.stock_before,
        sm.stock_after,
        sm.notes,
        p.product_code,
        p.product_name,
        p.unit,
        u.full_name AS created_by_name
    FROM stock_movements sm

    INNER JOIN products p
        ON sm.product_id = p.id

    INNER JOIN users u
        ON sm.created_by = u.id

    ORDER BY sm.created_at DESC
");

$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Movement Labels
|--------------------------------------------------------------------------
*/

$movement_labels = [

    "opening_stock" => "Opening Stock",

    "purchase" => "Purchase",

    "sale" => "Sale",

    "sale_return" => "Sale Return",

    "adjustment_in" => "Stock Adjustment In",

    "adjustment_out" => "Stock Adjustment Out",

    "damage" => "Damaged Stock",

    "stock_adjustment_in" => "Stock Adjustment In",

    "stock_adjustment_out" => "Stock Adjustment Out"

];

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
        ElectroCore | Stock Movement History
    </title>

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
                    Stock Movement History
                </h1>

                <p>
                    View all inventory transactions and stock changes
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


        <!-- PAGE CARD -->

        <div
            class="stat-card"
            style="margin-top:20px;"
        >

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:20px;
                "
            >

                <div>

                    <h2>
                        Inventory Transactions
                    </h2>

                    <p>
                        Complete history of stock movements.
                    </p>

                </div>


                <a
                    href="adjust.php"
                    style="
                        background:#0ea5e9;
                        color:white;
                        padding:12px 18px;
                        border-radius:8px;
                        text-decoration:none;
                        font-weight:600;
                    "
                >
                    + Stock Adjustment
                </a>

            </div>


            <!-- TABLE -->

            <div
                style="
                    overflow-x:auto;
                "
            >

                <table
                    style="
                        width:100%;
                        border-collapse:collapse;
                    "
                >

                    <thead>

                        <tr>

                            <th
                                style="
                                    text-align:left;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                Date
                            </th>


                            <th
                                style="
                                    text-align:left;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                Product
                            </th>


                            <th
                                style="
                                    text-align:left;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                Movement
                            </th>


                            <th
                                style="
                                    text-align:right;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                Quantity
                            </th>


                            <th
                                style="
                                    text-align:right;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                Before
                            </th>


                            <th
                                style="
                                    text-align:right;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                After
                            </th>


                            <th
                                style="
                                    text-align:left;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                Reference
                            </th>


                            <th
                                style="
                                    text-align:left;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                Notes
                            </th>


                            <th
                                style="
                                    text-align:left;
                                    padding:12px;
                                    border-bottom:1px solid #ddd;
                                "
                            >
                                User
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (count($movements) > 0): ?>


                        <?php foreach ($movements as $movement): ?>


                            <tr>


                                <!-- DATE -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                        white-space:nowrap;
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        date(
                                            "d M Y, h:i A",
                                            strtotime(
                                                $movement["created_at"]
                                            )
                                        )
                                    );
                                    ?>

                                </td>


                                <!-- PRODUCT -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                    "
                                >

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $movement["product_name"]
                                        );
                                        ?>

                                    </strong>

                                    <br>

                                    <small>

                                        <?php
                                        echo htmlspecialchars(
                                            $movement["product_code"]
                                        );
                                        ?>

                                    </small>

                                </td>


                                <!-- MOVEMENT -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                    "
                                >

                                    <?php

                                    echo htmlspecialchars(

                                        $movement_labels[
                                            $movement["movement_type"]
                                        ]
                                        ??
                                        ucwords(
                                            str_replace(
                                                "_",
                                                " ",
                                                $movement["movement_type"]
                                            )
                                        )

                                    );

                                    ?>

                                </td>


                                <!-- QUANTITY -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                        text-align:right;
                                        font-weight:600;
                                    "
                                >

                                    <?php
                                    echo number_format(
                                        (float)$movement["quantity"],
                                        2
                                    );
                                    ?>

                                    <?php
                                    echo htmlspecialchars(
                                        $movement["unit"]
                                    );
                                    ?>

                                </td>


                                <!-- BEFORE -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                        text-align:right;
                                    "
                                >

                                    <?php
                                    echo number_format(
                                        (float)$movement["stock_before"],
                                        2
                                    );
                                    ?>

                                </td>


                                <!-- AFTER -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                        text-align:right;
                                        font-weight:600;
                                    "
                                >

                                    <?php
                                    echo number_format(
                                        (float)$movement["stock_after"],
                                        2
                                    );
                                    ?>

                                </td>


                                <!-- REFERENCE -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                    "
                                >

                                    <?php

                                    echo $movement["reference_number"]
                                        !== null
                                        ? htmlspecialchars(
                                            $movement["reference_number"]
                                        )
                                        : "-";

                                    ?>

                                </td>


                                <!-- NOTES -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                    "
                                >

                                    <?php

                                    echo $movement["notes"]
                                        !== null
                                        ? htmlspecialchars(
                                            $movement["notes"]
                                        )
                                        : "-";

                                    ?>

                                </td>


                                <!-- USER -->

                                <td
                                    style="
                                        padding:12px;
                                        border-bottom:1px solid #eee;
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $movement["created_by_name"]
                                    );
                                    ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="9"
                                style="
                                    padding:30px;
                                    text-align:center;
                                "
                            >

                                No stock movements found.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


    </main>

</div>

</body>

</html>