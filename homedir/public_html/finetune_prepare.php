<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name("PNL");
session_start();

if (!isset($_SESSION["giris_kontrol"])) {
    die("Oturum geçersiz");
}

// DB bağlantısı
try {
    $db = new PDO(
        'mysql:host=localhost;port=3306;dbname=panelhankasys_crm2;charset=utf8mb4',
        'hanka_user',
        'HankaDB2025!',
        [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
} catch (PDOException $e) {
    die("DB Hatası: " . $e->getMessage());
}

$firma_id = $_SESSION["firma_id"];

// AI chat history'den başarılı sorguları çek
$stmt = $db->prepare("
    SELECT 
        ch.soru,
        ch.sql_query as sql_sorgusu,
        MAX(ch.tarih) as son_kullanim
    FROM ai_chat_history ch
    WHERE ch.firma_id = ?
    AND ch.sql_query IS NOT NULL
    AND ch.sql_query != ''
    AND ch.sonuc_sayisi > 0
    GROUP BY ch.soru, ch.sql_query
    ORDER BY son_kullanim DESC
    LIMIT 500
");

$stmt->execute([$firma_id]);
$rows = $stmt->fetchAll();

if (count($rows) === 0) {
    echo "⚠️ Henüz yeterli AI log verisi yok.\n";
    echo "📊 Önce birkaç başarılı AI sorgusu yapın.\n";
    echo "Örnek: 'Bu ay kaç sipariş aldık?', 'En çok satan ürün?' vb.\n";
    exit;
}

$training_data = [];

foreach ($rows as $row) {
    // OpenAI fine-tuning formatı: {"messages": [...]}
    $training_data[] = [
        "messages" => [
            [
                "role" => "system",
                "content" => "Sen bir SQL uzmanısın. Türkçe soruları MySQL sorgularına çeviriyorsun. Veritabanı: siparisler (siparis_no, musteri_id, personel_id, tarih, fiyat), musteri (id, firma_unvani), personeller (id, ad, soyad)."
            ],
            [
                "role" => "user",
                "content" => $row['soru']
            ],
            [
                "role" => "assistant",
                "content" => $row['sql_sorgusu']
            ]
        ]
    ];
}

// Minimum 10 örnek gerekli
if (count($training_data) < 10) {
    echo "⚠️ En az 10 örnek gerekli. Şu an: " . count($training_data) . "\n";
    echo "Daha fazla AI sorgusu yapın.\n";
    exit;
}

// JSONL formatında kaydet (her satır bir JSON)
$output_file = __DIR__ . "/finetune_data.jsonl";
$fp = fopen($output_file, 'w');

foreach ($training_data as $item) {
    fwrite($fp, json_encode($item, JSON_UNESCAPED_UNICODE) . "\n");
}

fclose($fp);

echo "✅ Fine-tuning verisi hazırlandı!\n";
echo "📊 Toplam örnek: " . count($training_data) . "\n";
echo "📁 Dosya: finetune_data.jsonl\n";
echo "\n";
echo "Sonraki adım:\n";
echo "Console'da şu komutu çalıştırın:\n";
echo "fetch('finetune_api.php?action=upload').then(r => r.json()).then(d => console.log(d))\n";
?>
