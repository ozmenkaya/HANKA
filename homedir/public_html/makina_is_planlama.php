<?php
    require_once "include/oturum_kontrol.php";

    if(!in_array(MAKINA_IS_PLANI, $_SESSION['sayfa_idler'])){
        include_once "include/yetkisiz.php"; exit;
    }
    
    $sth = $conn->prepare('SELECT * FROM departmanlar WHERE firma_id=:firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);


    #echo "<pre>"; print_r($departmanlar); exit;
?>
<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-list-check"></i> Makine İş Planlama
            </h5>
            <div>
                <div class="d-md-flex justify-content-end"> 
                    <span class="badge bg-success fs-6 me-2" id="realtime-durum">
                        <i class="fa-solid fa-circle-dot"></i> Anlık Veri
                    </span>
                    <div class="btn-group" role="group" aria-label="Basic example">
                        <a href="javascript:window.history.back();" 
                            class="btn btn-secondary"
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
                    <?php foreach ($departmanlar as $index => $departman) { ?>
                        <button  class="nav-link fw-bold <?php echo $index == 0 ? 'active':''; ?>" 
                            id="nav-tab-<?php echo $index;?>" data-bs-toggle="tab" 
                            data-bs-target="#nav-<?php echo $index;?>" type="button" role="tab" 
                            aria-controls="nav-profile" aria-selected="false"
                        >
                            <?php echo ($index+1).'- '.$departman['departman']; ?>
                        </button>
                    <?php }?>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <?php $makina_sayisi = 0; ?>
                <?php foreach ($departmanlar as $index => $departman) { ?>
                    <div class="tab-pane fade <?php echo $index == 0 ? 'show active':''; ?>" 
                            id="nav-<?php echo $index; ?>" role="tabpanel" 
                            aria-labelledby="nav-tab-<?php echo $index; ?>" tabindex="0"
                        >
                        <?php 
                            $sql = "SELECT * FROM `makinalar`  
                                WHERE firma_id = :firma_id AND departman_id = :departman_id AND durumu IN('aktif', 'bakımda')";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->bindParam('departman_id', $departman['id']);
                            $sth->execute();
                            $departman_makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                            //echo "<pre>"; print_r($departman_makinalar);

                            $sql = "SELECT planlama.id, planlama.mevcut_asama, planlama.departmanlar,planlama.makinalar,
                                    planlama.adetler,planlama.isim,planlama.sureler,planlama.durum,
                                    siparisler.isin_adi,`siparisler`.`siparis_no`,
                                    `musteri`.`marka`
                                    FROM siparisler
                                    JOIN musteri ON musteri.id = siparisler.musteri_id 
                                    JOIN `planlama` ON  planlama.siparis_id = siparisler.id
                                    WHERE planlama.onay_durum = 'evet'  AND planlama.firma_id = :firma_id 
                                    AND planlama.durum IN('baslamadi','beklemede', 'basladi')
                                    AND  planlama.aktar_durum = 'orijinal'
                                    ORDER BY planlama.sira
                                    ";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $isler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="row">
                            <?php foreach ($departman_makinalar as  $departman_index => $departman_makina) { ?>
                                <?php 
                                    $departman_id   = $departman['id'];
                                    $makina_id      = $departman_makina['id'];
                                    $mevcut_is_str  = '';
                                    foreach ($isler as   $is) { 
                                        $is_asama           = $is['mevcut_asama'];
                                        $is_departmanlar    = json_decode($is['departmanlar'],true);
                                        $is_departman       = isset($is_departmanlar[$is_asama]) ? $is_departmanlar[$is_asama] : 0;  
                                        $is_makinalar       = json_decode($is['makinalar'],true); 
                                        $is_makina          = isset($is_makinalar[$is_asama]) ? $is_makinalar[$is_asama] : 0; 

                                        if($is['durum'] == 'basladi' && $departman_id == $is_departman && $makina_id == $is_makina){ 
                                            $mevcut_is_str = $is['isin_adi'].'/'.$is['isim'];
                                            break;
                                        } 
                                    } 
                                    
                                ?>
                                <div class="col-md-12 d-flex">
                                    <div class="card mb-2 flex-fill <?php echo $departman_makina['durumu'] == 'aktif'  ? 'border-primary':'border-danger' ?>">
                                        <div class="card-header border-primary d-flex justify-content-between">
                                            <h5 class="col-md-4">
                                                <i class="fa-solid fa-gears"></i>
                                                <?php echo $departman_makina['makina_adi'].' '.$departman_makina['makina_modeli']; ?>
                                            </h5>
                                            <h5 class="col-md-8">
                                                <?php if($departman_makina['durumu'] == 'bakimda'){ ?>
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                <?php } ?>

                                                <span class="text-danger fw-bold" 
                                                    data-realtime="mevcut-is" 
                                                    data-departman-id="<?php echo $departman_id; ?>" 
                                                    data-makina-id="<?php echo $makina_id; ?>"
                                                ><?php echo $mevcut_is_str; ?></span>
                                            </h5>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-striped">
                                                    <thead class="table-primary">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>F. Adı</th>
                                                            <th>Şipariş No</th>
                                                            <th>İ. Adı</th>
                                                            <th>İsim</th>
                                                            <th class="text-end">Adet</th>
                                                            <th class="text-end">Süre</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="sortable-<?php echo ++$makina_sayisi; ?>">
                                                        <?php $index = 0; ?>
                                                        <?php foreach ($isler as   $is) { ?>
                                                            <?php if($is['durum'] == 'basladi'){ continue; }?>
                                                            <?php 
                                                                $is_asama           = $is['mevcut_asama'];
                                                                $is_departmanlar    = json_decode($is['departmanlar'],true);
                                                                $is_makinalar       = json_decode($is['makinalar'],true);
                                                                $is_adetler         = json_decode($is['adetler'],true);
                                                                $is_sureler         = json_decode($is['sureler'],true);
                                                                $is_departman       = isset($is_departmanlar[$is_asama]) ? $is_departmanlar[$is_asama] : 0;   
                                                                $is_makina          = isset($is_makinalar[$is_asama]) ? $is_makinalar[$is_asama] : 0;    
                                                                $is_adet            = isset($is_adetler[$is_asama]) ? $is_adetler[$is_asama] : 0;    
                                                                $is_sure            = isset($is_sureler[$is_asama]) ? $is_sureler[$is_asama] : 0;    
                                                            ?>
                                                            <?php if($departman_id == $is_departman && $makina_id == $is_makina){ ?>
                                                                <tr data-planlama-id="<?php echo $is['id']; ?>">
                                                                    <th class="table-primary"><?php echo ++$index ;?></th>
                                                                    <td><?php echo $is['marka']; ?></td>
                                                                    <td><?php echo $is['siparis_no']; ?></td>
                                                                    <td><?php echo $is['isin_adi']; ?></td>
                                                                    <td><?php echo $is['isim'];?></td>
                                                                    <td class="text-end fw-bold"><?php echo number_format($is_adet,0,'',','); ?></td>
                                                                    <td class="text-end"><?php echo $is_sure;?> Saat</td>
                                                                </tr>
                                                            <?php } ?>
                                                        <?php }?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        </div>

                        <?php if(empty($departman_makinalar)){ ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5>
                                        <i class="fa-solid fa-gears"></i>
                                        MAKINA YOK
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <h5 class="text-danger fw-bold">Makine Bulunmuyor veya Makineler Aktif Değil!</h5>
                                </div>
                            </div>      
                        <?php }?>

                    </div>
                <?php }?>
            </div>
        </div>
    </div>
</div>
<?php 
    include_once "include/uyari_session_oldur.php"; 
?>
<!-- <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script> -->
<script>
    const MAKINA_SAYISI = <?php echo $makina_sayisi; ?> 
    
    $(function(){
        // Drag & Drop sıralama
        for(let i = 1; i <= MAKINA_SAYISI; i++){
            $( `.sortable-${i}` ).sortable({
                stop: function(event, ui) {
                    let planlamaIdler = [];
                    $(event.target).find('tr').each(function(index){
                        planlamaIdler.push($(this).data('planlama-id'))
                        $(this).find("th:nth-child(1)").text(index+1);
                    });
                    $.ajax({
                        url         : "/index.php?url=makina_is_planlama_db_islem" ,
                        dataType    : "JSON",
                        type        : "POST",
                        data        : {planlama_idler:planlamaIdler, islem:"planlama_siralama"},
                        success     : function(data){ 
                        }
                    });
                }
            });
        }

        // Anlık veri güncellemesi
        var guncellemeAktif = true;
        var guncellemeSikligi = 15000; // 15 saniye
        
        function verilerıGuncelle() {
            if (!guncellemeAktif) return;
            
            // Durum badge'ini sarıya çevir (yükleniyor)
            $('#realtime-durum')
                .removeClass('bg-success bg-danger')
                .addClass('bg-warning')
                .html('<i class="fa-solid fa-spinner fa-spin"></i> Güncelleniyor...');
            
            $.ajax({
                url: '/index.php?url=makina_is_planlama_db_islem&islem=realtime-planlama',
                type: 'GET',
                dataType: 'json',
                timeout: 8000,
                success: function(response) {
                    if (response.success) {
                        // Her makina için mevcut işi güncelle
                        $('[data-realtime="mevcut-is"]').each(function() {
                            var departmanId = $(this).data('departman-id');
                            var makinaId = $(this).data('makina-id');
                            var key = departmanId + '_' + makinaId;
                            var yeniDeger = response.makina_mevcut_isler[key] || '';
                            var mevcutDeger = $(this).text();
                            
                            // Değer değiştiyse animasyonla güncelle
                            if (mevcutDeger !== yeniDeger) {
                                $(this).fadeOut(200, function() {
                                    $(this).text(yeniDeger).fadeIn(200);
                                });
                            }
                        });
                        
                        // Durum badge'ini yeşile çevir (başarılı)
                        $('#realtime-durum')
                            .removeClass('bg-warning bg-danger')
                            .addClass('bg-success')
                            .html('<i class="fa-solid fa-circle-dot"></i> Anlık Veri');
                    } else {
                        console.error('Güncelleme hatası:', response.error);
                        durumHataGoster();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX hatası:', status, error);
                    durumHataGoster();
                }
            });
        }
        
        // Hata durumunda badge'i kırmızı yap
        function durumHataGoster() {
            $('#realtime-durum')
                .removeClass('bg-success bg-warning')
                .addClass('bg-danger')
                .html('<i class="fa-solid fa-circle-exclamation"></i> Bağlantı Hatası');
            
            // 5 saniye sonra tekrar dene
            setTimeout(function() {
                verilerıGuncelle();
            }, 5000);
        }
        
        // İlk güncellemeyi hemen yap
        verilerıGuncelle();
        
        // Periyodik güncelleme başlat
        var guncellemeInterval = setInterval(verilerıGuncelle, guncellemeSikligi);
        
        // Sayfa görünür değilken güncellemeyi durdur (performans için)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                guncellemeAktif = false;
                clearInterval(guncellemeInterval);
            } else {
                guncellemeAktif = true;
                verilerıGuncelle();
                guncellemeInterval = setInterval(verilerıGuncelle, guncellemeSikligi);
            }
        });
    }); 
</script>