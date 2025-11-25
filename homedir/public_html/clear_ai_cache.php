<?php
require_once 'include/db.php';

// Test firması ID'si (Loglardan 16 olduğunu gördük)
$firma_id = 16; 

try {
    // Cache'i temizle (Soft delete: is_valid = 0)
    $stmt = $conn->prepare("UPDATE ai_cache SET is_valid = 0 WHERE firma_id = :firma_id");
    $stmt->execute(['firma_id' => $firma_id]);
    
    echo "✅ AI Cache temizlendi!\n";
    echo "----------------------\n";
    echo "Firma ID: $firma_id\n";
    echo "Temizlenen Kayıt: " . $stmt->rowCount() . "\n";
    
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
