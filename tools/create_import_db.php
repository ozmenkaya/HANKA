<?php
require_once 'homedir/public_html/include/db.php';

try {
    $conn->exec("CREATE DATABASE IF NOT EXISTS panelhankasys_crm2_import CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database created successfully.";
} catch (PDOException $e) {
    echo "Error creating database: " . $e->getMessage();
}
?>
