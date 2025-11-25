<?php
session_name("PNL");
session_start();

if (!isset($_SESSION["giris_kontrol"])) {
    echo '<div class="alert alert-danger">Oturum geçersiz!</div>';
    exit;
}

require_once "include/db.php";

$tedarikci_id = isset($_POST['tedarikci_id']) ? intval($_POST['tedarikci_id']) : 0;
$fason = isset($_POST['fason']) ? $_POST['fason'] : '';

if ($tedarikci_id == 0) {
    echo '<div class="alert alert-danger">Geçersiz tedarikçi ID!</div>';
    exit;
}

if ($fason == 'evet') {
    // FASON: Planlama kayıtlarını getir
    ?>
    <h6 class="mb-3"><i class="fas fa-cogs"></i> Fason İşler</h6>
    <?php
    
    $sql = "SELECT 
                p.*,
                s.siparis_no,
                s.siparis_tarihi,
                m.ad as musteri_ad,
                m.soyad as musteri_soyad,
                u.urun_adi,
                d.departman
            FROM planlama p
            LEFT JOIN siparisler s ON p.siparis_id = s.id
            LEFT JOIN musteriler m ON s.musteri_id = m.id
            LEFT JOIN urunler u ON s.urun_id = u.id
            LEFT JOIN departmanlar d ON JSON_CONTAINS(p.departmanlar, CAST(d.id AS JSON), '$')
            WHERE p.firma_id = :firma_id
            AND JSON_CONTAINS(
                (SELECT t.departman_idler FROM tedarikciler t WHERE t.id = :tedarikci_id),
                JSON_EXTRACT(p.departmanlar, '$[0]'),
                '$'
            )
            ORDER BY p.id DESC
            LIMIT 50";
    
    $sth = $conn->prepare($sql);
    $sth->bindParam(':firma_id', $_SESSION['firma_id']);
    $sth->bindParam(':tedarikci_id', $tedarikci_id);
    $sth->execute();
    $isler = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($isler)) {
        echo '<div class="alert alert-info">Bu fason için henüz iş kaydı bulunmuyor.</div>';
    } else {
        ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Sipariş No</th>
                        <th>Müşteri</th>
                        <th>Ürün</th>
                        <th>İş Adı</th>
                        <th>Departman</th>
                        <th>Adet</th>
                        <th>Biten</th>
                        <th>Aşama</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($isler as $key => $is) { 
                        $tamamlanma = $is['uretilecek_adet'] > 0 ? 
                            round(($is['biten_urun_adedi'] / $is['uretilecek_adet']) * 100, 1) : 0;
                        
                        $durum_class = 'bg-secondary';
                        if ($tamamlanma == 100) $durum_class = 'bg-success';
                        elseif ($tamamlanma > 50) $durum_class = 'bg-info';
                        elseif ($tamamlanma > 0) $durum_class = 'bg-warning';
                    ?>
                    <tr>
                        <td><?php echo $key + 1; ?></td>
                        <td>
                            <a href="/index.php?url=siparis_detay&id=<?php echo $is['siparis_id']; ?>" 
                               class="badge bg-primary text-decoration-none">
                                <?php echo $is['siparis_no']; ?>
                            </a>
                        </td>
                        <td><?php echo $is['musteri_ad'] . ' ' . $is['musteri_soyad']; ?></td>
                        <td><?php echo $is['urun_adi']; ?></td>
                        <td><?php echo $is['isim']; ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?php echo $is['departman'] ?? 'N/A'; ?>
                            </span>
                        </td>
                        <td><?php echo number_format($is['uretilecek_adet']); ?></td>
                        <td><?php echo number_format($is['biten_urun_adedi']); ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $is['mevcut_asama']; ?>/<?php echo $is['asama_sayisi']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $durum_class; ?>">
                                %<?php echo $tamamlanma; ?>
                            </span>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i> Toplam <strong><?php echo count($isler); ?></strong> iş gösteriliyor.
        </div>
        <?php
    }
    
} else {
    // TEDARİKÇİ: Satın alma kayıtlarını getir (stok hareketleri)
    ?>
    <h6 class="mb-3"><i class="fas fa-shopping-cart"></i> Satın Alınan Ürünler</h6>
    <?php
    
    // Önce tedarikçinin stok kalemlerini al
    $sql = "SELECT stok_kalem_idler FROM tedarikciler WHERE id = :tedarikci_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam(':tedarikci_id', $tedarikci_id);
    $sth->execute();
    $tedarikci = $sth->fetch(PDO::FETCH_ASSOC);
    
    $stok_kalem_idler = !empty($tedarikci['stok_kalem_idler']) ? 
        json_decode($tedarikci['stok_kalem_idler'], true) : [];
    
    if (empty($stok_kalem_idler)) {
        echo '<div class="alert alert-info">Bu tedarikçi için stok kalemi tanımlanmamış.</div>';
    } else {
        // Stok hareketlerini getir
        $stok_kalem_idler_str = implode(',', $stok_kalem_idler);
        
        $sql = "SELECT 
                    sh.*,
                    sk.stok_kalem,
                    ska.stok_adi,
                    p.ad as personel_ad,
                    p.soyad as personel_soyad
                FROM stok_hareketleri sh
                LEFT JOIN stok_kalemleri sk ON sh.stok_kalem_id = sk.id
                LEFT JOIN stok_alt_kalemler ska ON sh.stok_id = ska.id
                LEFT JOIN personeller p ON sh.islem_yapan_id = p.id
                WHERE sh.firma_id = :firma_id
                AND sh.stok_kalem_id IN ({$stok_kalem_idler_str})
                AND sh.islem_turu = 'giris'
                ORDER BY sh.tarih DESC
                LIMIT 100";
        
        $sth = $conn->prepare($sql);
        $sth->bindParam(':firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $hareketler = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($hareketler)) {
            echo '<div class="alert alert-info">Bu tedarikçiden henüz satın alma yapılmamış.</div>';
        } else {
            ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <th>Tarih</th>
                            <th>Stok Kalemi</th>
                            <th>Stok Adı</th>
                            <th>Miktar</th>
                            <th>Birim Fiyat</th>
                            <th>Toplam</th>
                            <th>İşlem Yapan</th>
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $toplam_tutar = 0;
                        foreach ($hareketler as $key => $hareket) { 
                            $tutar = $hareket['miktar'] * $hareket['birim_fiyat'];
                            $toplam_tutar += $tutar;
                        ?>
                        <tr>
                            <td><?php echo $key + 1; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($hareket['tarih'])); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo $hareket['stok_kalem']; ?>
                                </span>
                            </td>
                            <td><?php echo $hareket['stok_adi']; ?></td>
                            <td>
                                <strong><?php echo number_format($hareket['miktar'], 2); ?></strong>
                                <?php echo $hareket['birim']; ?>
                            </td>
                            <td><?php echo number_format($hareket['birim_fiyat'], 2); ?> ₺</td>
                            <td>
                                <strong><?php echo number_format($tutar, 2); ?> ₺</strong>
                            </td>
                            <td>
                                <?php echo $hareket['personel_ad'] . ' ' . $hareket['personel_soyad']; ?>
                            </td>
                            <td>
                                <small><?php echo $hareket['aciklama']; ?></small>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot class="table-success">
                        <tr>
                            <th colspan="6" class="text-end">Toplam Tutar:</th>
                            <th colspan="3">
                                <strong><?php echo number_format($toplam_tutar, 2); ?> ₺</strong>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="alert alert-success mt-3">
                <i class="fas fa-info-circle"></i> Toplam <strong><?php echo count($hareketler); ?></strong> satın alma kaydı gösteriliyor.
            </div>
            <?php
        }
    }
}
?>
