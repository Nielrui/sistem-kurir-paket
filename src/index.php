<?php
echo "<h1>Sistem Kurir Paket</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Status: <span style='color:green'>Running OK</span></p>";

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'kurir_db';
$user = getenv('DB_USER') ?: 'kurir_user';
$pass = getenv('DB_PASS') ?: 'kurir_pass123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "<p>Database: <span style='color:green'>Connected!</span></p>";
} catch (PDOException $e) {
    echo "<p>Database: <span style='color:orange'>Connecting... (tunggu MySQL ready)</span></p>";
}
?>