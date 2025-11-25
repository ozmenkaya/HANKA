<?php
    require_once "include/oturum_kontrol.php";
    require_once "tuketim_modal.php";
    require_once "teslimat_modal.php";
    require_once "fason_modal.php";
    require_once "ariza_modal.php";
    require_once "bakim_modal.php";
    require_once "degistirme_modal.php";
    require_once "devretme_modal.php"; 
    require_once "mesaj_log_modal.php"; 
    require_once "mola_modal.php"; 
    require_once "paydos_modal.php"; 
    require_once "toplanti_modal.php"; 
    require_once "yemek_mola_modal.php"; 
    require_once "yetkili_log_modal.php"; 

    $siparis_id = isset($_GET['siparis-id']) ? intval($_GET['siparis-id']) : 0;
    $sql = 'SELECT siparisler.siparis_no, siparisler.isin_adi, siparisler.tarih,siparisler.termin,
    siparisler.fiyat, siparisler.para_cinsi,siparisler.adet,
    musteri.marka
    FROM `siparisler` 
    JOIN musteri ON musteri.id = siparisler.musteri_id
    WHERE siparisler.id = :id AND  siparisler.firma_id = :firma_id';
    //AND siparisler.islem = "tamamlandi"
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis = $sth->fetch(PDO::FETCH_ASSOC);


    //echo "<pre>"; print_r($siparis_planlamalari); exit;

    if(empty($siparis)){
        include_once "include/yetkisiz.php";
        die();
    }

    $sql = "SELECT id FROM `planlama` 
            WHERE siparis_id = :siparis_id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $planlamalar_idler              = array_column($planlamalar, 'id');
    $planlamalar_idler_birlestir    = implode(',', $planlamalar_idler);

    //print_r($planlamalar_idler);exit;

    $sql = 'select 
                pl.id, 
                ua.planlama_id, 
                pl.teslim_durumu,
                pl.alt_urun_id, 
                pl.isim as urun,
                pl.uretilecek_adet,
                ua.uretilen_adet,
                ua.uretirken_verilen_fire_adet,
                ua.asama_sayisi,
                ua.mevcut_asama,
                ua.baslangic_tarihi,
                ua.bitis_tarihi,
                ua.personel_id,
                concat(prs.ad," ", prs.soyad) as personel,
                dp.departman,
                mk.makina_adi
            from siparisler sip 
            left join planlama as pl on sip.id = pl.siparis_id and
                                        sip.firma_id  = pl.firma_id
            left join uretilen_adetler ua on COALESCE(ua.planlama_id,0) = COALESCE(pl.id,0) and 
                                            COALESCE(ua.firma_id,0)    = COALESCE(pl.firma_id,0)
            left join personeller prs on prs.id = ua.personel_id and prs.firma_id = ua.firma_id 
            left join departmanlar dp on dp.id = ua.departman_id and dp.firma_id = ua.firma_id 
            left join makinalar mk on mk.id = ua.makina_id and mk.firma_id = ua.firma_id 
            where sip.id = :id AND sip.firma_id = :firma_id
            order by pl.id desc';

    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $uretimler = $sth->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="row mt-2">
    <div class="card border-secondary">
        <div class="card-header">
            <h5>
                <i class="fa-solid fa-flag-checkered"></i> 
                Sipariş Kodu: <b class="text-danger"><?php echo $siparis['siparis_no']; ?></b>  / 
                Müşteri İsmi: <b><?php echo $siparis['marka']; ?></b> / 
                İşin Adı: <b><?php echo $siparis['isin_adi']; ?></b>
            </h5>
        </div>
        <div class="card-bod pt-0">
            <div class="row mb-4">
                <div class="col-md-4">
                    <?php 
                        $teslim_tarihi = date('Y-m-d H:i:s');
                        $startTime = new DateTime($siparis['termin']);
                        $endTime = new DateTime($teslim_tarihi);
                        
                        $interval = $startTime->diff($endTime);
                        $daysDifference = $interval->format('%a');
                        
                    ?>
                    <div class="p-3 text-white bg-blue rounded-3 ">
                        <h4 class="text-white p-1">
                            <?php echo abs($daysDifference); ?> Gün 
                            <?php echo $daysDifference >= 0 ?  'Erken' : 'Geç';?> Teslim Edildi
                        </h4>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-regular fa-calendar-days"></i> Sipariş Tarihi:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo date('d.m.Y', strtotime($siparis['tarih'])); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-regular fa-calendar-days"></i> Termin Tarihi:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo date('d.m.Y', strtotime($siparis['termin'])); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-regular fa-calendar-days"></i> Teslim Tarihi:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo date('d.m.Y', strtotime($teslim_tarihi)); ?></h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <?php 
                        $para_cinsi = '<i class="fa-solid fa-turkish-lira-sign"></i>';
                        if($siparis['para_cinsi'] == 'DOLAR')      $para_cinsi = '<i class="fa-solid fa-dollar-sign"></i>';
                        if($siparis['para_cinsi'] == 'EURO')       $para_cinsi = '<i class="fa-solid fa-euro-sign"></i>';
                        if($siparis['para_cinsi'] == 'POUND')      $para_cinsi = '<i class="fa-solid fa-sterling-sign"></i>';
                    ?>
                    <?php 
                        $maliyet    = 100;
                        $fire       = 200;
                        $toplam     = $siparis['fiyat'] - ($maliyet + $fire);
                    ?>
                    <div class="p-3 text-white bg-success rounded-3">
                        
                        <h4 class="text-white p-1">
                            <?php echo number_format(abs($toplam),2).' '.$para_cinsi;?>  
                            <?php echo $toplam >= 0 ?  ' Kar' : ' Zarar'; ?>

                            (<?php echo  number_format((abs($toplam)/$siparis['fiyat'])*100);?> %)
                        </h4>
                        <div class="row">
                            
                            <div class="col-md-5 fw-bold">
                                <i class="fa-solid fa-wallet"></i> Satış Fiyatı:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo number_format($siparis['fiyat'],2).' '.$para_cinsi; ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-solid fa-wallet"></i> Maliyet:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo number_format($maliyet,2).' '.$para_cinsi; ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-solid fa-wallet"></i> Fire Maliyet:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo number_format($fire,2).' '.$para_cinsi; ?></h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <?php 
                        $sql = "SELECT SUM(uretilecek_adet) AS uretilecek_adet, 
                                SUM(teslim_edilen_urun_adedi) AS teslim_edilen_urun_adedi,
                                SUM(biten_urun_adedi) AS biten_urun_adedi
                                FROM `planlama`
                                WHERE siparis_id = :siparis_id AND aktar_durum = 'orijinal'";

                        $sth = $conn->prepare($sql);
                        $sth->bindParam("siparis_id", $siparis_id);
                        $sth->execute();
                        $planlama_uretilen_teslim_edilen = $sth->fetch(PDO::FETCH_ASSOC);

                        $uretilecek_adet    = $planlama_uretilen_teslim_edilen['uretilecek_adet'];
                        $teslim_adeti       = $planlama_uretilen_teslim_edilen['teslim_edilen_urun_adedi'];
                        $biten_urun_adedi   = $planlama_uretilen_teslim_edilen['biten_urun_adedi'];
                    ?>
                    <div class="p-3 text-white bg-danger rounded-3">
                        <h4 class="text-white p-1">
                            <?php echo number_format(abs($uretilecek_adet-$biten_urun_adedi));?>  
                            <?php if($uretilecek_adet == $biten_urun_adedi  ){ ?>
                                Tam Üretildi
                            <?php }elseif($uretilecek_adet-$biten_urun_adedi > 0 ){ ?>
                                Fazla Üretildi
                            <?php }else{ ?>
                                Eksik Üretildi
                            <?php } ?>
                        </h4>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-solid fa-arrow-down-1-9"></i> Sipariş Adedi:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo number_format($uretilecek_adet); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-solid fa-arrow-down-1-9"></i> Üretilen Adet:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo number_format($biten_urun_adedi); ?></h6>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 fw-bold">
                                <i class="fa-solid fa-arrow-down-1-9"></i> Teslim Adedi:
                            </div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white"><?php echo number_format($teslim_adeti, 0); ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row d-flex  mb-4">
                <div class="col-md-4">
                    <?php 
                        $uretim_yemek_mola_log_gecen_sure   = 0;
                        $uretim_mola_log_gecen_sure         = 0;
                        $uretim_ariza_log_gecen_sure        = 0;
                        $uretim_bakim_log_gecen_sure        = 0;
                        $uretim_toplanti_log_gecen_sure     = 0;

                        if(!empty($planlamalar_idler_birlestir)){
                            $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND,baslatma_tarihi,bitis_tarihi)) AS toplam_sure
                                FROM `uretim_yemek_mola_log` 
                                WHERE planlama_id IN({$planlamalar_idler_birlestir})";
                            $sth = $conn->prepare($sql);
                            $sth->execute();
                            $uretim_yemek_mola_log = $sth->fetch(PDO::FETCH_ASSOC);
                            $uretim_yemek_mola_log_gecen_sure = empty($uretim_yemek_mola_log['toplam_sure']) ? 0 : $uretim_yemek_mola_log['toplam_sure'];

                            $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND,baslatma_tarihi,bitis_tarihi)) AS toplam_sure
                                FROM `uretim_mola_log` 
                                WHERE planlama_id IN({$planlamalar_idler_birlestir})";
                            $sth = $conn->prepare($sql);
                            $sth->execute();
                            $uretim_mola_log = $sth->fetch(PDO::FETCH_ASSOC);
                            $uretim_mola_log_gecen_sure = empty($uretim_mola_log['toplam_sure']) ? 0 : $uretim_mola_log['toplam_sure'];

                            $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND,baslatma_tarihi,bitis_tarihi)) AS toplam_sure
                                FROM `uretim_ariza_log` 
                                WHERE planlama_id IN({$planlamalar_idler_birlestir})";
                            $sth = $conn->prepare($sql);
                            $sth->execute();
                            $uretim_ariza_log = $sth->fetch(PDO::FETCH_ASSOC);
                            $uretim_ariza_log_gecen_sure = empty($uretim_ariza_log['toplam_sure']) ? 0 : $uretim_ariza_log['toplam_sure'];

                            $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND,baslatma_tarihi,bitis_tarihi)) AS toplam_sure
                                FROM `uretim_bakim_log` 
                                WHERE planlama_id IN({$planlamalar_idler_birlestir})";
                            $sth = $conn->prepare($sql);
                            $sth->execute();
                            $uretim_bakim_log = $sth->fetch(PDO::FETCH_ASSOC);
                            $uretim_bakim_log_gecen_sure = empty($uretim_bakim_log['toplam_sure']) ? 0 : $uretim_bakim_log['toplam_sure'];

                            $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND,baslatma_tarihi,bitis_tarihi)) AS toplam_sure
                                FROM `uretim_toplanti_log` 
                                WHERE planlama_id IN({$planlamalar_idler_birlestir})";
                            $sth = $conn->prepare($sql);
                            $sth->execute();
                            $uretim_toplanti_log = $sth->fetch(PDO::FETCH_ASSOC);
                            $uretim_toplanti_log_gecen_sure = empty($uretim_toplanti_log['toplam_sure']) ? 0 : $uretim_toplanti_log['toplam_sure'];
                        }
                        
                        $uretim_mola_log_gecen_sure_hh_mm_ss        = $uretim_mola_log_gecen_sure == 0          ?  '00:00:00':secondToHHMMSS($uretim_mola_log_gecen_sure);
                        $uretim_yemek_mola_log_gecen_sure_hh_mm_ss  = $uretim_yemek_mola_log_gecen_sure == 0    ?  '00:00:00':secondToHHMMSS($uretim_yemek_mola_log_gecen_sure);
                        
                        $uretim_ariza_log_gecen_sure_hh_mm_ss       = $uretim_ariza_log_gecen_sure == 0         ?  '00:00:00':secondToHHMMSS($uretim_ariza_log_gecen_sure);
                        $uretim_bakim_log_gecen_sure_hh_mm_ss       = $uretim_bakim_log_gecen_sure == 0         ?  '00:00:00':secondToHHMMSS($uretim_bakim_log_gecen_sure);
                        $uretim_toplanti_log_gecen_sure_hh_mm_ss    = $uretim_toplanti_log_gecen_sure == 0      ?  '00:00:00':secondToHHMMSS($uretim_toplanti_log_gecen_sure);
                    
                    ?>
                    <div class="p-3 text-white bg-blue rounded-3">
                        <h4 class="text-white p-2">
                            <?php 
                                $plansiz_toplam_durma_hh_mm_ss = timeToSeconds(secondToHHMMSS($uretim_ariza_log_gecen_sure));
                                $plansiz_toplam_durma_hh_mm_ss += timeToSeconds(secondToHHMMSS($uretim_bakim_log_gecen_sure));
                                $plansiz_toplam_durma_hh_mm_ss += timeToSeconds(secondToHHMMSS($uretim_toplanti_log_gecen_sure));
                                
                            ?> 
                            <b>Plansız Toplam Durma(<?php echo secondsToTime($plansiz_toplam_durma_hh_mm_ss); ?>)</b>
                        </h4>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h5 class="text-white fw-bold">
                                    <?php echo $uretim_toplanti_log_gecen_sure_hh_mm_ss;?>
                                </h5>
                                <p class="fw-bold"> 
                                    Toplantı <br>Süresi
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="text-white fw-bold">
                                    <?php echo $uretim_ariza_log_gecen_sure_hh_mm_ss;?>
                                </h5>
                                <p class="fw-bold"> 
                                    Arıza <br>Süresi
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="text-white fw-bold">
                                    <?php   
                                        echo $uretim_bakim_log_gecen_sure_hh_mm_ss;
                                    ?>
                                </h5>
                                <p class="fw-bold"> 
                                    Bakım <br> Süresi
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <?php 
                        $sql = "SELECT TIMESTAMPDIFF(SECOND,MIN(baslatma_tarih),MAX(bitirme_tarihi)) AS gecen_sure, 
                                MIN(baslatma_tarih) AS baslatma_tarih 
                                FROM `uretim_islem_tarihler` 
                                WHERE planlama_id IN({$planlamalar_idler_birlestir})";
                        $sth = $conn->prepare($sql);
                        $sth->execute();
                        $gecen_sure = $sth->fetch(PDO::FETCH_ASSOC);
                        //print_r($gecen_sure);
                    ?>
                    <div class="p-3 text-white bg-success rounded-3">
                        <h4 class="text-white p-2">
                            Toplam Üretim Süresi
                        </h4>
                        <div class="row mb-2 ps-2">
                            <div class="col-md-6">
                                <h4 class="text-white">
                                    <?php   
                                        if(empty($gecen_sure['gecen_sure'])){
                                            $toplam_brut_hh_mm_ss = secondToHHMMSS(strtotime(date('Y-m-d H:i:s')) - strtotime($gecen_sure['baslatma_tarih']));
                                            $totalSeconds = strtotime(date('Y-m-d H:i:s')) - strtotime($gecen_sure['baslatma_tarih']) - ($uretim_mola_log_gecen_sure + $uretim_yemek_mola_log_gecen_sure + 
                                                                                    $uretim_ariza_log_gecen_sure + $uretim_bakim_log_gecen_sure);
                                        }else{
                                            $toplam_brut_hh_mm_ss = secondToHHMMSS($gecen_sure['gecen_sure']);
                                            $totalSeconds = $gecen_sure['gecen_sure'] - ($uretim_mola_log_gecen_sure + $uretim_yemek_mola_log_gecen_sure + 
                                                                                    $uretim_ariza_log_gecen_sure + $uretim_bakim_log_gecen_sure);
                                        }
                                        echo secondsToTime($totalSeconds); 
                                    ?>  
                                </h4>
                                <p class="fw-bold">Net Süre</p>
                            </div>
                            <div class="col-md-6">
                                <h4 class="text-white">
                                    <?php   
                                        echo $toplam_brut_hh_mm_ss;
                                    ?>
                                </h4>
                                <p class="fw-bold"> 
                                    Brüt Süre
                                </p>
                            </div>
                        </div>     
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 text-white bg-danger rounded-3">
                        <?php 
                            $sql = "SELECT SUM(fire_miktari) AS fire_miktari 
                                    FROM `stok_alt_depolar_kullanilanlar` WHERE  planlama_id IN({$planlamalar_idler_birlestir})";
                            $sth = $conn->prepare($sql);
                            $sth->execute();
                            $stok_alt_depolar_kullanilanlar_fire_adet = $sth->fetch(PDO::FETCH_ASSOC);


                            $sql = "SELECT SUM(uretirken_verilen_fire_adet) AS uretirken_verilen_fire_adet 
                                    FROM `uretilen_adetler` WHERE firma_id = :firma_id AND planlama_id IN({$planlamalar_idler_birlestir})";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $uretirken_verilen_fire_adet = $sth->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <h4 class="text-white p-2">
                            Toplam Fire
                        </h4>
                        <div class="row ps-2 mb-2">
                            <div class="col-md-5 fw-bold">Stok Fire:</div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white">
                                    <?php echo number_format($stok_alt_depolar_kullanilanlar_fire_adet['fire_miktari'] ?? 0);?> Adet
                                </h6>
                            </div>
                        </div>
                        <div class="row ps-2 mb-2">
                            <div class="col-md-5 fw-bold">Ürün Fire:</div>
                            <div class="col-md-7 text-end">
                                <h6 class="text-white">
                                    <?php echo number_format($uretirken_verilen_fire_adet['uretirken_verilen_fire_adet']);?> Adet
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row d-flex mb-1">
                <div class="col-md-4">
                    <div class="p-3 text-white bg-blue rounded-3">
                        <?php 
                            $planli_toplam_durma_hh_mm_ss = timeToSeconds(secondToHHMMSS($uretim_yemek_mola_log_gecen_sure));
                            $planli_toplam_durma_hh_mm_ss += timeToSeconds(secondToHHMMSS($uretim_mola_log_gecen_sure));
                        ?> 
                        <h4 class="text-white p-2">
                            <b>Planlı Toplam Durma(<?php echo secondsToTime($planli_toplam_durma_hh_mm_ss); ?>)</b>
                        </h4>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h5 class="text-white fw-bold">
                                    <?php echo $uretim_mola_log_gecen_sure_hh_mm_ss;?>
                                </h5>
                                <p class="fw-bold"> 
                                    Mola<br> Süresi
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h5 class="text-white fw-bold">
                                    <?php echo $uretim_yemek_mola_log_gecen_sure_hh_mm_ss;?>
                                </h5>
                                <p class="fw-bold"> 
                                    Yemek<br>Süresi
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <h3 class="text-center">Üretim Miktarları</h3>
                    <div class="table-responsive">
                            <div id="actionButtons" style="display: none; margin-bottom: 10px;">
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#tuketim-modal"
                                    title="Tüketimler" 
                                    type="button" 
                                    onclick="tuketimGoster()" 
                                    class="btn btn-sm btn-info waves-effect waves-light">
                                    <i class="mdi mdi-cube-outline"></i>
                                </button>
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#teslimat-modal"
                                    title="Teslimatlar" 
                                    type="button" 
                                    onclick="teslimatGoster()" 
                                    type="button" class="btn btn-sm btn-warning waves-effect waves-light">
                                    <i class="mdi mdi-truck"></i>
                                </button>
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#fason-modal"
                                    title="Fason Loglar" 
                                    type="button" 
                                    onclick="fasonGoster()" 
                                    type="button" class="btn btn-sm btn-danger waves-effect waves-light">
                                    <i class="mdi mdi-basket-unfill"></i>
                                </button>  
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#ariza-modal"
                                    title="Arıza Loglar" 
                                    type="button" 
                                    onclick="arizaGoster()" 
                                    type="button" class="btn btn-sm btn-pink waves-effect waves-light">
                                    <i class="mdi mdi-blinds"></i>
                                </button>  
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#bakim-modal"
                                    title="Bakım Loglar" 
                                    type="button" 
                                    onclick="bakimGoster()" 
                                    type="button" class="btn btn-sm btn-dark waves-effect waves-light">
                                    <i class="mdi mdi-blogger"></i>
                                </button>   
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#degistirme-modal"
                                    title="Değiştirme Loglar" 
                                    type="button" 
                                    onclick="degistirmeGoster()" 
                                    type="button" class="btn btn-sm btn-success waves-effect waves-light">
                                    <i class="mdi mdi-ornament"></i>
                                </button>   
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#devretme-modal"
                                    title="Devretme Sebepleri" 
                                    type="button" 
                                    onclick="devretmeGoster()" 
                                    type="button" class="btn btn-sm btn-primary waves-effect waves-light">
                                    <i class="mdi mdi-rotate-left-variant"></i>
                                </button>  
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#mesaj-modal"
                                    title="Mesaj Logları" 
                                    type="button" 
                                    onclick="mesajGoster()" 
                                    type="button" class="btn btn-sm btn-info waves-effect waves-light">
                                    <i class="mdi mdi-message-processing"></i>
                                </button>
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#mola-modal"
                                    title="Mola Logları" 
                                    type="button" 
                                    onclick="molaGoster()" 
                                    type="button" class="btn btn-sm btn-warning waves-effect waves-light">
                                    <i class="mdi mdi-archive"></i>
                                </button>  
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#paydos-modal"
                                    title="Paydos Logları" 
                                    type="button" 
                                    onclick="paydosGoster()" 
                                    type="button" class="btn btn-sm btn-danger waves-effect waves-light">
                                    <i class="mdi mdi-scale-bathroom"></i>
                                </button>  
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#toplanti-modal"
                                    title="Toplantı Logları" 
                                    type="button" 
                                    onclick="toplantiGoster()" 
                                    type="button" class="btn btn-sm btn-info waves-effect waves-light">
                                    <i class="mdi mdi-format-paint"></i>
                                </button>  
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#yemekmola-modal"
                                    title="Yemek Mola Logları" 
                                    type="button" 
                                    onclick="yemekmolaGoster()" 
                                    type="button" class="btn btn-sm btn-pink waves-effect waves-light">
                                    <i class="mdi mdi-fridge"></i>
                                </button> 
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#yetkili-modal"
                                    title="Üretim Yetkili Logları" 
                                    type="button" 
                                    onclick="yetkiliGoster()" 
                                    type="button" class="btn btn-sm btn-dark waves-effect waves-light">
                                    <i class="mdi mdi-dns"></i>
                                </button>   
                                    <div class="btn-group" role="group" style="float:right">
                                        <a href="javascript:window.history.back();" 
                                            class="btn btn-sm btn-secondary"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom" 
                                            data-bs-title="Geri Dön"
                                        >
                                            <i class="fa-solid fa-arrow-left"></i>
                                        </a>
                                        <a href="/index.php?url=rapor_db_islem&islem=rapor_excel&siparis_id=<?php echo $siparis_id; ?>" 
                                            class="btn btn-sm btn-success"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            data-bs-title="Excel"
                                        >
                                            <i class="fa-regular fa-file-excel"></i>
                                        </a>
                                    </div>
                            </div>	        
                            </div>
                            <table id="myTable" class="table table-hover table-sm">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="text-align align-middle text-center">#</th>
                                        <th class="text-center">Planlama Id</th>
                                        <th>Ürün</th>
                                        <th>Departman</th>
                                        <th>Makina Adı</th>
                                        <th class="text-center">Aşama Sayısı</th>
                                        <th class="text-center">Mevcut Aşama</th>
                                        <th class="text-end" style="padding-right: 30px;">Üretilecek Adet</th>
                                        <th class="text-end" style="padding-right: 30px;">Üretilen Adet</th>
                                        <th class="text-end" style="padding-right: 30px;">Fire Miktarı</th>
                                        <th class="text-center">Personel</th>
                                        <th class="text-center">Bşl.Trh.</th>
                                        <th class="text-center">Bit.Trh.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uretimler as $key => $uretim) { ?>
                                        <tr data-row-id="<?php echo $key + 1 ;?>">
                                            <td class="table-primary text-center align-middle table_sm_pd"><?php echo $key +1 ;?></td>
                                            <td class="text-center align-middle table_sm_pd"><?php echo $uretim['id'];?></td>
                                            <td class="align-middle table_sm_pd"><?php echo $uretim['urun'];?></td>
                                            <td class="align-middle table_sm_pd"><?php echo $uretim['departman'];?></td>
                                            <td class="align-middle table_sm_pd"><?php echo $uretim['makina_adi'];?></td>
                                            <td class="text-center align-middle table_sm_pd"><?php echo $uretim['asama_sayisi']; ?></td> 
                                            <td class="text-center align-middle table_sm_pd"><?php echo $uretim['mevcut_asama']; ?></td> 
                                            <td class="text-end align-middle table_sm_pd"><?php echo isset($uretim['uretilecek_adet']) && is_numeric($uretim['uretilecek_adet']) ? number_format($uretim['uretilecek_adet'], 0, ',', '.') : ''; ?></td> 
                                            <td class="text-end align-middle table_sm_pd"><?php echo isset($uretim['uretilen_adet']) && is_numeric($uretim['uretilen_adet']) ? number_format($uretim['uretilen_adet'], 0, ',', '.') : ''; ?></td> 
                                            <td class="text-end align-middle table_sm_pd"><?php echo isset($uretim['uretirken_verilen_fire_adet']) && is_numeric($uretim['uretirken_verilen_fire_adet']) ? number_format($uretim['uretirken_verilen_fire_adet'], 0, ',', '.') : ''; ?></td> 
                                            <td class="align-middle table_sm_pd"><?php echo $uretim['personel']; ?></td> 
                                            <td class="align-middle table_sm_pd"><?php echo isset($uretim['baslangic_tarihi']) && $uretim['baslangic_tarihi'] ? date('d.m.Y H:i:s', strtotime($uretim['baslangic_tarihi'])) : '' ?></td> 
                                            <td class="align-middle table_sm_pd"><?php echo isset($uretim['bitis_tarihi']) && $uretim['bitis_tarihi'] ? date('d.m.Y H:i:s', strtotime($uretim['bitis_tarihi'])) : '' ?></td>  
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
<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <!-- Mesaj buraya gelecek -->
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php 
    include_once "include/uyari_session_oldur.php"; 
?>
<!-- jQuery Script -->
<script>
    let selectedRowId = null;
    let selectedRowData = null;

    // Toast’ı başlatma fonksiyonu
    function showErrorToast(message) {
        const toast = $('#errorToast');
        toast.find('.toast-body').text(message);
        const bsToast = new bootstrap.Toast(toast[0], { delay: 3000 }); // 3 saniye sonra kapanır
        bsToast.show();
    }

    $(document).ready(function () { 

            $('#myTable tbody tr').on('dblclick', function () {

                $('#actionButtons').show();

                selectedRowId = $(this).data('row-id');

                const cells = $(this).find('td');
                selectedRowData = {
                    id: cells.eq(1).text(),
                    urun: cells.eq(2).text(),
                    departman: cells.eq(3).text(),
                    makina_adi: cells.eq(4).text(),
                    asama_sayisi: cells.eq(5).text(),
                    mevcut_asama: cells.eq(6).text(),
                    uretilecek_adet: cells.eq(7).text(),
                    uretilen_adet: cells.eq(8).text(),
                    fire_miktari: cells.eq(9).text(),
                    personel: cells.eq(10).text(),
                    baslangic_tarihi: cells.eq(11).text(),
                    bitis_tarihi: cells.eq(12).text()
                };

                $('#myTable tbody tr').removeClass('table-active');
                $(this).addClass('table-active');
            });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#myTable').length && !$(e.target).closest('#actionButtons').length) {
                $('#actionButtons').hide();
                selectedRowId = null;
                selectedRowData = null;
                $('#myTable tbody tr').removeClass('table-active');
            }
        });
    });
 
    function tuketimGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=tuketim-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                            $('#tuketimTablosu tbody').empty(); 
                            response.data.forEach(function(stok, index) {
                                let tuketimAdedi = parseInt(stok.tuketim_miktari) || 0;
                                let formattedAdet = tuketimAdedi.toLocaleString('tr-TR'); 

                                let fireAdedi = parseInt(stok.fire_miktari) || 0;
                                let formattedFireAdet = fireAdedi.toLocaleString('tr-TR'); 

                                let row = `s
                                    <tr>
                                        <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.stok_kalem}</td>
                                        <td class="pt-1 pb-1 table_sm_pd align-middle text-end">${formattedAdet}</td>
                                        <td class="pt-1 pb-1 table_sm_pd align-middle text-end">${formattedFireAdet}</td>
                                        <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.birim}</td>
                                        <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                        <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.tarih}</td>
                                    </tr>
                                `;
                                $('#tuketimTablosu tbody').append(row); 
                            }); 
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function teslimatGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=teslimat-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#teslimatTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let teslimAdedi = parseInt(stok.teslim_adedi) || 0;
                            let formattedAdet = teslimAdedi.toLocaleString('tr-TR'); 

                            let row = `s
                                <tr>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-end">${formattedAdet}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.tarih}</td>
                                </tr>
                            `;
                            $('#teslimatTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function fasonGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=fason-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#fasonTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.durum}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.iptal_sebebi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.gidis_tarihi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.gelis_tarihi}</td>
                                </tr>
                            `;
                            $('#fasonTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function arizaGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=ariza-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#arizaTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.mesaj}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.baslatma_tarihi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.bitis_tarihi}</td>
                                </tr>
                            `;
                            $('#arizaTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function bakimGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=bakim-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#bakimTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.ariza_sebebi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.sorun_cozuldu_mu}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.gelen_personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.personel_gelme_tarihi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.baslatma_tarihi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.bitis_tarihi}</td>
                                </tr>
                            `;
                            $('#bakimTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function degistirmeGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=degistirme-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#degistirmeTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.degistirme_sebebi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.sorun_bildirimi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.tarih}</td>
                                </tr>
                            `;
                            $('#degistirmeTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function devretmeGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=devretme-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#devretmeTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.hangi_makinadan}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.hangi_makinaya}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.hangi_makinaya}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.devretme_sebebi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.tarih}</td>
                                </tr>
                            `;
                            $('#devretmeTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function mesajGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=mesaj-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#mesajTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.mesaj}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.tarih}</td>
                                </tr>
                            `;
                            $('#mesajTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function molaGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=mola-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#molaTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.baslatma_tarihi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.bitis_tarihi}</td>
                                </tr>
                            `;
                            $('#molaTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function paydosGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=paydos-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#paydosTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.tarih}</td>
                                </tr>
                            `;
                            $('#paydosTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function toplantiGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=toplanti-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#toplantiTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.baslatma_tarihi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.bitis_tarihi}</td>
                                </tr>
                            `;
                            $('#toplantiTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function yemekmolaGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=yemekmola-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#yemekmolaTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.baslatma_tarihi}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.bitis_tarihi}</td>
                                </tr>
                            `;
                            $('#yemekmolaTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
    function yetkiliGoster() {
        if (selectedRowId && selectedRowData) {

            var id = selectedRowData.id + '_' + selectedRowData.mevcut_asama;
            
            $.ajax({
                url: "/index.php?url=rapor_db_islem&islem=yetkili-getir&id=" + id,
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        $('#yetkiliTablosu tbody').empty(); 
                        response.data.forEach(function(stok, index) {
                            let row = `s
                                <tr>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.gelen_personel}</td> 
                                    <td class="pt-1 pb-1 table_sm_pd align-middle">${stok.personel}</td>
                                    <td class="pt-1 pb-1 table_sm_pd align-middle text-center">${stok.tarih}</td>
                                </tr>
                            `;
                            $('#yetkiliTablosu tbody').append(row); 
                        }); 
                            
                    } else { 
                        showErrorToast('Veri alınamadı: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorToast('Hata: ' + error);
                }
            });
        } else {
            showErrorToast('Lütfen bir satır seçin.');
        }
    }
</script>