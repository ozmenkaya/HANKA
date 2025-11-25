<?php 
    include_once "include/oturum_kontrol.php";
?>
<div class="row mt-2">
    <?php require_once "include/uyari_session.php"; ?>
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-gears"></i> Makina Girişi</h5>
        </div>
        <div class="card-body pt-0">
            <form class="row g-3 needs-validation" action="/index.php?url=makina_db_islem" method="POST" id="makina-ekle-form">
                
                <div class="form-floating col-md-4">
                    <input type="text" name="makina_adi" class="form-control" id="makina_adi" required>
                    <label for="makina_adi" class="form-label">Makina Adı</label>
                </div>
                <div class="form-floating col-md-4">
                    <input type="text" name="makina_modeli" class="form-control" id="makina_modeli" required>
                    <label for="makina_modeli" class="form-label">Makina Modeli</label>
                </div>
                <div class="form-floating col-md-4">
                    <input type="text" name="makina_seri_no" class="form-control" id="makina_seri_no" required>
                    <label for="makina_seri_no" class="form-label">Makina Seri No</label>
                </div>

                <?php 
                    $sth = $conn->prepare('SELECT id, departman FROM departmanlar WHERE firma_id = :firma_id ORDER BY `departman` ASC');
                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                    $sth->execute();
                    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="form-floating col-md-4">
                    <select class="form-select" name="departman_id" id="departman_id" required>
                        <option selected disabled value="">Seç...</option>
                        <?php foreach($departmanlar as $departman){ ?>
                            <option value="<?php echo $departman['id']; ?>">
                                <?php echo $departman['departman']; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <label for="departman_id" class="form-label">Departman</label>
                </div>

                <div class="form-floating col-md-4">
                    <input type="number" min="1" name="makina_bakim_suresi" class="form-control" id="makina_bakim_suresi" required>
                    <label for="makina_bakim_suresi" class="form-label">Makina Bakım Aralığı (Ay)</label>
                </div>
                <div class="form-floating col-md-4">
                    <input type="date" name="makina_son_bakim_tarih" class="form-control" id="makina_son_bakim_tarih" required>
                    <label for="makina_son_bakim_tarih" class="form-label">Makina Son Bakım Tarihi</label>
                </div>

                <div class="card mt-3">
                    <div class="card-body">           
                        <div class="row">
                            <div class="col-md-5">
                                <label for="lst-box1-makine-personel" class="form-label">Makina Personeli</label>
                                <select class="form-select" name="makina_personel_idler[]" id="lst-box1-makine-personel" multiple="multiple" >
                                    <!-- <option selected disabled value="">Seç...</option> -->
                                </select>
                            </div>

                            <!-- <div class="col-md-1 text-center">
                                <br>
                                <input type='button' id='btn-right-makine-personel' value ='  >  ' class="btn btn-outline-primary"/>
                                <br/>
                                <br>
                                <input type='button' id='btn-left-makine-personel' value ='  <  ' class="btn btn-outline-success"/>          
                            </div>
                            <div class="col-md-5">
                                <label for="makina_personel_id" class="form-label">Seçilen Personeller</label>
                                <select multiple="multiple" id='lst-box2-makine-personel' class="form-select" name="makina_personel_idler[]">
                                </select>  
                                <div class="invalid-feedback">
                                    Lütfen Personel Atayın.
                                </div>      
                            </div> -->
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body m-2">
                        <?php  
                            $sth = $conn->prepare('SELECT id FROM departmanlar WHERE firma_id =:firma_id AND departman = "BAKIM" ');
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $bakim_departman = $sth->fetch(PDO::FETCH_ASSOC);

                            if(!empty($bakim_departman)){
                                $sth = $conn->prepare("SELECT personeller.id, personeller.ad, personeller.soyad FROM `personel_departmanlar` 
                                        JOIN personeller  ON personeller.id = personel_departmanlar.personel_id 
                                        WHERE firma_id = :firma_id AND personel_departmanlar.departman_id = {$bakim_departman['id']}");
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $bakim_personeller = $sth->fetchAll(PDO::FETCH_ASSOC);

                                //echo "<pre>"; print_r($bakim_personeller);
                            }
                        ?>
                        <div class="row">
                            <div class="col-md-5">
                                <label for="lst-box1-makine-bakim-personel" class="form-label">Makina Bakım Personeli</label>
                                <select class="form-select" name="makina_bakim_personel_idler[]" id="lst-box1-makine-bakim-personel" multiple="multiple"  >
                                    <!-- <option selected disabled value="">Seç...</option> -->
                                    <?php foreach ($bakim_personeller as $bakim_personel) { ?>
                                        <option  value="<?php echo $bakim_personel['id'] ?>">
                                            <?php echo $bakim_personel['ad'].' '.$bakim_personel['soyad']; ?>
                                        </option>
                                    <?php }?>
                                </select>
                                <?php if(empty($bakim_departman)){ ?>
                                    <div  class="text-danger fw-bold">
                                        Lütfen Bakım Departmanını Oluşturunuz. 
                                        <a href="/index.php?url=departman" class="btn btn-sm btn-info mt-1 text-white">
                                            <i class="fa-solid fa-building"></i> Departman Ekle
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>

                            <!-- <div class="col-md-1 text-center">
                                <br>
                                <input type='button' id='btn-right-makine-bakim-personel' value ='  >  ' class="btn btn-outline-primary"/>
                                <br/>
                                <br>
                                <input type='button' id='btn-left-makine-bakim-personel' value ='  <  ' class="btn btn-outline-success"/>          
                            </div>
                            <div class="col-md-5">
                                <label for="lst-box2-makine-bakim-personel" class="form-label">Seçilen Bakım Personelleri</label>
                                <select multiple="multiple" id='lst-box2-makine-bakim-personel' class="form-select" name="makina_bakim_personel_idler[]">
                                </select>  
                                <div class="invalid-feedback">
                                    Lütfen Personel Atayın.
                                </div>   
                            </div> -->
                        </div>
                        
                    </div>
                </div>

        
                <div class="form-floating col-md-6">
                    <select class="form-select" name="durumu" id="durumu" required>
                        <option  value="aktif">Aktif</option>
                        <option  value="pasif">Pasif</option>

                    </select>
                    <label for="durumu" class="form-label">Durumu</label>
                </div>

                <div class="form-floating col-md-6">
                    <select class="form-select" name="makina_ayar_suresi_varmi" id="makina_ayar_suresi_varmi" required>
                        <option selected value="var">Var</option>
                        <option  value="yok">Yok</option>

                    </select>
                    <label for="makina_ayar_suresi_varmi" class="form-label">Makina Ayar Süresi Var Mı?</label>
                </div>

                <div class="form-floating col-md-12">
                    <select class="form-select" name="planlamada_goster" id="planlamada_goster" required>
                        <option selected value="evet">Evet</option>
                        <option value="hayir">Hayır</option>
                    </select>
                    <label for="planlamada_goster" class="form-label">Makina Planlamada Gösterilsin Mi?</label>
                </div>

                <div class="form-floating col-md-12">
                    <textarea class="form-control" name="aciklama" id="aciklama" style="height: 100px"></textarea>
                    <label for="aciklama" class="form-label">Açıklama</label>
                </div>

                <?php 
                    $sth = $conn->prepare('SELECT stoga_geri_gonderme_durumu FROM firmalar WHERE id = :id');
                    $sth->bindParam('id', $_SESSION['firma_id']);
                    $sth->execute();
                    $firma_ayar = $sth->fetch(PDO::FETCH_ASSOC);
                    //print_r($firma_ayar);
                ?>
                <?php if($firma_ayar['stoga_geri_gonderme_durumu'] == 'evet'){ ?>
                    <div class="form-floating col-md-12">
                        <div class="form-check form-switch fs-6">
                            <input class="form-check-input" type="checkbox" role="switch" name="stoga_geri_gonderme_durumu" id="stoga_geri_gonderme_durumu">
                            <label class="form-check-label" for="stoga_geri_gonderme_durumu">Stoğa Geri Gönderilecek Mi?</label>
                        </div>
                    </div>
                <?php } ?>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button class="btn btn-primary" type="submit" name="makina_ekle" id="makina-ekle-button">
                            <i class="fa-regular fa-square-plus"></i> KAYDET
                        </button>
                        <a href="/index.php?url=makina" class="btn btn-secondary">
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

        $("#makina-ekle-form").submit(function(){
            $("#makina-ekle-button").addClass('disabled');
            return true;
        });

        $("#departman_id").change(function(){
            const departman_id = $(this).val();
            $.ajax({
                url         : "/index.php?url=personel_db_islem&islem=departman-id-personel&departman_id=" + departman_id,
                dataType    : "JSON",
                success     : function(personeller){
                    //let verilerHTML = '<option selected="" disabled="" value="">Seç...</option>';
                    let verilerHTML = '';
                    for(const personel of personeller)
                    {
                        verilerHTML += `<option value="${personel.id}">${personel.ad} ${personel.soyad}</option>`;
                    }
                    $("#lst-box1-makine-personel").html(verilerHTML);
                    $("#lst-box2-makine-personel").html('');
                }
            });
        });

        $('#btn-right-makine-personel').click(function(e) {
            var selectedOpts = $('#lst-box1-makine-personel option:selected');
            if (selectedOpts.length == 0) {
                alert("Nothing to move.");
                e.preventDefault();
            }

            $('#lst-box2-makine-personel').append($(selectedOpts).clone());
            $(selectedOpts).remove();

            $('#lst-box2-makine-personel option').each((index, elem) => {
                $(elem).prop('selected', true);
            });
            if( $('#lst-box2-makine-personel option').length == 0){
                $("#lst-box2-makine-personel").addClass('is-invalid');
            }else {
                $("#lst-box2-makine-personel").removeClass('is-invalid');
            }
            e.preventDefault();
        });

        $('#btn-left-makine-personel').click(function(e) {
            var selectedOpts = $('#lst-box2-makine-personel option:selected');
            if (selectedOpts.length == 0) {
                alert("Nothing to move.");
                e.preventDefault();
            }

            $('#lst-box1-makine-personel').append($(selectedOpts).clone());
            $(selectedOpts).remove();

            $('#lst-box2-makine-personel option').each((index, elem) => {
                $(elem).prop('selected', true);
            });
            if( $('#lst-box2-makine-personel option').length == 0){
                $("#lst-box2-makine-personel").addClass('is-invalid');
            }else {
                $("#lst-box2-makine-personel").removeClass('is-invalid');
            }
            e.preventDefault();
        });

        $('#btn-right-makine-bakim-personel').click(function(e) {
            var selectedOpts = $('#lst-box1-makine-bakim-personel option:selected');
            if (selectedOpts.length == 0) {
                alert("Nothing to move.");
                e.preventDefault();
            }

            $('#lst-box2-makine-bakim-personel').append($(selectedOpts).clone());
            $(selectedOpts).remove();

            $('#lst-box2-makine-bakim-personel option').each((index, elem) => {
                $(elem).prop('selected', true);
            });
            if( $('#lst-box2-makine-bakim-personel option').length == 0){
                $("#lst-box2-makine-bakim-personel").addClass('is-invalid');
            }else {
                $("#lst-box2-makine-bakim-personel").removeClass('is-invalid');
            }
            e.preventDefault();
        });

        $('#btn-left-makine-bakim-personel').click(function(e) {
            var selectedOpts = $('#lst-box2-makine-bakim-personel option:selected');
            if (selectedOpts.length == 0) {
                alert("Nothing to move.");
                e.preventDefault();
            }

            $('#lst-box1-makine-bakim-personel').append($(selectedOpts).clone());
            $(selectedOpts).remove();

            $('#lst-box2-makine-bakim-personel option').each((index, elem) => {
                $(elem).prop('selected', true);
            });
            if( $('#lst-box2-makine-bakim-personel option').length == 0){
                $("#lst-box2-makine-bakim-personel").addClass('is-invalid');
            }else {
                $("#lst-box2-makine-bakim-personel").removeClass('is-invalid');
            }
            e.preventDefault();
        });
    });

    function toggleFormMode() {
        // Form tipi değişti, kaydet butonuna basıldığında veritabanına kaydedilecek
    }
</script>

