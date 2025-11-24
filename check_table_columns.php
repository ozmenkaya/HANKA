<?php
require_once 'homedir/public_html/include/db.php';

try {
    $stmt = $conn->query("DESCRIBE uretilen_adetler");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
