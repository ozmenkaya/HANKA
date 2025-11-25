<?php
require_once "include/oturum_kontrol.php";
require_once "include/db.php";
require_once "include/OpenAI.php";

header("Content-Type: application/json; charset=utf-8");

try {
    // POST kontrolü
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Sadece POST istekleri kabul edilir");
    }
    
    $rapor_id = $_POST["rapor_id"] ?? 0;
    $baslangic = $_POST["baslangic_tarihi"] ?? date("Y-m-01");
    $bitis = $_POST["bitis_tarihi"] ?? date("Y-m-d");
    
    if (!$rapor_id) {
        throw new Exception("Rapor ID gerekli");
    }
    
    // Rapor şablonunu çek
    $sql = "SELECT * FROM rapor_sablonlari WHERE id = :id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("id", $rapor_id);
    $sth->bindParam("firma_id", $_SESSION["firma_id"]);
    $sth->execute();
    $rapor = $sth->fetch(PDO::FETCH_ASSOC);
    
    if (!$rapor) {
        throw new Exception("Rapor bulunamadı");
    }
    
    $veri_kaynagi = $rapor["veri_kaynagi"];
    
    // Verileri çek (rapor_tablo.php'deki SQL'leri kullan)
    $veriler = [];
    
    switch($veri_kaynagi) {
        case "siparislerv2":
            // rapor_tablo.php'deki SQL'i kopyala (satır 127-218)
            $sql = "SELECT 
                        s.id as siparis_id,
                        s.siparis_no,
                        m.firma_unvani as musteri_adi,
                        sft.tip as siparis_tipi,
                        t.tur as tur,
                        s.isin_adi,
                        s.adet,
                        s.fiyat,
                        s.para_cinsi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.isim')) as urun_ismi,
                        DATE_FORMAT(s.termin, '%d.%m.%Y') as termin,
                        DATE_FORMAT(s.tarih, '%d.%m.%Y') as siparis_tarihi,
                        CASE s.durum 
                            WHEN 0 THEN 'Onay Bekliyor'
                            WHEN 1 THEN 'Onaylandı'
                            WHEN 2 THEN 'Üretimde'
                            WHEN 3 THEN 'Tamamlandı'
                            ELSE 'Bilinmiyor'
                        END as durum
                    FROM siparisler s
                    LEFT JOIN musteri m ON m.id = s.musteri_id
                    LEFT JOIN siparis_form_tipleri sft ON sft.id = s.tip_id
                    LEFT JOIN turler t ON t.id = s.tur_id
                    WHERE s.firma_id = :firma_id 
                    AND s.tarih BETWEEN :baslangic AND :bitis
                    ORDER BY s.tarih DESC
                    LIMIT 200";
            break;
            
        case "uretim":
            $sql = "SELECT 
                        DATE_FORMAT(uit.tarih, '%d.%m.%Y') as tarih,
                        s.siparis_no,
                        m.firma_unvani as musteri,
                        CONCAT(p.ad, ' ', p.soyad) as personel,
                        mk.makina_adi,
                        uit.uretilen_adet,
                        uit.fire_adet,
                        uit.durum
                    FROM uretim_islem_tarihler uit
                    LEFT JOIN siparisler s ON s.id = uit.siparis_id
                    LEFT JOIN musteri m ON m.id = s.musteri_id
                    LEFT JOIN personeller p ON p.id = uit.personel_id
                    LEFT JOIN makinalar mk ON mk.id = uit.makina_id
                    WHERE uit.firma_id = :firma_id 
                    AND uit.tarih BETWEEN :baslangic AND :bitis
                    ORDER BY uit.tarih DESC
                    LIMIT 200";
            break;
            
        default:
            throw new Exception("Bu rapor türü için AI analizi henüz desteklenmiyor");
    }
    
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION["firma_id"]);
    $sth->bindParam("baslangic", $baslangic);
    $sth->bindParam("bitis", $bitis);
    $sth->execute();
    $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($veriler)) {
        throw new Exception("Analiz için veri bulunamadı");
    }
    
    // OpenAI ile analiz yap
    $ai = new OpenAI();
    $analysis = $ai->analyzeReport($veriler, $rapor["rapor_adi"]);
    
    // Log kaydet (opsiyonel)
    $log_sql = "INSERT INTO ai_analiz_log (
        firma_id, 
        kullanici_id, 
        rapor_id, 
        kayit_sayisi, 
        analiz, 
        tarih
    ) VALUES (
        :firma_id,
        :kullanici_id,
        :rapor_id,
        :kayit_sayisi,
        :analiz,
        NOW()
    )";
    
    try {
        $log_sth = $conn->prepare($log_sql);
        $log_sth->execute([
            "firma_id" => $_SESSION["firma_id"],
            "kullanici_id" => $_SESSION["kullanici_id"],
            "rapor_id" => $rapor_id,
            "kayit_sayisi" => count($veriler),
            "analiz" => $analysis
        ]);
    } catch (Exception $e) {
        // Log tablosu yoksa sessizce geç
    }
    
    // Başarılı yanıt
    echo json_encode([
        "success" => true,
        "analysis" => $analysis,
        "record_count" => count($veriler),
        "report_name" => $rapor["rapor_adi"]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
