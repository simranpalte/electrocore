<?php

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 23539;
$dbname = getenv('DB_NAME') ?: 'defaultdb';
$username = getenv('DB_USER') ?: 'avnadmin';
$password = getenv('DB_PASSWORD');

$caCertificate = __DIR__ . "/ca.pem";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::MYSQL_ATTR_SSL_CA => $caCertificate,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
        ]
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>