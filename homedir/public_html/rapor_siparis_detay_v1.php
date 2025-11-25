<?php
    require_once "include/oturum_kontrol.php";

    $siparis_id = isset($_GET['siparis-id']) ? intval($_GET['siparis-id']) : 0;
    $sql = 'SELECT siparisler.siparis_no, siparisler.isin_adi, siparisler.tarih,siparisler.termin,
    siparisler.fiyat, siparisler.para_cinsi,siparisler.adet,
    musteri.marka
    FROM `siparisler` 
    JOIN musteri ON musteri.id = siparisler.musteri_id
    WHERE siparisler.id = :id AND  siparisler.firma_id = :firma_id AND siparisler.islem = "tamamlandi"';
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

    $sql = "SELECT id,isim,siparis_id,departmanlar FROM planlama WHERE siparis_id = :siparis_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->execute();
    $siparisin_planlamalari = $sth->fetchAll(PDO::FETCH_ASSOC);
    $planlama_idler = [];
    foreach ($siparisin_planlamalari as $siparisin_planlama) {
        $planlama_idler[] = $siparisin_planlama['id'];
    }
?>
<div class="row mt-2">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>
                        <i class="fa-solid fa-flag-checkered"></i> 
                        Sipariş Kodu: <b><?php echo $siparis['siparis_no']; ?></b>  / 
                        Müşteri İsmi: <b><?php echo $siparis['marka']; ?></b> / 
                        İşin Adı: <b><?php echo $siparis['isin_adi']; ?></b>
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div class="row mb-4" >
                        <div class="col-md-4">
                            <?php 
                                $teslim_tarihi = date('Y-m-d H:i:s');
                                $startTime = new DateTime($siparis['termin']);
                                $endTime = new DateTime($teslim_tarihi);
                                
                                $interval = $startTime->diff($endTime);
                                $daysDifference = $interval->format('%a');
                                
                            ?>
                            <div class="p-3   text-white bg-<?php echo $daysDifference >= 0 ? 'primary':'danger'; ?> rounded-3 ">
                                
                                <h3 class="p-2">
                                    <?php echo abs($daysDifference); ?> Gün 
                                    <?php echo $daysDifference >= 0 ?  'Erken' : 'Geç';?>
                                </h3>
                                <div class="row">
                                    <div class="col-md-5">Sipariş Tarihi:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo date('d-m-Y', strtotime($siparis['tarih'])); ?></h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">Termin Tarihi:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo date('d-m-Y', strtotime($siparis['termin'])); ?></h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">Teslim Tarihi:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo date('d-m-Y', strtotime($teslim_tarihi)); ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <?php 
                                $maliyet = 100;
                                $fire  = 200;

                                $toplam = $siparis['fiyat'] - ($maliyet + $fire);
                            ?>
                            <div class="p-3 text-white bg-<?php echo $toplam >= 0 ? 'primary':'danger';?> rounded-3 ">
                                <h3 class="p-2">
                                    <?php echo abs($toplam).' '.$siparis['para_cinsi'];?>  
                                    <?php echo $toplam >= 0 ?  ' Kar' : ' Zarar'; ?>

                                    (<?php echo  number_format(abs($toplam)/$siparis['fiyat']*100,0,'','.');?> %)
                                </h3>
                                <div class="row">
                                    <div class="col-md-5">Satış Fiyatı:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo $siparis['fiyat'].' '.$siparis['para_cinsi']; ?></h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">Maliyet:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo $maliyet.' '.$siparis['para_cinsi']; ?></h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">Fire Maliyet:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo $fire.' '.$siparis['para_cinsi'] ; ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <?php 
                                $teslim_adeti = 1100;

                            ?>
                            <div class="p-3 text-white bg-<?php echo $siparis['adet']-$teslim_adeti >= 0 ? 'primary':'danger';?> rounded-3 ">
                                <h3 class="p-2">
                                    <?php echo number_format(abs($siparis['adet']-$teslim_adeti),0,'',',');?>  
                                    <?php if($siparis['adet'] == $teslim_adeti  ){ ?>
                                        Tam
                                    <?php }elseif($siparis['adet']-$teslim_adeti > 0 ){ ?>
                                        Fazla
                                    <?php }else{ ?>
                                        Eksik
                                    <?php } ?>
                                </h3>
                                <div class="row">
                                    <div class="col-md-5">Sipariş Adedi:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo number_format($siparis['adet'], 0, '',','); ?></h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">Teslim Adedi:</div>
                                    <div class="col-md-7 text-end">
                                        <h6><?php echo number_format($teslim_adeti, 0, '',','); ?></h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5" style="visibility: hidden;">-</div>
                                    <div class="col-md-7"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <?php 
                                $teslim_tarihi = date('Y-m-d H:i:s');
                                $startTime = new DateTime($siparis['termin']);
                                $endTime = new DateTime($teslim_tarihi);
                                
                                $interval = $startTime->diff($endTime);
                                $daysDifference = $interval->format('%a');
                                
                            ?>
                            <div class="p-3 text-white bg-primary rounded-3">
                                <?php 
                                    $toplam_gecen_sure = 0;
                                    $sql = "SELECT baslatma_tarihi,bitis_tarihi FROM `uretim_mola_log` WHERE `planlama_id` IN(:planlama_idler)";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindValue('planlama_idler', implode($planlama_idler));
                                    $sth->execute();
                                    $molalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    $toplam_mola_saati = 0;
                                    $toplam_mola_dakika = 0;
                                    foreach ($molalar as $mola) {
                                        $startTime = new DateTime($mola['bitis_tarihi']);
                                        $endTime = new DateTime($mola['baslatma_tarihi']);

                                        $interval = $startTime->diff($endTime);
                                        $toplam_mola_saati += $interval->h + ($interval->days * 24);
                                        $toplam_mola_dakika += $interval->i;
                                    }

                                    $toplam_gecen_sure += $toplam_mola_saati > 0 ? 
                                        round(($toplam_mola_saati*60 + $toplam_mola_dakika)/60,1) : 
                                        round($toplam_mola_dakika/60, 1);

                                    $sql = "SELECT baslatma_tarihi,bitis_tarihi FROM `uretim_ariza_log` WHERE `planlama_id` IN(:planlama_idler)";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindValue('planlama_idler', implode($planlama_idler));
                                    $sth->execute();
                                    $arizalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    $toplam_ariza_saati = 0;
                                    $toplam_ariza_dakika = 0;
                                    foreach ($arizalar as $ariza) {
                                        $startTime = new DateTime($ariza['bitis_tarihi']);
                                        $endTime = new DateTime($ariza['baslatma_tarihi']);

                                        $interval = $startTime->diff($endTime);
                                        $toplam_ariza_saati += $interval->h + ($interval->days * 24);
                                        $toplam_ariza_dakika += $interval->i;
                                    }

                                    $toplam_gecen_sure += $toplam_ariza_saati > 0 ? 
                                        round(($toplam_ariza_saati*60 + $toplam_ariza_dakika)/60,1) : 
                                        round($toplam_ariza_dakika/60, 1);

                                    $sql = "SELECT baslatma_tarihi,bitis_tarihi FROM `uretim_bakim_log` WHERE `planlama_id` IN(:planlama_idler)";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindValue('planlama_idler', implode($planlama_idler));
                                    $sth->execute();
                                    $bakimlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    $toplam_bakim_saati = 0;
                                    $toplam_bakim_dakika = 0;
                                    foreach ($bakimlar as $bakim) {
                                        $startTime = new DateTime($bakim['bitis_tarihi']);
                                        $endTime = new DateTime($bakim['baslatma_tarihi']);

                                        $interval = $startTime->diff($endTime);
                                        $toplam_bakim_saati += $interval->h + ($interval->days * 24);
                                        $toplam_bakim_dakika += $interval->i;
                                    }

                                    $toplam_gecen_sure += $toplam_bakim_saati > 0 ? 
                                        round(($toplam_bakim_saati*60 + $toplam_bakim_dakika)/60,1,) : 
                                        round($toplam_bakim_dakika/60, 1);

                                    $sql = "SELECT baslatma_tarihi,bitis_tarihi FROM `uretim_yemek_mola_log` WHERE `planlama_id` IN(:planlama_idler)";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindValue('planlama_idler', implode($planlama_idler));
                                    $sth->execute();
                                    $yemekler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    $toplam_yemek_saati = 0;
                                    $toplam_yemek_dakika = 0;
                                    foreach ($yemekler as $yemek) {
                                        $startTime = new DateTime($yemek['bitis_tarihi']);
                                        $endTime = new DateTime($yemek['baslatma_tarihi']);

                                        $interval = $startTime->diff($endTime);
                                        $toplam_yemek_saati += $interval->h + ($interval->days * 24);
                                        $toplam_yemek_dakika += $interval->i;
                                    }

                                    $toplam_gecen_sure += $toplam_yemek_saati > 0 ? 
                                        round(($toplam_yemek_saati*60 + $toplam_yemek_dakika)/60,1) : 
                                        round($toplam_yemek_dakika/60, 1);


                                ?>
                                
                                <h3 class="p-2">
                                    Toplam Durma  (<?php echo number_format($toplam_gecen_sure,1, ',','.') ;?> Saat)
                                </h3>
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <h4><?php echo $toplam_mola_saati > 0 ? number_format(($toplam_mola_saati*60 + $toplam_mola_dakika)/60,1,',','.') : $toplam_mola_dakika ; ?></h4>
                                        <p> 
                                            <?php echo $toplam_mola_saati > 0 ? 'Saat':'Dakika'; ?><br> Mola
                                        </p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4><?php echo $toplam_yemek_saati > 0 ? 
                                            number_format(($toplam_yemek_saati*60 + $toplam_yemek_dakika)/60,1,',','.') : $toplam_yemek_dakika ; ?></h4>
                                        <p> 
                                            <?php echo $toplam_yemek_saati > 0 ? 'Saat':'Dakika'; ?><br> Yemek
                                        </p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4><?php echo $toplam_ariza_saati > 0 ? number_format(($toplam_ariza_saati*60 + $toplam_ariza_dakika)/60,1,',','.') : $toplam_ariza_dakika ; ?></h4>
                                        <p> 
                                            <?php echo $toplam_ariza_saati > 0 ? 'Saat':'Dakika'; ?><br> Arıza
                                        </p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4><?php echo $toplam_bakim_saati > 0 ? 
                                            number_format(($toplam_bakim_saati*60 + $toplam_bakim_dakika)/60,1,',','.') : $toplam_bakim_dakika ; ?></h4>
                                        <p> 
                                            <?php echo $toplam_bakim_saati > 0 ? 'Saat':'Dakika'; ?><br> Bakım
                                        </p>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 text-white bg-primary rounded-3">
                                <?php 
                                    $sql = "SELECT baslatma_tarih,bitirme_tarihi FROM `uretim_islem_tarihler` 
                                            WHERE `planlama_id` IN(:planlama_idler)";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindValue('planlama_idler', implode($planlama_idler));
                                    $sth->execute();
                                    $uretim_islem_tarihler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    $toplam_brut_saati = 0;
                                    $toplam_brut_dakika = 0;
                                    foreach ($uretim_islem_tarihler as $uretim_islem_tarih) {
                                        $startTime = new DateTime($uretim_islem_tarih['baslatma_tarih']);
                                        $endTime = new DateTime($uretim_islem_tarih['bitirme_tarihi']);

                                        $interval = $startTime->diff($endTime);
                                        $toplam_brut_saati += $interval->h + ($interval->days * 24);
                                        $toplam_brut_dakika += $interval->i;
                                    }
                                ?>
                                <h4 class="p-2">
                                    <?php 
                                        $brut_saat = $toplam_brut_saati > 0 ? 
                                            ($toplam_brut_saati*60 + $toplam_brut_dakika)/60 : 
                                            $toplam_brut_dakika/60;
                                    ?>
                                    Toplam Üretim Süresi (<?php echo number_format($brut_saat*60/ $siparis['adet'],2,'.',','); ?>dk)
                                </h4>
                                <div class="row">
                                    <div class="col-md-6 text-center">
                                        <h4>
                                            <?php echo number_format($brut_saat - $toplam_gecen_sure,1, ',','.') ;?>    
                                        </h4>
                                        <p> 
                                            Saat <br> Net Süre 
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-center">
                                        <h4><?php echo $toplam_brut_saati > 0 ? 
                                            number_format(($toplam_brut_saati*60 + $toplam_brut_dakika)/60,1,',','.') : 
                                            $toplam_brut_dakika ; ?></h4>
                                        <p> 
                                            <?php echo $toplam_brut_saati > 0 ? 'Saat':'Dakika'; ?><br> Brüt
                                        </p>
                                    </div>
                                    <br>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 text-white bg-primary rounded-3">
                                <h3 class="p-2">
                                    Toplam Fire
                                </h3>
                                <?php 
                                /*
                                    $sql = "SELECT tuketilen_veriler FROM uretilen_adetler WHERE planlama_id IN (:planlama_id)";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindValue('planlama_id', implode(',', $planlama_idler));
                                    $sth->execute();
                                    $tuketilen_datalar  = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    $fireler_sonuc      = [];
                                    foreach ($tuketilen_datalar as $tuketilen_veri) {
                                        $tuketilen_veriler  = json_decode($tuketilen_veri['tuketilen_veriler'], true); 
                                        $stok_kalemler      = isset($tuketilen_veriler['stok_kalemler'])        ? $tuketilen_veriler['stok_kalemler'] : [];    
                                        $fireler            = isset($tuketilen_veriler['fireler'])              ? $tuketilen_veriler['fireler'] : [];  
                                        foreach ($stok_kalemler as $key => $stok_kalem) {
                                            if(!isset($fireler_sonuc[$stok_kalem])) $fireler_sonuc[$stok_kalem] = 0;
                                            $fireler_sonuc[$stok_kalem] += intval($fireler[$key]);
                                        }
                                    }
                                    */
                                ?>
                                <?php //foreach ($fireler_sonuc as $stok_kalem => $fire ) { ?>
                                    <div class="row">
                                        <div class="col-md-5"><?php //echo $stok_kalem; ?> Fire:</div>
                                        <div class="col-md-7 text-end">
                                            <h6><?php //echo $fire; ?> Adet</h6>
                                        </div>
                                    </div>
                                <?php //} ?>

                            </div>
                        </div>
                    </div>


                    <div class="row ">
                        <div class="col-md-12">
                            <?php foreach ($siparisin_planlamalari as $key => $siparis_planlama) { ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Alt Ürün : 
                                            <span class="badge bg-secondary"><?php echo $siparis_planlama['isim']; ?></span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                            $departmanlar = json_decode($siparis_planlama['departmanlar'], true);
                                            //print_r($departmanlar);
                                        ?>
                                        <div class="accordion" id="accordionPanelsStayOpenExample">
                                            <?php foreach ($departmanlar as $asama => $departman_id) { ?>
                                                <?php 
                                                    $sql = "SELECT departman FROM `departmanlar` WHERE id = :id";   
                                                    $sth = $conn->prepare($sql);
                                                    $sth->bindParam('id', $departman_id);
                                                    $sth->execute();
                                                    $departman = $sth->fetch(PDO::FETCH_ASSOC); 
                                                ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                                        data-bs-target="#panelsStayOpen-collapse-<?php echo $asama.'-'.$key;?>" aria-expanded="true" 
                                                        aria-controls="panelsStayOpen-collapse-<?php echo $asama.'-'.$key;?>">
                                                            <?php echo ($asama +1).'. '.$departman['departman']; ?>
                                                        </button>
                                                    </h2>
                                                    <div id="panelsStayOpen-collapse-<?php echo $asama.'-'.$key;?>" class="accordion-collapse collapse <?php echo $asama == 0 ? 'show':'';?>">
                                                        <div class="accordion-body">
                                                            <?php 
                                                                $sql = "SELECT uretilen_adetler.uretilen_adet,  
                                                                    uretilen_adetler.baslangic_tarihi,uretilen_adetler.bitis_tarihi,uretilen_adetler.tuketilen_veriler,
                                                                    personeller.ad, personeller.soyad,
                                                                    makinalar.makina_adi, makinalar.makina_modeli
                                                                    FROM `uretilen_adetler` 
                                                                    JOIN personeller ON personeller.id = uretilen_adetler.personel_id
                                                                    JOIN makinalar ON makinalar.id = uretilen_adetler.makina_id
                                                                    WHERE uretilen_adetler.planlama_id = :planlama_id AND 
                                                                    uretilen_adetler.departman_id = :departman_id AND 
                                                                    uretilen_adetler.asama = :asama";
                                                                $sth = $conn->prepare($sql);
                                                                $sth->bindParam('planlama_id', $siparis_planlama['id']);
                                                                $sth->bindParam('departman_id', $departman_id);
                                                                $sth->bindParam('asama', $asama);
                                                                $sth->execute();
                                                                $uretilen_adetler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                            ?>
                                                            <div class="table-responsive">
                                                                <table id="myTable" class="table table-striped table-bordered" style="font-size:12px !important;">
                                                                    <thead class="table-primary">
                                                                        <tr>
                                                                            <th>Part</th>
                                                                            <th>Personel Ad Soyad</th>
                                                                            <th>Makine</th>
                                                                            <th class="text-end">Adet</th>
                                                                            <!--
                                                                            <th>Başlangıç Tarihi</th>
                                                                            <th>Bitiş Tarihi</th>
                                            -->
                                                                            <th class="text-end">Geçen Süre</th>

                                                                            <?php if( isset($uretilen_adetler[0]) ){ ?>
                                                                                <?php $tuketilen_veriler  = json_decode($uretilen_adetler[0]['tuketilen_veriler'], true);?>
                                                                                <?php foreach($tuketilen_veriler['stok_kalemler'] as $stok_kalem) { ?>
                                                                                    <th class="text-end"><?php echo $stok_kalem; ?></th>
                                                                                    <th class="text-end">Fire</th>
                                                                                <?php }?>
                                                                            <?php } ?>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php 
                                                                            $toplam_adet = 0; 
                                                                            $toplam_gecen_sure = 0;
                                                                        ?>
                                                                        <?php foreach ($uretilen_adetler as $key => $uretilen_adet) { ?>
                                                                            <?php 
                                                                                $tuketilen_veriler  = json_decode($uretilen_adet['tuketilen_veriler'], true);
                                                                                $birimler           = isset($tuketilen_veriler['birimler'])             ? $tuketilen_veriler['birimler'] : [];    
                                                                                $stok_kalemler      = isset($tuketilen_veriler['stok_kalemler'])        ? $tuketilen_veriler['stok_kalemler'] : [];    
                                                                                $tuketilen_miktarlar= isset($tuketilen_veriler['tuketilen_miktarlar'])  ? $tuketilen_veriler['tuketilen_miktarlar'] : [];    
                                                                                $fireler            = isset($tuketilen_veriler['fireler'])              ? $tuketilen_veriler['fireler'] : [];    
                                                                            ?>
                                                                            <tr>
                                                                                <th><?php echo $key + 1;?></th>
                                                                                <td><?php echo $uretilen_adet['ad'].' '.$uretilen_adet['soyad']; ?></td>
                                                                                <td><?php echo $uretilen_adet['makina_adi'].' '.$uretilen_adet['makina_modeli']; ?></td>
                                                                                <td class="text-end"><?php echo number_format($uretilen_adet['uretilen_adet'],0,'',','); ?></td>
                                                                                <!--
                                                                                <td><?php echo date('d-m-Y H:i:s', strtotime($uretilen_adet['baslangic_tarihi']));?></td>
                                                                                <td><?php echo date('d-m-Y H:i:s', strtotime($uretilen_adet['bitis_tarihi']));?></td>
                                                                                -->
                                                                                <td class="text-end">
                                                                                    <?php 
                                                                                        $startTime = new DateTime($uretilen_adet['baslangic_tarihi']);
                                                                                        $endTime = new DateTime($uretilen_adet['bitis_tarihi']);
                                        
                                                                                        $interval = $startTime->diff($endTime);
                                                                                        $gecen_saat = $interval->h + ($interval->days * 24) + $interval->i/60;

                                                                                        echo number_format($gecen_saat, 1,',','.');
                                                                                    ?>
                                                                                    Saat
                                                                                </td>
                                                                                <?php foreach ($stok_kalemler  as $key => $stok_kalem) { ?>
                                                                                    <td class="text-end"><?php echo $tuketilen_miktarlar[$key].' '.$birimler[$key]; ?></td>
                                                                                    <td class="text-end"><?php echo $fireler[$key].' '.$birimler[$key]; ?></td>
                                                                                <?php }?>
                                                                                
                                                                            </tr>
                                                                            <?php 
                                                                                $toplam_adet += $uretilen_adet['uretilen_adet'];
                                                                                $toplam_gecen_sure += $gecen_saat;
                                                                            ?>
                                                                        <?php }?>
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr class="table-info">
                                                                            <th>Toplam</th>
                                                                            <th>-</th>
                                                                            <th>-</th>
                                                                            <th class="text-end"><?php echo number_format($toplam_adet, 0, '',','); ?></th>
                                                                            <!--
                                                                            <th>-</th>
                                                                            <th>-</th>
                                                                            -->
                                                                            <th class="text-end">
                                                                                <?php echo number_format($toplam_gecen_sure, 1,',','.'); ?> Saat
                                                                            </th>
                                                                            <?php if( isset($uretilen_adetler[0]) ){ ?>
                                                                                <?php $tuketilen_veriler  = json_decode($uretilen_adetler[0]['tuketilen_veriler'], true);?>
                                                                                <?php foreach($tuketilen_veriler['stok_kalemler'] as $stok_kalem) { ?>
                                                                                    <th>-</th>
                                                                                    <th>-</th>
                                                                                <?php }?>
                                                                            <?php } ?>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php }?>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php  
    include_once "include/uyari_session_oldur.php"; 
?> 