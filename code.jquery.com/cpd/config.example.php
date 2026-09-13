<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = "localhost";
$DB_USER = "YOUR_DB_USER";
$DB_PASS = "YOUR_DB_PASSWORD";
$DB_NAME = "YOUR_DB_NAME";

try {
  $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  $conn->set_charset("utf8mb4");
} catch (Exception $e) {
  error_log("DB Connection Error: " . $e->getMessage());
  $conn = null;
  if (php_sapi_name() !== 'cli-server') {
    die("Database connection failed.");
  }
}

if (session_status() === PHP_SESSION_NONE) session_start();
