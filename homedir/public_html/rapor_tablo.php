<?php
require_once "include/oturum_kontrol.php";
require_once "include/db.php";

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

if(!$rapor) {
    die('Rapor bulunamadı');
}

$sutunlar = json_decode($rapor['sutunlar'], true);
$veri_kaynagi = $rapor['veri_kaynagi'];

// Verileri çek
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
        break;
        
    case 'siparisler':
        $sql = "SELECT 
                    s.siparis_no,
                    m.firma_unvani as musteri_adi,
                    sft.tip as siparis_tipi,
                    t.tur as tur,
                    b.ad as birim,
                    s.isin_adi,
                    s.adet,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.isim')) as urun_ismi,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.miktar')) as json_miktar,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.kdv')) as kdv_orani,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.birim_fiyat')) as birim_fiyat_json,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.aciklama')) as json_aciklama,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.EBAT')) as ebat,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.BASKI')) as baski,
                    COALESCE(
                        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.GRAMAJ')), ''),
                        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"KARTON GRAMAJI\"')), '')
                    ) as gramaj,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.KARTON')) as karton,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"KAĞIT\"')) as kagit,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.LAMINASYON')) as laminasyon,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"UV LAK\"')) as uv_lak,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.VARAK')) as varak,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.KIRIM')) as kirim,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.PAKETLEME')) as paketleme_json,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"YAPIŞTIRMA\"')) as yapistirma,
                    JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.CILT')) as cilt,
                    ul.baslik as ulke,
                    sh.baslik as sehir,
                    il.baslik as ilce,
                    CONCAT(po.ad, ' ', po.soyad) as onaylayan,
                    ot.odeme_sekli as odeme_sekli,
                    my.yetkili_adi as musteri_temsilcisi,
                    DATE_FORMAT(s.termin, '%d.%m.%Y') as termin,
                    CASE s.durum 
                        WHEN 0 THEN 'Onay Bekliyor'
                        WHEN 1 THEN 'Onaylandı'
                        WHEN 2 THEN 'Üretimde'
                        WHEN 3 THEN 'Tamamlandı'
                        ELSE 'Bilinmiyor'
                    END as durum,
                    DATE_FORMAT(s.tarih, '%d.%m.%Y %H:%i') as olusturma_tarihi
                FROM siparisler s
                LEFT JOIN musteri m ON m.id = s.musteri_id
                LEFT JOIN siparis_form_tipleri sft ON sft.id = s.tip_id
                LEFT JOIN turler t ON t.id = s.tur_id
                LEFT JOIN birimler b ON b.id = s.birim_id
                LEFT JOIN firmalar f ON f.id = s.firma_id
                LEFT JOIN ulkeler ul ON ul.id = s.ulke_id
                LEFT JOIN sehirler sh ON sh.id = s.sehir_id
                LEFT JOIN ilceler il ON il.id = s.ilce_id
                LEFT JOIN personeller po ON po.id = s.onaylayan_personel_id
                LEFT JOIN odeme_tipleri ot ON ot.id = s.odeme_sekli_id
                LEFT JOIN musteri_yetkilileri my ON my.id = s.musteri_temsilcisi_id
                WHERE s.firma_id = :firma_id 
                AND s.tarih BETWEEN :baslangic AND :bitis
                ORDER BY s.tarih DESC";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->bindParam('baslangic', $baslangic);
        $sth->bindParam('bitis', $bitis);
        $sth->execute();
        $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
        $veri_yuklendi = true;
        break;

    case 'siparislerv2':
            $sql = "SELECT 
                        s.id as siparis_id,
                        s.siparis_no,
                        m.firma_unvani as musteri_adi,
                        m.vergi_numarasi as musteri_vergi_no,
                        m.vergi_dairesi as musteri_vergi_dairesi,
                        m.e_mail as musteri_email,
                        m.cep_tel as musteri_telefon,
                        sft.tip as siparis_tipi,
                        t.tur as tur,
                        b.ad as birim,
                        s.isin_adi,
                        s.adet,
                        s.fiyat,
                        s.para_cinsi,
                        -- JSON alanları
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.isim')) as urun_ismi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.miktar')) as json_miktar,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.kdv')) as kdv_orani,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.birim_fiyat')) as birim_fiyat_json,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.aciklama')) as json_aciklama,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.numune')) as numune,
                        -- Form alanları
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.EBAT')) as ebat,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.BASKI')) as baski,
                        COALESCE(
                            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.GRAMAJ')), ''),
                            NULLIF(JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"KARTON GRAMAJI\"')), '')
                        ) as gramaj,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.KARTON')) as karton,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"KAĞIT\"')) as kagit,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.LAMINASYON')) as laminasyon,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"UV LAK\"')) as uv_lak,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.VARAK')) as varak,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.KIRIM')) as kirim,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.PAKETLEME')) as paketleme_json,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"YAPIŞTIRMA\"')) as yapistirma,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.CILT')) as cilt,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.GOFRAJ')) as gofraj,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"OFSET LAK BİLGİSİ\"')) as ofset_lak_bilgisi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"KESİM\"')) as kesim,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"ONDÜLA KALİTE\"')) as ondula_kalite,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"ONDÜLA EBAT\"')) as ondula_ebat,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"MÜŞTERİ SİPARİŞ NUMARASI\"')) as musteri_siparis_numarasi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"BASKI YÜZÜ\"')) as baski_yuzu,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"LAK YÜZÜ\"')) as lak_yuzu,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"SELEFON MALZEMESİ\"')) as selefon_malzemesi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"SELEFON YÜZÜ\"')) as selefon_yuzu,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"ASETAT EBADI\"')) as asetat_ebadi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"PAKETLEME NOTLARI\"')) as paketleme_notlari,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"TEKLİ EBADI\"')) as tekli_ebadi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"MONTAJ SAYISI\"')) as montaj_sayisi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"BIÇAK NUMARASI\"')) as bicak_numarasi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"TASARIM NUMARASI\"')) as tasarim_numarasi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"KOLİ NUMARASI\"')) as koli_numarasi,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"KOLİ İÇİ ADET\"')) as koli_ici_adet,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"PALET ÖLÇÜSÜ\"')) as palet_olcusu,
                        JSON_UNQUOTE(JSON_EXTRACT(s.veriler, '$.form.\"PALET İÇİ KOLİ ADETİ\"')) as palet_ici_koli_adeti,
                        -- Lokasyon bilgileri
                        ul.baslik as ulke,
                        sh.baslik as sehir,
                        il.baslik as ilce,
                        -- Personel bilgileri
                        CONCAT(po.ad, ' ', po.soyad) as onaylayan,
                        NULL as olusturan,
                        -- Ödeme ve temsilci
                        ot.odeme_sekli as odeme_sekli,
                        my.yetkili_adi as musteri_temsilcisi,
                        my.yetkili_cep as temsilci_telefon,
                        my.yetkili_mail as temsilci_email,
                        -- Tarihler
                        DATE_FORMAT(s.termin, '%d.%m.%Y') as termin,
                        DATE_FORMAT(s.tarih, '%d.%m.%Y %H:%i') as siparis_tarihi,
                        -- Durum
                        CASE s.durum 
                            WHEN 0 THEN 'Onay Bekliyor'
                            WHEN 1 THEN 'Onaylandı'
                            WHEN 2 THEN 'Üretimde'
                            WHEN 3 THEN 'Tamamlandı'
                            ELSE 'Bilinmiyor'
                        END as durum,
                        CASE s.islem 
                            WHEN 'yeni' THEN 'Yeni'
                            WHEN 'islemde' THEN 'İşlemde'
                            WHEN 'tamamlandi' THEN 'Tamamlandı'
                            WHEN 'teslim_edildi' THEN 'Teslim Edildi'
                            WHEN 'iptal' THEN 'İptal'
                            ELSE s.islem 
                        END as islem_durumu,
                        -- Firma
                        f.firma_adi as firma
                    FROM siparisler s
                    LEFT JOIN musteri m ON m.id = s.musteri_id
                    LEFT JOIN siparis_form_tipleri sft ON sft.id = s.tip_id
                    LEFT JOIN turler t ON t.id = s.tur_id
                    LEFT JOIN birimler b ON b.id = s.birim_id
                    LEFT JOIN firmalar f ON f.id = s.firma_id
                    LEFT JOIN ulkeler ul ON ul.id = s.ulke_id
                    LEFT JOIN sehirler sh ON sh.id = s.sehir_id
                    LEFT JOIN ilceler il ON il.id = s.ilce_id
                    LEFT JOIN personeller po ON po.id = s.onaylayan_personel_id
                    -- LEFT JOIN personeller pol ON pol.id = s.olusturan_personel_id
                    LEFT JOIN odeme_tipleri ot ON ot.id = s.odeme_sekli_id
                    LEFT JOIN musteri_yetkilileri my ON my.id = s.musteri_temsilcisi_id
                    WHERE s.firma_id = :firma_id 
                    AND s.tarih BETWEEN :baslangic AND :bitis
                    ORDER BY s.tarih DESC";
            $sth = $conn->prepare($sql);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->bindParam('baslangic', $baslangic);
            $sth->bindParam('bitis', $bitis);
            $sth->execute();
            $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
            $veri_yuklendi = true;
            break;
        
    case 'planlama':
        $sql = "SELECT 
                    s.siparis_no,
                    m.firma_unvani as musteri,
                    p.isim as urun,
                    p.uretilecek_adet as adet,
                    CONCAT(p.mevcut_asama, '/', p.asama) as asama,
                    CASE p.durum 
                        WHEN 'baslamadi' THEN 'Başlamadı'
                        WHEN 'devam_ediyor' THEN 'Devam Ediyor'
                        WHEN 'tamamlandi' THEN 'Tamamlandı'
                        ELSE p.durum 
                    END as durum,
                    DATE_FORMAT(p.termin, '%d.%m.%Y') as termin,
                    f.firma_adi as firma,
                    d.departman as mevcut_departman,
                    DATE_FORMAT(p.tarih, '%d.%m.%Y') as planlama_tarihi
                FROM planlama p
                LEFT JOIN siparisler s ON s.id = p.siparis_id
                LEFT JOIN musteri m ON m.id = s.musteri_id
                LEFT JOIN firmalar f ON f.id = p.firma_id
                LEFT JOIN departmanlar d ON d.id = p.departman_id
                WHERE p.firma_id = :firma_id 
                AND p.tarih BETWEEN :baslangic AND :bitis
                ORDER BY p.tarih DESC";
        break;
        
    default:
        die('Desteklenmeyen veri kaynağı');
}

if(!isset($veri_yuklendi)) {
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('baslangic', $baslangic);
    $sth->bindParam('bitis', $bitis);
    $sth->execute();
    $veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid">

    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="padding: 1rem 1.25rem;">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-table"></i> <?= htmlspecialchars($rapor['rapor_adi']) ?>
                    </h5>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-primary" id="ai-analiz-btn" onclick="analyzeWithAI()">
                            <i class="fa-solid fa-brain"></i> AI Analiz
                        </button>
                        <a href="/index.php?url=raporlar" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Geri
                        </a>
                    </div>
                </div>
                <div class="card-body">
                        <?= date('d.m.Y', strtotime($baslangic)) ?> - <?= date('d.m.Y', strtotime($bitis)) ?>
                        <span class="ms-3">
                            <strong>Toplam Kayıt:</strong> <?= count($veriler) ?>
                        </span>
                    </div>
                    
                    <?php if(empty($veriler)): ?>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle"></i>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="raporTablosu">
                                <thead class="table-dark">
                                    <tr>
                                        <?php foreach($sutunlar as $sutun): ?>
                                            <th><?= htmlspecialchars($sutun['label']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($veriler as $satir): ?>
                                        <tr>
                                            <?php foreach($sutunlar as $sutun): ?>
                                                <td><?= htmlspecialchars($satir[$sutun['key']] ?? '-') ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
/* DataTables Search Box styling */
.dataTables_filter {
    float: none !important;
    text-align: left !important;
    padding-left: 0 !important;
}
.dataTables_filter label {
    display: flex;
    align-items: center;
    gap: 8px;
}
.dataTables_filter input {
    width: 300px !important;
    margin-left: 0 !important;
}

/* DataTables Buttons styling */
.dt-buttons {
    margin-bottom: 15px;
}
.dt-buttons .btn {
    margin-right: 5px;
    margin-bottom: 5px;
}
</style>


<script>
// DataTables eklentisi varsa kullan
$(document).ready(function() {
    if($.fn.DataTable) {
        $('#raporTablosu').DataTable({
            pageLength: 50,
            language: {
                url: 'https://cdnjs.cloudflare.com/ajax/libs/datatables-plugins/1.10.21/i18n/Turkish.json'
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fa-solid fa-file-excel"></i> Excel',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i class="fa-solid fa-file-csv"></i> CSV',
                    className: 'btn btn-info',
                    charset: 'utf-8',
                    bom: true,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger',
                    exportOptions: {
                        columns: ':visible'
                    },
                    orientation: 'landscape',
                    customize: function(doc) {
                        // Sütun sayısına göre sayfa boyutunu belirle
                        var columnCount = doc.content[1].table.body[0].length;
                        
                        // Sayfa boyutunu dinamik ayarla
                        if (columnCount > 20) {
                            // Çok fazla sütun - A1 veya custom boyut
                            doc.pageSize = { width: 841.89, height: 1190.55 }; // A1 landscape
                        } else if (columnCount > 15) {
                            // Fazla sütun - A2
                            doc.pageSize = { width: 594.28, height: 841.89 }; // A2 landscape
                        } else if (columnCount > 10) {
                            // Orta sütun - A3
                            doc.pageSize = 'A3';
                        } else {
                            // Az sütun - A4
                            doc.pageSize = 'A4';
                        }
                        
                        doc.pageOrientation = 'landscape';
                        
                        // Tüm sütunları otomatik genişlikle sığdır
                        doc.content[1].table.widths = Array(columnCount).fill('auto');
                        
                        // Font boyutunu sütun sayısına göre ayarla
                        if (columnCount > 20) {
                            doc.defaultStyle.fontSize = 6;
                            doc.styles.tableHeader.fontSize = 7;
                        } else if (columnCount > 15) {
                            doc.defaultStyle.fontSize = 7;
                            doc.styles.tableHeader.fontSize = 8;
                        } else {
                            doc.defaultStyle.fontSize = 8;
                            doc.styles.tableHeader.fontSize = 9;
                        }
                        
                        // Minimal kenar boşlukları
                        doc.pageMargins = [10, 10, 10, 10];
                        
                        // Tablo genişliğini sayfa genişliğine sığdır
                        doc.content[1].table.dontBreakRows = true;
                    },
                },
                {
                    extend: 'print',
                    text: '<i class="fa-solid fa-print"></i> Yazdır',
                    className: 'btn btn-secondary',
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ],
            
        });
    }
});
</script>
<!-- DataTables Buttons Extension Libraries -->

<!-- AI Analiz Modal -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fa-solid fa-brain"></i> AI Rapor Analizi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ai-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Analiz ediliyor...</span>
                    </div>
                    <p class="mt-3">🤖 Rapor analiz ediliyor, lütfen bekleyin...</p>
                    <small class="text-muted">Bu işlem 10-30 saniye sürebilir</small>
                </div>
                <div id="ai-result" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle"></i>
                        <strong>Rapor:</strong> <span id="ai-report-name"></span><br>
                        <strong>Analiz Edilen Kayıt:</strong> <span id="ai-record-count"></span>
                    </div>
                    <div id="ai-analysis-content" class="markdown-content">
                        <!-- AI analizi buraya gelecek -->
                    </div>
                </div>
                <div id="ai-error" style="display: none;" class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <strong>Hata:</strong> <span id="ai-error-message"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
// AI Analiz fonksiyonu
function analyzeWithAI() {
    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    modal.show();
    
    // Gösterge durumlarını sıfırla
    document.getElementById('ai-loading').style.display = 'block';
    document.getElementById('ai-result').style.display = 'none';
    document.getElementById('ai-error').style.display = 'none';
    
    // URL parametrelerini al
    const params = new URLSearchParams(window.location.search);
    const rapor_id = params.get('rapor_id');
    const baslangic_tarihi = params.get('baslangic_tarihi') || '<?= date("Y-m-01") ?>';
    const bitis_tarihi = params.get('bitis_tarihi') || '<?= date("Y-m-d") ?>';
    
    // AJAX isteği
    const formData = new FormData();
    formData.append('rapor_id', rapor_id);
    formData.append('baslangic_tarihi', baslangic_tarihi);
    formData.append('bitis_tarihi', bitis_tarihi);
    
    fetch('/index.php?url=rapor_ai_analiz', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('ai-loading').style.display = 'none';
        
        if (data.success) {
            // Başarılı analiz
            document.getElementById('ai-result').style.display = 'block';
            document.getElementById('ai-report-name').textContent = data.report_name;
            document.getElementById('ai-record-count').textContent = data.record_count;
            
            // Markdown'ı HTML'e çevir (basit)
            const analysisHtml = data.analysis
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/^### (.+)$/gm, '<h5>$1</h5>')
                .replace(/^## (.+)$/gm, '<h4>$1</h4>')
                .replace(/^# (.+)$/gm, '<h3>$1</h3>')
                .replace(/^- (.+)$/gm, '<li>$1</li>')
                .replace(/\n\n/g, '</p><p>')
                .replace(/^(.+)$/gm, '<p>$1</p>');
            
            document.getElementById('ai-analysis-content').innerHTML = analysisHtml;
        } else {
            // Hata
            document.getElementById('ai-error').style.display = 'block';
            document.getElementById('ai-error-message').textContent = data.error;
        }
    })
    .catch(error => {
        document.getElementById('ai-loading').style.display = 'none';
        document.getElementById('ai-error').style.display = 'block';
        document.getElementById('ai-error-message').textContent = 'Bağlantı hatası: ' + error.message;
    });
}
</script>

<style>
.markdown-content {
    line-height: 1.8;
}

.markdown-content h3 {
    color: #0d6efd;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
}

.markdown-content h4 {
    color: #6c757d;
    margin-top: 1.2rem;
    margin-bottom: 0.8rem;
}

.markdown-content h5 {
    color: #495057;
    margin-top: 1rem;
    margin-bottom: 0.6rem;
}

.markdown-content li {
    margin-bottom: 0.5rem;
    margin-left: 1.5rem;
}

.markdown-content p {
    margin-bottom: 1rem;
}

.markdown-content strong {
    color: #212529;
}

#ai-analiz-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
}

#ai-analiz-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

#ai-analiz-btn:active {
    transform: translateY(0);
}

.card-header .d-flex.gap-2 {
    gap: 0.5rem !important;
}
</style>
