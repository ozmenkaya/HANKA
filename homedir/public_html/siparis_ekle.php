<?php 
    include_once "include/oturum_kontrol.php";

    $musteri_id = intval($_GET['musteri_id']);

    $sth = $conn->prepare('SELECT firma_unvani FROM musteri WHERE id=:id AND firma_id = :firma_id');
    $sth->bindParam('id', $musteri_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $musteri = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($musteri))
    {
        require_once "include/yetkisiz.php"; exit;
    }

    $sth = $conn->prepare('SELECT * FROM birimler WHERE firma_id = :firma_id  ORDER BY ad;');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT * FROM `siparis_form` WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis_formlar = $sth->fetchAll(PDO::FETCH_ASSOC);
    
?>

<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->

<div class="row mt-2">
    
    <div class="modal fade" id="noAddressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="noAddressModalLabel" aria-hidden="true">
            <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noAddressModalLabel">Uyarı</h5>
                </div>
                <div class="modal-body">
                    Müşteri adresi bulunamadı. Lütfen önce bir adres ekleyin.
                </div>
                <div class="modal-footer">
                    <a href="/index.php?url=musteri_guncelle&id=<?= $musteri_id; ?>" class="btn btn-primary">Adres Ekle</a>
                </div>
            </div>
        </div>
    </div> 

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-bag-shopping"></i>
                Sipariş Ekle - Firma Ünvanı : <b><?php echo $musteri['firma_unvani']; ?></b>
            </h5>
            <div>
                <div class="d-md-flex justify-content-end"> 
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
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form class="row g-3 needs-validation" action="/index.php?url=siparis_db_islem" method="POST" enctype="multipart/form-data" id="siparis-ekle-form">
                <input type="hidden" name="musteri_id" value="<?php echo $musteri_id; ?>">
                <input type="hidden" id="alt-urun-sayisi" name="alt_urun_sayisi" value="0">

                <div class="card bg-light">
                    <div class="card-body row g-3">
                        <div class="form-floating col-md-12">
                            <?php 
                                $sth = $conn->prepare('SELECT siparis_form_tipleri.* FROM `siparis_form_tip_degerler` 
                                    JOIN siparis_form_tipleri 
                                    ON siparis_form_tipleri.id = siparis_form_tip_degerler.siparis_form_tip_id
                                    WHERE  siparis_form_tip_degerler.firma_id = :firma_id AND siparis_form_tip_degerler.deger = "1"');
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $siparis_form_tip_degerler = $sth->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <select class="form-select" id="tip" name="tip" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($siparis_form_tip_degerler as $siparis_form_tip_deger) { ?>
                                    <option value="<?php echo $siparis_form_tip_deger['id']; ?>">
                                    <?php echo $siparis_form_tip_deger['tip']; ?>
                                </option>
                                <?php }?>
                            </select>
                            <label for="tip" class="form-label">Sipariş Tipi</label>
                        </div>

                        <?php /*
                        <div class="form-floating col-md-6">
                            <?php 
                                $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <select class="form-select" id="tur_id" name="tur_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($turler as $tur) { ?>
                                    <option value="<?php echo $tur['id']; ?>"><?php echo $tur['tur']; ?></option>
                                <?php }?>
                            </select>
                            <label for="tur_id" class="form-label">Türü</label>
                        </div>
                        */?>
                        
                        <div class="form-floating col-md-12">
                            <input type="text" class="form-control" id="isin_adi" name="isin_adi" required />
                            <label for="isin_adi" class="form-label">İşin Adı</label>
                        </div>
                    </div>
                </div>


                <div class="card bg-light mt-3 ml-3" id="tek-fiyat" style="display:none;">
                    <div class="card-body row g-3">
                        <div class="form-floating col-md-2" id="tur_select">
                            <?php 
                                $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <select class="form-select" id="tek_fiyat_tur_id" name="tek_fiyat_tur_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($turler as $tur) { ?>
                                    <option value="<?php echo $tur['id']; ?>"><?php echo $tur['tur']; ?></option>
                                <?php }?>
                            </select>
                            <label for="tek_fiyat_tur_id" class="form-label">Türü</label>
                        </div>

                        <div class="form-floating col-md-2">
                            <input type="number" class="form-control" id="tek-fiyat-adet" name="tek_fiyat_adet" min="0" required />
                            <label for="tek-fiyat-adet" class="form-label">Miktar</label>
                        </div>
                        <div class="form-floating col-md-2">
                            <select class="form-select" id="tek-fiyat-birim" name="tek_fiyat_birim_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($birimler as $birim) { ?>
                                    <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                <?php }?>
                            </select>
                            <label for="birim_id" class="form-label">Birim</label>
                        </div>  


                        <div class="form-floating col-md-2">
                            <input type="number" class="form-control" id="tek-fiyat-birim-fiyat" name="tek_fiyat_birim_fiyat" step="0.001" min="0" required />
                            <label for="birim_fiyat" class="form-label">Birim Fiyat</label>
                        </div>

                        <div class="form-floating col-md-2">
                            <select class="form-select" id="tek-fiyat-kdv" name="tek_fiyat_kdv" required>
                                <option selected disabled value="">Seçiniz</option>
                                <option value="0">0</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                            </select>
                            <label for="kdv" class="form-label">Kdv</label>
                        </div>

                        <div class="form-floating col-md-2">
                            <select class="form-select" id="tek-fiyat-para-cinsi" name="tek_fiyat_para_cinsi" required>
                                <option selected disabled value="">Seçiniz</option>
                                <option value="TL">TL</option>
                                <option value="DOLAR">DOLAR</option>
                                <option value="EURO">EURO</option>
                                <option value="POUND">POUND</option>
                            </select>
                            <label for="para_cinsi" class="form-label">Para Cinsi</label>
                        </div>

                        <div class="form-floating col-md-2">
                            <input type="text" class="form-control border-success" id="tek-fiyat-toplam-fiyat" value="0"  readonly />
                            <label for="tek-fiyat-toplam-fiyat" class="form-label fw-bold">Toplam Fiyat</label>
                        </div>

                        <div class="form-floating col-md-3">
                            <select class="form-select" required id="tek-fiyat-numune" name="tek_fiyat_numune">
                                <option selected disabled value="">Seçiniz</option>
                                <option  value="0">Yok</option>
                                <option  value="0">Var</option>
                            </select> 
                            <label class="form-label">Numune</label>   
                        </div>
                        
                        <!--<div class="form-floating col-md-3">
                            <input type="file" class="form-control" multiple id="tek-fiyat-dosya" name="tek_fiyat_dosya[]"/>
                            <label id="tek-fiyat-dosya" class="form-label">Dosya yükle</label>
                        </div>-->

                        <div class="col-md-2">
                            <label for="tek-fiyat-dosya" style="background-color:white" class="input-group-text">Dosya yükle</label>
                            <input type="file" class="form-control" id="tek-fiyat-dosya" name="tek_fiyat_dosya[]" multiple />                   
                        </div>

                        <div class="form-floating col-md-6">
                            <textarea class="form-control" id="tek-fiyat-aciklama" name="tek_fiyat_aciklama" rows="3"></textarea>
                            <label for="aciklama" class="form-label">Açıklama</label>
                        </div>

                        <?php foreach ($siparis_formlar as $key => $siparis_form) { ?>
                            <div class="form-floating col-md-2 degerler" style="display:none" data-deger="<?php echo $siparis_form['deger']; ?>">
                                <?php 
                                if (!empty($siparis_form['options'])) { 
                                    $options_array = json_decode($siparis_form['options'], true); 
                                    if (is_array($options_array)) { ?>
                                        <select class="form-select" name="tek_fiyat_form[<?php echo $siparis_form['deger']; ?>]">
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($options_array as $option) { ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                            <?php } ?>
                                        </select>
                                    <?php } ?>
                                <?php } else { ?>
                                    <input type="text" class="form-control" name="tek_fiyat_form[<?php echo $siparis_form['deger']; ?>]" />
                                <?php } ?>
                                <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                            </div>
                        <?php } ?>
                    </div>
                </div>            

                <div class="card bg-light mt-3 ml-3" id="grup-tek-fiyat" style="display:none;">
                    <div class="card-body row g-3"> 

                        <div class="form-floating col-md-2">
                            <input type="number" class="form-control" id="grup-tek-fiyat-adet" name="grup_tek_fiyat_adet" min="0" required />
                            <label for="adet" class="form-label">Miktar</label>
                        </div>

                        <div class="form-floating col-md-2">
                            <select class="form-select" id="grup-tek-fiyat-birim" name="grup_tek_fiyat_birim_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($birimler as $birim) { ?>
                                    <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                <?php }?>
                            </select>
                            <label for="grup-tek-fiyat-birim-id" class="form-label">Birim</label>
                        </div>   

                        <div class="form-floating col-md-2">
                            <input type="number" class="form-control" id="grup-tek-fiyat-birim-fiyat" name="grup_tek_fiyat_birim_fiyat" 
                                step="0.001" min="0" required />
                            <label for="grup-tek-fiyat-birim-fiyat" class="form-label">Birim Fiyat</label>
                        </div>

                        <div class="form-floating col-md-2">
                            <select class="form-select" id="grup-tek-fiyat-kdv" name="grup_tek_fiyat_kdv" required>
                                <option selected disabled value="">Seçiniz</option>
                                <option value="0">0</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                            </select>
                            <label for="grup-tek-fiyat-kdv" class="form-label">Kdv</label>
                        </div>

                        <div class="form-floating col-md-2">
                            <select class="form-select" id="grup-tek-fiyat-para-cinsi" name="grup_tek_fiyat_para_cinsi" required>
                                <option selected disabled value="">Seçiniz</option>
                                <option value="TL">TL</option>
                                <option value="DOLLAR">DOLLAR</option>
                                <option value="EURO">EURO</option>
                                <option value="POUND">POUND</option>
                            </select>
                            <label for="grup-tek-fiyat-para-cinsi" class="form-label">Para Cinsi</label>
                        </div>


                        <div class="form-floating col-md-2">
                            <input type="text" class="form-control border-success" id="grup-tek-fiyat-toplam-fiyat" value="0"  readonly />
                            <label for="grup-tek-fiyat-toplam-fiyat" class="form-label fw-bold">Toplam Fiyat</label>
                        </div>

                        <div class="card bg-body-secondary">
                            <div class="card-body">   
                                <div id="grup-tek-fiyat-alt-urunler">
                                    <div class="alt-urun row g-3">
                                        <div class="col-md-2 fs-4">
                                            <div class="input-group fw-bold">
                                                <button class="btn btn-success alt-urun-button-ekle"  type="button"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom"
                                                    data-bs-custom-class="custom-tooltip"
                                                    data-bs-title="Alt Ürün Ekle"
                                                >
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                                <input type="text" name="grup_tek_fiyat_alt_urun_1[isim]"  class="form-control form-control-lg"  placeholder="Alt Ürün" required>
                                            </div>
                                        </div>  

                                        <div class="form-floating col-md-2">
                                            <?php 
                                                $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                $sth->execute();
                                                $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <select class="form-select tur-select" id="grup_tek_fiyat_alt_urun_1" name="grup_tek_fiyat_alt_urun_1[tur]" data-urun-id="1" required>
                                                <option selected disabled value="">Seçiniz</option>
                                                <?php foreach ($turler as $tur) { ?>
                                                    <option value="<?php echo $tur['id']; ?>"><?php echo $tur['tur']; ?></option>
                                                <?php }?>
                                            </select>
                                            <label for="grup_tek_fiyat_alt_urun_1" class="form-label">Türü</label>
                                        </div>

                                        <div class="form-floating col-md-2">
                                            <input type="number" class="form-control" id="grup_tek_fiyat_alt_urun_miktar_1" name="grup_tek_fiyat_alt_urun_1[miktar]"/>
                                            <label for="grup_tek_fiyat_alt_urun_miktar_1" class="form-label">Miktar</label>
                                        </div>

                                        <div class="form-floating col-md-2">
                                            <select class="form-select"  name="grup_tek_fiyat_alt_urun_1[birim_id]" required>
                                                <option selected disabled value="">Seçiniz</option>
                                                <?php foreach ($birimler as $birim) { ?>
                                                    <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                                <?php }?>
                                            </select>
                                            <label for="birim_id" class="form-label">Birim</label>
                                        </div> 


                                        <div class="form-floating col-md-2">
                                            <select class="form-select" name="grup_tek_fiyat_alt_urun_1[numune]" required>
                                                <option selected disabled value="">Seçiniz</option>
                                                <option  value="0">Yok</option>
                                                <option  value="0">Var</option>
                                            </select> 
                                            <label class="form-label">Numune</label>   
                                        </div>
                                        
                                        <!--<div class="form-floating col-md-2">
                                            <input type="file" class="form-control" name="grup_tek_fiyat_alt_urun_1[]" multiple />
                                            <label class="form-label">Dosya yükle</label>
                                        </div>-->

                                        <div class="col-md-2">
                                            <label for="grup_tek_fiyat_alt_urun_1" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                            <input type="file" class="form-control" id="grup_tek_fiyat_alt_urun_1" name="grup_tek_fiyat_alt_urun_1[]" multiple />                   
                                        </div>

                                        <div class="form-floating col-md-2">
                                            <textarea class="form-control" name="grup_tek_fiyat_alt_urun_1[aciklama]"></textarea>
                                            <label for="aciklama" class="form-label">Açıklama</label>
                                        </div>

                                        <?php foreach ($siparis_formlar as $key => $siparis_form) { ?>
                                            <div class="form-floating col-md-2 degerler_1" style="display:none" data-deger="<?php echo $siparis_form['deger']; ?>">
                                                <?php 
                                                if (!empty($siparis_form['options'])) { 
                                                    $options_array = json_decode($siparis_form['options'], true); 
                                                    if (is_array($options_array)) { ?>
                                                        <select class="form-select" name="grup_tek_fiyat_alt_urun_1[form][<?php echo $siparis_form['deger']; ?>]">
                                                            <option value=""><?php echo $siparis_form['deger']; ?> Seçiniz</option>
                                                            <?php foreach ($options_array as $option) { ?>
                                                                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <input type="text" class="form-control" name="grup_tek_fiyat_alt_urun_1[form][<?php echo $siparis_form['deger']; ?>]" />
                                                <?php } ?>
                                                <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <hr>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    
                <div class="card bg-light mt-3 ml-3" id="grup-ayri-fiyat" style="display:none;">
                    <div class="card-body row g-3"> 
                        <div class="form-floating col-md-4">
                            <input type="number" class="form-control" id="grup-ayri-fiyat-adet" name="grup_ayri_fiyat_adet"  min="0" required />
                            <label for="adet" class="form-label" id="grup-ayri-fiyat-adet">Miktar</label>
                        </div>

                        <div class="form-floating col-md-4">
                            <select class="form-select" id="grup-ayri-fiyat-birim-id" name="grup_ayri_fiyat_birim_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($birimler as $birim) { ?>
                                    <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                <?php }?>
                            </select>
                            <label for="grup-ayri-fiyat-birim-id" class="form-label">Birim</label>
                        </div>  
                        
                        <div class="form-floating col-md-4">
                            <select class="form-select" id="grup_ayri_fiyat_para_cinsi" name="grup_ayri_fiyat_para_cinsi" required>
                                <option selected disabled value="">Seçiniz</option>
                                <option value="TL">TL</option>
                                <option value="DOLLAR">DOLLAR</option>
                                <option value="EURO">EURO</option>
                                <option value="POUND">POUND</option>
                            </select>
                            <label for="grup_ayri_fiyat_para_cinsi" class="form-label">Para Cinsi</label>
                        </div>   
                        <div class="card bg-body-secondary">
                            <div class="card-body">      
                                <div id="grup-ayri-fiyat-alt-urunler">
                                    <div class="alt-urun row g-3">
                                        <div class="col-md-2">
                                            <div class="input-group fw-bold">
                                                <button class="btn btn-success alt-urun-button-ekle"  type="button"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom"
                                                    data-bs-custom-class="custom-tooltip"
                                                    data-bs-title="Alt Ürün Ekle"
                                                    data-alt-urun-id="1"
                                                >
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                                <input type="text" name="grup_ayri_fiyat_alt_urun_1[isim]"  class="form-control form-control-lg"  placeholder="Alt Ürün" required>
                                            </div>
                                        </div>  

                                        <div class="form-floating col-md-2">
                                            <?php 
                                                $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                $sth->execute();
                                                $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <select class="form-select tur-select" id="grup_ayri_fiyat_alt_urun_tur_1" name="grup_ayri_fiyat_alt_urun_1[tur]" data-urun-id="1" required>
                                                <option selected disabled value="">Seçiniz</option>
                                                <?php foreach ($turler as $tur) { ?>
                                                    <option value="<?php echo $tur['id']; ?>"><?php echo $tur['tur']; ?></option>
                                                <?php }?>
                                            </select>
                                            <label for="grup_ayri_fiyat_alt_urun_tur_1" class="form-label">Türü</label>
                                        </div>

                                        <div class="form-floating col-md-2">
                                            <input type="number" class="form-control grup-ayri-fiyat-miktar" name="grup_ayri_fiyat_alt_urun_1[miktar]"/>
                                            <label class="form-label">Miktar</label>
                                        </div>

                                        <div class="form-floating col-md-2">
                                            <select class="form-select" name="grup_ayri_fiyat_alt_urun_1[birim_id]" required>
                                                <option selected disabled value="">Seçiniz</option>
                                                <?php foreach ($birimler as $birim) { ?>
                                                    <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                                <?php }?>
                                            </select>
                                            <label class="form-label">Birim</label>
                                        </div> 
                                        
                                        <div class="form-floating col-md-2">
                                            <input type="number" class="form-control grup-ayri-fiyat-birim-fiyat" name="grup_ayri_fiyat_alt_urun_1[birim_fiyat]" step="0.001" min="0" required />
                                            <label class="form-label">Birim Fiyat</label>
                                        </div>

                                        <div class="form-floating col-md-2">
                                            <select class="form-select"  name="grup_ayri_fiyat_alt_urun_1[kdv]"required>
                                                <option selected disabled value="">Seçiniz</option>
                                                <option value="0">0</option>
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                            </select>
                                            <label  class="form-label">Kdv</label>
                                        </div>

                                        <div class="form-floating col-md-2">
                                            <input type="text" class="form-control grup-ayri-fiyat-toplam-fiyat border-success"   readonly />
                                            <label class="form-label fw-bold">Toplam Fiyat</label>
                                        </div>

                                        <div class="form-floating col-md-3">
                                            <select class="form-select" required name="grup_ayri_fiyat_alt_urun_1[numune]">
                                                <option selected disabled value="">Seçiniz</option>
                                                <option  value="0">Yok</option>
                                                <option  value="0">Var</option>
                                            </select> 
                                            <label class="form-label">Numune</label>   
                                        </div>
                                        
                                        <!--<div class="form-floating col-md-3">
                                            <input type="file" class="form-control" multiple name="grup_ayri_fiyat_alt_urun_1[]"/>
                                            <label class="form-label">Dosya yükle</label>
                                        </div>-->
                                        
                                        <div class="col-md-2">
                                            <label for="grup_ayri_fiyat_alt_urun_1" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                            <input type="file" class="form-control" id="grup_ayri_fiyat_alt_urun_1" name="grup_ayri_fiyat_alt_urun_1[]" multiple />                   
                                        </div>

                                        <div class="form-floating col-md-6">
                                            <textarea class="form-control"  name="grup_ayri_fiyat_alt_urun_1[aciklama]"></textarea>
                                            <label  class="form-label">Açıklama</label>
                                        </div>

                                        <?php foreach ($siparis_formlar as $key => $siparis_form) { ?>
                                            <div class="form-floating col-md-2 degerler_1" style="display:none" data-deger="<?php echo $siparis_form['deger']; ?>">
                                                <?php 
                                                if (!empty($siparis_form['options'])) { 
                                                    $options_array = json_decode($siparis_form['options'], true); 
                                                    if (is_array($options_array)) { ?>
                                                        <select class="form-select" name="grup_ayri_fiyat_alt_urun_1[form][<?php echo $siparis_form['deger']; ?>]">
                                                            <option value="">Seçiniz</option>
                                                            <?php foreach ($options_array as $option) { ?>
                                                                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <input type="text" class="form-control" name="grup_ayri_fiyat_alt_urun_1[form][<?php echo $siparis_form['deger']; ?>]" />
                                                <?php } ?>
                                                <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                            </div>
                                        <?php } ?>
                                        <hr>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>              
                </div>

                <div class="card bg-light">
                    <div class="card-body row g-3">
                        <?php
                        // Musterinin adreslerini getir
                        $adres_query = $conn->prepare('SELECT ma.*, u.baslik as ulke_adi, s.baslik as sehir_adi, i.baslik as ilce_adi 
                                                    FROM musteri_adresleri ma 
                                                    LEFT JOIN ulkeler u ON ma.ulke_id = u.id 
                                                    LEFT JOIN sehirler s ON ma.sehir_id = s.id 
                                                    LEFT JOIN ilceler i ON ma.ilce_id = i.id 
                                                    WHERE ma.musteri_id = :musteri_id');
                        $adres_query->bindParam(':musteri_id', $musteri_id);
                        $adres_query->execute();
                        $musteri_adresleri = $adres_query->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($musteri_adresleri)) {
                                echo '<script>
                                    var noAddressModal = new bootstrap.Modal(document.getElementById("noAddressModal"));
                                    noAddressModal.show();
                                </script>';
                            }
                        ?>
                        
                        <div class="form-floating col-md-12">
                            <select class="form-select" id="musteri_adres_sec" name="musteri_adres_sec">
                                <option value="" selected disabled>Adres Seçiniz</option>
                                <?php foreach($musteri_adresleri as $adres): ?>
                                    <option value="<?php echo $adres['id']; ?>" 
                                            data-adres="<?php echo htmlspecialchars($adres['adres']); ?>" 
                                            data-ulke="<?php echo $adres['ulke_id']; ?>" 
                                            data-sehir="<?php echo $adres['sehir_id']; ?>" 
                                            data-ilce="<?php echo $adres['ilce_id']; ?>" 
                                            data-adres-turu="<?php echo $adres['adres_turu']; ?>">
                                        <?php echo $adres['adres_turu']; ?> - 
                                        <?php echo $adres['baslik']; ?> - 
                                        <?php echo $adres['ulke_adi']; ?>, 
                                        <?php echo $adres['sehir_adi']; ?>, 
                                        <?php echo $adres['ilce_adi']; ?> 
                                        <?php echo $adres['is_default'] ? '(Varsayılan)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="musteri_adres_sec" class="form-label">Müşteri Adresi</label>
                        </div>

                        <?php 
                            $sth = $conn->prepare('SELECT id, baslik FROM ulkeler ORDER BY baslik ');
                            $sth->execute();
                            $ulkeler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="ulke_id" name="ulke_id" required>
                                <option selected disabled value="">Ülke Seçiniz</option>
                                <option value="223">Türkiye</option>
                                <?php foreach ($ulkeler as $ulke) { ?>
                                    <option value="<?php echo $ulke['id']; ?>"><?php echo $ulke['baslik']; ?></option>
                                <?php }?>
                            </select>
                            <label for="ulke_id" class="form-label">Ülke</label>
                        </div>

                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="sehir_id" name="sehir_id" required>
                                <option selected disabled>Şehir Seçiniz</option>
                            </select>
                            <label for="sehir_id" class="form-label">Şehir</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="ilce_id" name="ilce_id" required>
                                <option selected disabled>İlçe Seçiniz</option>
                            </select>
                            <label for="ilce_id" class="form-label">İlçe</label>
                        </div>

                        <div class="form-floating col-md-12">
                            <input type="text" class="form-control" id="teslimat_adresi" name="teslimat_adresi" required />
                            <label for="teslimat_adresi" class="form-label">Teslimat Adresi</label>
                        </div>

                        <div class="form-floating col-md-4">
                            <input type="date" class="form-control" id="termin" name="termin" required />
                            <label for="termin" class="form-label">Termin Tarihi</label>
                        </div>

                        <div class="form-floating col-md-4">
                            <input type="date" class="form-control" id="uretim" name="uretim" required />
                            <label for="uretim" class="form-label">Üretim Tarihi</label>
                        </div>

                        <?php  
                            $sth = $conn->prepare('SELECT id, ad, soyad FROM personeller 
                                WHERE yetki_id IN(2,4) AND firma_id = :firma_id');
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4">
                            <select class="form-select" id="musteri_temsilcisi_id" name="musteri_temsilcisi_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($personeller as $personel) { ?>
                                    <option value="<?php echo $personel['id']; ?>"><?php echo $personel['ad'].' '.$personel['soyad']; ?></option>
                                <?php } ?>
                            </select>
                            <label for="musteri_temsilcisi_id" class="form-label">Musteri Temsilcisi</label>
                        </div>

                        <div class="form-floating col-md-4">
                            <input type="date" class="form-control" id="vade" name="vade" required />
                            <label for="vade" class="form-label">Vade</label>
                        </div>

                        <?php 
                            $sth = $conn->prepare('SELECT * FROM odeme_tipleri');
                            $sth->execute();
                            $odeme_tipleri = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4">
                            <select class="form-select" id="odeme_sekli_id" name="odeme_sekli_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($odeme_tipleri as $odeme_tipi) { ?>
                                    <option value="<?php echo $odeme_tipi['id'];?>"><?php echo $odeme_tipi['odeme_sekli']; ?></option>
                                <?php }?>
                            </select>
                            <label for="odeme_sekli_id" class="form-label">Ödeme Şekli</label>
                        </div>

                        <div class="form-floating col-md-6">
                            <textarea class="form-control" id="nakliye" name="nakliye" rows="3" required></textarea>
                            <label for="nakliye" class="form-label">Nakliye</label>
                        </div>

                        <div class="form-floating col-md-6">
                            <textarea class="form-control" id="paketleme" name="paketleme" rows="3"></textarea>
                            <label for="paketleme" class="form-label">Paketleme</label>
                        </div>

                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button class="btn btn-primary" type="submit" name="siparis_ekle" id="siparis-ekle-button">
                            <i class="fa-regular fa-square-plus"></i> KAYDET
                        </button>
                        <a href="/index.php?url=siparis&musteri_id=<?php echo $musteri_id;?>" class="btn btn-danger">
                            <i class="fa-regular fa-circle-xmark"></i> İPTAL
                        </a>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
            const TEKLI_URUN        = 1;
            const GRUP_TEK_FIYAT    = 2;
            const GRUP_AYRI_FIYAT   = 3;
            let altUrunSayisi       = 1;
            $(document).ready(function() {
                //form submit edildiğinde button pasife çekme
                $("#siparis-ekle-form").submit(function(){
                    $("#siparis-ekle-button").addClass('disabled');
                    return true;
                });

                $("#grup-tek-fiyat,#grup-ayri-fiyat").hide();
                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });

                $('.form-select').select2({
                    theme: 'bootstrap-5'
                });

                //
                $("#tek_fiyat_tur_id").change(function(){
                    const turId = $(this).val();
                    turIdGoreSiparisFormuGetirme(turId);
                });

                //grup ayrı fiyat satır toplam fiyat hesaplama
                //.grup-ayri-fiyat-birim-fiyat .grup-ayri-fiyat-miktar
                $(document).on('keyup', '.grup-ayri-fiyat-miktar', function(){
                    const miktar        = $(this).val();
                    const birimFiyat    = $(this).closest('.alt-urun').find('.grup-ayri-fiyat-birim-fiyat').val();
                    $(this).closest('.alt-urun').find('.grup-ayri-fiyat-toplam-fiyat').val((miktar*birimFiyat).toFixed(2));
                });

                $(document).on('keyup', '.grup-ayri-fiyat-birim-fiyat', function(){
                    const birimFiyat    = $(this).val();
                    const miktar        = $(this).closest('.alt-urun').find('.grup-ayri-fiyat-miktar').val();
                    $(this).closest('.alt-urun').find('.grup-ayri-fiyat-toplam-fiyat').val((miktar*birimFiyat).toFixed(2));
                });

                //grup ayrı fiyat alt ürün ekle
                $("#grup-ayri-fiyat-alt-urunler .alt-urun-button-ekle").click(function(){
                    altUrunSayisi++;
                    
                    // Sipariş formlarını PHP'den JSON olarak al
                    const siparisFormlar = <?php echo json_encode($siparis_formlar); ?>;
                    const birimlerData = <?php echo json_encode($birimler); ?>;
                    const turlerData = <?php echo json_encode($turler); ?>;
                    
                    // Türler için option HTML oluştur
                    let turlerOptions = '<option selected disabled value="">Seçiniz</option>';
                    turlerData.forEach(function(tur) {
                        turlerOptions += `<option value="${tur.id}">${tur.tur}</option>`;
                    });
                    
                    // Birimler için option HTML oluştur
                    let birimlerOptions = '<option selected disabled value="">Seçiniz</option>';
                    birimlerData.forEach(function(birim) {
                        birimlerOptions += `<option value="${birim.id}">${birim.ad}</option>`;
                    });
                    
                    // Sipariş form alanlarını oluştur
                    let formAlanlariHTML = '';
                    siparisFormlar.forEach(function(siparis_form) {
                        let inputHTML = '';
                        if (siparis_form.options && siparis_form.options !== '') {
                            try {
                                const options_array = JSON.parse(siparis_form.options);
                                if (Array.isArray(options_array)) {
                                    inputHTML = `<select class="form-select" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[form][${siparis_form.deger}]">
                                        <option value="">Seçiniz</option>`;
                                    options_array.forEach(function(option) {
                                        inputHTML += `<option value="${option}">${option}</option>`;
                                    });
                                    inputHTML += `</select>`;
                                }
                            } catch(e) {
                                inputHTML = `<input type="text" class="form-control" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[form][${siparis_form.deger}]" />`;
                            }
                        } else {
                            inputHTML = `<input type="text" class="form-control" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[form][${siparis_form.deger}]" />`;
                        }
                        
                        formAlanlariHTML += `
                            <div class="form-floating col-md-2 degerler_${altUrunSayisi}" style="display:none" data-deger="${siparis_form.deger}">
                                ${inputHTML}
                                <label class="form-label">${siparis_form.deger}</label>
                            </div>`;
                    });
                    
                    const altUrunHTML = `
                        <div class="alt-urun row g-3">
                            <div class="col-md-2">
                                <div class="input-group fw-bold">
                                    <button class="btn btn-danger alt-urun-button-cikar"  type="button"
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="bottom"
                                        data-bs-custom-class="custom-tooltip"
                                        data-bs-title="Alt Ürün Çıkar"
                                        data-alt-urun-id="${altUrunSayisi}"
                                    >
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="text" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[isim]"  class="form-control form-control-lg"  placeholder="Alt Ürün" required>
                                </div>
                            </div>  

                            <div class="form-floating col-md-2">
                                <select class="form-select tur-select" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[tur]" data-urun-id="${altUrunSayisi}" required>
                                    ${turlerOptions}
                                </select>
                                <label class="form-label">Türü</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <input type="number" class="form-control grup-ayri-fiyat-miktar" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[miktar]"/>
                                <label class="form-label">Miktar</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <select class="form-select" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[birim_id]" required>
                                    ${birimlerOptions}
                                </select>
                                <label  class="form-label">Birim</label>
                            </div> 
                            
                            <div class="form-floating col-md-2">
                                <input type="number" class="form-control grup-ayri-fiyat-birim-fiyat" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[birim_fiyat]" step="0.001" min="0" required />
                                <label  class="form-label">Birim Fiyat</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <select class="form-select" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[kdv]" required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <option value="0">0</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                </select>
                                <label class="form-label">Kdv</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <input type="text" class="form-control grup-ayri-fiyat-toplam-fiyat border-success border-2"  readonly />
                                <label  class="form-label fw-bold">Toplam Fiyat</label>
                            </div>

                            <div class="form-floating col-md-3">
                                <select class="form-select" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[numune]" required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <option  value="0">Yok</option>
                                    <option  value="0">Var</option>
                                </select> 
                                <label class="form-label">Numune</label>   
                            </div>

                            <div class="col-md-2">
                                <label for="grup_ayri_fiyat_alt_urun_${altUrunSayisi}" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                <input type="file" class="form-control" id="grup_ayri_fiyat_alt_urun_${altUrunSayisi}" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[]" multiple />                   
                            </div>

                            <div class="form-floating col-md-6">
                                <textarea class="form-control" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[aciklama]"></textarea>
                                <label  class="form-label">Açıklama</label>
                            </div>

                            ${formAlanlariHTML}

                            <hr>
                        </div>
                    `;
                    $("#alt-urun-sayisi").val(altUrunSayisi);  
                    $("#grup-ayri-fiyat-alt-urunler").append(altUrunHTML);

                    // Yeni eklenen satır için tur-select event'ini bağla
                    $(`[name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[tur]"]`).on('change', function() {
                        const turId = $(this).val(); 
                        const urunId = $(this).data("urun-id");
                        turIdGoreSiparisFormuGetirmeCustom(turId, urunId);
                    });

                    $('.form-select').select2({
                        theme: 'bootstrap-5'
                    });
                });

                //grup ayrı fiyat alt ürün çıkar
                $(document).on('click','#grup-ayri-fiyat-alt-urunler .alt-urun-button-cikar', function(){
                    $(this).closest('.alt-urun').remove();
                    altUrunSayisi--;
                    $("#alt-urun-sayisi").val(altUrunSayisi);
                })

                //grup tek fiyat alt ürün çıkar
                $(document).on('click','#grup-tek-fiyat-alt-urunler .alt-urun-button-cikar', function(){
                    $(this).closest('.alt-urun').remove();
                    altUrunSayisi--;
                    $("#alt-urun-sayisi").val(altUrunSayisi);  
                })

                //grup tek fiyat alt ürün ekle
                $("#grup-tek-fiyat-alt-urunler .alt-urun-button-ekle").click(function(){
                    altUrunSayisi++;
                    
                    // Sipariş formlarını PHP'den JSON olarak al
                    const siparisFormlar = <?php echo json_encode($siparis_formlar); ?>;
                    const birimlerData = <?php echo json_encode($birimler); ?>;
                    const turlerData = <?php echo json_encode($turler); ?>;
                    
                    // Türler için option HTML oluştur
                    let turlerOptions = '<option selected disabled value="">Seçiniz</option>';
                    turlerData.forEach(function(tur) {
                        turlerOptions += `<option value="${tur.id}">${tur.tur}</option>`;
                    });
                    
                    // Birimler için option HTML oluştur
                    let birimlerOptions = '<option selected disabled value="">Seçiniz</option>';
                    birimlerData.forEach(function(birim) {
                        birimlerOptions += `<option value="${birim.id}">${birim.ad}</option>`;
                    });
                    
                    // Sipariş form alanlarını oluştur
                    let formAlanlariHTML = '';
                    siparisFormlar.forEach(function(siparis_form) {
                        let inputHTML = '';
                        if (siparis_form.options && siparis_form.options !== '') {
                            try {
                                const options_array = JSON.parse(siparis_form.options);
                                if (Array.isArray(options_array)) {
                                    inputHTML = `<select class="form-select" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[form][${siparis_form.deger}]">
                                        <option value="">${siparis_form.deger} Seçiniz</option>`;
                                    options_array.forEach(function(option) {
                                        inputHTML += `<option value="${option}">${option}</option>`;
                                    });
                                    inputHTML += `</select>`;
                                }
                            } catch(e) {
                                inputHTML = `<input type="text" class="form-control" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[form][${siparis_form.deger}]" />`;
                            }
                        } else {
                            inputHTML = `<input type="text" class="form-control" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[form][${siparis_form.deger}]" />`;
                        }
                        
                        formAlanlariHTML += `
                            <div class="form-floating col-md-2 degerler_${altUrunSayisi}" style="display:none" data-deger="${siparis_form.deger}">
                                ${inputHTML}
                                <label class="form-label">${siparis_form.deger}</label>
                            </div>`;
                    });
                    
                    let altUrunHTML = `
                        <div class="alt-urun row g-3 mt-2">
                            <div class="col-md-2">
                                <div class="input-group fw-bold">
                                    <button class="btn btn-danger alt-urun-button-cikar"  type="button"
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="bottom"
                                        data-bs-custom-class="custom-tooltip"
                                        data-bs-title="Alt Ürün Çıkar"
                                        data-alt-urun-id="${altUrunSayisi}"
                                    >
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="text" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[isim]"   class="form-control form-control-lg"  placeholder="Alt Ürün" required>
                                </div>
                            </div> 

                            <div class="form-floating col-md-2">
                                <select class="form-select tur-select" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[tur]" data-urun-id="${altUrunSayisi}" required>
                                    ${turlerOptions}
                                </select>
                                <label class="form-label">Türü</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <input type="number" class="form-control" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[miktar]" >
                                <label class="form-label">Miktar</label>
                            </div> 

                            <div class="form-floating col-md-2">
                                <select class="form-select" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[birim_id]"  required>
                                    ${birimlerOptions}
                                </select>
                                <label for="birim_id" class="form-label">Birim</label>
                            </div> 

                            <div class="form-floating col-md-2">
                                <select class="form-select" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[numune]"  required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <option  value="0">Yok</option>
                                    <option  value="0">Var</option>
                                </select> 
                                <label class="form-label">Numune</label>   
                            </div>

                            <div class="col-md-2">
                                <label for="grup_tek_fiyat_alt_urun_${altUrunSayisi}" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                <input type="file" class="form-control" id="grup_tek_fiyat_alt_urun_${altUrunSayisi}" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[]" multiple />                   
                            </div>

                            <div class="form-floating col-md-2">
                                <textarea class="form-control" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[aciklama]" ></textarea>
                                <label class="form-label">Açıklama</label>
                            </div>

                            ${formAlanlariHTML}
                            <hr>
                        </div>
                    `;
                    $("#alt-urun-sayisi").val(altUrunSayisi);            
                    $("#grup-tek-fiyat-alt-urunler").append(altUrunHTML);
                    
                    // Yeni eklenen satır için tur-select event'ini bağla
                    $(`[name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[tur]"]`).on('change', function() {
                        const turId = $(this).val(); 
                        const urunId = $(this).data("urun-id");
                        turIdGoreSiparisFormuGetirmeCustom(turId, urunId);
                    });

                    $('.form-select').select2({
                        theme: 'bootstrap-5'
                    });

                });

                //adet ve birim fiyat değiştiğinde
                $("#tek-fiyat-adet, #tek-fiyat-birim-fiyat").keyup(function(){
                    const adet          = $("#tek-fiyat-adet").val();
                    const birim_fiyat   = $("#tek-fiyat-birim-fiyat").val();
                    $("#tek-fiyat-toplam-fiyat").val((adet*birim_fiyat).toFixed(2))
                });

                
                $("#grup-tek-fiyat-adet, #grup-tek-fiyat-birim-fiyat").keyup(function(){
                    const adet          = $("#grup-tek-fiyat-adet").val();
                    const birim_fiyat   = $("#grup-tek-fiyat-birim-fiyat").val();
                    $("#grup-tek-fiyat-toplam-fiyat").val((adet*birim_fiyat).toFixed(2))
                });


                //tip değiştirme
                $("#tip").change(function(){
                    altUrunSayisi = 1;
                    $("#alt-urun-sayisi").val(altUrunSayisi);
                    
                    const tip = $(this).val();
                    if(tip == TEKLI_URUN){
                        //$("#tur_select").show();
                        $("#tek-fiyat").show();
                        $("#grup-tek-fiyat").hide();
                        $("#grup-ayri-fiyat").hide();
                        $("#grup-tek-fiyat *, #grup-ayri-fiyat *").prop('disabled', true);
                        $("#tek-fiyat *").prop('disabled', false);
                        $("#alt-urun-sayisi").val(0);
                    }
                    else if(tip == GRUP_TEK_FIYAT){
                        //$("#tur_select").hide();
                        $("#grup-tek-fiyat").show();
                        $("#grup-ayri-fiyat").hide();
                        $("#tek-fiyat").hide();
                        $("#tek-fiyat *, #grup-ayri-fiyat *").prop('disabled', true);
                        $("#grup-tek-fiyat *").prop('disabled', false);
                    }
                    else if(tip == GRUP_AYRI_FIYAT){
                        //$("#tur_select").hide();
                        $("#grup-tek-fiyat").hide();
                        $("#grup-ayri-fiyat").show();
                        $("#tek-fiyat").hide();
                        $("#tek-fiyat *, #grup-tek-fiyat *").prop('disabled', true);
                        $("#grup-ayri-fiyat *").prop('disabled', false);
                    }

                    //$("#tek-fiyat-toplam-fiyat").prop('disabled', true);
                    //$("#grup-tek-fiyat-toplam-fiyat").prop('disabled', true);
                    //$(".grup-ayri-fiyat-toplam-fiyat").prop('disabled', true);
                });

                $("#ulke_id").change(function(){
                    const ulke_id = $(this).val();

                    $.ajax({
                        url         : "/index.php?url=ulke_il_ilce_kontrol&ulke_id=" + ulke_id,
                        dataType    : "JSON",
                        success     : function(sehirler){
                            let sehirler_HTML = "<option selected disabled>Şehir Seçiniz</option>";

                            for(const sehir of sehirler)
                            {
                                sehirler_HTML += `
                                    <option value="${sehir.id}">${sehir.baslik}</option>
                                `;
                            }
                            $("#sehir_id").html(sehirler_HTML);
                        }
                    });

                });

                $("#sehir_id").change(function(){
                    const sehir_id = $(this).val();

                    $.ajax({
                        url         : "/index.php?url=ulke_il_ilce_kontrol&sehir_id=" + sehir_id,
                        dataType    : "JSON",
                        success     : function(ilceler){
                            let ilceler_HTML =  "<option selected disabled>İlçe Seçiniz</option>";
                            for(const ilce of ilceler)
                            {
                                ilceler_HTML += `
                                    <option value="${ilce.id}">${ilce.baslik}</option>
                                `;
                            }

                            $("#ilce_id").html(ilceler_HTML);
                        }

                    });
                });
                
                //Müşteri adres seçildiğinde
                $("#musteri_adres_sec").change(function(){
                    const secilen_adres_id = $(this).val();
                    const secilen_adres = $("#musteri_adres_sec option:selected");
                    const adres = secilen_adres.attr('data-adres');
                    const ulke_id = secilen_adres.attr('data-ulke');
                    const sehir_id = secilen_adres.attr('data-sehir');
                    const ilce_id = secilen_adres.attr('data-ilce');

                    // Teslimat adresini doldur
                    $("#teslimat_adresi").val(adres);
                    
                    // Ülke seçimini güncelle
                    $("#ulke_id").val(ulke_id).trigger('change');
                    
                    // Şehir listesini getir ve seç
                    $.ajax({
                        url: "/index.php?url=ulke_il_ilce_kontrol&ulke_id=" + ulke_id,
                        dataType: "JSON",
                        success: function(sehirler){
                            let sehirler_HTML = "<option selected disabled>İl Seçiniz</option>";
                            
                            for(const sehir of sehirler) {
                                const selected = (sehir.id == sehir_id) ? 'selected' : '';
                                sehirler_HTML += `<option value="${sehir.id}" ${selected}>${sehir.baslik}</option>`;
                            }
                            
                            $("#sehir_id").html(sehirler_HTML);
                            
                            // İlçe listesini getir ve seç
                            $.ajax({
                                url: "/index.php?url=ulke_il_ilce_kontrol&sehir_id=" + sehir_id,
                                dataType: "JSON",
                                success: function(ilceler){
                                    let ilceler_HTML = "<option selected disabled>İlçe Seçiniz</option>";
                                    
                                    for(const ilce of ilceler) {
                                        const selected = (ilce.id == ilce_id) ? 'selected' : '';
                                        ilceler_HTML += `<option value="${ilce.id}" ${selected}>${ilce.baslik}</option>`;
                                    }
                                    
                                    $("#ilce_id").html(ilceler_HTML);
                                }
                            });
                        }
                    });
                });

                $('.tur-select').on('change', function() {
                    const turId = $(this).val(); 
                    const urunId = $(this).data("urun-id"); // Hangi ürüne ait olduğunu alıyoruz
                    turIdGoreSiparisFormuGetirmeCustom(turId, urunId);
                }); 
            });
            
            function turIdGoreSiparisFormuGetirme(turId){             
                $.ajax({
                    url         : "/index.php?url=siparis_db_islem" ,
                    dataType    : "JSON",
                    type        : "POST",
                    data        : {tur_id:turId, islem:'tur_id_gore_siparis_form_getir'},
                    success     : function(veriler){ 
                        $(".degerler").hide();
                        for(const siparis_form of veriler.siparis_formlar){
                            $(`[data-deger="${siparis_form.deger}"`).closest('div').show();
                            
                        }
                    }
                });
            }
 
            function turIdGoreSiparisFormuGetirmeCustom(turId, urunId){
                $.ajax({
                    url         : "/index.php?url=siparis_db_islem",
                    dataType    : "JSON",
                    type        : "POST",
                    data        : {tur_id: turId, islem: 'tur_id_gore_siparis_form_getir'},
                    success     : function(veriler){ 
                        // Önce ilgili ürün içindeki tüm sipariş form alanlarını gizle
                        $(`.degerler_${urunId}`).hide();

                        console.log(`Gizle: .degerler_${urunId}`);

                        // Gelen verilere göre ilgili ürünün sipariş form alanlarını göster
                        for(const siparis_form of veriler.siparis_formlar){
                            console.log(`Yükle: .degerler_${urunId}[data-urun-id="${urunId}"][data-deger="${siparis_form.deger}"]`);
                            $(`.degerler_${urunId}[data-deger="${siparis_form.deger}"]`).show();
                        }
                    }
                });
            }
        </script>