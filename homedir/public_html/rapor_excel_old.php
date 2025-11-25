<?php
require_once "include/db.php";
require_once "include/oturum_kontrol.php";

$rapor_id = $_GET['rapor_id'] ?? 0;
$baslangic = $_GET['baslangic_tarihi'] ?? date('Y-m-01');
$bitis = $_GET['bitis_tarihi'] ?? date('Y-m-d');

// Rapor şablonunu çek
$sql = "SELECT * FROM rapor_sablonlari WHERE id = :id AND firma_id = :firma_id";
$sth = $conn->prepare($sql);
$sth->bindParam('id', $rapor_id);
$sth->bindParam('firma_id', $_SESSION['firma_id']);
$sth->execute();
$rapor = $sth->fetch(PDO::FETCH_ASSOC);

// DEBUG
if(!$rapor) {
}
if(!$rapor) {
    die('Rapor bulunamadı');
}

$sutunlar = json_decode($rapor['sutunlar'], true);
$veri_kaynagi = $rapor['veri_kaynagi'];

// Verileri çek - TÜM ID'LER İSİMLERE ÇEVRİLDİ
$veriler = [];
switch($veri_kaynagi) {
    case 'uretim':
        $sql = "SELECT 
                    DATE_FORMAT(uit.tarih, '%d.%m.%Y') as tarih,
                    s.siparis_no,
                    pl.isim as urun_adi,
                    m.makina_adi,
                    CONCAT(p.ad, ' ', p.soyad) as personel,
                    uit.uretilen_adet,
                    uit.fire_adet,
                    DATE_FORMAT(uit.baslatma_tarih, '%d.%m.%Y %H:%i') as baslatma_tarih,
                    DATE_FORMAT(uit.bitis_tarih, '%d.%m.%Y %H:%i') as bitis_tarih,
                    f.firma_adi as firma,
                    d.departman as departman,
                    CASE uit.durum 
                        WHEN 'tamamlandi' THEN 'Tamamlandı'
                        WHEN 'devam_ediyor' THEN 'Devam Ediyor'
                        WHEN 'beklemede' THEN 'Beklemede'
                        ELSE uit.durum 
                    END as durum_text
                FROM uretim_islem_tarihler uit
                LEFT JOIN planlama pl ON pl.id = uit.planlama_id
                LEFT JOIN siparisler s ON s.id = pl.siparis_id
                LEFT JOIN makinalar m ON m.id = uit.makina_id
                LEFT JOIN personeller p ON p.id = uit.personel_id
                LEFT JOIN firmalar f ON f.id = uit.firma_id
                LEFT JOIN departmanlar d ON d.id = pl.departman_id
                WHERE uit.firma_id = :firma_id 
                AND uit.tarih BETWEEN :baslangic AND :bitis
                ORDER BY uit.tarih DESC";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('baslangic', $baslangic);
        $sth->bindParam('bitis', $bitis);
        $sth->execute();
        $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'siparisler':
        $sql = "SELECT 
                    s.siparis_no,
                    m.firma_unvani as musteri,
                    s.isin_adi,
                    s.adet,
                    DATE_FORMAT(s.termin, '%d.%m.%Y') as termin,
                    CASE s.durum 
                        WHEN 'onay_bekliyor' THEN 'Onay Bekliyor'
                        WHEN 'onaylandi' THEN 'Onaylandı'
                        WHEN 'uretimde' THEN 'Üretimde'
                        WHEN 'tamamlandi' THEN 'Tamamlandı'
                        WHEN 'iptal' THEN 'İptal'
                        ELSE s.durum 
                    END as durum,
                    DATE_FORMAT(s.tarih, '%d.%m.%Y') as olusturma_tarihi,
                    f.firma_adi as firma,
                    CONCAT(p.ad, ' ', p.soyad) as olusturan_personel,
                    s.paketleme,
                    s.aciklama
                FROM siparisler s
                LEFT JOIN musteri m ON m.id = s.musteri_id
                LEFT JOIN firmalar f ON f.id = s.firma_id
                LEFT JOIN personeller p ON p.id = s.onaylayan_personel_id
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
        
    case 'planlama':
        $sql = "SELECT 
                    s.siparis_no,
                    p.isim as urun,
                    p.uretilecek_adet as adet,
                    CONCAT(p.mevcut_asama, '/', p.asama_sayisi) as asama,
                    p.mevcut_asama,
                    p.asama_sayisi,
                    CASE p.durum 
                        WHEN 'baslamadi' THEN 'Başlamadı'
                        WHEN 'basladi' THEN 'Başladı'
                        WHEN 'bitti' THEN 'Bitti'
                        WHEN 'beklemede' THEN 'Beklemede'
                        ELSE p.durum 
                    END as durum,
                    DATE_FORMAT(s.termin, '%d.%m.%Y') as termin,
                    m.firma_unvani as musteri,
                    f.firma_adi as firma,
                    d.departman as mevcut_departman,
                    DATE_FORMAT(p.tarih, '%d.%m.%Y') as planlama_tarihi
                FROM planlama p
                JOIN siparisler s ON s.id = p.siparis_id
                LEFT JOIN musteri m ON m.id = s.musteri_id
                LEFT JOIN firmalar f ON f.id = p.firma_id
                LEFT JOIN departmanlar d ON d.id = p.departman_id
                WHERE p.firma_id = :firma_id 
                AND p.tarih BETWEEN :baslangic AND :bitis
                ORDER BY p.sira";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('baslangic', $baslangic);
        $sth->bindParam('bitis', $bitis);
        $sth->execute();
        $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'makinalar':
        $sql = "SELECT 
                    m.makina_adi,
                    m.makina_modeli,
                    CASE m.durumu 
                        WHEN 'aktif' THEN 'Aktif'
                        WHEN 'pasif' THEN 'Pasif'
                        WHEN 'arizali' THEN 'Arızalı'
                        WHEN 'bakimda' THEN 'Bakımda'
                        ELSE m.durumu 
                    END as durum,
                    d.departman,
                    f.firma_adi as firma,
                    COUNT(DISTINCT uit.id) as toplam_is,
                    COUNT(DISTINCT CASE WHEN uit.durum = 'tamamlandi' THEN uit.id END) as tamamlanan_is,
                    SUM(uit.uretilen_adet) as toplam_uretilen,
                    ROUND(
                        (COUNT(DISTINCT CASE WHEN uit.durum = 'tamamlandi' THEN uit.id END) * 100.0) / 
                        NULLIF(COUNT(DISTINCT uit.id), 0), 
                    2) as verimlilik
                FROM makinalar m
                LEFT JOIN departmanlar d ON d.id = m.departman_id
                LEFT JOIN firmalar f ON f.id = m.firma_id
                LEFT JOIN uretim_islem_tarihler uit ON uit.makina_id = m.id 
                    AND uit.tarih BETWEEN :baslangic AND :bitis
                WHERE m.firma_id = :firma_id
                GROUP BY m.id, m.makina_adi, m.makina_modeli, m.durumu, d.departman, f.firma_adi
                ORDER BY m.makina_adi";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('baslangic', $baslangic);
        $sth->bindParam('bitis', $bitis);
        $sth->execute();
        $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'personel':
        $sql = "SELECT 
                    CONCAT(p.ad, ' ', p.soyad) as personel,
                    p.email,
                    y.yetki as yetki,
                    d.departman,
                    f.firma_adi as firma,
                    COUNT(DISTINCT uit.id) as toplam_is,
                    COUNT(DISTINCT CASE WHEN uit.durum = 'tamamlandi' THEN uit.id END) as tamamlanan_is,
                    SUM(uit.uretilen_adet) as uretilen_adet,
                    SUM(uit.fire_adet) as fire_adet,
                    ROUND(
                        (COUNT(DISTINCT CASE WHEN uit.durum = 'tamamlandi' THEN uit.id END) * 100.0) / 
                        NULLIF(COUNT(DISTINCT uit.id), 0), 
                    2) as verimlilik,
                    GROUP_CONCAT(DISTINCT m.makina_adi SEPARATOR ', ') as makinalar
                FROM personeller p
                LEFT JOIN yetkiler y ON y.id = p.yetki_id
                LEFT JOIN departmanlar d ON d.id = p.departman_id
                LEFT JOIN firmalar f ON f.id = p.firma_id
                LEFT JOIN uretim_islem_tarihler uit ON uit.personel_id = p.id 
                    AND uit.tarih BETWEEN :baslangic AND :bitis
                LEFT JOIN makinalar m ON m.id = uit.makina_id
                WHERE p.firma_id = :firma_id
                GROUP BY p.id, p.ad, p.soyad, p.email, y.yetki, d.departman, f.firma_adi
                ORDER BY p.ad, p.soyad";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('baslangic', $baslangic);
        $sth->bindParam('bitis', $bitis);
        $sth->execute();
        $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'stok':
        $sql = "SELECT 
                    st.stok_adi,
                    st.stok_kodu,
                    k.kategori_adi as kategori,
                    sh.hareket_tipi,
                    sh.miktar,
                    st.birim,
                    DATE_FORMAT(sh.tarih, '%d.%m.%Y %H:%i') as tarih,
                    sh.aciklama,
                    CONCAT(p.ad, ' ', p.soyad) as islem_yapan,
                    f.firma_adi as firma,
                    s.siparis_no,
                    m.makina_adi
                FROM stok_hareketleri sh
                LEFT JOIN stok st ON st.id = sh.stok_id
                LEFT JOIN stok_kategoriler k ON k.id = st.kategori_id
                LEFT JOIN personeller p ON p.id = sh.personel_id
                LEFT JOIN firmalar f ON f.id = sh.firma_id
                LEFT JOIN siparisler s ON s.id = sh.siparis_id
                LEFT JOIN makinalar m ON m.id = sh.makina_id
                WHERE sh.firma_id = :firma_id 
                AND sh.tarih BETWEEN :baslangic AND :bitis
                ORDER BY sh.tarih DESC";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('baslangic', $baslangic);
        $sth->bindParam('bitis', $bitis);
        $sth->execute();
        $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
        break;
}

// Excel oluştur (XML formatında)
$dosya_adi = $rapor['rapor_adi'] . '_' . date('Y-m-d_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $dosya_adi . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // UTF-8 BOM

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

echo '<Styles>';
echo '<Style ss:ID="Header">';
echo '<Font ss:Bold="1" ss:Color="#FFFFFF"/>';
echo '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>';
echo '<Borders>';
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
echo '</Borders>';
echo '</Style>';

echo '<Style ss:ID="Cell">';
echo '<Borders>';
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '</Borders>';
echo '</Style>';

echo '<Style ss:ID="EvenRow">';
echo '<Interior ss:Color="#F2F2F2" ss:Pattern="Solid"/>';
echo '<Borders>';
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>';
echo '</Borders>';
echo '</Style>';
echo '</Styles>';

echo '<Worksheet ss:Name="' . htmlspecialchars($rapor['rapor_adi']) . '">';
echo '<Table>';

// Başlık satırı
echo '<Row>';
foreach($sutunlar as $sutun) {
    echo '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($sutun['label']) . '</Data></Cell>';
}
echo '</Row>';

// Veri satırları
$row_num = 0;
foreach($veriler as $satir) {
    $row_num++;
    $style = ($row_num % 2 == 0) ? 'EvenRow' : 'Cell';
    
    echo '<Row>';
    foreach($sutunlar as $sutun) {
        $deger = $satir[$sutun['key']] ?? '-';
        
        // Veri tipini belirle
        if(is_numeric($deger) && strpos($deger, '.') !== false) {
            $type = 'Number';
        } elseif(is_numeric($deger)) {
            $type = 'Number';
        } else {
            $type = 'String';
        }
        
        echo '<Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . htmlspecialchars($deger) . '</Data></Cell>';
    }
    echo '</Row>';
}

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';
?>
