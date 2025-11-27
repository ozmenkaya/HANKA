<?php
require_once "../homedir/public_html/include/db.php";

try {
    $stmt = $conn->query("DESCRIBE siparisler");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in siparisler table:\n";
    foreach ($columns as $col) {
        echo "- " . $col . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
