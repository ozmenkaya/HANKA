<?php   
    require_once "include/oturum_kontrol.php";

    // Değişkenleri başlangıçta boş dizi olarak tanımla
    $onaylanmamis_siparisler = [];
    $onaylanmis_siparisler = [];
    $bitmis_siparisler = [];

    try{ 
            $sth = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, siparisler.termin, 
                                siparisler.fiyat, siparisler.adet, siparisler.para_cinsi, siparisler.islem as sip_durum, siparisler.islem,
                                siparisler.aktif,
                                musteri.marka, CONCAT_WS(" ", personeller.ad, personeller.soyad) AS personel_ad_soyad
                                FROM siparisler 
                                JOIN musteri ON siparisler.musteri_id = musteri.id
                                JOIN personeller ON personeller.id  = siparisler.musteri_temsilcisi_id
                                WHERE siparisler.firma_id = :firma_id AND  onay_baslangic_durum = "hayır" AND siparisler.islem != "iptal"
                                ORDER BY siparisler.id DESC');
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $onaylanmamis_siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);

            $sth = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, siparisler.termin, 
                            siparisler.fiyat, siparisler.adet, siparisler.para_cinsi, siparisler.islem as sip_durum, siparisler.islem,
                            siparisler.aktif,
                            musteri.marka, CONCAT_WS(" ", personeller.ad, personeller.soyad) AS personel_ad_soyad
                            FROM siparisler 
                            JOIN musteri ON siparisler.musteri_id = musteri.id
                            JOIN personeller ON personeller.id  = siparisler.musteri_temsilcisi_id
                            WHERE siparisler.firma_id = :firma_id AND onay_baslangic_durum = "evet" AND siparisler.islem != "iptal" ORDER BY siparisler.id DESC');
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $onaylanmis_siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);



            $sth = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, siparisler.termin, 
                            siparisler.fiyat, siparisler.adet, siparisler.para_cinsi, siparisler.islem as sip_durum, siparisler.islem,
                            siparisler.aktif,
                            musteri.marka, CONCAT_WS(" ", personeller.ad, personeller.soyad) AS personel_ad_soyad
                            FROM siparisler 
                            JOIN musteri ON siparisler.musteri_id = musteri.id
                            JOIN personeller ON personeller.id  = siparisler.musteri_temsilcisi_id
                            WHERE siparisler.firma_id = :firma_id AND onay_baslangic_durum = "evet" AND islem = "tamamlandi" ORDER BY siparisler.id DESC');
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $bitmis_siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) { 
        error_log("Siparisler_onay.php hatası: " . $e->getMessage());
        echo "Veritabanı sorgusu sırasında bir hata oluştu. Lütfen sistem yöneticisi ile iletişime geçin.";
    }
?> 
<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-bag-shopping"></i> Siparişler
            </h5>
            <div>
                <div class="d-flex justify-content-end"> 
                    <div class="btn-group" role="group" aria-label="Basic example">
                        <a href="javascript:window.history.back();" 
                            class="btn btn-secondary"
                            data-bs-target="#departman-ekle-modal"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom" 
                            data-bs-title="Geri Dön"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <a href="/index.php?url=siparis_db_islem&islem=siparis_excel" 
                            class="btn btn-success"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom" 
                            data-bs-title="Excel"
                        >
                            <i class="fa-regular fa-file-excel"></i>
                        </a>
                    </div>
                </div>
            </div>  
        </div>
        <div class="card-body pt-0">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <?php if(in_array(YENI_SIPARIS_GOR, $_SESSION['sayfa_idler'])){  ?>
                        <button class="nav-link active position-relative fw-bold" id="nav-tab-onaylanmayan" data-bs-toggle="tab" 
                            data-bs-target="#nav-onaylanmayan" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                            Yeni Siparişler
                            <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-danger">
                                <?php echo count($onaylanmamis_siparisler); ?>
                                <span class="visually-hidden">Yeni Siparişler</span>
                            </span>
                        </button>
                    <?php }?>
                    <button class="nav-link position-relative fw-bold" id="nav-tab-onaylanan" data-bs-toggle="tab" 
                        data-bs-target="#nav-onaylanan" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                        Siparişler
                        <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-primary">
                                <?php echo count($onaylanmis_siparisler ); ?>
                                <span class="visually-hidden">Onaylanmış Siparişler</span>
                            </span>
                    </button>
                    <button class="nav-link position-relative fw-bold" id="nav-tab-biten" data-bs-toggle="tab" 
                        data-bs-target="#nav-biten" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                        Biten Siparişler
                        <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-success">
                                <?php echo count($bitmis_siparisler ); ?>
                                <span class="visually-hidden">Biten Siparişler</span>
                            </span>
                    </button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <?php if(in_array(YENI_SIPARIS_GOR, $_SESSION['sayfa_idler'])){  ?>
                    <div class="tab-pane fade show active" id="nav-onaylanmayan" role="tabpanel" 
                        aria-labelledby="nav-tab-onaylanmayan" tabindex="0">
                        <div class="table-responsive">
                            <table id="onaylanmayanTable" class="table table-hover table-sm" >
                                <thead class="table-primary">
                                    <tr>
                                        <th class="text-align align-middle text-center">#</th>
                                        <th>Sipariş No</th>
                                        <th>İşin Adı</th>
                                        <th>Müşteri</th>
                                        <th>M.Temsilcisi</th>
                                        <th>Termin</th>
                                        <th class="text-end">Adet</th>
                                        <th class="text-end">Fiyat</th>
                                        <th class="text-center">Sil.Drm.</th>
                                        <th class="text-center">Durum</th>
                                        <th class="text-center">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($onaylanmamis_siparisler  as $key=>$onaylanmamis_siparis) { ?>
                                        <tr>
                                            <td class="table-primary text-center align-middle table_sm_pd"><?php echo $key +1 ;?></td>
                                            <th class="table-secondary align-middle table_sm_pd"><?php echo $onaylanmamis_siparis['siparis_no'];?></th>
                                            <td class="align-middle table_sm_pd"><?php echo $onaylanmamis_siparis['isin_adi']; ?></td>
                                            <td class="align-middle table_sm_pd"><?php echo $onaylanmamis_siparis['marka']; ?></td>
                                            <td class="align-middle table_sm_pd"><?php echo $onaylanmamis_siparis['personel_ad_soyad']; ?></td>
                                            <td class="align-middle table_sm_pd"><?php echo date('d.m.Y', strtotime($onaylanmamis_siparis['termin'])); ?></td>
                                            <td class="text-end align-middle table_sm_pd">
                                                <?php echo number_format($onaylanmamis_siparis['adet'],0,'','.'); ?> Adet
                                            </td>
                                            <td class="text-end align-middle table_sm_pd">
                                                <?php 
                                                    $para_cinsi = '<i class="fa-solid fa-turkish-lira-sign"></i>';
                                                    if($onaylanmamis_siparis['para_cinsi'] == 'DOLAR')      $para_cinsi = '<i class="fa-solid fa-dollar-sign"></i>';
                                                    if($onaylanmamis_siparis['para_cinsi'] == 'EURO')       $para_cinsi = '<i class="fa-solid fa-euro-sign"></i>';
                                                    if($onaylanmamis_siparis['para_cinsi'] == 'POUND')      $para_cinsi = '<i class="fa-solid fa-sterling-sign"></i>';
                                                ?>
                                                <?php echo number_format($onaylanmamis_siparis['fiyat'], 2, ',', '.').' '.$para_cinsi; ?> 
                                            </td>
                                            <td class="text-center align-middle table_sm_pd">
                                                <?php if($onaylanmamis_siparis['islem'] === 'iptal'){ ?>
                                                <span class="badge rounded-pill bg-danger" style="
                                                    padding-top: 5px;
                                                    padding-bottom: 4px;
                                                    padding-right: 5px;
                                                    padding-left: 5px;
                                                "><?php echo $onaylanmamis_siparis['islem'] ?>
                                                <?php } ?>
                                            </td>
                                            <td class="text-center align-middle table_sm_pd">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input siparis-durum-switch" 
                                                           type="checkbox" 
                                                           role="switch" 
                                                           data-siparis-id="<?php echo $onaylanmamis_siparis['id']; ?>"
                                                           <?php echo isset($onaylanmamis_siparis['aktif']) && $onaylanmamis_siparis['aktif'] == 1 ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle table_sm_pd">
                                                <div class="btn-group custom-dropdown">
                                                    <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="mdi mdi-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <?php if(in_array(YENI_SIPARIS_ONAY, $_SESSION['sayfa_idler'])){  ?>
                                                            <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparisler_admin_kontrol&siparis_id=<?php echo $onaylanmamis_siparis['id']; ?>">Güncelle</a>
                                                            <div class="dropdown-divider"></div>
                                                        <?php } ?>
                                                        <?php if(in_array(SIPARIS_SIL, $_SESSION['sayfa_idler'])){  ?>
                                                            <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparisler_admin_db_islem&islem=iptal&siparis_id=<?php echo $onaylanmamis_siparis['id']; ?>">Sil</a>
                                                            <div class="dropdown-divider" style="margin-top:2px"></div>
                                                        <?php } ?>
                                                    </div>
                                                </div> 
                                            </td> 
                                            <!-- <td >
                                            <div class="d-flex justify-content-end"> 
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <?php if(in_array(YENI_SIPARIS_ONAY, $_SESSION['sayfa_idler'])){  ?>
                                                        <a href="/index.php?url=siparisler_admin_kontrol&siparis_id=<?php echo $onaylanmamis_siparis['id']; ?>" 
                                                            class="btn btn-warning" 
                                                            name="siparis_guncelle"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="Kontrol"
                                                        >
                                                            <i class="fa-regular fa-circle-check"></i>
                                                        </a>
                                                    <?php }?>
                                                    <?php if(in_array(SIPARIS_SIL, $_SESSION['sayfa_idler'])){  ?>
                                                        <a href="/index.php?url=siparisler_admin_db_islem&islem=iptal&siparis_id=<?php echo $onaylanmamis_siparis['id']; ?>" 
                                                            class="btn  btn-danger" 
                                                            onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="İptal"
                                                        >
                                                            <i class="fa-solid fa-ban"></i>
                                                        </a>
                                                </div>
                                            </div>
                                                <?php }?>
                                            </td> -->
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php }?>

                <div class="tab-pane fade" id="nav-onaylanan" role="tabpanel" 
                    aria-labelledby="nav-tab-onaylanan" tabindex="1">
                    <div class="table-responsive">
                        <table id="onaylananTable" class="table table-hover table-sm">
                            <thead class="table-primary">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Sipariş No</th>
                                    <th>Müşteri</th>
                                    <th>İşin Adı</th>
                                    <th>M.Temsilcisi</th>
                                    <th class="text-center">Termin</th>
                                    <th class="text-end">Adet</th>
                                    <th class="text-end">Fiyat</th>
                                    <th class="text-center">Sil.Drm.</th>
                                    <th class="text-center">Durum</th>
                                    <th class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($onaylanmis_siparisler  as $index=>$onaylanmis_siparis) { ?>
                                    <tr>
                                        <th class="table-primary text-center align-middle table_sm_pd"><?php echo $index+1;?></th>
                                        <td class="table-secondary align-middle table_sm_pd"><?php echo $onaylanmis_siparis['siparis_no'];?></td>
                                        <td class="align-middle table_sm_pd"><?php echo $onaylanmis_siparis['marka']; ?></td>
                                        <td class="align-middle table_sm_pd"><?php echo $onaylanmis_siparis['isin_adi']; ?></td>
                                        <td class="align-middle table_sm_pd"><?php echo $onaylanmis_siparis['personel_ad_soyad']; ?></td>
                                        <td class="align-middle text-center table_sm_pd"><?php echo date('d.m.Y',strtotime($onaylanmis_siparis['termin'])); ?></td>
                                        <td class="text-end align-middle table_sm_pd">
                                            <?php echo number_format($onaylanmis_siparis['adet'],0,'','.'); ?> Adet
                                        </td>
                                        <td class="text-end align-middle table_sm_pd">
                                            <?php 
                                                $para_cinsi = '<i class="fa-solid fa-turkish-lira-sign"></i>';
                                                if($onaylanmis_siparis['para_cinsi'] == 'DOLAR')      $para_cinsi = '<i class="fa-solid fa-dollar-sign"></i>';
                                                if($onaylanmis_siparis['para_cinsi'] == 'EURO')       $para_cinsi = '<i class="fa-solid fa-euro-sign"></i>';
                                                if($onaylanmis_siparis['para_cinsi'] == 'POUND')      $para_cinsi = '<i class="fa-solid fa-sterling-sign"></i>';
                                            ?>
                                            <?php echo number_format($onaylanmis_siparis['fiyat'], 2, ',', '.').' '.$para_cinsi; ?>
                                        </td>
                                        <td class="text-center align-middle table_sm_pd">
                                            <?php if($onaylanmis_siparis['islem'] === 'iptal'){ ?>
                                            <span class="badge rounded-pill bg-danger" style="
                                                padding-top: 5px;
                                                padding-bottom: 4px;
                                                padding-right: 5px;
                                                padding-left: 5px;
                                            "><?php echo $onaylanmis_siparis['islem'] ?>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center align-middle table_sm_pd">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input siparis-durum-switch" 
                                                       type="checkbox" 
                                                       role="switch" 
                                                       data-siparis-id="<?php echo $onaylanmis_siparis['id']; ?>"
                                                       <?php echo (isset($onaylanmis_siparis['aktif']) && $onaylanmis_siparis['aktif'] == 1) ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle table_sm_pd">
                                            <div class="btn-group custom-dropdown">
                                                <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="mdi mdi-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <?php if(in_array(TUM_SIPARISLERI_DUZENLE, $_SESSION['sayfa_idler'])){  ?>
                                                        <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparisler_admin_kontrol&siparis_id=<?php echo $onaylanmis_siparis['id']; ?>">Güncelle</a>
                                                        <div class="dropdown-divider"></div>
                                                    <?php } ?>
                                                    <?php if(in_array(SIPARIS_SIL, $_SESSION['sayfa_idler'])){  ?>
                                                        <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparisler_admin_db_islem&islem=iptal&siparis_id=<?php echo $onaylanmis_siparis['id']; ?>">Sil</a>
                                                        <div class="dropdown-divider" style="margin-top:2px"></div>
                                                    <?php } ?>
                                                </div>
                                            </div> 
                                        </td> 
                                        <!-- <td>
                                            <div class="d-md-flex justify-content-end"> 
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <?php if(in_array(TUM_SIPARISLERI_DUZENLE, $_SESSION['sayfa_idler'])){  ?>
                                                        <a href="/index.php?url=siparisler_admin_kontrol&siparis_id=<?php echo $onaylanmis_siparis['id']; ?>" 
                                                            class="btn  btn-warning" name="siparis_guncelle"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="Güncelle"    
                                                        >
                                                            <i class="fa-regular fa-pen-to-square"></i>
                                                        </a>
                                                    <?php }?>
                                                    <?php if(in_array(SIPARIS_SIL, $_SESSION['sayfa_idler'])){  ?>
                                                        <a href="/index.php?url=siparisler_admin_db_islem&islem=iptal&siparis_id=<?php echo $onaylanmis_siparis['id']; ?>" 
                                                            class="btn  btn-danger" 
                                                            onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="İptal"
                                                        >
                                                            <i class="fa-solid fa-ban"></i>
                                                        </a>
                                                    <?php }?>
                                                </div>
                                            </div> 
                                        </td> -->
                                    </tr>
                                <?php }?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="nav-biten" role="tabpanel" 
                    aria-labelledby="nav-tab-biten" tabindex="1">
                    <div class="table-responsive">
                        <table id="bitenTable" class="table table-hover table-sm">
                            <thead class="table-primary">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Sipariş No</th>
                                    <th>İşin Adı</th>
                                    <th>Müşteri</th>
                                    <th>M.Temsilcisi</th>
                                    <th class="text-center">Termin</th>
                                    <th class="text-end">Adet</th>
                                    <th class="text-end">Fiyat</th>
                                    <th class="text-center">Sil.Drm.</th>
                                    <th class="text-center">Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bitmis_siparisler  as $index=>$bitmis_siparis) { ?>
                                    <tr>
                                        <th class="table-primary text-center align-middle table_sm_pd"><?php echo $index+1;?></th>
                                        <td class="table-secondary align-middle table_sm_pd"><?php echo $bitmis_siparis['siparis_no'];?></td>
                                        <td class="align-middle table_sm_pd"><?php echo $bitmis_siparis['isin_adi']; ?></td>
                                        <td class="align-middle table_sm_pd"><?php echo $bitmis_siparis['marka']; ?></td>
                                        <td class="align-middle table_sm_pd"><?php echo $bitmis_siparis['personel_ad_soyad']; ?></td>
                                        <td class="align-middle text-center table_sm_pd"><?php echo date('d.m.Y',strtotime($bitmis_siparis['termin'])); ?></td>
                                        <td class="text-end align-middle table_sm_pd">
                                            <?php echo number_format($bitmis_siparis['adet'],0,'','.'); ?> Adet
                                        </td>
                                        <td class="text-end align-middle table_sm_pd">
                                            <?php 
                                                $para_cinsi = '<i class="fa-solid fa-turkish-lira-sign"></i>';
                                                if($bitmis_siparis['para_cinsi'] == 'DOLAR')      $para_cinsi = '<i class="fa-solid fa-dollar-sign"></i>';
                                                if($bitmis_siparis['para_cinsi'] == 'EURO')       $para_cinsi = '<i class="fa-solid fa-euro-sign"></i>';
                                                if($bitmis_siparis['para_cinsi'] == 'POUND')      $para_cinsi = '<i class="fa-solid fa-sterling-sign"></i>';
                                            ?>
                                            <?php echo number_format($bitmis_siparis['fiyat'], 2, ',', '.').' '.$para_cinsi; ?>
                                        </td>
                                        <td class="text-center align-middle table_sm_pd">
                                            <?php if($bitmis_siparis['islem'] === 'iptal'){ ?>
                                            <span class="badge rounded-pill bg-danger" style="
                                                padding-top: 5px;
                                                padding-bottom: 4px;
                                                padding-right: 5px;
                                                padding-left: 5px;
                                            "><?php echo $bitmis_siparis['islem'] ?>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center align-middle table_sm_pd">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input siparis-durum-switch" 
                                                       type="checkbox" 
                                                       role="switch" 
                                                       data-siparis-id="<?php echo $bitmis_siparis['id']; ?>"
                                                       <?php echo (isset($bitmis_siparis['aktif']) && $bitmis_siparis['aktif'] == 1) ? 'checked' : ''; ?>>
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
<?php  
    include_once "include/uyari_session_oldur.php";
?>

<script>
$(document).ready(function() {
    // Sipariş durumu switch değiştiğinde AJAX ile güncelle
    $('.siparis-durum-switch').on('change', function() {
        const siparisId = $(this).data('siparis-id');
        const durum = $(this).is(':checked') ? 1 : 0;
        const switchElement = $(this);
        const eskiDurum = !durum; // Eski durumu kaydet
        
        $.ajax({
            url: '/index.php?url=siparis_durum_guncelle',
            type: 'POST',
            data: {
                siparis_id: siparisId,
                durum: durum,
                firma_id: <?php echo $_SESSION['firma_id']; ?>
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Başarılı güncelleme - switch durumunu koru
                    console.log('Sipariş durumu güncellendi:', siparisId, durum);
                    
                    // Toast bildirim göster (SweetAlert2 olmadan)
                    showToast(response.message || 'Sipariş durumu güncellendi', 'success');
                } else {
                    // Hata durumunda switch'i eski haline geri al
                    switchElement.prop('checked', eskiDurum);
                    showToast(response.message || 'Bir hata oluştu', 'error');
                }
            },
            error: function(xhr, status, error) {
                // Hata durumunda switch'i eski haline geri al
                switchElement.prop('checked', eskiDurum);
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                showToast('Sipariş durumu güncellenirken bir hata oluştu.', 'error');
            }
        });
    });
    
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
    
    // ============================================
    // CANLI VERİ GÜNCELLEMESİ (Auto-Refresh)
    // ============================================
    let dataHash = ''; // Veri hash'i
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
    
    function checkForUpdates() {
        if (isRefreshing) return;
        
        isRefreshing = true;
        
        $.ajax({
            url: '/index.php?url=siparisler_onay_data',
            type: 'GET',
            data: {
                firma_id: <?php echo $_SESSION['firma_id']; ?>,
                hash: dataHash
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // İlk yüklemede hash'i kaydet
                    if (dataHash === '') {
                        dataHash = response.hash;
                        console.log('✓ Canlı veri takibi başlatıldı (10 sn aralık)');
                        showRefreshIndicator();
                    } 
                    // Değişiklik varsa yenile
                    else if (response.has_changes) {
                        console.log('🔄 Yeni veri tespit edildi, sayfa yenileniyor...');
                        
                        // Toast göster
                        showToast('Yeni veri güncelleniyor...', 'success');
                        
                        // 500ms sonra yenile (toast gösterme süresi)
                        setTimeout(() => location.reload(), 500);
                        return; // Reload sonrası çalışmayı durdur
                    }
                    
                    // Hash'i güncelle
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
    
    // 10 saniyede bir kontrol et
    setInterval(checkForUpdates, 10000);
    
    // Sayfa görünür olduğunda kontrol et
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('Sayfa aktif, veri kontrol ediliyor...');
            checkForUpdates();
        }
    });
});
</script> 