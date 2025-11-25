<?php 
    include "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
    #echo "<pre>"; print_r($musteriler); exit;
?>
<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-chart-simple"></i> Türler
            </h5>
            <div >
                <?php //if(isset($_SESSION['sayfa_yetki_33']) && $_SESSION['sayfa_yetki_33'] == 1){  ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                        data-bs-target="#tur-ekle-modal"
                        data-bs-placement="bottom" 
                        data-bs-title="Tür Ekle"
                    >
                        <i class="fa-solid fa-plus"></i> 
                    </button>
                <?php //}?>
            </div>
        </div>
        <div class="card-body pt-0">
            <table id="myTable" class="table table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Tür</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($turler as $index=>$tur){ ?>
                        <tr>
                            <th class="table-primary"><?php echo $index + 1;?></th>
                            <td><?php echo $tur['tur']; ?></td>
                            <td>
                                <div class="d-md-flex justify-content-end"> 
                                    <div class="btn-group" role="group" aria-label="Basic example">                
                                        <a href="/index.php?url=turler_guncelle&id=<?php echo $tur['id']; ?>" type="button" 
                                            class="btn btn-warning"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            data-bs-title="Güncelle"
                                        >
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="/index.php?url=turler_db_islem&islem=tur_sil&id=<?php echo $tur['id']; ?>" 
                                                onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"  
                                                class="btn btn-danger"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Sil"
                                            >
                                                <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>

                                </div>
                            </td> 
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>      
</div>
<!-- Stok Kalem Ekle -->
<div class="modal fade" id="tur-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form  action="/index.php?url=turler_db_islem" method="POST" id="tur-ekle-form">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                        <i class="fa-solid fa-chart-simple"></i> Tür Ekle
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating p-1">
                        <input type="text" class="form-control" name="tur" id="tur" required >
                        <label for="tur" class="form-label">Tür</label>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit" name="tur_ekle" id="tur-ekle-button">
                        <i class="fa-regular fa-square-plus"></i> KAYDET
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-regular fa-rectangle-xmark"></i> İPTAL
                    </button>
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
        $("#tur-ekle-form").submit(function(){
            $("#tur-ekle-button").addClass('disabled');
            return true;
        });
    });
</script>
