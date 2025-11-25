<?php 
    require_once "include/oturum_kontrol.php";
?> 
<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-lock fs-4"></i> Şifre Değiştirme
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
            <form class="row g-3 needs-validation" action="/index.php?url=sifre_guncelle_db_islem" method="POST" id="sifre-guncelle-form" autocomplete="off">
                <div class="form-floating col-md-12">
                    <input type="password" name="mevcut_sifre" class="form-control" id="mevcut-sifre" >
                    <label for="mevcut-sifre" class="form-label">Mevcut Şifre</label>
                </div>
                <div class="form-floating col-md-6">
                    <input type="password" name="yeni_sifre" class="form-control" id="yeni-sifre" >
                    <label for="yeni-sifre" class="form-label">Şifre</label>
                </div>
                <div class="form-floating col-md-6">
                    <input type="password" name="yeni_sifre_tekrar" class="form-control" id="yeni-tekrar-sifre" >
                    <label for="yeni-tekrar-sifre" class="form-label">Şifre Tekrar</label>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <button class="btn btn-warning" type="submit" name="sifre_guncelle" id="sifre-guncelle-button">
                            <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                        </button>
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
        $("#sifre-guncelle-form").submit(function(){
            $("#sifre-guncelle-button").addClass('disabled');
            return true;
        });
    });
</script> 
