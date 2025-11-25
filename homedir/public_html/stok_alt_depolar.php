<?php 
    require_once "include/oturum_kontrol.php";
    
    $stok_alt_kalem_id  = intval($_GET['stok_alt_kalem_id']);
    $stok_id            = intval($_GET['stok_id']);

    $sth = $conn->prepare('SELECT stok_alt_kalemler.*, birimler.ad AS birim_ad FROM stok_alt_kalemler 
    LEFT JOIN birimler ON birimler.id = stok_alt_kalemler.birim_id 
    WHERE stok_alt_kalemler.id = :id AND stok_alt_kalemler.firma_id = :firma_id');
    $sth->bindParam('id', $stok_alt_kalem_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $stok_alt_kalem = $sth->fetch(PDO::FETCH_ASSOC);
    $veriler = json_decode($stok_alt_kalem['veri'], true);

    $sth = $conn->prepare('SELECT stok_kalem FROM stok_kalemleri WHERE id = :id');
    $sth->bindParam('id', $stok_id);
    $sth->execute();
    $stok_kalem = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($stok_kalem) || empty($stok_alt_kalem))
    {
        require_once "include/yetkisiz.php";
        die();
    }

    $sql = "SELECT SUM(tuketim_miktari) AS toplam_tuketim_miktari, SUM(fire_miktari) AS toplam_fire_miktar FROM `stok_alt_depolar_kullanilanlar` 
            WHERE stok_alt_kalem_id = :stok_alt_kalem_id ";
    $sth = $conn->prepare($sql);
    $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem['id']);
    $sth->execute();
    $toplam_tuketim = $sth->fetch(PDO::FETCH_ASSOC);

    // Firma Bilgileri 
    $sql = "SELECT firma_adi, etiket_logo, domain_adi FROM firmalar WHERE id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->execute();
    $firma_data = $sth->fetch(PDO::FETCH_ASSOC);

    if($firma_data === false || empty($firma_data)) {
        $etiketLogo = 'varsayilan.svg';
    }else{
        $etiketLogo = $firma_data['etiket_logo'];
    }  

    $etiket_yukseklik = 10;
    $etiket_genislik  = 10;
?>
<div class="row mt-2">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-secondary">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="text-danger fw-bold">
                        <?php echo $stok_kalem['stok_kalem'];?>
                    </h5>
                    <div>
                        <div class="d-flex justify-content-end"> 
                            <div class="btn-group justify-content-end" role="group" aria-label="Basic example">
                                <a href="/index.php?url=stok" 
                                    class="btn btn-secondary"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom" 
                                    data-bs-title="Geri Dön"
                                >
                                    <i class="fa-solid fa-arrow-left"></i>
                                </a>
                                <button data-bs-toggle="modal" 
                                    data-bs-target="#stok-alt-depo-ekle" 
                                    class="btn btn-primary align-self-end" 
                                    data-bs-placement="bottom" 
                                    data-bs-title="Ekle"
                                    type="button"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <?php 
                        $veriler = json_decode($stok_alt_kalem['veri'], true);
                    ?>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item active fw-bold" aria-current="true">STOK BİLGİLERİ</li>
                                <?php foreach ($veriler as $stok_alt_kalem_adi => $veri) { ?>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span class="badge bg-secondary fs-6"><?php echo $stok_alt_kalem_adi; ?></span>
                                        <span>
                                            <?php echo $veri;?>
                                        </span>
                                    </li>
                                <?php }?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item active fw-bold" aria-current="true">STOK BİLGİLERİ</li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="badge bg-secondary fs-6">Gelen Stok</span> 
                                    <span>
                                        <?php echo number_format($stok_alt_kalem['toplam_stok']);?>
                                        <?php echo $stok_alt_kalem['birim_ad']; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="badge bg-secondary fs-6">Tüketilen Stok</span> 
                                    <span>
                                        <?php echo number_format($toplam_tuketim['toplam_tuketim_miktari'] ?? 0);?>
                                        <?php echo $stok_alt_kalem['birim_ad']; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="badge bg-secondary fs-6">Fire Stok</span> 
                                    <span>
                                        <?php echo number_format($toplam_tuketim['toplam_fire_miktar'] ?? 0);?>
                                        <?php echo $stok_alt_kalem['birim_ad']; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="badge bg-secondary fs-6">Toplam Stok</span> 
                                    <span>
                                        <?php echo number_format($stok_alt_kalem['toplam_stok'] - ($toplam_tuketim['toplam_tuketim_miktari'] ?? 0) - ($toplam_tuketim['toplam_fire_miktar'] ?? 0));?>
                                        <?php echo $stok_alt_kalem['birim_ad']; ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card border-secondary">
                <div class="card-header">
                    <h5>
                        <i class="fa-sharp fa-solid fa-layer-group"></i> Gelen Stoklar
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <table id="myTable" class="table table-hover table-sm">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Stok Kodu</th>
                                <th>Fatura No</th>
                                <th class="text-end">Adet</th>
                                <th class="text-end">Kullanılan Adet</th>
                                <th class="text-end">Maliyet</th>
                                <th>Tedarikçi</th>
                                <th class="text-center">Tarih</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sth = $conn->prepare('SELECT stok_alt_depolar.id, stok_alt_depolar.`adet`,stok_alt_depolar.`stok_kodu`,
                                                    stok_alt_depolar.birim_id,stok_alt_depolar.qr_kod,`stok_alt_depolar`.`siparis_no`,
                                                    stok_alt_depolar.`maliyet`,stok_alt_depolar.`fatura_no`,stok_alt_depolar.ekleme_tarihi,
                                                    `stok_alt_depolar`.`para_cinsi`,`stok_alt_depolar`.`kullanilan_adet`,
                                                    tedarikciler.firma_adi, birimler.ad AS birim_ad
                                                    FROM stok_alt_depolar 
                                                    JOIN tedarikciler ON stok_alt_depolar.tedarikci_id = tedarikciler.id 
                                                    JOIN birimler ON birimler.id = stok_alt_depolar.birim_id
                                                    WHERE stok_alt_depolar.stok_alt_kalem_id = :stok_alt_kalem_id
                                                    ORDER BY stok_alt_depolar.id DESC
                                                ');
                            $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem_id);
                            $sth->execute();
                            $stok_alt_depolar = $sth->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php foreach ($stok_alt_depolar as $key => $stok_alt_depo) { ?>
                                <?php 
                                    $para_cinsi = '<i class="fa-solid fa-turkish-lira-sign"></i>';
                                    if($stok_alt_depo['para_cinsi'] == 'DOLAR')      $para_cinsi = '<i class="fa-solid fa-dollar-sign"></i>';
                                    if($stok_alt_depo['para_cinsi'] == 'EURO')       $para_cinsi = '<i class="fa-solid fa-euro-sign"></i>';
                                    if($stok_alt_depo['para_cinsi'] == 'POUND')      $para_cinsi = '<i class="fa-solid fa-sterling-sign"></i>';
                                ?>
                                <tr>
                                    <td class="table-primary text-center align-middle">
                                        <?php echo $key + 1; ?>
                                    </td>
                                    <td class="table-secondary align-middle">
                                        <?php echo $stok_alt_depo['stok_kodu']; ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php echo empty($stok_alt_depo['fatura_no']) ? '<b class="fw-bold text-danger">GİRİLMEDİ</b>':$stok_alt_depo['fatura_no']; ?> - 
                                        <?php if($stok_alt_depo['siparis_no']){?>
                                            <b class="text-success">
                                                <?php echo $stok_alt_depo['siparis_no']; ?> (S.Özel ve Tekrar Sipariş)</b>
                                        <?php } ?>
                                    </td>
                                    <th class="text-end table-success align-middle">
                                        <?php echo number_format($stok_alt_depo['adet']); ?>
                                        <?php echo $stok_alt_depo['birim_ad']; ?>
                                    </th>
                                    <th class="text-end table-primary align-middle">
                                        <?php echo number_format($stok_alt_depo['kullanilan_adet']); ?>
                                        <?php echo $stok_alt_depo['birim_ad']; ?>
                                    </th>
                                    <td class="text-end align-middle">
                                        <?php echo number_format($stok_alt_depo['maliyet'], 2, ',', '.'); ?> 
                                        <?php echo $para_cinsi;?>
                                    </td>
                                    <td class="align-middle"><?php echo $stok_alt_depo['firma_adi']; ?></td>
                                    
                                    <td class="text-center align-middle"><?php echo date('d-m-Y H:i:s', strtotime($stok_alt_depo['ekleme_tarihi'])); ?></td>
                                    
                                    <td class="align-middle" style="width:150px"> 
                                        <div class="d-flex justify-content-center"> 
                                            <div class="btn-group" role="group">
                                                <button 
                                                    data-supplier="<?php echo $stok_alt_depo['firma_adi']; ?>"
                                                    data-stok-kodu="<?php echo $stok_alt_depo['stok_kodu']; ?>"
                                                    data-tarih="<?php echo date('d.m.Y', strtotime($stok_alt_depo['ekleme_tarihi'])); ?>"
                                                    data-adet="<?php echo $stok_alt_depo['adet']; ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stokEtiketModal"
                                                    data-bs-title="Etiket Yazdır"
                                                    class="btn btn-secondary"
                                                    type="button"
                                                    id="stokEtiketModalButton"
                                                >
                                                    <i class="fa-solid fa-print"></i>
                                                </button>
                                                <button 
                                                    class="btn btn-danger stok-alt-depo-dusme"
                                                    data-stok-id="<?php echo $_GET['stok_id']; ?>"
                                                    data-stok-alt-depo-id="<?php echo $stok_alt_depo['id']; ?>"
                                                    data-birim-id="<?php echo $stok_alt_depo['birim_id']; ?>"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Stoktan Düşme"
                                                >
                                                    <i class="fa-solid fa-minus"></i>
                                                </button>
                                                <a href="/index.php?url=stok_alt_depolar_guncelle&id=<?php echo $stok_alt_depo['id']; ?>&stok_id=<?php echo $_GET['stok_id']; ?>" 
                                                    class="btn btn-warning"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Güncelle"
                                                >
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                                <a href="/index.php?url=stok_alt_depolar_db_islem&islem=stok_alt_depo_sil&id=<?php echo $stok_alt_depo['id']; ?>&stok_alt_kalem_id=<?php echo $_GET['stok_alt_kalem_id']?>&stok_id=<?php echo $stok_id;?>" 
                                                    onClick="return confirm('Silmek İstediğinize Emin Misiniz?')" 
                                                    class="btn btn-danger"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Sil"
                                                >
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>

        <div class="col-md-12">
            <div class="card border-secondary">
                <div class="card-header">
                    <h5>
                        <i class="fa-sharp fa-solid fa-layer-group"></i> Kullanılan Stoklar
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <table id="myTable" class="table table-hover table-sm">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Stok Kodu</th>
                                <th>Sipariş No</th>
                                <th>İşin Adı</th>
                                <th>Personel</th>
                                <th class="text-end">Tüketim Miktari</th>
                                <th class="text-end">Fire</th>
                                <th>Açıklama</th>
                                <th class="text-center">Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sth = $conn->prepare('SELECT stok_alt_depolar_kullanilanlar.tuketim_miktari,
                                                    stok_alt_depolar_kullanilanlar.fire_miktari,stok_alt_depolar_kullanilanlar.tarih,
                                                    `stok_alt_depolar`.`stok_kodu`, stok_alt_depolar_kullanilanlar.aciklama,
                                                    sip.siparis_no, sip.isin_adi,concat(prs.ad," ",prs.soyad) as personel
                                                    FROM stok_alt_depolar_kullanilanlar 
                                                    JOIN stok_alt_depolar ON stok_alt_depolar.id = stok_alt_depolar_kullanilanlar.stok_alt_depo_id
                                                    LEFT JOIN planlama as pln on pln.firma_id  = stok_alt_depolar.firma_id and pln.id = stok_alt_depolar_kullanilanlar.planlama_id
                                                    LEFT JOIN siparisler as sip on sip.firma_id  = pln.firma_id and sip.id = pln.siparis_id 
                                                    LEFT JOIN personeller as prs on prs.firma_id = stok_alt_depolar.firma_id and prs.id = stok_alt_depolar_kullanilanlar.personel_id
                                                    WHERE stok_alt_depolar_kullanilanlar.stok_alt_kalem_id = :stok_alt_kalem_id ORDER BY stok_alt_depolar_kullanilanlar.id DESC');
                            $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem_id);
                            $sth->execute();
                            $stok_alt_depolar_kullanilanlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php foreach ($stok_alt_depolar_kullanilanlar as $key => $stok_alt_depolar_kullanilan) { ?>
                                <tr>
                                    <td class="table-primary table_sm_pd align-middle text-center"><?php echo $key + 1; ?></td>
                                    <td class="table-secondary table_sm_pd align-middle"><?php echo $stok_alt_depolar_kullanilan['stok_kodu']; ?></td>
                                    <td class="table_sm_pd align-middle"><?php echo $stok_alt_depolar_kullanilan['siparis_no']; ?></td>
                                    <td class="table_sm_pd align-middle"><?php echo $stok_alt_depolar_kullanilan['isin_adi']; ?></td>
                                    <td class="table_sm_pd align-middle"><?php echo $stok_alt_depolar_kullanilan['personel']; ?></td>                                  
                                    <td class="text-end table_sm_pd align-middle"><?php echo number_format($stok_alt_depolar_kullanilan['tuketim_miktari']); ?></td>
                                    <td class="text-end table_sm_pd align-middle">
                                        <?php echo number_format($stok_alt_depolar_kullanilan['fire_miktari']); ?>
                                    </td>
                                    <td class="table_sm_pd align-middle">
                                        <?php echo $stok_alt_depolar_kullanilan['aciklama']; ?>
                                    </td>
                                    <td class="text-center table_sm_pd align-middle">
                                        <?php echo date('d.m.Y H:i:s', strtotime($stok_alt_depolar_kullanilan['tarih'])); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Stok Ekleme Moda -->
<div class="modal fade" id="stok-alt-depo-ekle" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog  modal-lg">
        <div class="modal-content">
            <form action="/index.php?url=stok_alt_depolar_db_islem" method="POST">
                <input type="hidden" name="stok_alt_kalem_id" value="<?php echo $_GET['stok_alt_kalem_id']; ?>">
                <input type="hidden" name="stok_id" value="<?php echo $_GET['stok_id']; ?>">
                <input type="hidden" name="stok_kodu" 
                    value="<?php echo str_replace(['Ğ','Ü','Ş','İ','Ö','Ç'],['G','U','S','I','O','C'],
                                                    mb_strtoupper(mb_substr($stok_kalem['stok_kalem'],0,3))); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Stok Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger fw-bold d-flex justify-content-between border-3">
                        <span>
                            1- Stok Eklenirken Takip İçin 
                            <span class="text-decoration-underline fst-italic">QR</span> Kod Oluşacaktır.
                        </span>
                        <span>
                            <i class="fa-solid fa-qrcode"></i>
                        </span>
                    </div>
                    
                    <div class="form-floating col-md-12">
                        <input type="number" class="form-control" name="adet" id="adet" required >
                        <label for="adet" class="form-label">Miktar</label>
                    </div>
                    <?php if(empty($stok_alt_depolar )){?>
                        <?php 
                            $sth = $conn->prepare('SELECT * FROM birimler ORDER BY ad ');
                            $sth->execute();
                            $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-12 mt-2">
                            <select class="form-select" id="birim" name="birim_id" required>
                                <option selected disabled value="">Seç...</option>
                                <?php foreach ($birimler as $birim) { ?>
                                    <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                <?php }?>
                            </select>
                            <label for="birim_id" class="form-label">Birim</label>
                        </div>
                    <?php }else{?> 
                        <input type="hidden" name="birim_id" value="<?php echo $stok_alt_depolar[0]['birim_id']?>">
                    <?php } ?>
                    
                    <div class="row g-3 mt-1">
                        <div class="form-floating col-md-6">
                            <select class="form-select" id="para_cinsi" name="para_cinsi" required>
                                <option selected disabled value="">Seçiniz</option>
                                <option value="TL">TL</option>
                                <option value="DOLAR">DOLAR</option>
                                <option value="EURO">EURO</option>
                                <option value="POUND">POUND</option>
                            </select>
                            <label for="para_cinsi" class="form-label">Para Cinsi</label>
                        </div>

                        <div class="form-floating col-md-6">
                            <input type="number" class="form-control" name="maliyet" id="maliyet" required step="0.001">
                            <label for="maliyet" class="form-label">Toplam Maliyet</label>
                        </div>
                    </div>

                    <div class="form-floating col-md-12 mt-2">
                        <input type="text" class="form-control" name="fatura_no" id="fatura_no" >
                        <label for="fatura_no" class="form-label">Fatura No</label>
                    </div>
                    <?php 
                        $sth = $conn->prepare('SELECT id, firma_adi FROM tedarikciler 
                        WHERE firma_id = :firma_id AND fason = "hayır" ORDER BY firma_adi ASC');
                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                        $sth->execute();
                        $tedarikciler = $sth->fetchAll(PDO::FETCH_ASSOC);
                    ?>    
                    <div class="form-floating col-md-12 mt-2">
                        <select name="tedarikci_id" id="tedarikci_id" class="form-control" required> 
                            <option value="" selected disabled>Seçiniz</option>
                            <?php foreach ($tedarikciler as $index => $tedarikci) { ?>
                                <option value="<?php echo $tedarikci['id'];?>">
                                    <?php echo ($index + 1).' - '.$tedarikci['firma_adi']; ?>
                                </option>
                            <?php }?>
                        </select>
                        <label for="tedarikci_id" class="form-label">Tedarikçi</label>
                    </div>

                    <div class="form-floating col-md-12 mt-2">
                        <?php 
                            $sql = "SELECT siparisler.stok_alt_depo_kod,siparisler.siparis_no, siparisler.isin_adi,
                                    musteri.marka FROM `siparisler` 
                                    JOIN musteri ON musteri.id = siparisler.musteri_id
                                    WHERE siparisler.firma_id = :firma_id ORDER BY siparisler.isin_adi ASC";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <select class="form-select form-select-lg" id="stok_alt_depo_kod" name="stok_alt_depo_kod">
                            <option value="">Seçiniz</option>    
                            <?php foreach ($siparisler as $index => $siparis) { ?>
                                <option value="<?php echo $siparis['stok_alt_depo_kod']; ?>">
                                    <?php echo ($index + 1).' - '.$siparis['siparis_no'].' - '.$siparis['marka'].' - '.$siparis['isin_adi'];?>
                                </option>  
                            <?php }?>
                        </select>
                        <label for="stok_alt_depo_kod" class="form-label">Siparişler</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="stok_alt_depo_ekle">
                        <i class="fa-regular fa-square-plus"></i> KAYDET
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-regular fa-rectangle-xmark"></i> İPTAL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stok Alt Depo Düşme -->
<div class="modal fade" id="stok-alt-depo-dusme" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-minus fs-4"></i> Alt Stok Depo Düşme
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/index.php?url=stok_alt_depolar_db_islem" method="POST" id="stok-dusme-form">
                    <input type="hidden" name="stok_id" id="stok-id">
                    <input type="hidden" name="stok_alt_depo_id" id="stok-alt-depo-id">
                    <input type="hidden" name="birim_id" id="birim-id">
                    <div class="form-floating col-md-12 mb-2">
                        <input type="number" class="form-control" name="adet" id="dusme-adeti" required >
                        <label for="dusme-adeti" class="form-label">Miktar</label>
                    </div>  
                    <div class="form-floating col-md-12 mb-2">
                        <textarea class="form-control" name="aciklama" id="aciklama" style="height: 100px"></textarea>
                        <label for="aciklama">Açıklama</label>
                    </div>   
                    
                    <button type="submit" class="btn btn-primary" name="stok_alt_depo_dusme" id="stok-dusme-button">
                        <i class="fa-regular fa-paper-plane"></i> KAYDET
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>

<!--  Etiket Yazdır -->
<div class="modal fade" id="stokEtiketModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">
                    <i class="fa-regular fa-file-pdf"></i> Etiket Yazdır
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>			
			</div>
            <div class="modal-body">
					<div class="row mb-1">
						<div class="col-md-6">
							<label for="labelWidth" class="form-label">Genişlik (cm):</label>
							<input type="number" id="stokLabelWidth" class="form-control" value="<?php echo $etiket_genislik; ?>" step="1">
						</div>
						<div class="col-md-6">
							<label for="labelHeight" class="form-label">Yükseklik (cm):</label>
							<input type="number" id="stokLabelHeight" class="form-control" value="<?php echo $etiket_yukseklik ?>" step="1">
						</div>
					</div>
					<div class="row mb-2">
						<div class="col-md-6">
							<label for="stokBoxQuantity" class="form-label">Adet:</label>
							<input type="number" id="stokBoxQuantity" class="form-control" value="0">
						</div> 
						<div class="col-md-6">
							<label for="stokDate" class="form-label">Tarih:</label>
							<input type="date" id="stokDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
						</div> 
					</div>
					<div id="stok-label-content" class="label-container" style="width: <?php echo $etiket_genislik; ?>cm; height: <?php echo $etiket_yukseklik; ?>cm;">
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
								<th>Tarih<br>Date</th>
								<td id="stokDateValue"><?php echo date('d.m.Y'); ?></td>
							</tr>
							<tr>
                                <th>Stok Kodu<br>Mtrl.Code</th>
                                <td id="stokKodu"></td>
							</tr>
                            <tr>
                                <th>Tedarikçi<br>Supplier</th>
                                <td id="stokSupplier"></td>
							</tr>
							<tr>
								<th>Adet<br>Quantity</th>
								<td id="stokBoxQuantityValue">0</td>
							</tr>
                            <tr>
                                <th>Stok Bilgileri<br>Stock Info</th> 
                                <td>
                                    <ul style="list-style:none;padding-left:2px;margin-bottom:2px">
                                        <?php foreach ($veriler as $key => $value) { ?>
                                            <li><strong><?php echo $key; ?>: </strong><?php echo $value; ?></li>
                                        <?php }?>
                                    </ul>
                                </td>
							</tr>
						</table>
					</div> 
			</div>
			<div class="modal-footer"> 
				<button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
				<button type="submit" class="btn btn-success waves-effect waves-light" id="stokEtiketYazdir">Yazdır</button>
				<button type="submit" class="btn btn-blue waves-effect waves-light" id="stokSaveAsPdf">Pdf</button>                       
			</div>
		</div>
	</div>
</div>

<?php 
    include_once "include/uyari_session_oldur.php";
?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function(){  
        //Stok Alt Depo Düşme Modal Açma
        $(".stok-alt-depo-dusme").click(function(){
            const stokId = $(this).data('stok-id');
            const stokAltDepoId = $(this).data('stok-alt-depo-id');
            const birimId = $(this).data('birim-id');

            $("#stok-id").val(stokId);
            $("#stok-alt-depo-id").val(stokAltDepoId);
            $("#birim-id").val(birimId);

            $("#stok-alt-depo-dusme").modal('show');
        });

        //Stok Alt Depo Düşme Button Disable
        $("#stok-dusme-form").submit(function(){
            $("#stok-dusme-button").addClass('disabled');
            return true;
        });
    });
</script>
<script>
    $(document).ready(function(){
        // QR kod oluşturma
        <?php 
              $data = [
                'Tedarikçi' => $stok_alt_depo['firma_adi'],
                'Stok Kodu' => $stok_alt_depo['stok_kodu'],
                'Tarih'     => date('d.m.Y', strtotime($stok_alt_depo['ekleme_tarihi'])),
                'Adet'      => $stok_alt_depo['adet']
            ];

            echo "const content = " . json_encode($data) . ";";
        ?>
        const qrCodeDiv = document.getElementById('qrcode');
        const qrContent = content
        new QRCode(qrCodeDiv, {
            text: qrContent,
            width: 50,
            height: 50,
            colorDark: "#000000",
            colorLight: "#ffffff"
        });
    });
</script>