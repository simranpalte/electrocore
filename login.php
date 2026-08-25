<?php

session_start();

require_once __DIR__ . "/config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    header("Location: index.php?error=empty");
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, full_name, username, password, role, status
    FROM users
    WHERE username = ?
    LIMIT 1
");

$stmt->execute([$username]);

$user = $stmt->fetch();

if (!$user || !password_verify($password, $user["password"])) {
    header("Location: index.php?error=invalid");
    exit;
}

if ($user["status"] !== "active") {
    header("Location: index.php?error=inactive");
    exit;
}

session_regenerate_id(true);

$_SESSION["user_id"] = $user["id"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["username"] = $user["username"];
$_SESSION["role"] = $user["role"];

$update = $pdo->prepare("
    UPDATE users
    SET last_login = NOW()
    WHERE id = ?
");

$update->execute([$user["id"]]);

header("Location: dashboard.php");
exit;