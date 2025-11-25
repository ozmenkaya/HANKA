<?php 
    include_once "include/oturum_kontrol.php";

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $sth = $conn->prepare('SELECT id, marka, firma_unvani, adresi, ilce_id, sehir_id, ulke_id, sektor_id, cep_tel, 
    sabit_hat, e_mail, 
    aciklama, vergi_dairesi, vergi_numarasi, 
    musteri_temsilcisi_id, yetkili_adi, yetkili_cep, yetkili_mail, yetkili_gorev, aciklama, vade FROM musteri WHERE id=:id AND firma_id = :firma_id');
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

    $sth = $conn->prepare('SELECT id, baslik FROM ulkeler ORDER BY baslik');
    $sth->execute();
    $ulkeler = $sth->fetchAll(PDO::FETCH_ASSOC);
                    
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

    // Müşteri adreslerini çek
    $sth = $conn->prepare('SELECT * FROM musteri_adresleri WHERE musteri_id = :musteri_id ORDER BY is_default DESC');
    $sth->bindParam('musteri_id', $id);
    $sth->execute();
    $adresler = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    // Varsayılan adresi bul
    $default_adres_index = 0;
    foreach($adresler as $index => $adres) {
        if($adres['is_default'] == 1) {
            $default_adres_index = $index;
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
                <div class="card-body">
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

                        <!-- Adresler Bölümü -->
                        <div class="mt-4">
                        <div class="card mb-3 col-md-12 p-0">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Adresler</h6>
                                    <button type="button" class="btn btn-sm btn-primary" id="adres-ekle">Adres Ekle</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="adres-listesi">
                                    <?php if(count($adresler) > 0): ?>
                                        <?php foreach($adresler as $index => $adres): ?>
                                            <div class="adres-item card bg-light px-2 py-2 <?php echo $index > 0 ? 'mt-2' : ''; ?>">
                                                <div class="row align-items-center m-0">
                                                    <div class="form-floating col-md-2 p-1">
                                                        <label>Adres Türü</label>
                                                        <select class="form-select js-example-basic-single" name="adresler[<?php echo $index; ?>][adres_turu]" required>
                                                            <option disabled selected>Adres Türü</option>
                                                            <option value="Merkez" <?php echo $adres['adres_turu'] == 'Merkez' ? 'selected' : ''; ?>>Merkez</option>
                                                            <option value="Sevk" <?php echo $adres['adres_turu'] == 'Sevk' ? 'selected' : ''; ?>>Sevk</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-floating col-md-3 p-1">
                                                        <label>Adres Başlığı</label>
                                                        <input class="form-control" type="text" name="adresler[<?php echo $index; ?>][baslik]" value="<?php echo $adres['baslik']; ?>" required>
                                                    </div>
                                                    <div class="form-floating col-md-2 p-1">
                                                            <div class="row px-2 mt-2 flex-column">
                                                                <div class="form-group col-auto p-1 default-merkez-container" <?php echo $adres['adres_turu'] != 'Merkez' ? 'style="display:none;"' : ''; ?>>
                                                                    <input type="radio" id="default_adres<?php echo $index; ?>_merkez" name="default_adres_merkez" value="<?php echo $index; ?>" <?php echo $adres['adres_turu'] == 'Merkez' && $adres['is_default'] == 1 ? 'checked' : ''; ?>>
                                                                    <label for="default_adres<?php echo $index; ?>_merkez"> Varsayılan Merkez </label>
                                                                </div>
                                                                <div class="form-group col-auto p-1 default-sevk-container" <?php echo $adres['adres_turu'] != 'Sevk' ? 'style="display:none;"' : ''; ?>>
                                                                    <input type="radio" id="default_adres<?php echo $index; ?>_sevk" name="default_adres_sevk" value="<?php echo $index; ?>" <?php echo $adres['adres_turu'] == 'Sevk' && $adres['is_default'] == 1 ? 'checked' : ''; ?>>
                                                                    <label for="default_adres<?php echo $index; ?>_sevk"> Varsayılan Sevk </label>
                                                                </div>
                                                            </div>
                                                            
                                                    </div>
                                                    <?php if( $index > 0 ): ?>
                                                    <div class="form-group offset-md-4 col-md-1 p-1 d-flex justify-content-end">
                                                        <button type="button" class="btn btn-danger adres-sil">Sil</button>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="row align-items-center m-0">
                                                    <div class="form-floating col-md-2 p-1">
                                                        <label>Ülke</label>
                                                        <select class="form-select js-example-basic-single" name="adresler[<?php echo $index; ?>][ulke_id]" id="adres_ulke_<?php echo $index; ?>" required>
                                                            <option selected disabled>Ülke Seçiniz</option>
                                                            <option value="223">Türkiye</option>
                                                            <?php foreach($ulkeler as $ulke): ?>
                                                            <option value="<?= $ulke['id'] ?>" <?php echo $ulke['id'] == $adres['ulke_id'] ? 'selected' : ''; ?>><?= $ulke['baslik'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <?php 
                                                        $sth = $conn->prepare('SELECT id, baslik FROM sehirler WHERE `ulke_id` = :ulke_id');
                                                        $sth->bindParam('ulke_id', $adres['ulke_id']);
                                                        $sth->execute();
                                                        $adres_sehirler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                    ?>
                                                    <div class="form-floating col-md-2 p-1">
                                                        <label>Şehir</label>
                                                        <select class="form-select js-example-basic-single" name="adresler[<?php echo $index; ?>][sehir_id]" id="adres_sehir_<?php echo $index; ?>" required>
                                                            <option selected disabled>Şehir Seçiniz</option>
                                                            <?php foreach($adres_sehirler as $sehir): ?>
                                                            <option value="<?= $sehir['id'] ?>" <?php echo $sehir['id'] == $adres['sehir_id'] ? 'selected' : ''; ?>><?= $sehir['baslik'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <?php 
                                                        $sth = $conn->prepare('SELECT id, baslik FROM ilceler WHERE `sehir_id` = :sehir_id');
                                                        $sth->bindParam('sehir_id', $adres['sehir_id']);
                                                        $sth->execute();
                                                        $adres_ilceler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                    ?>
                                                    <div class="form-floating col-md-2 p-1">
                                                        <label>İlçe</label>
                                                        <select class="form-select js-example-basic-single" name="adresler[<?php echo $index; ?>][ilce_id]" id="adres_ilce_<?php echo $index; ?>" required>
                                                            <option selected disabled>İlçe Seçiniz</option>
                                                            <?php foreach($adres_ilceler as $ilce): ?>
                                                            <option value="<?= $ilce['id'] ?>" <?php echo $ilce['id'] == $adres['ilce_id'] ? 'selected' : ''; ?>><?= $ilce['baslik'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-floating col-md-6 p-1">
                                                        <label>Adres</label>
                                                        <input class="form-control" name="adresler[<?php echo $index; ?>][adres]" value="<?php echo $adres['adres']; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="adres-item card bg-light px-2 py-2">
                                            <div class="row align-items-center m-0">
                                                <div class="form-floating col-md-2 p-1">
                                                    <select class="form-select js-example-basic-single" name="adresler[0][adres_turu]" required>
                                                        <option disabled selected>Adres Türü</option>
                                                        <option value="Merkez" selected>Merkez</option>
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
                                                            <input type="radio" id="default_adres0_merkez" name="default_adres_merkez" value="0" checked>
                                                            <label for="default_adres0_merkez"> Varsayılan Merkez </label>
                                                        </div>
                                                        <div class="form-group col-auto p-1 default-sevk-container" style="display:none;">
                                                            <input type="radio" id="default_adres0_sevk" name="default_adres_sevk" value="0">
                                                            <label for="default_adres0_sevk"> Varsayılan Sevk </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row align-items-center m-0">
                                                <div class="form-floating col-md-2 p-1">
                                                    <select class="form-select js-example-basic-single" name="adresler[0][ulke_id]" id="adres_ulke_0" required>
                                                        <option selected disabled>Ülke Seçiniz</option>
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
                                                        <option selected disabled>Şehir Seçiniz</option>
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
                                                        <option selected disabled>İlçe Seçiniz</option>
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
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
                        <div class="form-floating col-md-4">
                            <input type="number" class="form-control" name="vade" id="vade" value="<?php echo $musteri['vade']; ?>" required>
                            <label for="vade" class="form-label">Vade</label>
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
                            
                            <?php
                            if( count($yetkililer) == 0 ): ?>
                            <div class="yetkili-item">
                                <div class="row align-items-center m-0">
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[0][adi]" value="<?php echo $musteri['yetkili_adi'];?>" required>
                                        <label>Adı Soyadı</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[0][cep]" value="<?php echo $musteri['yetkili_cep'];?>" required>
                                        <label>Cep Telefonu</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[0][mail]" value="<?php echo $musteri['yetkili_mail'];?>" required>
                                        <label>E-mail</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <select class="form-select" name="yetkililer[0][gorev]" required>
                                            <option value="Firma Sahibi" <?=$musteri['yetkili_gorev'] == 'Firma Sahibi' ?? 'selected';?>>Firma Sahibi</option>
                                            <option value="Müdür" <?=$musteri['yetkili_gorev'] == 'Müdür' ?? 'selected';?>>Müdür</option>
                                            <option value="Satın Alma" <?=$musteri['yetkili_gorev'] == 'Satın Alma' ?? 'selected';?>>Satın Alma</option>
                                        </select>
                                        <label>Görevi</label>
                                    </div>
                                    <div class="form-floating col-auto p-1">
                                        <input type="text" class="form-control" name="yetkililer[0][aciklama]" value="<?php echo $musteri['aciklama'];?>">
                                        <label>Not</label>
                                    </div>
                                    <div class="form-group col-auto p-1">
                                        <input type="radio" id="default_yetkili0" name="default_yetkili" value="1" checked>
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
                            <input type="hidden" name="musteri_guncelle" value="1">
                            <button class="btn btn-primary" type="submit" name="musteri_guncelle_btn">
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
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>                                   

        <script>
            $(function(){
                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });

                // Ülke ve şehir seçimi için event handler'lar
                for (let i = 0; i <= <?php echo count($adresler); ?>; i++) {
                    $(`#adres_ulke_${i}`).on('change', function() {
                        const ulkeId = $(this).val();
                        const sehirSelect = $(`#adres_sehir_${i}`);
                        
                        $.ajax({
                            url: "/index.php?url=ulke_il_ilce_kontrol&ulke_id=" + ulkeId,
                            dataType: "JSON",
                            success: function(sehirler) {
                                let sehirler_HTML = "<option selected disabled>İl Seçiniz</option>";
                                
                                for(const sehir of sehirler) {
                                    sehirler_HTML += `
                                        <option value="${sehir.id}">${sehir.baslik}</option>
                                    `;
                                }
                                sehirSelect.html(sehirler_HTML);
                            }
                        });
                    });
                    
                    $(`#adres_sehir_${i}`).on('change', function() {
                        const sehirId = $(this).val();
                        const ilceSelect = $(`#adres_ilce_${i}`);
                        
                        $.ajax({
                            url: "/index.php?url=ulke_il_ilce_kontrol&sehir_id=" + sehirId,
                            dataType: "JSON",
                            success: function(ilceler) {
                                let ilceler_HTML = "<option selected disabled>İlçe Seçiniz</option>";
                                
                                for(const ilce of ilceler) {
                                    ilceler_HTML += `
                                        <option value="${ilce.id}">${ilce.baslik}</option>
                                    `;
                                }
                                ilceSelect.html(ilceler_HTML);
                            }
                        });
                    });
                }
                
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

        <script>
            $(document).ready(function() {
                let adresIndex = <?php echo count($adresler) > 0 ? count($adresler) : 1; ?>;

                $('#adres-ekle').on('click', function() {
                    const adresIndex = $('.adres-item').length;
                    
                    // Yeni adres satırını oluştur
                    const newAdresHtml = `
                        <div class="adres-item card bg-light px-2 py-2 mt-2">
                            <div class="row align-items-center m-0">
                                <div class="form-floating col-md-2 p-1">
                                    <select class="form-select" name="adresler[${adresIndex}][adres_turu]" required>
                                        <option value="Merkez" selected>Merkez</option>
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
                                    <select class="form-select" name="adresler[${adresIndex}][ulke_id]" id="adres_ulke_${adresIndex}" required>
                                        <option selected disabled>Ülke Seçiniz</option>
                        <option value="223">Türkiye</option>
                                        <?php foreach($ulkeler as $ulke): ?>
                                        <option value="<?= $ulke['id'] ?>"><?= $ulke['baslik'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label>Ülke</label>
                                </div>
                                <div class="form-floating col-md-2 p-1">
                                    <select class="form-select" name="adresler[${adresIndex}][sehir_id]" id="adres_sehir_${adresIndex}" required>
                                        <option selected disabled>İl Seçiniz</option>
                                    </select>
                                    <label>İl</label>
                                </div>
                                <div class="form-floating col-md-2 p-1">
                                    <select class="form-select" name="adresler[${adresIndex}][ilce_id]" id="adres_ilce_${adresIndex}" required>
                                        <option selected disabled>İlçe Seçiniz</option>
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
                    
                    // Yeni adres satırını ekle
                    $('#adres-listesi').append(newAdresHtml);
                    
                    // Select2 için yeni eklenen select elementlerini initialize et
                    // Bu satır tamamen kaldırılıyor çünkü CSS hatalarına neden oluyor
                    $('.form-select').select2({
                        theme: 'bootstrap-5'
                    });

                    // Adres türü değiştiğinde varsayılan seçeneklerini göster/gizle
                    $(`select[name="adresler[${adresIndex}][adres_turu]"]`).on('change', function() {
                        const adresTuru = $(this).val();
                        const adresItem = $(this).closest('.adres-item');
                        
                        if (adresTuru === 'Merkez') {
                            adresItem.find('.default-merkez-container').show();
                            adresItem.find('.default-sevk-container').hide();
                            adresItem.find('input[name="default_adres_sevk"]').prop('checked', false);
                        } else if (adresTuru === 'Sevk') {
                            adresItem.find('.default-merkez-container').hide();
                            adresItem.find('.default-sevk-container').show();
                            adresItem.find('input[name="default_adres_merkez"]').prop('checked', false);
                        }
                    });

                    // Yeni eklenen adres için ülke değişikliği event listener'ı ekle
                    $(`#adres_ulke_${adresIndex}`).on('change', function() {
                        const ulkeId = $(this).val();
                        const sehirSelect = $(`#adres_sehir_${adresIndex}`);
                        
                        // Şehirleri getir
                        $.ajax({
                            url: "/index.php?url=ulke_il_ilce_kontrol&ulke_id=" + ulkeId,
                            dataType: "JSON",
                            success: function(sehirler) {
                                let sehirler_HTML = "<option selected disabled>İl Seçiniz</option>";

                                for(const sehir of sehirler)
                                {
                                    sehirler_HTML += `
                                        <option value="${sehir.id}">${sehir.baslik}</option>
                                    `;
                                }
                                sehirSelect.html(sehirler_HTML);
                            }
                        });
                    });
                    
                    // Yeni eklenen adres için şehir değişikliği event listener'ı ekle
                    $(`#adres_sehir_${adresIndex}`).on('change', function() {
                        const sehirId = $(this).val();
                        const ilceSelect = $(`#adres_ilce_${adresIndex}`);
                        
                        $.ajax({
                            url         : "/index.php?url=ulke_il_ilce_kontrol&sehir_id=" + sehirId,
                            dataType    : "JSON",
                            success     : function(ilceler){
                                let ilceler_HTML =  "<option selected disabled>İlçe Seçiniz</option>";
                                for(const ilce of ilceler)
                                {
                                    ilceler_HTML += `
                                        <option value="${ilce.id}">${ilce.baslik}</option>
                                    `;
                                }

                                ilceSelect.html(ilceler_HTML);
                            }
                        });
                    });
                });
                
                // Adres türü değiştiğinde varsayılan seçeneklerini göster/gizle
                $('#adres-listesi').on('change', 'select[name*="[adres_turu]"]', function() {
                    const adresItem = $(this).closest('.adres-item');
                    const adresTuru = $(this).val();
                    
                    // Varsayılan seçeneklerini göster/gizle
                    if (adresTuru === 'Merkez') {
                        adresItem.find('.default-merkez-container').show();
                        adresItem.find('.default-sevk-container').hide();
                        // Sevk varsayılan seçimi iptal et
                        adresItem.find('input[name="default_adres_sevk"]').prop('checked', false);
                    } else if (adresTuru === 'Sevk') {
                        adresItem.find('.default-merkez-container').hide();
                        adresItem.find('.default-sevk-container').show();
                        // Merkez varsayılan seçimi iptal et
                        adresItem.find('input[name="default_adres_merkez"]').prop('checked', false);
                    }
                });
                
                // Adres silme işlemi - delegasyon ile
                $('#adres-listesi').on('click', '.adres-sil', function() {
                    const adresItem = $(this).closest('.adres-item');
                    const adresTuru = adresItem.find('select[name*="[adres_turu]"]').val();
                    
                    // Eğer bu adres varsayılan olarak seçiliyse, silmeye izin verme
                    const isDefaultMerkez = adresItem.find('input[name="default_adres_merkez"]').prop('checked');
                    const isDefaultSevk = adresItem.find('input[name="default_adres_sevk"]').prop('checked');
                    if (isDefaultMerkez || isDefaultSevk) {
                        alert('Varsayılan adres silinemez! Önce başka bir adresi varsayılan olarak seçin.');
                        return;
                    }
                    
                    // Adresi sil
                    adresItem.remove();
                    
                    // Adres indekslerini güncelle
                    updateAdresIndexes();
                });
                
                // Adres indekslerini güncelleme fonksiyonu
                function updateAdresIndexes() {
                    $('.adres-item').each(function(index) {
                        const adresItem = $(this);
                        
                        // Adres türü select
                        adresItem.find('select[name*="[adres_turu]"]').attr('name', `adresler[${index}][adres_turu]`);
                        
                        // Ülke select
                        const ulkeSelect = adresItem.find('select[name*="[ulke_id]"]');
                        ulkeSelect.attr('name', `adresler[${index}][ulke_id]`);
                        ulkeSelect.attr('id', `adres_ulke_${index}`);
                        
                        // Şehir select
                        const sehirSelect = adresItem.find('select[name*="[sehir_id]"]');
                        sehirSelect.attr('name', `adresler[${index}][sehir_id]`);
                        sehirSelect.attr('id', `adres_sehir_${index}`);
                        
                        // İlçe select
                        const ilceSelect = adresItem.find('select[name*="[ilce_id]"]');
                        ilceSelect.attr('name', `adresler[${index}][ilce_id]`);
                        ilceSelect.attr('id', `adres_ilce_${index}`);
                        
                        // Adres input
                        adresItem.find('textarea[name*="[adres]"]').attr('name', `adresler[${index}][adres]`);
                        
                        // Varsayılan radio button
                        const radioBtnMerkez = adresItem.find('input[name="default_adres_merkez"]');
                        radioBtnMerkez.attr('name', 'default_adres_merkez');
                        radioBtnMerkez.attr('id', `default_adres${index}_merkez`);
                        radioBtnMerkez.val(index);
                        adresItem.find('label[for*="default_adres_merkez"]').attr('for', `default_adres${index}_merkez`);
                        
                        const radioBtnSevk = adresItem.find('input[name="default_adres_sevk"]');
                        radioBtnSevk.attr('name', 'default_adres_sevk');
                        radioBtnSevk.attr('id', `default_adres${index}_sevk`);
                        radioBtnSevk.val(index);
                        adresItem.find('label[for*="default_adres_sevk"]').attr('for', `default_adres${index}_sevk`);
                    });
                }
                
                // Form gönderilmeden önce kontrol
                $('form').on('submit', function(e) {
                    // En az bir Merkez adresi olmalı
                    const hasMerkezAdres = $('.adres-item').find('select[name*="[adres_turu]"] option[value="Merkez"]:selected').length > 0;
                    if (!hasMerkezAdres) {
                        e.preventDefault();
                        alert('En az bir Merkez adresi olmalıdır!');
                        return false;
                    }
                    
                    // En az bir Sevk adresi olmalı
                    const hasSevkAdres = $('.adres-item').find('select[name*="[adres_turu]"] option[value="Sevk"]:selected').length > 0;
                    if (!hasSevkAdres) {
                        e.preventDefault();
                        alert('En az bir Sevk adresi olmalıdır!');
                        return false;
                    }
                    
                    // Varsayılan adres kontrolleri
                    const hasDefaultMerkez = $('input[name="default_adres_merkez"]:checked').length > 0;
                    if (!hasDefaultMerkez) {
                        e.preventDefault();
                        alert('Lütfen bir varsayılan Merkez adresi seçin!');
                        return false;
                    }
                    
                    const hasDefaultSevk = $('input[name="default_adres_sevk"]:checked').length > 0;
                    if (!hasDefaultSevk) {
                        e.preventDefault();
                        alert('Lütfen bir varsayılan Sevk adresi seçin!');
                        return false;
                    }
                    
                    return true;
                });
            });
        </script>