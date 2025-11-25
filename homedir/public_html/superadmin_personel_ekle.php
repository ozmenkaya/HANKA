<?php 
    require_once "include/oturum_kontrol.php";
    if($_SESSION['yetki_id'] != 0)
    {
        require_once "include/yetkisiz.php";
        die();
    }
?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-user-plus"></i>
                Personel Girişi
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
            <form class="row g-3 needs-validation" action="/index.php?url=superadmin_personel_db_islem" method="POST">
                <?php 
                    $sth = $conn->prepare('SELECT id, firma_adi FROM firmalar');
                    $sth->execute();
                    $firmalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="form-floating col-md-12">
                    <select class="form-select form-select-lg js-example-basic-single" name="firma_id" id="firma_id" required>
                        <option selected disabled value="">Firma Seçiniz</option>
                        <?php foreach ($firmalar as $firma) { ?>
                            <option  value="<?php echo $firma['id'];?>"><?php echo $firma['firma_adi'];?></option>
                        <?php }?>
                    </select>
                    <label for="firma_id" class="form-label">Firma</label>
                </div>
                
                <div class="form-floating col-md-6">
                    <input type="text" name="ad" class="form-control" id="ad" required>
                    <label for="ad" class="form-label">Adı</label>
                </div>
                <div class="form-floating col-md-6">
                    <input type="text" name="soyad" class="form-control" id="soyad" required>
                    <label for="soyad" class="form-label">Soyadı</label>
                </div>
                <div class="form-floating col-md-6">
                    <input type="email" name="email" class="form-control" id="email" required>
                    <label for="email" class="form-label">E-mail</label>
                </div>

                <div class="form-floating col-md-6">
                    <input type="text" name="sifre" class="form-control" id="sifre" required>
                    <label for="sifre" class="form-label">Şifre</label>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <button class="btn btn-primary" type="submit" name="personel_ekle">
                            <i class="fa-regular fa-square-plus"></i> KAYDET
                        </button>
                        <a href="/index.php?url=firma" class="btn btn-secondary" type="submit">
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function(){
        $('.js-example-basic-single').select2({
            theme: 'bootstrap-5'
        });
    });
</script> 