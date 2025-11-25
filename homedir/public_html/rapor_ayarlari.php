<?php
require_once "include/oturum_kontrol.php";
require_once "include/db.php";

// Firma ID kontrolü
$firma_id = $_SESSION['firma_id'];

// Mevcut rapor ayarlarını getir
$sql = "SELECT * FROM rapor_ayarlari WHERE firma_id = ? ORDER BY sira, id";
$stmt = $conn->prepare($sql);
$stmt->execute([$firma_id]);
$mevcut_ayarlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mevcut tablo_adi'larını array'e al
$mevcut_tablolar = array_column($mevcut_ayarlar, 'tablo_adi');

// Tüm database tablolarını getir
$all_tables_sql = "SHOW TABLES FROM panelhankasys_crm2";
$all_tables_stmt = $conn->query($all_tables_sql);
$all_tables = $all_tables_stmt->fetchAll(PDO::FETCH_COLUMN);

// Sistem tablolarını filtrele (rapor için uygun olmayanlar)
$exclude_tables = ['rapor_sablonlari', 'rapor_ayarlari', 'firmalar', 'personeller', 
                   'yetkiler', 'sayfa_yetkiler', 'sayfalar', 'giris_loglari',
                   'dokumantasyon', 'bildirimler', 'sessions'];

$filtered_tables = array_diff($all_tables, $exclude_tables);
?>

<style>
    .table-row {
        transition: all 0.3s;
    }
    .table-row:hover {
        background-color: #f8f9fa;
    }
    .drag-handle {
        cursor: move;
        padding: 5px;
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: #28a745;
    }
    input:checked + .slider:before {
        transform: translateX(26px);
    }
</style>

<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="mdi mdi-cog"></i> Rapor Ayarları</h1>
            </div>

            <!-- Başarı/Hata Mesajları -->
            <div id="mesajAlani"></div>

            <!-- Açıklama -->
            <div class="alert alert-info">
                <i class="mdi mdi-information"></i> 
                <strong>Bilgi:</strong> Burada raporlama sayfasında hangi tabloların görüneceğini seçebilirsiniz. 
                Aktif olan tablolar rapor oluşturma ekranında veri kaynağı olarak listelenecektir.
            </div>

            <!-- Mevcut Aktif Tablolar -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="mdi mdi-table-check"></i> Aktif Rapor Tabloları</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Tablo Adı</th>
                                    <th>Görünen İsim</th>
                                    <th width="100">Durum</th>
                                    <th width="150">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="aktifTablolar">
                                <?php foreach($mevcut_ayarlar as $index => $ayar): ?>
                                <tr class="table-row" data-id="<?= $ayar['id'] ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><code><?= htmlspecialchars($ayar['tablo_adi']) ?></code></td>
                                    <td>
                                        <input type="text" 
                                               class="form-control form-control-sm label-input" 
                                               value="<?= htmlspecialchars($ayar['tablo_label']) ?>"
                                               data-id="<?= $ayar['id'] ?>">
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" 
                                                   class="aktif-switch" 
                                                   data-id="<?= $ayar['id'] ?>" 
                                                   <?= $ayar['aktif'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success kaydet-btn" data-id="<?= $ayar['id'] ?>">
                                            <i class="mdi mdi-content-save"></i> Kaydet
                                        </button>
                                        <button class="btn btn-sm btn-danger sil-btn" data-id="<?= $ayar['id'] ?>">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Yeni Tablo Ekle -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="mdi mdi-plus-circle"></i> Yeni Tablo Ekle</h5>
                </div>
                <div class="card-body">
                    <form id="yeniTabloForm">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Tablo Seç</label>
                                <select class="form-select" id="yeniTabloAdi" required>
                                    <option value="">-- Tablo Seçin --</option>
                                    <?php foreach($filtered_tables as $table): ?>
                                        <?php if(!in_array($table, $mevcut_tablolar)): ?>
                                        <option value="<?= $table ?>"><?= $table ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Görünen İsim</label>
                                <input type="text" class="form-control" id="yeniTabloLabel" placeholder="Örn: Müşteri Listesi" required>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-success d-block w-100">
                                    <i class="mdi mdi-plus"></i> Ekle
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Aktif/Pasif değiştirme
    $('.aktif-switch').change(function() {
        const id = $(this).data('id');
        const aktif = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            url: 'rapor_ayarlari_db_islem.php',
            type: 'POST',
            dataType: 'json',
            data: {
                islem: 'durum_degistir',
                id: id,
                aktif: aktif
            },
            success: function(response) {
                if(response.durum === 'oturum_hatasi') {
                    alert(response.mesaj);
                    window.location.href = '/login.php?url=' + encodeURIComponent(window.location.pathname + window.location.search);
                    return;
                }
                mesajGoster(response.durum, response.mesaj);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response:', xhr.responseText);
                mesajGoster('hata', 'Bir hata oluştu: ' + error);
            }
        });
    });

    // Label güncelleme
    $('.kaydet-btn').click(function() {
        const id = $(this).data('id');
        const label = $(this).closest('tr').find('.label-input').val();
        
        $.ajax({
            url: 'rapor_ayarlari_db_islem.php',
            type: 'POST',
            dataType: 'json',
            data: {
                islem: 'label_guncelle',
                id: id,
                label: label
            },
            success: function(response) {
                if(response.durum === 'oturum_hatasi') {
                    alert(response.mesaj);
                    window.location.href = '/login.php?url=' + encodeURIComponent(window.location.pathname + window.location.search);
                    return;
                }
                mesajGoster(response.durum, response.mesaj);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response:', xhr.responseText);
                mesajGoster('hata', 'Bir hata oluştu: ' + error);
            }
        });
    });

    // Tablo silme
    $('.sil-btn').click(function() {
        if(!confirm('Bu tabloyu rapor ayarlarından kaldırmak istediğinize emin misiniz?')) {
            return;
        }
        
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        $.ajax({
            url: 'rapor_ayarlari_db_islem.php',
            type: 'POST',
            dataType: 'json',
            data: {
                islem: 'sil',
                id: id
            },
            success: function(response) {
                if(response.durum === 'oturum_hatasi') {
                    alert(response.mesaj);
                    window.location.href = '/login.php?url=' + encodeURIComponent(window.location.pathname + window.location.search);
                    return;
                }
                if(response.durum === 'basarili') {
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
                mesajGoster(response.durum, response.mesaj);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response:', xhr.responseText);
                mesajGoster('hata', 'Bir hata oluştu: ' + error);
            }
        });
    });

    // Yeni tablo ekleme
    $('#yeniTabloForm').submit(function(e) {
        e.preventDefault();
        
        const tabloAdi = $('#yeniTabloAdi').val();
        const tabloLabel = $('#yeniTabloLabel').val();
        
        $.ajax({
            url: 'rapor_ayarlari_db_islem.php',
            type: 'POST',
            dataType: 'json',
            data: {
                islem: 'ekle',
                tablo_adi: tabloAdi,
                tablo_label: tabloLabel
            },
            success: function(response) {
                if(response.durum === 'oturum_hatasi') {
                    alert(response.mesaj);
                    window.location.href = '/login.php?url=' + encodeURIComponent(window.location.pathname + window.location.search);
                    return;
                }
                mesajGoster(response.durum, response.mesaj);
                if(response.durum === 'basarili') {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response:', xhr.responseText);
                mesajGoster('hata', 'Bir hata oluştu: ' + error);
            }
        });
    });

    // Mesaj gösterme fonksiyonu
    function mesajGoster(durum, mesaj) {
        const alertClass = durum === 'basarili' ? 'alert-success' : 'alert-danger';
        const icon = durum === 'basarili' ? 'mdi-check-circle' : 'mdi-alert-circle';
        
        const html = `
            <div class="alert ${alertClass} alert-dismissible fade show">
                <i class="mdi ${icon}"></i> ${mesaj}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('#mesajAlani').html(html);
        
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 3000);
    }
});
</script>
