<?php 
    require_once "include/oturum_kontrol.php";
    $personel_id = intval($_GET['id']);

    $sth = $conn->prepare('SELECT id, ad, soyad, adres, yetki_id, cep_numarasi, sabit_hat, email, sifre, 
        dogum_tarihi, ise_baslama, aciklama,durum FROM personeller WHERE id=:id AND firma_id = :firma_id');
    $sth->bindParam('id', $personel_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $personel = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($personel))
    {
        require_once "include/yetkisiz.php";
        exit;
    }
?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-user"></i>
                Personel Bilgi Güncelleme
            </h5>
            <div>
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
        <div class="card-body pt-0">
            <form class="row g-3 needs-validation" action="/index.php?url=personel_db_islem" method="POST" id="personel-guncelle-form">
                
                <input type="hidden" name="id" value="<?php echo $personel['id']; ?>">
                <div class="form-floating col-md-6">
                    <input type="text" name="ad" class="form-control" id="ad" value="<?php echo $personel['ad']; ?>" required>
                    <label for="ad" class="form-label">Adı</label>
                </div>
                <div class="form-floating col-md-6">
                    <input type="text" name="soyad" class="form-control" id="soyad" value="<?php echo $personel['soyad']; ?>" required>
                    <label for="soyad" class="form-label">Soyadı</label>
                </div>
                <div class="form-floating col-md-12">
                    <input type="text" name="adres" class="form-control" id="adres"  value="<?php echo $personel['adres']; ?>"required>
                    <label for="adres" class="form-label">Adresi</label>
                </div>
                
                
                <?php 
                    $sth = $conn->prepare('SELECT id, yetki FROM yetkiler');
                    $sth->execute();
                    $yetkiler = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="form-floating col-md-4">
                    <select class="form-select" name="yetki_id" id="yetki_id" value="<?php echo $personel['yetki_id']; ?>" required>
                        <?php foreach($yetkiler as $yetki){ ?>
                            <?php if($yetki['yetki'] != "Superadmin"){ ?>
                                <?php if($yetki['id'] != SUPER_ADMIN_YETKI_ID){ ?>
                                    <option value="<?php echo $yetki['id']; ?>" 
                                        <?php echo $yetki['id'] == $personel['yetki_id'] ? 'selected' : ''; ?>
                                    >
                                        <?php echo $yetki['yetki']; ?>
                                    </option>
                                <?php }else{?> 
                                    <option value="<?php echo $yetki['id']; ?>" selected> <?php echo $yetki['yetki']; ?> </option>
                                <?php } ?>
                            <?PHP } ?>
                        <?php } ?>
                    </select>
                    <label for="yetki_id" class="form-label">Görev</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="text" name="cep_numarasi" class="form-control" id="cep_numarasi"value="<?php echo $personel['cep_numarasi']; ?>" required>
                    <label for="cep_numarasi" class="form-label">Cep Telefonu</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="text" name="sabit_hat" class="form-control" id="sabit_hat" value="<?php echo $personel['sabit_hat']; ?>" required>
                    <label for="sabit_hat" class="form-label">Sabit Hat</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="email" name="email" class="form-control" id="email" value="<?php echo $personel['email']; ?>"required>
                    <label for="email" class="form-label">e-mail</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="text" name="sifre" class="form-control" id="sifre" >
                    <label for="sifre" class="form-label">Şifre</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="date" name="dogum_tarihi" class="form-control" id="dogum_tarihi" value="<?php echo $personel['dogum_tarihi']; ?>"required>
                    <label for="dogum_tarihi" class="form-label">Doğum Tarihi</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="date" name="ise_baslama" class="form-control" id="ise_baslama" value="<?php echo $personel['ise_baslama']; ?>"required>
                    <label for="ise_baslama" class="form-label">İşe Başlama</label>
                </div>

                <div class="form-floating col-md-4">
                    <textarea name="aciklama" class="form-control" id="aciklama" rows="100" required><?php echo $personel['aciklama']; ?></textarea>
                    <label for="aciklama" class="form-label">Açıklama</label>
                </div>

                <div class="form-floating col-md-4">
                    <select class="form-select" name="durum" id="durum" required>
                        <option value="aktif" <?php echo $personel['durum'] == 'aktif' ? 'selected':''?>>Aktif</option>
                        <option value="pasif" <?php echo $personel['durum'] == 'pasif' ? 'selected':''?>>Pasif</option>
                    </select>
                    <label for="durum" class="form-label">Durum</label>
                </div>

                <div class="row mt-3 mb-2">
                    <div class="col-md-5">
                        <?php 
                            $sth = $conn->prepare('SELECT id, departman FROM departmanlar WHERE firma_id = :firma_id ORDER BY `departman` ASC');
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);

                            $sql = "SELECT departmanlar.id, departmanlar.departman FROM `personel_departmanlar` JOIN departmanlar  ON departmanlar.id = personel_departmanlar.departman_id
                            WHERE personel_id = :personel_id AND firma_id = :firma_id";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('personel_id', $personel_id);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $personel_departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);

                            $personel_departman_idler = [];
                            foreach ($personel_departmanlar  as $personel_departman) {
                                $personel_departman_idler[] = $personel_departman['id'];
                            }

                        ?>
                        <!-- <label for="lstBox1" class="form-label">Departman</label>   
                        <select class="form-select" multiple="multiple"  id="lstBox1"  >
                            <option selected disabled value="">Seç...</option>
                            <?php foreach($departmanlar as $departman){ ?>
                                <?php if(!in_array($departman['id'], $personel_departman_idler)){?>
                                    <option value="<?php echo $departman['id']; ?>">
                                        <?php echo $departman['departman']; ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>  -->
                        <label for="departman_idler" class="form-label fw-bold">Departman</label>  
                        <select  class="form-select form-select-lg js-example-basic-single" id="departman_idler" name="departman_idler[]" multiple>
                            <?php foreach ($departmanlar as $key => $departman) { ?>
                                <option value="<?php echo $departman['id']; ?>"
                                    <?php echo in_array($departman['id'], $personel_departman_idler ) ? 'selected':'';?>
                                >
                                    <?php echo $departman['departman']; ?>
                                </option>
                            <?php }?>
                        </select>         
                    </div>
                    <!-- <div class="col-md-2 text-center">
                        <input type='button' id='btnRight' value ='  >  ' class="btn btn-outline-primary"/>
                        <br/>
                        <br>
                        <input type='button' id='btnLeft' value ='  <  ' class="btn btn-outline-success"/>
                    </div>
                    <div class="col-md-5">
                        <label for="lstBox2" class="form-label">Seçili Departman</label>  
                        <select class="form-select" multiple="multiple"  name="departman_idler[]" id="lstBox2" >
                            <?php foreach($personel_departmanlar as $departman){ ?>
                                <option value="<?php echo $departman['id']; ?>">
                                    <?php echo $departman['departman']; ?>
                                </option>
                            <?php } ?>
                        </select> 
                    </div>        -->
                </div>


                <div class="row mt-2">
                    <div class="col-md-12">
                        <button class="btn btn-warning" type="submit" name="personel_guncelle" id="personel-guncelle-button">
                            <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                        </button>
                        <a href="/index.php?url=personel" class="btn btn-danger" type="submit">
                            <i class="fa-regular fa-rectangle-xmark"></i> İPTAL
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
    include_once "include/uyari_session_oldur.php";
?>

<script>
    $(function(){

        $("#personel-guncelle-form").submit(function(){
            $("#personel-guncelle-button").addClass('disabled');
            return true;
        });

        $('#lstBox2 option').each((index, elem) => {
            $(elem).prop('selected', true);
        })
        $('#btnRight').click(function(e) {
            var selectedOpts = $('#lstBox1 option:selected');
            if (selectedOpts.length == 0) {
                alert("Nothing to move.");
                e.preventDefault();
            }

            $('#lstBox2').append($(selectedOpts).clone());
            $(selectedOpts).remove();
            $('#lstBox2 option').each((index, elem) => {
                $(elem).prop('selected', true);
            })
            if( $('#lstBox2 option').length == 0){
                $("#lstBox2").addClass('is-invalid');
            }else {
                $("#lstBox2").removeClass('is-invalid');

            }
            e.preventDefault();
        });

        $('#btnLeft').click(function(e) {
            var selectedOpts = $('#lstBox2 option:selected');
            if (selectedOpts.length == 0) {
                alert("Nothing to move.");
                e.preventDefault();
            }

            $('#lstBox1').append($(selectedOpts).clone());
            $(selectedOpts).remove();

            $('#lstBox2 option').each((index, elem) => {
                $(elem).prop('selected', true);
            })

            if( $('#lstBox2 option').length == 0){
                $("#lstBox2").addClass('is-invalid');
            }else {
                $("#lstBox2").removeClass('is-invalid');
            }
            e.preventDefault();
        });
    })
</script>



