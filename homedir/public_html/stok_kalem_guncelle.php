<?php 

include "include/oturum_kontrol.php";

$id = intval($_GET['id']);
$sth = $conn->prepare('SELECT id, stok_kalem FROM stok_kalemleri 
                        WHERE id=:id AND firma_id = :firma_id');
$sth->bindParam('id', $id);
$sth->bindParam('firma_id', $_SESSION['firma_id']);
$sth->execute();
$stok_kalemleri = $sth->fetch(PDO::FETCH_ASSOC);

//echo $id;
//echo "<pre>"; print_r($stok_kalemleri); exit;
?>

<div class="row mt-2">
    <div class="card">
        <div class="card-header">
            <h5>
                <i class="fa-sharp fa-solid fa-layer-group fs-4"></i> Stok Kalem Güncelle
            </h5>
        </div>
        <div class="card-body pt-0">
            <form class="row g-3 needs-validation" action="/index.php?url=stok_kalem_db_islem" method="POST">
                <input type="hidden" name="id" value="<?php echo $stok_kalemleri['id']; ?>">
                <div class="form-floating col-md-8">
                    <input type="text" class="form-control" name="stok_kalem" id="stok_kalem" value="<?php echo $stok_kalemleri['stok_kalem'];?>" required >
                    <label for="stok_kalem" class="form-label">Stok Adı</label>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <button class="btn btn-warning" type="submit" name="stok_kalem_guncelle">
                            <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                        </button>
                        <a href="/index.php?url=stok_kalem" class="btn btn-secondary" >
                            <i class="fa-regular fa-rectangle-xmark"></i> STOK KALEMLER
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
