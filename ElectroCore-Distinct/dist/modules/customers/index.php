<?php

require_once '../../config/database.php';

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "User";
$role = $_SESSION["role"] ?? "";


/*
|--------------------------------------------------------------------------
| ADD CUSTOMER
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    header("Content-Type: application/json");

    $fullName = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $alternatePhone = trim($_POST["alternate_phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $state = trim($_POST["state"] ?? "");
    $pincode = trim($_POST["pincode"] ?? "");
    $gstin = trim($_POST["gstin"] ?? "");

    $openingBalance = (float) ($_POST["opening_balance"] ?? 0);
    $creditLimit = (float) ($_POST["credit_limit"] ?? 0);


    if ($fullName === "") {

        echo json_encode([
            "success" => false,
            "message" => "Full name is required."
        ]);

        exit;
    }


    if ($openingBalance < 0) {
        $openingBalance = 0;
    }


    if ($creditLimit < 0) {
        $creditLimit = 0;
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | Generate Customer Code
        |--------------------------------------------------------------------------
        */

        $lastStmt = $pdo->query("
            SELECT customer_code
            FROM customers
            ORDER BY id DESC
            LIMIT 1
        ");

        $lastCode = $lastStmt->fetchColumn();

        $nextNumber = 1;


        if ($lastCode) {

            if (preg_match('/CUS-(\d+)/', $lastCode, $matches)) {

                $nextNumber =
                    ((int) $matches[1]) + 1;
            }
        }


        $customerCode =
            "CUS-" .
            str_pad(
                $nextNumber,
                3,
                "0",
                STR_PAD_LEFT
            );


        /*
        |--------------------------------------------------------------------------
        | Insert Customer
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO customers (
                customer_code,
                full_name,
                phone,
                alternate_phone,
                email,
                address,
                city,
                state,
                pincode,
                gstin,
                opening_balance,
                credit_limit,
                status
            )
            VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active'
            )
        ");


        $stmt->execute([

            $customerCode,

            $fullName,

            $phone !== ""
                ? $phone
                : null,

            $alternatePhone !== ""
                ? $alternatePhone
                : null,

            $email !== ""
                ? $email
                : null,

            $address !== ""
                ? $address
                : null,

            $city !== ""
                ? $city
                : null,

            $state !== ""
                ? $state
                : null,

            $pincode !== ""
                ? $pincode
                : null,

            $gstin !== ""
                ? $gstin
                : null,

            $openingBalance,

            $creditLimit
        ]);


        echo json_encode([

            "success" => true,

            "message" =>
                "Customer added successfully."

        ]);

        exit;


    } catch (Throwable $e) {

        echo json_encode([

            "success" => false,

            "message" =>
                "Could not add customer: "
                . $e->getMessage()

        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| FETCH CUSTOMERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        customer_code,
        full_name,
        phone,
        email,
        city,
        gstin,
        opening_balance,
        credit_limit,
        status
    FROM customers
    ORDER BY id DESC
");

$customers =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>ElectroCore | Customers</title>


<!-- ELECTROCORE DASHBOARD CSS -->

<link
    rel="stylesheet"
    href="../../assets/css/dashboard.css"
>


<style>

/* ============================================================
   ELECTROCORE CUSTOMERS PAGE
============================================================ */

.customers-page {

    width: 100%;

    max-width: 100%;

}


/* ============================================================
   PAGE HEADER
============================================================ */

.customers-page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}


.customers-page-title h1 {

    margin: 0;

    font-size: 28px;

    font-weight: 750;

    color: #0f172a;

}


.customers-page-title p {

    margin-top: 6px;

    color: #64748b;

    font-size: 14px;

}


/* ============================================================
   ADD CUSTOMER BUTTON
============================================================ */

.customer-add-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    border: none;

    padding: 12px 18px;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color: #ffffff;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 5px 14px
        rgba(37, 99, 235, 0.22);

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.customer-add-btn:hover {

    transform: translateY(-1px);

    box-shadow:
        0 7px 18px
        rgba(37, 99, 235, 0.28);

}


/* ============================================================
   MESSAGE
============================================================ */

.customer-message {

    display: none;

    padding: 13px 16px;

    margin-bottom: 18px;

    border-radius: 9px;

    font-size: 14px;

    font-weight: 650;

}


.customer-message.success {

    display: block;

    background: #f0fdf4;

    border: 1px solid #bbf7d0;

    color: #15803d;

}


.customer-message.error {

    display: block;

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #be123c;

}


/* ============================================================
   MAIN CUSTOMER CARD
============================================================ */

.customers-card {

    background: #ffffff;

    border: 1px solid #e2e8f0;

    border-radius: 14px;

    overflow: hidden;

    box-shadow:
        0 5px 18px
        rgba(15, 23, 42, 0.055);

}


/* ============================================================
   CARD TOP
============================================================ */

.customers-card-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    padding: 20px 22px;

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #172554
        );

}


.customers-card-heading {

    display: flex;

    align-items: center;

    gap: 13px;

    color: #ffffff;

}


.customers-card-icon {

    width: 40px;

    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: #2563eb;

    color: #ffffff;

    font-size: 18px;

    font-weight: 800;

}


.customers-card-heading h2 {

    margin: 0;

    color: #ffffff;

    font-size: 17px;

    font-weight: 750;

}


.customers-card-heading p {

    margin-top: 4px;

    color: #cbd5e1;

    font-size: 12px;

}


/* ============================================================
   SEARCH
============================================================ */

.customer-search {

    width: 390px;

    max-width: 100%;

    position: relative;

}


.customer-search input {

    width: 100%;

    padding: 11px 14px 11px 40px;

    border: 1px solid rgba(255,255,255,.18);

    border-radius: 8px;

    background:
        rgba(255,255,255,.10);

    color: #ffffff;

    font-size: 14px;

    font-family: inherit;

    outline: none;

}


.customer-search input::placeholder {

    color: #cbd5e1;

}


.customer-search input:focus {

    border-color: #60a5fa;

    background:
        rgba(255,255,255,.14);

}


.customer-search-icon {

    position: absolute;

    left: 14px;

    top: 50%;

    transform: translateY(-50%);

    color: #cbd5e1;

    font-size: 15px;

}


/* ============================================================
   TABLE
============================================================ */

.customer-table-wrapper {

    width: 100%;

    overflow-x: auto;

}


.customer-table {

    width: 100%;

    min-width: 1050px;

    border-collapse: collapse;

}


.customer-table th {

    padding: 14px 15px;

    background: #f8fafc;

    border-bottom:
        1px solid #e2e8f0;

    color: #475569;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .45px;

    text-align: left;

}


.customer-table td {

    padding: 15px;

    border-bottom:
        1px solid #eef2f7;

    color: #334155;

    font-size: 14px;

    vertical-align: middle;

}


.customer-table tbody tr {

    transition:
        background .15s ease;

}


.customer-table tbody tr:hover {

    background: #f8fbff;

}


.customer-table tbody tr:last-child td {

    border-bottom: none;

}


/* ============================================================
   CUSTOMER CODE
============================================================ */

.customer-code {

    display: inline-flex;

    padding: 5px 9px;

    border-radius: 6px;

    background: #eff6ff;

    color: #1d4ed8;

    font-size: 12px;

    font-weight: 750;

}


/* ============================================================
   CUSTOMER NAME
============================================================ */

.customer-name {

    color: #0f172a;

    font-weight: 700;

}


.customer-phone {

    color: #475569;

}


/* ============================================================
   MONEY
============================================================ */

.customer-money {

    color: #0f172a;

    font-weight: 650;

}


/* ============================================================
   STATUS
============================================================ */

.customer-status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 750;

}


.customer-status::before {

    content: "";

    width: 6px;

    height: 6px;

    border-radius: 50%;

}


.customer-status.active {

    background: #ecfdf5;

    color: #047857;

}


.customer-status.active::before {

    background: #10b981;

}


.customer-status.inactive {

    background: #fff1f2;

    color: #be123c;

}


.customer-status.inactive::before {

    background: #f43f5e;

}


/* ============================================================
   NO DATA
============================================================ */

.customer-no-data {

    padding: 50px 20px !important;

    text-align: center !important;

    color: #94a3b8 !important;

    font-size: 14px !important;

}


/* ============================================================
   MODAL OVERLAY
============================================================ */

.customer-modal {

    display: none;

    position: fixed;

    inset: 0;

    z-index: 9999;

    padding: 35px 20px;

    background:
        rgba(2, 6, 23, 0.68);

    overflow-y: auto;

    backdrop-filter: blur(4px);

}


.customer-modal.show {

    display: block;

}


/* ============================================================
   MODAL
============================================================ */

.customer-modal-content {

    width: 100%;

    max-width: 780px;

    margin: 0 auto;

    background: #ffffff;

    border: 1px solid #dbe3ef;

    border-radius: 15px;

    overflow: hidden;

    box-shadow:
        0 25px 70px
        rgba(0,0,0,.28);

}


/* ============================================================
   MODAL HEADER
============================================================ */

.customer-modal-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 19px 23px;

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #172554
        );

    color: #ffffff;

}


.customer-modal-title {

    display: flex;

    align-items: center;

    gap: 12px;

}


.customer-modal-icon {

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    background: #2563eb;

    color: white;

    font-weight: 800;

}


.customer-modal-title h2 {

    margin: 0;

    font-size: 17px;

    color: white;

}


.customer-modal-title p {

    margin-top: 3px;

    color: #cbd5e1;

    font-size: 12px;

}


.customer-close {

    width: 34px;

    height: 34px;

    border: none;

    border-radius: 8px;

    background:
        rgba(255,255,255,.08);

    color: #cbd5e1;

    font-size: 23px;

    cursor: pointer;

}


.customer-close:hover {

    background:
        rgba(255,255,255,.16);

    color: white;

}


/* ============================================================
   MODAL BODY
============================================================ */

.customer-modal-body {

    padding: 25px;

}


/* ============================================================
   FORM
============================================================ */

.customer-form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 19px;

}


.customer-form-group {

    display: flex;

    flex-direction: column;

}


.customer-form-group.full {

    grid-column: 1 / -1;

}


.customer-form-group label {

    margin-bottom: 7px;

    color: #475569;

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .35px;

}


.customer-form-group input,
.customer-form-group textarea {

    width: 100%;

    padding: 12px 13px;

    border: 1px solid #dbe1ea;

    border-radius: 8px;

    background: #f8fafc;

    color: #0f172a;

    font-family: inherit;

    font-size: 14px;

    transition: .2s ease;

}


.customer-form-group textarea {

    min-height: 90px;

    resize: vertical;

}


.customer-form-group input:hover,
.customer-form-group textarea:hover {

    border-color: #94a3b8;

}


.customer-form-group input:focus,
.customer-form-group textarea:focus {

    outline: none;

    background: #ffffff;

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.10);

}


/* ============================================================
   MODAL FOOTER
============================================================ */

.customer-modal-actions {

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    padding: 17px 23px;

    border-top:
        1px solid #e5e7eb;

    background: #f8fafc;

}


.customer-cancel-btn {

    border: 1px solid #cbd5e1;

    padding: 11px 18px;

    border-radius: 8px;

    background: #ffffff;

    color: #475569;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

}


.customer-cancel-btn:hover {

    background: #f1f5f9;

}


.customer-save-btn {

    border: none;

    padding: 11px 20px;

    border-radius: 8px;

    background: #2563eb;

    color: #ffffff;

    font-size: 13px;

    font-weight: 750;

    cursor: pointer;

    box-shadow:
        0 4px 10px
        rgba(37,99,235,.18);

}


.customer-save-btn:hover {

    background: #1d4ed8;

}


.customer-save-btn:disabled {

    opacity: .6;

    cursor: not-allowed;

}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 900px) {

    .customers-card-top {

        align-items: flex-start;

        flex-direction: column;

    }


    .customer-search {

        width: 100%;

    }

}


@media (max-width: 700px) {

    .customers-page-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 15px;

    }


    .customer-add-btn {

        width: 100%;

        justify-content: center;

    }


    .customers-card-top {

        padding: 17px;

    }


    .customer-form-grid {

        grid-template-columns: 1fr;

    }


    .customer-form-group.full {

        grid-column: auto;

    }


    .customer-modal {

        padding: 15px;

    }


    .customer-modal-body {

        padding: 18px;

    }

}

</style>

</head>


<body>


<div class="dashboard">


    <!-- =====================================================
         ELECTROCORE SIDEBAR
    ===================================================== -->

    <?php require_once __DIR__ . "/../../includes/sidebar.php"; ?>


    <!-- =====================================================
         MAIN CONTENT
    ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOP BAR
        ================================================= -->

        <div class="topbar">

            <div class="page-title">

                <h1>Customers</h1>

                <p>
                    Manage your ElectroCore customers
                </p>

            </div>


            <div class="user-info">

                <strong>
                    <?= htmlspecialchars($full_name) ?>
                </strong>

                <span>

                    <?= htmlspecialchars(
                        ucwords(
                            str_replace(
                                "_",
                                " ",
                                $role
                            )
                        )
                    ) ?>

                </span>

            </div>

        </div>


        <!-- =================================================
             CUSTOMERS PAGE
        ================================================= -->

        <div class="customers-page">


            <!-- PAGE HEADER -->

            <div class="customers-page-header">

                <div class="customers-page-title">

                    <h1>
                        Customer Management
                    </h1>

                    <p>
                        Add, search and manage your customer records.
                    </p>

                </div>


                <button
                    type="button"
                    class="customer-add-btn"
                    onclick="openModal()"
                >

                    <span>+</span>

                    Add Customer

                </button>

            </div>


            <!-- MESSAGE -->

            <div
                id="message"
                class="customer-message"
            ></div>


            <!-- CUSTOMER CARD -->

            <div class="customers-card">


                <!-- CARD HEADER -->

                <div class="customers-card-top">


                    <div class="customers-card-heading">

                        <div class="customers-card-icon">
                            C
                        </div>

                        <div>

                            <h2>
                                Customers
                            </h2>

                            <p>
                                Customer directory and account information
                            </p>

                        </div>

                    </div>


                    <!-- SEARCH -->

                    <div class="customer-search">

                        <span class="customer-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search customers..."
                            oninput="searchCustomers()"
                        >

                    </div>


                </div>


                <!-- TABLE -->

                <div class="customer-table-wrapper">

                    <table class="customer-table">

                        <thead>

                            <tr>

                                <th>
                                    Code
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    City
                                </th>

                                <th>
                                    GSTIN
                                </th>

                                <th>
                                    Opening Balance
                                </th>

                                <th>
                                    Credit Limit
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody id="customerTable">


                        <?php if (empty($customers)): ?>


                            <tr>

                                <td
                                    colspan="9"
                                    class="customer-no-data"
                                >

                                    No customers found.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($customers as $customer): ?>


                                <tr
                                    class="customer-row"
                                    data-search="<?= htmlspecialchars(
                                        strtolower(
                                            $customer['customer_code']
                                            . ' '
                                            . $customer['full_name']
                                            . ' '
                                            . ($customer['phone'] ?? '')
                                            . ' '
                                            . ($customer['email'] ?? '')
                                            . ' '
                                            . ($customer['city'] ?? '')
                                            . ' '
                                            . ($customer['gstin'] ?? '')
                                        )
                                    ) ?>"
                                >


                                    <td>

                                        <span class="customer-code">

                                            <?= htmlspecialchars(
                                                $customer['customer_code']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="customer-name">

                                            <?= htmlspecialchars(
                                                $customer['full_name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="customer-phone">

                                            <?= htmlspecialchars(
                                                $customer['phone'] ?? '-'
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $customer['email'] ?? '-'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $customer['city'] ?? '-'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $customer['gstin'] ?? '-'
                                        ) ?>

                                    </td>


                                    <td>

                                        <span class="customer-money">

                                            ₹<?= number_format(
                                                (float)
                                                $customer['opening_balance'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="customer-money">

                                            ₹<?= number_format(
                                                (float)
                                                $customer['credit_limit'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                customer-status
                                                <?= $customer['status'] === 'active'
                                                    ? 'active'
                                                    : 'inactive'
                                                ?>
                                            "
                                        >

                                            <?= ucfirst(
                                                $customer['status']
                                            ) ?>

                                        </span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</div>



<!-- ============================================================
     ADD CUSTOMER MODAL
============================================================ -->

<div
    id="customerModal"
    class="customer-modal"
>


    <div class="customer-modal-content">


        <!-- MODAL HEADER -->

        <div class="customer-modal-header">


            <div class="customer-modal-title">

                <div class="customer-modal-icon">
                    +
                </div>

                <div>

                    <h2>
                        Add New Customer
                    </h2>

                    <p>
                        Enter customer information below
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="customer-close"
                onclick="closeModal()"
            >

                &times;

            </button>


        </div>


        <!-- FORM -->

        <form
            id="customerForm"
            onsubmit="saveCustomer(event)"
        >


            <div class="customer-modal-body">


                <div class="customer-form-grid">


                    <!-- FULL NAME -->

                    <div class="customer-form-group full">

                        <label>
                            Full Name *
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Enter customer full name"
                            required
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="customer-form-group">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            placeholder="Enter phone number"
                        >

                    </div>


                    <!-- ALTERNATE PHONE -->

                    <div class="customer-form-group">

                        <label>
                            Alternate Phone
                        </label>

                        <input
                            type="text"
                            name="alternate_phone"
                            placeholder="Optional alternate number"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="customer-form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="customer@example.com"
                        >

                    </div>


                    <!-- PINCODE -->

                    <div class="customer-form-group">

                        <label>
                            Pincode
                        </label>

                        <input
                            type="text"
                            name="pincode"
                            placeholder="Enter pincode"
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="customer-form-group full">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="address"
                            placeholder="Enter customer address"
                        ></textarea>

                    </div>


                    <!-- CITY -->

                    <div class="customer-form-group">

                        <label>
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            placeholder="Enter city"
                        >

                    </div>


                    <!-- STATE -->

                    <div class="customer-form-group">

                        <label>
                            State
                        </label>

                        <input
                            type="text"
                            name="state"
                            placeholder="Enter state"
                        >

                    </div>


                    <!-- GSTIN -->

                    <div class="customer-form-group">

                        <label>
                            GSTIN
                        </label>

                        <input
                            type="text"
                            name="gstin"
                            maxlength="15"
                            placeholder="Enter GSTIN"
                        >

                    </div>


                    <!-- OPENING BALANCE -->

                    <div class="customer-form-group">

                        <label>
                            Opening Balance
                        </label>

                        <input
                            type="number"
                            name="opening_balance"
                            value="0"
                            min="0"
                            step="0.01"
                        >

                    </div>


                    <!-- CREDIT LIMIT -->

                    <div class="customer-form-group">

                        <label>
                            Credit Limit
                        </label>

                        <input
                            type="number"
                            name="credit_limit"
                            value="0"
                            min="0"
                            step="0.01"
                        >

                    </div>


                </div>

            </div>


            <!-- MODAL ACTIONS -->

            <div class="customer-modal-actions">


                <button
                    type="button"
                    class="customer-cancel-btn"
                    onclick="closeModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    class="customer-save-btn"
                    id="saveCustomerButton"
                >

                    Save Customer

                </button>


            </div>


        </form>

    </div>

</div>



<script>

/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/

function openModal() {

    const modal =
        document.getElementById(
            "customerModal"
        );

    modal.classList.add("show");

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/

function closeModal() {

    const modal =
        document.getElementById(
            "customerModal"
        );

    modal.classList.remove("show");

}


/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE MODAL
|--------------------------------------------------------------------------
*/

document
    .getElementById("customerModal")
    .addEventListener(
        "click",
        function(event) {

            if (event.target === this) {

                closeModal();

            }

        }
    );


/*
|--------------------------------------------------------------------------
| SEARCH CUSTOMERS
|--------------------------------------------------------------------------
*/

function searchCustomers() {

    const searchInput =
        document.getElementById(
            "searchInput"
        );


    const search =
        searchInput.value
            .toLowerCase()
            .trim();


    const rows =
        document.querySelectorAll(
            ".customer-row"
        );


    rows.forEach(
        function(row) {

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

        }
    );

}


/*
|--------------------------------------------------------------------------
| SAVE CUSTOMER
|--------------------------------------------------------------------------
*/

function saveCustomer(event) {

    event.preventDefault();


    const form =
        document.getElementById(
            "customerForm"
        );


    const button =
        document.getElementById(
            "saveCustomerButton"
        );


    const formData =
        new FormData(form);


    button.disabled = true;

    button.textContent =
        "Saving...";


    fetch(
        "index.php",
        {
            method: "POST",
            body: formData
        }
    )


    .then(
        function(response) {

            return response.json();

        }
    )


    .then(
        function(result) {

            if (result.success) {

                closeModal();

                form.reset();


                showMessage(
                    result.message,
                    "success"
                );


                setTimeout(
                    function() {

                        window.location.reload();

                    },
                    700
                );


            } else {

                showMessage(
                    result.message ||
                    "Unable to add customer.",
                    "error"
                );

            }

        }
    )


    .catch(
        function(error) {

            console.error(error);


            showMessage(
                "Unable to connect to the server.",
                "error"
            );

        }
    )


    .finally(
        function() {

            button.disabled = false;

            button.textContent =
                "Save Customer";

        }
    );

}


/*
|--------------------------------------------------------------------------
| SHOW MESSAGE
|--------------------------------------------------------------------------
*/

function showMessage(
    message,
    type
) {

    const element =
        document.getElementById(
            "message"
        );


    element.textContent =
        message;


    element.className =
        "customer-message " +
        type;

}


/*
|--------------------------------------------------------------------------
| ESC KEY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key === "Escape"
        ) {

            closeModal();

        }

    }
);

</script>


</body>

</html>