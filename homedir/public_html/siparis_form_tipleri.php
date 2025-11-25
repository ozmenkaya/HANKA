<?php 
    include "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT * FROM siparis_form_tipleri');
    $sth->execute();
    $siparis_form_tipler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT * FROM siparis_form_tip_degerler WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis_form_tip_degerler = $sth->fetchAll(PDO::FETCH_ASSOC);

    //echo "<pre>"; print_r($siparis_form_tip_degerler); exit;
?>
    <div class="row">
        <div class="card mt-2">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-solid fa-chart-simple"></i> Sipariş Form Tipleri Ayarlama
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
            <div class="card-body pt-0">
                <form class="row g-3 needs-validation" action="/index.php?url=siparis_form_tipleri_db_islem" method="POST">
                    <?php foreach ($siparis_form_tipler as $index => $siparis_form_tip) { ?>
                        <?php 
                            $deger = 0;
                            $siparis_form_deger_tip_id = 0;
                            foreach ($siparis_form_tip_degerler  as $siparis_form_tip_deger ) {
                                if($siparis_form_tip_deger['siparis_form_tip_id'] == $siparis_form_tip['id']){
                                    $deger = $siparis_form_tip_deger['deger'];
                                    $siparis_form_deger_tip_id  = $siparis_form_tip_deger['id'];
                                    break;
                                }
                            }    
                        ?>
                        <div class="form-floating col-md-4">
                            <input type="hidden" name="siparis_form_tip_idler[]" value="<?php echo $siparis_form_tip['id'];?>">
                            <input type="hidden" name="siparis_form_tip_deger_idler[]" value="<?php echo $siparis_form_deger_tip_id;?>">
                            <select class="form-select" id="siparis_form_tip_deger" name="siparis_form_tip_degerler[]" required>
                                <option value="0" <?php echo $deger == 0 ? 'selected': '';?>>Kullanma</option>
                                <option value="1" <?php echo $deger == 1 ? 'selected': '';?>>Kullan</option>
                            </select>
                            <label for="siparis_form_tip_degerler" class="form-label">
                                <?php echo ($index+1).' - '.$siparis_form_tip['tip'];?>
                            </label>
                        </div>
                    <?php }?>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button class="btn btn-warning" type="submit" name="siparis_form_tip_guncelle">
                                <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php 
        include "include/uyari_session_oldur.php";
    ?>
