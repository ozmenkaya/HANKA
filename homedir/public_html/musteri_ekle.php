<?php
    include_once "include/oturum_kontrol.php";
    //echo "<pre>"; print_r($_SERVER); exit;

    
    if(!in_array(MUSTERI_OLUSTUR, $_SESSION['sayfa_idler'])){ 
        require_once "include/yetkisiz.php";
        die();
    }

    $sth = $conn->prepare('SELECT id, baslik FROM ulkeler ORDER BY baslik ');
    $sth->execute();
    $ulkeler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT id, ad, soyad FROM personeller WHERE yetki_id IN(2,4) AND firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT id, sektor_adi FROM sektorler WHERE firma_id = :firma_id  ORDER BY sektor_adi ASC  ');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $sektorler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" /> -->
    <div class="row mt-2">
        <div class="col-md-12">
            <?php if(empty($personeller)){?>
                <div class="alert alert-danger" role="alert">
                    <h5>
                        Müşteri Eklemek İçin Müşteri Temsilcisi veya Pazarlama Personeli Eklemeniz Gereklidir! 
                        <a href="/index.php?url=personel_ekle" class="btn btn-primary btn-sm">Personel Ekle</a>
                    </h5>
                </div>
            <?php } ?>

            <?php if(empty($sektorler)){?>
                <div class="alert alert-danger" role="alert">
                    <h5>
                        Müşteri Eklemek İçin Sektor Gereklidir! 
                        <a href="/index.php?url=sektor" class="btn btn-primary btn-sm">Sektor Ekle</a>
                        
                    </h5>
                </div>
            <?php } ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-user-plus"></i>
                        Müşteri Ekle
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
                <div class="card-body">
                    <form class="row g-3 needs-validation" action="/index.php?url=musteri_db_islem" method="POST" id="musteri-ekle-form"> 
                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="marka" id="marka" required>
                            <label for="marka" class="form-label">Marka</label>
                        </div>
                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="firma_unvani" id="firma_unvani" required>
                            <label for="firma_unvani" class="form-label">Firma Ünvanı</label>
                        </div>

                        <?php 
                        /*
                        <div class="form-floating col-md-12">
                            <input type="text" class="form-control" name="adresi" id="adresi" required>
                            <label for="adresi" class="form-label">Adresi</label>
                        </div>


                            $sth = $conn->prepare('SELECT id, baslik FROM ulkeler ORDER BY baslik ');
                            $sth->execute();
                            $ulkeler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="ulke_id" 
                                name="ulke_id" required>
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
                            </select>
                            <label for="sehir_id" class="form-label">Şehir</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="ilce_id" name="ilce_id" required>
                            </select>
                            <label for="ilce_id" class="form-label">İlçe</label>
                        </div>
                        */ ?>

                        <div class="form-floating col-md-4">
                            <select class="form-select" name="sektor_id" id="sektor_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($sektorler as $sektor) { ?>
                                    <option value="<?php echo $sektor['id']; ?>"><?php echo $sektor['sektor_adi']; ?></option>
                                <?php } ?>
                            </select>
                            <label for="sektor_id" class="form-label">Sektör</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="cep_tel" id="cep_tel" required>
                            <label for="cep_tel" class="form-label">Cep Telefonu</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="sabit_hat" id="sabit_hat" required>
                            <label for="sabit_hat" class="form-label">Sabit Hat</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="e_mail" id="e_mail" required>
                            <label for="e_mail" class="form-label">E-mail</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="vergi_numarasi" id="vergi_numarasi" required>
                            <label for="vergi_numarasi" class="form-label">Vergi Numarası</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="vergi_dairesi" id="vergi_dairesi" required>
                            <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                        </div>

                        <div class="form-floating col-md-4">
                            <input type="number" value="0" class="form-control" name="vade" id="vade" required>
                            <label for="vade" class="form-label">Vade</label>
                        </div>

                        <div class="form-floating col-md-4 mb-2">
                            <select class="form-select" id="musteri_temsilcisi_id" name="musteri_temsilcisi_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($personeller as $personel) { ?>
                                    <option value="<?php echo $personel['id']; ?>"><?php echo $personel['ad'].' '.$personel['soyad']; ?></option>
                                <?php } ?>
                            </select>
                            <label for="musteri_temsilcisi_id" class="form-label">Musteri Temsilcisi</label>
                        </div>

                        <div class="border-bottom border-secondary"></div>

                        <!-- Adresler Bölümü -->
                        <div class="card mb-3 col-md-12 p-0">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Adresler</h6>
                                    <button type="button" class="btn btn-sm btn-primary" id="adres-ekle">Adres Ekle</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="adres-listesi">
                                    <div class="adres-item card bg-light px-2 py-2">
                                        <div class="row align-items-center m-0">
                                            <div class="form-floating col-md-2 p-1">
                                                <select class="form-select js-example-basic-single" name="adresler[0][adres_turu]" required>
                                                    <option value="" disabled selected>Adres Türü</option>
                                                    <option value="Merkez">Merkez</option>
                                                    <option value="Sevk">Sevk</option>
                                                </select>
                                                <label>Adres Türü</label> 
                                            </div>
                                            <div class="form-floating col-md-3 p-1">
                                                <label>Adres Başlığı</label>
                                                <input class="form-control" type="text" name="adresler[0][baslik]" required>
                                            </div>
                                            <div class="col-md-2 p-1">
                                                <div class="row px-2 flex-column">
                                                    <div class="form-group col-auto p-1 default-merkez-container">
                                                        <input type="radio" id="default_adres0_merkez" name="default_adres_merkez" value="1" checked>
                                                        <label for="default_adres0_merkez"> Varsayılan Merkez </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row align-items-center m-0">
                                            <div class="form-floating col-md-2 p-1">
                                                <select class="form-select js-example-basic-single" name="adresler[0][ulke_id]" id="adres_ulke_0" required>
                                                    <option value="" selected disabled>Ülke Seçiniz</option>
                                                    <option value="223">Türkiye</option>
                                                    <?php foreach($ulkeler as $ulke): ?>
                                                    <option value="<?= $ulke['id'] ?>"><?= $ulke['baslik'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label>Ülke</label>
                                            </div>
                                            <?php 
                                                $sth = $conn->prepare('SELECT id, baslik FROM sehirler WHERE `ulke_id` = :ulke_id');
                                                $sth->bindParam('ulke_id', $musteri['ulke_id']);
                                                $sth->execute();
                                                $sehirler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <div class="form-floating col-md-2 p-1">
                                                <select class="form-select js-example-basic-single" name="adresler[0][sehir_id]" id="adres_sehir_0" required>
                                                    <option value="" selected disabled>Şehir Seçiniz</option>
                                                    <?php foreach($sehirler as $sehir): ?>
                                                    <option value="<?= $sehir['id'] ?>"><?= $sehir['baslik'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label>Şehir</label>
                                            </div>
                                            <?php 
                                                $sth = $conn->prepare('SELECT id, baslik FROM ilceler WHERE `sehir_id` = :sehir_id');
                                                $sth->bindParam('sehir_id', $musteri['sehir_id']);
                                                $sth->execute();
                                                $ilceler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <div class="form-floating col-md-2 p-1">
                                                <select class="form-select js-example-basic-single" name="adresler[0][ilce_id]" id="adres_ilce_0" required>
                                                    <option value="" selected disabled>İlçe Seçiniz</option>
                                                    <?php foreach($ilceler as $ilce): ?>
                                                    <option value="<?= $ilce['id'] ?>"><?= $ilce['baslik'] ?></option>
                                                    <?php endforeach; ?>
                                                </select> 
                                                <label>İlçe</label>
                                            </div>

                                            <div class="form-floating col-md-6 p-1">
                                                <label>Adres</label>
                                                <input class="form-control" name="adresler[0][adres]" required>
                                            </div>
                                            
                                        </div> <!-- row -->
                                    </div> <!-- adres-item -->  
                                </div>
                            </div>
                        </div>

                        <!-- Yetkililer Bölümü -->
                        <div class="card mb-3">
                            <h4>Yetkililer</h4>
                            <div id="yetkili-listesi">
                                <div class="yetkili-item">
                                    <div class="row align-items-center m-0">
                                        <div class="form-floating col-auto p-1">
                                            <input type="text" class="form-control" name="yetkililer[0][adi]" required>
                                            <label>Adı Soyadı</label>
                                        </div>
                                        <div class="form-floating col-auto p-1">
                                            <input type="text" class="form-control" name="yetkililer[0][cep]" required>
                                            <label>Cep Telefonu</label>
                                        </div>
                                        <div class="form-floating col-auto p-1">
                                            <input type="text" class="form-control" name="yetkililer[0][mail]" required>
                                            <label>E-mail</label>
                                        </div>
                                        <div class="form-floating col-auto p-1">
                                            <select class="form-select" name="yetkililer[0][gorev]" required>
                                                <option value="Firma Sahibi">Firma Sahibi</option>
                                                <option value="Müdür">Müdür</option>
                                                <option value="Satın Alma">Satın Alma</option>
                                            </select>
                                            <label>Görevi</label>
                                        </div>
                                        <div class="form-floating col-auto p-1">
                                            <input type="text" class="form-control" name="yetkililer[0][aciklama]">
                                            <label>Not</label>
                                        </div>
                                        <div class="form-group col-auto p-1">
                                            <input type="radio" id="default_yetkili" name="default_yetkili" value="1" checked>
                                            <label for="default_yetkili"> Varsayılan </label>
                                        </div>
                                        <div class="form-group col-auto p-2">
                                            <button type="button" id="yetkili-ekle" class="btn btn-primary">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div >
                            <button class="btn btn-primary" type="submit" name="musteri_ekle" id="musteri-ekle-button">
                                <i class="fa-solid fa-paper-plane"></i> KAYDET
                            </button>
                            <a href="/index.php?url=musteriler" class="btn btn-secondary">
                                <i class="fa-regular fa-rectangle-xmark"></i> MÜŞTERİLER
                            </a>
                        </div>
            
                    </form>
                </div>
            </div>
        </div>
    </div>    
</main>      
<script src="assets/node_modules/jquery/dist/jquery.min.js"></script>
<script>
    $(document).ready(function() {

        $("#musteri-ekle-form").submit(function(){
            $("#musteri-ekle-button").addClass('disabled');
            return true;
        });
 
        $("#ulke_id").change(function(){
            const ulke_id = $(this).val();

            $.ajax({
            url: "/index.php?url=ulke_il_ilce_kontrol&ulke_id=" + ulke_id,
            dataType: "json",
            method: "GET",
            success: function(sehirler) {
                try {
                    // Gelen veriyi kontrol et
                    if (!sehirler || !Array.isArray(sehirler)) {
                        alert("Gelen veri bir dizi değil veya boş: " + JSON.stringify(sehirler));
                    }

                    // Varsayılan seçenek
                    let sehirler_HTML = "<option selected disabled>İl Seçiniz</option>";

                    // Şehirleri döngüyle HTML'e ekle
                    for (const sehir of sehirler) {
                        if (!sehir.id || !sehir.baslik) {
                            console.warn("Geçersiz şehir verisi:", sehir);
                            continue;
                        }
                        sehirler_HTML += `
                            <option value="${sehir.id}">${sehir.baslik}</option>
                        `;
                    }

                    // HTML'i #sehir_id elemanına ekle
                    $("#sehir_id").html(sehirler_HTML);
                } catch (error) {
                    console.error("Şehir verileri işlenirken bir hata oluştu:", error.message);
                    console.log("Hata detayları:", error);
                    alert("Şehir verileri yüklenirken bir hata oluştu: " + error.message);
                    $("#sehir_id").html("<option selected disabled>Veri yüklenemedi</option>");
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX çağrısı başarısız oldu:", {
                    status: status,
                    error: error,
                    xhr: xhr
                });
                alert("Şehir verileri alınamadı. Hata: " + status + " - " + error);
                $("#sehir_id").html("<option selected disabled>Veri alınamadı</option>");
            },
            complete: function() {
                console.log("AJAX isteği tamamlandı.");
            }
        });

        });

        $("#sehir_id").change(function(){
            const sehir_id = $(this).val();

            $.ajax({
                url         : "/index.php?url=ulke_il_ilce_kontrol&sehir_id=" + sehir_id,
                dataType    : "JSON",
                success     : function(ilceler){
                    let ilceler_HTML =  "<option value='' selected disabled>İlçe Seçiniz</option>";
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
</script>

<script>
    $(document).ready(function() {
        let yetkiliIndex = 1;

        $("#yetkili-ekle").click(function() {
            let newYetkili = `
                <div class="yetkili-item mt-2">
                    <div class="row align-items-center m-0">
                        <div class="form-floating col-auto p-1">
                            <input type="text" class="form-control" name="yetkililer[${yetkiliIndex}][adi]" required>
                            <label>Adı Soyadı</label>
                        </div>
                        <div class="form-floating col-auto p-1">
                            <input type="text" class="form-control" name="yetkililer[${yetkiliIndex}][cep]" required>
                            <label>Cep Telefonu</label>
                        </div>
                        <div class="form-floating col-auto p-1">
                            <input type="text" class="form-control" name="yetkililer[${yetkiliIndex}][mail]" required>
                            <label>E-mail</label>
                        </div>
                        <div class="form-floating col-auto p-1">
                            <select class="form-select" name="yetkililer[${yetkiliIndex}][gorev]" required>
                                <option value="Firma Sahibi">Firma Sahibi</option>
                                <option value="Müdür">Müdür</option>
                                <option value="Satın Alma">Satın Alma</option>
                            </select>
                            <label>Görevi</label>
                        </div>
                        <div class="form-floating col-auto p-1">
                            <input type="text" class="form-control" name="yetkililer[${yetkiliIndex}][aciklama]">
                            <label>Not</label>
                        </div>
                        <div class="form-group col-auto p-1">
                            <input type="radio" id="default_yetkili${yetkiliIndex}" name="default_yetkili" value="${yetkiliIndex}">
                            <label for="default_yetkili${yetkiliIndex}"> Varsayılan </label>
                        </div>
                        <div class="form-group col-auto p-2">
                            <button type="button" class="btn btn-danger yetkili-sil">Sil</button>
                        </div>
                    </div>
                </div>
            `;

            $("#yetkili-listesi").append(newYetkili);
            yetkiliIndex++;
        });

        $(document).on("click", ".yetkili-sil", function() {
            $(this).closest(".yetkili-item").remove();
        });

        // Adres ekleme işlemi
        let adresIndex = 1;

        $("#adres-ekle").click(function() { 
            const newAdres = `
                        <div class="adres-item card bg-light px-2 py-2 mt-2">
                            <div class="row align-items-center m-0">
                                <div class="form-floating col-md-2 p-1">
                                    <select class="form-select" name="adresler[${adresIndex}][adres_turu]" required>
                                        <option value="" disabled selected>Adres Türü</option>
                                        <option value="Merkez">Merkez</option>
                                        <option value="Sevk">Sevk</option>
                                    </select>
                                    <label>Adres Türü</label>
                                </div>
                                <div class="form-floating col-md-3 p-1">
                                    <label>Adres Başlığı</label>
                                    <input class="form-control px-2" type="text" name="adresler[${adresIndex}][baslik]" placeholder="Adres başlığı" required>
                                </div>
                                <div class="form-floating col-md-2 p-1">
                                    <div class="row px-2 flex-column">
                                        <div class="form-group col-auto p-1 default-merkez-container">
                                            <input type="radio" id="default_adres${adresIndex}_merkez" name="default_adres_merkez" value="${adresIndex}">
                                            <label for="default_adres${adresIndex}_merkez"> Varsayılan Merkez </label>
                                        </div>
                                        <div class="form-group col-auto p-1 default-sevk-container" style="display:none;">
                                            <input type="radio" id="default_adres${adresIndex}_sevk" name="default_adres_sevk" value="${adresIndex}">
                                            <label for="default_adres${adresIndex}_sevk"> Varsayılan Sevk </label>
                                        </div>
                                    </div>
                                </div>
                                 <div class="form-group offset-md-4 col-md-1 p-1 d-flex justify-content-end">
                                    <button type="button" class="btn btn-danger adres-sil">Sil</button>
                                </div>
                            </div>
                            <div class="row align-items-center m-0">
                                <div class="form-floating col-md-2 p-1">
                                    <select class="form-select adres-ulke" name="adresler[${adresIndex}][ulke_id]" id="adres_ulke_${adresIndex}" required>
                                        <option value="" selected disabled>Ülke Seçiniz</option>
                                        <option value="223">Türkiye</option>
                                        <?php foreach($ulkeler as $ulke): ?>
                                        <option value="<?= $ulke['id'] ?>"><?= $ulke['baslik'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label>Ülke</label>
                                </div>
                                <div class="form-floating col-md-2 p-1">
                                    <select class="form-select adres-sehir" name="adresler[${adresIndex}][sehir_id]" id="adres_sehir_${adresIndex}" required>
                                        <option value="" selected disabled>İl Seçiniz</option>
                                    </select>
                                    <label>İl</label>
                                </div>
                                <div class="form-floating col-md-2 p-1">
                                    <select class="form-select" name="adresler[${adresIndex}][ilce_id]" id="adres_ilce_${adresIndex}" required>
                                        <option value="" selected disabled>İlçe Seçiniz</option>
                                    </select>
                                    <label>İlçe</label>
                                </div>
                                <div class="form-floating col-md-6 p-1">
                                    <label>Adres</label>
                                    <input class="form-control" name="adresler[${adresIndex}][adres]" type="text" required>
                                </div>
                            </div>
                        </div>
                    `;

            $("#adres-listesi").append(newAdres);
            $('.js-example-basic-single').select2({
                theme: 'bootstrap-5'
            });
            $('.form-select').select2({
                theme: 'bootstrap-5'
            });

            // Varsayilan Sevk Adresi için yazıldı ---- selimhur 260325
            $('select[name="adresler[' + adresIndex + '][adres_turu]"]').change(function() {
                var $this = $(this);
                var $parent = $this.closest('.adres-item');
                var $merkezContainer = $parent.find('.default-merkez-container');
                var $sevkContainer = $parent.find('.default-sevk-container');
                
                if ($this.val() == 'Merkez') {
                    $merkezContainer.show();
                    $sevkContainer.hide();
                    $sevkContainer.find('input[type="radio"]').prop('checked', false);
                } else if ($this.val() == 'Sevk') {
                    $merkezContainer.hide();
                    $sevkContainer.show();
                    $merkezContainer.find('input[type="radio"]').prop('checked', false);
                }
            });

            adresIndex++;
        });

        $(document).on("click", ".adres-sil", function() {
            $(this).closest(".adres-item").remove();
        });

        // Ülke değiştiğinde illeri getir
        $(document).on("change", "#adres_ulke_0", function() {
            const ulke_id = $(this).val();
            getIller(ulke_id, "#adres_sehir_0");
        });

        // İl değiştiğinde ilçeleri getir
        $(document).on("change", "#adres_sehir_0", function() {
            const sehir_id = $(this).val();
            getIlceler(sehir_id, "#adres_ilce_0");
        });

        // Dinamik eklenen adresler için
        $(document).on("change", ".adres-ulke", function() {
            const ulke_id = $(this).val();
            const id = $(this).attr('id').replace('adres_ulke_', '');
            getIller(ulke_id, `#adres_sehir_${id}`);
        });

        $(document).on("change", ".adres-sehir", function() {
            const sehir_id = $(this).val();
            const id = $(this).attr('id').replace('adres_sehir_', '');
            getIlceler(sehir_id, `#adres_ilce_${id}`);
        });

        // İlleri getir
        function getIller(ulke_id, target) {
            $.ajax({
                url: "/index.php?url=ulke_il_ilce_kontrol&ulke_id=" + ulke_id,
                dataType: "JSON",
                success: function(sehirler) {
                    let sehirler_HTML = "<option selected disabled>İl Seçiniz</option>";
                    for(const sehir of sehirler) {
                        sehirler_HTML += `<option value="${sehir.id}">${sehir.baslik}</option>`;
                    }
                    $(target).html(sehirler_HTML);
                }
            });
        }

        // İlçeleri getir
        function getIlceler(sehir_id, target) {
            $.ajax({
                url: "/index.php?url=ulke_il_ilce_kontrol&sehir_id=" + sehir_id,
                dataType: "JSON",
                success: function(ilceler) {
                    let ilceler_HTML = "<option value='' selected disabled>İlçe Seçiniz</option>";
                    for(const ilce of ilceler) {
                        ilceler_HTML += `<option value="${ilce.id}">${ilce.baslik}</option>`;
                    }
                    $(target).html(ilceler_HTML);
                }
            });
        }

        $("#ulke_id").change(function(){
            const ulke_id = $(this).val();

            $.ajax({
                url         : "/index.php?url=ulke_il_ilce_kontrol&ulke_id=" + ulke_id,
                dataType    : "JSON",
                success     : function(sehirler){
                    let sehirler_HTML = "<option selected disabled>İl Seçiniz</option>";

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
                    let ilceler_HTML =  "<option value='' selected disabled>İlçe Seçiniz</option>";
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
</script>