<?php 
    include_once "include/oturum_kontrol.php";

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $sth = $conn->prepare('SELECT id, marka, firma_unvani, adresi, ilce_id, sehir_id, ulke_id, sektor_id, cep_tel, 
    sabit_hat, e_mail, 
    aciklama, vergi_dairesi, vergi_numarasi, 
    musteri_temsilcisi_id FROM musteri WHERE id=:id AND firma_id = :firma_id');
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $musteri = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($musteri))
    {
        //header("Location: index.php");
        require_once "include/yetkisiz.php";
        die();
    }
    
    // Müşteri yetkililerini çek
    $sth = $conn->prepare('SELECT * FROM musteri_yetkilileri WHERE musteri_id = :musteri_id ORDER BY is_default DESC');
    $sth->bindParam('musteri_id', $id);
    $sth->execute();
    $yetkililer = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    // Varsayılan yetkiliyi bul
    $default_yetkili_index = 0;
    foreach($yetkililer as $index => $yetkili) {
        if($yetkili['is_default'] == 1) {
            $default_yetkili_index = $index;
            break;
        }
    }
?>
        <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" /> -->
        <div class="row mt-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-user"></i>
                        Müşteri Bilgileri Güncelleme
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
                    <form class="row g-3 needs-validation" action="/index.php?url=musteri_db_islem" method="POST">
                        
                        <input type="hidden" name="id" value="<?php echo $musteri['id']; ?>">
                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="marka" id="marka" 
                                value="<?php echo $musteri['marka'];?>" required>
                            <label for="marka" class="form-label">Marka</label>
                        </div>
                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="firma_unvani" id="firma_unvani" 
                                value="<?php echo $musteri['firma_unvani'] ?>" required>
                            <label for="firma_unvani" class="form-label">Firma Ünvanı</label>
                        </div>
                        <div class="form-floating col-md-12">
                            <input type="text" class="form-control" name="adresi" value="<?php echo $musteri['adresi']; ?>" id="adresi" required>
                            <label for="adresi" class="form-label">Adresi</label>
                        </div>
                        <?php 
                            $sth = $conn->prepare('SELECT id, baslik FROM ulkeler ORDER BY baslik ');
                            $sth->execute();
                            $ulkeler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="ulke_id" name="ulke_id" required>
                                <option value="223">Türkiye</option>
                                <?php foreach ($ulkeler as $ulke) { ?>
                                    <option value="<?php echo $ulke['id']; ?>" <?php echo $ulke['id'] == $musteri['ulke_id'] ? 'selected':''; ?>><?php echo $ulke['baslik']; ?></option>
                                <?php }?>
                            </select>
                            <label for="ulke_id" class="form-label">Ülke</label>
                        </div>

                        <?php 
                            $sth = $conn->prepare('SELECT id, baslik FROM sehirler WHERE `ulke_id` = :ulke_id');
                            $sth->bindParam('ulke_id', $musteri['ulke_id']);
                            $sth->execute();
                            $sehirler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="sehir_id" name="sehir_id" required>
                                <?php foreach ($sehirler as  $sehir) { ?>
                                    <option value="<?php echo $sehir['id']; ?>" <?php echo $sehir['id'] == $musteri['sehir_id'] ? 'selected':'';  ?>><?php echo $sehir['baslik']; ?></option>
                                <?php }?>
                            </select>
                            <label for="sehir_id" class="form-label">Şehir</label>
                        </div>

                        <?php 
                            $sth = $conn->prepare('SELECT id, baslik FROM ilceler WHERE `sehir_id` = :sehir_id');
                            $sth->bindParam('sehir_id', $musteri['sehir_id']);
                            $sth->execute();
                            $ilceler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4">
                            <select class="form-select form-select-lg js-example-basic-single" id="ilce_id" name="ilce_id" required>
                                <?php foreach ($ilceler as  $ilce) { ?>
                                    <option value="<?php echo $ilce['id']; ?>" <?php echo $ilce['id'] == $musteri['ilce_id'] ? 'selected':'';  ?>><?php echo $ilce['baslik']; ?></option>
                                <?php }?>
                            </select>
                            <label for="ilce_id" class="form-label">İlçe</label>
                        </div>

                        <div class="form-floating col-md-4">
                            <?php 
                                $sth = $conn->prepare('SELECT id, sektor_adi FROM sektorler WHERE firma_id =:firma_id ORDER BY sektor_adi ASC ');
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $sektorler = $sth->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <select class="form-select" name="sektor_id" id="sektor_id" required>
                                <?php foreach ($sektorler as $sektor) { ?>
                                    <option value="<?php echo $sektor['id']; ?>" <?php echo $sektor['id'] == $musteri['sektor_id'] ? 'selected' :''?>><?php echo $sektor['sektor_adi']; ?></option>
                                <?php } ?>
                            </select>
                            <label for="sektor_id" class="form-label">Sektör</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="cep_tel" value="<?php echo $musteri['cep_tel']; ?>" id="cep_tel" required>
                            <label for="cep_tel"  class="form-label">Cep Telefonu</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="sabit_hat"  value="<?php echo $musteri['sabit_hat']; ?>" id="sabit_hat" required>
                            <label for="sabit_hat" class="form-label">Sabit Hat</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="e_mail" value="<?php echo $musteri['e_mail']; ?>" id="e_mail" required>
                            <label for="e_mail" class="form-label">E-mail</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="vergi_numarasi" value="<?php echo $musteri['vergi_numarasi']; ?>" id="vergi_numarasi" required>
                            <label for="vergi_numarasi" class="form-label">Vergi Numarası</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="vergi_dairesi" id="vergi_dairesi" value="<?php echo $musteri['vergi_dairesi']; ?>" required>
                            <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                        </div>
                        <?php  
                            $sth = $conn->prepare('SELECT id, ad, soyad FROM personeller 
                                WHERE yetki_id IN(2,4) AND firma_id = :firma_id');
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating col-md-4 mb-2">
                            <select class="form-select" id="musteri_temsilcisi_id" name="musteri_temsilcisi_id" required>
                                <?php foreach ($personeller as $personel) { ?>
                                    <option value="<?php echo $personel['id']; ?>" <?php echo $personel['id'] == $musteri['musteri_temsilcisi_id'] ? 'selected' :''?>><?php echo $personel['ad'].' '.$personel['soyad']; ?></option>
                                <?php } ?>
                            </select>
                            <label for="musteri_temsilcisi_id" class="form-label">Musteri Temsilcisi</label>
                        </div>
                        
                        <div class="border-bottom border-secondary"></div>
                        
                        <h4>Yetkililer</h4>
                        <div id="yetkili-listesi">
                            <?php foreach($yetkililer as $index => $yetkili): ?>
                            <div class="yetkili-item<?php echo $index > 0 ? ' mt-2' : ''; ?>">
                                <div class="row align-items-center m-0">
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[<?php echo $index; ?>][adi]" value="<?php echo $yetkili['yetkili_adi']; ?>" required>
                                        <label>Adı Soyadı</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[<?php echo $index; ?>][cep]" value="<?php echo $yetkili['yetkili_cep']; ?>" required>
                                        <label>Cep Telefonu</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[<?php echo $index; ?>][mail]" value="<?php echo $yetkili['yetkili_mail']; ?>" required>
                                        <label>E-mail</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <select class="form-select" name="yetkililer[<?php echo $index; ?>][gorev]" required>
                                            <option value="Firma Sahibi" <?php echo $yetkili['yetkili_gorev'] == 'Firma Sahibi' ? 'selected' : ''; ?>>Firma Sahibi</option>
                                            <option value="Müdür" <?php echo $yetkili['yetkili_gorev'] == 'Müdür' ? 'selected' : ''; ?>>Müdür</option>
                                            <option value="Satın Alma" <?php echo $yetkili['yetkili_gorev'] == 'Satın Alma' ? 'selected' : ''; ?>>Satın Alma</option>
                                        </select>
                                        <label>Görevi</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[<?php echo $index; ?>][aciklama]" value="<?php echo $yetkili['yetkili_aciklama']; ?>">
                                        <label>Not</label>
                                    </div>
                                    <div class="form-group col-auto p-1">
                                        <input type="radio" id="default_yetkili<?php echo $index; ?>" name="default_yetkili" value="<?php echo $index; ?>" <?php echo $yetkili['is_default'] == 1 ? 'checked' : ''; ?>>
                                        <label for="default_yetkili<?php echo $index; ?>"> Varsayılan </label>
                                    </div>
                                    <?php if($index > 0): ?>
                                    <div class="form-group col-auto p-2">
                                        <button type="button" class="btn btn-danger yetkili-sil">Sil</button>
                                    </div>
                                    <?php else: ?>
                                    <div class="form-group col-auto p-2">
                                        <button type="button" id="yetkili-ekle" class="btn btn-primary">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if(empty($yetkililer)): ?>
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
                                        <input type="radio" id="default_yetkili0" name="default_yetkili" value="0" checked>
                                        <label for="default_yetkili0"> Varsayılan </label>
                                    </div>
                                    <div class="form-group col-auto p-2">
                                        <button type="button" id="yetkili-ekle" class="btn btn-primary">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <button class="btn btn-primary" type="submit" name="musteri_guncelle">
                                <i class="fa-solid fa-paper-plane"></i> GÜNCELLE
                            </button>
                            <a href="/index.php?url=musteriler" class="btn btn-secondary">
                                <i class="fa-regular fa-rectangle-xmark"></i> MÜŞTERİLER
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>                                    -->
        <script>
            $(function(){
                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });

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
        </script>

        <script>
            $(document).ready(function() {
                let yetkiliIndex = <?php echo count($yetkililer) > 0 ? count($yetkililer) : 1; ?>;

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
            });
        </script>