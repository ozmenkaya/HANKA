<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "include/oturum_kontrol.php";

$tedarikci_id = isset($_GET['tedarikci_id']) ? intval($_GET['tedarikci_id']) : 0;

if ($tedarikci_id == 0) {
    header("Location: /index.php?url=tedarikci");
    exit;
}

// Tedarikçi bilgilerini al
$sth = $conn->prepare('SELECT * FROM tedarikciler WHERE id = :id AND firma_id = :firma_id');
$sth->bindParam('id', $tedarikci_id);
$sth->bindParam('firma_id', $_SESSION['firma_id']);
$sth->execute();
$tedarikci = $sth->fetch(PDO::FETCH_ASSOC);

if (!$tedarikci) {
    header("Location: /index.php?url=tedarikci");
    exit;
}

// Stok kalemlerini al (varsa)
$stok_kalem_idler = !empty($tedarikci['stok_kalem_idler']) ? 
    json_decode($tedarikci['stok_kalem_idler'], true) : [];

$stok_kalemleri = [];
if (!empty($stok_kalem_idler)) {
    $stok_kalem_idler_str = implode(',', $stok_kalem_idler);
    // Stok kalemlerini getir
    $sql = "SELECT * FROM stok_kalemleri WHERE id IN ({$stok_kalem_idler_str})";
    $sth = $conn->prepare($sql);
    $sth->execute();
    $stok_kalemleri = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// GİRİŞLER: stok_alt_depolar tablosundan
$sql = "SELECT 
            sad.*,
            sk.id as stok_id,
            sk.stok_kalem,
            ska.veri,
            b.ad as birim_adi
        FROM stok_alt_depolar sad
        LEFT JOIN stok_alt_kalemler ska ON sad.stok_alt_kalem_id = ska.id
        LEFT JOIN stok_kalemleri sk ON ska.stok_id = sk.id
        LEFT JOIN birimler b ON sad.birim_id = b.id
        WHERE sad.firma_id = :firma_id
        AND sad.tedarikci_id = :tedarikci_id
        ORDER BY sad.ekleme_tarihi DESC
        LIMIT 500";

$sth = $conn->prepare($sql);
$sth->bindParam(':firma_id', $_SESSION['firma_id']);
$sth->bindParam(':tedarikci_id', $tedarikci_id);
$sth->execute();
$girişler = $sth->fetchAll(PDO::FETCH_ASSOC);

// DEBUG: Giriş sayısını kontrol et
error_log("Tedarikçi ID: $tedarikci_id - Giriş sayısı: " . count($girişler));
if (count($girişler) > 0) {
    error_log("İlk giriş: " . print_r($girişler[0], true));
}

// TÜKETİMLER: stok_alt_depolar_kullanilanlar tablosundan
$sql = "SELECT 
            sdk.*,
            sk.stok_kalem,
            ska.veri,
            sad.tedarikci_id,
            b.ad as birim_adi,
            p.ad as personel_ad,
            p.soyad as personel_soyad,
            pl.isim as is_adi,
            pl.siparis_id,
            s.siparis_no,
            m.firma_unvani as musteri_adi,
            d.departman
        FROM stok_alt_depolar_kullanilanlar sdk
        LEFT JOIN stok_alt_depolar sad ON sdk.stok_alt_depo_id = sad.id
        LEFT JOIN stok_alt_kalemler ska ON sdk.stok_alt_kalem_id = ska.id
        LEFT JOIN stok_kalemleri sk ON ska.stok_id = sk.id
        LEFT JOIN birimler b ON sdk.birim_id = b.id
        LEFT JOIN personeller p ON sdk.personel_id = p.id
        LEFT JOIN planlama pl ON sdk.planlama_id = pl.id
        LEFT JOIN siparisler s ON pl.siparis_id = s.id
        LEFT JOIN musteri m ON s.musteri_id = m.id
        LEFT JOIN departmanlar d ON sdk.departman_id = d.id
        WHERE sad.firma_id = :firma_id
        AND sad.tedarikci_id = :tedarikci_id";

// Eğer tedarikçiye ait stok_kalem_idler belirtilmişse, ona göre filtrele
if (!empty($stok_kalem_idler)) {
    $sql .= " AND sdk.stok_alt_kalem_id IN ({$stok_kalem_idler_str})";
}

$sql .= " ORDER BY sdk.tarih DESC LIMIT 500";

$sth = $conn->prepare($sql);
$sth->bindParam(':firma_id', $_SESSION['firma_id']);
$sth->bindParam(':tedarikci_id', $tedarikci_id);
$sth->execute();
$tuketimler = $sth->fetchAll(PDO::FETCH_ASSOC);

// DEBUG: Tüketim sayısını kontrol et
error_log("Tedarikçi ID: $tedarikci_id - Tüketim sayısı: " . count($tuketimler));

// Giriş ve tüketimleri birleştir
$hareketler = [];
foreach ($girişler as $giris) {
    $hareketler[] = array_merge($giris, ['islem_turu' => 'giris']);
}
foreach ($tuketimler as $tuketim) {
    $hareketler[] = array_merge($tuketim, ['islem_turu' => 'cikis']);
}

// Tarihe göre sırala (en yeni en üstte)
usort($hareketler, function($a, $b) {
    $tarih_a = isset($a['ekleme_tarihi']) ? $a['ekleme_tarihi'] : $a['tarih'];
    $tarih_b = isset($b['ekleme_tarihi']) ? $b['ekleme_tarihi'] : $b['tarih'];
    return strtotime($tarih_b) - strtotime($tarih_a);
});

?>
<div class="row">
    <div class="card mt-2">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fa-solid fa-shopping-cart"></i> Stok Hareketleri - <?php echo htmlspecialchars($tedarikci['firma_adi']); ?>
            </h5>
            <div>
                <?php if (!empty($hareketler)): ?>
                    <button onclick="exportTableToExcel('myTable', 'stok_hareketleri_<?php echo $tedarikci['firma_adi']; ?>')" class="btn btn-success btn-sm me-2">
                        <i class="fa-solid fa-file-excel"></i> Excel
                    </button>
                    <button onclick="exportTableToPDF()" class="btn btn-danger btn-sm me-2">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                <?php endif; ?>
                <a href="/index.php?url=tedarikci" class="btn btn-light btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($stok_kalemleri)): ?>
                <div class="alert alert-info mb-3">
                    <strong>Stok Kalemleri:</strong>
                    <?php foreach ($stok_kalemleri as $kalem): ?>
                        <span class="badge bg-secondary ms-1"><?php echo $kalem['stok_kalem']; ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($hareketler)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Bu tedarikçi için stok hareketi bulunmuyor.
                    <br><small>Debug: Giriş: <?php echo count($girişler); ?>, Tüketim: <?php echo count($tuketimler); ?>, Toplam Hareket: <?php echo count($hareketler); ?></small>
                    <?php if (!empty($girişler)): ?>
                        <br><small>İlk giriş örneği: <pre><?php print_r($girişler[0]); ?></pre></small>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="myTable" class="table table-bordered table-hover table-sm">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Tarih</th>
                                <th>İşlem</th>
                                <th>Stok Kalemi</th>
                                <th>Özellikler</th>
                                <th>Miktar</th>
                                <th>Maliyet/Birim</th>
                                <th>Toplam</th>
                                <th>İş/Departman</th>
                                <th>Personel</th>
                                <th>Detay</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $toplam_giris = 0;
                            $toplam_giris_maliyet = 0;
                            $toplam_cikis = 0;
                            
                            foreach ($hareketler as $key => $hareket): 
                                $tarih = isset($hareket['ekleme_tarihi']) ? $hareket['ekleme_tarihi'] : $hareket['tarih'];
                                
                                if ($hareket['islem_turu'] == 'giris') {
                                    // GİRİŞ
                                    $miktar = $hareket['adet'];
                                    $birim_fiyat = $hareket['maliyet'];
                                    $tutar = $miktar * $birim_fiyat;
                                    $toplam_giris += $miktar;
                                    $toplam_giris_maliyet += $tutar;
                                    
                                    $row_class = 'table-success';
                                    $badge_class = 'bg-success';
                                    $icon = 'fa-arrow-down';
                                    $islem_text = 'GİRİŞ';
                                } else {
                                    // ÇIKIŞ (TÜKETİM)
                                    $miktar = $hareket['tuketim_miktari'];
                                    $fire = $hareket['fire_miktari'];
                                    $toplam_miktar = $miktar + $fire;
                                    $birim_fiyat = 0; // Tüketimde maliyet yok
                                    $tutar = 0;
                                    $toplam_cikis += $toplam_miktar;
                                    
                                    $row_class = 'table-danger';
                                    $badge_class = 'bg-danger';
                                    $icon = 'fa-arrow-up';
                                    $islem_text = 'TÜKETİM';
                                }
                                
                                // Veri JSON'dan özellikleri al
                                $veri = !empty($hareket['veri']) ? json_decode($hareket['veri'], true) : [];
                                $ozellikler = [];
                                if (!empty($veri)) {
                                    if (isset($veri['EBAT'])) $ozellikler[] = "Ebat: {$veri['EBAT']}";
                                    if (isset($veri['TİP'])) $ozellikler[] = "Tip: {$veri['TİP']}";
                                    if (isset($veri['GRAMAJ'])) $ozellikler[] = "Gramaj: {$veri['GRAMAJ']}";
                                    if (isset($veri['RENK'])) $ozellikler[] = "Renk: {$veri['RENK']}";
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?> bg-opacity-10">
                                <td><?php echo $key + 1; ?></td>
                                <td>
                                    <small><?php echo date('d.m.Y H:i', strtotime($tarih)); ?></small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                        <?php echo $islem_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $hareket['stok_kalem']; ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?php echo !empty($ozellikler) ? implode('<br>', $ozellikler) : '-'; ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <?php if ($hareket['islem_turu'] == 'giris'): ?>
                                        <strong><?php echo number_format($miktar, 2); ?></strong>
                                        <small><?php echo $hareket['birim_adi']; ?></small>
                                    <?php else: ?>
                                        <strong><?php echo number_format($miktar, 2); ?></strong>
                                        <?php if ($fire > 0): ?>
                                            <br><small class="text-danger">Fire: <?php echo number_format($fire, 2); ?></small>
                                        <?php endif; ?>
                                        <small><?php echo $hareket['birim_adi']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($hareket['islem_turu'] == 'giris'): ?>
                                        <?php echo number_format($birim_fiyat, 2); ?> 
                                        <small><?php echo $hareket['para_cinsi']; ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($hareket['islem_turu'] == 'giris'): ?>
                                        <strong><?php echo number_format($tutar, 2); ?> <?php echo $hareket['para_cinsi']; ?></strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hareket['islem_turu'] == 'cikis'): ?>
                                        <?php if (!empty($hareket['is_adi'])): ?>
                                            <div class="mb-1">
                                                <i class="fas fa-briefcase text-primary"></i>
                                                <strong><?php echo $hareket['is_adi']; ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($hareket['siparis_no'])): ?>
                                            <div class="mb-1">
                                                <small>
                                                    <i class="fas fa-file-invoice"></i>
                                                    Sipariş: <?php echo $hareket['siparis_no']; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($hareket['musteri_adi'])): ?>
                                            <div class="mb-1">
                                                <small>
                                                    <i class="fas fa-user"></i>
                                                    <?php echo $hareket['musteri_adi']; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($hareket['departman'])): ?>
                                            <span class="badge bg-info"><?php echo $hareket['departman']; ?></span>
                                        <?php endif; ?>
                                        <?php if (empty($hareket['is_adi']) && empty($hareket['siparis_no']) && empty($hareket['departman'])): ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small>Fatura: <?php echo $hareket['fatura_no']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php 
                                        if ($hareket['islem_turu'] == 'cikis' && !empty($hareket['personel_ad'])) {
                                            echo $hareket['personel_ad'] . ' ' . $hareket['personel_soyad'];
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($hareket['islem_turu'] == 'giris'): ?>
                                        <?php if (!empty($hareket['stok_id']) && !empty($hareket['stok_alt_kalem_id'])): ?>
                                            <a href="/index.php?url=stok_alt_depolar&stok_alt_kalem_id=<?php echo $hareket['stok_alt_kalem_id']; ?>&stok_id=<?php echo $hareket['stok_id']; ?>" 
                                               class="badge bg-info text-decoration-none"
                                               title="Depo detayı">
                                                <i class="fas fa-warehouse"></i>
                                            </a>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (!empty($hareket['aciklama'])): ?>
                                            <small><?php echo $hareket['aciklama']; ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-info">
                            <tr>
                                <th colspan="5" class="text-end">Toplam Giriş:</th>
                                <th class="text-end">
                                    <span class="badge bg-success"><?php echo number_format($toplam_giris, 2); ?></span>
                                </th>
                                <th colspan="5"></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Toplam Giriş Maliyeti:</th>
                                <th colspan="2" class="text-end">
                                    <span class="badge bg-success"><?php echo number_format($toplam_giris_maliyet, 2); ?> TL</span>
                                </th>
                                <th colspan="4"></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Toplam Çıkış (Tüketim):</th>
                                <th class="text-end">
                                    <span class="badge bg-danger"><?php echo number_format($toplam_cikis, 2); ?></span>
                                </th>
                                <th colspan="5"></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Kalan Stok:</th>
                                <th class="text-end">
                                    <span class="badge bg-primary"><?php echo number_format($toplam_giris - $toplam_cikis, 2); ?></span>
                                </th>
                                <th colspan="5"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                                <div class="alert alert-success mt-3">
                    <i class="fas fa-info-circle"></i> 
                    Toplam <strong><?php echo count($hareketler); ?></strong> hareket gösteriliyor (Son 500 kayıt).
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" crossorigin="anonymous"></script>

<script>
// PDF kütüphanelerini sıralı yükle ve hazır olana kadar bekle
window.pdfLibrariesLoaded = false;

(function loadPDFLibs() {
    // Önce jsPDF yükle
    var jspdfScript = document.createElement('script');
    jspdfScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    jspdfScript.onload = function() {
        console.log('jsPDF loaded');
        
        // jsPDF yüklendikten sonra autoTable yükle
        var autoTableScript = document.createElement('script');
        autoTableScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js';
        autoTableScript.onload = function() {
            console.log('autoTable plugin loaded');
            
            // autoTable'ın bağlanması için kısa bir gecikme
            setTimeout(function() {
                // Test et ve işaretle
                if (window.jspdf && window.jspdf.jsPDF) {
                    var testDoc = new window.jspdf.jsPDF();
                    if (typeof testDoc.autoTable === 'function') {
                        window.pdfLibrariesLoaded = true;
                        console.log('✅ PDF libraries ready! autoTable is available.');
                    } else {
                        console.error('❌ autoTable still not available after loading');
                    }
                }
            }, 100);
        };
        autoTableScript.onerror = function() {
            console.error('Failed to load autoTable plugin');
        };
        document.head.appendChild(autoTableScript);
    };
    jspdfScript.onerror = function() {
        console.error('Failed to load jsPDF');
    };
    document.head.appendChild(jspdfScript);
})();
</script>

<script>
// Excel Export - SheetJS kullanarak
function exportTableToExcel(tableID, filename = '') {
    var table = document.getElementById(tableID);
    var wb = XLSX.utils.table_to_book(table, {sheet: "Stok Hareketleri"});
    
    // Filename
    filename = filename ? filename + '.xlsx' : 'stok_hareketleri.xlsx';
    
    // Export
    XLSX.writeFile(wb, filename);
}

// Türkçe karakterleri ASCII'ye çevir
function turkishToAscii(text) {
    if (!text) return text;
    var map = {
        'ç': 'c', 'Ç': 'C',
        'ğ': 'g', 'Ğ': 'G',
        'ı': 'i', 'İ': 'I',
        'ö': 'o', 'Ö': 'O',
        'ş': 's', 'Ş': 'S',
        'ü': 'u', 'Ü': 'U'
    };
    return text.replace(/[çÇğĞıİöÖşŞüÜ]/g, function(match) {
        return map[match] || match;
    });
}

// PDF Export
function exportTableToPDF() {
    try {
        // Kütüphanelerin yüklendiğini kontrol et
        if (!window.pdfLibrariesLoaded) {
            alert('PDF kütüphaneleri henüz yükleniyor. Lütfen birkaç saniye bekleyip tekrar deneyin.');
            return;
        }
        
                // jsPDF kontrolü
        var jsPDFConstructor = window.jspdf ? window.jspdf.jsPDF : (typeof jsPDF !== 'undefined' ? jsPDF : null);
        
        if (!jsPDFConstructor) {
            alert('jsPDF yüklenmedi. Lütfen sayfayı yenileyin.');
            console.error('jsPDF not found');
            return;
        }
        
        const doc = new jsPDFConstructor('l', 'mm', 'a4'); // landscape, millimeters, A4
        
        // autoTable kontrolü
        if (typeof doc.autoTable !== 'function') {
            alert('PDF tablo eklentisi yüklenemedi. Lütfen sayfayı yenileyin.');
            console.error('autoTable not available on doc:', typeof doc.autoTable);
            return;
        }
        
        // Türkçe karakter desteği için font ayarı
        doc.setFont('helvetica');
        doc.setLanguage('tr-TR');
    
        // Başlık - Türkçe karakterleri çevir
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text(turkishToAscii('Stok Hareketleri - <?php echo addslashes($tedarikci['firma_adi']); ?>'), 14, 15);
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Tarih: ' + new Date().toLocaleDateString('tr-TR'), 14, 22);
    
    // Tablo verileri
    var table = document.getElementById('myTable');
    
    // Header - Detay kolonu hariç - Türkçe karakterleri çevir
    var headers = [];
    var headerCells = table.querySelectorAll('thead tr th');
    headerCells.forEach(function(cell, index) {
        if (index < headerCells.length - 1) { // Son kolonu (Detay) çıkar
            headers.push(turkishToAscii(cell.textContent.trim()));
        }
    });
    
    // Body - Detay kolonu hariç - Türkçe karakterleri çevir
    var rows = [];
    var bodyRows = table.querySelectorAll('tbody tr');
    bodyRows.forEach(function(row) {
        var rowData = [];
        var cells = row.querySelectorAll('td');
        cells.forEach(function(cell, index) {
            if (index < cells.length - 1) { // Son kolonu (Detay) çıkar
                // Badge ve icon'ları temizle
                var text = cell.textContent.trim();
                text = text.replace(/\s+/g, ' '); // Çoklu boşlukları tek boşluğa çevir
                rowData.push(turkishToAscii(text));
            }
        });
        if (rowData.length > 0) {
            rows.push(rowData);
        }
    });
    
    // Ana tablo - A4 landscape (297mm) için optimize edilmiş genişlikler
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 28,
        styles: { 
            font: 'helvetica',
            fontSize: 6.5, 
            cellPadding: 1.5,
            overflow: 'linebreak',
            cellWidth: 'wrap',
            fontStyle: 'normal'
        },
        headStyles: { 
            font: 'helvetica',
            fillColor: [25, 135, 84], 
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center',
            fontSize: 7
        },
        alternateRowStyles: { fillColor: [245, 245, 245] },
        margin: { top: 28, left: 7, right: 7 },
        tableWidth: 'wrap',
        columnStyles: {
            0: { cellWidth: 7, halign: 'center' },   // # - çok dar
            1: { cellWidth: 21 },   // Tarih
            2: { cellWidth: 14 },   // İşlem
            3: { cellWidth: 26 },   // Stok Kalemi
            4: { cellWidth: 35 },   // Özellikler
            5: { cellWidth: 16 },   // Miktar
            6: { cellWidth: 20 },   // Maliyet/Birim
            7: { cellWidth: 20 },   // Toplam
            8: { cellWidth: 30 },   // İş/Departman
            9: { cellWidth: 22 }    // Personel
        },
        didDrawPage: function(data) {
            // Her sayfanın sonuna sayfa numarası ekle
            var pageNumber = doc.internal.getNumberOfPages();
            doc.setFontSize(8);
            doc.text('Sayfa ' + data.pageNumber + ' / ' + pageNumber, 
                     doc.internal.pageSize.width / 2, 
                     doc.internal.pageSize.height - 10, 
                     { align: 'center' });
        }
    });
    
    // Özet bilgileri ayrı tablo olarak ekle
    var finalY = doc.lastAutoTable.finalY + 10;
    
    // Özet başlık
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('OZET BILGILER', 14, finalY);
    
    // Özet tablosu
    var ozetData = [
        ['Toplam Giris', '<?php echo number_format($toplam_giris ?? 0, 2); ?>'],
        ['Toplam Giris Maliyeti', '<?php echo number_format($toplam_giris_maliyet ?? 0, 2); ?> TL'],
        ['Toplam Cikis (Tuketim)', '<?php echo number_format($toplam_cikis ?? 0, 2); ?>'],
        ['Kalan Stok', '<?php echo number_format(($toplam_giris ?? 0) - ($toplam_cikis ?? 0), 2); ?>']
    ];
    
    doc.autoTable({
        body: ozetData,
        startY: finalY + 5,
        theme: 'grid',
        tableWidth: 120,
        styles: { 
            font: 'helvetica',
            fontSize: 9,
            cellPadding: 3,
            halign: 'left',
            fontStyle: 'normal'
        },
        columnStyles: {
            0: { 
                cellWidth: 65, 
                fontStyle: 'bold',
                fillColor: [240, 240, 240]
            },
            1: { 
                cellWidth: 55,
                halign: 'right',
                fontStyle: 'bold'
            }
        },
        margin: { left: 14, right: 14 }
    });
    
    doc.save('stok_hareketleri_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $tedarikci['firma_adi']); ?>.pdf');
    
    } catch (error) {
        console.error('PDF Export Error:', error);
        alert('PDF oluşturulurken hata oluştu: ' + error.message);
    }
}
</script>

<?php include_once "include/footer.php"; ?>
