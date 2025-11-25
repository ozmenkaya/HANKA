<?php
    require_once "include/oturum_kontrol.php";

    $id = isset($_GET['id']) ? trim($_GET['id']) : 0 ;
    $id = intval($id);

    $sth = $conn->prepare('SELECT * FROM birimler WHERE id = :id AND firma_id = :firma_id');
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $birim = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($birim))
    {
        require_once "include/yetkisiz.php"; die();
    }
?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header">
            <h5>
                Birim Güncelle
            </h5>
        </div>
        <div class="card-body">
            <form  action="/index.php?url=birim_db_islem" method="POST" id="birim-guncelle-form" class="row g-3 needs-validation" >
                <input type="hidden" name="id" value="<?php echo $birim['id']; ?>">
                <div class="form-floating mb-3 col-md-6">
                    <input type="text" class="form-control" name="birim" id="birim" required value="<?php echo $birim['ad']; ?>">
                    <label for="birim" class="form-label">Birim</label>
                </div>
                <div class="form-floating mb-3">
                    <button class="btn btn-warning" type="submit" name="birim_guncelle" id="birim-guncelle-button">
                        <i class="fa-regular fa-square-plus"></i> GÜNCELLE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
    include_once "include/uyari_session_oldur.php"; 
?>
<script>
    $(function(){
        //formu gönderdiğinde buttonu pasif yapma
        $("#birim-guncelle-form").submit(function(){
            $("#birim-guncelle-button").addClass('disabled');
            return true;
        });

    });                                        
</script>