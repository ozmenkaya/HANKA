<?php
    require_once "include/oturum_kontrol.php";

    $planlama_id    = isset($_GET['planlama-id'])   ? intval($_GET['planlama-id']) : 0;
    $makina_id      = isset($_GET['makina-id'])     ? intval($_GET['makina-id']) : 0;

    $sql = "SELECT planlama.isim, siparisler.siparis_no, siparisler.isin_adi, siparisler.adet, planlama.mevcut_asama,planlama.asama_sayisi,planlama.uretilecek_adet,
        planlama.sureler, planlama.adetler, planlama.stok_alt_kalemler, planlama.durum, planlama.departmanlar, planlama.durum,`planlama`.`tekil_kod`,
        planlama.tekil_kod, planlama.stok_alt_depo_adetler, planlama.detaylar,planlama.stok_alt_depolar,`planlama`.`stok_alt_kalemler`,`planlama`.`arsiv_altlar`,
        planlama.grup_kodu,planlama.grup_kodu,planlama.orijinal_adetler,
        siparisler.isin_adi,siparisler.termin, siparisler.aciklama,siparisler.id AS siparis_id, siparisler.musteri_id, siparisler.tip_id,siparisler.paketleme,`siparisler`.`veriler`,
        `planlama`.`alt_urun_id`,planlama.fason_durumlar, birimler.ad AS birim_ad, `planlama`.`asamada_eksik_adet_varmi`
        FROM `planlama` 
        JOIN siparisler ON siparisler.id = planlama.siparis_id 
        JOIN birimler ON birimler.id = siparisler.birim_id 
        WHERE planlama.id = :id AND planlama.firma_id = :firma_id AND aktar_durum = 'orijinal'";

    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $planlama_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $is = $sth->fetch(PDO::FETCH_ASSOC);

    //$stok_alt_kalemler  = json_decode($is['stok_alt_kalemler']);
    //$stok_alt_kalemler  = isset($stok_alt_kalemler[$is['mevcut_asama']]) ? $stok_alt_kalemler[$is['mevcut_asama']]:[];

    $stok_alt_depolar  = json_decode($is['stok_alt_depolar']);
    $stok_alt_depolar  = isset($stok_alt_depolar[$is['mevcut_asama']]) ? $stok_alt_depolar[$is['mevcut_asama']]:[];

    //echo "<pre>"; print_r($stok_alt_depolar); exit;

    $planlanmis_stok_veriler = [];
    foreach ($stok_alt_depolar as $index => $stok_alt_depo) {
        if($stok_alt_depo == 0){ continue; }
        $sql = "SELECT birimler.ad AS birim_ad,birimler.id AS birim_id,
        stok_alt_kalemler.veri,`stok_kalemleri`.`stok_kalem`, 
        stok_kalemleri.id AS stok_id ,stok_alt_kalemler.id AS stok_alt_kalem_id,
        stok_alt_depolar.id AS stok_alt_depo_id, `stok_alt_depolar`.`stok_kodu`
        FROM `stok_alt_depolar` 
        JOIN birimler ON stok_alt_depolar.birim_id = birimler.id
        JOIN stok_alt_kalemler ON stok_alt_kalemler.id = stok_alt_depolar.stok_alt_kalem_id
        JOIN stok_kalemleri ON stok_kalemleri.id = stok_alt_kalemler.stok_id
        WHERE stok_alt_depolar.id = :id AND stok_alt_depolar.firma_id = :firma_id";
        
        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $stok_alt_depo);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $planlanmis_stok = $sth->fetch(PDO::FETCH_ASSOC);

        if(!empty($planlanmis_stok)){
            $planlanmis_stok_veriler[] = $planlanmis_stok;
        }
    }

    //echo "<pre>"; print_r($stok_alt_kalemler);
    //echo "<pre>"; print_r($planlanmis_stok_veriler); exit;

    
    if(empty($is))
    {
        include "include/yetkisiz.php"; exit;
    }

    $sql = "SELECT makina_adi,makina_modeli,makina_ayar_suresi_varmi 
            FROM makinalar WHERE id = :id AND firma_id = :firma_id AND durumu = 'aktif'";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $makina_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $makina = $sth->fetch(PDO::FETCH_ASSOC);

    
    if(empty($makina))
    {
        include "include/yetkisiz.php";
        exit;
    }

    $departmanlar = json_decode($is['departmanlar']);
    $departman_id = $departmanlar[$is['mevcut_asama']];

    $sql = "SELECT makinalar,mevcut_asama 
        FROM `planlama` 
        WHERE firma_id = :firma_id 
        AND durum IN('baslamadi','basladi','beklemede') 
        AND onay_durum = 'evet' AND aktar_durum = 'orijinal'";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlama_isler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $makinadaki_is_sayisi = 0;
    foreach ($planlama_isler as $planlama_is) {
        $makinalar = json_decode($planlama_is['makinalar']);
        if(isset($makinalar[$planlama_is['mevcut_asama']]) && $makinalar[$planlama_is['mevcut_asama']] == $makina_id ) {
            $makinadaki_is_sayisi++;
        }
    }
    

    $sql ="SELECT departman,sorumlu_personel_idler FROM `departmanlar` WHERE id = :id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $departman_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departman = $sth->fetch(PDO::FETCH_ASSOC);

    if ($departman === false || empty($departman)) {
        $sorumlu_personel_idler = [];
    } else {
        $sorumlu_personel_idler = json_decode($departman['sorumlu_personel_idler'], true); // JSON'u diziye çevir
        $sorumlu_personel_idler = array_filter($sorumlu_personel_idler);
    }
    
    $departmandan_yetkili_kisiler = [];
    if(!empty($sorumlu_personel_idler)){
        $sorumlu_personel_idler = implode(',',$sorumlu_personel_idler);
        $sql ="SELECT id, ad, soyad FROM `personeller` WHERE firma_id = :firma_id AND  id IN($sorumlu_personel_idler)";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $departmandan_yetkili_kisiler = $sth->fetchAll(PDO::FETCH_ASSOC);
    }
    

    //echo "<pre>"; print_r($departmandan_yetkili_kisiler); exit;


    $sql = "SELECT * FROM `siparis_dosyalar` WHERE siparis_id = :siparis_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id',$is['siparis_id']);
    $sth->execute();
    $siparis_resimler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM `makinalar` 
            WHERE id != :id AND firma_id = :firma_id AND departman_id = :departman_id AND durumu = 'aktif'";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id',$makina_id);
    $sth->bindParam('firma_id',$_SESSION['firma_id']);
    $sth->bindParam('departman_id',$departman_id);
    $sth->execute();
    $calisan_makina_haric_departmandaki_makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);
    $departmandaki_makina_sayisi = count($calisan_makina_haric_departmandaki_makinalar);

    //echo $departmandaki_makina_sayisi; exit;

    $sql = "SELECT SUM(uretilen_adet) AS uretilen_adet FROM `uretilen_adetler` 
            WHERE grup_kodu = :grup_kodu AND firma_id = :firma_id 
            AND mevcut_asama = :mevcut_asama";
    
    $sth = $conn->prepare($sql);
    $sth->bindParam('grup_kodu',$is['grup_kodu']);
    $sth->bindParam('firma_id',$_SESSION['firma_id']);
    $sth->bindParam('mevcut_asama',$is['mevcut_asama']);
    $sth->execute();
    $toplam_uretilen_adet = $sth->fetch(PDO::FETCH_ASSOC);
    $toplam_uretilen_adet = empty($toplam_uretilen_adet['uretilen_adet']) ? 0 : $toplam_uretilen_adet['uretilen_adet'];

    $adetler                = json_decode($is['adetler'], true);
    $orijinal_adetler       = json_decode($is['orijinal_adetler'], true);
    $toplam_uretilecek_adet = isset($adetler[$is['mevcut_asama']]) ? $adetler[$is['mevcut_asama']] : 0;

    //echo $toplam_uretilecek_adet; exit;

    $sql = "SELECT uretim_mesaj_log.id, uretim_mesaj_log.mesaj, uretim_mesaj_log.tarih,
        personeller.ad, personeller.soyad, 
        departmanlar.departman,
        makinalar.makina_adi,makinalar.makina_modeli
        FROM `uretim_mesaj_log` 
        JOIN personeller ON personeller.id = uretim_mesaj_log.personel_id 
        JOIN departmanlar ON departmanlar.id = uretim_mesaj_log.departman_id
        JOIN makinalar ON makinalar.id = uretim_mesaj_log.makina_id
        WHERE uretim_mesaj_log.grup_kodu = :grup_kodu ORDER BY uretim_mesaj_log.id DESC
        ";

    $sth = $conn->prepare($sql);
    $sth->bindParam("grup_kodu", $is['grup_kodu']);
    $sth->execute();
    $mesajlar = $sth->fetchAll(PDO::FETCH_ASSOC);


    $sql = "SELECT eksik_uretimde_onay_isteme_durumu FROM `firmalar`  WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("id", $_SESSION['firma_id']);
    $sth->execute();
    $firma_ayar = $sth->fetch(PDO::FETCH_ASSOC);

    $fason_durumlar = json_decode($is['fason_durumlar']);

    //print_r($firma_ayar); exit;

    $sql = "SELECT SUM(aktarilan_adet) AS aktarilan_adet FROM `uretim_aktarma_loglar` 
            WHERE grup_kodu = :grup_kodu  AND aktarilan_asama = :aktarilan_asama";
    $sth = $conn->prepare($sql);
    $sth->bindParam('grup_kodu',$is['grup_kodu']);
    $sth->bindParam('aktarilan_asama',$is['mevcut_asama']);
    $sth->execute();
    $uretim_aktarilan_adet = $sth->fetch(PDO::FETCH_ASSOC);

    $uretim_aktarilan_adet =    empty($uretim_aktarilan_adet['aktarilan_adet']) ? 
                                $orijinal_adetler[$is['mevcut_asama']] : 
                                $uretim_aktarilan_adet['aktarilan_adet'];


    $kalan_adet = $uretim_aktarilan_adet-$toplam_uretilen_adet;

    $sql = "SELECT `personeller`.id, `personeller`.ad, `personeller`.soyad 
    FROM `makina_personeller` 
    JOIN personeller ON personeller.id = makina_personeller.personel_id
    WHERE `makina_personeller`.`makina_id` = :makina_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("makina_id", $makina_id);
    $sth->execute();
    $bakim_personeller = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Firma Bilgileri 
    $sql = "SELECT firma_adi, etiket_logo, domain_adi FROM firmalar WHERE id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->execute();
    $firma_data = $sth->fetch(PDO::FETCH_ASSOC);

    if($firma_data === false || empty($firma_data)) {
        $etiketLogo = 'varsayilan.svg';
    } else {
        $etiketLogo = $firma_data['etiket_logo'];
    }  

    if ($firma_data === false || empty($firma_data)) {
        $domain_adi = '';
    } else {
        $domain_adi = $firma_data['domain_adi'];
    }

    // Etiket Bilgileri
    $sql = "SELECT yukseklik, genislik FROM departman_planlama WHERE etiket_varmi  = 'var' and firma_id = :firma_id and departman_id = :departman_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("departman_id", $departman_id);
    $sth->execute();
    $etiket_data = $sth->fetch(PDO::FETCH_ASSOC);
    if($etiket_data === false || empty($etiket_data)) {
        $etiket_goster = false;
        $etiket_yukseklik = 0;
        $etiket_genislik  = 0;
    } else {
        $etiket_goster = true;
        $etiket_yukseklik = intval($etiket_data['yukseklik']);
        $etiket_genislik  = intval($etiket_data['genislik']);
    }  

    // Müşteri Bilgileri 
    $sql ="SELECT firma_unvani FROM musteri WHERE firma_id = :firma_id AND id = :musteri_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('musteri_id', $is['musteri_id']);
    $sth->execute();
    $musteri_data = $sth->fetch(PDO::FETCH_ASSOC);
    if($musteri_data === false || empty($musteri_data)) {
        $musteri_firma_unvani = '';
    } else {
        $musteri_firma_unvani = $musteri_data['firma_unvani'];
    }

    //print_r($bakim_personeller); exit;
?>
        <style>
            a{
                text-decoration:none;
            }
            a.disabled {
                cursor: no-drop;
            }
            .mevcut-asama{
                /*border-style:dashed;*/
                font-weight: bold;
                font-size:15px !important;
            }
            .uploadFile {
                width: 30%;
                background-color: white;
                border: 1px solid grey;
                color: grey;
                font-size: 10px;
                /*line-height: 23px;*/
                overflow: hidden;
                /*padding: 10px 10px 4px 10px;*/
                position: relative;
                resize: none;
                cursor: pointer !important;
                display: block;
                font-size: 999px;
                filter: alpha(opacity=0);
                min-height: 100%;
                min-width: 100%;
                opacity: 0;
                position: absolute;
                right: 0px;
                text-align: right;
                top: 0px;
                z-index: 1;
            }

            th.table-info2{
                padding-top: 6px;
                padding-bottom: 6px;
            }

            td.table-info2{
                padding-top: 6px;
                padding-bottom: 6px;
            }

            li.table-info2{
                padding-top: 6px;
                padding-bottom: 6px;
            }

        </style>
        <?php //require_once "include/header.php";?>
        <div class="container">
            <div class="card border-secondary mt-2 mb-1">
                <div class="card-header border-secondary d-md-flex align-items-center justify-content-between pt-0 pb-0">
                    <h5>
                        <i class="fa-solid fa-gears"></i>
                        <span class="badge bg-secondary"> <?php echo $departman['departman'].' - '.$makina['makina_adi'].'/'.$makina['makina_modeli']; ?> </span>
                    </h5>

                    <h5>
                        <i class="fa-regular fa-circle-user"></i> <b>USTA:</b> 
                        <span class="badge bg-secondary"> <?php echo  $_SESSION['ad'].' '.$_SESSION['soyad']; ?></span>
                    </h5>

                    <h5>
                        <i class="fa-solid fa-gears"></i>  <b>İş Sayısı:</b>  
                        <span class="badge bg-secondary" style="border-radius:50%"> 
                            <?php echo  $makinadaki_is_sayisi; ?>
                        </span>
                    </h5>

                    <?php if($etiket_goster){ ?>

                        <h5 
                            style="cursor:pointer" 
                            data-bs-toggle="modal" 
                            data-bs-target="#etiketModal"
                            data-bs-placement="bottom" 
                            data-bs-title="Ekle" 
                            >
                            <i class="fa-solid fa-print"></i><b> Etiket</b>  
                        </h5>

                    <?php } ?>

                    <?php 
                        $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                        WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 8";
                        $sth = $conn->prepare($sql);
                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                        $sth->bindParam('departman_id',$departman_id);
                        $sth->execute();
                        $mesaj_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                    ?>

                    <h5>
                        <?php if(!empty($mesaj_ayar) && $mesaj_ayar['durum'] == 1){ ?>
                            <?php 
                                $sql = 'SELECT uretim_mesaj_log_id 
                                        FROM uretim_mesaj_log_gorunum_durumu 
                                        WHERE personel_id = :personel_id 
                                        ORDER BY uretim_mesaj_log_id DESC LIMIT 1';
                                $sth = $conn->prepare($sql);
                                $sth->bindParam("personel_id", $_SESSION['personel_id']);
                                $sth->execute();
                                $son_kullanan_uretim_mesaj_log = $sth->fetch(PDO::FETCH_ASSOC);
                                $son_kullanan_uretim_mesaj_log_id = isset($son_kullanan_uretim_mesaj_log['uretim_mesaj_log_id']) ? $son_kullanan_uretim_mesaj_log['uretim_mesaj_log_id'] : 0;

                                $okunmayan_mesaj_sayisi = 0;
                                foreach ($mesajlar as $mesaj) {
                                    if($mesaj['id'] > $son_kullanan_uretim_mesaj_log_id) $okunmayan_mesaj_sayisi++;
                                }
                            ?>
                            <a href="javascript:;" data-bs-toggle="modal"  data-bs-target="#mesaj-modal" id="mesajlar"
                                style="font-size:27px !important;margin-right:10px;"
                            >
                                <i class="fa-solid fa-envelope  position-relative">
                                    <span class="position-absolute top-10 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px">
                                        <?php echo $okunmayan_mesaj_sayisi; ?>
                                    </span>
                                </i>
                            </a> 
                        <?php } ?>

                        <i class="fa-regular fa-clock"></i>
                        <span id="simdiki-saat" class="fw-bold"><?php echo date('H:i:s'); ?></span>
                        <a href="/index.php?url=makina_is_listesi&makina-id=<?php echo $makina_id; ?>" 
                            class="btn btn-primary fw-bold"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom"
                            data-bs-html="true" 
                            data-bs-title="<b class='fs-6'>Tüm İşlere Git</b>"
                        >
                            <i class="fa-solid fa-gears"></i> İşler
                        </a>
                        <a href="/index.php?url=makina_is_ekran&planlama-id=<?php echo $planlama_id; ?>&makina-id=<?php echo $makina_id;?>" 
                            class="btn btn-secondary fw-bold"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom"
                            data-bs-html="true" 
                            data-bs-title="<b class='fs-6'>Sayfayı Yenile</b>"
                        >
                            <i class="fa-solid fa-retweet"></i> 
                            <span id="geri-sayim">30000</span> sn
                        </a>
                    </h5>
                </div>
                <div class="card-body pt-0 pb-0">
                    <div class="row d-flex justify-content-center">
                        <div class="col-md-2 d-flex align-items-stretch">
                            <div class="card  flex-grow-1">
                                <div class="card-body pt-1 pb-1">
                                    <?php if($is['asamada_eksik_adet_varmi'] == 'var'){?>
                                        <div class="card text-bg-secondary">
                                            <div class="card-body text-center">
                                                <?php if($makina['makina_ayar_suresi_varmi'] == 'yok'){ ?>
                                                    <i class="fa-regular fa-circle-play fa-3x mb-2"></i>
                                                    <h5 class="card-title">
                                                        İŞİ BAŞLAT
                                                    </h5>
                                                <?php }else{ ?>
                                                    <i class="fa-solid fa-screwdriver-wrench fa-3x mb-2"></i>
                                                    <h5 class="card-title">
                                                        MAKİNA AYAR
                                                    </h5>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="alert alert-danger mt-2 fw-bold" role="alert">
                                            Eksik Mal Üretildiği İçin Onay Bekliyor
                                        </div>
                                    <?php }else if(in_array($is['durum'], ['baslamadi','beklemede'])){?>
                                        <a href="javascript:;" id="makina-ayar">
                                            <div class="card text-primary mb-3 border-primary">
                                                <div class="card-body text-center">
                                                    <?php if($makina['makina_ayar_suresi_varmi'] == 'yok'){ ?>
                                                        <i class="fa-regular fa-circle-play fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">
                                                            İŞİ BAŞLAT
                                                        </h5>
                                                    <?php }else{ ?>
                                                        <i class="fa-solid fa-screwdriver-wrench fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">
                                                            MAKİNA AYAR
                                                        </h5>
                                                    <?php }?>
                                                </div>
                                            </div>
                                        </a> 
                                    <?php }else{ ?>
                                        <div class="card text-primary mb-3 border-primary">
                                            <div class="card-body text-center">
                                                <i class="fa-solid fa-pause fa-3x mb-2"></i>
                                                <h5 class="card-title fw-bold">
                                                    BAŞLADI
                                                </h5>
                                            </div>
                                        </div>
                                        <?php 
                                            $sql = "SELECT * FROM uretim_islem_tarihler 
                                            WHERE planlama_id = :planlama_id ORDER BY id DESC LIMIT 1";  
                                            $sth = $conn->prepare($sql);
                                            $sth->bindParam('planlama_id', $planlama_id);  
                                            $sth->execute();
                                            $planlama_tarih = $sth->fetch(PDO::FETCH_ASSOC);


                                            $ilkTarih = new DateTime($planlama_tarih['baslatma_tarih']);
                                            $ikinciTarih = new DateTime(date('d-m-Y H:i:s'));
                                            $fark = $ikinciTarih->diff($ilkTarih);

                                        ?>
                                        <ol class="list-group">
                                            <li class="list-group-item list-group pt-1 pb-1"> <b>B. Tarihi: </b>
                                                <?php 
                                                    if (is_array($planlama_tarih)) {
                                                        echo date('d-m-Y H:i:s', strtotime($planlama_tarih['baslatma_tarih'])); 
                                                    } else {
                                                        echo "Başlama tarihi bulunamadı";
                                                    }
                                                ?>
                                            </li>
                                            <li class="list-group-item list-group pt-1 pb-1"><b>Süre : </b>
                                                <span id="sure-farki">
                                                    <?php echo $fark->format('%H:%I:%S'); ?>
                                                </span>
                                            </li>
                                        </ol>
                                    <?php }?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 d-flex align-items-stretch">
                            <div class="card flex-grow-1">
                                <div class="card-body" style="padding:6px">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th class="table-info table-info2">Sipariş No</th>
                                                <th class="table-info2">
                                                    <button type="button" class="btn btn-primary btn-sm fw-bold text-decoration-underline" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#siparis-detay-modal"
                                                        data-bs-placement="bottom"
                                                        data-bs-html="true"
                                                        data-bs-custom-class="custom-tooltip"
                                                        data-bs-title="<b><i class='fa-regular fa-rectangle-list'></i> Sipariş Detayları</b>"
                                                    >
                                                        <?php echo $is['siparis_no'];?>
                                                    </button>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th class="table-info table-info2">Sipariş Adet</th>
                                                <th class="table-info2">
                                                    <span
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        data-bs-html="true"
                                                        data-bs-title="<b class='fs-6'>İş Bitiminde Üretilmesi Gereken Toplam Adet</b>"
                                                        style="cursor:pointer"
                                                    >
                                                        <?php echo number_format(end($orijinal_adetler)); ?>
                                                    </span>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th class="table-info table-info2">İşin Adı</th>
                                                <td class="table-info2"><?php echo $is['isin_adi']; ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-info table-info2">Alt Ürün</th>
                                                <td class="table-info2"><?php echo $is['isim']; ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-info table-info2">Hedef Süre</th>
                                                <td class="table-info2">
                                                    <?php 
                                                        $sureler = json_decode($is['sureler']);
                                                        echo $sureler[$is['mevcut_asama']]; 
                                                    ?>
                                                    saat
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 d-flex align-items-stretch">
                            <div class="card flex-grow-1">
                                <div class="card-body" style="padding:6px">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <?php 
                                                $arsiv_altlar_idler = json_decode($is['arsiv_altlar'], true); 
                                                $mevcut_asama_arsiv_altlar_idler = array_filter($arsiv_altlar_idler[$is['mevcut_asama']]);
                                                $mevcut_asama_arsiv_altlar_idler_str = implode(',',$mevcut_asama_arsiv_altlar_idler);
                                                $arsiv_altlar = [];
                                                if(!empty($mevcut_asama_arsiv_altlar_idler_str)){
                                                    $sql = "SELECT arsiv_kalemler.arsiv,`arsiv_altlar`.`kod` FROM `arsiv_altlar` 
                                                    JOIN arsiv_kalemler ON arsiv_kalemler.id = arsiv_altlar.arsiv_id
                                                    WHERE arsiv_altlar.id IN({$mevcut_asama_arsiv_altlar_idler_str})";
                                                    $sth = $conn->prepare($sql);
                                                    $sth->execute();
                                                    $arsiv_altlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                }
                                                
                                                //print_r($arsiv_altlar);

                                                $detaylar = json_decode($is['detaylar'], true); 
                                            ?>
                                            <?php foreach ($arsiv_altlar as $index => $arsiv_alt) { ?>
                                                <tr>
                                                    <th class="table-info text-danger"><?php echo $index+1; ?>. ARŞİV</th>
                                                    <td><?php echo $arsiv_alt['arsiv'].' '.$arsiv_alt['kod']; ?></td>
                                                </tr>
                                            <?php }?>
                                            <tr class="table-danger">
                                                <th class="align-middle table-info2">DETAY</th>
                                                <th class="table-info2">
                                                    <?php echo $detaylar[$is['mevcut_asama']];?>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th class="table-info table-info2">İşlem Adet</th>
                                                <th class="table-info2">
                                                    <span 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        data-bs-html="true"
                                                        data-bs-title="<b class='fs-6'>Mevcut Aşamada Üretilmesi Gereken Toplam Adet</b>"
                                                        style="cursor:pointer"
                                                    >
                                                        <?php echo number_format($orijinal_adetler[$is['mevcut_asama']]); ?>
                                                    </span>
                                                    /
                                                    <span
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        data-bs-html="true"
                                                        data-bs-title="<b class='fs-6'>Mevcut Aşamaya Gelen Toplam Adet</b>"
                                                        style="cursor:pointer"
                                                    >
                                                        <?php echo number_format($uretim_aktarilan_adet);?>
                                                    </span>
                                                </th>
                                            </tr>
                                            <?php 
                                                $stok_alt_kalemler = json_decode($is['stok_alt_kalemler'], true); 
                                                $stok_alt_kalemler = isset($stok_alt_kalemler[$is['mevcut_asama']]) ? $stok_alt_kalemler[$is['mevcut_asama']] : [];
                                                
                                                $stok_alt_depo_adetler  = json_decode($is['stok_alt_depo_adetler']);
                                                $stok_alt_depo_adetler  = $stok_alt_depo_adetler[$is['mevcut_asama']];

                                                $stok_alt_depolar = json_decode($is['stok_alt_depolar'], true); 
                                                $stok_alt_depolar = isset($stok_alt_depolar[$is['mevcut_asama']]) ? $stok_alt_depolar[$is['mevcut_asama']] : [];
                                                

                                                $stok_kalem_sayici = 0;

                                            ?>
                                            <?php foreach ($stok_alt_kalemler as $key => $stok_kalem_id) { ?>
                                                <?php 
                                                    if($stok_kalem_id == 0 || $stok_alt_depo_adetler[$key] == 0 || $stok_alt_depolar[$key] == 0){ continue; }    
                                                ?>
                                                <?php 
                                                    $sql = "SELECT stok_alt_kalemler.veri, birimler.ad,
                                                    stok_kalemleri.stok_kalem 
                                                    FROM `stok_alt_kalemler` 
                                                    JOIN stok_kalemleri ON stok_kalemleri.id = `stok_alt_kalemler`.`stok_id`
                                                    LEFT JOIN birimler ON birimler.id = stok_alt_kalemler.birim_id  
                                                    WHERE stok_alt_kalemler.id = :id AND stok_alt_kalemler.firma_id = :firma_id";
                                                    
                                                    $sth = $conn->prepare($sql);
                                                    $sth->bindParam('id', $stok_kalem_id);
                                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                    $sth->execute();
                                                    $stok_kalemler_veri = $sth->fetch(PDO::FETCH_ASSOC);

                                                    $sql = "SELECT stok_kodu FROM `stok_alt_depolar`  WHERE id = :id";
                                                    $sth = $conn->prepare($sql);
                                                    $sth->bindParam('id', $stok_alt_depolar[$key]);
                                                    $sth->execute();
                                                    $stok_alt_depo = $sth->fetch(PDO::FETCH_ASSOC);
                                                ?>
                                                <tr>
                                                    <th class="table-info text-decoration-underline"> 
                                                        <?php echo ++$stok_kalem_sayici.'-'.$stok_kalemler_veri['stok_kalem']; ?>
                                                    </th> 
                                                    <td>
                                                        <div class="d-flex flex-wrap justify-content-between mb-1 border-bottom pb-2">
                                                            <span class="badge bg-primary">
                                                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                                                <?php 
                                                                    echo $stok_alt_depo_adetler[$key].' '.$stok_kalemler_veri['ad']; 
                                                                ?>
                                                            </span>
                                                            <span class="badge bg-secondary">
                                                                STOK KODU : <?php echo $stok_alt_depo['stok_kodu']; ?>
                                                            </span>
                                                        </div>
                                                        <?php $veriler = json_decode($stok_kalemler_veri['veri'], true); ?>
                                                        <?php foreach ($veriler as $etiket => $deger) { ?>
                                                            <span class="badge bg-secondary">
                                                                <?php echo $etiket;?> : 
                                                            </span>
                                                            <?php echo $deger; ?>  
                                                        <?php }?>
                                                    </td>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 d-flex align-items-stretch">
                            <div class="card flex-grow-1">
                                <div class="card-body pt-1 pb-1">
                                    <?php if(in_array($is['durum'], ['basladi'])){ ?>
                                        <a href="#" id="is-bitir">
                                            <div class="card border-primary mb-1">
                                                <div class="card-body text-center pt-2 pb-1">
                                                    <i class="fa-solid fa-circle-stop fa-3x mb-2"></i>
                                                    <h5 class="card-title text fw-bold">İŞİ BİTİR</h5>
                                                </div>
                                            </div>
                                        </a> 
                                    <?php }else { ?>
                                        <a href="javascript:;" class="disabled">
                                            <div class="card border-primary mb-1">
                                                <div class="card-body text-center pt-2 pb-1">
                                                    <i class="fa-solid fa-circle-stop fa-3x mb-2"></i>
                                                    <h5 class="card-title text fw-bold">İŞİ BİTİR</h5>
                                                </div>
                                            </div>
                                        </a>
                                    <?php } ?>

                                    <ol class="list-group mt-3" style="margin-top:4px!important">
                                        <li class="list-group-item table-info2"> 
                                            <b>Üretilen: </b>
                                            <span
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                data-bs-html="true"
                                                data-bs-title="<b class='fs-6'>Mevcut Aşamada Toplam Üretilen Adet</b>"
                                                style="cursor:pointer"
                                            >
                                                <?php echo number_format($toplam_uretilen_adet);?> 
                                            </span>
                                        </li>
                                        <li class="list-group-item table-info2">
                                            <b>Kalan: </b>
                                            <span
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                data-bs-html="true"
                                                data-bs-title="<b class='fs-6'>Mevcut Aşamada Kalan Adet</b>"
                                                style="cursor:pointer"
                                            >
                                                <?php 
                                                    echo number_format($orijinal_adetler[$is['mevcut_asama']]-$toplam_uretilen_adet); 
                                                ?> 
                                            </span>
                                            /
                                            <span
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                data-bs-html="true"
                                                data-bs-title="<b class='fs-6'>Gelen İşten Kalan Adet</b>"
                                                style="cursor:pointer"
                                            >
                                                <?php echo number_format($uretim_aktarilan_adet-$toplam_uretilen_adet); ?> 
                                            </span>
                                        </li>
                                        <li class="list-group-item table-info2">
                                            <?php 
                                                $sql = "SELECT SUM(aktarilan_adet) AS aktarilan_adet 
                                                        FROM `uretim_mevcut_asamada_aktarilan` 
                                                        WHERE  grup_kodu = :grup_kodu AND mevcut_asama = :mevcut_asama";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam("grup_kodu", $is['grup_kodu']);
                                                $sth->bindParam("mevcut_asama", $is['mevcut_asama']);
                                                $sth->execute();
                                                $aktarilan_adet = $sth->fetch(PDO::FETCH_ASSOC);
                                                //print_r($aktarilan_adet);
                                                $aktarilan_adet = empty($aktarilan_adet['aktarilan_adet']) ? 0 : $aktarilan_adet['aktarilan_adet'];
                                            ?>
                                            <b>Aktarılan Adet:</b>
                                            <?php echo number_format($aktarilan_adet);?>    
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="container">
            <div class="row mt-1 mb-1">
                <div class="col-md-12">
                    <div class="card border-secondary">
                        <div class="card-header border-secondary d-flex justify-content-between">
                            <div></div>
                            <div>
                                <?php 
                                    $sql = "SELECT `mevcut_asama`, SUM(`uretilen_adet`) as uretilen_adet, 
                                    SUM(`uretirken_verilen_fire_adet`) AS uretirken_verilen_fire_adet 
                                    FROM `uretilen_adetler` WHERE  `grup_kodu` = :grup_kodu GROUP BY `mevcut_asama`; ";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('grup_kodu', $is['grup_kodu']);
                                    $sth->execute();
                                    $uretilen_adetler_fireler = $sth->fetchAll(PDO::FETCH_ASSOC); 
                                    //print_r($uretilen_adetler_fireler);

                                    foreach ($departmanlar as $index => $departman_idx) {
                                        $sql = "SELECT departman FROM departmanlar 
                                        WHERE firma_id = :firma_id AND id  =:id";
                                        $sth = $conn->prepare($sql);
                                        $sth->bindParam('id', $departman_idx);
                                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                        $sth->execute();
                                        $departman = $sth->fetch(PDO::FETCH_ASSOC); 
                                    ?>
                                        <span class="badge mb-1 p-2 <?php echo $is['mevcut_asama'] == $index ? 'text-bg-success mevcut-asama':'text-bg-secondary'; ?>">
                                            <?php echo ($index+1).' - '. $departman['departman']; ?>
                                            <?php foreach ($uretilen_adetler_fireler as $uretilen_adet_fire) { ?>
                                                <?php if($uretilen_adet_fire['mevcut_asama'] == $index){ ?>
                                                    <span class="fw-bold fs-6">
                                                        (<?php echo number_format($uretilen_adet_fire['uretilen_adet']).'/'.number_format($uretilen_adet_fire['uretirken_verilen_fire_adet']);?>)
                                                    </span>
                                                    <?php break; ?>
                                                <?php }?>
                                            <?php }?>
                                            <?php if($fason_durumlar[$index] == 1){ ?>
                                                <span class="fw-bold fs-6">(FASON)</span>
                                            <?php } ?>
                                        </span>
                                        <?php if( intval($index) < count($departmanlar) -1 ){ ?>
                                            <i class="fa-solid fa-arrow-right-long fw-bold"></i>
                                        <?php } ?>
                                    <?php 
                                    }
                                ?>
                            </div>
                        </div>
                        <div class="card-body pt-0 pb-0">
                            <div class="row">
                                <!-- Mola -->
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                            WHERE firma_id = :firma_id AND departman_id = :departman_id 
                                            AND makina_is_button_id = 1";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $mola_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($mola_ayar) && $mola_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-1" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                            <a href="javascript:;" class="disabled">
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="mola-baslat">
                                        <?php } ?>
                                                <div class="card border-primary mb-0">
                                                    <div class="card-body text-center text-primary">
                                                        <i class="fa-solid fa-mug-saucer fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">MOLA</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                    </div>  
                                <?php } ?>

                                <!-- Yemek Mola -->
                                <?php   
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 2";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $yemek_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>

                                <?php if(!empty($yemek_ayar) && $yemek_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-1" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                            <a href="javascript:;"  class="disabled">
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="yemek-mola-baslat">
                                        <?php } ?>
                                                <div class="card border-primary">
                                                    <div class="card-body text-center">
                                                        <i class="fa-solid fa-utensils fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">YEMEK</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                    </div>  
                                <?php } ?>

                                <!-- Toplantı --> 
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 3";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $toplanti_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($toplanti_ayar) && $toplanti_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-1" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                            <a href="javascript:;"  class="disabled">
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="toplanti-baslat">
                                        <?php }?>
                                                <div class="card border-primary">
                                                    <div class="card-body text-center">
                                                        <i class="fa-solid fa-handshake fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">TOPLANTI</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                    </div>  
                                <?php } ?>

                                <!-- Paydos -->            
                                <?php
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 4";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $paydos_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($paydos_ayar) && $paydos_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-1" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                            <a href="javascript:;" class="text-primary disabled">
                                        <?php }else{ ?>
                                            <a href="javascript:;" class="text-primary" id="paydos-baslat">
                                        <?php } ?>
                                            <div class="card border-primary">
                                                <div class="card-body text-center">
                                                    <i class="fa-solid fa-right-from-bracket fa-3x mb-2"></i>
                                                    <h5 class="card-title fw-bold">PAYDOS</h5>
                                                </div>
                                            </div>
                                        </a> 
                                    </div>  
                                <?php } ?>

                                <!-- Devret -->
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 5";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $devret_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($devret_ayar) && $devret_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-1" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede']) || $departmandaki_makina_sayisi == 0){ ?>
                                            <a href="javascript:;" class="disabled <?php echo $departmandaki_makina_sayisi == 0 ? 'text-danger':'';?>"> 
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="devret-baslat">
                                        <?php } ?>
                                                <div class="card border-<?php echo $departmandaki_makina_sayisi == 0 ? 'danger':'primary';?> ''">
                                                    <div class="card-body text-center">
                                                        <i class="fa-solid fa-arrow-up-right-from-square fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">DEVRET</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                    </div>  
                                <?php } ?>

                                <!-- Kontrol -->
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 6";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $kontrol_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($kontrol_ayar) && $kontrol_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-1" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                            <a href="javascript:;" class="disabled" >
                                        <?php }else{ ?>
                                                <a href="javascript:;" id="kontrol">
                                        <?php } ?>
                                            <div class="card border-primary">
                                                <div class="card-body text-center">
                                                    <i class="fa-solid fa-list-check fa-3x mb-2"></i>
                                                    <h5 class="card-title fw-bold">KONTROL</h5>
                                                </div>
                                            </div>
                                        </a> 
                                    </div>  
                                <?php } ?>

                                <!-- Değiştir -->
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 7";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $degistir_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($degistir_ayar) && $degistir_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-3" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                            <a href="javascript:;" class="disabled" >
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="degistir-baslat">
                                        <?php } ?>
                                            <div class="card border-primary">
                                                <div class="card-body text-center">
                                                    <i class="fa-solid fa-retweet fa-3x mb-2"></i>
                                                    <h5 class="card-title fw-bold">DEĞİŞTİR</h5>
                                                </div>
                                            </div>
                                        </a> 
                                    </div>  
                                <?php } ?>

                                <!-- Kamera -->            
                                <div class="col-md-2 mb-1" style="height:131px">
                                    <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                        <a href="javascript:;" class="disabled" data-bs-toggle="modal" >
                                    <?php }else{ ?>
                                        <a href="javascript:;">
                                    <?php }?>
                                        <div class="card border-primary">
                                            <div class="card-body text-center">
                                                <i class="fa-solid fa-camera fa-2x mb-2"></i>
                                                <h5 class="card-title fw-bold">
                                                    <input type="file" class="inputfile form-control"/>
                                                </h5>
                                            </div>
                                        </div>
                                    </a> 
                                </div>  


                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 11";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $yetkili_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($yetkili_ayar) && $yetkili_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-3" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede']) || count($departmandan_yetkili_kisiler) == 0){ ?>
                                            <a href="javascript:;"  class="disabled text-danger">
                                                <div class="card border-danger">
                                                    <div class="card-body text-center">
                                                        <i class="fa-solid fa-user fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">YETKİLİ</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="yetkili-baslat">
                                                <div class="card border-primary">
                                                    <div class="card-body text-center">
                                                        <i class="fa-solid fa-user fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">YETKİLİ</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                        <?php }?>
                                            
                                    </div>  
                                <?php } ?>

                                <!-- Arıza -->
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 9";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $ariza_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($ariza_ayar) && $ariza_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-3" style="height:131px">
                                        <?php if(in_array($is['durum'], ['baslamadi','beklemede'])){ ?>
                                            <a href="javascript:;"  class="disabled">
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="ariza-baslat">
                                        <?php } ?>
                                            <div class="card border-primary">
                                                <div class="card-body text-center">
                                                    <i class="fa-solid fa-screwdriver-wrench fa-3x mb-2"></i>
                                                    <h5 class="card-title fw-bold">ARIZA</h5>
                                                </div>
                                            </div>
                                        </a> 
                                    </div>  
                                <?php } ?>

                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 12";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $bakim_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($bakim_ayar) && $bakim_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-3" style="height:131px">
                                        <?php if( in_array($is['durum'], ['baslamadi','beklemede']) || empty($bakim_personeller)){ ?>
                                            <a href="javascript:;" class="disabled text-danger" >
                                                <div class="card border-danger">
                                                    <div class="card-body text-center">
                                                        <i class="fa-solid fa-triangle-exclamation fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">BAKIM</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="bakim-baslat" >
                                                <div class="card border-primary">
                                                    <div class="card-body text-center">
                                                        <i class="fa-solid fa-triangle-exclamation fa-3x mb-2"></i>
                                                        <h5 class="card-title fw-bold">BAKIM</h5>
                                                    </div>
                                                </div>
                                            </a> 
                                        <?php } ?>
                                    </div>  
                                <?php } ?>

                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id AND makina_is_button_id = 5";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$departman_id);
                                    $sth->execute();
                                    $aktar_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($aktar_ayar) && $aktar_ayar['durum'] == 1){ ?>
                                    <div class="col-md-2 mb-3" style="height:131px">
                                        <?php if( in_array($is['durum'], ['baslamadi','beklemede']) || $is['mevcut_asama'] + 1 == $is['asama_sayisi']){ ?>
                                            <a href="javascript:;" class="disabled" >
                                        <?php }else{ ?>
                                            <a href="javascript:;" id="aktar-baslat" >
                                        <?php } ?>
                                            <div class="card border-primary">
                                                <div class="card-body text-center">
                                                    <i class="fa-solid fa-share-nodes fa-3x mb-2"></i>
                                                    <h5 class="card-title fw-bold">AKTAR</h5>
                                                </div>
                                            </div>
                                        </a> 
                                    </div>  
                                <?php } ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
 
        <div class="modal fade" id="etiketModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h4 class="modal-title" id="myCenterModalLabel">Etiket Yazdır</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body">
                            <div class="row mb-1">
                                <div class="col-md-6">
                                    <label for="labelWidth" class="form-label">Genişlik (cm):</label>
                                    <input type="number" id="labelWidth" class="form-control" value="<?php echo $etiket_genislik; ?>" step="0.1">
                                </div>
                                <div class="col-md-6">
                                    <label for="labelHeight" class="form-label">Yükseklik (cm):</label>
                                    <input type="number" id="labelHeight" class="form-control" value="<?php echo $etiket_yukseklik; ?>"" step="0.1">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label for="boxQuantity" class="form-label">Koli İçi Adet:</label>
                                    <input type="number" id="boxQuantity" class="form-control" value="0">
                                </div> 
                                <div class="col-md-6">
                                    <label for="prodDate" class="form-label">Üretim Tarihi:</label>
                                    <input type="date" id="prodDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div> 
                            </div>
                            <div id="label-content" class="label-container" style="width: 10cm; height: 9.7cm;">
                                <div class="label-header">
                                    <span> 
                                            <img class="object-fit-fill" 
                                                src="dosyalar/logo/<?php echo $etiketLogo;?>" 
                                                loading="lazy"  
                                                style="width:150px;height:50px"
                                            >  
                                    </span>
                                    <div class="qr-code" id="qrcode"></div> 
                                </div>
                                <table class="label-table">
                                    <tr>
                                        <th>Firma<br>Customer</th>
                                        <td><?php echo strtoupper($musteri_firma_unvani); ?></td>
                                    </tr>
                                    <tr>
                                        <th>İş Emri Num.<br>Order</th>
                                        <td><?php echo $is['siparis_no'];?></td>
                                    </tr>
                                    <tr>
                                        <th>Ürün Adı<br>Product Name</th>
                                        <td><?php echo $is['isin_adi']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Koli İçi Adet<br>In Box</th>
                                        <td id="boxQuantityValue">0</td>
                                    </tr>
                                    <tr>
                                        <th>Üretim Tarihi<br>Product. Date</th>
                                        <td id="prodDateValue"><?php echo date('d.m.Y'); ?></td>
                                    </tr>
                                </table>
                            </div> 
                            <!-- <div class="text-end">
                                <button type="submit" class="btn btn-success waves-effect waves-light" name="departman_ekle" id="departman-ekle-button">Kaydet</button>
                                <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                            </div> -->
                    </div>
                    <div class="modal-footer"> 
                        <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success waves-effect waves-light" id="etiketYazdir">Yazdır</button>
                    </div>
                </div>
            </div>
        </div>
                             
        <?php 
            include_once "makina_is_ekran_modal.php"; 
            include_once "include/uyari_session_oldur.php"; 
        ?>

        <script src="js/makina_is_ekran.js"></script>
        <script>
            $(document).ready(function(){
                 // QR kod oluşturma
                 <?php 
                    echo "const domainAdi = " . json_encode($domain_adi) . ";";
                ?>
                const qrCodeDiv = document.getElementById('qrcode');
                const qrContent = domainAdi
                new QRCode(qrCodeDiv, {
                    text: qrContent,
                    width: 50,
                    height: 50,
                    colorDark: "#000000",
                    colorLight: "#ffffff"
                });
 
                let geriSayim = 30000;
                setInterval(function(){
                    if(geriSayim < 1 ) window.location.reload();
                    $("#geri-sayim").text(--geriSayim);
                },1000);

                const planlama_id = <?php echo $planlama_id; ?>;
                const grup_kodu = "<?php echo $is['grup_kodu']; ?>";
                //Tüm Mesajlar Göründü Yap
                $("#mesajlar").click(() => {
                    $.get(`/index.php?url=makina_is_ekran_db_islem&islem=mesajlari-gordu&grup_kodu=${grup_kodu}`, function(data, status){
                        $("#mesajlar span").text(0);
                    });
                });

                $('.inputfile').on("change", function(){
                    var file_data = $(this).prop('files')[0];
                    var form_data = new FormData();
                    form_data.append('file', file_data);
                    form_data.append('planlama_id', planlama_id);
                    form_data.append('mevcut_asama', <?php echo $is['mevcut_asama']; ?>);
                    $.ajax({
                        url         : '/index.php?url=makina_is_ekran_db_islem&islem=urun-resim-ekle', 
                        dataType    : 'JSON', 
                        cache       : false,
                        contentType : false,
                        processData : false,
                        data        : form_data,
                        type        : 'POST',
                        success     : function (response) {
                            if(response.durum){
                                $.notify("Yükleme Başarılı.","success");
                            }else{
                                $.notify("Yükleme Başarısız","error");
                            }
                        },
                        error: function (response) {
                            $.notify("Yükleme Başarısız(Error)","error");
                        }
                    });
                });
            });
            
        </script>