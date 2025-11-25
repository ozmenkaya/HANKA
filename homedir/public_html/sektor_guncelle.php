<?php 
    include "include/oturum_kontrol.php";

    $id     = intval($_GET['id']);
    $sth = $conn->prepare('SELECT id, sektor_adi FROM sektorler WHERE id=:id AND firma_id = :firma_id');
    $sth->bindParam('id', $id);
    $sth->bindValue('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $sektor = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($sektor))
    {
        require_once "include/yetkisiz.php";
        die();
    }

?>
<div class="row mt-2"> 
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-chart-simple"></i> Sektör Güncelle
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
            <form class="row g-3 needs-validation" action="/index.php?url=sektor_db_islem" method="POST">
                <input type="hidden" name="id" value="<?php echo $sektor['id']; ?>">
                <div class="form-floating col-md-6">
                    <input type="text" class="form-control" name="sektor_adi" id="sektor_adi" value="<?php echo $sektor['sektor_adi'];?>" required >
                    <label for="sektor_adi" class="form-label">Sektor</label>
                </div>
                
                <div class="col-md-4 align-self-center">
                    <button class="btn btn-warning" type="submit" name="sektor_guncelle"
                        data-bs-toggle="tooltip" 
                        data-bs-placement="bottom" 
                        data-bs-title="Güncelle"
                    >
                        <i class="fa-regular fa-pen-to-square"></i> DÜZENLE 
                    </button>
                </div>
            </form>
        </div>
    </div> 
</div> 