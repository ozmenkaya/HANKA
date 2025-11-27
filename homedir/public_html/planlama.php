<?php 
    include "include/oturum_kontrol.php";

    // OPTIMIZE: Tek sorguda tüm veriyi LEFT JOIN ile çek (N+1 problemi çözüldü)
    $sth = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, siparisler.termin, 
                            siparisler.fiyat, siparisler.adet,
                            musteri.marka, CONCAT_WS(" ", personeller.ad, personeller.soyad) AS personel_ad_soyad,
                            planlama.planlama_durum, planlama.onay_durum
                            FROM siparisler 
                            JOIN musteri ON siparisler.musteri_id = musteri.id
                            JOIN personeller ON personeller.id  = siparisler.musteri_temsilcisi_id
                            LEFT JOIN planlama ON planlama.siparis_id = siparisler.id AND planlama.firma_id = siparisler.firma_id
                            WHERE siparisler.firma_id = :firma_id 
                            AND onay_baslangic_durum = "evet" 
                            AND islem != "iptal"
                            AND (siparisler.aktif = 1 OR siparisler.aktif IS NULL)
                            ORDER BY siparisler.id DESC
                                                    ');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Sayıları tek döngüde hesapla (çok daha hızlı)
    $islemdeki_siparis_sayisi = 0;
    $planlanmamis_siparis_sayisi = 0;
    $onay_bekleyen_siparis_sayisi = 0;

    foreach ($siparisler as $siparis) {
        if(!isset($siparis['planlama_durum']) || in_array($siparis['planlama_durum'], ['hayır','yarım_kalmıs'])){ 
            $planlanmamis_siparis_sayisi++;
        }elseif(isset($siparis['onay_durum']) && $siparis['onay_durum'] == 'hayır' ){
            $onay_bekleyen_siparis_sayisi++;
        }elseif(isset($siparis['onay_durum']) && $siparis['onay_durum'] == 'evet' ){
            $islemdeki_siparis_sayisi++;
        }
    }

    // Tamamlanan siparişleri çek (BİTENLER sekmesi için)
    $sth_tamamlanan = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, 
                            siparisler.termin, siparisler.fiyat, siparisler.adet,
                            musteri.marka, CONCAT_WS(" ", personeller.ad, personeller.soyad) AS personel_ad_soyad
                            FROM siparisler 
                            JOIN musteri ON siparisler.musteri_id = musteri.id
                            JOIN personeller ON personeller.id = siparisler.musteri_temsilcisi_id
                            WHERE siparisler.firma_id = :firma_id 
                            AND siparisler.islem = "tamamlandi"
                            AND (siparisler.aktif = 1 OR siparisler.aktif IS NULL)
                            ORDER BY siparisler.id DESC
                            LIMIT 100');
    $sth_tamamlanan->bindParam(':firma_id', $_SESSION['firma_id']);
    $sth_tamamlanan->execute();
    $tamamlanan_siparisler = $sth_tamamlanan->fetchAll(PDO::FETCH_ASSOC);
    $tamamlanan_siparis_sayisi = count($tamamlanan_siparisler);
?>
    <div class="row">
        <div class="card mt-2">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-solid fa-list-check"></i> Planlamalar
                </h5>
                <div>
                    <div class="d-flex justify-content-end"> 
                        <div class="btn-group" role="group">
                            <a href="javascript:window.history.back();" 
                                class="btn btn-secondary"
                                data-bs-target="#departman-ekle-modal"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Geri Dön"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <button class="nav-link active position-relative fw-bold" id="nav-tab-onaylanmayan" data-bs-toggle="tab" 
                            data-bs-target="#nav-onaylanmayan" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                            Planlamayı Bekleyen
                            <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-danger fs-6">
                                <?php echo $planlanmamis_siparis_sayisi;?>
                                <span class="visually-hidden">Planlamayı Bekleyen</span>
                            </span>
                        </button>

                        <button class="nav-link position-relative fw-bold" id="nav-tab-onay-bekleyen" data-bs-toggle="tab" 
                            data-bs-target="#nav-onay-bekleyen" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                            Onay Bekleyenler 
                            <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-success fs-6">
                                <?php echo $onay_bekleyen_siparis_sayisi; ?>
                                <span class="visually-hidden">Onay Bekleyenler </span>
                            </span>
                        </button>

                        <button class="nav-link position-relative fw-bold" id="nav-tab-onaylanan" data-bs-toggle="tab" 
                            data-bs-target="#nav-onaylanan" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                            İşlemdekiler
                            <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-info fs-6">
                                <?php echo $islemdeki_siparis_sayisi; ?>
                                <span class="visually-hidden">İşlemdekiler</span>
                            </span>
                        </button>

                        <button class="nav-link position-relative fw-bold" id="nav-tab-bitenler" data-bs-toggle="tab" 
                            data-bs-target="#nav-bitenler" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                            Bitenler
                            <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-secondary fs-6">
                                <?php echo $tamamlanan_siparis_sayisi; ?>
                                <span class="visually-hidden">Bitenler</span>
                            </span>
                        </button>
                    </div>
                </nav>
                <div class="tab-content mt-3" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-onaylanmayan" role="tabpanel" 
                        aria-labelledby="nav-tab-onaylanmayan" tabindex="0">
                        <div class="table-responsive">
                            <table id="myTable" class="table table-hover table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Sipariş No</th>
                                        <th>İşin Adı</th>
                                        <th>Müşteri</th>
                                        <th>Müşteri Temsilcisi</th>
                                        <th>Termin</th>
                                        <th class="text-end">Adet</th>
                                        <th class="text-end">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sira = 0; ?>
                                    <?php foreach ($siparisler  as $siparis) { ?>
                                        <?php 
                                            // OPTIMIZE: Artık sorgu yok, veri zaten $siparis dizisinde
                                        ?>
                                        <?php if(!isset($siparis['planlama_durum']) || in_array($siparis['planlama_durum'], ['hayır','yarım_kalmıs'])  ){ ?>
                                            <tr>
                                                <th class="table-primary"><?php echo ++$sira;?></th>
                                                <th class="table-secondary"><?php echo $siparis['siparis_no'];?></th>
                                                <td><?php echo $siparis['isin_adi']; ?></td>
                                                <td><?php echo $siparis['marka']; ?></td>
                                                <td><?php echo $siparis['personel_ad_soyad']; ?></td>
                                                <td><?php echo date('d-m-Y',strtotime($siparis['termin'])); ?></td>
                                                <td class="text-end">
                                                    <?php echo number_format($siparis['adet'],0,'',','); ?> Adet
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-end"> 
                                                        <div class="btn-group" role="group">
                                                            <?php if(in_array(PLANLAMA, $_SESSION['sayfa_idler']) && 
                                                                (!isset($siparis['planlama_durum']) || $siparis['planlama_durum'] == 'hayır')){  ?>
                                                                <a href="/index.php?url=planla_siparis&siparis_id=<?php echo $siparis['id']; ?>" 
                                                                    class="btn btn-sm btn-success"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-placement="bottom" 
                                                                    data-bs-title="Planla"
                                                                >
                                                                    <i class="fa-solid fa-list-check"></i>
                                                                </a>
                                                            <?php }else if(in_array(PLANLAMA, $_SESSION['sayfa_idler']) && 
                                                                    (!isset($siparis['planlama_durum']) || $siparis['planlama_durum'] == 'yarım_kalmıs' ) ){ ?> 
                                                                <a href="/index.php?url=planla_siparis_duzenle&siparis_id=<?php echo $siparis['id']; ?>" 
                                                                    class="btn btn-sm btn-warning" 
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-placement="bottom" 
                                                                    data-bs-title="Güncelle"
                                                                >
                                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                                </a>
                                                            <?php }?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php }?>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-onay-bekleyen" role="tabpanel" 
                        aria-labelledby="nav-tab-onaylanan" tabindex="1">
                        <div class="table-responsive">
                            <table id="myTable" class="table table-hover table-striped" >
                                <thead class="table-primary">
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Sipariş No</th>
                                        <th>Müşteri</th>
                                        <th>İşin Adı</th>
                                        <th>Müşteri Temsilcisi</th>
                                        <th>Termin</th>
                                        <th class="text-end">Adet</th>
                                        <th class="text-end">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sira = 0; ?>
                                    <?php foreach ($siparisler  as $siparis) { ?>
                                        <?php 
                                            // OPTIMIZE: Veri zaten $siparis dizisinde
                                        ?>
                                        <?php if(isset($siparis['onay_durum']) && $siparis['onay_durum'] == 'hayır' 
                                                && $siparis['planlama_durum'] == 'evet'){ ?>
                                            <tr>
                                                <th class="table-primary"><?php echo ++$sira; ?></th>
                                                <th class="table-secondary"><?php echo $siparis['siparis_no'];?></th>
                                                <td><?php echo $siparis['marka']; ?></td>
                                                <td><?php echo $siparis['isin_adi']; ?></td>
                                                <td><?php echo $siparis['personel_ad_soyad']; ?></td>
                                                <td><?php echo date('d-m-Y',strtotime($siparis['termin'])); ?></td>
                                                <td class="text-end">
                                                    <?php echo number_format($siparis['adet'],0,'',','); ?> Adet
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end"> 
                                                        <div class="btn-group" role="group" aria-label="Basic example">
                                                            <a href="/index.php?url=planlama_db_islem&islem=planlama-pdf&siparis_id=<?php echo $siparis['id']; ?>" 
                                                                class="btn btn-secondary" 
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom" 
                                                                data-bs-title="Planlama PDF"
                                                                id="savePlanlamaPdf"
                                                                target="_blank"
                                                            >
                                                                <i class="fa-regular fa-file-pdf"></i>
                                                            </a>
                                                            <?php  if(in_array(PLANLAMA, $_SESSION['sayfa_idler'])){   ?>
                                                                <a href="/index.php?url=planla_siparis_duzenle&siparis_id=<?php echo $siparis['id']; ?>" 
                                                                    class="btn btn-success" name="siparis_guncelle"
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-placement="bottom" 
                                                                    data-bs-title="Planlamayı Onayla"
                                                                >
                                                                    <i class="fa-regular fa-circle-check"></i>
                                                                </a>
                                                            <?php } ?>  
                                                            <a href="/index.php?url=planla_siparis_duzenle&siparis_id=<?php echo $siparis['id']; ?>" 
                                                                class="btn btn-warning" 
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom" 
                                                                data-bs-title="Güncelle"
                                                            >
                                                                <i class="fa-regular fa-pen-to-square"></i>
                                                            </a>  
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php }?>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-onaylanan" role="tabpanel" 
                        aria-labelledby="nav-tab-onaylanan" tabindex="1">
                        <div class="table-responsive">
                            <table id="myTable" class="table table-hover table-striped" >
                                <thead class="table-primary">
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Sipariş No</th>
                                        <th>Müşteri</th>
                                        <th>İşin Adı</th>
                                        <th>Müşteri Temsilcisi</th>
                                        <th>Termin</th>
                                        <th class="text-end">Adet</th>
                                        <th class="text-end">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sira = 0; ?>
                                    <?php foreach ($siparisler  as $siparis) { ?>
                                        <?php 
                                            // OPTIMIZE: Veri zaten $siparis dizisinde
                                        ?>
                                        <?php if(isset($siparis['onay_durum']) && $siparis['onay_durum'] == 'evet'){ ?>
                                            <tr>
                                                <th class="table-primary"><?php echo ++$sira;?></th>
                                                <th class="table-secondary"><?php echo $siparis['siparis_no'];?></th>
                                                <td><?php echo $siparis['marka']; ?></td>
                                                <td><?php echo $siparis['isin_adi']; ?></td>
                                                <td><?php echo $siparis['personel_ad_soyad']; ?></td>
                                                <td><?php echo date('d-m-Y',strtotime($siparis['termin'])); ?></td>
                                                <td class="text-end">
                                                    <?php echo number_format($siparis['adet'],0,'',','); ?> Adet
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end"> 
                                                        <div class="btn-group" role="group">
                                                            <a href="/index.php?url=planlama_db_islem&islem=planlama-pdf&siparis_id=<?php echo $siparis['id']; ?>" 
                                                                class="btn btn-secondary" 
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom" 
                                                                data-bs-title="Planlama PDF"
                                                                target="_blank"
                                                            >
                                                                <i class="fa-regular fa-file-pdf"></i>
                                                            </a>
                                                            <?php //if(isset($_SESSION['sayfa_yetki_46']) && $_SESSION['sayfa_yetki_46'] == 1){  ?>
                                                                <a href="/index.php?url=planla_siparis_duzenle&siparis_id=<?php echo $siparis['id']; ?>" class="btn btn-warning" 
                                                                    name="siparis_guncelle"
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-placement="bottom" 
                                                                    data-bs-title="Planı Düzenle"
                                                                >
                                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                                </a>
                                                            <?php //}?>    
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php }?>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-bitenler" role="tabpanel" 
                        aria-labelledby="nav-tab-bitenler" tabindex="2">
                        <div class="table-responsive">
                            <table id="bitenlerTable" class="table table-hover table-striped" >
                                <thead class="table-primary">
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Sipariş No</th>
                                        <th>Müşteri</th>
                                        <th>İşin Adı</th>
                                        <th>Müşteri Temsilcisi</th>
                                        <th>Termin</th>
                                        <th class="text-end">Adet</th>
                                        <th class="text-end">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sira = 0; ?>
                                    <?php foreach ($tamamlanan_siparisler  as $siparis) { ?>
                                        <tr>
                                            <th class="table-primary"><?php echo ++$sira;?></th>
                                            <th class="table-secondary"><?php echo $siparis['siparis_no'];?></th>
                                            <td><?php echo $siparis['marka']; ?></td>
                                            <td><?php echo $siparis['isin_adi']; ?></td>
                                            <td><?php echo $siparis['personel_ad_soyad']; ?></td>
                                            <td><?php echo date('d-m-Y',strtotime($siparis['termin'])); ?></td>
                                            <td class="text-end">
                                                <?php echo number_format($siparis['adet'],0,'',','); ?> Adet
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end"> 
                                                    <div class="btn-group" role="group">
                                                        <a href="/index.php?url=planlama_db_islem&islem=planlama-pdf&siparis_id=<?php echo $siparis['id']; ?>" 
                                                            class="btn btn-secondary" 
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="Planlama PDF"
                                                            target="_blank"
                                                        >
                                                            <i class="fa-regular fa-file-pdf"></i>
                                                        </a>
                                                        <a href="/index.php?url=planla_siparis_duzenle&siparis_id=<?php echo $siparis['id']; ?>" 
                                                            class="btn btn-info" 
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="Planlamayı Görüntüle"
                                                        >
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
        
    </div>
    <?php include_once "include/uyari_session_oldur.php"; ?>

<script>
$(document).ready(function() {
    // ============================================
    // CANLI VERİ GÜNCELLEMESİ (Auto-Refresh)
    // ============================================
    let dataHash = '';
    let isRefreshing = false;
    let refreshIndicator = null;
    
    // Yenileme göstergesi
    function showRefreshIndicator() {
        if (!refreshIndicator) {
            refreshIndicator = $('<div>')
                .attr('id', 'refresh-indicator')
                .css({
                    position: 'fixed',
                    bottom: '20px',
                    right: '20px',
                    background: '#17a2b8',
                    color: 'white',
                    padding: '10px 20px',
                    borderRadius: '50px',
                    fontSize: '12px',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                    zIndex: 9998,
                    display: 'flex',
                    alignItems: 'center',
                    gap: '10px'
                })
                .html('<i class="fa fa-sync fa-spin"></i> Canlı Veri Aktif')
                .appendTo('body');
        }
    }
    
    // Toast bildirim fonksiyonu
    function showToast(message, type) {
        const bgColor = type === 'success' ? '#28a745' : '#dc3545';
        const icon = type === 'success' ? '✓' : '✕';
        
        const toast = $('<div>')
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: bgColor,
                color: 'white',
                padding: '15px 25px',
                borderRadius: '5px',
                zIndex: 9999,
                boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                fontSize: '14px',
                fontWeight: 'bold'
            })
            .html(icon + ' ' + message)
            .appendTo('body');
        
        setTimeout(() => toast.fadeOut(300, () => toast.remove()), 2000);
    }
    
    function checkForUpdates() {
        if (isRefreshing) return;
        
        isRefreshing = true;
        
        $.ajax({
            url: '/index.php?url=planlama_data',
            type: 'GET',
            data: {
                firma_id: <?php echo $_SESSION['firma_id']; ?>,
                hash: dataHash
            },
            dataType: 'json',
            success: function(response) {
                console.log('📊 API Response:', response); // DEBUG
                
                if (response.success) {
                    // İlk yüklemede hash'i kaydet
                    if (dataHash === '') {
                        dataHash = response.hash;
                        console.log('✓ Planlama canlı veri takibi başlatıldı (5 sn aralık)');
                        console.log('📌 İlk Hash:', dataHash);
                        showRefreshIndicator();
                    } 
                    // Değişiklik varsa yenile
                    else if (response.has_changes) {
                        console.log('🔄 Hash değişti! Eski:', dataHash, 'Yeni:', response.hash);
                        console.log('🔄 Planlama verisinde değişiklik, sayfa yenileniyor...');
                        showToast('Yeni veri güncelleniyor...', 'success');
                        setTimeout(() => location.reload(), 500);
                        return;
                    } else {
                        console.log('✓ Hash aynı, değişiklik yok');
                    }
                    
                    dataHash = response.hash;
                }
                isRefreshing = false;
            },
            error: function(xhr, status, error) {
                console.error('Canlı veri kontrolü hatası:', error);
                isRefreshing = false;
            }
        });
    }
    
    // İlk kontrolü hemen yap
    checkForUpdates();
    
    // 5 saniyede bir kontrol et (test için hızlandırıldı)
    setInterval(checkForUpdates, 5000);
    
    // Sayfa görünür olduğunda kontrol et
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('Sayfa aktif, planlama verisi kontrol ediliyor...');
            checkForUpdates();
        }
    });
    
    // ============================================
    // DATATABLE İNİT (Bitenler Arama Özelliği)
    // ============================================
    $('#bitenlerTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json"
        },
        "pageLength": 25,
        "order": [[0, 'desc']]  // En son tamamlanandan başla
    });
});
</script>
