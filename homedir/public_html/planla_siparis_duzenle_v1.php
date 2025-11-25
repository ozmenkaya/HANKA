<?php
    require_once "include/oturum_kontrol.php";

    $siparis_id     = isset($_GET['siparis_id']) ? intval($_GET['siparis_id']) : 0;
    
    $sth = $conn->prepare('SELECT siparisler.*,
                            ulkeler.baslik AS ulke_adi,
                            sehirler.baslik AS sehir_adi, 
                            ilceler.baslik AS ilce_adi,
                            turler.tur,
                            odeme_tipleri.odeme_sekli,
                            musteri.marka
                            FROM siparisler 
                            JOIN ulkeler ON ulkeler.id = siparisler.ulke_id
                            JOIN sehirler ON sehirler.id = siparisler.sehir_id
                            JOIN ilceler ON ilceler.id = siparisler.ilce_id
                            JOIN turler ON turler.id = siparisler.tur_id  
                            JOIN odeme_tipleri ON odeme_tipleri.id = siparisler.odeme_sekli_id
                            JOIN `musteri` ON `musteri`.id = siparisler.musteri_id
                            WHERE siparisler.id = :id AND siparisler.firma_id = :firma_id');
    $sth->bindParam('id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($siparis)){
        include "include/yetkisiz.php"; die();
    }

    $sql = "SELECT * FROM `planlama` WHERE siparis_id = :siparis_id AND firma_id = :firma_id ORDER BY id DESC";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    //echo "<pre>"; print_r($planlamalar); exit;
    if(empty($planlamalar)){
        include "include/yetkisiz.php"; die();
    }

    $sql = "SELECT * FROM `departmanlar` 
            WHERE firma_id = :firma_id ORDER BY `departmanlar`.`departman` ASC";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    
    $sql = "SELECT id,firma_id, firma_adi FROM `tedarikciler` 
            WHERE firma_id = :firma_id AND fason = 'evet' 
            ORDER BY `tedarikciler`.`firma_adi` ASC";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $tedarikciler = $sth->fetchAll(PDO::FETCH_ASSOC);
    //echo "<pre>"; print_r($tedarikciler); exit;

    $sql = "SELECT * FROM `siparis_dosyalar` WHERE siparis_id = :siparis_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id',$siparis_id);
    $sth->execute();
    $siparis_resimler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
        <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
        <style>
            .button-yuvarlak{
                border-radius:50%;
            }
        </style> -->
        <div class="row mt-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-bag-shopping"></i> Planlama Düzenle İşlemi 
                    </h5>
                    <div>
                        Sipariş Kodu:   <b class="text-danger text-decoration-underline"><?php echo $siparis['siparis_no']; ?></b> -
                        Firma Adı :     <b class="text-danger text-decoration-underline"><?php echo $siparis['marka']?></b> - 
                        İşin Adı:       <b class="text-danger text-decoration-underline"><?php echo $siparis['isin_adi']?> </b>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row">
                        <div class="col-md-2" >
                            <?php 
                                $veriler = json_decode($siparis['veriler'],true);
                            ?>
                            <?php if($siparis['tip_id'] == TEK_URUN){?>
                                <?php $veri = $veriler; ?>
                                <ul class="list-group mb-2">
                                    <li class="list-group-item list-group-item-success fw-bold" aria-current="true">1. Alt Ürün</li>
                                    <li class="list-group-item"><b>İsim:</b> <?php echo $veri['isim']; ?></li>
                                    <li class="list-group-item"><b>Miktar:</b> <?php echo number_format($veri['miktar'],0,'',','); ?></li>
                                    <li class="list-group-item">
                                        <b>Birim Fiyat:</b> <?php echo number_format($veri['birim_fiyat'],2,',','.'); ?>
                                    </li>
                                    <li class="list-group-item"><b>KDV: </b> %<?php echo $veri['kdv']; ?></li>
                                    <?php 
                                        $sth = $conn->prepare('SELECT * FROM `birimler`  WHERE id = :id');
                                        $sth->bindParam('id', $veri['birim_id']);
                                        $sth->execute();
                                        $birim = $sth->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <li class="list-group-item"><b>Birim:</b> <?php echo $birim['ad']; ?></li>
                                    <li class="list-group-item">
                                        <b>Numune:</b> 
                                        <?php if($veri['numune'] == 1){?> 
                                            <span class="badge text-bg-success">VAR</span>
                                        <?php }else{?> 
                                            <span class="badge text-bg-danger">YOK</span>
                                        <?php }?>
                                    </li>
                                    <li class="list-group-item"><b>Açıklama:</b> <?php echo $veri['aciklama']; ?></li>
                                    <?php if($veri['form']){?>
                                        <?php foreach ($veri['form'] as $key => $value) { ?>
                                            <?php if(!empty($value)){ ?>
                                                <li class="list-group-item list-group-item-warning"><b><?php echo $key; ?>:</b> <?php echo $value; ?></li>
                                            <?php } ?>
                                        <?php }?>
                                    <?php } ?>
                                </ul>

                                <div class="border rounded mb-2 px-1">
                                    <?php foreach ($siparis_resimler as $siparis_dosya) { ?>
                                        <?php 
                                            $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                        ?>
                                        <?php if($uzanti == 'pdf'){ ?>
                                            <a class="text-decoration-none" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" download="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>">
                                                <img src="dosyalar/pdf.png" 
                                                    class="rounded img-thumbnai object-fit-fill" 
                                                    alt="" 
                                                    style="height:50px; min-height:50px; width:50px;"
                                                    
                                                > 
                                            </a>
                                        <?php }else{?>
                                            <a class="text-decoration-none example-image-link" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                        data-lightbox="example-set" data-title="">
                                                <img src="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                    class="rounded img-thumbnai border border-secondary-subtle object-fit-fill mb-1 mt-1" 
                                                style="height:50px; min-height:50px; width:50px;">
                                            </a>
                                        <?php } ?>
                                        <?php if(empty($siparis_resimler)){?>
                                            <h6 class="text-danger fw-bold">Dosya Yok</h6>
                                        <?php } ?>
                                    <?php } ?>
                                </div> 
                            <?php }else if(in_array($siparis['tip_id'], [GRUP_URUN_TEK_FIYAT, GRUP_URUN_AYRI_FIYAT])){?>
                                <?php foreach($veriler as $index => $veri){ ?>
                                    <ul class="list-group mb-2">
                                        <li class="list-group-item list-group-item-success fw-bold" aria-current="true"><?php echo $index+1;?>. Alt Ürün</li>         
                                        <li class="list-group-item">
                                            <b>İsim:</b> <?php echo $veri['isim']; ?>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Miktar:</b> <?php echo number_format($veri['miktar'],0,'',','); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Birim Fiyat:</b> <?php echo number_format($veri['birim_fiyat'],2,',','.'); ?>
                                        </li>
                                        <li class="list-group-item"><b>KDV: </b> %<?php echo $veri['kdv']; ?></li>
                                        <?php 
                                            $sth = $conn->prepare('SELECT * FROM `birimler`  WHERE id = :id');
                                            $sth->bindParam('id', $veri['birim_id']);
                                            $sth->execute();
                                            $birim = $sth->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <li class="list-group-item"><b>Birim:</b> <?php echo $birim['ad']; ?></li>
                                        <li class="list-group-item">
                                            <b>Numune:</b>
                                            <?php if($veri['numune'] == 1){?> 
                                                <span class="badge text-bg-success">VAR</span>
                                            <?php }else{?> 
                                                <span class="badge text-bg-danger">YOK</span>
                                            <?php }?>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Açıklama:</b> <?php echo $veri['aciklama']; ?>
                                        </li>
                                        
                                        <?php if(isset($veri['form'])){ ?>
                                            <?php foreach ($veri['form'] as $key => $value) { ?>
                                                <?php if(!empty($value)){ ?>
                                                    <li class="list-group-item list-group-item-warning"><b>
                                                        <?php echo $key; ?>:</b> <?php echo $value; ?>
                                                    </li>
                                                <?php } ?>
                                            <?php }?>
                                        <?php }?>
                                    </ul>

                                    <div class="border rounded mb-2 px-1">
                                        <?php $resim_varmi = false; ?>
                                        <?php foreach ($siparis_resimler as $siparis_dosya) { ?>
                                            <?php if($index == $siparis_dosya['alt_urun_index']){ ?>
                                                <?php $resim_varmi = true; ?>
                                                <?php 
                                                    $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                                ?>
                                                <?php if($uzanti == 'pdf'){ ?>
                                                    <a class="text-decoration-none" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" download="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>">
                                                        <img src="dosyalar/pdf.png" 
                                                            class="rounded img-thumbnai object-fit-fill" 
                                                            alt="" 
                                                            style="height:50px; min-height:50px; width:50px;"
                                                            
                                                        > 
                                                    </a>
                                                <?php }else{?>
                                                    <a class="text-decoration-none example-image-link-<?php echo $index; ?>" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                                data-lightbox="example-set-<?php echo $index; ?>" data-title="">
                                                        <img src="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                            class="rounded img-fluid object-fit-fill border border-secondary-subtle mb-1 mt-1" 
                                                            style="height:50px; min-height:50px; width:50px;">
                                                    </a>
                                                <?php }?>
                                            <?php }?>
                                        <?php } ?> 
                                        <?php if(!$resim_varmi){?>
                                            <h6 class="text-danger fw-bold">Dosya Yok</h6>
                                        <?php }?>
                                    </div>
                                <?php }?>
                            <?php }?>

                            <ul class="list-group mb-2">
                                <li class="list-group-item active fw-bold" aria-current="true">Sipariş Bilgileri</li>
                                <li class="list-group-item">
                                    <strong>Türü :</strong><?php echo $siparis['tur']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Adet :</strong><?php echo number_format($siparis['adet'], 0, '','.'); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Teslimat Adresi :</strong><?php echo $siparis['teslimat_adresi']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Teslimat Ülkesi :</strong><?php echo $siparis['ulke_adi']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Teslimat Şehiri :</strong><?php echo $siparis['sehir_adi']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Teslimat İlçesi :</strong><?php echo $siparis['ilce_adi']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Termin Tarihi :</strong><?php echo date('d-m-Y', strtotime($siparis['termin'])); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Üretim Tarihi :</strong><?php echo date('d-m-Y',strtotime($siparis['uretim'])); ?>
                                </li>
                                <?php   
                                    $sth = $conn->prepare('SELECT personeller.ad, personeller.soyad FROM `siparisler` JOIN personeller ON siparisler.musteri_temsilcisi_id = personeller.id 
                                                            WHERE siparisler.id = :id');
                                    $sth->bindParam('id', $siparis['id']);
                                    $sth->execute();
                                    $musteri_temsilci = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <li class="list-group-item">
                                    <strong>M. Temsilcisi :</strong><?php echo $musteri_temsilci['ad'].' '.$musteri_temsilci['soyad'] ; ?>
                                </li>
                                <?php   
                                    $sth = $conn->prepare('SELECT musteri.marka FROM `siparisler` 
                                                            JOIN musteri ON siparisler.musteri_id = musteri.id 
                                                            WHERE siparisler.id = :id');
                                    $sth->bindParam('id', $siparis['id']);
                                    $sth->execute();
                                    $musteri = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <li class="list-group-item">
                                    <strong>Müşteri : </strong><?php echo $musteri['marka'] ; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Vade Tarihi : </strong><?php echo date('d-m-Y', strtotime($siparis['vade'])); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Fiyat :</strong><?php echo number_format($siparis['fiyat'], 2, ',','.'); ?> <?php echo $siparis['para_cinsi']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Ödeme Şekli :</strong><?php echo $siparis['odeme_sekli']; ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-10">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <h5>
                                                <i class="fa-brands fa-product-hunt"></i> Alt Ürünler
                                            </h5>
                                        </div>
                                        <!--
                                        <div class="col-md-2 text-end">
                                            <button class="btn btn-sm btn-primary mb-2 button-yuvarlak" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Alt Ürün Ekle" 
                                                id="alt-urun-ekle">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>  
                                        -->
                                    </div>
                                </div>
                                <div class="card-body" id="alt-urunler">
                                    <form action="/index.php?url=planlama_db_islem" method="POST" id="planlama-form"">       
                                        <?php foreach ($planlamalar as $planlamaIndex => $planlama) { ?>
                                            <?php 
                                                $altUrunId = count($planlamalar)- $planlamaIndex;    
                                            ?>    

                                            <input type="hidden" name="alt_urun_<?php echo $altUrunId; ?>[planlama_id]" value="<?php echo $planlama['id'];?>">
                                            <div class="card mb-2 alt-urun" id="alt-urun-<?php echo $altUrunId; ?>" data-alt-urun-id="<?php echo $altUrunId; ?>">
                                                <div class="card-header">
                                                    <div class="row">
                                                        <div class="col-md-10">
                                                            <div class="lead fw-bold text-danger alt-urun-sayisi-uyari">
                                                                <?php echo $altUrunId; ?>. Alt Ürün Bilgileri
                                                            </div>
                                                        </div>
                                                        <!--
                                                        <div class="col-md-2 text-end">
                                                            <button class="btn btn-sm btn-danger alt-urun-cikar button-yuvarlak" 
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom"
                                                                data-bs-custom-class="custom-tooltip"
                                                                data-bs-title="Alt Ürün Çıkar" 
                                                                data-alt-urun-id="<?php echo $altUrunId; ?>"
                                                            >
                                                                <i class="fa-solid fa-minus"></i>
                                                            </button>
                                                        </div>
                                                        -->
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row mb-2">
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <button class="btn btn-outline-success departmanlari-getir" type="button"
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-placement="bottom"
                                                                    data-bs-custom-class="custom-tooltip"
                                                                    data-bs-title="Alt Aşama Ekle"
                                                                    data-alt-urun-id="<?php echo $altUrunId; ?>"
                                                                >
                                                                    <i class="fa-solid fa-plus"></i>
                                                                </button>
                                                                <input type="text"  class="form-control isim"  placeholder="İsim.." name="alt_urun_<?php echo $altUrunId; ?>[isim]" value="<?php echo $planlama['isim']; ?>"   required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <span class="input-group-text">Ü. Adet</span>
                                                                <input type="number"  class="form-control uretilecek-adet"  name="alt_urun_<?php echo $altUrunId; ?>[uretilecek_adet]" value="<?php echo $planlama['uretilecek_adet']; ?>" placeholder="Üretilecek Adet.." required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="alt-asamalar sortable-<?php echo $altUrunId; ?>">
                                                        <?php 
                                                            $secilmis_departmanlar  = json_decode($planlama['departmanlar'], true);
                                                            $secilmis_departmanlar  = array_reverse($secilmis_departmanlar);

                                                            $secilmis_fason_durumlar = json_decode($planlama['fason_durumlar'], true);
                                                            $secilmis_fason_durumlar = array_reverse($secilmis_fason_durumlar);
                                                        
                                                            $secilmis_adetler        = json_decode($planlama['adetler'], true);
                                                            $secilmis_adetler        = array_reverse($secilmis_adetler);

                                                            $secilmis_sureler        = json_decode($planlama['sureler'], true);
                                                            $secilmis_sureler        = array_reverse($secilmis_sureler);

                                                            $secilmis_detaylar       = json_decode($planlama['detaylar'], true);
                                                            $secilmis_detaylar       = array_reverse($secilmis_detaylar);

                                                            $secilmis_makinalar     = json_decode($planlama['makinalar'], true);
                                                            $secilmis_makinalar     = array_reverse($secilmis_makinalar);

                                                            $secilmis_fason_tedarikciler = json_decode($planlama['fason_tedarikciler'], true);
                                                            $secilmis_fason_tedarikciler = array_reverse($secilmis_fason_tedarikciler);


                                                            $secilmis_alt_arsivler      = json_decode($planlama['arsiv_altlar'], true);
                                                            $secilmis_alt_arsivler      = array_reverse($secilmis_alt_arsivler);
                                                            //print_r($secilmis_alt_arsivler);
                                                            $secilmis_stok_alt_kalemler = json_decode($planlama['stok_alt_kalemler'], true);
                                                            $secilmis_stok_alt_kalemler = array_reverse($secilmis_stok_alt_kalemler);

                                                            $secilmis_stok_alt_depo_adetler = json_decode($planlama['stok_alt_depo_adetler'], true);
                                                            $secilmis_stok_alt_depo_adetler = array_reverse($secilmis_stok_alt_depo_adetler);


                                                            $secilmis_stok_alt_depolar  = json_decode($planlama['stok_alt_depolar'], true);
                                                            $secilmis_stok_alt_depolar  = array_reverse($secilmis_stok_alt_depolar);     
                                                            
                                                        ?>
                                                        <?php foreach ($secilmis_departmanlar as $departman_index =>$departman_id) { ?>
                                                            <?php 
                                                                $altAsamaId = count($secilmis_departmanlar)-$departman_index;
                                                            ?>
                                                            
                                                            <div class="card bg-light mb-2 alt-asama ui-sortable-handle <?php echo  $planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1 ? 'border-danger border border-3':'';?>" 
                                                                id="alt-asama-<?php echo $altAsamaId;?>"
                                                            >
                                                                <div class="card-header">
                                                                    <div class="row mb-2">
                                                                        <div class="col-md-10 lead fw-bold text-success">
                                                                            <i class="fa-solid fa-arrows-up-down-left-right"></i>
                                                                            <span class="alt-urunun-kacinci-asama-yazisi"><?php echo $altAsamaId;?></span>. Aşama 
                                                                        </div>
                                                                        <div class="col-md-2 text-end">
                                                                            <?php if($altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                
                                                                                <i class="fa-solid fa-circle-exclamation fa-2x text-info"
                                                                                    data-bs-toggle="tooltip" 
                                                                                    data-bs-placement="bottom"
                                                                                    data-bs-custom-class="custom-tooltip"
                                                                                    data-bs-title="<b class='text-danger'>İşlemde veya Bitmiş Olduğu İçin İşlem Yapılamaz</b>"
                                                                                    data-bs-html="true"
                                                                                ></i>
                                                                            <?php }else{?> 
                                                                                <button class="btn btn-sm btn-danger alt-asama-cikar button-yuvarlak"  
                                                                                    data-bs-toggle="tooltip" 
                                                                                    data-bs-placement="bottom"
                                                                                    data-bs-custom-class="custom-tooltip"
                                                                                    data-bs-title="Alt Aşama Çıkar"
                                                                                    >
                                                                                    <i class="fa-solid fa-minus"></i> 
                                                                                </button>    
                                                                            <?php } ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body alt-asama-veriler">
                                                                    <div class="row mb-2 departman-fason-durum">
                                                                        <div class="col-md-6 departman-kolon">
                                                                            <div class="input-group flex-nowrap">
                                                                                <span class="input-group-text">Departman</span>
                                                                                <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                    <select  class="form-select departman" disabled  >
                                                                                        <?php foreach ($departmanlar as $departman) { ?>
                                                                                            <option value="<?php echo $departman['id']; ?>" 
                                                                                                <?php echo $departman['id'] == $departman_id ? 'selected':'';?>
                                                                                            >
                                                                                                <?php echo $departman['departman']; ?>
                                                                                            </option>
                                                                                        <?php }?>
                                                                                    </select>
                                                                                    <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[departman][]" value="<?php echo $departman_id; ?>">
                                                                                <?php }else{?> 
                                                                                    <select name="alt_urun_<?php echo $altUrunId;?>[departman][]" class="form-select departman-degistir departman" required  >
                                                                                        <?php foreach ($departmanlar as $departman) { ?>
                                                                                            <option value="<?php echo $departman['id']; ?>" 
                                                                                                <?php echo $departman['id'] == $departman_id ? 'selected':'';?>
                                                                                            >
                                                                                                <?php echo $departman['departman']; ?>
                                                                                            </option>
                                                                                        <?php }?>
                                                                                    </select>
                                                                                <?php }?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6 fason-kolon"> 
                                                                            <div class="input-group flex-nowrap">
                                                                                <span class="input-group-text">Fason</span>
                                                                                <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                    <select class="form-select fason_durum" disabled>
                                                                                        <option value="0" <?php echo $secilmis_fason_durumlar[$departman_index] == 0 ? 'selected': ''; ?>>Hayır</option>
                                                                                        <option value="1" <?php echo $secilmis_fason_durumlar[$departman_index] == 1 ? 'selected': ''; ?>>Evet</option>
                                                                                    </select>
                                                                                    <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[fason_durum][]" value="<?php echo $secilmis_fason_durumlar[$departman_index]; ?>">
                                                                                <?php }else{?>
                                                                                    <select class="form-select fason_durum" name="alt_urun_<?php echo $altUrunId;?>[fason_durum][]">
                                                                                        <option value="0" <?php echo $secilmis_fason_durumlar[$departman_index] == 0 ? 'selected': ''; ?>>Hayır</option>
                                                                                        <option value="1" <?php echo $secilmis_fason_durumlar[$departman_index] == 1 ? 'selected': ''; ?>>Evet</option>
                                                                                    </select>
                                                                                <?php } ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>                                                     
                                                                    <div class="row mb-2 adet-sure-detay">
                                                                        <?php 
                                                                            $sql = "SELECT birimler.ad FROM `departman_planlama` 
                                                                            JOIN birimler ON birimler.id = departman_planlama.birim_id
                                                                            WHERE departman_planlama.departman_id = :departman_id";
                                                                            $sth = $conn->prepare($sql);
                                                                            $sth->bindParam('departman_id', $departman_id);
                                                                            $sth->execute();
                                                                            $birim = $sth->fetch(PDO::FETCH_ASSOC);

                                                                        ?>
                                                                        <div class="col-md-3">
                                                                            <div class="input-group">
                                                                                <span class="input-group-text adet-birim" >
                                                                                    <?php echo $birim['ad']; ?>
                                                                                </span>
                                                                                <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                    <input type="number"  class="form-control form-control-sm adet" 
                                                                                        value="<?php echo $secilmis_adetler[$departman_index]; ?>" 
                                                                                        name="alt_urun_<?php echo $altUrunId;?>[adet][]" min="0"   disabled
                                                                                    >
                                                                                    <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[adet][]" value="<?php echo $secilmis_adetler[$departman_index]; ?>">
                                                                                <?php }else{ ?>
                                                                                    <input type="number"  class="form-control form-control-sm adet" 
                                                                                        value="<?php echo $secilmis_adetler[$departman_index]; ?>" 
                                                                                        name="alt_urun_<?php echo $altUrunId;?>[adet][]" min="0"   
                                                                                    >
                                                                                <?php } ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">
                                                                                    Süre (saat)
                                                                                </span>
                                                                                <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                    <input type="number" step="0.01" class="form-control form-control-sm sure"
                                                                                        value="<?php echo $secilmis_sureler[$departman_index]; ?>"
                                                                                        name="alt_urun_<?php echo $altUrunId;?>[sure][]" min="0" disabled
                                                                                    >
                                                                                    <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[sure][]" value="<?php echo $secilmis_sureler[$departman_index]; ?>">
                                                                                <?php }else{?> 
                                                                                    <input type="number" step="0.01" class="form-control form-control-sm sure"
                                                                                        value="<?php echo $secilmis_sureler[$departman_index]; ?>"
                                                                                        name="alt_urun_<?php echo $altUrunId;?>[sure][]" min="0"
                                                                                    >
                                                                                <?php }?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">
                                                                                    Detay
                                                                                </span>
                                                                                <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                    <input type="text" class="form-control form-control-sm detay" 
                                                                                        value="<?php echo $secilmis_detaylar[$departman_index]; ?>" 
                                                                                        name="alt_urun_<?php echo $altUrunId;?>[detay][]" disabled
                                                                                    >
                                                                                    <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[detay][]" value="<?php echo $secilmis_detaylar[$departman_index]; ?>" >
                                                                                <?php }else{?> 
                                                                                    <input type="text" class="form-control form-control-sm detay" 
                                                                                        value="<?php echo $secilmis_detaylar[$departman_index]; ?>" 
                                                                                        name="alt_urun_<?php echo $altUrunId;?>[detay][]"
                                                                                    >
                                                                                <?php }?>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <?php if($secilmis_fason_durumlar[$departman_index] == 0){?>               
                                                                        <div class="row mb-2 makinalar">
                                                                            <div class="col-md-6">
                                                                                <div class="input-group">
                                                                                    <span class="input-group-text">
                                                                                        Makina
                                                                                    </span> 
                                                                                    <?php 
                                                                                        $sql = 'SELECT id,makina_adi, makina_modeli 
                                                                                                FROM makinalar WHERE firma_id = :firma_id AND departman_id = :departman_id';
                                                                                        $sth = $conn->prepare($sql);
                                                                                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                                                        $sth->bindParam('departman_id', $departman_id);
                                                                                        $sth->execute();
                                                                                        $departman_makinalar = $sth->fetchAll(PDO::FETCH_ASSOC); 
                                                                                    ?>
                                                                                    <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                        <select class="form-select makina" 
                                                                                            name="alt_urun_<?php echo $altUrunId;?>[makina][]" disabled>
                                                                                            <?php foreach ($departman_makinalar as $departman_makina_index => $departman_makina) { ?>
                                                                                                <option value="<?php echo $departman_makina['id']; ?>"
                                                                                                    <?php echo $departman_makina['id'] == $secilmis_makinalar[$departman_index] ? 'selected':'';?>>
                                                                                                    <?php echo ($departman_makina_index +1).'. '.$departman_makina['makina_adi'].' '.$departman_makina['makina_modeli']; ?>
                                                                                                </option>
                                                                                            <?php }?>
                                                                                        </select>
                                                                                        <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[makina][]" value="<?php echo $secilmis_makinalar[$departman_index];?>">
                                                                                    <?php }else{?> 
                                                                                        <select class="form-select makina <?php echo count($departman_makinalar) == 0 ? 'is-invalid':'';?>" 
                                                                                            name="alt_urun_<?php echo $altUrunId;?>[makina][]" required>
                                                                                            <?php foreach ($departman_makinalar as $departman_makina_index => $departman_makina) { ?>
                                                                                                <option value="<?php echo $departman_makina['id']; ?>"
                                                                                                    <?php echo $departman_makina['id'] == $secilmis_makinalar[$departman_index] ? 'selected':'';?>>
                                                                                                    <?php echo ($departman_makina_index +1).'. '.$departman_makina['makina_adi'].' '.$departman_makina['makina_modeli']; ?>
                                                                                                </option>
                                                                                            <?php }?>
                                                                                        </select>
                                                                                    <?php }?>
                                                                                    <div class="invalid-feedback fw-bold">
                                                                                        Bu Departmanda Makina Yoktur
                                                                                        <a class="btn btn-sm btn-warning text-white fw-bold">
                                                                                            <i class="fa-solid fa-building"></i> Makina Ekle
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-2 tedarikciler">
                                                                            <input type="hidden" class="fason_tedarikci"  name="alt_urun_<?php echo $altUrunId;?>[fason_tedarikci][]"  value="0">
                                                                        </div>
                                                                    <?php }else{?>
                                                                        <div class="row mb-2 makinalar">
                                                                            <input type="hidden" class="fason_tedarikci"  name="alt_urun_<?php echo $altUrunId;?>[makina][]"  value="0">
                                                                        </div>
                                                                        <div class="row mb-2 tedarikciler">
                                                                            <div class="col-md-2">
                                                                                <span class="input-group-text">
                                                                                    Tedarikçi
                                                                                </span> 
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <select class="form-select js-example-basic-single fason_tedarikci" 
                                                                                    name="alt_urun_<?php echo $altUrunId;?>[fason_tedarikci][]" required 
                                                                                >
                                                                                    <?php foreach ($tedarikciler as $tedarikci) { ?>
                                                                                        <option value="<?php echo $tedarikci['id']; ?>" 
                                                                                            <?php echo $tedarikci['id'] == $secilmis_fason_tedarikciler[$departman_index] ? 'selected':''; ?>
                                                                                        >
                                                                                            <?php echo $tedarikci['firma_adi']; ?>
                                                                                        </option>
                                                                                    <?php }?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    <?php }?>

                                                                    <div class="arsivler">
                                                                        <?php 
                                                                            $sql = "SELECT * FROM `arsiv_kalemler` WHERE firma_id = :firma_id AND departman_id = :departman_id";
                                                                            $sth = $conn->prepare($sql);
                                                                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                                            $sth->bindParam('departman_id', $departman_id);
                                                                            $sth->execute();
                                                                            $arsivler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                                                        
                                                                            foreach ($arsivler as $index=>$arsiv) {
                                                                                $sql = "SELECT * FROM `arsiv_altlar` WHERE arsiv_id = :arsiv_id AND siparis_id = :siparis_id";
                                                                                $sth = $conn->prepare($sql);
                                                                                $sth->bindParam('arsiv_id', $arsiv['id']);
                                                                                $sth->bindParam('siparis_id', $_GET['siparis_id']);
                                                                                $sth->execute();
                                                                                $alt_arsivler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                                                if(empty($alt_arsivler)){ 
                                                                                    unset($arsivler[$index]);
                                                                                }
                                                                                else{
                                                                                    $arsivler[$index]['alt_arsivler'] = $alt_arsivler;
                                                                                }
                                                                            }
                                                                        ?>

                                                                        <?php foreach ($arsivler as $arsivIndex => $arsiv) { ?>
                                                                            <div class="row mb-2 alt-arsiv">
                                                                                <div class="col-md-6">
                                                                                    <div class="input-group">
                                                                                        <span class="input-group-text">
                                                                                            <strong class="text-danger"><?php echo $arsivIndex + 1; ?>. Arşiv - </strong> 
                                                                                            <?php echo $arsiv['arsiv'];?>
                                                                                        </span> 
                                                                                        <select class="form-select alt_arsiv"
                                                                                            name="alt_urun_<?php echo $altUrunId;?>[alt_arsiv][<?php echo $altAsamaId; ?>][]"
                                                                                        >
                                                                                            <option value="0">Seçiniz</option>
                                                                                            <?php foreach ($arsivler[$arsivIndex]['alt_arsivler'] as $key => $arsivAlt) { ?>
                                                                                                <option value="<?php echo $arsivAlt['id'];?>" 
                                                                                                <?php echo $secilmis_alt_arsivler[$departman_index][$arsivIndex] == $arsivAlt['id'] ? 'selected':''; ?>
                                                                                                >
                                                                                                    <?php echo $arsivAlt['kod'].' - '.$arsivAlt['ebat'].' - '.$arsivAlt['detay'];?>
                                                                                                </option>
                                                                                            <?php }?>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6 alt-arsiv-resimler">
                                                                                    <?php 
                                                                                        $sth = $conn->prepare('SELECT * FROM `arsiv_alt_dosyalar` WHERE firma_id = :firma_id AND arsiv_alt_id = :arsiv_alt_id');
                                                                                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                                                        $sth->bindParam('arsiv_alt_id',$secilmis_alt_arsivler[$departman_index][$arsivIndex] );
                                                                                        $sth->execute();
                                                                                        $arsiv_alt_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);

                                                                                        $tekilKod = uniqid();
                                                                                    ?>
                                                                                    <?php foreach ($arsiv_alt_dosyalar as $arsiv_alt_dosya_index => $arsiv_alt_dosya) { ?>
                                                                                        <a class="example-image-link-<?php echo $tekilKod; ?>" 
                                                                                            href="dosyalar/arsivler/<?php echo $arsiv_alt_dosya['ad']; ?>" 
                                                                                            data-lightbox="example-set-<?php echo $tekilKod; ?>" data-title="">
                                                                                            <img src="dosyalar/arsivler/<?php echo $arsiv_alt_dosya['ad']; ?>" 
                                                                                                class="img-fluid rounded m-1"  
                                                                                                style="width:30px;height:30px;cursor:pointer;border:1px solid #dee2e6"
                                                                                            >
                                                                                        </a>
                                                                                    <?php }?>
                                                                                </div>
                                                                            </div>
                                                                        <?php }?>

                                                                        <?php if(empty($arsivler)){?>
                                                                            <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[alt_arsiv][<?php echo $altAsamaId; ?>][]" value="0">
                                                                        <?php }?>
                                                                    </div>

                                                                    <div class="stoklar">
                                                                        <?php 
                                                                            $sql = "SELECT departman_planlama.stok
                                                                            FROM `departman_planlama` 
                                                                            WHERE firma_id = :firma_id AND `departman_id` = :departman_id";
                                                                            $sth = $conn->prepare($sql);
                                                                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                                            $sth->bindParam('departman_id', $departman_id);
                                                                            $sth->execute();
                                                                            $departman_stok             = $sth->fetch(PDO::FETCH_ASSOC);
                                                                            $departman_stoklar          = json_decode($departman_stok['stok'], true);
                                                                            //print_r($departman_stoklar);

                                                                            $stoklar                    = [];
                                                                            $stok_alt_kalemler_sonuc    = [];
                                                                            foreach ($departman_stoklar as $key => $stok_id) {
                                                                                //if($stok_id == 0) continue;
                                                                                $sql = "SELECT id,stok_kalem FROM `stok_kalemleri`  WHERE id = :id";
                                                                                $sth = $conn->prepare($sql);
                                                                                $sth->bindParam('id', $stok_id);
                                                                                $sth->execute();
                                                                                $stok_kalem = $sth->fetch(PDO::FETCH_ASSOC);
                                                                                if($stok_kalem){
                                                                                    $sql = "SELECT stok_alt_kalemler.id, stok_alt_kalemler.veri,stok_alt_kalemler.toplam_stok
                                                                                            FROM `stok_alt_kalemler` 
                                                                                            WHERE stok_alt_kalemler.firma_id = :firma_id 
                                                                                            AND stok_alt_kalemler.stok_id = :stok_id AND toplam_stok > 0";
                                                                                    $sth = $conn->prepare($sql);
                                                                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                                                    $sth->bindParam('stok_id', $stok_id);
                                                                                    $sth->execute();
                                                                                    $stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                                                    if(!empty($stok_alt_kalemler)){
                                                                                        $stoklar[] = $stok_kalem;
                                                                                        $stok_alt_kalemler_sonuc[] = $stok_alt_kalemler;
                                                                                    }
                                                                                }
                                                                            }
                                                                            //print_r($stoklar);
                                                                        ?>
                                                                        <?php foreach($stoklar as $stokIndex => $stok){?>
                                                                            <div class="row mb-2 stok-alt-kalem-stok-alt-kalem-adet-stok-alt-depolar">
                                                                                <div class="col-md-2">
                                                                                    <div class="input-group">
                                                                                        <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                            <button class="btn btn-outline-secondary" type="button" 
                                                                                                data-bs-toggle="tooltip" 
                                                                                                data-bs-placement="bottom" 
                                                                                                data-bs-custom-class="custom-tooltip" 
                                                                                                data-bs-title="Stok Ekle"
                                                                                                data-stok-id="<?php echo $stok['id'];?>" disabled
                                                                                                >
                                                                                                <i class="fa-solid fa-plus"></i>
                                                                                            </button>
                                                                                        <?php }else{?> 
                                                                                            <button class="btn btn-outline-success stok-tekrar" type="button" 
                                                                                                data-bs-toggle="tooltip" 
                                                                                                data-bs-placement="bottom" 
                                                                                                data-bs-custom-class="custom-tooltip" 
                                                                                                data-bs-title="Stok Ekle"
                                                                                                data-stok-id="<?php echo $stok['id'];?>"
                                                                                                >
                                                                                                <i class="fa-solid fa-plus"></i>
                                                                                            </button>
                                                                                        <?php }?>
                                                                                        <input type="text" class="form-control readonly stok-input" disabled value="<?php echo $stokIndex + 1?> -  <?php echo $stok['stok_kalem'];?>">

                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-md-4 stok-alt-kalem-kolon">
                                                                                    <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                        <select class="js-example-basic-single form-select stok-alt-kalem" 
                                                                                            name="alt_urun_<?php echo $altUrunId;?>[stok_alt_kalem][<?php echo $altAsamaId;?>][]"
                                                                                            disabled
                                                                                        >
                                                                                            <option value="0">Seçiniz</option>
                                                                                            <?php foreach ($stok_alt_kalemler_sonuc[$stokIndex] as $stokAltKalemIndex => $stokAltKalem) { ?>
                                                                                                <?php 
                                                                                                    $keyler = implode('/', array_keys(json_decode($stokAltKalem['veri'], true)));    
                                                                                                    $valuelar = implode(' ', array_values(json_decode($stokAltKalem['veri'], true)));    
                                                                                                ?>
                                                                                                <option value="<?php echo $stokAltKalem['id']; ?>" 
                                                                                                    <?php echo isset($secilmis_stok_alt_kalemler[$departman_index][$stokIndex]) && $secilmis_stok_alt_kalemler[$departman_index][$stokIndex] == $stokAltKalem['id'] ? 'selected':'';?>
                                                                                                >
                                                                                                    <?php echo ($stokAltKalemIndex+1).' - '.$keyler; ?> Toplam Stok: <?php echo $stokAltKalem['toplam_stok'].' '.$valuelar; ?>
                                                                                                </option>
                                                                                            <?php }?>
                                                                                        </select>
                                                                                        <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[stok_alt_kalem][<?php echo $altAsamaId;?>][]" value="<?php echo isset($secilmis_stok_alt_kalemler[$departman_index][$stokIndex]) && $secilmis_stok_alt_kalemler[$departman_index][$stokIndex] ? $secilmis_stok_alt_kalemler[$departman_index][$stokIndex] : 0;?>">
                                                                                    <?php }else{?> 
                                                                                        <select class="js-example-basic-single form-select stok-alt-kalem" 
                                                                                            name="alt_urun_<?php echo $altUrunId;?>[stok_alt_kalem][<?php echo $altAsamaId;?>][]"
                                                                                        >
                                                                                            <option value="0">Seçiniz</option>
                                                                                            <?php foreach ($stok_alt_kalemler_sonuc[$stokIndex] as $stokAltKalemIndex => $stokAltKalem) { ?>
                                                                                                <?php 
                                                                                                    $keyler = implode('/', array_keys(json_decode($stokAltKalem['veri'], true)));    
                                                                                                    $valuelar = implode(' ', array_values(json_decode($stokAltKalem['veri'], true)));    
                                                                                                ?>
                                                                                                <option value="<?php echo $stokAltKalem['id']; ?>" 
                                                                                                    <?php echo isset($secilmis_stok_alt_kalemler[$departman_index][$stokIndex]) && $secilmis_stok_alt_kalemler[$departman_index][$stokIndex] == $stokAltKalem['id'] ? 'selected':'';?>
                                                                                                >
                                                                                                    <?php echo ($stokAltKalemIndex+1).' - '.$keyler; ?> Toplam Stok: <?php echo $stokAltKalem['toplam_stok'].' '.$valuelar; ?>
                                                                                                </option>
                                                                                            <?php }?>
                                                                                        </select>    
                                                                                    <?php } ?>
                                                                                </div>

                                                                                <div class="col-md-4 stok-alt-depolar">
                                                                                    <?php 
                                                                                        
                                                                                        $sql = "SELECT stok_alt_depolar.id,stok_alt_depolar.adet,stok_alt_depolar.fatura_no,stok_alt_depolar.siparis_no,
                                                                                                `tedarikciler`.`firma_adi`
                                                                                                FROM `stok_alt_depolar` 
                                                                                                JOIN `tedarikciler` ON `tedarikciler`.id = stok_alt_depolar.tedarikci_id
                                                                                                WHERE stok_alt_kalem_id = :stok_alt_kalem_id AND (siparis_no IS NULL OR siparis_no = :siparis_no)
                                                                                                ORDER BY siparis_no DESC";
                                                                                        $sth = $conn->prepare($sql);
                                                                                        $sth->bindParam('stok_alt_kalem_id', $secilmis_stok_alt_kalemler[$departman_index][$stokIndex]);
                                                                                        $sth->bindParam('siparis_no', $siparis['siparis_no']);
                                                                                        $sth->execute();
                                                                                        $stok_alt_depolar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                                                        
                                                                                    ?>
                                                                                    <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                        <select class="js-example-basic-single form-select stok-alt-depo" 
                                                                                            name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo][<?php echo $altAsamaId;?>][]" disabled
                                                                                            >
                                                                                            <option value="0">Seçiniz</option>
                                                                                            <?php foreach ($stok_alt_depolar as $index => $stok_alt_depo) { ?>
                                                                                                <option class="<?php echo $stok_alt_depo['siparis_no'] ? 'fw-bold text-danger':'';?>" value="<?php echo $stok_alt_depo['id']?>" <?php echo isset($secilmis_stok_alt_depolar[$departman_index][$stokIndex]) && $stok_alt_depo['id'] == $secilmis_stok_alt_depolar[$departman_index][$stokIndex] ? 'selected':'';?>>
                                                                                                    <?php echo $index +1;?> - Firma/Adet/Fatura No/: 
                                                                                                    <?php echo $stok_alt_depo['firma_adi'].' '.$stok_alt_depo['adet'].' '.$stok_alt_depo['fatura_no']; ?>
                                                                                                    <?php echo $stok_alt_depo['siparis_no'] ? '(Siparişe Özel)' : '-'; ?>
                                                                                                </option>
                                                                                            <?php }?>
                                                                                        </select>
                                                                                        <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo][<?php echo $altAsamaId;?>][]" value="<?php echo isset($secilmis_stok_alt_depolar[$departman_index][$stokIndex]) ? $secilmis_stok_alt_depolar[$departman_index][$stokIndex]:0;?>">
                                                                                    <?php }else{?> 
                                                                                        <select class="js-example-basic-single form-select stok-alt-depo" 
                                                                                            name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo][<?php echo $altAsamaId;?>][]"
                                                                                            >
                                                                                            <option value="0">Seçiniz</option>
                                                                                            <?php foreach ($stok_alt_depolar as $index => $stok_alt_depo) { ?>
                                                                                                <option class="<?php echo $stok_alt_depo['siparis_no'] ? 'fw-bold text-danger':'';?>" value="<?php echo $stok_alt_depo['id']?>" <?php echo isset($secilmis_stok_alt_depolar[$departman_index][$stokIndex]) && $stok_alt_depo['id'] == $secilmis_stok_alt_depolar[$departman_index][$stokIndex] ? 'selected':'';?>>
                                                                                                    <?php echo $index +1;?> - Firma/Adet/Fatura No/: 
                                                                                                    <?php echo $stok_alt_depo['firma_adi'].' '.$stok_alt_depo['adet'].' '.$stok_alt_depo['fatura_no']; ?>
                                                                                                    <?php echo $stok_alt_depo['siparis_no'] ? '(Siparişe Özel)' : '-'; ?>
                                                                                                </option>
                                                                                            <?php }?>
                                                                                        </select>
                                                                                    <?php }?>
                                                                                </div>

                                                                                <div class="col-md-2 stok-alt-kalem-adet-kolon">
                                                                                    <div class="input-group">
                                                                                        <?php 
                                                                                            $sql = "SELECT birimler.ad FROM `stok_alt_kalemler` 
                                                                                            JOIN birimler ON birimler.id = stok_alt_kalemler.birim_id
                                                                                            WHERE stok_alt_kalemler.id = :id";
                                                                                            $sth = $conn->prepare($sql);
                                                                                            $sth->bindParam('id', $secilmis_stok_alt_kalemler[$departman_index][$stokIndex]);
                                                                                            $sth->execute();
                                                                                            $birim = $sth->fetch(PDO::FETCH_ASSOC);
                                                                                        ?>
                                                                                        <span class="input-group-text stok-alt-kalem-birim-yazisi">
                                                                                            <?php echo isset($birim['ad']) ? $birim['ad'] : '-'; ?>
                                                                                        </span>
                                                                                        <?php if($planlama['durum'] != 'baslamadi' && $altAsamaId <= $planlama['mevcut_asama'] +1){ ?>
                                                                                            <input type="number"  
                                                                                                class="form-control form-control-sm stok-alt-kalem-adet" min="0"
                                                                                                value="<?php echo $secilmis_stok_alt_depo_adetler[$departman_index][$stokIndex]; ?>"
                                                                                                name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo_adet][<?php echo $altAsamaId;?>][]" disabled>
                                                                                            <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo_adet][<?php echo $altAsamaId;?>][]" value="<?php echo $secilmis_stok_alt_depo_adetler[$departman_index][$stokIndex]; ?>">
                                                                                        <?php }else{?> 
                                                                                            <input type="number"  
                                                                                                class="form-control form-control-sm stok-alt-kalem-adet" min="0"
                                                                                                value="<?php echo $secilmis_stok_alt_depo_adetler[$departman_index][$stokIndex]; ?>"
                                                                                                name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo_adet][<?php echo $altAsamaId;?>][]">
                                                                                        <?php }?>
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                            </div>
                                                                        <?php }?>

                                                                        <?php if(empty($stoklar)){?>
                                                                            <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[stok_alt_kalem][<?php echo $altAsamaId;?>][]" value="0">
                                                                            <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo][<?php echo $altAsamaId;?>][]" value="0">
                                                                            <input type="hidden" name="alt_urun_<?php echo $altUrunId;?>[stok_alt_depo_adet][<?php echo $altAsamaId;?>][]" value="0">
                                                                        <?php }?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>                                          
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        
                                        <input type="hidden" name="siparis_id" value="<?php echo $siparis_id; ?>">
                                        <input type="hidden" name="alt_urun_sayisi" id="alt-urun-sayisi" value="<?php echo count($planlamalar); ?>"> 
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <?php if(isset($planlamalar[0]['planlama_durum']) && $planlamalar[0]['planlama_durum'] == 'evet'){ ?>
                                                    <button type="submit" name="planlama_onay_guncelle" value="onay" class="btn btn-success planlama-onay-guncelle-button">
                                                        <i class="fa-regular fa-circle-check"></i> ONAY
                                                    </button>     
                                                <?php }else if(isset($planlamalar[0]['planlama_durum']) && $planlamalar[0]['planlama_durum'] == 'yarım_kalmıs'){ ?>  
                                                    <button type="submit" name="planlama_onay_guncelle" value="guncelle" class="btn btn-success planlama-onay-guncelle-button">
                                                        <i class="fa-solid fa-list-check"></i>  PLANLA
                                                    </button> 
                                                    <button type="submit" name="planlama_onay_guncelle" value="yarım_kalmıs" class="btn btn-primary planlama-onay-guncelle-button">
                                                        <i class="fa-regular fa-floppy-disk"></i> KAYDET
                                                    </button> 
                                                <?php } ?>      
                                            </div>
                                        </div>
                                    </form> 
                                </div>
                            </div>   
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <?php include_once "include/uyari_session_oldur.php"; ?>
        <!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script> -->
        <script>
            $(function(){
                let altUrunSayisi = <?php echo count($planlamalar); ?>;

                for(let i = 1; i <= altUrunSayisi; i++){
                    $(`.sortable-${i}` ).sortable({
                        stop: function(event, ui) {
                            const altAsamalar = $(event.target).find('div.alt-asama:not(.ui-sortable-placeholder)');
                            let altAsamaSayisi = altAsamalar.length;
                            altAsamalar.each(function(index){
                                $(this).find('.alt-urunun-kacinci-asama-yazisi').text(altAsamaSayisi--)
                            });
                        }
                    });
                }


                $("#planlama-form").submit(function(){
                    $(".planlama-onay-guncelle-button").addClass('disabled');
                    return true;
                });

                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });

                //alt arsiv resimleri getir
                $(document).on('change', '.alt_arsiv', function(){
                    const altArsivId = $(this).val();
                    const resimlerElement = $(this).closest('.alt-arsiv').find('.alt-arsiv-resimler');
                    if(altArsivId == 0){
                        resimlerElement.html('');
                        return;
                    }
                    altArsivResimGetir(altArsivId, resimlerElement);
                });
                
                //fason değiştiğinde
                $(document).on('change', '.fason_durum', async function(){
                    const altUrunId     = $(this).closest('.alt-urun').data('alt-urun-id');
                    const fasonDurum    = $(this).val();
                    const departmanId   = $(this).closest('.departman-fason-durum').find('.departman-kolon .departman').val();
                    const element       = $(this).closest('div.alt-asama-veriler');
                    if(!departmanId) return; //departman seçilmemişse

                    $(element).find('.tedarikciler').html('');
                    $(element).find('.makinalar').html('');
                    
                    fasonDurum == 1 ?
                        tedarikcileriGetir(element, altUrunId):
                        makinalariGetir(element, altUrunId, departmanId);
                });

                //departman değiştir
                $(document).on('change', '.departman-degistir', async function(){
                    const altUrunId     = $(this).closest('.alt-urun').data('alt-urun-id');
                    const departmanId   = $(this).val();
                    const fasonDurum    = $(this).closest('div.departman-kolon').next('.fason-kolon').find('.fason_durum').val();
                    const element       = $(this).closest('div.alt-asama-veriler');
                    const altAsamaId    = $(this).closest('div.alt-asama').find('.alt-urunun-kacinci-asama-yazisi').text().trim();
                    $(element).find('.tedarikciler').html('');
                    $(element).find('.makinalar').html('');

                    const response = await fetch("/index.php?url=planlama_db_islem&islem=departmanin_birimini_getir&departman_id=" + departmanId);
                    const departman_birim = await response.json();

                    $(element).find('.adet-birim').text(departman_birim.birim.ad)

                    fasonDurum == 1 ? 
                        tedarikcileriGetir(element, altUrunId):
                        makinalariGetir(element, altUrunId, departmanId);

                    altArsivGetir(element, altUrunId, departmanId,altAsamaId);
                    stokGetir(element, altUrunId, departmanId, altAsamaId);
                    //$('.js-example-basic-single').select2();
                });

                //alt asama ekleme
                $(document).on('click', '.departmanlari-getir', function(){
                    const altUrunId = $(this).data('alt-urun-id');
                    departmanGetir(altUrunId)
                });

                //alt aşama çıkar
                $(document).on('click','.alt-asama-cikar', function(){
                    const altUrunElement = $(this).closest('.alt-asamalar');
                    $(this).closest('.alt-asama').remove();
                    const mevcutAltUrunSayisi = $(altUrunElement).find('.alt-asama').length;
                    
                    altUrunElement.find('.alt-asama').each(function(index,element){
                        $(element).attr('id',`alt-asama-${mevcutAltUrunSayisi-index}`);
                        $(element).find('.lead').text(`${mevcutAltUrunSayisi-index}. Aşama`);
                    })
                });

                //alt ürün ekleme
                $('#alt-urun-ekle').click(function(){
                    altUrunSayisi++;
                    $("#alt-urunler form").prepend(`
                        <input type="hidden" name="alt_urun_${altUrunSayisi}[planlama_id]" value="0">
                        <div class="card mb-2 alt-urun" id="alt-urun-${altUrunSayisi}" data-alt-urun-id="${altUrunSayisi}">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="lead fw-bold text-danger alt-urun-sayisi-uyari">
                                            ${altUrunSayisi}. Alt Ürün Bilgileri
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button class="btn btn-sm btn-danger alt-urun-cikar button-yuvarlak" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="Alt Ürün Çıkar" 
                                            data-alt-urun-id="${altUrunSayisi}"
                                        >
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <button class="btn btn-outline-success departmanlari-getir" type="button"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Alt Aşama Ekle"
                                                data-alt-urun-id="${altUrunSayisi}"
                                            >
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                            <input type="text"  class="form-control isim"  placeholder="İsim.." name="alt_urun_${altUrunSayisi}[isim]"   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text">Ü. Adet</span>
                                            <input type="number"  class="form-control uretilecek-adet"  name="alt_urun_${altUrunSayisi}[uretilecek_adet]" placeholder="Üretilecek Adet.." required>
                                        </div>
                                    </div>
                                </div>
                                <div class="alt-asamalar sortable-${altUrunSayisi}" ></div>
                            </div>
                        </div>
                    `);
                    $(`.sortable-${altUrunSayisi}` ).sortable({
                        stop: function(event, ui) {
                            const altAsamalar = $(event.target).find('div.alt-asama:not(.ui-sortable-placeholder)');
                            let altAsamaSayisi = altAsamalar.length;
                            altAsamalar.each(function(index){
                                $(this).find('.alt-urunun-kacinci-asama-yazisi').text(altAsamaSayisi--)
                            });
                        }
                    });
                    $("#alt-urun-sayisi").val(altUrunSayisi);
                    const tooltipTriggerEl = document.querySelector(`#alt-urun-${altUrunSayisi} button.alt-urun-cikar`);
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });

                //alt ürün çıkar
                $(document).on('click', '.alt-urun-cikar', function(){
                    const altUrunId = $(this).data('alt-urun-id');
                    $(`#alt-urun-${altUrunId}`).remove();

                    altUrunSayisi--;
                    $("#alt-urunler form > div.card.alt-urun").each(function(index,element){
                        //console.log($(element));
                        $(element).attr('id', `alt-urun-${altUrunSayisi-index}`)
                        .attr('data-alt-urun-id', altUrunSayisi-index)
                        .find('.alt-urun-sayisi-uyari').text(`${altUrunSayisi-index}. Alt Ürün Bilgileri`);

                        $(element).find('button.alt-urun-cikar').attr('data-alt-urun-id', altUrunSayisi-index);
                        $(element).find('button.departmanlari-getir').attr('data-alt-urun-id', altUrunSayisi-index);
                    
                        $(element).find('input.bitmis_is_adet').attr('name', `alt_urun_${altUrunSayisi-index}[bitmis_is_adet]`);
                        $(element).find('input.isim').attr('name', `alt_urun_${altUrunSayisi-index}[isim]`);
                        $(element).find('input.uretilecek-adet').attr('name', `alt_urun_${altUrunSayisi-index}[uretilecek_adet]`);
                        $(element).find('select.departman').attr('name', `alt_urun_${altUrunSayisi-index}[departman][]`);
                        $(element).find('select.fason_durum').attr('name', `alt_urun_${altUrunSayisi-index}[fason_durum][]`);
                        $(element).find('input.adet').attr('name', `alt_urun_${altUrunSayisi-index}[adet][]`);
                        $(element).find('input.sure').attr('name', `alt_urun_${altUrunSayisi-index}[sure][]`);
                        $(element).find('input.detay').attr('name', `alt_urun_${altUrunSayisi-index}[detay][]`);
                        $(element).find('select.makina').attr('name', `alt_urun_${altUrunSayisi-index}[makina][]`);
                        $(element).find('select.fason_tedarikci').attr('name', `alt_urun_${altUrunSayisi-index}[fason_tedarikci][]`);
                        $(element).find('select.alt_arsiv').attr('name', $(element)?.find('select.alt_arsiv')?.val()?.replace(/\d+/, altUrunSayisi-index));
                        $(element).find('select.stok-alt-kalem').attr('name', $(element)?.find('select.stok-alt-kalem')?.val()?.replace(/\d+/, altUrunSayisi-index));
                        $(element).find('input.stok-alt-kalem-adet').attr('name', $(element)?.find('input.stok-alt-kalem-adet')?.val()?.replace(/\d+/, altUrunSayisi-index));
                        
                    });
                    $("#alt-urun-sayisi").val(altUrunSayisi);
                });

                //stok alt kalem değiştirme
                $(document).on('change', '.stok-alt-kalem', function(){
                    const stokAltKalemId    = $(this).val();
                    const element           = $(this).closest('.stok-alt-kalem-stok-alt-kalem-adet-stok-alt-depolar');
                    const altUrunId         = $(this).closest('.alt-urun').data('alt-urun-id');
                    const altAsamaId        = $(this).closest('div.alt-asama').find('.alt-urunun-kacinci-asama-yazisi').text().trim();
                    
                    $.ajax({
                        url         : "/index.php?url=planlama_db_islem&islem=stok_alt_depo_getir" ,
                        dataType    : "JSON",
                        type        : "POST",
                        data        : {stok_alt_kalem_id:stokAltKalemId, siparis_id:"<?php echo $siparis_id;?>"},
                        success     : function(veriler){ 
                            element.find('.stok-alt-kalem-birim-yazisi').text(veriler.birim?.ad ?? '-');
                            let stokAltDepolarHTML = '<option selected value="0">Seçiniz</option>';
                            veriler?.stok_alt_depolar.forEach((stok_alt_depo, index)=>{
                                stokAltDepolarHTML += `<option value="${stok_alt_depo.id}">
                                    ${index+1} - Firma/Adet/Fatura No/: 
                                    ${stok_alt_depo.firma_adi} ${stok_alt_depo.adet}  ${stok_alt_depo.fatura_no} 
                                    ${stok_alt_depo.siparis_no ? '(Siparişe Özel)' : '-'}
                                </option>`;
                            });
                            //console.log(stokAltDepolarHTML)
                            element.find('.stok-alt-depolar').html(`
                                <select class="js-example-basic-single form-select stok-alt-depo" 
                                    name="alt_urun_${altUrunId}[stok_alt_depo][${altAsamaId}][]"
                                    >
                                    ${stokAltDepolarHTML}
                                </select>
                            `);

                            $('.js-example-basic-single').select2({
                                theme: 'bootstrap-5'
                            });
                        }
                    });
                });

                //stok tekrar çıkar
                $(document).on('click', '.stok-tekrar-cikar', function(){
                    const enYakinStoklar    = $(this).closest('.stoklar');
                    $(this).closest('.stok-alt-kalem-stok-alt-kalem-adet-stok-alt-depolar').remove();
                    enYakinStoklar.find('.stok-input').each(( index, element ) => {
                        $(element).val($(element).val().replace(/\d/, index+1));
                    });
                });

                //stok tekrar ekle
                $(document).on('click', '.stok-tekrar', function(){
                    
                    const altUrunId         = $(this).closest('.alt-urun').data('alt-urun-id');
                    const altAsamaId        = $(this).closest('div.alt-asama').find('.alt-urunun-kacinci-asama-yazisi').text().trim();
                    const stokId            = $(this).data('stok-id');
                    const _this             = $(this).closest('.stok-alt-kalem-stok-alt-kalem-adet-stok-alt-depolar');
                    

                    $.ajax({
                        url         : "/index.php?url=planlama_db_islem&islem=stok_alt_kalem_getir" ,
                        dataType    : "JSON",
                        type        : "POST",
                        data        : {stok_id:stokId},
                        success     : function(veriler){ 
                            let stokAltKalemlerHTML = "<option selected value=''>Seçiniz</option>";
                            veriler?.stok_alt_kalemler.forEach((stok_alt_kalem, index)=>{
                                stokAltKalemlerHTML += `
                                    <option value="${stok_alt_kalem.id}">
                                        ${index + 1} - 
                                        ${Object.keys(JSON.parse(stok_alt_kalem.veri)).join('/')}
                                        ${Object.values(JSON.parse(stok_alt_kalem.veri)).join(' ')}
                                        Toplam Stok: ${stok_alt_kalem.toplam_stok}
                                    </option>`;
                            });
                            let stokHTML = `
                            <div class="row mb-2 stok-alt-kalem-stok-alt-kalem-adet-stok-alt-depolar">
                                <div class="col-md-2">
                                    <div class="input-group">
                                        <button class="btn btn-outline-success stok-tekrar-cikar" 
                                            type="button" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            data-bs-custom-class="custom-tooltip" 
                                            data-bs-title="Stok Çıkar"
                                            >
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        <input type="text" class="form-control readonly stok-input" disabled value="1 -  ${veriler.stok_kalem.stok_kalem}">
                                    </div>
                                </div>
                                <div class="col-md-4 stok-alt-kalem-kolon">
                                    <select class="js-example-basic-single form-select stok-alt-kalem" 
                                        name="alt_urun_${altUrunId}[stok_alt_kalem][${altAsamaId}][]"
                                    >
                                        ${stokAltKalemlerHTML}
                                    </select>
                                </div>
                                <div class="col-md-4 stok-alt-depolar">
                                    <input type="hidden" name="alt_urun_${altUrunId}[stok_alt_depo][${altAsamaId}][]" value="0">
                                </div>
                                <div class="col-md-2 stok-alt-kalem-adet-kolon">
                                    <div class="input-group">
                                        <span class="input-group-text stok-alt-kalem-birim-yazisi">
                                            -
                                        </span>
                                        <input type="number"  
                                            class="form-control form-control-sm stok-alt-kalem-adet" min="0"
                                            name="alt_urun_${altUrunId}[stok_alt_depo_adet][${altAsamaId}][]">
                                    </div>
                                </div>
                            </div>
                            `;
                            _this.after(stokHTML);
                            const enYakinStoklar    = _this.closest('.stoklar').find('.stok-input');
                            enYakinStoklar.each(( index, element ) => {
                                $(element).val($(element).val().replace(/\d/, index+1));
                            });
                            $('.js-example-basic-single').select2({
                                theme: 'bootstrap-5'
                            });
                        }
                    });
                });

                
            });

            async function stokGetir(element, altUrunId, departmanId, altAsamaId){
                $(element).find('.stoklar').html('');
                const response  = await fetch("/index.php?url=planlama_db_islem&islem=stoklari_getir&departman_id=" + departmanId + "&siparis_id="+ <?php echo $siparis_id;?>);
                const stoklar   = await response.json();
                let stokHTML    = "";
                let stokAltKalemlerHTML = "";
                stoklar?.stoklar.forEach((stok, index) => {
                    stokAltKalemlerHTML = "<option selected value='0'>Seçiniz</option>";
                    stoklar?.stok_alt_kalemler?.[index].forEach((stok_alt_kalem, index)=>{
                        stokAltKalemlerHTML += `
                        <option value="${stok_alt_kalem.id}">
                            ${index + 1} - 
                            ${Object.keys(JSON.parse(stok_alt_kalem.veri)).join('/')}
                            ${Object.values(JSON.parse(stok_alt_kalem.veri)).join(' ')}
                            Toplam Stok: ${stok_alt_kalem.toplam_stok}
                        </option>`;
                    });

                    stokHTML += `
                    <div class="row mb-2 stok-alt-kalem-stok-alt-kalem-adet-stok-alt-depolar">
                        <div class="col-md-2">
                            <div class="input-group">
                                <button class="btn btn-outline-success stok-tekrar" type="button" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="bottom" 
                                    data-bs-custom-class="custom-tooltip" 
                                    data-bs-title="Stok Ekle"
                                    data-stok-id="${stok.id}"
                                    >
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                                <input type="text" class="form-control readonly stok-input" disabled value="${index +1} -  ${stok.stok_kalem}">
                                
                            </div>
                        </div>
                        <div class="col-md-4 stok-alt-kalem-kolon">
                            <select class="js-example-basic-single form-select stok-alt-kalem" 
                                name="alt_urun_${altUrunId}[stok_alt_kalem][${altAsamaId}][]"
                            >
                                ${stokAltKalemlerHTML}
                            </select>
                        </div>
                        <div class="col-md-4 stok-alt-depolar">
                            <input type="hidden" name="alt_urun_${altUrunId}[stok_alt_depo][${altAsamaId}][]" value="0">
                        </div>
                        <div class="col-md-2 stok-alt-kalem-adet-kolon">
                            <div class="input-group">
                                <span class="input-group-text stok-alt-kalem-birim-yazisi">
                                    -
                                </span>
                                <input type="number"  
                                    class="form-control form-control-sm stok-alt-kalem-adet" min="0"
                                    name="alt_urun_${altUrunId}[stok_alt_depo_adet][${altAsamaId}][]">
                            </div>
                        </div>
                    </div>
                    `;
                });
                $(element).find('.stoklar').html(stokHTML);
                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });
            }

            async function altArsivResimGetir(altArsivId, resimlerElement){
                
                const response = await fetch("/index.php?url=planlama_db_islem&islem=alt_arsiv_resim_getir&arsiv_alt_id=" + altArsivId);
                const altArsivResimler = await response.json();
                let resimlerHTML = '';
                let now = Date.now();
                altArsivResimler?.alt_arsiv_resimler.forEach((resim, index)=>{
                    resimlerHTML += `
                    <a class="example-image-link-${now}" 
                    href="dosyalar/arsivler/${resim.ad}" data-lightbox="example-set-${now}" data-title="">
                            <img src="dosyalar/arsivler/${resim.ad}" 
                                class="img-fluid rounded m-1" 
                                style="width:30px;height:30px;cursor:pointer;border:1px solid #dee2e6">
                    </a>
                    `;
                });
                resimlerElement.html(resimlerHTML);
                lightbox.option({
                    'resizeDuration'                :   200,
                    'wrapAround'                    :   true,
                    'alwaysShowNavOnTouchDevices'   :   true,
                    'disableScrolling'              :   true,
                    'imageFadeDuration'             :   200,
                    'fadeDuration'                  :   200,
                    'albumLabel'                    :   'Resim %1/%2'
                });
                
            }


            function departmanGetir(altUrunId){
                //console.log("alt urun id =>" , altUrunId)
                $.ajax({
                    url         : "/index.php?url=ajax_islemler&islem=departman-getir" ,
                    dataType    : "JSON",
                    success     : function(departmanlar){ 
                        //console.log("Alt aşama sayısı => ", $(`#alt-urun-${altUrunId} .alt-asamalar .alt-asama`).length )
                        const simdikiAltAsamaSayisi = $(`#alt-urun-${altUrunId} .alt-asamalar .alt-asama`).length + 1;

                        let departmanlarHTML = `<option selected disabled value="">Seçiniz...</option>`;
                        for(const departman of departmanlar)
                        {
                            departmanlarHTML += `<option value="${departman['id']}">${departman['departman']}</option>`;
                        }

                        let yeniAltAsamaHTML =`
                        <input type="hidden" name="alt_urun_${altUrunId}[bitmis_is_adet][]" value="0">
                            <div class="card bg-light mb-2 alt-asama ui-sortable-handle" id="alt-asama-${simdikiAltAsamaSayisi}">
                                <div class="card-header">
                                    <div class="row mb-2">
                                        <div class="col-md-10 lead fw-bold text-success">
                                            <i class="fa-solid fa-arrows-up-down-left-right"></i>
                                            <span class="alt-urunun-kacinci-asama-yazisi">${simdikiAltAsamaSayisi}</span>. Aşama 
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button class="btn btn-sm btn-danger alt-asama-cikar button-yuvarlak"  
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Alt Aşama Çıkar"
                                                >
                                                <i class="fa-solid fa-minus"></i> 
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body alt-asama-veriler">
                                    <div class="row mb-2 departman-fason-durum">
                                        <div class="col-md-6 departman-kolon">
                                            <div class="input-group flex-nowrap">
                                                <span class="input-group-text">Departman</span>
                                                <select 
                                                    name="alt_urun_${altUrunId}[departman][]"
                                                    class="form-select departman-degistir departman"
                                                    required>
                                                    ${departmanlarHTML}
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 fason-kolon"> 
                                            <div class="input-group flex-nowrap">
                                                <span class="input-group-text">Fason</span>
                                                <select class="form-select fason_durum" 
                                                    name="alt_urun_${altUrunId}[fason_durum][]">
                                                    <option value="0">Hayır</option>
                                                    <option value="1">Evet</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2 adet-sure-detay">
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text adet-birim" >
                                                    -
                                                </span>
                                                <input type="number"  class="form-control form-control-sm adet" 
                                                    name="alt_urun_${altUrunId}[adet][]" min="0" required >
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    Süre (saat)
                                                </span>
                                                <input type="number" step="0.01" class="form-control form-control-sm sure" 
                                                    name="alt_urun_${altUrunId}[sure][]" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    Detay
                                                </span>
                                                <input type="text" class="form-control form-control-sm detay" 
                                                    name="alt_urun_${altUrunId}[detay][]">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2 makinalar"></div>
                                    <div class="row mb-2 tedarikciler"></div>
                                    <div class="arsivler"></div>
                                    <div class="stoklar"></div>
                                </div>
                            </div>
                        `;
                        $(`#alt-urun-${altUrunId} .alt-asamalar`).prepend(yeniAltAsamaHTML);
                    }
                });
            }

            async function altArsivGetir(element, altUrunId, departmanId, altAsamaId){
                $(element).find('.arsivler').html('');
                const response = await fetch("/index.php?url=planlama_db_islem&islem=siparis_alt_arsiv&departman_id=" + departmanId + "&siparis_id="+ <?php echo $siparis_id;?>);
                const arsivler = await response.json();

                //console.log(arsivler?.arsivler);
                
                let arsivHTML = '';
                let arsivAltlarHTML = '';
                
                arsivler?.arsivler.forEach((arsiv, index) => {
                    arsivAltlarHTML = '<option selected value="0">Seçiniz..</option>';
                    arsiv?.alt_arsivler.forEach((alt_arsiv) => {
                        arsivAltlarHTML += `<option value="${alt_arsiv.id}">${alt_arsiv.kod} - ${alt_arsiv.ebat} - ${alt_arsiv.detay}</option>`
                    })
                    arsivHTML += `
                        <div class="row mb-2 alt-arsiv">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <strong class="text-danger">${index + 1}. Arşiv - </strong> ${arsiv.arsiv}
                                    </span> 
                                    <select class="form-select alt_arsiv"
                                        name="alt_urun_${altUrunId}[alt_arsiv][${altAsamaId}][]"
                                    >
                                    ${arsivAltlarHTML}
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 alt-arsiv-resimler"></div>
                        </div>
                    `;
                });
                
                $(element).find('.arsivler').html(arsivHTML);

                if(arsivler?.arsivler.length == 0){
                    $(element).find('.arsivler').html(`
                        <input type="hidden" class="alt_arsiv" name="alt_urun_${altUrunId}[alt_arsiv][${altAsamaId}][]" 
                        value="0">
                    `);
                }
            }

            async function tedarikcileriGetir(element, altUrunId){
                const response = await fetch("/index.php?url=planlama_db_islem&islem=tedarikcileri_getir");
                const tedarikciler = await response.json();

                let tedarikciHTML = '';
                tedarikciler?.tedarikciler.forEach((tedarikci, index) =>{
                    tedarikciHTML += `<option value="${tedarikci.id}">
                        ${index+1}. ${tedarikci.firma_adi} ${tedarikci.tedarikci_unvani}
                    </option>`;
                });

                $(element).find('.tedarikciler').html(`
                    <div class="col-md-2">
                        <span class="input-group-text">
                            Tedarikçi
                        </span> 
                    </div>
                    <div class="col-md-4">
                        <select class="form-select js-example-basic-single fason_tedarikci" 
                            name="alt_urun_${altUrunId}[fason_tedarikci][]" required 
                        >
                            <option value="">Seçiniz..</option>
                            ${tedarikciHTML}
                        </select>
                    </div>
                `);
                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });
                $(element).find('.makinalar').html(`
                    <input type="hidden" class="makina" name="alt_urun_${altUrunId}[makina][]" value="0">
                `);
            }

            async function makinalariGetir(element, altUrunId, departmanId){
                //console.log("altUrunId => ", altUrunId)
                const response = await fetch("/index.php?url=planlama_db_islem&islem=departmanin_makinalari&departman_id=" + departmanId);
                const makinalar = await response.json();

                let makinaHTML = '';
                makinalar?.makinalar.forEach((makina, index) =>{
                    makinaHTML += `<option value="${makina.id}">
                        ${index+1}. ${makina.makina_adi} ${makina.makina_modeli}
                    </option>`;
                });

                $(element).find('.makinalar').html(`
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                Makina
                            </span> 
                            <select class="form-select makina ${makinalar?.makinalar.length == 0 ? 'is-invalid':''}" name="alt_urun_${altUrunId}[makina][]" required>
                                <option selected value="">Seçiniz..</option>
                                ${makinaHTML}
                            </select>
                            <div class="invalid-feedback fw-bold">
                                Bu Departmanda Makina Yoktur
                                <a class="btn btn-sm btn-warning text-white fw-bold">
                                    <i class="fa-solid fa-building"></i> Makine Ekle
                                </a>
                            </div>
                        </div>
                    </div>
                `);

                $(element).find('.tedarikciler').html(`
                    <input type="hidden" class="fason_tedarikci"  name="alt_urun_${altUrunId}[fason_tedarikci][]"  value="0">
                `);
            }
        </script>