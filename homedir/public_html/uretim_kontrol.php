<?php
    require_once "include/oturum_kontrol.php";

    $sql = "SELECT planlama.isim, planlama.uretilecek_adet,planlama.asama_sayisi, planlama.mevcut_asama,planlama.durum,
            planlama.departmanlar,planlama.adetler,planlama.id AS planlama_id,planlama.makinalar,`planlama`.`isim`,
            planlama.alt_urun_id,planlama.grup_kodu,
            siparisler.isin_adi,siparisler.siparis_no,siparisler.termin,siparisler.id AS siparis_id,
            musteri.marka
            FROM `planlama` 
            JOIN siparisler ON siparisler.id = planlama.siparis_id 
            JOIN musteri ON musteri.id = siparisler.musteri_id
            WHERE planlama.firma_id = :firma_id AND planlama.durum IN('basladi', 'beklemede','fasonda') 
            AND aktar_durum = 'orijinal';";

    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->execute();
    $uretimdeki_isler = $sth->fetchAll(PDO::FETCH_ASSOC);



    $sql = "SELECT planlama.isim, planlama.uretilecek_adet,
            planlama.id AS planlama_id,`planlama`.`isim`,
            siparisler.isin_adi,siparisler.siparis_no,siparisler.termin,siparisler.id AS siparis_id,
            musteri.marka
            FROM `planlama` 
            JOIN siparisler ON siparisler.id = planlama.siparis_id 
            JOIN musteri ON musteri.id = siparisler.musteri_id
            WHERE planlama.firma_id = :firma_id AND planlama.durum = 'baslamadi'";

    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->execute();
    $henuz_baslamis_isler = $sth->fetchAll(PDO::FETCH_ASSOC);


    $sql = "SELECT planlama.isim, planlama.id AS planlama_id, planlama.eksik_adet,
            siparisler.isin_adi,siparisler.siparis_no,siparisler.termin,siparisler.id AS siparis_id,
            musteri.marka
            FROM `planlama` 
            JOIN siparisler ON siparisler.id = planlama.siparis_id 
            JOIN musteri ON musteri.id = siparisler.musteri_id
            WHERE planlama.firma_id = :firma_id AND planlama.asamada_eksik_adet_varmi = 'var'";

    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->execute();
    $eksik_uretilip_onay_bekleyenler = $sth->fetchAll(PDO::FETCH_ASSOC);

    //echo "<pre>"; print_r($uretimdeki_isler);exit;
?>
        <div class="row">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-list-check"></i>
                        Üretim Kontrol
                    </h5>
                    <div class="d-flex justify-content-end"> 
                        <span class="badge bg-success fs-6 me-2" id="realtime-durum">
                            <i class="fa-solid fa-circle-dot"></i> Anlık Veri
                        </span>
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
                <div class="card-body pt-0">
                    <nav class="mt-2">
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active position-relative fw-bold" id="nav-tab-uretimdeki-isler" data-bs-toggle="tab" 
                                data-bs-target="#nav-uretimdeki-isler" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                Üretimdeki İşler
                                <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-success fs-6" data-realtime="uretimdeki-isler-sayi">
                                    <?php echo number_format(count($uretimdeki_isler));?>
                                    <span class="visually-hidden">Üretimdeki İşler</span>
                                </span>
                            </button>

                            <button class="nav-link position-relative fw-bold" id="nav-tab-uretime-girmeyen-isler" data-bs-toggle="tab" 
                                data-bs-target="#nav-uretime-girmeyen-isler" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                Üretimdeki Henüz Girmeyen İşler 
                                <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-danger fs-6" data-realtime="henuz-baslamis-isler-sayi">
                                    <?php echo number_format(count($henuz_baslamis_isler)); ?>
                                    <span class="visually-hidden">Üretime Henüz Girmeyen İşler</span>
                                </span>
                            </button>

                            <button class="nav-link position-relative fw-bold" id="nav-tab-eksik-uretimde-onay-bekleyen" data-bs-toggle="tab" 
                                data-bs-target="#nav-eksik-uretimde-onay-bekleyen" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                Eksik Üretimde Onay Bekleyenler
                                <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-info fs-6" data-realtime="eksik-onay-bekleyen-sayi">
                                    <?php echo number_format(count($eksik_uretilip_onay_bekleyenler)); ?>
                                    <span class="visually-hidden">Üretime Henüz Girmeyen İşler</span>
                                </span>
                            </button>
                        </div>
                    </nav>
                    <div class="tab-content mt-3" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-uretimdeki-isler" role="tabpanel" 
                            aria-labelledby="nav-tab-uretimdeki-isler" tabindex="0">

                            <div class="table-responsive">
                                <table id="myTable" class="table table-hover" >
                                    <thead class="table-primary">
                                        <tr>
                                            <th>#</th>
                                            <th>Sipariş No</th>
                                            <th>Müşteri</th>
                                            <th>İşin Adı</th>
                                            <th>Termin</th>
                                            <th>İşin Durumu</th>
                                            <th>Çalışan Usta</th>
                                            <th>Makina</th>
                                            <th class="text-end">Mesaj</th>
                                            <th class="text-end">İş Değişliği</th>
                                            <th class="text-end">Adet</th>
                                            <th>Çekilen Fotolar</th>
                                            <th>Link</th>
                                        </tr>
                                    </thead>
                                    <tbody class="align-middle">
                                        <?php foreach ($uretimdeki_isler as $index => $uretimdeki_is) { ?>
                                            <?php 
                                                        
                                                if($uretimdeki_is['durum'] == 'baslamadi')      { $mevcut_asama_sonuc = 0;}
                                                else                                            { $mevcut_asama_sonuc = $uretimdeki_is['mevcut_asama'] + 1;}

                                                
                                                $adetler        = json_decode($uretimdeki_is['adetler']);
                                                $makinalar      = json_decode($uretimdeki_is['makinalar']);
                                                $departmanlar   = json_decode($uretimdeki_is['departmanlar']);

                                                $isdeki_departmanlar = [];
                                                foreach ($departmanlar as $key => $departman_id) {
                                                    $sql = "SELECT departman FROM `departmanlar` WHERE id = :id";
                                                    $sth = $conn->prepare($sql);
                                                    $sth->bindParam("id", $departman_id);
                                                    $sth->execute();
                                                    $isdeki_departmanlar[] = $sth->fetch(PDO::FETCH_ASSOC);
                                                }
                                                
                                                $sql = "SELECT makina_adi,makina_modeli,durumu FROM `makinalar`  WHERE id = :id";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam("id", $makinalar[$uretimdeki_is['mevcut_asama']]);
                                                $sth->execute();
                                                $makina = $sth->fetch(PDO::FETCH_ASSOC);

                                                $sql = "SELECT SUM(uretilen_adet) AS uretilen_adet FROM `uretilen_adetler` 
                                                        WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam("planlama_id", $uretimdeki_is['planlama_id']);
                                                $sth->bindParam("mevcut_asama", $uretimdeki_is['mevcut_asama']);
                                                $sth->execute();
                                                $uretilen_adet = $sth->fetch(PDO::FETCH_ASSOC);
                                                $uretilen_adet = empty($uretilen_adet['uretilen_adet']) ? 0 : $uretilen_adet['uretilen_adet'];

                                                $makina_durum_class = '';
                                                if($makinalar[$uretimdeki_is['mevcut_asama']] != 0 && $makina['durumu'] == 'bakimda')      $makina_durum_class = 'table-warning';
                                                elseif($makinalar[$uretimdeki_is['mevcut_asama']] != 0 && $makina['durumu'] == 'pasif')    $makina_durum_class = 'table-danger';

                                                
                                                $sql = "SELECT personeller.ad, personeller.soyad FROM `uretim_islem_tarihler` 
                                                        JOIN personeller ON personeller.id = uretim_islem_tarihler.personel_id
                                                        WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam("planlama_id", $uretimdeki_is['planlama_id']);
                                                $sth->bindParam("mevcut_asama", $uretimdeki_is['mevcut_asama']);
                                                $sth->execute();
                                                $calisan_usta = $sth->fetch(PDO::FETCH_ASSOC);
                                                //print_r($calisan_usta); 

                                                $sql = "SELECT * FROM `uretim_dosyalar`  WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam("planlama_id", $uretimdeki_is['planlama_id']);
                                                $sth->bindParam("mevcut_asama", $uretimdeki_is['mevcut_asama']);
                                                $sth->execute();
                                                $uretim_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                $adetler[$uretimdeki_is['mevcut_asama']] = $adetler[$uretimdeki_is['mevcut_asama']] == 0 ? 1 : $adetler[$uretimdeki_is['mevcut_asama']];
                                                $asama_yuzdesi  = round($uretilen_adet*100/$adetler[$uretimdeki_is['mevcut_asama']],2);

                                                $sql = "SELECT * FROM `uretim_islem_tarihler` WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama ORDER BY id DESC LIMIT 1";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam("planlama_id", $uretimdeki_is['planlama_id']);
                                                $sth->bindParam("mevcut_asama", $uretimdeki_is['mevcut_asama']);
                                                $sth->execute();
                                                $uretim_islem_tarih = $sth->fetch(PDO::FETCH_ASSOC);

                                                $simdinin_tarihi = new DateTime(date('Y-m-d H:i:s'));
                                                $is_baslama_tarihi = new DateTime(isset($uretim_islem_tarih['baslatma_tarih']) ? $uretim_islem_tarih['baslatma_tarih'] : date('Y-m-d H:i:s'));

                                                $interval = $simdinin_tarihi->diff($is_baslama_tarihi);

                                                $sql = "SELECT sorun_bildirisin_mi FROM `uretim_degistir_loglar` WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam("planlama_id", $uretimdeki_is['planlama_id']);
                                                $sth->bindParam("mevcut_asama", $uretimdeki_is['mevcut_asama']);
                                                $sth->execute();
                                                $is_degislikleri = $sth->fetchAll(PDO::FETCH_ASSOC);

                                                $is_degisiklig_sorun_bildirisin_class = '';
                                                foreach ($is_degislikleri as $is_degislik) {
                                                    if($is_degislik['sorun_bildirisin_mi'] == 1) $is_degisiklig_sorun_bildirisin_class = 'table-danger';
                                                }
                                            ?>
                                            <tr class="<?php echo $makina_durum_class.' '.$is_degisiklig_sorun_bildirisin_class; ?>">
                                                <th class="table-primary">
                                                    <?php if(!empty($is_degisiklig_sorun_bildirisin_class)){?>
                                                        <i class="fa-solid fa-circle-exclamation text-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="İş Değiştirip Sorun Bildiriyi İşaretlenmiş"></i>
                                                    <?php }?>
                                                    <?php echo $index + 1; ?>
                                                </th>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm fw-bold text-decoration-underline siparis-detay" data-bs-toggle="tooltip" data-bs-placement="bottom" 
                                                        data-bs-html="true" data-bs-custom-class="custom-tooltip" 
                                                        data-bs-title="<b><i class='fa-regular fa-rectangle-list'></i> Sipariş Detayları</b>"
                                                        data-planlama-id="<?php echo $uretimdeki_is['planlama_id']?>"
                                                    >                                                 
                                                        <?php echo $uretimdeki_is['siparis_no']; ?>
                                                    </button>
                                                </td>
                                                <td><?php echo $uretimdeki_is['marka']; ?></td>
                                                <td><?php echo $uretimdeki_is['isin_adi'].'/'.$uretimdeki_is['isim']; ?></td>
                                                
                                                <td><?php echo date('d-m-Y', strtotime($uretimdeki_is['termin'])); ?></td>
                                                <td>
                                                    <ul class="list-group">
                                                        <li class="list-group-item">
                                                            <span class="badge bg-secondary">
                                                                <?php echo $mevcut_asama_sonuc; ?> / 
                                                                <?php echo $uretimdeki_is['asama_sayisi']?>
                                                            </span>
                                                            <?php if($uretimdeki_is['mevcut_asama'] < $uretimdeki_is['asama_sayisi'] ){ ?>
                                                                <?php 
                                                                    $departmanlar = json_decode($uretimdeki_is['departmanlar'], true); 
                                                                    $mevcut_departman_id = $departmanlar[$uretimdeki_is['mevcut_asama']];

                                                                    $sql = "SELECT departman FROM `departmanlar` WHERE id = :id";
                                                                    $sth = $conn->prepare($sql);
                                                                    $sth->bindParam('id', $mevcut_departman_id);
                                                                    $sth->execute();
                                                                    $departman = $sth->fetch(PDO::FETCH_ASSOC);

                                                                    $isteki_departmanlar_html = '<ul class="list-group fw-bold text-left">
                                                                        <li class="list-group-item active" aria-current="true">Aşamalar</li>
                                                                    ';
                                                                    foreach ($isdeki_departmanlar as $index_departman_id => $isdeki_departman) {
                                                                        $aktif_class = $uretimdeki_is['mevcut_asama'] == $index_departman_id ? 'list-group-item-success':'';
                                                                        $isteki_departmanlar_html .= '<li class="list-group-item '.$aktif_class.'">'.($index_departman_id +1).'-'.$isdeki_departman['departman'].'</li>';
                                                                    }

                                                                    $isteki_departmanlar_html .= '</ul>';
                                                                ?>
                                                                
                                                                <span class="badge bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                    data-bs-title='<?php echo $isteki_departmanlar_html; ?>' data-bs-html="true" 
                                                                > 
                                                                    <?php echo $departman['departman']; ?>  
                                                                </span>
                                                            <?php }else{ ?>
                                                                <span class="badge bg-success"> Bitti </span>              
                                                            <?php } ?>
                                                            <span class="badge bg-secondary">
                                                                <?php echo number_format($uretilen_adet); ?>/
                                                                <?php echo number_format($adetler[$uretimdeki_is['mevcut_asama']]); ?>
                                                            </span>
                                                            <span class="badge bg-secondary fw-bold fs-6 mt-1">
                                                                Geçen Süre: <?php echo $interval->format('%H') . ":" . $interval->format('%I');?>
                                                            </span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <div class="progress" role="progressbar"  aria-valuenow="<?php echo $asama_yuzdesi;?>" aria-valuemin="0" aria-valuemax="100" style="height: 25px">
                                                                <div class="progress-bar progress-bar-striped fw-bold <?php echo $uretimdeki_is['asama_sayisi'] == $uretimdeki_is['mevcut_asama'] ? 'bg-success' :''; ?>" style="width: <?php echo $asama_yuzdesi; ?>%">
                                                                    <?php echo $asama_yuzdesi; ?>%
                                                                </div>
                                                            </div>   
                                                        </li>
                                                    </ul>
                                                </td>
                                                <td class="table-success fw-bold">
                                                    <?php echo isset($calisan_usta['ad']) ? $calisan_usta['ad']:''; ?>             
                                                    <?php echo isset($calisan_usta['soyad']) ? $calisan_usta['soyad']:''; ?>             
                                                </td>
                                                <td>
                                                    <?php if($makinalar[$uretimdeki_is['mevcut_asama']] == 0){?>
                                                        <span>FASON</span>
                                                    <?php }else{ ?>
                                                        <?php echo $makina['makina_adi'].' '.$makina['makina_modeli'];?>
                                                    <?php }?>
                                                </td>
                                                <td class="text-end">
                                                    <?php 
                                                        $sql = 'SELECT uretim_mesaj_log_id 
                                                                FROM uretim_mesaj_log_gorunum_durumu 
                                                                WHERE personel_id = :personel_id AND grup_kodu = :grup_kodu
                                                                ORDER BY uretim_mesaj_log_id DESC LIMIT 1';
                                                        $sth = $conn->prepare($sql);
                                                        $sth->bindParam("grup_kodu", $uretimdeki_is['grup_kodu']);
                                                        $sth->bindParam("personel_id", $_SESSION['personel_id']);
                                                        $sth->execute();
                                                        $son_kullanan_uretim_mesaj_log = $sth->fetch(PDO::FETCH_ASSOC);
                                                        $son_kullanan_uretim_mesaj_log_id = isset($son_kullanan_uretim_mesaj_log['uretim_mesaj_log_id']) ? $son_kullanan_uretim_mesaj_log['uretim_mesaj_log_id'] : 0;

                                                        $sql = "SELECT COUNT(*) AS mesaj_sayisi FROM `uretim_mesaj_log`  
                                                                WHERE planlama_id = :planlama_id AND id > :id";
                                                        $sth = $conn->prepare($sql);
                                                        $sth->bindParam("planlama_id", $uretimdeki_is['planlama_id']);
                                                        $sth->bindParam("id", $son_kullanan_uretim_mesaj_log_id);
                                                        $sth->execute();
                                                        $mesaj_sayisi = $sth->fetch(PDO::FETCH_ASSOC);
                                                        $mesaj_sayisi = empty($mesaj_sayisi['mesaj_sayisi']) ? 0 : $mesaj_sayisi['mesaj_sayisi'];

                                                        //echo $uretimdeki_is['planlama_id'].' '.$son_kullanan_uretim_mesaj_log_id;
                                                        
                                                    ?>
                                                    <a href="javascript:;" class="mesajlar" 
                                                        data-planlama-id="<?php echo $uretimdeki_is['planlama_id']; ?>"  
                                                        data-departman-id="<?php echo $departmanlar[$uretimdeki_is['mevcut_asama']]; ?>"
                                                        data-makina-id="<?php echo $makinalar[$uretimdeki_is['mevcut_asama']]; ?>"
                                                        data-mevcut-asama="<?php echo $uretimdeki_is['mevcut_asama']; ?>"
                                                        data-grup-kodu="<?php echo $uretimdeki_is['grup_kodu']; ?>"
                                                        style="font-size:27px !important;margin-right:10px;"
                                                    >
                                                        <i class="fa-solid fa-envelope  position-relative">
                                                            <span class="position-absolute top-10 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px">
                                                                <?php echo $mesaj_sayisi; ?>
                                                            </span>
                                                        </i>
                                                    </a>
                                                </td>
                                                <td class="text-end">
                                                    <a href="javascript:;" class="is-degisikligi" 
                                                        data-planlama-id="<?php echo $uretimdeki_is['planlama_id']; ?>"
                                                        data-mevcut-asama="<?php echo $uretimdeki_is['mevcut_asama']; ?>"
                                                        style="font-size:27px !important;margin-right:10px;"
                                                    >
                                                        <i class="fa-solid fa-arrows-rotate position-relative">
                                                            <span class="position-absolute top-10 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px">
                                                                <?php echo count($is_degislikleri); ?>
                                                            </span>
                                                        </i>
                                                    </a>
                                                </td>
                                                <td class="text-end"><?php echo number_format($uretimdeki_is['uretilecek_adet']); ?></td>
                                                <td>
                                                    <?php foreach ($uretim_dosyalar as $uretim_dosya) { ?>
                                                        <a class="text-decoration-none example-image-link" href="dosyalar/uretim-dosyalar/<?php echo $uretim_dosya['dosya_adi']; ?>" 
                                                            data-lightbox="example-set-<?php echo $index; ?>" data-title="">
                                                            <img src="dosyalar/uretim-dosyalar/<?php echo $uretim_dosya['dosya_adi']; ?>" 
                                                                class="rounded img-thumbnai border border-secondary-subtle object-fit-fill mb-1 mt-1" 
                                                                style="height:50px; min-height:50px; width:50px;"
                                                                loading="lazy"
                                                            >
                                                        </a>
                                                    <?php }?>            
                                                </td>
                                                <td class="text-center">
                                                    <a href="/index.php?url=planla_siparis_duzenle&siparis_id=<?php echo $uretimdeki_is['siparis_id'];?>&alt-urun-id=<?php echo $uretimdeki_is['alt_urun_id'];?>" target="_blank">
                                                        <i class="fa-solid fa-list-check fa-2x"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php }?>
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>

                        <div class="tab-pane fade" id="nav-uretime-girmeyen-isler" role="tabpanel" 
                            aria-labelledby="nav-tab-uretime-girmeyen-isler" tabindex="1">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-hover" >
                                    <thead class="table-primary">
                                        <tr>
                                            <th>#</th>
                                            <th>Sipariş No</th>
                                            <th>İşin Adı/Alt Ürün</th>
                                            <th>Müşteri</th>
                                            <th>Termin</th>
                                            <th class="text-end">Adet</th>
                                            <th class="text-center">Link</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($henuz_baslamis_isler as $index => $henuz_baslamis_is) { ?>
                                            
                                            <tr >
                                                <th class="table-primary"><?php echo $index + 1; ?></th>
                                                <td >
                                                    <button type="button" class="btn btn-primary btn-sm fw-bold text-decoration-underline siparis-detay" data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" data-bs-html="true" data-bs-custom-class="custom-tooltip" 
                                                        data-bs-title="<b><i class='fa-regular fa-rectangle-list'></i> Sipariş Detayları</b>"
                                                        data-planlama-id="<?php echo $henuz_baslamis_is['planlama_id']?>"
                                                    >                                                 
                                                        <?php echo $henuz_baslamis_is['siparis_no']; ?>
                                                    </button>
                                                </td>
                                                <th>
                                                    <?php echo $henuz_baslamis_is['isin_adi'].' / '.$henuz_baslamis_is['isim']; ?>
                                                </th>
                                                <td><?php echo $henuz_baslamis_is['marka']; ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($henuz_baslamis_is['termin'])); ?></td>
                                                <td class="text-end"><?php echo number_format($henuz_baslamis_is['uretilecek_adet']); ?></td>
                                                <td class="text-center">
                                                    <div class="d-md-flex justify-content-end"> 
                                                        <div class="btn-group" role="group" aria-label="Basic example">
                                                            <a href="/index.php?url=planla_siparis_duzenle&siparis_id=<?php echo $henuz_baslamis_is['siparis_id'];?>" target="_blank">
                                                                <i class="fa-solid fa-list-check fa-2x"></i>
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

                        <div class="tab-pane fade" id="nav-eksik-uretimde-onay-bekleyen" role="tabpanel" 
                            aria-labelledby="nav-tab-eksik-uretimde-onay-bekleyen" tabindex="1">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-hover" >
                                    <thead class="table-primary">
                                        <tr>
                                            <th>#</th>
                                            <th>Sipariş No</th>
                                            <th>İşin Adı</th>
                                            <th>Müşteri</th>
                                            <th>Termin</th>
                                            <th class="text-end">Eksik Adet</th>
                                            <th class="text-end">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eksik_uretilip_onay_bekleyenler as $index => $eksik_uretilip_onay_bekleyen) { ?>
                                            
                                            <tr>
                                                <th class="table-primary"><?php echo $index + 1; ?></th>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm fw-bold text-decoration-underline siparis-detay" 
                                                        data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" data-bs-custom-class="custom-tooltip" 
                                                        data-bs-title="<b><i class='fa-regular fa-rectangle-list'></i> Sipariş Detayları</b>"
                                                        data-planlama-id="<?php echo $eksik_uretilip_onay_bekleyen['planlama_id']?>"
                                                    >                                                 
                                                        <?php echo $eksik_uretilip_onay_bekleyen['siparis_no']; ?>
                                                    </button>
                                                </td>
                                                <td><?php echo $eksik_uretilip_onay_bekleyen['isin_adi']; ?></td>
                                                <td><?php echo $eksik_uretilip_onay_bekleyen['marka']; ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($eksik_uretilip_onay_bekleyen['termin'])); ?></td>
                                                <td class="text-end"><?php echo number_format($eksik_uretilip_onay_bekleyen['eksik_adet']); ?></td>
                                                
                                                <td>
                                                    <div class="d-flex justify-content-end">
                                                        <div class="btn-group" role="group" aria-label="Basic example">
                                                            <a href="/index.php?url=uretim_kontrol_db_islem&islem=eksik-adet-onay&planlama_id=<?php echo $eksik_uretilip_onay_bekleyen['planlama_id']; ?>"
                                                                onClick="return confirm('Onay Vermek İstediğinize Emin Misiniz?')"
                                                                class="btn btn-success"
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom" 
                                                                data-bs-title="Onay Ver"
                                                            >
                                                                <i class="fa-regular fa-circle-check"></i>
                                                            </a>
                                                            <a href="/index.php?url=uretim_kontrol_db_islem&islem=eksik-adet-iptal&planlama_id=<?php echo $eksik_uretilip_onay_bekleyen['planlama_id']; ?>"
                                                                onClick="return confirm('Onay İptalini Yapmak İstediğinize Emin Misiniz?')"
                                                                class="btn btn-danger"
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom" 
                                                                data-bs-title="Onay İptal"
                                                            >
                                                                <i class="fa-regular fa-circle-xmark"></i>
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

        <!-- Mesaj Modal -->
        <div class="modal fade" id="mesaj-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="mesajStaticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fs-5" id="mesajStaticBackdropLabel">
                            <i class="fa-solid fa-envelope"></i>
                            Mesaj İşlemi
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form class="row g-3 needs-validation mb-2" id="mesaj-gonder-form" action="/index.php?url=uretim_kontrol_db_islem" method="POST">
                            <input type="hidden" name="departman_id" id="departman_id">
                            <input type="hidden" name="planlama_id" id="planlama_id">
                            <input type="hidden" name="makina_id" id="makina_id">
                            <input type="hidden" name="mevcut_asama" id="mevcut_asama">
                            <input type="hidden" name="grup_kodu" id="grup-kodu">
                            <div class="form-floating col-md-12">
                                <textarea name="mesaj" id="mesaj" class="form-control" style="height: 150px;"></textarea>
                                <label for="mesaj" class="form-label">Mesaj</label>
                            </div>
                            <div class="form-floating col-md-12">
                                <button type="submit" class="btn btn-success" name="mesaj-gonder" id="mesaj-gonder-button">
                                    <i class="fa-regular fa-paper-plane"></i>  GÖNDER
                                </button>
                            </div>
                        </form>
                        <div class="table-responsive">                    
                            <table  class="table table-bordered table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Tarih</th>
                                        <th>Personel Ad Soyad</th>
                                        <th>Departman</th>
                                        <th>Makina Adı/Model</th>
                                        <th style="width:40% !important">Mesaj</th>
                                    </tr>
                                </thead>
                                <tbody id="uretim-mesaj-log"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- İş Değiştir Modal -->
        <div class="modal fade" id="is-degistir-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">
                            <i class="fa-solid fa-rotate"></i> İş Değiştir Log
                        </h1>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">                    
                            <table  class="table table-bordered table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Tarih</th>
                                        <th>Sorun Bildirme</th>
                                        <th style="width:40% !important">Mesaj</th>
                                    </tr>
                                </thead>
                                <tbody id="is-degistir-mesaj-log"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
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

        <?php 
            include_once "include/uyari_session_oldur.php"; 
        ?>

        <script>
            $(function(){
                // Anlık veri güncellemesi
                var guncellemeAktif = true;
                var guncellemeSikligi = 20000; // 20 saniye
                
                function verilerıGuncelle() {
                    if (!guncellemeAktif) return;
                    
                    // Durum badge'ini sarıya çevir (yükleniyor)
                    $('#realtime-durum')
                        .removeClass('bg-success bg-danger')
                        .addClass('bg-warning')
                        .html('<i class="fa-solid fa-spinner fa-spin"></i> Güncelleniyor...');
                    
                    $.ajax({
                        url: '/index.php?url=uretim_kontrol_db_islem&islem=realtime-uretim-kontrol',
                        type: 'GET',
                        dataType: 'json',
                        timeout: 8000,
                        success: function(response) {
                            if (response.success) {
                                // Üretimdeki işler sayısını güncelle
                                var uretimdekiElement = $('[data-realtime="uretimdeki-isler-sayi"]');
                                var mevcutUretimdeki = parseInt(uretimdekiElement.text().replace(/[^0-9]/g, ''));
                                var yeniUretimdeki = response.uretimdeki_isler_sayi;
                                
                                if (mevcutUretimdeki !== yeniUretimdeki) {
                                    uretimdekiElement.fadeOut(200, function() {
                                        $(this).html(yeniUretimdeki.toLocaleString('tr-TR') + '<span class="visually-hidden">Üretimdeki İşler</span>').fadeIn(200);
                                    });
                                }
                                
                                // Henüz başlamış işler sayısını güncelle
                                var henuzElement = $('[data-realtime="henuz-baslamis-isler-sayi"]');
                                var mevcutHenuz = parseInt(henuzElement.text().replace(/[^0-9]/g, ''));
                                var yeniHenuz = response.henuz_baslamis_isler_sayi;
                                
                                if (mevcutHenuz !== yeniHenuz) {
                                    henuzElement.fadeOut(200, function() {
                                        $(this).html(yeniHenuz.toLocaleString('tr-TR') + '<span class="visually-hidden">Üretime Henüz Girmeyen İşler</span>').fadeIn(200);
                                    });
                                }
                                
                                // Eksik onay bekleyen sayısını güncelle
                                var eksikElement = $('[data-realtime="eksik-onay-bekleyen-sayi"]');
                                var mevcutEksik = parseInt(eksikElement.text().replace(/[^0-9]/g, ''));
                                var yeniEksik = response.eksik_onay_bekleyen_sayi;
                                
                                if (mevcutEksik !== yeniEksik) {
                                    eksikElement.fadeOut(200, function() {
                                        $(this).html(yeniEksik.toLocaleString('tr-TR') + '<span class="visually-hidden">Üretime Henüz Girmeyen İşler</span>').fadeIn(200);
                                    });
                                }
                                
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

                // Diğer fonksiyonlar devam ediyor...
                //Mesaj Gönder Formu Submit Edildiğinde Buttonu Pasife Alma
                $("#mesaj-gonder-form").submit(function(){
                    $("#mesaj-gonder-button").addClass('disabled');
                    return true;
                });

                $(".siparis-detay").click(function(){
                    const planlamaId = $(this).data('planlama-id');
                    $.ajax({
                        url         : '/index.php?url=uretim_kontrol_db_islem&islem=siparis-detay', 
                        dataType    : 'JSON', 
                        data        : {planlama_id:planlamaId},
                        type        : 'POST',
                        success     : function (data) {
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
                            

                            $("#siparis-detay-body").html(`
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            <li class="list-group-item active fw-bold" aria-current="true">Sipariş Detay</li>
                                            <li class="list-group-item"><b>Türü   : </b> ${data.planlama.tur}</li>
                                            <li class="list-group-item"><b>Ü.Adet : </b> ${data.planlama.uretilecek_adet}</li>
                                            <li class="list-group-item list-group-item-primary"><b>Teslimat Adresi : </b> ${data.planlama.teslimat_adresi}</li>
                                            <li class="list-group-item list-group-item-primary"><b>Teslimat Ülkesi : </b> ${data.planlama.ulke_adi}</li>
                                            <li class="list-group-item list-group-item-primary"><b>Teslimat Şehri : </b> ${data.planlama.sehir_adi}</li>
                                            <li class="list-group-item list-group-item-primary"><b>Teslimat İlçesi : </b> ${data.planlama.ilce_adi}</li>
                                            <li class="list-group-item"><b>Termin Tarihi : </b> ${data.planlama.termin}</li>
                                            <li class="list-group-item"><b>Üretim Tarihi : </b> ${data.planlama.uretim}</li>
                                            <li class="list-group-item"><b>M. Temsilcisi : </b> ${data.planlama.ad} ${data.planlama.soyad}</li>
                                            <li class="list-group-item"><b>Müşteri  : </b> ${data.planlama.marka} / ${data.planlama.firma_unvani}</li>
                                            <li class="list-group-item"><b>Vade Tarihi  : </b> ${data.planlama.vade}</li>
                                            <li class="list-group-item"><b>Fiyat  : </b> ${data.planlama.fiyat} ${data.planlama.para_cinsi}</li>
                                            <li class="list-group-item"><b>Ödeme Şekli  : </b> ${data.planlama.odeme_sekli}</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            <li class="list-group-item list-group-item-success fw-bold" aria-current="true">${data.planlama.alt_urun_id}. Alt Ürün</li>
                                            <li class="list-group-item"><b>İsim         : </b> ${veriler.isim}</li>
                                            <li class="list-group-item"><b>Miktar       : </b> ${veriler.miktar}</li>
                                            <li class="list-group-item"><b>Birim Fiyat  : </b> ${veriler.birim_fiyat}</li>
                                            <li class="list-group-item"><b>KDV          : </b> ${veriler.kdv}</li>
                                            <li class="list-group-item"><b>Numune       : </b> ${veriler.numune == 1 ? '<span class="badge text-bg-success">VAR</span>':'<span class="badge text-bg-danger">YOK</span>'}</li>
                                            <li class="list-group-item"><b>Açıklama     : </b> ${veriler.aciklama}</li>
                                            ${formHTML}
                                        </ul>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h5>
                                            <i class="fa-regular fa-image"></i> Sipariş Dosyaları
                                        </h5>
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
                
                //Mesajları Getirme
                $(document).on('click', '.mesajlar', function(event) {
                    event.preventDefault(); 

                    const _this = $(this);
                    const planlama_id   = $(this).data('planlama-id');
                    const departman_id  = $(this).data('departman-id');
                    const makina_id     = $(this).data('makina-id');
                    const mevcut_asama  = $(this).data('mevcut-asama');
                    const grup_kodu     = $(this).data('grup-kodu');

                    log("planlama_id = " , planlama_id)

                    $("#planlama_id").val(planlama_id);
                    $("#departman_id").val(departman_id);
                    $("#makina_id").val(makina_id);
                    $("#mevcut_asama").val(mevcut_asama);
                    $("#grup-kodu").val(grup_kodu);

                    $.get(`/index.php?url=makina_is_ekran_db_islem&islem=mesajlari-gordu&grup_kodu=${grup_kodu}`, function(data, status){
                        $(_this).find('i span').text(0);
                    });

                    //$("#mesaj-modal").modal('show');
                    $.ajax({
                        url         : '/index.php?url=uretim_kontrol_db_islem&islem=mesajlari-cek', 
                        dataType    : 'JSON', 
                        data        : {planlama_id},
                        type        : 'POST',
                        success     : function (data) {
                            let mesajlarHTML = '';
                            data.mesajlar.forEach((mesaj, index)=>{
                                mesajlarHTML += `
                                    <tr>
                                        <th>${index+1}</th>
                                        <td>${mesaj.tarih}</td>
                                        <td>${mesaj.ad} ${mesaj.soyad}</td>
                                        <td>${mesaj.departman}</td>
                                        <td>${mesaj.makina_adi} ${mesaj.makina_modeli}</td>
                                        <td style="width:40% !important">${mesaj.mesaj}</td>
                                    </tr>
                                `;
                            });

                            $("#uretim-mesaj-log").html(mesajlarHTML);
                            $("#mesaj-modal").modal('show');
                            
                        },
                        error: function (response) {
                            $.notify("Veri Çekme Başarısız(Error)","error");
                        }
                    });
                });

                //İş Değiştir Logları Getirme
                $(document).on('click', '.is-degisikligi', function(event) {
                    event.preventDefault();
                    
                    const planlama_id   = $(this).data('planlama-id');
                    const mevcut_asama  = $(this).data('mevcut-asama');

                    $.ajax({
                        url         : '/index.php?url=uretim_kontrol_db_islem&islem=is-degistir-log-cek', 
                        dataType    : 'JSON', 
                        data        : {planlama_id, mevcut_asama},
                        type        : 'POST',
                        success     : function (data) {
                            let mesajlarHTML = '';
                            let sorun_bildirisin_mi = '';
                            data.is_degistir_loglar.forEach((mesaj, index)=>{
                                sorun_bildirisin_mi = mesaj.sorun_bildirisin_mi == 1 ? '<span class="badge bg-danger">EVET</span>':'<span class="badge bg-secondary">HAYIR</span>';
                                mesajlarHTML += `
                                    <tr>
                                        <th>${index+1}</th>
                                        <td>${mesaj.tarih}</td>
                                        <td class="text-end">${sorun_bildirisin_mi}</td>
                                        <td style="width:40% !important">${mesaj.degistirme_sebebi}</td>
                                    </tr>
                                `;
                            });

                            $("#is-degistir-mesaj-log").html(mesajlarHTML);
                            $("#is-degistir-modal").modal('show');
                        },
                        error: function (response) {
                            $.notify("Veri Çekme Başarısız(Error)","error");
                        }
                    });
                });
            });
        </script>