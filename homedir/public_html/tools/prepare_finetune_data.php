<?php
/**
 * HANKA AI - Fine-Tuning Veri Hazırlayıcı
 * Veritabanındaki başarılı sohbet geçmişini OpenAI JSONL formatına çevirir.
 * 
 * Kullanım: php prepare_finetune_data.php
 */

require_once __DIR__ . "/../include/db.php"; // DB bağlantısı

// Ayarlar
$MIN_SQL_LENGTH = 10; // Çok kısa SQL'leri alma
$OUTPUT_FILE = __DIR__ . "/../logs/hanka_finetune_dataset.jsonl";
$LIMIT = 500; // En son kaç kayıt alınsın?

echo "🚀 HANKA AI Veri Hazırlayıcı Başlatılıyor...\n";

try {
    // Başarılı ve SQL içeren kayıtları çek
    $sql = "SELECT * FROM ai_chat_history 
            WHERE sql_query IS NOT NULL 
            AND sql_query != '' 
            AND LENGTH(sql_query) > :min_len
            AND sonuc_sayisi > 0 
            ORDER BY tarih DESC LIMIT :limit";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':min_len', $MIN_SQL_LENGTH, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $LIMIT, PDO::PARAM_INT);
    $stmt->execute();
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($records);
    
    echo "📊 Toplam $count adet uygun kayıt bulundu.\n";
    
    if ($count < 10) {
        die("❌ Yetersiz veri! En az 10 kayıt gerekli. (Bulunan: $count)\n");
    }
    
    // Dosyayı temizle/oluştur
    if (!is_dir(dirname($OUTPUT_FILE))) mkdir(dirname($OUTPUT_FILE), 0777, true);
    $fp = fopen($OUTPUT_FILE, 'w');
    
    $exported = 0;
    
    foreach ($records as $row) {
        // System Prompt (Modelin kimliği)
        $system_message = "Sen HANKA ERP sistemi için uzman bir SQL asistanısın. Kullanıcı sorularını MySQL sorgularına çevirirsin. Firma ID: {$row['firma_id']}.";
        
        // User Message (Soru)
        $user_message = "Soru: " . $row['soru'];
        
        // Assistant Message (Cevap - JSON formatında SQL)
        // Not: AIChatEngine normalde JSON döner ama history'de raw SQL olabilir.
        // Biz modelin JSON dönmesini istiyoruz.
        $assistant_response = json_encode([
            "sql" => $row['sql_query'],
            "explanation" => "Otomatik oluşturulan sorgu."
        ], JSON_UNESCAPED_UNICODE);
        
        // OpenAI Chat Formatı
        $training_example = [
            "messages" => [
                ["role" => "system", "content" => $system_message],
                ["role" => "user", "content" => $user_message],
                ["role" => "assistant", "content" => $assistant_response]
            ]
        ];
        
        // JSONL satırı olarak yaz
        fwrite($fp, json_encode($training_example, JSON_UNESCAPED_UNICODE) . "\n");
        $exported++;
    }
    
    fclose($fp);
    
    echo "✅ İşlem Tamamlandı!\n";
    echo "📂 Dosya oluşturuldu: $OUTPUT_FILE\n";
    echo "📝 Toplam $exported satır yazıldı.\n\n";
    echo "👉 SONRAKİ ADIM: Bu dosyayı bilgisayarınıza indirin ve Google Colab'a yükleyin.\n";
    
} catch (Exception $e) {
    die("❌ Hata: " . $e->getMessage() . "\n");
}
