<?php
require_once "include/oturum_kontrol.php";
require_once "include/db.php";

$rapor_id = $_GET['rapor_id'] ?? 0;
$baslangic = $_GET['baslangic'] ?? date('Y-m-01');
$bitis = $_GET['bitis'] ?? date('Y-m-t');

// Rapor bilgilerini çek
$sql = "SELECT * FROM rapor_sablonlari WHERE id = ? AND firma_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$rapor_id, $_SESSION['firma_id']]);
$rapor = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$rapor) {
    die('Rapor bulunamadı');
}

$veri_kaynagi = $rapor['veri_kaynagi'];
$secili_sutunlar = json_decode($rapor['sutunlar'], true);

// Veri kaynağına göre sorgu
switch($veri_kaynagi) {
    case 'siparisler':
        $sql = "SELECT 
                    s.siparis_no,
                    s.isin_adi,
                    s.adet,
                    
                    -- ID'leri TEXT'e çevir
                    m.firma_unvani as musteri_adi,
                    sft.tip_adi as siparis_tipi,
                    t.tur_adi as tur,
                    b.birim_adi as birim,
                    u.ulke_adi as ulke,
                    seh.sehir_adi as sehir,
                    ilc.ilce_adi as ilce,
                    CONCAT(onaylayan.ad, ' ', onaylayan.soyad) as onaylayan_personel,
                    ot.tip_adi as odeme_sekli,
                    CONCAT(temsilci.ad, ' ', temsilci.soyad) as musteri_temsilcisi,
                    
                    -- Tarihler
                    DATE_FORMAT(s.termin, '%d.%m.%Y') as termin,
                    DATE_FORMAT(s.uretim, '%d.%m.%Y') as uretim_tarihi,
                    DATE_FORMAT(s.vade, '%d.%m.%Y') as vade_tarihi,
                    DATE_FORMAT(s.tarih, '%d.%m.%Y %H:%i') as olusturma_tarihi,
                    
                    -- Fiyat bilgileri
                    s.fiyat,
                    s.para_cinsi,
                    
                    -- Durum
                    CASE s.islem 
                        WHEN 'yeni' THEN 'Yeni'
                        WHEN 'islemde' THEN 'İşlemde'
                        WHEN 'tamamlandi' THEN 'Tamamlandı'
                        WHEN 'teslim_edildi' THEN 'Teslim Edildi'
                        WHEN 'iptal' THEN 'İptal'
                        ELSE s.islem 
                    END as islem_durumu,
                    
                    CASE s.numune 
                        WHEN 'var' THEN 'Var'
                        WHEN 'yok' THEN 'Yok'
                    END as numune_durumu,
                    
                    s.paketleme,
                    s.nakliye,
                    s.aciklama,
                    f.firma_adi as firma,
                    
                    -- JSON verileri ayrı sütunlara
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.isim')) as urun_ismi,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.miktar')) as json_miktar,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.kdv')) as kdv_orani,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.birim_fiyat')) as birim_fiyat,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.aciklama')) as json_aciklama,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.EBAT')) as ebat,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.BASKI')) as baski,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.GRAMAJ')) as gramaj,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.KARTON')) as karton,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.KAĞIT')) as kagit,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.LAMINASYON')) as laminasyon,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"UV LAK\"')) as uv_lak,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.VARAK')) as varak,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.KIRIM')) as kirim,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.PAKETLEME')) as json_paketleme,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.YAPIŞTIRMA')) as yapistirma,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.CILT')) as cilt

                FROM siparisler s
                LEFT JOIN musteri m ON m.id = s.musteri_id
                LEFT JOIN siparis_form_tipleri sft ON sft.id = s.tip_id
                LEFT JOIN turler t ON t.id = s.tur_id
                LEFT JOIN birimler b ON b.id = s.birim_id
                LEFT JOIN ulkeler u ON u.id = s.ulke_id
                LEFT JOIN sehirler seh ON seh.id = s.sehir_id
                LEFT JOIN ilceler ilc ON ilc.id = s.ilce_id
                LEFT JOIN personeller onaylayan ON onaylayan.id = s.onaylayan_personel_id
                LEFT JOIN odeme_tipleri ot ON ot.id = s.odeme_sekli_id
                LEFT JOIN musteri_yetkilileri temsilci ON temsilci.id = s.musteri_temsilcisi_id
                LEFT JOIN firmalar f ON f.id = s.firma_id
                WHERE s.firma_id = :firma_id 
                AND s.tarih BETWEEN :baslangic AND :bitis
                ORDER BY s.tarih DESC";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('baslangic', $baslangic);
        $sth->bindParam('bitis', $bitis);
        $sth->execute();
        $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    // Diğer case'ler buraya gelecek (uretim, planlama, vs.)
    default:
        die('Geçersiz veri kaynağı');
}

// Excel XML oluştur
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment;filename="' . $rapor['rapor_adi'] . '_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">';
echo '<Worksheet ss:Name="Rapor">';
echo '<Table>';

// Başlık satırı - sadece seçili sütunlar
echo '<Row>';
if(!empty($veriler)) {
    $ilk_satir = $veriler[0];
    foreach(array_keys($ilk_satir) as $kolon) {
        // Kolon ismini güzelleştir
        $label = ucfirst(str_replace('_', ' ', $kolon));
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($label) . '</Data></Cell>';
    }
}
echo '</Row>';

// Veri satırları
foreach($veriler as $satir) {
    echo '<Row>';
    foreach($satir as $deger) {
        $deger = $deger ?? '';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($deger) . '</Data></Cell>';
    }
    echo '</Row>';
}

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';
exit;
