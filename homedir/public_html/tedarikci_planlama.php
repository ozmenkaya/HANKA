<?php 
global $conn;

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

// Departman IDlerini al
$departman_idler_json = isset($tedarikci['departman_idler']) ? $tedarikci['departman_idler'] : null;
$departman_idler = !empty($departman_idler_json) && $departman_idler_json != 'null' ? json_decode($departman_idler_json, true) : [];

if (empty($departman_idler)) {
    $isler = [];
    $departman_hatasi = "Bu tedarikçiye atanmış departman bulunmamaktadır. Lütfen tedarikçi düzenleme sayfasından departman ataması yapınız.";
} else {
    // Planlama kayıtlarını getir - JSON_CONTAINS ile departman kontrolü
    $departman_idler_str = implode(',', $departman_idler);
    
    $sql = "SELECT 
                p.*,
                s.siparis_no,
                s.siparis_tarihi,
                s.isin_adi,
                m.marka as musteri_ad
            FROM planlama p
            LEFT JOIN siparisler s ON p.siparis_id = s.id
            LEFT JOIN musteri m ON s.musteri_id = m.id
            WHERE p.firma_id = :firma_id
            AND (";
    
    // Her departman için JSON_CONTAINS kontrolü ekle
    $conditions = [];
    foreach ($departman_idler as $dept_id) {
        $conditions[] = "JSON_CONTAINS(p.departmanlar, '$dept_id', '$')";
    }
    $sql .= implode(' OR ', $conditions);
    $sql .= ") ORDER BY p.id DESC";
    
    $sth = $conn->prepare($sql);
    $sth->bindParam(':firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $isler = $sth->fetchAll(PDO::FETCH_ASSOC);
}

// Departman isimlerini al
$departmanlar = [];
if (!empty($departman_idler)) {
    $departman_idler_str = implode(',', $departman_idler);
    $sql = "SELECT id, departman FROM departmanlar WHERE id IN({$departman_idler_str})";
    $sth = $conn->prepare($sql);
    $sth->execute();
    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);
    $departman_map = [];
    foreach ($departmanlar as $d) {
        $departman_map[$d['id']] = $d['departman'];
    }
}

?>

<div class="container-fluid">
<div class="row">
    <div class="card mt-2">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fa-solid fa-cogs"></i> Fason İşler - <?php echo htmlspecialchars($tedarikci['firma_adi']); ?>
            </h5>
            <a href="/index.php?url=tedarikci" class="btn btn-light btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Geri Dön
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($departmanlar)): ?>
                <div class="alert alert-info mb-3">
                    <strong>Departmanlar:</strong>
                    <?php foreach ($departmanlar as $dept): ?>
                        <span class="badge bg-secondary ms-1"><?php echo $dept['departman']; ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($departman_hatasi)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <strong>Departman Atama Hatası!</strong><br>
                    <?php echo $departman_hatasi; ?>
                    <hr>
                    <a href="/index.php?url=tedarikci_guncelle&id=<?php echo $tedarikci_id; ?>" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-edit"></i> Tedarikçiyi Düzenle ve Departman Ata
                    </a>
                </div>
            <?php elseif (empty($isler)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Bu fason tedarikçi için henüz iş kaydı bulunmuyor.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="myTable" class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Sipariş No</th>
                                <th>Sipariş Tarihi</th>
                                <th>Müşteri</th>
                                <th>Ürün</th>
                                <th>İş Adı</th>
                                <th>Grup Kodu</th>
                                <th>Üretilecek</th>
                                <th>Biten</th>
                                <th>Teslim</th>
                                <th>Aşama</th>
                                <th>Tamamlanma</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($isler as $key => $is): 
                                $tamamlanma = $is['uretilecek_adet'] > 0 ? 
                                    round(($is['biten_urun_adedi'] / $is['uretilecek_adet']) * 100, 1) : 0;
                                
                                $durum_class = 'bg-secondary';
                                $row_class = '';
                                if ($tamamlanma == 100) {
                                    $durum_class = 'bg-success';
                                    $row_class = 'table-success';
                                } elseif ($tamamlanma > 50) {
                                    $durum_class = 'bg-info';
                                    $row_class = 'table-info';
                                } elseif ($tamamlanma > 0) {
                                    $durum_class = 'bg-warning';
                                    $row_class = 'table-warning';
                                }
                                
                                // Departmanları göster
                                $is_departmanlar = json_decode($is['departmanlar'], true);
                                $dept_names = [];
                                if (!empty($is_departmanlar)) {
                                    foreach ($is_departmanlar as $dept_id) {
                                        if (isset($departman_map[$dept_id])) {
                                            $dept_names[] = $departman_map[$dept_id];
                                        }
                                    }
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo $key + 1; ?></td>
                                <td>
                                    <a href="/index.php?url=siparis_detay&id=<?php echo $is['siparis_id']; ?>" 
                                       class="badge bg-primary text-decoration-none">
                                        <?php echo $is['siparis_no']; ?>
                                    </a>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($is['siparis_tarihi'])); ?></td>
                                <td><?php echo $is['musteri_ad']; ?></td>
                                <td><?php echo $is['isin_adi']; ?></td>
                                <td><strong><?php echo $is['isim']; ?></strong></td>
                                <td>
                                    <code><?php echo $is['grup_kodu']; ?></code>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo number_format($is['uretilecek_adet']); ?></strong>
                                </td>
                                <td class="text-end">
                                    <?php echo number_format($is['biten_urun_adedi']); ?>
                                </td>
                                <td class="text-end">
                                    <?php echo number_format($is['teslim_edilen_urun_adedi']); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">
                                        <?php echo $is['mevcut_asama']; ?> / <?php echo $is['asama_sayisi']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar <?php echo str_replace('bg-', 'bg-', $durum_class); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $tamamlanma; ?>%;" 
                                             aria-valuenow="<?php echo $tamamlanma; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <strong>%<?php echo $tamamlanma; ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="/index.php?url=planlama_detay&id=<?php echo $is['id']; ?>" 
                                       class="btn btn-sm btn-info"
                                       data-bs-toggle="tooltip"
                                       title="Detay">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-success mt-3">
                    <i class="fas fa-info-circle"></i> 
                    Toplam <strong><?php echo count($isler); ?></strong> iş gösteriliyor.
                    
                    <?php 
                    $toplam_uretilecek = array_sum(array_column($isler, 'uretilecek_adet'));
                    $toplam_biten = array_sum(array_column($isler, 'biten_urun_adedi'));
                    $genel_tamamlanma = $toplam_uretilecek > 0 ? round(($toplam_biten / $toplam_uretilecek) * 100, 1) : 0;
                    ?>
                    <br>
                    <strong>Toplam Üretilecek:</strong> <?php echo number_format($toplam_uretilecek); ?> adet |
                    <strong>Biten:</strong> <?php echo number_format($toplam_biten); ?> adet |
                    <strong>Genel Tamamlanma:</strong> <span class="badge bg-primary">%<?php echo $genel_tamamlanma; ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div> <!-- /container-fluid -->
