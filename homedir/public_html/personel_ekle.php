<?php 
    require_once "include/oturum_kontrol.php";

    if(!in_array(PERSONEL_EKLE, $_SESSION['sayfa_idler']))
    {
        require_once "include/yetkisiz.php";
        die();
    }
?>

<div class="row mt-2">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-user-plus"></i> Personel Girişi
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
            <form class="row g-3 needs-validation" action="/index.php?url=personel_db_islem" method="POST" id="personel-ekle">
                <div class="form-floating col-md-6">
                    <input type="text" name="ad" class="form-control" id="ad" required>
                    <label for="ad" class="form-label">Adı</label>
                </div>
                <div class="form-floating col-md-6">
                    <input type="text" name="soyad" class="form-control" id="soyad" required>
                    <label for="soyad" class="form-label">Soyadı</label>
                </div>
                <div class="form-floating col-md-12">
                    <input type="text" name="adres" class="form-control" id="adres" required>
                    <label for="adres" class="form-label">Adresi</label>
                </div>
                
                <?php 
                    $sth = $conn->prepare('SELECT id, yetki FROM yetkiler');
                    $sth->execute();
                    $yetkiler = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="form-floating col-md-4">
                    <select class="form-select" name="yetki_id" id="yetki_id" required>
                        <option selected disabled value="">Seç...</option>
                        <?php foreach($yetkiler as $yetki){ ?>
                            <?php if($yetki['yetki'] != "Superadmin"){ ?>
                                <option value="<?php echo $yetki['id']; ?>"><?php echo $yetki['yetki']; ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                    <label for="yetki_id" class="form-label">Görev</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="text" name="cep_numarasi" class="form-control" id="cep_numarasi" required>
                    <label for="cep_numarasi" class="form-label">Cep Telefonu</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="text" name="sabit_hat" class="form-control" id="sabit_hat" required>
                    <label for="sabit_hat" class="form-label">Sabit Hat</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="email" name="email" class="form-control" id="email" required>
                    <label for="email" class="form-label">E-mail</label>
                    <div id="invalidCheck3Feedback" class="invalid-feedback">
                        Mail adresi  sistemde mevcut.
                    </div>
                </div>

                <div class="form-floating col-md-4">
                    <input type="text" name="sifre" class="form-control" id="sifre" required>
                    <label for="sifre" class="form-label">Şifre</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="date" name="dogum_tarihi" class="form-control" id="dogum_tarihi" required>
                    <label for="dogum_tarihi" class="form-label">Doğum Tarihi</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="date" name="ise_baslama" class="form-control" id="ise_baslama" required>
                    <label for="ise_baslama" class="form-label">İşe Başlama</label>
                </div>

                <div class="form-floating col-md-4">
                    <textarea name="aciklama" class="form-control" id="aciklama" rows="100" required></textarea>
                    <label for="aciklama" class="form-label">Açıklama</label>
                </div>

                <div class="form-floating col-md-4">
                    <select class="form-select" name="durum" id="durum" required>
                        <option value="aktif">Aktif</option>
                        <option value="pasif">Pasif</option>
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
                        ?>
                        <label for="lstBox11" class="form-label">Departman</label>
                        <select class="form-select" multiple="multiple"  id="departman_idler" name="departman_idler[]">
                            <!-- <option selected  value="">Seçiniz</option> -->
                            <?php foreach($departmanlar as $departman){ ?>
                                <option value="<?php echo $departman['id']; ?>">
                                    <?php echo $departman['departman']; ?>
                                </option>
                            <?php } ?>
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
                        <select multiple="multiple" class="form-select" id='lstBox2' name="departman_idler[]">
                        </select>
                       
                    </div> -->
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <button class="btn btn-primary" type="submit" name="personel_ekle" id="personel-ekle-button">
                            <i class="fa-regular fa-square-plus"></i> KAYDET
                        </button>
                        <a href="/index.php?url=personeller" class="btn btn-danger">
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
    
    $(document).ready(function() {

        $("#personel-ekle").submit(function(){
            $("#personel-ekle-button").addClass('disabled');
            return true;
        });
        
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

        $("#tum-yetkiler").change(function(){
            if($(this).is(':checked')){
                $('.yetkiler').prop('checked', true);
            }
            else{
                $('.yetkiler').prop('checked', false);
            }
        })

        $("#email").blur(function(){
            const email = $(this).val();
            $.ajax({
                url         : "/index.php?url=personel_db_islem&islem=email-kontrol&email=" + email,
                dataType    : "JSON",
                success     : function(personel){
                    console.log(personel);
                    if(personel?.data?.id)
                    {
                        $("#email").addClass('is-invalid');
                    }
                    else 
                    {
                        $("#email").removeClass('is-invalid');
                    }
                }
            });
        })
        /*
        $('#personel-ekle').submit(function(){
            const departman_idler = $('#lstBox2').val();
            if(departman_idler.length == 0)
            {
                $("#lstBox2").focus();
                $("#lstBox2").addClass('is-invalid');
                return false;
            }
            $("#lstBox2").removeClass('is-invalid');
            return true;
        });
        */
    });
</script> 


