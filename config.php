<?php
// filepath: c:\xampp\htdocs\php_student\config.php

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/.env');

// Database configuration from environment variables
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'dashboard_qrcode';
$user = $_ENV['DB_USER'] ?? 'postgres';
$pass = $_ENV['DB_PASS'] ?? '123456789';
$port = $_ENV['DB_PORT'] ?? '5432';
$endpoint = $_ENV['DB_ENDPOINT'] ?? '';

// Build DSN with Neon endpoint parameter
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
if (!empty($endpoint)) {
    $dsn .= ";options='endpoint=$endpoint'";
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
   echo "<h3>Database Connection Failed</h3>";
    echo "<strong>DSN:</strong> " . $dsn . "<br>";
    echo "<strong>Host:</strong> " . $host . "<br>";
    echo "<strong>Database:</strong> " . $db . "<br>";
    echo "<strong>User:</strong> " . $user . "<br>";
    echo "<strong>Port:</strong> " . $port . "<br>";
    echo "<strong>Endpoint:</strong> " . $endpoint . "<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Available PDO drivers:</strong> " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
    echo "<strong>PHP Extensions:</strong> " . implode(', ', get_loaded_extensions()) . "<br>";
    die();
}


// includes/functions.php
// (functions like sanitize_input, is_logged_in, redirect_if_not_logged_in should only be defined in includes/functions.php)


// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Professor</title>
    <link rel="stylesheet" href="../assets/style.css"> 
</head>

<body>
    <header> 
    </header>
