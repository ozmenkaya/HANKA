<?php 
    include "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT * FROM sektorler WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $sektorler = $sth->fetchAll(PDO::FETCH_ASSOC);
    #echo "<pre>"; print_r($musteriler); exit;
?>
<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-chart-simple"></i> Sektorler
            </h5>
            <div>
                <div class="d-flex justify-content-end"> 
                    <div class="btn-group" role="group">
                        <a href="javascript:window.history.back();" 
                                class="btn btn-secondary"
                                data-bs-target="#departman-ekle-modal"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Geri Dön"
                            >
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>

                        <?php if(in_array(SEKTOR_OLUSTUR, $_SESSION['sayfa_idler'])){  ?>
                            <button type="button" class="btn btn-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#sektor-ekle-modal"
                                data-bs-placement="bottom" 
                                data-bs-title="Sektor Ekle"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <table id="myTable" class="table table-hover" >
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Sektor</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sektorler as $index=>$sektor){ ?>
                        <tr>
                            <th class="table-primary"><?php echo $index+1;?></th>
                            <td><?php echo $sektor['sektor_adi']; ?></td>
                            <td>
                                <div class="d-flex justify-content-end"> 
                                    <div class="btn-group" role="group" aria-label="Basic example">                
                                        <?php if(in_array(SEKTOR_DUZENLE, $_SESSION['sayfa_idler'])){  ?>
                                            <a href="/index.php?url=sektor_guncelle&id=<?php echo $sektor['id']; ?>" type="button" 
                                                class="btn btn-warning"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Güncelle"
                                            >
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        <?php }?>
                                        <?php if(in_array(SEKTOR_SIL, $_SESSION['sayfa_idler'])){  ?>
                                                <a href="/index.php?url=sektor_db_islem&islem=sektor_sil&id=<?php echo $sektor['id']; ?>" 
                                                    onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"  
                                                    class="btn btn-danger"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Sil"
                                                >
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                        <?php }?>
                                        
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
<div class="modal fade" id="sektor-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form  action="/index.php?url=sektor_db_islem" method="POST" id="sektor-ekle-form">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                        <i class="fa-solid fa-chart-simple"></i> Sektor Ekle
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating p-1">
                        <input type="text" class="form-control" name="sektor_adi" id="sektor_adi" required >
                        <label for="sektor_adi" class="form-label">Sektor</label>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit" name="sektor_ekle" id="sektor-ekle-button">
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
        $('#sektor-ekle-modal').on('shown.bs.modal', function () {
            $('#sektor_adi').focus();
        });

        $("#sektor-ekle-form").submit(function(){
            $("#sektor-ekle-button").addClass('disabled');
            return true;
        });

    });
</script>