<?php
require_once "include/oturum_kontrol.php";

// Kayıtlı raporları çek
$sql = "SELECT * FROM rapor_sablonlari WHERE firma_id = :firma_id ORDER BY olusturma_tarihi DESC";
$sth = $conn->prepare($sql);
$sth->bindParam('firma_id', $_SESSION['firma_id']);
$sth->execute();
$raporlar = $sth->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-file-excel"></i> Excel Raporları
                    </h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yeniRaporModal">
                        <i class="fa-solid fa-plus"></i> Yeni Rapor Şablonu
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Rapor Adı</th>
                                    <th>Veri Kaynağı</th>
                                    <th>Sütunlar</th>
                                    <th>Oluşturma Tarihi</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($raporlar)){ ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="fa-solid fa-inbox fa-3x mb-2"></i>
                                            <p>Henüz rapor şablonu oluşturulmamış</p>
                                        </td>
                                    </tr>
                                <?php } else { ?>
                                    <?php foreach($raporlar as $rapor){ ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($rapor['rapor_adi']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php 
                                                    $kaynaklar = [
                                                        'uretim' => 'Üretim Verileri',
                                                        'siparisler' => 'Siparişler',
                                                        'planlama' => 'Planlama',
                                                        'makinalar' => 'Makinalar',
                                                        'personel' => 'Personel',
                                                        'stok' => 'Stok'
                                                    ];
                                                    echo $kaynaklar[$rapor['veri_kaynagi']] ?? $rapor['veri_kaynagi'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $sutunlar = json_decode($rapor['sutunlar'], true);
                                                echo count($sutunlar) . ' sütun';
                                                ?>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($rapor['olusturma_tarihi'])); ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-success btn-sm rapor-indir" 
                                                    data-rapor-id="<?php echo $rapor['id']; ?>">
                                                    <i class="fa-solid fa-download"></i> İndir
                                                </button>
                                                <button class="btn btn-danger btn-sm rapor-sil" 
                                                    data-rapor-id="<?php echo $rapor['id']; ?>">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Rapor Şablonu Modal -->
<div class="modal fade" id="yeniRaporModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-excel"></i> Yeni Rapor Şablonu Oluştur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="raporForm">
                    <div class="mb-3">
                        <label class="form-label">Rapor Adı</label>
                        <input type="text" class="form-control" name="rapor_adi" required 
                            placeholder="Örn: Aylık Üretim Raporu">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Veri Kaynağı</label>
                        <select class="form-select" name="veri_kaynagi" id="veriKaynagi" required>
                            <option value="">Seçiniz...</option>
                            <option value="uretim">Üretim Verileri</option>
                            <option value="siparisler">Siparişler</option>
                            <option value="planlama">Planlama</option>
                            <option value="makinalar">Makinalar</option>
                            <option value="personel">Personel Performansı</option>
                            <option value="stok">Stok Hareketleri</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excel'de Görünecek Sütunlar</label>
                        <div id="sutunlarContainer" class="border rounded p-3">
                            <p class="text-muted">Önce veri kaynağı seçiniz...</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="raporKaydet">
                    <i class="fa-solid fa-save"></i> Rapor Şablonunu Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rapor İndir Modal -->
<div class="modal fade" id="raporIndirModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-calendar-days"></i> Tarih Aralığı Seç
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="indirForm">
                    <input type="hidden" name="rapor_id" id="indirRaporId">
                    
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="baslangic_tarihi" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="bitis_tarihi" required>
                    </div>

                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle"></i>
                        Seçili tarih aralığındaki veriler Excel dosyası olarak indirilecek
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" id="excelIndir">
                    <i class="fa-solid fa-file-excel"></i> Excel İndir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Veri kaynağına göre sütunları getir
const sutunTanimlari = {
    uretim: [
        {key: 'tarih', label: 'Tarih'},
        {key: 'siparis_no', label: 'Sipariş No'},
        {key: 'urun_adi', label: 'Ürün Adı'},
        {key: 'makina_adi', label: 'Makina'},
        {key: 'personel', label: 'Personel'},
        {key: 'uretilen_adet', label: 'Üretilen Adet'},
        {key: 'fire_adet', label: 'Fire Adet'},
        {key: 'baslatma_tarih', label: 'Başlatma Tarihi'},
        {key: 'bitis_tarih', label: 'Bitiş Tarihi'},
        {key: 'firma', label: 'Firma'},
        {key: 'departman', label: 'Departman'},
        {key: 'durum_text', label: 'Durum'}
    ],
    siparisler: [
        {key: 'siparis_no', label: 'Sipariş No'},
        {key: 'musteri', label: 'Müşteri'},
        {key: 'isin_adi', label: 'İşin Adı'},
        {key: 'adet', label: 'Adet'},
        {key: 'termin', label: 'Termin Tarihi'},
        {key: 'durum', label: 'Durum'},
        {key: 'olusturma_tarihi', label: 'Oluşturma Tarihi'},
        {key: 'firma', label: 'Firma'},
        {key: 'olusturan_personel', label: 'Oluşturan'},
        {key: 'paketleme', label: 'Paketleme'},
        {key: 'aciklama', label: 'Açıklama'}
    ],
    planlama: [
        {key: 'siparis_no', label: 'Sipariş No'},
        {key: 'musteri', label: 'Müşteri'},
        {key: 'urun', label: 'Ürün'},
        {key: 'adet', label: 'Adet'},
        {key: 'asama', label: 'Aşama (Mevcut/Toplam)'},
        {key: 'durum', label: 'Durum'},
        {key: 'termin', label: 'Termin'},
        {key: 'firma', label: 'Firma'},
        {key: 'mevcut_departman', label: 'Mevcut Departman'},
        {key: 'planlama_tarihi', label: 'Planlama Tarihi'}
    ],
    makinalar: [
        {key: 'makina_adi', label: 'Makina Adı'},
        {key: 'makina_modeli', label: 'Model'},
        {key: 'durum', label: 'Durum'},
        {key: 'departman', label: 'Departman'},
        {key: 'firma', label: 'Firma'},
        {key: 'toplam_is', label: 'Toplam İş'},
        {key: 'tamamlanan_is', label: 'Tamamlanan İş'},
        {key: 'toplam_uretilen', label: 'Toplam Üretim'},
        {key: 'verimlilik', label: 'Verimlilik %'}
    ],
    personel: [
        {key: 'personel', label: 'Personel'},
        {key: 'email', label: 'Email'},
        {key: 'yetki', label: 'Yetki'},
        {key: 'departman', label: 'Departman'},
        {key: 'firma', label: 'Firma'},
        {key: 'makinalar', label: 'Çalıştığı Makinalar'},
        {key: 'toplam_is', label: 'Toplam İş'},
        {key: 'tamamlanan_is', label: 'Tamamlanan'},
        {key: 'uretilen_adet', label: 'Üretilen Adet'},
        {key: 'fire_adet', label: 'Fire Adet'},
        {key: 'verimlilik', label: 'Verimlilik %'}
    ],
    stok: [
        {key: 'stok_adi', label: 'Stok Adı'},
        {key: 'stok_kodu', label: 'Stok Kodu'},
        {key: 'kategori', label: 'Kategori'},
        {key: 'hareket_tipi', label: 'Hareket Tipi'},
        {key: 'miktar', label: 'Miktar'},
        {key: 'birim', label: 'Birim'},
        {key: 'tarih', label: 'Tarih'},
        {key: 'aciklama', label: 'Açıklama'},
        {key: 'islem_yapan', label: 'İşlem Yapan'},
        {key: 'firma', label: 'Firma'},
        {key: 'siparis_no', label: 'Sipariş No'},
        {key: 'makina_adi', label: 'Makina'}
    ]
};

$('#veriKaynagi').change(function(){
    const kaynak = $(this).val();
    if(!kaynak) {
        $('#sutunlarContainer').html('<p class="text-muted">Önce veri kaynağı seçiniz...</p>');
        return;
    }
    
    const sutunlar = sutunTanimlari[kaynak];
    let html = '<p class="mb-2"><strong>Seçmek istediğiniz sütunları işaretleyin:</strong></p>';
    
    sutunlar.forEach(sutun => {
        html += `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="sutunlar[]" 
                    value="${sutun.key}" id="sutun_${sutun.key}" checked>
                <label class="form-check-label" for="sutun_${sutun.key}">
                    ${sutun.label}
                </label>
            </div>
        `;
    });
    
    $('#sutunlarContainer').html(html);
});

// Rapor kaydet
$('#raporKaydet').click(function(){
    const formData = new FormData($('#raporForm')[0]);
    
    // Seçili sütunları topla
    const seciliSutunlar = [];
    $('input[name="sutunlar[]"]:checked').each(function(){
        const key = $(this).val();
        const kaynak = $('#veriKaynagi').val();
        const sutun = sutunTanimlari[kaynak].find(s => s.key === key);
        seciliSutunlar.push(sutun);
    });
    
    if(seciliSutunlar.length === 0) {
        alert('En az bir sütun seçmelisiniz!');
        return;
    }
    
    formData.append('sutunlar', JSON.stringify(seciliSutunlar));
    formData.append('islem', 'rapor-kaydet');
    
    $.ajax({
        url: '/index.php?url=raporlar_db_islem',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response){
            if(response.success) {
                alert('Rapor şablonu kaydedildi!');
                location.reload();
            } else {
                alert('Hata: ' + response.message);
            }
        }
    });
});

// Rapor indir
$(document).on('click', '.rapor-indir', function(){
    const raporId = $(this).data('rapor-id');
    $('#indirRaporId').val(raporId);
    $('#raporIndirModal').modal('show');
});

$('#excelIndir').click(function(){
    const formData = $('#indirForm').serialize();
    window.location.href = '/index.php?url=rapor_excel&' + formData;
});

// Rapor sil
$(document).on('click', '.rapor-sil', function(){
    if(!confirm('Bu rapor şablonunu silmek istediğinize emin misiniz?')) return;
    
    const raporId = $(this).data('rapor-id');
    $.post('/index.php?url=raporlar_db_islem', {
        islem: 'rapor-sil',
        rapor_id: raporId
    }, function(response){
        if(response.success) {
            alert('Rapor silindi');
            location.reload();
        }
    }, 'json');
});
</script>
