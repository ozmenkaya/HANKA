<?php 
    include_once "include/oturum_kontrol.php";
    if(!in_array(TEDARIKCI_DUZENLE, $_SESSION['sayfa_idler'])){
        include "include/yetkisiz.php";
        die();
    }
    
    $tedarikci_id = intval($_GET['id']);

    $sth = $conn->prepare('SELECT * FROM tedarikciler WHERE id=:id AND firma_id = :firma_id');
    $sth->bindParam('id', $tedarikci_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $tedarikci = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($tedarikci))
    {
        include "include/yetkisiz.php";
        exit;
    }
?>
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" /> -->
        <div class="row mt-2 mb-4">
            <?php require_once "include/uyari_session.php";?>
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-parachute-box"></i> Tedarikçi Güncelle
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
                    <form class="row g-3 needs-validation" action="/index.php?url=tedarikci_db_islem" method="POST"> 
                        <input type="hidden" name="id" value="<?php echo $tedarikci['id'];?>">

                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="firma_adi" id="firma_adi" value="<?php echo $tedarikci['firma_adi']; ?>" required>
                            <label for="firma_adi" class="form-label">Firma Adı</label>
                        </div>
                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="email" id="email" value="<?php echo $tedarikci['email']; ?>" required>
                            <label for="email" class="form-label">Email</label>
                        </div>
                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="tedarikci_unvani" id="tedarikci_unvani"  value="<?php echo $tedarikci['tedarikci_unvani']; ?>"required>
                            <label for="tedarikci_unvani" class="form-label">Unvanı</label>
                        </div>
                        <div class="form-floating col-md-6">
                            <input type="text" class="form-control" name="tedarikci_adresi" id="tedarikci_adresi" value="<?php echo $tedarikci['tedarikci_adresi']; ?>" required>
                            <label for="tedarikci_adresi" class="form-label">Adresi</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="tedarikci_telefonu" id="tedarikci_telefonu" value="<?php echo $tedarikci['tedarikci_telefonu']; ?>" required>
                            <label for="tedarikci_telefonu" class="form-label">Telefonu</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="tedarikci_vergi_no" id="tedarikci_vergi_no" value="<?php echo $tedarikci['tedarikci_vergi_no']; ?>" required>
                            <label for="tedarikci_vergi_no" class="form-label">Vergi No</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <input type="text" class="form-control" name="tedarikci_vd" id="tedarikci_vd" value="<?php echo $tedarikci['tedarikci_vd']; ?>" required>
                            <label for="tedarikci_vd" class="form-label">Vergi Dairesi</label>
                        </div>
                        <div class="form-floating col-md-4">
                            <select class="form-select"  name="fason" id="fason" required>
                                <option  value="evet" <?php echo $tedarikci['fason'] == 'evet'? 'selected': ''; ?>>Evet</option>
                                <option  value="hayır" <?php echo $tedarikci['fason'] == 'hayır'? 'selected': ''; ?>>Hayır</option>
                            </select>
                            <label for="fason" class="form-label">Fason</label>
                        </div>
                        <div class="form-floating col-md-8">
                            <textarea class="form-control" name="tedarikci_aciklama"  id="tedarikci_aciklama"  cols="30" rows="10"><?php echo $tedarikci['tedarikci_aciklama']; ?></textarea>
                            <label for="tedarikci_aciklama" class="form-label">Açıklama</label>
                        </div>

                        <?php 
                            $sql = 'SELECT * FROM `stok_kalemleri` WHERE firma_id = :firma_id';
                            $sth = $conn->prepare($sql);
                            $sth->bindParam("firma_id", $_SESSION['firma_id']);
                            $sth->execute();
                            $stok_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);

                            $secili_stok_kalemler = json_decode($tedarikci['stok_kalem_idler'], true);
                            $secili_stok_kalemler = empty($secili_stok_kalemler) ? [] : $secili_stok_kalemler;
                        ?>
                        <div class="col-md-12 stok_kalem_idler" style="display: none;">
                            <label for="stok_kalem_idler" class="form-label text-danger fw-bold">Stok Kalemler</label>  
                            <select  class="form-select form-select-lg js-example-basic-single" id="stok_kalem_idler" name="stok_kalem_idler[]" multiple>
                                <?php foreach ($stok_kalemler as $key => $stok_kalem) { ?>
                                    <option value="<?php echo $stok_kalem['id']; ?>" 
                                        <?php echo in_array($stok_kalem['id'], $secili_stok_kalemler ) ? 'selected':'';?>
                                    >
                                        <?php echo $stok_kalem['stok_kalem']; ?>
                                    </option>
                                <?php }?>
                            </select>  
                        </div>

                        <?php 
                            $sql = 'SELECT id, departman FROM `departmanlar` WHERE firma_id = :firma_id';
                            $sth = $conn->prepare($sql);
                            $sth->bindParam("firma_id", $_SESSION['firma_id']);
                            $sth->execute();
                            $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);

                            $departman_idler_json = isset($tedarikci['departman_idler']) ? $tedarikci['departman_idler'] : null;
                            $secili_departmanlar = !empty($departman_idler_json) ? json_decode($departman_idler_json, true) : [];
                            $secili_departmanlar = empty($secili_departmanlar ) ? [] : $secili_departmanlar;
                        ?>
                        <div class="col-md-12 departman_idler" style="display: none;">
                            <label for="departman_idler" class="form-label text-danger fw-bold">Departmanlar</label>  
                            <select  class="form-select form-select-lg js-example-basic-single" id="departman_idler" name="departman_idler[]" multiple>
                                <?php foreach ($departmanlar as $key => $departman) { ?>
                                    <option value="<?php echo $departman['id']; ?>"
                                        <?php echo in_array($departman['id'], $secili_departmanlar ) ? 'selected':'';?>
                                    >
                                        <?php echo $departman['departman']; ?>
                                    </option>
                                <?php }?>
                            </select>          
                        </div>

                        <div class="col-md-2 align-self-center">
                            <button class="btn btn-warning"   type="submit" name="tedarikci_guncelle" >
                                <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                            </button>
                        </div>
                    </form>
                </div>
            </div>  
        </div>
    </main>
    <?php 
        require_once "include/uyari_session_oldur.php";
    ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function(){
            const fason_durum = $("#fason").val();
            if(fason_durum == 'evet'){
                $(".departman_idler").show();
            }else if(fason_durum == 'hayır'){
                $(".stok_kalem_idler").show();
            }

            $('.js-example-basic-single').select2({
                theme: 'bootstrap-5'
            });

            $("#tedarikci-ekle-form").submit(function(){
                $("#tedarikci-ekle-button").addClass('disabled');
                return true;
            });

            $("#fason").change(function(){
                const fason = $(this).val();
                
                if(fason == 'evet'){
                    $(".stok_kalem_idler").hide();
                    $(".departman_idler").show();
                }else if(fason == 'hayır'){
                    $(".stok_kalem_idler").show();
                    $(".departman_idler").hide();
                }
            });
        });
    </script>