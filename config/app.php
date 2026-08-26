<?php
$host = $_SERVER['HTTP_HOST'] ?? '';

if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) {
    define('BASE_URL', '/electrocore');
} else {
    define('BASE_URL', '');
}
?>
