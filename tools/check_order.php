<?php
require_once 'include/db.php';

$siparis_no = 'GLR001365';

try {
    $stmt = $conn->prepare("SELECT * FROM siparisler WHERE siparis_no LIKE :siparis_no");
    $stmt->execute([':siparis_no' => "%$siparis_no%"]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        echo "Order Found:\n";
        print_r($result);
    } else {
        echo "Order NOT Found for '$siparis_no'.\n";
        
        // Let's check similar orders
        $stmt = $conn->query("SELECT siparis_no FROM siparisler ORDER BY id DESC LIMIT 20");
        echo "\nRecent Orders:\n";
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['siparis_no'] . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
