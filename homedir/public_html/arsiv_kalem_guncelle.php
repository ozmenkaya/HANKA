<?php 
    try {
        $id = intval($_GET['id']);
        
        $sth = $conn->prepare('SELECT id, arsiv, departman_idler, arsiv_tur_id FROM arsiv_kalemler WHERE id=:id AND firma_id = :firma_id');
        
        $sth->bindParam('id', $id, PDO::PARAM_INT);
        $sth->bindParam('firma_id', $_SESSION['firma_id'], PDO::PARAM_INT);
        $sth->execute();
        $arsiv_kalem = $sth->fetch(PDO::FETCH_ASSOC);
        
        if (empty($arsiv_kalem)) {
            include "include/yetkisiz.php";
            exit;
        }
    } catch (PDOException $e) {
        error_log("PDO Hatası: " . $e->getMessage());
        
        header('HTTP/1.1 500 Internal Server Error');
        echo $e->getMessage();
        exit;
    } catch (Exception $e) {
        error_log("Genel Hata: " . $e->getMessage());
        
        header('HTTP/1.1 500 Internal Server Error');
        echo "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        exit;
    }
?>
<div class="row mt-2">
    <div class="card" style="width:50%">
        <div class="card-header">
            <h5>
                <i class="fa-regular fa-folder-open"></i>
                Güncelleme İşlemi
            </h5>
        </div>
            <form class="row g-3 needs-validation" action="/index.php?url=arsiv_kalem_db_islem" method="POST">
                <input type="hidden" name="id" value="<?php echo $arsiv_kalem['id']; ?>">
                <div class="form-floating col-md-12">
                    <input type="text" class="form-control" name="arsiv" id="arsiv" value="<?php echo $arsiv_kalem['arsiv'];?>" required >
                    <label for="arsiv" class="form-label">Arşiv</label>
                </div>
                <?php 
                    $sql = 'SELECT id, departman FROM `departmanlar` WHERE firma_id = :firma_id';
                    $sth = $conn->prepare($sql);
                    $sth->bindParam("firma_id", $_SESSION['firma_id']);
                    $sth->execute();
                    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);

                    $secili_departmanlar = json_decode($arsiv_kalem['departman_idler'], true);
                    $secili_departmanlar = empty($secili_departmanlar ) ? [] : $secili_departmanlar;
                ?>
                <div class="form-floating col-md-12 pt-1"> 
                    <select  class="select-margin form-select" id="departman_idler" name="departman_idler[]" multiple required>
                        <?php foreach ($departmanlar as $key => $departman) { ?>
                            <option value="<?php echo $departman['id']; ?>"
                                <?php echo in_array($departman['id'], $secili_departmanlar ) ? 'selected':'';?>
                            >
                                <?php echo $departman['departman']; ?>
                            </option>
                        <?php }?>
                    </select>     
                    <label style="z-index: 1" for="departman_idler" class="form-label">Departman Seçiniz</label>     
                </div>

                <div class="form-floating col-md-12">
                    <select class="form-select" id="arsiv_tur_id" name="arsiv_tur_id" required>
                        <option selected disabled value="">Seçiniz..</option>
                        <option  value="0" <?php echo $arsiv_kalem['arsiv_tur_id'] == 0 ? 'selected': '';?>>DİJİTAL</option>
                        <option  value="1" <?php echo $arsiv_kalem['arsiv_tur_id'] == 1 ? 'selected': '';?>>FİZİKSEL</option> 
                    </select>
                    <label for="arsiv_tur_id" class="form-label">Arsiv Türü</label>
                </div>
                <div class="col-md-4 align-self-center mb-2 mt-2">
                    <button class="btn btn-warning" type="submit" name="arsiv_kalem_guncelle">
                        <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>