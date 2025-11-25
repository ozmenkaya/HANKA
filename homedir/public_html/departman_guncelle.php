<?php 
    include "include/oturum_kontrol.php";

    $id     = intval($_GET['id']);
    $sth    = $conn->prepare('SELECT id, departman,sorumlu_personel_idler FROM departmanlar 
                            WHERE id=:id AND firma_id = :firma_id');
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departman = $sth->fetch(PDO::FETCH_ASSOC);

    //echo "<pre>"; print_r($departman); exit;

    if(empty($departman))
    {
        require_once "include/yetkisiz.php";
        die();
    }

    $sth = $conn->prepare('SELECT id,ad,soyad FROM personeller WHERE firma_id = :firma_id ORDER BY `personeller`.`ad` ASC');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header">
            <h5>
                <?php echo $departman['departman'];?> Departmanı Güncelle
            </h5>
        </div>
        <div class="card-body pt-0">
            <form class="row g-3" action="/index.php?url=departman_db_islem" method="POST" id="departman-guncelle-form">
                <input type="hidden" name="id" value="<?php echo $departman['id']; ?>">
                
                <div class="form-floating col-md-6">
                    <input type="text" class="form-control" name="departman" id="departman" value="<?php echo $departman['departman'];?>" required >
                    <label for="departman" class="form-label">Departman</label>
                </div>
                <br>
                <div class="col-md-12">
                    <label for="sorumlu_personel_idler" class="form-label">Sorumlu Kişiler</label> 
                    <select class="form-select form-select-lg" id="sorumlu_personel_idler" name="sorumlu_personel_idler[]" multiple>
                        <?php $sorumlu_personel_idler = json_decode($departman['sorumlu_personel_idler']);?>
                        <option value="0">Seçiniz</option>
                        <?php foreach ($personeller as $key => $personel) { ?>
                            <option value="<?php echo $personel['id']; ?>" <?php echo in_array($personel['id'], $sorumlu_personel_idler) ? 'selected':'';?>>
                                <?php echo $personel['ad'].' '.$personel['soyad']; ?>
                            </option>
                        <?php }?>
                    </select>   
                </div>

                <div class="row mb-2 mt-2">
                    <div class="col-md-4 align-self-center">
                        <button class="btn btn-warning" type="submit" id="departman-guncelle-button" name="departman_guncelle">
                            <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                        </button>
                        <a href="/index.php?url=departman" class="btn btn-secondary" >
                            <i class="fa-solid fa-xmark"></i>  İPTAL
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php  
    include "include/uyari_session_oldur.php";
?>
<script>
    $(function(){
        $("#departman-guncelle-form").submit(function(){
            $("#departman-guncelle-button").addClass('disabled');
            return true;
        });
    });   
</script> 