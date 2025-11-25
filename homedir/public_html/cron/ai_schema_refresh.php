<?php
/**
 * AI Schema Refresh - Günlük otomatik şema güncelleme
 * Cron: 0 2 * * * /usr/bin/php /var/www/html/cron/ai_schema_refresh.php
 */
require_once '/var/www/html/include/db.php';
require_once '/var/www/html/include/DatabaseLearner.php';

$log_file = '/var/www/html/logs/ai_schema_refresh.log';

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

writeLog("=== Schema Refresh Started ===");

try {
    // Her firma için şemayı güncelle
    $firma_sql = "SELECT DISTINCT firma_id FROM firmalar WHERE aktif = 1";
    $firma_sth = $conn->query($firma_sql);
    
    while ($firma = $firma_sth->fetch(PDO::FETCH_ASSOC)) {
        $firma_id = $firma['firma_id'];
        
        writeLog("Refreshing schema for firma_id: $firma_id");
        
        $learner = new DatabaseLearner($conn, $firma_id);
        $result = $learner->learnDatabaseSchema(true);
        
        if ($result["success"]) {
            writeLog("  ✓ Success: {$result['tables_learned']} tables learned");
        } else {
            writeLog("  ✗ Error: {$result['error']}");
        }
    }
    
    writeLog("=== Schema Refresh Completed ===");
    
} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage());
}
