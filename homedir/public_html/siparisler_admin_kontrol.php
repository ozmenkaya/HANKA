<?php 
    include_once "include/oturum_kontrol.php";

    $siparis_id = isset($_GET['siparis_id']) ? intval($_GET['siparis_id']) : 0;

    $sth = $conn->prepare('SELECT id,tip_id, musteri_id,veriler, isin_adi, tur_id, adet, birim_id, 
    teslimat_adresi, ulke_id, sehir_id, ilce_id, termin, uretim, vade, fiyat, para_cinsi, 
    odeme_sekli_id, numune, aciklama, musteri_temsilcisi_id, paketleme,nakliye, islem
    FROM siparisler WHERE id=:id AND firma_id = :firma_id');
    $sth->bindParam('id', $siparis_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();

    $siparis = $sth->fetch(PDO::FETCH_ASSOC);
    
    if(empty($siparis))
    {
        require_once "include/yetkisiz.php";
        die();
    }

    $sth = $conn->prepare('SELECT ad FROM siparis_dosyalar WHERE siparis_id = :siparis_id');
    $sth->bindParam('siparis_id', $siparis['id']);
    $sth->execute();
    $siparis_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);


    $sth = $conn->prepare('SELECT firma_unvani FROM musteri WHERE id = :id');
    $sth->bindParam('id', $siparis['musteri_id']);
    $sth->execute();
    $musteri = $sth->fetch(PDO::FETCH_ASSOC);
    

    $sth = $conn->prepare('SELECT * FROM birimler WHERE firma_id = :firma_id  ORDER BY ad;');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT * FROM `siparis_form` WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis_formlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM `siparis_dosyalar` WHERE siparis_id = :siparis_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id', $siparis['id']);
    $sth->execute();
    $siparis_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $tur_id = $siparis['tur_id'];

    $sth = $conn->prepare('SELECT * FROM siparis_form WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis_formlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $siparis_formlar_idler = [];
    
    if ( $siparis['tip_id'] != 1) {
        foreach( json_decode($siparis['veriler'], true) as $veriKey => $veriForm ) {
            foreach ( $siparis_formlar as $key => $siparis_form ) {
                $tur_idler = json_decode($siparis_form['tur_idler'], true);
                if (isset($veriForm['tur'])) {
                    if(in_array($veriForm['tur'], $tur_idler)){
                        $siparis_formlar_idler[$veriKey][] = $siparis_form['id'];
                    }
                }
            }
        }
    } else {
        $siparis_formlar_idler = [];

        // Gelen veriyi her zaman çoklu gibi işle (tekli ise diziye al)
        $veriler = json_decode($siparis['veriler'], true);
        if ( !isset($veriler[0]) ) {
            $veriler = [$veriler]; // Tekli siparişi dizi haline getir
        }

        if ( !isset($veriler[0]['tur']) ) {
            $veriler[0]['tur'] = $siparis['tur_id'];
            
            $siparis_veriler_tur = json_decode($siparis['veriler'], true); // JSON'u diziye çevir

            // Eğer $siparis_veriler_tur bir dizi değilse, varsayılan olarak boş bir dizi oluştur
            if (!is_array($siparis_veriler_tur)) {
                $siparis_veriler_tur = [];
            }

            // Yeni "tur" anahtarını ekle
            $siparis_veriler_tur['tur'] = $siparis['tur_id']; 

            // Diziyi tekrar JSON formatına çevir
            $siparis['veriler'] = json_encode($siparis_veriler_tur, JSON_UNESCAPED_UNICODE);

        }

        // Çoklu sipariş mantığı ile her tur_id için formları al
        foreach ($veriler as $veriKey => $veriForm) {
            foreach ($siparis_formlar as $key => $siparis_form) {
                $tur_idler = json_decode($siparis_form['tur_idler'], true);
                if ($tur_idler != null) {
                    if ( in_array($veriForm['tur'], $tur_idler) ) {
                        $siparis_formlar_idler[$veriKey][] = $siparis_form['id'];
                    }
                }
            }
        }
    }

    /*
    foreach ( $siparis_formlar as $key => $siparis_form ) {
        
        $tur_idler = json_decode($siparis_form['tur_idler'], true);
        if(in_array($tur_id, $tur_idler)){
            $siparis_formlar_idler[] = $siparis_form['id'];
        }

    }
    */
    

    #echo "<pre>"; print_R($siparis_formlar_sonuc); exit;
?>

        <div class="row mt-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-bag-shopping"></i>
                        Sipariş Bilgileri Güncelleme Formu -
                        Firma Ünvanı : <b class="text-danger"><?php echo $musteri['firma_unvani']; ?></b>
                    </h5>
                    <div>
                        <div class="d-flex justify-content-end"> 
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <a href="javascript:window.history.back();" 
                                    class="btn btn-secondary"
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
                    <?php 
                        $veriler        = json_decode($siparis['veriler'], true); 
                        if($siparis['tip_id'] == TEK_URUN){
                            $veriler[] = $siparis['veriler'];
                        }
                        $altUrunSayisi  = 1;
                        if(in_array($siparis['tip_id'],[GRUP_URUN_TEK_FIYAT,GRUP_URUN_AYRI_FIYAT])){
                            $altUrunSayisi = count($veriler);
                        }
                    ?>
                    <form class="row g-3 needs-validation" action="/index.php?url=siparisler_admin_db_islem" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="siparis_id" value="<?php echo $siparis['id'];?>">
                        <input type="hidden" name="eski_tip_id" value="<?php echo $siparis['tip_id'];?>">
                        <input type="hidden" id="alt-urun-sayisi" name="alt_urun_sayisi" value="<?php echo $altUrunSayisi; ?>">
                        <input type="hidden" name="musteri_id" value="<?php echo $siparis['musteri_id'];?>">
                        <div class="card bg-light mt-0">
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
                                        <option  disabled value="">Seçiniz</option>
                                        <?php foreach ($siparis_form_tip_degerler as $siparis_form_tip_deger) { ?>
                                            <option value="<?php echo $siparis_form_tip_deger['id']; ?>"
                                                <?php echo $siparis_form_tip_deger['id'] == $siparis['tip_id'] ? 'selected' : ''; ?>
                                            >
                                                <?php echo $siparis_form_tip_deger['tip']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="tip" class="form-label">Sipariş Tipi</label>
                                </div>

                                <?php /*
                                <div class="form-floating col-md-6" id="tur_select">
                                    <?php 
                                        $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                        $sth->execute();
                                        $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <select class="form-select" id="tur_id" name="tur_id" required>
                                        <option selected disabled value="">Seçiniz</option>
                                        <?php foreach ($turler as $tur) { ?>
                                            <option value="<?php echo $tur['id']; ?>" 
                                                <?php echo $tur['id'] == $siparis['tur_id'] ? 'selected' : ''; ?>
                                            >
                                                <?php echo $tur['tur']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="tur_id" class="form-label">Türü</label>
                                </div>
                                */
                                ?>

                                <div class="form-floating col-md-12">
                                    <input type="text" class="form-control" id="isin_adi" name="isin_adi" value="<?php echo $siparis['isin_adi']; ?>" required />
                                    <label for="isin_adi" class="form-label">İşin Adı</label>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light mt-0 ml-3" id="tek-fiyat" style="display:<?php echo $siparis['tip_id'] == TEK_URUN ? '':'none';?>;">
                            <?php 
                                $miktar         = isset($veriler['miktar'])         ? intval($veriler['miktar']) : 0;
                                $birim_fiyat    = isset($veriler['birim_fiyat'])    ? floatval($veriler['birim_fiyat']) : 0;
                                $birim_id       = isset($veriler['birim_id'])       ? $veriler['birim_id'] : 0;
                                $numune         = isset($veriler['numune'])         ? $veriler['numune'] : 0;
                                $aciklama       = isset($veriler['aciklama'])       ? $veriler['aciklama'] :'';  
                                $form           = isset($veriler['form'])           ? $veriler['form'] : [];
                                $kdv            = isset($veriler['kdv'])            ? $veriler['kdv'] :0;
                                $tur_id        = isset($veriler['tur']) ? $veriler['tur'] : 0;

                            ?>
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
                                        <?php foreach ($turler as $turItem) { 
                                            $selectedTur0 = ($turItem['id'] == $tur_id ? 'selected' : null);
                                            ?>
                                            <option value="<?php echo $turItem['id']; ?>" <?php echo $selectedTur0;?>>
                                                <?php echo $turItem['tur']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="tek_fiyat_tur_id" class="form-label">Türü</label>
                                </div>

                                <div class="form-floating col-md-2">
                                    <input type="number" class="form-control" id="tek-fiyat-adet" name="tek_fiyat_adet" value="<?php echo $siparis['tip_id'] == TEK_URUN ? $siparis['adet'] : ''; ?>" min="0" required />
                                    <label for="adet" class="form-label">Miktar</label>
                                </div>
                                <div class="form-floating col-md-2">
                                    <select class="form-select" id="tek-fiyat-birim" name="tek_fiyat_birim_id" required>
                                        <option <?php echo $siparis['tip_id'] != TEK_URUN ? 'selected':''; ?> disabled value="">Seçiniz</option>
                                        <?php foreach ($birimler as $birim) { ?>
                                            <option value="<?php echo $birim['id']; ?>"
                                                <?php echo $siparis['tip_id'] == TEK_URUN && $birim_id == $birim['id'] ? 'selected':'';?>
                                            >
                                                <?php echo $birim['ad']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="birim_id" class="form-label">Birim</label>
                                </div>  

                                <div class="form-floating col-md-2">
                                    <input type="number" class="form-control" id="tek-fiyat-birim-fiyat" name="tek_fiyat_birim_fiyat" step="0.001" min="0" 
                                        value="<?php echo $siparis['tip_id'] == TEK_URUN ? $birim_fiyat : '';?>" required >
                                    <label for="birim_fiyat" class="form-label">Birim Fiyat</label>
                                </div>

                                <div class="form-floating col-md-2">
                                    <select  class="form-select" id="tek-fiyat-kdv" name="tek_fiyat_kdv" required>
                                        <option <?php echo $siparis['tip_id'] != TEK_URUN ? 'selected':''; ?> disabled value="">Seçiniz</option>
                                        <option value="0"   <?php echo $siparis['tip_id'] == TEK_URUN && $kdv == 0 ? 'selected':'';?>>0</option>
                                        <option value="10"  <?php echo $siparis['tip_id'] == TEK_URUN && $kdv == 10 ? 'selected':'';?>>10</option>
                                        <option value="20"  <?php echo $siparis['tip_id'] == TEK_URUN && $kdv == 20 ? 'selected':'';?>>20</option>
                                    </select>
                                    <label for="kdv" class="form-label">Kdv</label>
                                </div>

                                <div class="form-floating col-md-2">
                                    <select class="form-select" id="tek-fiyat-para-cinsi" name="tek_fiyat_para_cinsi" required>
                                        <option <?php echo $siparis['tip_id'] != TEK_URUN ? 'selected':''; ?> disabled value="">Seçiniz</option>
                                        <option value="TL"      <?php echo $siparis['tip_id'] == TEK_URUN && $siparis['para_cinsi'] == 'TL' ? 'selected':'';?>>TL</option>
                                        <option value="DOLLAR"  <?php echo $siparis['tip_id'] == TEK_URUN && $siparis['para_cinsi'] == 'DOLLAR' ? 'selected':'';?>>DOLLAR</option>
                                        <option value="EURO"    <?php echo $siparis['tip_id'] == TEK_URUN && $siparis['para_cinsi'] == 'EURO' ? 'selected':'';?>>EURO</option>
                                        <option value="POUND"   <?php echo $siparis['tip_id'] == TEK_URUN && $siparis['para_cinsi'] == 'POUND' ? 'selected':'';?>>POUND</option>
                                    </select>
                                    <label for="para_cinsi" class="form-label">Para Cinsi</label>
                                </div>

                                <div class="form-floating col-md-2">
                                    <input type="text" class="form-control border-success" id="tek-fiyat-toplam-fiyat" 
                                        value="<?php echo $siparis['tip_id'] == TEK_URUN ? $miktar*$birim_fiyat : '';?>"  readonly >
                                    <label for="tek-fiyat-toplam-fiyat" class="form-label fw-bold">Toplam Fiyat</label>
                                </div>

                                <div class="form-floating col-md-3">
                                    <select class="form-select" required id="tek-fiyat-numune" name="tek_fiyat_numune">
                                        <option <?php echo $siparis['tip_id'] != TEK_URUN ? 'selected':''; ?> disabled value="">Seçiniz</option>
                                        <option  value="0" <?php echo $siparis['tip_id'] == TEK_URUN && $numune == 0 ? 'selected':'';?>>Yok</option>
                                        <option  value="1" <?php echo $siparis['tip_id'] == TEK_URUN && $numune == 1 ? 'selected':'';?>>Var</option>
                                    </select> 
                                    <label class="form-label">Numune</label>   
                                </div>
                                
                                <!--<div class="form-floating col-md-3">
                                    <input type="file" class="form-control" multiple id="tek-fiyat-dosya" name="tek_fiyat_dosya[]" >
                                    <label id="tek-fiyat-dosya" class="form-label">Dosya yükle</label>
                                </div>-->

                                <div class="col-md-2">
                                    <label for="tek-fiyat-dosya" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                    <input type="file" class="form-control" id="tek-fiyat-dosya" name="tek_fiyat_dosya[]" multiple />                   
                                </div>

                                <div class="form-floating col-md-6">
                                    <textarea class="form-control" id="tek-fiyat-aciklama" name="tek_fiyat_aciklama" ><?php echo $siparis['tip_id'] == TEK_URUN ? $aciklama : ''; ?></textarea>
                                    <label for="aciklama" class="form-label">Açıklama</label>
                                </div>

                                <?php 
                                 if($siparis['tip_id'] == TEK_URUN) {
                                            foreach ($siparis_formlar as $key => $siparis_form) { 
                                    ?>
                                    <div class="form-floating col-md-2 degerler_1" style="display:<?php echo !in_array($siparis_form['id'], $siparis_formlar_idler[0]) ? 'none':'';?>"
                                                            data-deger="<?php echo $siparis_form['deger']; ?>"
                                                            >
                                                        <?php
                                                        if (!empty($siparis_form['options'])) { 
                                                            $options_array = json_decode($siparis_form['options'], true); 
                                                            if (is_array($options_array)) { 
                                                                ?>
                                                                <select class="form-select" name="tek_fiyat_form[<?php echo $siparis_form['deger']; ?>]">
                                                                    <option value="">
                                                                        <?php echo $siparis_form['deger']; ?> Seçiniz
                                                                    </option>
                                                                    <?php 
                                                                    foreach ($options_array as $option) { 
                                                                        $selectedO = isset($form[$siparis_form['deger']]) && $form[$siparis_form['deger']] == $option ? 'selected' : null;
                                                                        ?>
                                                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $selectedO; ?>><?php echo htmlspecialchars($option); ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            <?php } ?>
                                                        <?php } else { ?>
                                                            <input type="text" class="form-control" name="tek_fiyat_form[<?php echo $siparis_form['deger']; ?>]" 
                                                                value="<?php echo isset($form[$siparis_form['deger']]) ? $form[$siparis_form['deger']] : '';?>" />
                                                        <?php } ?>
                                                        <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                                    </div>
                                                    <?php }
                                                }  ?>

                                <?php 
                                /*
                                if($siparis['tip_id'] == TEK_URUN) {
                                    foreach ($siparis_formlar as $key => $siparis_form) { 
                                        var_dump($siparis_formlar_idler);
                                    ?>
                                        <div class="form-floating col-md-2 degerler" 
                                            style="display:<?php echo !in_array($siparis_form['id'], $siparis_formlar_idler)? 'none':'';?>"
                                            data-deger="<?php echo $siparis_form['deger']; ?>"
                                        >
                                            <input type="text" class="form-control" 
                                                name="tek_fiyat_form[<?php echo $siparis_form['deger'];?>]"
                                                value="<?php echo $siparis['tip_id'] == TEK_URUN && isset($form[$siparis_form['deger']]) ? $form[$siparis_form['deger']] : '';?>"
                                            >
                                            <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                        </div>
                                        <?php
                                    }
                                }
                                */
                                ?>

                                <?php if($siparis['tip_id'] == TEK_URUN) { ?>
                                    <div class="tek-fiyat-dosyalar">
                                        <?php foreach ($siparis_dosyalar as  $siparis_dosya) { ?>
                                            <?php if($siparis_dosya['alt_urun_index'] == 0){?> 
                                                <span style="position:relative;display:inline-block">
                                                    <button type="button" class="btn btn-danger btn-sm resim-sil" 
                                                        data-resim-id="<?php echo $siparis_dosya['id']; ?>" 
                                                        style="position:absolute;right:4px;top:4px"
                                                        data-resim-ad="<?php echo $siparis_dosya['ad'];?>"
                                                    >
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                    <?php 
                                                        $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                                    ?>

                                                    <?php if($uzanti == 'pdf'){ ?>
                                                        <a 
                                                            class="pdf-modal-goster"
                                                            data-href="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" 
                                                            href="javascript:;"
                                                        >
                                                            <img src="dosyalar/pdf.png" 
                                                                class="rounded img-thumbnai border border-secondary-subtle object-fit-fill" 
                                                                alt="<?php //echo $veri['isim']; ?>" 
                                                                style="width:100px;height:100px;"
                                                                
                                                            > 
                                                        </a>
                                                    <?php }else{?>
                                                        <a class="example-image-link" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                            data-lightbox="example-set" data-title="<?php echo $siparis['isin_adi']; ?> Resim(ler)">
                                                            <img src="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" 
                                                                class="rounded img-thumbnai border border-secondary-subtle object-fit-fill" 
                                                                alt="<?php //echo $veri['isim']; ?>" 
                                                                style="width:100px;height:100px;"
                                                            >   
                                                        </a> 
                                                    <?php } ?>
                                                </span>
                                            <?php } ?>
                                        <?php }?>

                                        <?php if(empty($siparis_dosyalar)){ ?>
                                            <div class="text-danger fw-bold border border-danger rounded p-3" >
                                                <i class="fa-solid fa-exclamation"></i> Dosya Yok
                                            </div>
                                        <?php }?>
                                    </div>
                                <?php }?>
                            </div>
                        </div>  
                        
                        <div class="card bg-light mt-0 ml-3" id="grup-tek-fiyat" style="display:<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? '':'none';?>;">            
                            <?php 
                                $birim_fiyat    = isset($veriler[0]['birim_fiyat']) ? $veriler[0]['birim_fiyat'] : 0;
                                $kdv            = isset($veriler[0]['kdv'])         ? $veriler[0]['kdv'] : 0;    
                            ?>
                            <div class="card-body row g-3">
                                <div class="form-floating col-md-2">
                                    <input type="number" class="form-control" id="grup-tek-fiyat-adet" name="grup_tek_fiyat_adet" min="0" 
                                        value="<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? $siparis['adet'] : ''; ?>" required >
                                    <label for="adet" class="form-label">Miktar</label>
                                </div>
                                
                                <div class="form-floating col-md-2">
                                    <select class="form-select" id="grup-tek-fiyat-birim" name="grup_tek_fiyat_birim_id" required>
                                        <option <?php echo $siparis['tip_id'] != GRUP_URUN_TEK_FIYAT ? 'selected':''; ?> disabled value="">Seçiniz</option>
                                        <?php foreach ($birimler as $birim) { ?>
                                            <option value="<?php echo $birim['id']; ?>"
                                                <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $siparis['birim_id']== $birim['id'] ? 'selected':'';?>
                                            >
                                                <?php echo $birim['ad']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="grup-tek-fiyat-birim-id" class="form-label">Birim</label>
                                </div>  

                                <div class="form-floating col-md-2">
                                    <input type="number" class="form-control" id="grup-tek-fiyat-birim-fiyat" name="grup_tek_fiyat_birim_fiyat" 
                                        step="0.001" min="0" required value="<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? $birim_fiyat : ''; ?>">
                                    <label for="grup-tek-fiyat-birim-fiyat" class="form-label">Birim Fiyat</label>
                                </div>

                                <div class="form-floating col-md-2">
                                    <select class="form-select" id="grup-tek-fiyat-kdv" name="grup_tek_fiyat_kdv" required>
                                        <option <?php echo $siparis['tip_id'] != GRUP_URUN_TEK_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                        <option value="0"   <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $kdv == 0 ? 'selected':'';?>>0</option>
                                        <option value="10"  <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $kdv == 10 ? 'selected':'';?>>10</option>
                                        <option value="20"  <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $kdv == 20 ? 'selected':'';?>>20</option>
                                    </select>
                                    <label for="grup-tek-fiyat-kdv" class="form-label">Kdv</label>
                                </div>

                                <div class="form-floating col-md-2">
                                    <select class="form-select" id="grup-tek-fiyat-para-cinsi" name="grup_tek_fiyat_para_cinsi" required>
                                        <option <?php echo $siparis['tip_id'] != GRUP_URUN_TEK_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                        <option value="TL"      <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $siparis['para_cinsi'] == 'TL' ? 'selected':'';?>>TL</option>
                                        <option value="DOLLAR"  <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $siparis['para_cinsi'] == 'DOLLAR' ? 'selected':'';?>>DOLLAR</option>
                                        <option value="EURO"    <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $siparis['para_cinsi'] == 'EURO' ? 'selected':'';?>>EURO</option>
                                        <option value="POUND"   <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $siparis['para_cinsi'] == 'POUND' ? 'selected':'';?>>POUND</option>
                                    </select>
                                    <label for="grup-tek-fiyat-para-cinsi" class="form-label">Para Cinsi</label>
                                </div>


                                <div class="form-floating col-md-2">
                                    <input type="text" class="form-control border-success" id="grup-tek-fiyat-toplam-fiyat"  
                                        value="<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? $siparis['adet']*$birim_fiyat : ''; ?>" disabled readonly >
                                    <label for="grup-tek-fiyat-toplam-fiyat" class="form-label fw-bold">Toplam Fiyat</label>
                                </div>
                                
                                <div class="card bg-body-secondary">
                                    <div class="card-body">
                                        <div id="grup-tek-fiyat-alt-urunler">
                                            <?php 
                                            foreach($veriler as $index => $veri){ ?>
                                                <div class="alt-urun row g-3">
                                                    <div class="col-md-2">
                                                        <div class="input-group fw-bold">
                                                            <button class="btn <?php echo $index == 0 ? 'btn-success alt-urun-button-ekle': 'btn-danger alt-urun-button-cikar';?>"  type="button"
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom"
                                                                data-bs-custom-class="custom-tooltip"
                                                                data-bs-title="<?php echo $index == 0 ? 'Alt Ürün Ekle': 'Alt Ürün Çıkar';?>"
                                                            >
                                                                <?php if($index == 0){?>
                                                                    <i class="fa-solid fa-plus"></i>
                                                                <?php }else{?>
                                                                    <i class="fa-solid fa-minus"></i>
                                                                <?php }?>

                                                            </button>
                                                            <input type="text" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[isim]"  
                                                                class="form-control form-control-lg grup-tek-fiyat-isim"  
                                                                value="<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? $veri['isim'] :''; ?>" placeholder="Alt Ürün" required>
                                                        </div>
                                                    </div>  

                                                    <div class="form-floating col-md-2">
                                                        <?php 
                                                            $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                                                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                            $sth->execute();
                                                            $turler = $sth->fetchAll(PDO::FETCH_ASSOC);

                                                        ?>
                                                        <select class="form-select tur-select" id="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[tur]" data-urun-id="<?php echo intval($index)+1;?>" required>
                                                            <option selected disabled value="">Seçiniz</option>
                                                            <?php foreach ($turler as $turItem) { ?>
                                                                <option value="<?php echo $turItem['id']; ?>" 
                                                                    <?php echo (isset($veri['tur']) && $veri['tur'] == $turItem['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($turItem['tur']); ?>
                                                                </option>
                                                            <?php }?>
                                                        </select>
                                                        <label for="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>" class="form-label">Türü</label>
                                                    </div>

                                                    <div class="form-floating col-md-2">
                                                        <input type="number" class="form-control grup-tek-fiyat-miktar" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[miktar]" 
                                                            value="<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? $veri['miktar'] : ''; ?>">
                                                        <label class="form-label">Miktar</label>
                                                    </div>

                                                    <div class="form-floating col-md-2">
                                                        <select class="form-select grup-tek-fiyat-birim-id"  name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[birim_id]" required>
                                                            <option <?php echo $siparis['tip_id'] != GRUP_URUN_TEK_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                                            <?php foreach ($birimler as $birim) { ?>
                                                                <option value="<?php echo $birim['id']; ?>" 
                                                                    <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $veri['birim_id'] == $birim['id'] ? 'selected':''; ?>
                                                                >
                                                                    <?php echo $birim['ad']; ?>
                                                                </option>
                                                            <?php }?>
                                                        </select>
                                                        <label for="birim_id" class="form-label">Birim</label>
                                                    </div> 

                                                    <div class="form-floating col-md-2">
                                                        <select class="form-select grup-tek-fiyat-numune" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[numune]" required>
                                                            <option <?php echo $siparis['tip_id'] != GRUP_URUN_TEK_FIYAT ? 'selected' :'';?> disabled value="">Seçiniz</option>
                                                            <option  value="0" <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $veri['numune'] == 0 ? 'selected':''; ?>>Yok</option>
                                                            <option  value="1" <?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && $veri['numune'] == 1 ? 'selected':''; ?>>Var</option>
                                                        </select> 
                                                        <label class="form-label">Numune</label>   
                                                    </div>
                                                     
                                                    <!--<div class="form-floating col-md-2">
                                                        <input type="file" class="form-control grup-tek-fiyat-dosya" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[]" multiple >
                                                        <label class="form-label">Dosya yükle</label>
                                                    </div>-->

                                                    <div class="col-md-2">
                                                        <label for="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                                        <input type="file" class="form-control" id="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[]" multiple />                   
                                                    </div>

                                                    <div class="form-floating col-md-2">
                                                        <textarea class="form-control grup-tek-fiyat-aciklama" 
                                                            name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[aciklama]"><?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? $veri['aciklama'] : ''; ?></textarea>
                                                        <label for="aciklama" class="form-label">Açıklama</label>
                                                    </div>

                                                    <?php 
                                                    foreach ($siparis_formlar as $key => $siparis_form) { ?>
                                                    <?php $form = isset($veri['form']) ? $veri['form'] : []; ?>
                                                    <div class="form-floating col-md-2 degerler_<?php echo $index;?>" 
                                                        style="display:<?php echo !in_array($siparis_form['id'], $siparis_formlar_idler[$index] ?? []) ? 'none':'';?>"
                                                        data-deger="<?php echo $siparis_form['deger']; ?>">
                                                        <?php
                                                        if (!empty($siparis_form['options'])) { 
                                                            $options_array = json_decode($siparis_form['options'], true); 
                                                            if (is_array($options_array)) { 
                                                                ?>
                                                                <select class="form-select" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[form][<?php echo $siparis_form['deger']; ?>]">
                                                                    <option value="">
                                                                        <?php echo $siparis_form['deger']; ?> Seçiniz
                                                                    </option>
                                                                    <?php 
                                                                    foreach ($options_array as $option) { 
                                                                        $selectedO = isset($veri['form'][$siparis_form['deger']]) && $veri['form'][$siparis_form['deger']] == $option ? 'selected' : null;
                                                                        ?>
                                                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $selectedO; ?>><?php echo htmlspecialchars($option); ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            <?php } ?>
                                                        <?php } else { ?>
                                                            <input type="text" class="form-control" name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[form][<?php echo $siparis_form['deger']; ?>]" 
                                                                value="<?php echo isset($veri['form'][$siparis_form['deger']]) ? $veri['form'][$siparis_form['deger']] : null?>" />
                                                        <?php } ?>
                                                        <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                                    </div>
                                                    <?php } ?>

                                                    <?php 
                                                    /*foreach ($siparis_formlar as $key => $siparis_form) { ?>
                                                        <?php $form = isset($veri['form']) ? $veri['form'] : []; ?>
                                                        <div class="form-floating col-md-2 degerler" 
                                                            style="display:<?php echo !in_array($siparis_form['id'], $siparis_formlar_idler[$index]) ? 'none':'';?>"
                                                            data-deger="<?php echo $siparis_form['deger']; ?>"
                                                            >
                                                            <input type="text" class="form-control grup-tek-fiyat-form-deger" 
                                                                name="grup_tek_fiyat_alt_urun_<?php echo intval($index)+1;?>[form][<?php echo $siparis_form['deger']; ?>]" 
                                                                value="<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT && isset($form[$siparis_form['deger']]) ? $form[$siparis_form['deger']] : '';?>"    
                                                            >
                                                            <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                                        </div>
                                                    <?php } */?>

                                                    <?php if($siparis['tip_id'] == GRUP_URUN_TEK_FIYAT){ ?>    
                                                        <div class="grup-tek-fiyat-dosyalar">
                                                            <?php $resim_varmi = false; ?>
                                                            <?php foreach ($siparis_dosyalar as  $siparis_dosya) { ?>
                                                                <?php if($siparis_dosya['alt_urun_index'] == $index){?> 
                                                                    <?php $resim_varmi = true;?>
                                                                    <span style="position:relative;display:inline-block">
                                                                        <button type="button" class="btn btn-danger btn-sm resim-sil" 
                                                                            data-resim-id="<?php echo $siparis_dosya['id']; ?>" 
                                                                            style="position:absolute;right:4px;top:4px"
                                                                            data-resim-ad="<?php echo $siparis_dosya['ad'];?>"
                                                                        >
                                                                            <i class="fa-solid fa-trash-can"></i>
                                                                        </button>
                                                                        <?php 
                                                                            $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                                                        ?>
                                                                        <?php if($uzanti == 'pdf'){ ?>
                                                                            <a 
                                                                                class="pdf-modal-goster"
                                                                                data-href="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" 
                                                                                href="javascript:;"
                                                                            >
                                                                                <img src="dosyalar/pdf.png" 
                                                                                    class="rounded img-thumbnai border border-secondary-subtle object-fit-fill" 
                                                                                    alt="<?php echo $veri['isim']; ?>" 
                                                                                    style="width:100px;height:100px;"
                                                                                    
                                                                                > 
                                                                            </a>
                                                                        <?php }else{?>
                                                                            <a class="example-image-link-grup-tek-fiyat-<?php echo $index; ?>" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                                                data-lightbox="example-set-grup-tek-fiyat-<?php echo $index; ?>" data-title="<?php echo $siparis['tip_id'] == GRUP_URUN_TEK_FIYAT ? $veri['isim'] :''; ?> Resim(ler)">
                                                                                <img src="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" 
                                                                                    class="rounded img-thumbnai border border-secondary-subtle object-fit-fill" 
                                                                                    alt="<?php echo $veri['isim']; ?>" 
                                                                                    style="width:100px;height:100px;"
                                                                                >   
                                                                            </a> 
                                                                        <?php } ?>
                                                                    </span>
                                                                <?php } ?>
                                                            <?php }?>
                                                            <?php if(!$resim_varmi){ ?>
                                                                <div class="text-danger fw-bold border border-danger rounded p-3">
                                                                    <i class="fa-solid fa-exclamation"></i> Dosya Yok
                                                                </div>
                                                            <?php }?>
                                                        </div>
                                                    <?php }?>
                                                </div>
                                                <hr>  
                                                <?php 
                                                    if($siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT) break; 
                                                ?>
                                            <?php }?>      
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="card bg-light mt-0 ml-3" id="grup-ayri-fiyat" style="display:<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? '':'none';?>;">
                            <div class="card-body row g-3">

                                <div class="form-floating col-md-4">
                                    <input type="number" class="form-control" id="grup-ayri-fiyat-adet" name="grup_ayri_fiyat_adet"  min="0" required 
                                        value="<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? $siparis['adet'] : '';?>">
                                    <label for="adet" class="form-label" id="grup-ayri-fiyat-adet">Miktar</label>
                                </div>

                                <div class="form-floating col-md-4">
                                    <select class="form-select" id="grup-ayri-fiyat-birim-id" name="grup_ayri_fiyat_birim_id" required>
                                        <option <?php echo $siparis['tip_id'] != GRUP_URUN_AYRI_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                        <?php foreach ($birimler as $birim) { ?>
                                            <option value="<?php echo $birim['id']; ?>"
                                                <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $siparis['birim_id'] == $birim['id'] ? 'selected':'';?>
                                            >
                                                <?php echo $birim['ad']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="grup-ayri-fiyat-birim-id" class="form-label">Birim</label>
                                </div>   
                                
                                <div class="form-floating col-md-4">
                                    <select class="form-select" id="grup_ayri_fiyat_para_cinsi" name="grup_ayri_fiyat_para_cinsi" required>
                                        <option <?php echo $siparis['tip_id'] != GRUP_URUN_AYRI_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                        <option value="TL"      <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $siparis['para_cinsi'] == 'TL' ? 'selected':'';?>>TL</option>
                                        <option value="DOLLAR"  <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $siparis['para_cinsi'] == 'DOLLAR' ? 'selected':'';?>>DOLLAR</option>
                                        <option value="EURO"    <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $siparis['para_cinsi'] == 'EURO' ? 'selected':'';?>>EURO</option>
                                        <option value="POUND"   <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $siparis['para_cinsi'] == 'POUND' ? 'selected':'';?>>POUND</option>
                                    </select>
                                    <label for="grup_ayri_fiyat_para_cinsi" class="form-label">Para Cinsi</label>
                                </div>

                                <div class="card bg-body-secondary">
                                    <div class="card-body">
                                        <div id="grup-ayri-fiyat-alt-urunler">
                                            <?php //print_r($veriler); ?>
                                            <?php foreach ($veriler as $index => $veri) { ?>
                                                <div class="alt-urun row g-3">
                                                    <div class="col-md-2">
                                                        <div class="input-group fw-bold">
                                                            <button class="btn <?php echo $index == 0 ? 'btn-success alt-urun-button-ekle':'btn-danger alt-urun-button-cikar';?>"  type="button"
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom"
                                                                data-bs-custom-class="custom-tooltip"
                                                                data-bs-title="<?php echo $index == 0 ? 'Alt Ürün Ekle':'Alt Ürün Çıkar';?>"
                                                                data-alt-urun-id="1"
                                                            >
                                                                <?php if($index == 0){?>
                                                                    <i class="fa-solid fa-plus"></i>
                                                                <?php }else{?>
                                                                    <i class="fa-solid fa-minus"></i>
                                                                <?php }?>
                                                            </button>
                                                            <input type="text" name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[isim]"  
                                                                class="form-control form-control-lg grup-ayri-fiyat-isim"  
                                                                placeholder="Alt Ürün" required 
                                                                value="<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? $veri['isim'] : ''; ?>"
                                                            >
                                                        </div>
                                                    </div>  

                                                    <div class="form-floating col-md-2">
                                                        <?php 
                                                            $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                                                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                                            $sth->execute();
                                                            $turler = $sth->fetchAll(PDO::FETCH_ASSOC);

                                                        ?>
                                                        <select class="form-select tur-select" id="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>" name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[tur]" data-urun-id="<?php echo intval($index)+1;?>" required>
                                                            <option selected disabled value="">Seçiniz</option>
                                                            <?php foreach ($turler as $turItem) { ?>
                                                                <option value="<?php echo $turItem['id']; ?>" 
                                                                    <?php echo (isset($veri['tur']) && $veri['tur'] == $turItem['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($turItem['tur']); ?>
                                                                </option>
                                                            <?php }?>
                                                        </select>
                                                        <label for="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>" class="form-label">Türü</label>
                                                    </div>

                                                    <div class="form-floating col-md-2">
                                                        <input type="number" class="form-control grup-ayri-fiyat-miktar" 
                                                            name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[miktar]" value="<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? $veri['miktar'] :''; ?>">
                                                        <label class="form-label">Miktar</label>
                                                    </div>

                                                    <div class="form-floating col-md-2">
                                                        <select class="form-select grup-ayri-fiyat-birim-id" name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[birim_id]" required>
                                                            <option <?php echo $siparis['tip_id'] != GRUP_URUN_AYRI_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                                            <?php foreach ($birimler as $birim) { ?>
                                                                <option value="<?php echo $birim['id']; ?>"
                                                                    <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $veri['birim_id'] == $birim['id'] ? 'selected':''; ?>
                                                                >
                                                                    <?php echo $birim['ad']; ?>
                                                                </option>
                                                            <?php }?>
                                                        </select>
                                                        <label class="form-label">Birim</label>
                                                    </div> 
                                                    
                                                    <div class="form-floating col-md-2">
                                                        <input type="number" class="form-control grup-ayri-fiyat-birim-fiyat" 
                                                            name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[birim_fiyat]" step="0.001" min="0" required 
                                                            value="<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? $veri['birim_fiyat'] :''; ?>">
                                                        <label class="form-label">Birim Fiyat</label>
                                                    </div>

                                                    <div class="form-floating col-md-2">
                                                        <select class="form-select grup-ayri-fiyat-kdv"  name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[kdv]" required>
                                                            <option <?php echo $siparis['tip_id'] != GRUP_URUN_AYRI_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                                            <option value="0"   <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $veri['kdv'] == 0 ? 'selected':'';?>>0</option>
                                                            <option value="10"  <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $veri['kdv'] == 10 ? 'selected':'';?>>10</option>
                                                            <option value="20"  <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $veri['kdv'] == 20 ? 'selected':'';?>>20</option>
                                                        </select>
                                                        <label  class="form-label">Kdv</label>
                                                    </div>

                                                    <div class="form-floating col-md-2">
                                                        <input type="text" class="form-control grup-ayri-fiyat-toplam-fiyat border-success"   
                                                            readonly value="<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? $veri['miktar']*$veri['birim_fiyat'] : '';?>">
                                                        <label  class="form-label fw-bold">Toplam Fiyat</label>
                                                    </div>

                                                    <div class="form-floating col-md-3">
                                                        <select class="form-select grup-ayri-fiyat-numune" required name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[numune]">
                                                            <option <?php echo $siparis['tip_id'] != GRUP_URUN_AYRI_FIYAT ? 'selected':'';?> disabled value="">Seçiniz</option>
                                                            <option  value="0"  <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $veri['numune'] == 0 ? 'selected':'';?>>Yok</option>
                                                            <option  value="1"  <?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && $veri['numune'] == 1 ? 'selected':'';?>>Var</option>
                                                        </select> 
                                                        <label class="form-label">Numune</label>   
                                                    </div>
                                                     
                                                    <!--<div class="form-floating col-md-3">
                                                        <input type="file" class="form-control grup-ayri-fiyat-dosya" multiple name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[]" >
                                                        <label class="form-label">Dosya yükle</label>
                                                    </div>-->
                                                    
                                                    <div class="col-md-2">
                                                        <label for="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                                        <input type="file" class="form-control" id="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>" name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[]" multiple />                   
                                                    </div>

                                                    <div class="form-floating col-md-6">
                                                        <textarea class="form-control grup-ayri-fiyat-aciklama"  name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[aciklama]"><?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? $veri['aciklama'] : '';?></textarea>
                                                        <label  class="form-label">Açıklama</label>
                                                    </div>
                                                    
                                                    <?php 
                                                    if ($siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT) {
                                                        
                                                    foreach ($siparis_formlar as $key => $siparis_form) { ?>
                                                        <?php $form = isset($veri['form']) ? $veri['form'] : []; ?>
                                                        <div class="form-floating col-md-2 degerler" 
                                                            style="display:<?php echo !in_array($siparis_form['id'], $siparis_formlar_idler[$index])? 'none':'';?>"
                                                            data-deger="<?php echo $siparis_form['deger']; ?>"    
                                                        >
                                                            <input type="text" class="form-control grup-ayri-fiyat-form" 
                                                                name="grup_ayri_fiyat_alt_urun_<?php echo intval($index)+1;?>[form][<?php echo $siparis_form['deger']; ?>]"
                                                                value="<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT  && isset($form[$siparis_form['deger']]) ? $form[$siparis_form['deger']] : '';?>"
                                                            >
                                                            <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                                        </div>
                                                    <?php }
                                                    }
                                                    ?>

                                                    <?php if($siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ){ ?>
                                                        <div class="grup-ayri-fiyat-dosyalar">
                                                            <?php $resim_varmi = false;?>
                                                            <?php foreach ($siparis_dosyalar as  $siparis_dosya) { ?>
                                                                <?php if($siparis_dosya['alt_urun_index'] == $index){?> 
                                                                    <?php $resim_varmi = true; ?>
                                                                    <span style="position:relative;display:inline-block">
                                                                        <button type="button" class="btn btn-danger btn-sm resim-sil" 
                                                                            data-resim-id="<?php echo $siparis_dosya['id']; ?>" 
                                                                            style="position:absolute;right:4px;top:4px"
                                                                            data-resim-ad="<?php echo $siparis_dosya['ad'];?>"
                                                                        >
                                                                            <i class="fa-solid fa-trash-can"></i>
                                                                        </button>
                                                                        <?php 
                                                                            $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                                                        ?>

                                                                        <?php if($uzanti == 'pdf'){ ?>
                                                                            <a class="pdf-modal-goster" 
                                                                                data-href="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" 
                                                                                href="javascript:;"
                                                                            >
                                                                                <img src="dosyalar/pdf.png" 
                                                                                    class="rounded img-thumbnai border border-secondary-subtle object-fit-fill" 
                                                                                    alt="<?php echo $veri['isim']; ?>" 
                                                                                    style="width:100px;height:100px;"
                                                                                    
                                                                                > 
                                                                            </a>
                                                                        <?php }else{?>
                                                                            <a class="example-image-link-grup-yari-fiyat-<?php echo $index; ?>" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                                                data-lightbox="example-set-grup-ayri-fiyat-<?php echo $index; ?>" data-title="<?php echo $siparis['tip_id'] == GRUP_URUN_AYRI_FIYAT ? $veri['isim'] : ''; ?> Resimleri">
                                                                                <img src="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>" class="rounded img-thumbnai border" alt="" 
                                                                                    style="width:100px;height:100px;object-fit:content;">   
                                                                            </a> 
                                                                        <?php } ?>
                                                                    </span>
                                                                <?php } ?>
                                                            <?php }?>
                                                            <?php if(!$resim_varmi){?>
                                                                <div class="text-danger fw-bold border border-danger rounded p-3" role="alert">
                                                                    <i class="fa-solid fa-exclamation"></i> Dosya Yok
                                                                </div>
                                                            <?php }?>
                                                        </div>
                                                    <?php } ?>

                                                    <hr>
                                                </div>

                                                <?php 
                                                    if($siparis['tip_id'] == GRUP_URUN_TEK_FIYAT) break;
                                                ?>
                                            <?php }?>   
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        
                        <div class="card bg-light mt-0">
                            <div class="card-body row g-3">
                                <div class="form-floating col-md-12">
                                    <?php 
                                    if ( intval($siparis['teslimat_adresi']) > 0 ) {
                                        $stmt_adres_bul = $conn->prepare('SELECT * FROM musteri_adresleri WHERE id = ?');
                                        $stmt_adres_bul->execute([
                                            $siparis['teslimat_adresi']
                                        ]);
                                        $row_adres_bul = $stmt_adres_bul->fetch();
                                    } else {
                                        $stmt_adres_bul = $conn->prepare('SELECT * FROM musteri_adresleri WHERE ulke_id = ? && sehir_id =? && ilce_id = ?');
                                        $stmt_adres_bul->execute([
                                            $siparis['ulke_id'],
                                            $siparis['sehir_id'],
                                            $siparis['ilce_id']
                                        ]);
                                        $row_adres_bul = $stmt_adres_bul->fetch();
                                    }

                                    if ( isset($row_adres_bul) && $row_adres_bul != false ) {
                                        $tespit_edilen_adres_id = $row_adres_bul['id'];
                                        $tespit_edilen_adres = $row_adres_bul['baslik'].' - '.$row_adres_bul['adres'];
                                    } else {
                                        $tespit_edilen_adres_id = 0;
                                        $tespit_edilen_adres = $siparis['teslimat_adresi'];
                                    }
                                    ?>
                                    <input type="hidden" class="form-control" id="teslimat_adresi" name="teslimat_adresi" value="<?php echo $tespit_edilen_adres_id; ?>" required />
                                    <input type="text" class="form-control" name="teslimat_adresi_text" value="<?php echo $tespit_edilen_adres; ?>" readyonly required />
                                    <label for="teslimat_adresi" class="form-label">Teslimat Adresi</label>
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
                                            <option value="<?php echo $ulke['id']; ?>" 
                                                <?php echo $ulke['id'] == $siparis['ulke_id'] ? 'selected':''; ?>
                                            >
                                                <?php echo $ulke['baslik']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="ulke_id" class="form-label">Ülke</label>
                                </div>

                                <?php 
                                    $sth = $conn->prepare('SELECT id, baslik FROM sehirler WHERE `ulke_id` = :ulke_id');
                                    $sth->bindParam('ulke_id', $siparis['ulke_id']);
                                    $sth->execute();
                                    $sehirler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <div class="form-floating col-md-4">
                                    <select class="form-select form-select-lg js-example-basic-single" id="sehir_id" name="sehir_id" required>
                                        <option  disabled>Şehir Seçiniz</option>
                                        <?php foreach ($sehirler as  $sehir) { ?>
                                            <option value="<?php echo $sehir['id']; ?>" <?php echo $sehir['id'] == $siparis['sehir_id'] ? 'selected':'';  ?>><?php echo $sehir['baslik']; ?></option>
                                        <?php }?>
                                    </select>
                                    <label for="sehir_id" class="form-label">Şehir</label>
                                </div>
                                <?php 
                                    $sth = $conn->prepare('SELECT id, baslik FROM ilceler WHERE `sehir_id` = :sehir_id');
                                    $sth->bindParam('sehir_id', $siparis['sehir_id']);
                                    $sth->execute();
                                    $ilceler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <div class="form-floating col-md-4">
                                    <select class="form-select form-select-lg js-example-basic-single" id="ilce_id" name="ilce_id" required>
                                        <option selected disabled>İlçe Seçiniz</option>
                                        <?php foreach ($ilceler as  $ilce) { ?>
                                            <option value="<?php echo $ilce['id']; ?>" <?php echo $ilce['id'] == $siparis['ilce_id'] ? 'selected':'';  ?>><?php echo $ilce['baslik']; ?></option>
                                        <?php }?>
                                    </select>
                                    <label for="ilce_id" class="form-label">İlçe</label>
                                </div>

                                <div class="form-floating col-md-4">
                                    <input type="date" class="form-control" id="termin" name="termin" value="<?php echo $siparis['termin']; ?>" required />
                                    <label for="termin" class="form-label">Termin Tarihi</label>
                                </div>

                                <div class="form-floating col-md-4">
                                    <input type="date" class="form-control" id="uretim" name="uretim" value="<?php echo $siparis['uretim']; ?>"  required />
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
                                            <option value="<?php echo $personel['id']; ?>" 
                                                <?php echo $personel['id'] == $siparis['musteri_temsilcisi_id'] ? 'selected': ''; ?>
                                            >
                                                <?php echo $personel['ad'].' '.$personel['soyad']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <label for="musteri_temsilcisi_id" class="form-label">Musteri Temsilcisi</label>
                                </div>

                                <div class="form-floating col-md-4">
                                    <input type="date" class="form-control" id="vade" value="<?php echo $siparis['vade']; ?>" name="vade" required />
                                    <label for="vade" class="form-label">Vade</label>
                                </div>

                                <?php 
                                    $sth = $conn->prepare('SELECT * FROM odeme_tipleri');
                                    $sth->execute();
                                    $odeme_tipleri = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <div class="form-floating col-md-4">
                                    <select class="form-select" id="odeme_sekli_id" name="odeme_sekli_id" required>
                                        <option  disabled value="">Seçiniz</option>
                                        <?php foreach ($odeme_tipleri as $odeme_tipi) { ?>
                                            <option value="<?php echo $odeme_tipi['id'];?>"
                                                <?php echo $odeme_tipi['id'] == $siparis['odeme_sekli_id'] ? 'selected' :''; ?>
                                            >
                                                <?php echo $odeme_tipi['odeme_sekli']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="odeme_sekli_id" class="form-label">Ödeme Şekli</label>
                                </div>

                                <div class="form-floating col-md-6">
                                    <textarea class="form-control" id="nakliye" name="nakliye"  required><?php echo $siparis['nakliye']; ?></textarea>
                                    <label for="nakliye" class="form-label">Nakliye</label>
                                </div>

                                <div class="form-floating col-md-6">
                                    <textarea class="form-control" id="paketleme" name="paketleme" ><?php echo $siparis['paketleme']; ?></textarea>
                                    <label for="paketleme" class="form-label">Paketleme</label>
                                </div>
                                
                                <?php /* Durum kolonu artık yok, islem kolonu kullanılıyor
                                <div class="col-md-6">
                                    <label class="form-label" for="durum">Sipariş Durumu</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="durum" name="durum">
                                        <span class="ms-1" id="durumText">Aktif</span>
                                    </div>
                                </div>
                                */ ?>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button class="btn btn-success" type="submit" name="siparis_onay">
                                    <i class="fa-regular fa-circle-check"></i> ONAY
                                </button>
                                <a href="/index.php?url=siparisler_onay" class="btn btn-danger">
                                    <i class="fa-regular fa-rectangle-xmark"></i> İPTAL
                                </a>
                            </div>
                        </div>
                    </form>
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

        <?php 
            include "include/uyari_session_oldur.php";
        ?>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            const TEKLI_URUN        = 1;
            const GRUP_TEK_FIYAT    = 2;
            const GRUP_AYRI_FIYAT   = 3;
            let altUrunSayisi       = <?php echo $altUrunSayisi; ?>;
            const TIP_ID            = <?php echo $siparis['tip_id']; ?>

            if(TIP_ID == TEKLI_URUN){
                $("#tek-fiyat").show();
                $("#grup-tek-fiyat").hide();
                $("#grup-ayri-fiyat").hide();
                $("#grup-tek-fiyat *, #grup-ayri-fiyat *").prop('disabled', true);
                $("#tek-fiyat *").prop('disabled', false);
                $("#alt-urun-sayisi").val(0);
            }
            else if(TIP_ID == GRUP_TEK_FIYAT){
                $("#tur_select").hide();
                $("#grup-tek-fiyat").show();
                $("#grup-ayri-fiyat").hide();
                $("#tek-fiyat").hide();
                $("#tek-fiyat *, #grup-ayri-fiyat *").prop('disabled', true);
                $("#grup-tek-fiyat *").prop('disabled', false);
            }
            else if(TIP_ID == GRUP_AYRI_FIYAT){
                $("#tur_select").hide();
                $("#grup-tek-fiyat").hide();
                $("#grup-ayri-fiyat").show();
                $("#tek-fiyat").hide();
                $("#tek-fiyat *, #grup-tek-fiyat *").prop('disabled', true);
                $("#grup-ayri-fiyat *").prop('disabled', false);
            }

            $(function(){

                //PDF Modalda Göster
                $(document).on('click', '.pdf-modal-goster', function(){
                    const pdfURL = $(this).data('href');
                    $("#arsiv-pdf-modal-body").html(`
                        <div class="ratio ratio-16x9">
                            <iframe src="${pdfURL}"  allowfullscreen></iframe>
                        </div>
                    `);
                    $("#arsiv-pdf-modal").modal('show');
                });

                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });

                $(document).on('click', '.resim-sil', function(){
                    if(confirm("Dosyayı Silmek İstediğinize Emin Misiniz?")){
                        const id = $(this).data('resim-id');
                        const resimAd = $(this).data('resim-ad');
                        const _this = $(this);
                        $.ajax({
                            url         : "/index.php?url=siparis_db_islem",
                            dataType    : "JSON",
                            type        : "POST",
                            data        : {islem:'siparis_dosya_sil',id, resim_ad:resimAd},
                            success     : function(veri){
                                if(veri.durum){
                                    _this.closest('span').remove();
                                }
                            }
                        });
                    }
                    
                });

                //grup ayrı fiyat satır toplam fiyat hesaplama
                //.grup-ayri-fiyat-birim-fiyat .grup-ayri-fiyat-miktar
                $(document).on('keyup', '.grup-ayri-fiyat-miktar', function(){
                    const miktar = $(this).val();
                    const birimFiyat = $(this).closest('.alt-urun').find('.grup-ayri-fiyat-birim-fiyat').val();
                    $(this).closest('.alt-urun').find('.grup-ayri-fiyat-toplam-fiyat').val(miktar*birimFiyat);
                });

                $(document).on('keyup', '.grup-ayri-fiyat-birim-fiyat', function(){
                    const birimFiyat = $(this).val();
                    const miktar = $(this).closest('.alt-urun').find('.grup-ayri-fiyat-miktar').val();
                    $(this).closest('.alt-urun').find('.grup-ayri-fiyat-toplam-fiyat').val(miktar*birimFiyat);
                });

                //grup ayrı fiyat alt ürün ekle
                $("#grup-ayri-fiyat-alt-urunler .alt-urun-button-ekle").click(function(){
                    altUrunSayisi++;
                    const altUrunHTML = `
                        <div class="alt-urun row g-3">
                            <div class="col-md-2">
                                <div class="input-group fw-bold">
                                    <button class="btn btn-danger alt-urun-button-cikar"  type="button"
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="bottom"
                                        data-bs-custom-class="custom-tooltip"
                                        data-bs-title="Alt Ürün Ekle"
                                        data-alt-urun-id="1"
                                    >
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="text" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[isim]"  class="form-control form-control-lg grup-ayri-fiyat-isim"  placeholder="Alt Ürün" required>
                                </div>
                            </div>  

                            <div class="form-floating col-md-2">
                                <input type="number" class="form-control grup-ayri-fiyat-miktar" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[miktar]"/>
                                <label class="form-label">Miktar</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <select class="form-select grup-ayri-fiyat-birim-id" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[birim_id]" required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <?php foreach ($birimler as $birim) { ?>
                                        <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                    <?php }?>
                                </select>
                                <label  class="form-label">Birim</label>
                            </div> 
                            
                            <div class="form-floating col-md-2">
                                <input type="number" class="form-control grup-ayri-fiyat-birim-fiyat" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[birim_fiyat]" step="0.001" min="0" required />
                                <label  class="form-label">Birim Fiyat</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <select class="form-select grup-ayri-fiyat-birim-kdv" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[kdv]" required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <option value="0">0</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                </select>
                                <label class="form-label">Kdv</label>
                            </div>

                            <div class="form-floating col-md-2">
                                <input type="text" class="form-control grup-ayri-fiyat-toplam-fiyat border-success"    readonly />
                                <label  class="form-label fw-bold">Toplam Fiyat</label>
                            </div>

                            <div class="form-floating col-md-3">
                                <select class="form-select grup-ayri-fiyat-numune" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[numune]" required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <option  value="0">Yok</option>
                                    <option  value="1">Var</option>
                                </select> 
                                <label class="form-label">Numune</label>   
                            </div>
                            

                            <!--<div class="form-floating col-md-3">
                                <input type="file" class="form-control grup-ayri-fiyat-dosya" multiple name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[]"/>
                                <label class="form-label">Dosya yükle</label>
                            </div>-->

                            <div class="col-md-2">
                                <label for="grup_ayri_fiyat_alt_urun_${altUrunSayisi}" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                <input type="file" class="form-control" id="grup_ayri_fiyat_alt_urun_${altUrunSayisi}" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[]" multiple />                   
                            </div>

                            <div class="form-floating col-md-6">
                                <textarea class="form-control grup-ayri-fiyat-aciklama" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[aciklama]"></textarea>
                                <label  class="form-label">Açıklama</label>
                            </div>

                            <?php foreach ($siparis_formlar as $key => $siparis_form) { ?>
                                <div class="form-floating col-md-2 degerler" style="display:none" data-deger="<?php echo $siparis_form['deger']; ?>">
                                    <input type="text" class="form-control grup-ayri-fiyat-form" name="grup_ayri_fiyat_alt_urun_${altUrunSayisi}[form][<?php echo $siparis_form['deger']; ?>]">
                                    <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                </div>
                            <?php }?>

                            <hr>
                        </div>
                    `;
                    $("#alt-urun-sayisi").val(altUrunSayisi);  
                    $("#grup-ayri-fiyat-alt-urunler").append(altUrunHTML);
                    turIdGoreSiparisFormuGetirme($("#tur_id").val());
                });

                //grup ayrı fiyat alt ürün çıkar
                $(document).on('click','#grup-ayri-fiyat-alt-urunler .alt-urun-button-cikar', function(){
                    $(this).closest('.alt-urun').remove();
                    altUrunSayisi--;
                    $("#alt-urun-sayisi").val(altUrunSayisi);
                    $("#grup-ayri-fiyat-alt-urunler .alt-urun").each((index, element) => {
                        $(element).find('input.grup-ayri-fiyat-isim').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[isim]`);
                        $(element).find('input.grup-ayri-fiyat-miktar').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[miktar]`);
                        $(element).find('select.grup-ayri-fiyat-birim-id').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[birim_id]`);
                        $(element).find('input.grup-ayri-fiyat-birim-fiyat').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[birim_fiyat]`);
                        $(element).find('select.grup-ayri-fiyat-birim-kdv').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[kdv]`);
                        $(element).find('select.grup-ayri-fiyat-numune').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[numune]`);
                        $(element).find('select.grup-ayri-fiyat-dosya').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[]`);
                        $(element).find('textarea.grup-ayri-fiyat-aciklama').attr('name',`grup_ayri_fiyat_alt_urun_${index+1}[aciklama]`);
                        $(element).find('input.grup-ayri-fiyat-form-deger').attr('name',$(element).find('input.grup-ayri-fiyat-form-deger').attr('name').replace(/\d+/,index+1))
                    });
                    //grup_ayri_fiyat_alt_urun_1[isim]
                })

                //grup tek fiyat alt ürün çıkar
                $(document).on('click','#grup-tek-fiyat-alt-urunler .alt-urun-button-cikar', function(){
                    $(this).closest('.alt-urun').remove();
                    altUrunSayisi--;
                    $("#alt-urun-sayisi").val(altUrunSayisi);  
                    
                    $("#grup-tek-fiyat-alt-urunler .alt-urun").each((index, element) => {
                        $(element).find('input.grup-tek-fiyat-isim').attr('name',`grup_tek_fiyat_alt_urun_${index+1}[isim]`)
                        $(element).find('input.grup-tek-fiyat-miktar').attr('name',`grup_tek_fiyat_alt_urun_${index+1}[miktar]`)
                        $(element).find('select.grup-tek-fiyat-birim-id').attr('name',`grup_tek_fiyat_alt_urun_${index+1}[birim_id]`)
                        $(element).find('select.grup-tek-fiyat-numune').attr('name',`grup_tek_fiyat_alt_urun_${index+1}[numune]`)
                        $(element).find('input.grup-tek-fiyat-dosya').attr('name',`grup_tek_fiyat_alt_urun_${index+1}[]`)
                        $(element).find('textarea.grup-tek-fiyat-aciklama').attr('name',`grup_tek_fiyat_alt_urun_${index+1}[aciklama]`)
                        $(element).find('input.grup-tek-fiyat-form-deger').attr('name',$(element).find('input.grup-tek-fiyat-form-deger').attr('name').replace(/\d+/,index+1))
                    });
                })

                //grup tek fiyat alt ürün ekle
                $("#grup-tek-fiyat-alt-urunler .alt-urun-button-ekle").click(function(){
                    altUrunSayisi++;
                    let altUrunHTML = `
                        <div class="alt-urun row g-3 mt-2">
                            <div class="col-md-2">
                                <div class="input-group fw-bold">
                                    <button class="btn btn-danger alt-urun-button-cikar"  type="button"
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="bottom"
                                        data-bs-custom-class="custom-tooltip"
                                        data-bs-title="Alt Ürün Ekle"
                                        data-alt-urun-id="1"
                                    >
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="text" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[isim]"   class="form-control form-control-lg grup-tek-fiyat-isim"  placeholder="Alt Ürün" required>
                                </div>
                            </div> 
                            <div class="form-floating col-md-2">
                                <input type="number" class="form-control grup-tek-fiyat-miktar" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[miktar]" >
                                <label class="form-label">Miktar</label>
                            </div> 

                            <div class="form-floating col-md-2">
                                <select class="form-select grup-tek-fiyat-birim-id"  name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[birim_id]"  required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <?php foreach ($birimler as $birim) { ?>
                                        <option value="<?php echo $birim['id']; ?>"><?php echo $birim['ad']; ?></option>
                                    <?php }?>
                                </select>
                                <label class="form-label">Birim</label>
                            </div> 

                            <div class="form-floating col-md-2">
                                <select class="form-select grup-tek-fiyat-numune" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[numune]"  required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <option  value="0">Yok</option>
                                    <option  value="1">Var</option>
                                </select> 
                                <label class="form-label">Numune</label>   
                            </div>

                            <!--<div class="form-floating col-md-2">
                                <input type="file" class="form-control grup-tek-fiyat-dosya" multiple name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[]" />
                                <label class="form-label">Dosya yükle</label>
                            </div>-->
                            
                            <div class="col-md-2">
                                <label for="grup_tek_fiyat_alt_urun_${altUrunSayisi}" style="background-color:white" class="input-group-text">Dosya yükle</label>
                                <input type="file" class="form-control" id="grup_tek_fiyat_alt_urun_${altUrunSayisi}" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[]" multiple />                   
                            </div>

                            <div class="form-floating col-md-2">
                                <textarea class="form-control grup-tek-fiyat-aciklama" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[aciklama]" ></textarea>
                                <label for="aciklama" class="form-label">Açıklama</label>
                            </div>

                            <?php foreach ($siparis_formlar as $key => $siparis_form) { ?>
                                <div class="form-floating col-md-2 degerler" style="display:none" data-deger="<?php echo $siparis_form['deger']; ?>">
                                    <input type="text" class="form-control grup-tek-fiyat-form-deger" name="grup_tek_fiyat_alt_urun_${altUrunSayisi}[form][<?php echo $siparis_form['deger']; ?>]" />
                                    <label class="form-label"><?php echo $siparis_form['deger']; ?></label>
                                </div>
                            <?php }?>
                            <hr>
                        </div>
                    `;
                    $("#alt-urun-sayisi").val(altUrunSayisi);            
                    $("#grup-tek-fiyat-alt-urunler").append(altUrunHTML);
                    turIdGoreSiparisFormuGetirme($("#tur_id").val());
                });

                //adet ve birim fiyat değiştiğinde
                $("#tek-fiyat-adet, #tek-fiyat-birim-fiyat").keyup(function(){
                    const adet          = $("#tek-fiyat-adet").val();
                    const birim_fiyat   = $("#tek-fiyat-birim-fiyat").val();
                    const toplam_fiyat  = adet*birim_fiyat;
                    $("#tek-fiyat-toplam-fiyat").val(toplam_fiyat)
                });
                $("#grup-tek-fiyat-adet, #grup-tek-fiyat-birim-fiyat").keyup(function(){
                    const adet          = $("#grup-tek-fiyat-adet").val();
                    const birim_fiyat   = $("#grup-tek-fiyat-birim-fiyat").val();
                    const toplam_fiyat  = adet*birim_fiyat;
                    $("#grup-tek-fiyat-toplam-fiyat").val(toplam_fiyat)
                });

            

                //tip değiştirme
                $("#tip").change(function(){       
                    const tip = $(this).val();
                    if(tip == TEKLI_URUN){
                        $("#tur_select").show();
                        $("#tek-fiyat").show();
                        $("#grup-tek-fiyat").hide();
                        $("#grup-ayri-fiyat").hide();
                        $("#grup-tek-fiyat *, #grup-ayri-fiyat *").prop('disabled', true);
                        $("#tek-fiyat *").prop('disabled', false);
                        $("#alt-urun-sayisi").val(0);
                    }
                    else if(tip == GRUP_TEK_FIYAT){
                        $("#tur_select").hide();
                        $("#grup-tek-fiyat").show();
                        $("#grup-ayri-fiyat").hide();
                        $("#tek-fiyat").hide();
                        $("#tek-fiyat *, #grup-ayri-fiyat *").prop('disabled', true);
                        $("#grup-tek-fiyat *").prop('disabled', false);
                    }
                    else if(tip == GRUP_AYRI_FIYAT){
                        $("#tur_select").hide();
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
            
            // Sipariş durumu switch değiştiğinde yazıyı güncelle
            $('#durum').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#durumText').text('Aktif');
                } else {
                    $('#durumText').text('Pasif');
                }
            });
        </script>