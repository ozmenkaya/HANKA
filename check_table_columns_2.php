<?php
require_once 'homedir/public_html/include/db.php';

try {
    $stmt = $conn->query("DESCRIBE uretim_eksik_uretilen_loglar");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
