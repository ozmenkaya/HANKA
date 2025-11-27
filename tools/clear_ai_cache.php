<?php
require_once __DIR__ . "/../homedir/public_html/include/db.php";
require_once __DIR__ . "/../homedir/public_html/include/AICache.php";

// Firma ID 16 (GÜLERMAT)
$firma_id = 16;

echo "Clearing AI Cache for Firma ID: $firma_id\n";

try {
    $aiCache = new AICache($conn);
    $affected = $aiCache->invalidate(['siparis', 'sipariş', 'order', 'GLR', 'guler', 'güler'], $firma_id);
    echo "Invalidated $affected cache entries.\n";
    
    // Also clear specific hash if possible, but keyword invalidation should cover it.
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
