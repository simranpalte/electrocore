<?php

require_once "../../config/database.php";

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Generate Payment Number
|--------------------------------------------------------------------------
*/

$payment_number = "PAY-" . date("YmdHis");


/*
|--------------------------------------------------------------------------
| Fetch Customers
|--------------------------------------------------------------------------
*/

$customer_stmt = $pdo->query("
    SELECT
        id,
        full_name
    FROM customers
    WHERE status = 'active'
    ORDER BY full_name ASC
");

$customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Fetch Suppliers
|--------------------------------------------------------------------------
*/

$supplier_stmt = $pdo->query("
    SELECT
        id,
        company_name
    FROM suppliers
    WHERE status = 'active'
    ORDER BY company_name ASC
");

$suppliers = $supplier_stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| EDIT MODE
|--------------------------------------------------------------------------
*/

$payment = null;

if (
    isset($_GET['id']) &&
    is_numeric($_GET['id'])
) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM payments
        WHERE id = ?
    ");

    $stmt->execute([
        (int) $_GET['id']
    ]);

    $payment = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($payment) {

        $payment_number =
            $payment['payment_number'];

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

<?= $payment
    ? 'Edit Payment'
    : 'Add Payment'
?>

- ElectroCore

</title>


<style>

/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
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
| PAGE LAYOUT
|--------------------------------------------------------------------------
|
| Sidebar occupies its own fixed area.
| Main content starts beside it.
|--------------------------------------------------------------------------
*/

.page-layout {

    display:
        flex;

    min-height:
        100vh;

}


/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/

.sidebar {

    width:
        240px;

    min-width:
        240px;

    position:
        fixed;

    top:
        0;

    left:
        0;

    bottom:
        0;

    overflow-y:
        auto;

    z-index:
        100;

}


/*
|--------------------------------------------------------------------------
| MAIN AREA
|--------------------------------------------------------------------------
*/

.main-content {

    margin-left:
        240px;

    width:
        calc(100% - 240px);

    min-height:
        100vh;

    padding:
        30px 34px 40px;

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
        center;

    gap:
        20px;

    margin-bottom:
        28px;

}


.page-header-left {

    min-width:
        0;

}


.page-label {

    color:
        #3b82f6;

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

    color:
        #ffffff;

    font-size:
        29px;

    font-weight:
        700;

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

    white-space:
        nowrap;

    transition:
        0.2s;

}


.back-button:hover {

    border-color:
        #3b82f6;

    color:
        #60a5fa;

    background:
        #151d2b;

}


/*
|--------------------------------------------------------------------------
| FORM CARD
|--------------------------------------------------------------------------
*/

.form-card {

    width:
        100%;

    max-width:
        1250px;

    background:
        #111722;

    border:
        1px solid #202938;

    border-radius:
        13px;

    padding:
        26px;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,0.20);

}


/*
|--------------------------------------------------------------------------
| CARD HEADER
|--------------------------------------------------------------------------
*/

.card-header {

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    margin-bottom:
        25px;

    padding-bottom:
        20px;

    border-bottom:
        1px solid #202938;

}


.card-icon {

    width:
        42px;

    height:
        42px;

    border-radius:
        10px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        rgba(59,130,246,0.10);

    border:
        1px solid
        rgba(59,130,246,0.20);

    color:
        #3b82f6;

    font-size:
        19px;

    font-weight:
        700;

}


.card-header h2 {

    color:
        #ffffff;

    font-size:
        17px;

    font-weight:
        700;

}


.card-header p {

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

.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap:
        21px 24px;

}


/*
|--------------------------------------------------------------------------
| FORM GROUP
|--------------------------------------------------------------------------
*/

.form-group {

    display:
        flex;

    flex-direction:
        column;

    min-width:
        0;

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


/*
|--------------------------------------------------------------------------
| INPUTS
|--------------------------------------------------------------------------
*/

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

    background:
        #0c111a;

    color:
        #edf2f7;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size:
        13px;

    transition:
        0.2s;

}


input::placeholder,
textarea::placeholder {

    color:
        #566174;

}


input:hover,
select:hover,
textarea:hover {

    border-color:
        #35445a;

}


input:focus,
select:focus,
textarea:focus {

    outline:
        none;

    border-color:
        #3b82f6;

    box-shadow:
        0 0 0 3px
        rgba(59,130,246,0.10);

}


input[readonly] {

    background:
        #171d28;

    color:
        #60a5fa;

    font-weight:
        700;

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


textarea {

    min-height:
        115px;

    resize:
        vertical;

    line-height:
        1.5;

}


/*
|--------------------------------------------------------------------------
| HINT
|--------------------------------------------------------------------------
*/

.hint {

    color:
        #647084;

    font-size:
        11px;

    margin-top:
        6px;

}


/*
|--------------------------------------------------------------------------
| PARTY SECTIONS
|--------------------------------------------------------------------------
*/

#customer_group,
#supplier_group {

    transition:
        0.2s;

}


/*
|--------------------------------------------------------------------------
| ACTION BUTTONS
|--------------------------------------------------------------------------
*/

.form-actions {

    display:
        flex;

    justify-content:
        flex-end;

    align-items:
        center;

    gap:
        12px;

    margin-top:
        28px;

    padding-top:
        22px;

    border-top:
        1px solid #202938;

}


.btn {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    min-height:
        42px;

    padding:
        11px 21px;

    border-radius:
        8px;

    font-size:
        13px;

    font-weight:
        700;

    text-decoration:
        none;

    cursor:
        pointer;

    border:
        1px solid transparent;

    transition:
        0.2s;

}


.btn-primary {

    background:
        #2563eb;

    color:
        #ffffff;

    box-shadow:
        0 6px 18px
        rgba(37,99,235,0.18);

}


.btn-primary:hover {

    background:
        #3b82f6;

    transform:
        translateY(-1px);

}


.btn-secondary {

    background:
        #111722;

    border-color:
        #293445;

    color:
        #aeb8c8;

}


.btn-secondary:hover {

    background:
        #181f2b;

    border-color:
        #3b4658;

    color:
        #ffffff;

}


/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.page-footer {

    max-width:
        1250px;

    display:
        flex;

    justify-content:
        space-between;

    gap:
        20px;

    color:
        #566174;

    font-size:
        11px;

    padding:
        18px 2px 0;

}


/*
|--------------------------------------------------------------------------
| SCROLLBAR
|--------------------------------------------------------------------------
*/

::-webkit-scrollbar {

    width:
        8px;

    height:
        8px;

}


::-webkit-scrollbar-track {

    background:
        #090d14;

}


::-webkit-scrollbar-thumb {

    background:
        #293445;

    border-radius:
        10px;

}


::-webkit-scrollbar-thumb:hover {

    background:
        #3b4658;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 1100px) {

    .main-content {

        padding:
            26px 25px 35px;

    }

    .form-card {

        max-width:
            none;

    }

}


@media (max-width: 850px) {

    .sidebar {

        width:
            210px;

        min-width:
            210px;

    }

    .main-content {

        margin-left:
            210px;

        width:
            calc(100% - 210px);

        padding:
            24px 20px;

    }

    .form-grid {

        grid-template-columns:
            1fr;

    }

    .form-group.full {

        grid-column:
            auto;

    }

}


@media (max-width: 650px) {

    .page-layout {

        display:
            block;

    }

    .sidebar {

        position:
            relative;

        width:
            100%;

        min-width:
            100%;

        height:
            auto;

        bottom:
            auto;

    }

    .main-content {

        margin-left:
            0;

        width:
            100%;

        padding:
            20px 15px 30px;

    }

    .page-header {

        flex-direction:
            column;

        align-items:
            flex-start;

    }

    .back-button {

        width:
            100%;

        justify-content:
            center;

    }

    .form-card {

        padding:
            20px 17px;

    }

    .form-actions {

        flex-direction:
            column-reverse;

    }

    .btn {

        width:
            100%;

    }

    .page-footer {

        flex-direction:
            column;

        gap:
            6px;

    }

}

</style>

</head>


<body>


<div class="page-layout">


    <!--
    |--------------------------------------------------------------------------
    | SIDEBAR
    |--------------------------------------------------------------------------
    -->

    <?php

    /*
    |----------------------------------------------------------------------
    | Use the same centralized ElectroCore sidebar
    |----------------------------------------------------------------------
    */

    include "../../includes/sidebar.php";

    ?>


    <!--
    |--------------------------------------------------------------------------
    | MAIN CONTENT
    |--------------------------------------------------------------------------
    -->

    <main class="main-content">


        <!-- PAGE HEADER -->

        <div class="page-header">


            <div class="page-header-left">

                <div class="page-label">
                    PAYMENTS
                </div>

                <h1>

                    <?= $payment
                        ? 'Edit Payment'
                        : 'Add Payment'
                    ?>

                </h1>

                <p>
                    Record a customer or supplier payment.
                </p>

            </div>


            <a
                href="index.php"
                class="back-button"
            >

                ← Back to Payments

            </a>


        </div>



        <!-- FORM CARD -->

        <div class="form-card">


            <!-- CARD HEADER -->

            <div class="card-header">


                <div class="card-icon">
                    ₹
                </div>


                <div>

                    <h2>
                        Payment Information
                    </h2>

                    <p>
                        Enter the details of the payment transaction.
                    </p>

                </div>


            </div>



            <form
                action="save.php"
                method="POST"
            >


                <?php if ($payment): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= htmlspecialchars(
                            $payment['id']
                        ) ?>"
                    >

                <?php endif; ?>


                <div class="form-grid">


                    <!-- PAYMENT NUMBER -->

                    <div class="form-group">

                        <label>
                            Payment Number
                        </label>

                        <input
                            type="text"
                            name="payment_number"
                            value="<?= htmlspecialchars(
                                $payment_number
                            ) ?>"
                            readonly
                        >

                    </div>



                    <!-- PAYMENT TYPE -->

                    <div class="form-group">

                        <label>
                            Payment Type *
                        </label>

                        <select
                            name="payment_type"
                            id="payment_type"
                            required
                        >

                            <option value="">
                                Select Payment Type
                            </option>


                            <option
                                value="sale_payment"
                                <?= (
                                    $payment &&
                                    $payment['payment_type']
                                    === 'sale_payment'
                                )
                                ? 'selected'
                                : '' ?>
                            >
                                Sale Payment
                            </option>


                            <option
                                value="purchase_payment"
                                <?= (
                                    $payment &&
                                    $payment['payment_type']
                                    === 'purchase_payment'
                                )
                                ? 'selected'
                                : '' ?>
                            >
                                Purchase Payment
                            </option>


                            <option
                                value="customer_refund"
                                <?= (
                                    $payment &&
                                    $payment['payment_type']
                                    === 'customer_refund'
                                )
                                ? 'selected'
                                : '' ?>
                            >
                                Customer Refund
                            </option>


                            <option
                                value="supplier_payment"
                                <?= (
                                    $payment &&
                                    $payment['payment_type']
                                    === 'supplier_payment'
                                )
                                ? 'selected'
                                : '' ?>
                            >
                                Supplier Payment
                            </option>

                        </select>

                    </div>



                    <!-- CUSTOMER -->

                    <div
                        class="form-group"
                        id="customer_group"
                    >

                        <label>
                            Customer
                        </label>

                        <select name="customer_id">

                            <option value="">
                                Select Customer
                            </option>


                            <?php foreach (
                                $customers
                                as $customer
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $customer['id']
                                    ) ?>"
                                    <?= (
                                        $payment &&
                                        $payment['customer_id']
                                        == $customer['id']
                                    )
                                    ? 'selected'
                                    : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $customer['full_name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- SUPPLIER -->

                    <div
                        class="form-group"
                        id="supplier_group"
                    >

                        <label>
                            Supplier
                        </label>

                        <select name="supplier_id">

                            <option value="">
                                Select Supplier
                            </option>


                            <?php foreach (
                                $suppliers
                                as $supplier
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $supplier['id']
                                    ) ?>"
                                    <?= (
                                        $payment &&
                                        $payment['supplier_id']
                                        == $supplier['id']
                                    )
                                    ? 'selected'
                                    : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $supplier['company_name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- SALE ID -->

                    <div class="form-group">

                        <label>
                            Sale ID
                        </label>

                        <input
                            type="number"
                            name="sale_id"
                            min="1"
                            value="<?= htmlspecialchars(
                                $payment['sale_id'] ?? ''
                            ) ?>"
                            placeholder="Optional"
                        >

                        <span class="hint">
                            Enter the related sale ID if applicable.
                        </span>

                    </div>



                    <!-- PURCHASE ID -->

                    <div class="form-group">

                        <label>
                            Purchase ID
                        </label>

                        <input
                            type="number"
                            name="purchase_id"
                            min="1"
                            value="<?= htmlspecialchars(
                                $payment['purchase_id'] ?? ''
                            ) ?>"
                            placeholder="Optional"
                        >

                        <span class="hint">
                            Enter the related purchase ID if applicable.
                        </span>

                    </div>



                    <!-- PAYMENT METHOD -->

                    <div class="form-group">

                        <label>
                            Payment Method *
                        </label>

                        <select
                            name="payment_method"
                            required
                        >

                            <option value="">
                                Select Payment Method
                            </option>


                            <?php

                            $methods = [
                                'cash',
                                'upi',
                                'card',
                                'bank_transfer',
                                'cheque'
                            ];

                            foreach (
                                $methods
                                as $method
                            ):

                            ?>

                                <option
                                    value="<?= $method ?>"
                                    <?= (
                                        $payment &&
                                        $payment['payment_method']
                                        === $method
                                    )
                                    ? 'selected'
                                    : '' ?>
                                >

                                    <?= ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $method
                                        )
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <!-- AMOUNT -->

                    <div class="form-group">

                        <label>
                            Amount *
                        </label>

                        <input
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            required
                            value="<?= htmlspecialchars(
                                $payment['amount'] ?? ''
                            ) ?>"
                            placeholder="0.00"
                        >

                    </div>



                    <!-- REFERENCE -->

                    <div class="form-group">

                        <label>
                            Reference Number
                        </label>

                        <input
                            type="text"
                            name="reference_number"
                            value="<?= htmlspecialchars(
                                $payment['reference_number'] ?? ''
                            ) ?>"
                            placeholder="Optional reference number"
                        >

                    </div>



                    <!-- DATE -->

                    <div class="form-group">

                        <label>
                            Payment Date *
                        </label>

                        <input
                            type="datetime-local"
                            name="payment_date"
                            required
                            value="<?= $payment
                                ? date(
                                    'Y-m-d\TH:i',
                                    strtotime(
                                        $payment['payment_date']
                                    )
                                )
                                : date('Y-m-d\TH:i') ?>"
                        >

                    </div>



                    <!-- NOTES -->

                    <div class="form-group full">

                        <label>
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            placeholder="Optional notes about this payment..."
                        ><?= htmlspecialchars(
                            $payment['notes'] ?? ''
                        ) ?></textarea>

                    </div>


                </div>



                <!-- ACTIONS -->

                <div class="form-actions">


                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <?= $payment
                            ? '✓ Update Payment'
                            : '✓ Save Payment'
                        ?>

                    </button>


                </div>


            </form>


        </div>



        <!-- FOOTER -->

        <div class="page-footer">

            <span>
                © <?= date("Y") ?> ElectroCore
            </span>

            <span>
                Billing & Inventory Management System
            </span>

        </div>


    </main>


</div>



<script>

/*
|--------------------------------------------------------------------------
| PAYMENT TYPE / PARTY FIELD CONTROL
|--------------------------------------------------------------------------
*/

const paymentType =
    document.getElementById(
        'payment_type'
    );


const customerGroup =
    document.getElementById(
        'customer_group'
    );


const supplierGroup =
    document.getElementById(
        'supplier_group'
    );


function updatePartyFields() {

    const type =
        paymentType.value;


    if (
        type === 'purchase_payment' ||
        type === 'supplier_payment'
    ) {

        supplierGroup.style.display =
            'flex';

        customerGroup.style.display =
            'none';

    } else {

        customerGroup.style.display =
            'flex';

        supplierGroup.style.display =
            'none';

    }

}


paymentType.addEventListener(
    'change',
    updatePartyFields
);


updatePartyFields();

</script>


</body>

</html>