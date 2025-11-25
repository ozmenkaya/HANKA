<?php
    require_once "include/oturum_kontrol.php";

    $makina_id = isset($_GET['makina-id']) ? $_GET['makina-id'] : 0;

    $sql = "SELECT makinalar.makina_adi, makinalar.makina_modeli FROM `makinalar` 
    JOIN `makina_personeller` ON `makina_personeller`.makina_id  = makinalar.id
    WHERE makinalar.firma_id = :firma_id AND makinalar.id = :id AND makinalar.durumu = 'aktif' 
    AND makina_personeller.personel_id = :personel_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('id', $makina_id);
    $sth->bindParam('personel_id', $_SESSION['personel_id']);
    $sth->execute();
    $makina = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($makina))
    {
        require_once "include/yetkisiz.php"; exit;
    }
    
?>
        <div class="container">
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card border-secondary">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold">
                                <i class="fa-solid fa-gears"></i>
                                <span class="badge bg-secondary">
                                    <?php echo $makina['makina_adi'].' '.$makina['makina_modeli']; ?>
                                </span>
                            </h5>
                            <h5 class="fw-bold">
                                <i class="fa-regular fa-circle-user"></i> <b>USTA:</b> 
                                <span class="badge bg-secondary">
                                    <?php echo  $_SESSION['ad'].' '.$_SESSION['soyad']; ?>
                                </span>
                            </h5>

                            <h5 class="fw-bold">
                                <i class="fa-solid fa-gears"></i>  <b>İş Sayısı:</b>  
                                <span class="badge bg-secondary rounded-circle" id="is-sayisi" ></span>
                            </h5>

                            <div>
                                <a href="/index.php?url=makina_listesi" class="btn btn-primary fw-bold">
                                    <i class="fa-solid fa-gears"></i> Makinalar
                                </a>
                                <span class="badge bg-success ms-2" id="realtime-status">
                                    <i class="fa-solid fa-circle-dot"></i> Anlık Veri
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card border-secondary">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-hover align-middle" >
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Sıra</th>
                                            <th class="text-center">Bilgi</th>
                                            <th>Sipariş No</th>
                                            <th>İşin Adı</th>
                                            <th>Alt Ürün</th>
                                            <th>İşin Durumu</th>
                                            <th class="text-end">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php  
                                            $sql = "SELECT planlama.id, planlama.makinalar, planlama.isim,planlama.asama_sayisi, planlama.mevcut_asama,
                                            planlama.durum,planlama.departmanlar,`planlama`.`uretilecek_adet`,planlama.durum,planlama.adetler,
                                            planlama.orijinal_adetler, planlama.asamada_eksik_adet_varmi, planlama.grup_kodu,
                                            siparisler.isin_adi,siparisler.siparis_no
                                            FROM planlama 
                                            JOIN siparisler ON siparisler.id = planlama.siparis_id
                                            WHERE planlama.firma_id = :firma_id AND planlama.durum IN('baslamadi','basladi','beklemede') 
                                            AND onay_durum = 'evet' AND aktar_durum = 'orijinal' ORDER BY planlama.sira";
                                            $sth = $conn->prepare($sql);
                                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                            $sth->execute();
                                            $isler = $sth->fetchAll(PDO::FETCH_ASSOC);

                                            //echo "<pre>";print_r($isler); exit;

                                            $is_basladi_mi = false;
                                            foreach ($isler as $key => $is) {
                                                $makinalar = json_decode($is['makinalar'], true); 
                                                if($is['durum'] == 'basladi' && isset($makinalar[$is['mevcut_asama']]) && $makinalar[$is['mevcut_asama']] == $makina_id) 
                                                {
                                                    //işlemdekini en başa koyma
                                                    array_unshift($isler, $isler[$key]);
                                                    unset($isler[$key+1]);
                                                    $is_basladi_mi = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                        <?php $sira = 0;?>
                                        <?php foreach ($isler as $is) { ?>
                                            <?php 
                                                $makinalar          = json_decode($is['makinalar'], true); 
                                                if(empty($makinalar)) continue; //Planlama Yapılmayan
                                                $adetler            = json_decode($is['adetler'], true); 
                                                $orijinal_adetler   = json_decode($is['orijinal_adetler'], true); 
                                                $orijinal_adet      = isset($orijinal_adetler[$is['mevcut_asama']]) ? $orijinal_adetler[$is['mevcut_asama']] : 0;

                                                $sql = "SELECT SUM(aktarilan_adet) AS aktarilan_adet FROM `uretim_aktarma_loglar` 
                                                        WHERE grup_kodu = :grup_kodu  AND aktarilan_asama = :aktarilan_asama";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam('grup_kodu',$is['grup_kodu']);
                                                $sth->bindParam('aktarilan_asama',$is['mevcut_asama']);
                                                $sth->execute();
                                                $uretim_aktarilan_adet = $sth->fetch(PDO::FETCH_ASSOC);
                                                $uretim_aktarilan_adet = empty($uretim_aktarilan_adet['aktarilan_adet']) ? 
                                                                            $orijinal_adetler[$is['mevcut_asama']] : 
                                                                            $uretim_aktarilan_adet['aktarilan_adet'];

                                                $uretim_aktarilan_adet = $uretim_aktarilan_adet == 0 ? 1 : $uretim_aktarilan_adet;

                                                $sql = "SELECT SUM(uretilen_adet) AS  toplam_uretilen_adet FROM `uretilen_adetler`
                                                        WHERE grup_kodu = :grup_kodu  AND mevcut_asama = :mevcut_asama";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam('grup_kodu', $is['grup_kodu']);
                                                $sth->bindParam('mevcut_asama', $is['mevcut_asama']);
                                                $sth->execute();
                                                $toplam_uretilen_adet = $sth->fetch(PDO::FETCH_ASSOC);
                                                $toplam_uretilen_adet = isset($toplam_uretilen_adet['toplam_uretilen_adet']) ? $toplam_uretilen_adet['toplam_uretilen_adet'] : 0;
                                                //print_r($adetler);
                                                $asama_yuzdesi = round(($toplam_uretilen_adet*100)/$uretim_aktarilan_adet,2);
                                            ?>
                                            <?php if( isset($makinalar[$is['mevcut_asama']]) && $makinalar[$is['mevcut_asama']] == $makina_id){ ?>
                                                <tr class="<?php echo $is_basladi_mi && in_array($is['asamada_eksik_adet_varmi'], ['basladi','uret'])  ? 'table-danger':''; ?>"> 
                                                    <th class="table-primary">
                                                        
                                                        <?php echo ++$sira ; ?>
                                                    </th>
                                                    <th class="text-center">
                                                        <?php if($is['asamada_eksik_adet_varmi'] == 'var'){ ?>
                                                            <span class="fw-bold text-danger" 
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-html="true"
                                                                data-bs-placement="top" 
                                                                data-bs-title="<b class='text-danger'>Eksik Mal Üretildiği İçin Onay Bekliyor</b>"
                                                            >
                                                                <i class="fa-solid fa-circle-exclamation fa-2x"></i> 
                                                            </span>
                                                        <?php }else if($is['asamada_eksik_adet_varmi'] == 'uret'){?> 
                                                            <span class="fw-bold text-danger" 
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-html="true"
                                                                data-bs-placement="top" 
                                                                data-bs-title="<b class='text-danger'>Eksik Mal Tamamlayınız. İş Başlatıldı</b>"
                                                            >
                                                                <i class="fa-solid fa-circle-exclamation fa-3x"></i> 
                                                            </span>
                                                        <?php } ?>
                                                    </th>
                                                    <th class="table-secondary">
                                                        <button type="button" class="btn btn-primary btn-sm fw-bold text-decoration-underline siparis-detay" 
                                                            data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" 
                                                            data-bs-custom-class="custom-tooltip" 
                                                            data-bs-title="<b><i class='fa-regular fa-rectangle-list'></i> Sipariş Detayları</b>" data-planlama-id="<?php echo $is['id']; ?>"
                                                        >   
                                                            <?php echo $is['siparis_no']; ?>                                              
                                                        </button>
                                                    </th>
                                                    <td><?php echo $is['isin_adi']; ?></td>
                                                    <td><?php echo $is['isim']; ?></td>
                                                    <td>
                                                        <?php $bitmis_mi = $is['mevcut_asama'] == $is['asama_sayisi'];?>
                                                        <ul class="list-group">
                                                            <li class="list-group-item d-flex gap-2">
                                                                <?php 
                                                                    if($is['durum'] == 'baslamadi'){
                                                                        $mevcut_asama_sonuc = 0;
                                                                    }else if($is['durum'] == 'bitti'){
                                                                        $mevcut_asama_sonuc = $is['mevcut_asama'];
                                                                    }else{
                                                                        $mevcut_asama_sonuc = $is['mevcut_asama'] + 1;
                                                                    }
                                                                ?>
                                                                
                                                                <span class="badge bg-secondary mb-1">
                                                                    <?php echo $mevcut_asama_sonuc; ?> / 
                                                                    <?php echo $is['asama_sayisi']?>
                                                                </span>
                                                                <?php if($is['mevcut_asama'] < $is['asama_sayisi'] ){ ?>
                                                                    <?php 
                                                                        $departmanlar = json_decode($is['departmanlar'], true); 
                                                                        $mevcut_departman_id = $departmanlar[$is['mevcut_asama']];

                                                                        $sql = "SELECT departman FROM `departmanlar` WHERE id = :id";
                                                                        $sth = $conn->prepare($sql);
                                                                        $sth->bindParam('id', $mevcut_departman_id);
                                                                        $sth->execute();
                                                                        $departman = $sth->fetch(PDO::FETCH_ASSOC);
                                                                    ?>
                                                                    
                                                                    <span class="badge bg-secondary mb-1"> <?php echo $departman['departman']; ?>  </span>
                                                                <?php }else{ ?>
                                                                    <span class="badge bg-success mb-1"> Bitti </span>              
                                                                <?php } ?>
                                                                <span class="badge bg-secondary mb-1"> 
                                                                    <?php echo number_format($toplam_uretilen_adet); ?> / 
                                                                    <?php echo number_format($uretim_aktarilan_adet); ?>
                                                                </span>
                                                            </li>
                                                            <li class="list-group-item">
                                                                <div class="progress" role="progressbar"  aria-valuenow="<?php echo $asama_yuzdesi;?>" aria-valuemin="0" aria-valuemax="100" style="height: 25px">
                                                                    <div class="progress-bar progress-bar-striped fw-bold <?php echo $is['asama_sayisi'] == $is['mevcut_asama'] ? 'bg-success' :''; ?>" style="width: <?php echo $asama_yuzdesi; ?>%">
                                                                        <?php echo $asama_yuzdesi; ?>%
                                                                    </div>
                                                                </div>   
                                                            </li>
                                                        </ul>
                                                    </td>
                                                    <td class="text-end">
                                                        <?php if($is_basladi_mi == false){ ?>
                                                            <a href="/index.php?url=makina_is_ekran&planlama-id=<?php echo $is['id'] ?>&makina-id=<?php echo $makina_id;?>" 
                                                                class="btn btn-success"
                                                            >
                                                                <i class="fa-solid fa-paper-plane"></i>
                                                            </a>
                                                        <?php }elseif($is_basladi_mi && $is['durum'] == 'basladi'){ ?> 
                                                            <a href="/index.php?url=makina_is_ekran&planlama-id=<?php echo $is['id'] ?>&makina-id=<?php echo $makina_id;?>" 
                                                                class="btn btn-success"
                                                            >
                                                                <i class="fa-solid fa-paper-plane"></i>
                                                            </a>
                                                        <?php } ?>
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

        <!-- Sipariş Detay Modal -->
        <div class="modal fade" id="siparis-detay-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-bag-shopping"></i> Sipariş Detay
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="siparis-detay-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php include_once "include/uyari_session_oldur.php"; ?>
        <script>
            const makina_id = <?php echo $makina_id; ?>;
            let sonGuncelleme = Date.now();

            // İş sayısını ayarla
            $("#is-sayisi").text("<?php echo $sira;?>");

            // Real-time veri güncelleme (10 saniyede bir)
            function verilerıGuncelle() {
                $.ajax({
                    url: '/index.php?url=makina_is_listesi_db_islem&islem=realtime-liste',
                    method: 'GET',
                    data: {
                        makina_id: makina_id
                    },
                    dataType: 'json',
                    success: function(data) {
                        if(data.success) {
                            // İş sayısını güncelle
                            if(data.is_sayisi !== undefined) {
                                $("#is-sayisi").text(data.is_sayisi);
                            }
                            
                            // Eğer liste değiştiyse tam sayfa yenile
                            if(data.liste_degisti) {
                                console.log('✓ Liste değişti, sayfa yenileniyor...');
                                window.location.reload();
                            } else {
                                console.log('✓ Veriler güncellendi:', new Date().toLocaleTimeString());
                            }
                            
                            sonGuncelleme = Date.now();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('✗ Veri güncelleme hatası:', error);
                    }
                });
            }
            
            // İlk yüklemede 5 saniye sonra çalıştır
            setTimeout(verilerıGuncelle, 5000);
            
            // Her 10 saniyede bir güncelle
            setInterval(verilerıGuncelle, 10000);

            $(".siparis-detay").click(function(){
                    const planlamaId = $(this).data('planlama-id');
                    $.ajax({
                        url         : '/index.php?url=makina_is_ekran_db_islem&islem=siparis-detay-ajax', 
                        dataType    : 'JSON', 
                        data        : {planlama_id:planlamaId},
                        type        : 'POST',
                        success     : function (data) {
                            log(data)
                            let resimlerHTML = '', formHTML = '';
                            data.siparis_dosyalar.forEach((dosya) => {
                                resimlerHTML += `
                                    <a class="text-decoration-none example-image-link" 
                                        href="dosyalar/siparisler/${dosya.ad}" 
                                        data-lightbox="example-set" data-title=""
                                    >
                                        <img src="dosyalar/siparisler/${dosya.ad}" 
                                            class="rounded img-thumbnai border border-secondary-subtle object-fit-fill mb-1 mt-1" 
                                            style="height:50px; min-height:50px; width:50px;"
                                        >
                                    </a>
                                `;
                            });

                            let veriler;
                            if(data.planlama.tip_id == 1){
                                veriler = JSON.parse(data.planlama.veriler);
                            }else if(data.planlama.tip_id == 2 || data.planlama.tip_id == 3){
                                veriler = JSON.parse(data.planlama.veriler)[data.planlama.alt_urun_id-1];
                            }

                            if(veriler.form){
                                for(const [key, value] of Object.entries(veriler.form)){
                                    if(value != ''){
                                        formHTML += `
                                            <li class="list-group-item list-group-item-warning"><b>${key}:</b> ${value}</li>
                                        `;
                                    }
                                    
                                }
                            }
                            

                            let stokVerilerHTML = '';
                            let keys = [];
                            let values = [];
                            data.stok_veriler.forEach((stok_veri)=>{
                                log(keys, values);
                                keys = Object.keys(JSON.parse(stok_veri.veri));
                                values = Object.values(JSON.parse(stok_veri.veri));
                                stokVerilerHTML += `
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold">${stok_veri.stok_kalem}</div>
                                            ${keys.join('/')}  => ${values.join('/')}
                                        </div>
                                    </li>
                                `;
                            });

                            $("#siparis-detay-body").html(`
                                <div class="row mb-1">
                                    <div class="col-md-6">
                                        <ol class="list-group list-group-numbered mb-2">
                                        <li class="list-group-item active fw-bold" aria-current="true">Şipariş Detayları</li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold">İşin Adı</div>
                                                    ${data.planlama.isin_adi}
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold">Sipariş Adeti</div>
                                                    ${data.planlama.uretilecek_adet}
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold">Termin Tarihi</div>
                                                    ${data.planlama.termin}      
                                                </div>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold">Paketleme</div>
                                                    ${data.planlama.paketleme}       
                                                </div>
                                            </li>
                                            
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold">Açıklama</div>
                                                    ${data.planlama.aciklama}      
                                                </div>
                                            </li>
                                        </ol>
                                        <ol class="list-group list-group-numbered">
                                            <li class="list-group-item active fw-bold" aria-current="true">MAZLEMELER</li>
                                            ${stokVerilerHTML}
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            ${formHTML}
                                        </ul>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fa-solid fa-image"></i> Sipariş Resimleri</h5>
                                    </div>
                                    <div class="card-body">
                                        ${resimlerHTML}
                                    </div>
                                </div>
                            `);
                            $("#siparis-detay-modal").modal('show');
                        },
                        error: function (response) {
                            $.notify("Veri Çekme Başarısız(Error)","error");
                        }
                    });
                });
        </script>