<?php
/**
 * Database connection
 */

$db_host = "localhost";
$db_user = "hanka_user";
$db_pass = "HankaDB2025!";
$db_name = "panelhankasys_crm2";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
