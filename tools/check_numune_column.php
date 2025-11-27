<?php
require_once 'include/db.php';

try {
    $stmt = $conn->query("SHOW COLUMNS FROM siparisler LIKE 'numune'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($column);
    
    // Also check what kind of data is in there for the siparis we are trying to copy
    // We don't know the ID, but let's just check a few recent ones
    $stmt = $conn->query("SELECT id, numune FROM siparisler ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRecent Data:\n";
    print_r($rows);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
