<?php
/**
 * Veritabanı Bakım Scripti
 * CLI: php db_maintenance.php
 * Web: sadece admin (yetki_id = 1)
 */

if (php_sapi_name() !== "cli") {
    session_name("PNL");
    session_start();
    if (!isset($_SESSION["giris_kontrol"]) || $_SESSION["yetki_id"] != 1) {
        die("Erişim yetkiniz yok!");
    }
    require_once "db.php";
} else {
    $_SERVER["REQUEST_URI"] = "/maintenance.php";
    require_once __DIR__ . "/db.php";
}

$tables = [
    "musteri", "planlama", "stok_kalemleri", "stok_alt_depolar",
    "stok_alt_kalemler", "tedarikciler", "personeller", "ai_chat_history"
];

echo "🔧 VERITABANI BAKIMI\n";
echo str_repeat("=", 60) . "\n\n";

$total_size = 0;
$total_rows = 0;

foreach ($tables as $table) {
    echo "📋 $table\n";
    
    // ANALYZE TABLE
    try {
        $stmt = $conn->query("ANALYZE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ✅ Analiz: " . $result["Msg_text"] . "\n";
    } catch (Exception $e) {
        echo "  ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    // Boyut bilgisi
    $stmt = $conn->prepare("
        SELECT 
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
            table_rows
        FROM information_schema.TABLES 
        WHERE table_schema = 'panelhankasys_crm2' AND table_name = :table
    ");
    $stmt->execute(["table" => $table]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($info) {
        $total_size += $info["size_mb"];
        $total_rows += $info["table_rows"];
        echo "  📊 Boyut: {$info['size_mb']} MB, Kayıt: " . number_format($info["table_rows"]) . "\n";
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "💾 Toplam: {$total_size} MB, " . number_format($total_rows) . " kayıt\n";
echo "✅ Bakım tamamlandı: " . date("Y-m-d H:i:s") . "\n";
echo "📅 Sonraki bakım: " . date("Y-m-d", strtotime("+1 month")) . "\n";
?>
