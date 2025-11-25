<?php 
require_once 'include/oturum_kontrol.php';

// Sadece admin yetkisi olanlar görebilir
if(!in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID, ADMIN_YETKI_ID])) {
    header('Location: /index.php?url=anasayfa');
    exit;
}

require_once 'include/header.php';
require_once 'include/sol_menu.php';

// Veritabanı bilgileri
$backup_dir = '/var/backups/db';

// Mevcut yedekleri listele
$backups = [];
if(is_dir($backup_dir)) {
    $files = glob($backup_dir . '/*.sql');
    rsort($files); // En yeni önce
    foreach($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file)),
            'path' => $file
        ];
    }
}
?>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            
            <!-- Başlık -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">
                            <i class="mdi mdi-database"></i> Veritabanı Yedekleme
                        </h4>
                    </div>
                </div>
            </div>

            <!-- Yedekleme İşlemleri -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="header-title">Yedekleme İşlemleri</h4>
                                <button class="btn btn-success" onclick="yeniYedekOlustur()">
                                    <i class="mdi mdi-plus-circle"></i> Yeni Yedek Oluştur
                                </button>
                            </div>

                            <div class="alert alert-info">
                                <i class="mdi mdi-information"></i>
                                <strong>Bilgi:</strong> Yedekler <code><?php echo $backup_dir; ?></code> klasöründe saklanmaktadır.
                            </div>

                            <?php if(empty($backups)): ?>
                                <div class="alert alert-warning">
                                    <i class="mdi mdi-alert"></i> Henüz yedek dosyası bulunamadı.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-centered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Dosya Adı</th>
                                                <th>Boyut</th>
                                                <th>Tarih</th>
                                                <th class="text-end">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($backups as $backup): ?>
                                                <tr>
                                                    <td>
                                                        <i class="mdi mdi-database text-primary"></i>
                                                        <?php echo $backup['name']; ?>
                                                    </td>
                                                    <td><?php echo number_format($backup['size'] / 1024 / 1024, 2); ?> MB</td>
                                                    <td><?php echo $backup['date']; ?></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-info" onclick="indirYedek('<?php echo $backup['name']; ?>')">
                                                            <i class="mdi mdi-download"></i> İndir
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="silYedek('<?php echo $backup['name']; ?>')">
                                                            <i class="mdi mdi-delete"></i> Sil
                                                        </button>
                                                    </td>
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

            <!-- Otomatik Yedekleme Ayarları -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Otomatik Yedekleme Ayarları</h4>
                            
                            <div class="alert alert-secondary">
                                <i class="mdi mdi-clock-outline"></i>
                                <strong>Cron Job:</strong> Otomatik yedekleme için cron job ayarlanmalıdır.
                                <br><br>
                                <code>0 2 * * * /usr/bin/php /var/www/html/yedekleme_cron.php</code>
                                <br><small class="text-muted">Her gün saat 02:00'da otomatik yedek alır</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function yeniYedekOlustur() {
    if(!confirm('Yeni veritabanı yedeği oluşturulsun mu?')) {
        return;
    }
    
    $.notify('Yedek oluşturuluyor...', 'info');
    
    $.ajax({
        url: '/index.php?url=yedekleme_islem',
        type: 'POST',
        data: { islem: 'olustur' },
        dataType: 'json',
        success: function(data) {
            if(data.success) {
                $.notify('Yedek başarıyla oluşturuldu!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                $.notify('Hata: ' + data.message, 'error');
            }
        },
        error: function() {
            $.notify('Bağlantı hatası!', 'error');
        }
    });
}

function indirYedek(dosya) {
    window.location.href = '/index.php?url=yedekleme_islem&islem=indir&dosya=' + dosya;
}

function silYedek(dosya) {
    if(!confirm('"' + dosya + '" dosyası silinsin mi?')) {
        return;
    }
    
    $.ajax({
        url: '/index.php?url=yedekleme_islem',
        type: 'POST',
        data: { islem: 'sil', dosya: dosya },
        dataType: 'json',
        success: function(data) {
            if(data.success) {
                $.notify('Yedek silindi!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                $.notify('Hata: ' + data.message, 'error');
            }
        },
        error: function() {
            $.notify('Bağlantı hatası!', 'error');
        }
    });
}
</script>

<?php require_once 'include/footer.php'; ?>
