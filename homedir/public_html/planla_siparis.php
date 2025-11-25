<?php 
    include "include/oturum_kontrol.php";

    $siparis_id = isset($_GET['siparis_id']) ? intval($_GET['siparis_id']) : 0;

    //planlanmış bir sipariş ise buraya giremesin
    $sql = "SELECT planlama_durum FROM `planlama` WHERE firma_id = :firma_id 
        AND siparis_id = :siparis_id ORDER BY id DESC";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('siparis_id', $siparis_id);
    $sth->execute();
    $siparis_planlama_varmi = $sth->fetch(PDO::FETCH_ASSOC);
    
    //echo "<pre>"; print_r($siparis_planlama_varmi); exit;
    if(isset($siparis_planlama_varmi['planlama_durum']) && in_array($siparis_planlama_varmi['planlama_durum'], ['evet','yarım_kalmıs']))
    {
        header("Location: /index.php?url=planla_siparis_duzenle&siparis_id={$siparis_id}");
        exit;
    }
    

    $sth = $conn->prepare('SELECT siparisler.*,
                            ulkeler.baslik AS ulke_adi,
                            sehirler.baslik AS sehir_adi, 
                            ilceler.baslik AS ilce_adi,
                            odeme_tipleri.odeme_sekli,
                            musteri.marka
                            FROM siparisler 
                            JOIN `musteri` ON `musteri`.id = siparisler.musteri_id
                            JOIN ulkeler ON ulkeler.id = siparisler.ulke_id
                            JOIN sehirler ON sehirler.id = siparisler.sehir_id
                            JOIN ilceler ON ilceler.id = siparisler.ilce_id
                            JOIN odeme_tipleri ON odeme_tipleri.id = siparisler.odeme_sekli_id
                            WHERE siparisler.id = :id AND siparisler.firma_id = :firma_id');
    $sth->bindParam('id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($siparis))
    {
        include "include/yetkisiz.php"; die();
    }

    $sql = "SELECT * FROM `siparis_dosyalar` WHERE siparis_id = :siparis_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id',$siparis_id);
    $sth->execute();
    $siparis_resimler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT * FROM turler');
    $sth->execute();
    $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
        <style>
            .sidebar-card {
                position: sticky;
                top: 20px;
                max-height: calc(100vh - 40px);
                overflow-y: auto;
            }
            .sidebar-card::-webkit-scrollbar {
                width: 6px;
            }
            .sidebar-card::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }
            .sidebar-card::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 10px;
            }
            .sidebar-card::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
            .info-badge {
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
                border-radius: 8px;
            }
            .alt-urun-card {
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .alt-urun-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateY(-2px);
            }
            .asama-card {
                transition: all 0.2s ease;
            }
            .asama-card:hover {
                background-color: #f8f9fa;
            }
            
            /* Aşama başlığı yüksekliğini düşür */
            .asama-card .card-header,
            .alt-urun-card .card-header {
                padding: 0.25rem 0.75rem !important;
                min-height: auto !important;
            }
            
            .asama-card .card-header h5,
            .asama-card .card-header h6,
            .alt-urun-card .card-header h5 {
                margin: 0 !important;
                font-size: 0.85rem !important;
                line-height: 1.3 !important;
            }
            
            .alt-asama .card-header {
                padding: 0.25rem 0.75rem !important;
            }
            
            .alt-asama .card-header h6,
            .alt-asama .card-header .h6 {
                margin: 0 !important;
                font-size: 0.85rem !important;
                line-height: 1.3 !important;
            }
            
            /* Alt ürün başlıkları için */
            .alt-urun .card-header {
                padding: 0.35rem 0.75rem !important;
            }
            
            .alt-urun .card-header h5,
            .alt-urun .card-header .h5 {
                margin: 0 !important;
                font-size: 0.9rem !important;
                line-height: 1.4 !important;
            }
            
            /* Aşama card body yüksekliğini ve aralıklarını düşür */
            .alt-asama .card-body,
            .alt-asama-veriler {
                padding: 0.5rem !important;
            }
            
            .alt-asama .row.mb-2 {
                margin-bottom: 0.5rem !important;
            }
            
            .alt-asama {
                margin-bottom: 0.75rem !important;
            }
            
            /* Planla ve Kaydet butonları yanyana */
            .planlama-onay-guncelle-button,
            .planlama-ekle-kaydet-button {
                display: inline-block !important;
                margin-right: 0.75rem !important;
                width: auto !important;
                flex: 0 0 auto !important;
            }
            
            .planlama-onay-guncelle-button:last-child,
            .planlama-ekle-kaydet-button:last-child {
                margin-right: 0 !important;
            }
            .compact-list-item {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            .section-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
            }
            .image-thumbnail-mini {
                height: 45px;
                min-height: 45px;
                width: 45px;
                transition: transform 0.2s ease;
            }
            .image-thumbnail-mini:hover {
                transform: scale(1.1);
            }
            
            /* Form elementleri için minimal ve eşit yükseklik */
            .form-select,
            .form-control {
                height: 38px !important;
                min-height: 38px !important;
                padding: 0.375rem 0.75rem !important;
                font-size: 0.875rem !important;
                line-height: 1.5 !important;
                display: flex !important;
                align-items: center !important;
            }
            
            /* Butonlar için - inline olmalı */
            .btn {
                height: 38px !important;
                min-height: 38px !important;
                padding: 0.375rem 0.75rem !important;
                font-size: 0.875rem !important;
                line-height: 1.5 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            /* Input group text (label'lar) */
            .input-group-text {
                height: 38px !important;
                min-height: 38px !important;
                padding: 0.375rem 0.75rem !important;
                font-size: 0.875rem !important;
                line-height: 1.5 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            /* Textarea için */
            textarea.form-control {
                height: auto !important;
                min-height: 38px !important;
                display: block !important;
            }
            
            /* Input group içindeki elementler */
            .input-group {
                align-items: stretch !important;
            }
            
            .input-group .form-select,
            .input-group .form-control {
                height: 38px !important;
                display: flex !important;
                align-items: center !important;
            }
            
            /* Select option'ları düzelt */
            .form-select option {
                padding: 0.5rem !important;
            }
            
            /* Select2 için */
            .select2-container--bootstrap-5 .select2-selection {
                height: 38px !important;
                min-height: 38px !important;
                display: flex !important;
                align-items: center !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                line-height: 1.5 !important;
                padding: 0.375rem 0.75rem !important;
                display: flex !important;
                align-items: center !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
                height: 38px !important;
                display: flex !important;
                align-items: center !important;
            }
        </style>
<div class="row mt-2">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-bag-shopping"></i>
                Siparişin Planlaması
            </h5>
            <div>
                Sipariş Kodu: <b class="text-danger fw-bold"><?php echo $siparis['siparis_no']; ?></b> -
                Firma Adı   : <b><?php echo $siparis['marka']?> </b> - 
                İşin Adı    : <b><?php echo $siparis['isin_adi']?></b> 
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="row">
                <div class="col-md-2">         
                    <?php 
                        $veriler = json_decode($siparis['veriler'],true);

                        $para_cinsi = '<i class="fa-solid fa-turkish-lira-sign"></i>';
                        if($siparis['para_cinsi'] == 'DOLAR')      $para_cinsi = '<i class="fa-solid fa-dollar-sign"></i>';
                        if($siparis['para_cinsi'] == 'EURO')       $para_cinsi = '<i class="fa-solid fa-euro-sign"></i>';
                        if($siparis['para_cinsi'] == 'POUND')      $para_cinsi = '<i class="fa-solid fa-sterling-sign"></i>';
                    ?>
                    <?php if($siparis['tip_id'] == TEK_URUN){?>
                        <?php $veri = $veriler; ?>
                        <ul class="list-group mb-2">
                            <li class="list-group-item list-group-item-success fw-bold" aria-current="true">1. Alt Ürün</li>
                            <li class="list-group-item"><b>KDV: </b> %<?php echo $veri['kdv']; ?></li>
                            <li class="list-group-item"><b>İsim:</b> <?php echo $veri['isim']; ?></li>

                            <?php 
                                foreach($turler as $tur): 
                                    if( $tur['id'] == $veri['tur'] || $tur['id'] == $siparis['tur_id'] ): ?>
                                <li class="list-group-item">
                                    <strong>Türü :</strong><?php echo $tur['tur']; ?>
                                </li>
                                <?php endif; endforeach;?>

                            <li class="list-group-item"><b>Miktar:</b> <?php echo number_format($veri['miktar'],0,'',','); ?></li>
                            <li class="list-group-item">
                                <b>Birim Fiyat:</b> 
                                <?php echo number_format($veri['birim_fiyat'],2,'.',','); ?>
                                <?php echo $para_cinsi; ?>
                            </li>
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
                                <b>Açıklama:</b> <?php echo str_replace(["\r\n", "\n", "\r"], '<br>',$veri['aciklama']); ?>
                            </li>
                            
                            <?php if(isset($veri['form'])){ ?>
                                <?php foreach ($veri['form'] as $key => $value) { ?>
                                    <?php if(!empty($value)){?>
                                        <li class="list-group-item list-group-item-info">
                                            <b><?php echo $key; ?>:</b> <?php echo $value; ?>
                                        </li>
                                    <?php }?>
                                <?php }?>
                            <?php } ?>
                        </ul>
                        <div class="border border-secondary-subtle rounded p-1 mb-2">
                            <?php foreach ($siparis_resimler as $siparis_dosya) { ?>
                                <?php 
                                    $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                ?>
                                <?php if($uzanti == 'pdf'){ ?>
                                    <a  href="javascript:;" class="text-decoration-none pdf-modal-goster" 
                                        data-href="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>">
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
                                <li class="list-group-item"><b>KDV: </b> %<?php echo $veri['kdv']; ?></li>
                                <li class="list-group-item"><b>İsim:</b> <?php echo $veri['isim']; ?></li>

                                <?php 
                                foreach($turler as $tur): 
                                    if( $tur['id'] == $veri['tur'] || $tur['id'] == $siparis['tur_id'] ): ?>
                                <li class="list-group-item">
                                    <strong>Türü :</strong><?php echo $tur['tur']; ?>
                                </li>
                                <?php endif; endforeach;?>

                                <li class="list-group-item"><b>Miktar:</b> <?php echo number_format($veri['miktar'],0,'',','); ?></li>
                                <li class="list-group-item">
                                    <b>Birim Fiyat:</b> <?php echo number_format($veri['birim_fiyat'],2,'.',','); ?>
                                    <?php echo $para_cinsi; ?>
                                </li>
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
                                    <b>Açıklama:</b> <?php echo str_replace(["\r\n", "\n", "\r"], '<br>',$veri['aciklama']); ?>
                                </li>
                                <?php if(isset($veri['form'])){ ?>
                                    <?php foreach ($veri['form'] as $key => $value) { ?>
                                        <?php if(!empty($value)){?>
                                            <li class="list-group-item list-group-item-info">
                                                <b><?php echo $key; ?>:</b> <?php echo $value; ?>
                                            </li>
                                        <?php } ?>
                                    <?php }?>
                                <?php } ?>
                            </ul>
                            <div class="border rounded mb-2 p-1">
                                <?php $resim_varmi = false; ?>
                                <?php foreach ($siparis_resimler as $siparis_dosya) { ?>
                                    <?php if($index == $siparis_dosya['alt_urun_index']){ ?>
                                        <?php $resim_varmi = true; ?>
                                        <?php 
                                            $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                        ?>
                                        <?php if($uzanti == 'pdf'){ ?>
                                            <a href="javascript:;" class="text-decoration-none pdf-modal-goster" 
                                                data-href="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" >
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
                            <strong>Adet :</strong><?php echo number_format($siparis['adet'], 0, '','.'); ?>
                        </li>
                        <li class="list-group-item list-group-item-primary">
                            <strong>T. Adresi :</strong><?php echo $siparis['teslimat_adresi']; ?>
                        </li>
                        <li class="list-group-item list-group-item-primary">
                            <strong>T. Ülkesi :</strong><?php echo $siparis['ulke_adi']; ?>
                        </li>
                        <li class="list-group-item list-group-item-primary">
                            <strong>T. Şehiri :</strong><?php echo $siparis['sehir_adi']; ?>
                        </li>
                        <li class="list-group-item list-group-item-primary">
                            <strong>T. İlçesi :</strong><?php echo $siparis['ilce_adi']; ?>
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
                            <strong>Fiyat :</strong>
                            <?php echo number_format($siparis['fiyat'], 2, ',','.'); ?> <?php echo $para_cinsi; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Ödeme Şekli :</strong><?php echo $siparis['odeme_sekli']; ?>
                        </li>
                    </ul>
                            
                </div>
                <div class="col-md-10">
                    <div class="card mb-2">
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
                            <form action="/index.php?url=planlama_db_islem" method="POST" id="planlama-form">
                                <?php 
                                    if($siparis['tip_id'] == TEK_URUN){
                                        $veriler = [$veriler];
                                    }
                                    $veriler = array_reverse($veriler);
                                ?>
                                <?php foreach ($veriler as $index => $veri) { ?>
                                    <?php $altUrunId = count($veriler)- intval($index); ?>
                                    <div class="card mb-2 alt-urun border border-3 border-success mb-3" id="alt-urun-<?php echo $altUrunId; ?>" data-alt-urun-id="<?php echo $altUrunId; ?>">
                                        <div class="card-header">
                                            <div class="row">
                                                <div class="col-md-10">
                                                    <div class="lead fw-bold text-danger alt-urun-sayisi-uyari">
                                                        <?php echo count($veriler)-intval($index); ?>. Alt Ürün Bilgileri
                                                    </div>
                                                </div>
                                                <div class="col-md-2 text-end"></div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <!-- İsim Satırı-->
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <div class="input-group fw-bold">
                                                        <button class="btn btn-outline-success departmanlari-getir" type="button"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="Alt Aşama Ekle"
                                                            data-alt-urun-id="<?php echo $altUrunId; ?>"
                                                        >
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                        <input type="text"  class="form-control isim fs-5"  name="alt_urun_<?php echo $altUrunId; ?>[isim]" 
                                                            value="<?php echo $veri['isim'];?>" placeholder="İsim.." required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="input-group border-3">
                                                        <span 
                                                            class="input-group-text fw-bold"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-html="true" 
                                                            data-bs-title="<b class='text-danger fs-6'>Üretilecek Adet</b>"
                                                        >
                                                            Ü. Adet
                                                        </span>
                                                        <input type="text"  class="form-control uretilecek-adet fs-5" min="1" 
                                                            name="alt_urun_<?php echo $altUrunId; ?>[uretilecek_adet]" 
                                                            value="<?php echo number_format($veri['miktar']); ?>" 
                                                            placeholder="Üretilecek Adet.." required
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alt-asamalar sortable-1">
                                            </div>
                                        </div>
                                    </div>
                                <?php }?>

                                <input type="hidden" name="alt_urun_sayisi" id="alt-urun-sayisi" value="<?php echo count($veriler); ?>"> 
                                <input type="hidden" name="siparis_id" value="<?php echo $siparis_id; ?>"> 
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <button type="submit" name="planlama_ekle_kaydet" value="ekle" class="btn btn-success planlama-ekle-kaydet-button">
                                            <i class="fa-solid fa-list-check"></i> PLANLA
                                        </button>       
                                        <button type="submit" name="planlama_ekle_kaydet" value="kaydet" class="btn btn-primary planlama-ekle-kaydet-button">
                                            <i class="fa-regular fa-floppy-disk"></i> KAYDET
                                        </button>       
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

<!--  PDF Modal -->
<div class="modal fade" id="arsiv-pdf-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">
                    <i class="fa-regular fa-file-pdf"></i> ARŞİV PDF
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="arsiv-pdf-modal-body">
                
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

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function(){

        //PDF Modalda Göster
        $(document).on('click', '.pdf-modal-goster',function(){
            const pdfURL = $(this).data('href');
            $("#arsiv-pdf-modal-body").html(`
                <div class="ratio ratio-16x9">
                    <iframe src="${pdfURL}"  allowfullscreen></iframe>
                </div>
            `);
            $("#arsiv-pdf-modal").modal('show');
        });
        
        let altUrunSayisi = 1;
        $(`.sortable-${altUrunSayisi}` ).sortable({
            stop: function(event, ui) {
                const altAsamalar = $(event.target).find('div.alt-asama:not(.ui-sortable-placeholder)');
                let altAsamaSayisi = altAsamalar.length;
                altAsamalar.each(function(index){
                    $(this).find('.alt-urunun-kacinci-asama-yazisi').text(altAsamaSayisi--)
                });
            }
        });

        $("#planlama-form").submit(function(){
            $(".planlama-ekle-kaydet-button").addClass('disabled');
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
            $(this).val() ? $(this).addClass('is-valid').removeClass('is-invalid') : $(this).addClass('is-invalid').removeClass('is-valid');
            const altUrunId     = $(this).closest('.alt-urun').data('alt-urun-id');
            const departmanId   = $(this).val();
            const fasonDurum    = $(this).closest('div.departman-kolon').next('.fason-kolon').find('.fason_durum').val();
            const element       = $(this).closest('div.alt-asama-veriler');
            const altAsamaId    = $(this).closest('div.alt-asama').find('.alt-urunun-kacinci-asama-yazisi').text().trim();
            $(element).find('.tedarikciler').html('');
            $(element).find('.makinalar').html('');

            const response = await fetch("/index.php?url=planlama_db_islem&islem=departmanin_birimini_getir&departman_id=" + departmanId);
            const departman_birim = await response.json();

            $(element).find('.adet-birim').text(departman_birim.birim.ad);
            

            fasonDurum == 1 ? 
                tedarikcileriGetir(element, altUrunId):
                makinalariGetir(element, altUrunId, departmanId);

            altArsivGetir(element, altUrunId, departmanId,altAsamaId);
            stokGetir(element, altUrunId, departmanId, altAsamaId);
            //$('.js-example-basic-single').select2();
        });

        //alt asama ekleme
        $(document).on('click', '.departmanlari-getir', function(e){
            e.preventDefault(); // Form submit'i engelle
            const altUrunId = $(this).data('alt-urun-id');
            console.log('Alt Aşama Ekle butonuna tıklandı. Alt Ürün ID:', altUrunId);
            
            if(!altUrunId) {
                console.error('Alt Ürün ID bulunamadı!');
                alert('Hata: Alt Ürün ID bulunamadı!');
                return;
            }
            
            departmanGetir(altUrunId);
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
            
                $(element).find('input.isim').attr('name', `alt_urun_${altUrunSayisi-index}[isim]`);
                $(element).find('input.uretilecek-adet').attr('name', `alt_urun_${altUrunSayisi-index}[uretilecek_adet]`);
                $(element).find('select.departman').attr('name', `alt_urun_${altUrunSayisi-index}[departman]`);
                $(element).find('select.fason_durum').attr('name', `alt_urun_${altUrunSayisi-index}[fason_durum]`);
                $(element).find('input.adet').attr('name', `alt_urun_${altUrunSayisi-index}[adet]`);
                $(element).find('input.sure').attr('name', `alt_urun_${altUrunSayisi-index}[sure]`);
                $(element).find('textarea.detay').attr('name', `alt_urun_${altUrunSayisi-index}[detay]`);
                $(element).find('select.makina').attr('name', `alt_urun_${altUrunSayisi-index}[makina]`);
                $(element).find('select.fason_tedarikci').attr('name', `alt_urun_${altUrunSayisi-index}[fason_tedarikci]`);
                $(element).find('select.alt_arsiv').attr('name', $(element)?.find('select.alt_arsiv')?.val()?.replace(/\d+/, altUrunSayisi-index));
                $(element).find('input.stok-kalem').attr('name', $(element)?.find('input.stok-kalem')?.val()?.replace(/\d+/, altUrunSayisi-index));
                $(element).find('select.stok-alt-kalem').attr('name', $(element)?.find('select.stok-alt-kalem')?.val()?.replace(/\d+/, altUrunSayisi-index));
                $(element).find('select.stok-alt-depo').attr('name', $(element)?.find('select.stok-alt-depo')?.val()?.replace(/\d+/, altUrunSayisi-index));
                $(element).find('input.stok-alt-depo').attr('name', $(element)?.find('input.stok-alt-depo')?.val()?.replace(/\d+/, altUrunSayisi-index));
                $(element).find('input.stok-alt-kalem-adet').attr('name', $(element)?.find('input.stok-alt-kalem-adet')?.val()?.replace(/\d+/, altUrunSayisi-index));
                
            });
            $("#alt-urun-sayisi").val(altUrunSayisi);
        });


        //stok alt kalem değiştirken stok alt depoları getir
        $(document).on('change', '.stok-alt-kalem', function(){
            log(new Date())
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
                    console.log(veriler);
                    element.find('.stok-alt-kalem-birim-yazisi').text(veriler.birim?.ad ?? '-');
                    let stokAltDepolarHTML = '<option selected value="0">Seçiniz</option>';
                    let stok_alt_depo_sayici = 0;
                    veriler?.stok_alt_depolar?.forEach((stok_alt_depo, index)=>{
                        if(stok_alt_depo.kalan_adet > 0){
                            stokAltDepolarHTML += `<option value="${stok_alt_depo.id}">
                                ${++stok_alt_depo_sayici} - 
                                ${stok_alt_depo?.firma_adi} - 
                                Kalan Miktar:${stok_alt_depo?.kalan_adet}  ${veriler.birim.ad} - 
                                ${stok_alt_depo.stok_kodu}  - 
                                ${stok_alt_depo?.siparis_no ? stok_alt_depo?.siparis_no + ' (Siparişe Özel)' : '(Genel Kullanım)'} - 
                                ${stok_alt_depo?.fatura_no} 
                            </option>`;
                        }
                        
                    });
                    //console.log(stokAltDepolarHTML)
                    element.find('.stok-alt-depolar').html(`
                        <select class="js-example-basic-single form-select stok-alt-depo" 
                            name="alt_urun_${altUrunId}[stok_alt_depo][${altAsamaId}][]"
                            >
                            ${stokAltDepolarHTML}
                            <option value="-1" class="fw-bold">${++stok_alt_depo_sayici} - Depoda Yoktur Stok Geldiğinde Otomatik Seçecek</option>
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
                                ${Object.values(JSON.parse(stok_alt_kalem.veri)).join(' ')}
                                Stok: ${stok_alt_kalem.toplam_stok}
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
                                <input type="text" class="form-control readonly stok-input fw-bold" disabled value="1-${veriler.stok_kalem.stok_kalem}">
                                <input type="hidden" class="stok-kalem" name="alt_urun_${altUrunId}[stok_kalem][${altAsamaId}][]" value="${stokId}">
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
                            <input type="hidden" class="stok-alt-depo" name="alt_urun_${altUrunId}[stok_alt_depo][${altAsamaId}][]" value="0">
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

        if(stoklar.stoklar.length > 0){
            stoklar?.stoklar.forEach((stok, index) => {
                stokAltKalemlerHTML = "<option selected value='0'>Seçiniz</option>";
                stoklar?.stok_alt_kalemler?.[index].forEach((stok_alt_kalem, index)=>{
                    stokAltKalemlerHTML += `
                    <option value="${stok_alt_kalem.id}">
                        ${index + 1} -
                        ${Object.values(JSON.parse(stok_alt_kalem.veri)).join(' ')}
                        Stok: ${stok_alt_kalem.toplam_stok}
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
                            <input type="text" class="form-control readonly stok-input fw-bold" disabled value="${index +1}-${stok.stok_kalem}">
                            <input type="hidden" class="stok-kalem" name="alt_urun_${altUrunId}[stok_kalem][${altAsamaId}][]" value="${stok.id}">
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
                        <input type="hidden" class="stok-alt-depo" name="alt_urun_${altUrunId}[stok_alt_depo][${altAsamaId}][]" value="0">
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
        }else{
            stokHTML = `
                <input type="hidden" name="alt_urun_${altUrunId}[stok_alt_kalem][${altAsamaId}][]" value="0">
                <input type="hidden" name="alt_urun_${altUrunId}[stok_alt_depo][${altAsamaId}][]" value="0">
                <input type="hidden" name="alt_urun_${altUrunId}[stok_alt_depo_adet][${altAsamaId}][]" value="0">
                <input type="hidden" name="alt_urun_${altUrunId}[stok_kalem][${altAsamaId}][]" value="0">
            `;
        }
        

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
        let uzanti;

        altArsivResimler?.alt_arsiv_resimler.forEach((resim, index)=>{
            uzanti = resim.ad.endsWith('.pdf');
            if(uzanti){
                resimlerHTML += `
                    <a  href="javascript:;" class="text-decoration-none pdf-modal-goster text-decoration-none" 
                        data-href="dosyalar/arsivler/${resim.ad}">
                        <img src="dosyalar/pdf.png" 
                            class="rounded img-thumbnai object-fit-fill"
                            style="height:35px; min-height:35px; width:35px;border:1px solid #dee2e6"
                            
                        > 
                    </a>
                `;
            }else{
                resimlerHTML += `
                    <a class="example-image-link-${now} text-decoration-none" 
                        href="dosyalar/arsivler/${resim.ad}" data-lightbox="example-set-${now}" data-title="">
                            <img src="dosyalar/arsivler/${resim.ad}" 
                                class="rounded img-thumbnai object-fit-fill" 
                                style="height:35px; min-height:35px; width:35px;border:1px solid #dee2e6">
                    </a>
                `;
            }
        });
        resimlerElement.html(resimlerHTML);
        
    }
    
    function departmanGetir(altUrunId){
        console.log("departmanGetir çağrıldı. Alt Ürün ID =>" , altUrunId);
        
        $.ajax({
            url         : "/index.php?url=ajax_islemler&islem=departman-getir",
            dataType    : "JSON",
            beforeSend  : function() {
                console.log("AJAX isteği gönderiliyor...");
            },
            success     : function(departmanlar){ 
                console.log("AJAX başarılı! Departmanlar:", departmanlar);
                
                if(!departmanlar || departmanlar.length === 0) {
                    console.error("Departman bulunamadı!");
                    alert("Hata: Departman listesi boş!");
                    return;
                }
                const simdikiAltAsamaSayisi = $(`#alt-urun-${altUrunId} .alt-asamalar .alt-asama`).length + 1;

                let departmanlarHTML = `<option selected disabled value="">Seçiniz</option>`;
                for(const departman of departmanlar)
                {
                    departmanlarHTML += `<option class="fw-bold" value="${departman['id']}">${departman['departman']}</option>`;
                }

                let yeniAltAsamaHTML =`
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
                                        <span class="input-group-text fw-bold">Departman</span>
                                        <select 
                                            name="alt_urun_${altUrunId}[departman][]"
                                            class="form-select departman-degistir departman fw-bold text-success"
                                            required>
                                            ${departmanlarHTML}
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 fason-kolon"> 
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text fw-bold">Fason</span>
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
                                        <span class="input-group-text adet-birim fw-bold" >
                                            -
                                        </span>
                                        <input type="number"  class="form-control form-control-sm adet" 
                                            name="alt_urun_${altUrunId}[adet][]" min="0" required >
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">
                                            Süre (saat)
                                        </span>
                                        <input type="number" step="0.01" class="form-control form-control-sm sure" 
                                            name="alt_urun_${altUrunId}[sure][]" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">
                                            Detay
                                        </span>
                                        <textarea class="form-control form-control-sm detay"  style="height:100px"
                                            name="alt_urun_${altUrunId}[detay][]"></textarea>
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
                
                console.log("Yeni alt aşama HTML'i ekleniyor...");
                const altAsamalarElement = $(`#alt-urun-${altUrunId} .alt-asamalar`);
                
                if(altAsamalarElement.length === 0) {
                    console.error(`#alt-urun-${altUrunId} .alt-asamalar elementi bulunamadı!`);
                    alert(`Hata: Alt ürün #${altUrunId} bulunamadı!`);
                    return;
                }
                
                altAsamalarElement.prepend(yeniAltAsamaHTML);
                console.log("Alt aşama başarıyla eklendi!");
            },
            error: function(xhr, status, error) {
                console.error("AJAX Hatası!");
                console.error("Status:", status);
                console.error("Error:", error);
                console.error("Response:", xhr.responseText);
                alert("Hata: Departmanlar yüklenirken bir sorun oluştu!\n" + error);
            }
        });
    }

    async function altArsivGetir(element, altUrunId, departmanId, altAsamaId){
        $(element).find('.arsivler').html('');
        const response = await fetch("/index.php?url=planlama_db_islem&islem=siparis_alt_arsiv&departman_id=" + departmanId + "&siparis_id="+ <?php echo $siparis_id;?>);
        let arsivler = await response.json();

        //console.log("***");
        //console.log(arsivler.arsivler); 
        
        let arsivHTML = '';
        let arsivAltlarHTML = '';
        
        arsivler.arsivler.forEach((arsiv, index) => {
            arsivAltlarHTML = '<option selected value="0">Seçiniz..</option>';
            arsiv?.alt_arsivler?.forEach((alt_arsiv, alt_arsiv_index) => {
                arsivAltlarHTML += `<option value="${alt_arsiv.id}">
                    ${alt_arsiv_index+1} - ${alt_arsiv.kod} / ${alt_arsiv.ebat} / ${alt_arsiv.detay}
                </option>`
            })
            arsivHTML += `
                <div class="row mb-2 alt-arsiv">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <strong class="text-danger">${index + 1}. ARŞİV - ${arsiv.arsiv}</strong> 
                            </span> 
                            <select class="form-select alt_arsiv js-example-basic-single"
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
            tedarikciHTML += `<option value="${tedarikci.id}">${tedarikci.firma_adi} ${tedarikci.tedarikci_unvani}</option>`;
        });

        $(element).find('.tedarikciler').html(`
            <div class="col-md-2">
                <span class="input-group-text fw-bold">
                    <i class="fa-brands fa-supple me-2"></i> FASON
                </span> 
            </div>
            <div class="col-md-4">
                <select class="form-select js-example-basic-single fason_tedarikci" 
                    name="alt_urun_${altUrunId}[fason_tedarikci][]" required 
                >
                    <option value="">Seçiniz</option>
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
        let otomatikSecilecekMakina = '';
        
        // Aktif makina sayısını hesapla
        const aktifMakinalar = makinalar?.makinalar.filter(makina => makina.durumu === 'aktif') || [];
        const toplamMakina = makinalar?.makinalar.length || 0;
        
        // Seçim mantığı:
        // 1. Sadece 1 makina varsa (aktif/pasif fark etmez) → Otomatik seç
        // 2. Birden fazla makina varsa ve sadece 1 aktif varsa → Aktifi otomatik seç
        // 3. Birden fazla aktif makina varsa → "Seçiniz" göster
        
        if (toplamMakina === 1) {
            // Sadece 1 makina var, otomatik seç
            otomatikSecilecekMakina = makinalar.makinalar[0].id;
        } else if (toplamMakina > 1 && aktifMakinalar.length === 1) {
            // Birden fazla makina var ama sadece 1 aktif, onu seç
            otomatikSecilecekMakina = aktifMakinalar[0].id;
        }
        // Aksi durumda otomatikSecilecekMakina boş kalır, "Seçiniz" gösterilir
        
        makinalar?.makinalar.forEach((makina, index) =>{
            const selected = (makina.id == otomatikSecilecekMakina) ? 'selected' : '';
            
            makinaHTML += `<option value="${makina.id}" ${selected}>
                ${index+1}. ${makina.makina_adi} ${makina.makina_modeli}
            </option>`;
        });

        $(element).find('.makinalar').html(`
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text fw-bold">
                        Makina
                    </span> 
                    <select class="form-select makina ${makinalar?.makinalar.length == 0 ? 'is-invalid':''}" name="alt_urun_${altUrunId}[makina][]" required>
                        ${otomatikSecilecekMakina ? '' : '<option selected value="">Seçiniz..</option>'}
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