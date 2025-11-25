<?php 
    $sth = $conn->prepare('SELECT * FROM stok_kalemleri WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $stok_kalemleri = $sth->fetchAll(PDO::FETCH_ASSOC);
    #echo "<pre>"; print_r($musteriler); exit;
?>

<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5> 
                <i class="fa-sharp fa-solid fa-layer-group"></i> Stok Kalemleri
            </h5>
            <div>
                <div class="d-flex justify-content-end"> 
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
                        <?php if(in_array(STOK_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                            <button type="button" class="btn btn-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#stok-kalem-ekle-modal"
                                data-bs-placement="bottom" 
                                data-bs-title="Ekle"
                            >
                                <i class="fa-solid fa-plus"></i> 
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table id="myTable" class="table table-hover" >
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Stok Adı</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stok_kalemleri as $index => $stok_kalem){ ?>
                        <tr>
                            <th class="table-primary"><?php echo $index + 1; ?></th>
                            <td>
                                <?php echo $stok_kalem['stok_kalem']; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end"> 
                                    <div class="btn-group" role="group" aria-label="Basic example">
                                        
                                        <?php if(in_array(STOK_GOR, $_SESSION['sayfa_idler'])){ ?>
                                            <a href="/index.php?url=stok_alt_kalem_deger&stok_id=<?php echo $stok_kalem['id']; ?>"  
                                                class="btn btn-secondary"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Değerler"
                                            >
                                                <i class="fa-solid fa-table-list"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if(in_array(STOK_DUZENLE, $_SESSION['sayfa_idler'])){ ?>
                                            <a href="/index.php?url=stok_kalem_guncelle&id=<?php echo $stok_kalem['id']; ?>"  
                                                class="btn btn-warning"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Güncelle"
                                            >
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if(in_array(STOK_SIL, $_SESSION['sayfa_idler'])){ ?>   
                                            <a href="/index.php?url=stok_kalem_db_islem&islem=stok_kalem_sil&id=<?php echo $stok_kalem['id']; ?>" 
                                                onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"  
                                                class="btn btn-danger"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Sil"
                                            >
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        <?php } ?>
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
</div>

<!-- Stok Kalem Ekle -->
<div class="modal fade" id="stok-kalem-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form  action="/index.php?url=stok_kalem_db_islem" method="POST" id="stok-kalem-ekle-form">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                        <i class="fa-sharp fa-solid fa-layer-group"></i>  Stok Kalem Ekle
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating col-md-12">
                        <input type="text" class="form-control" name="stok_kalem" id="stok_kalem" required >
                        <label for="stok_kalem" class="form-label">Stok Adı</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit" name="stok_kalem_ekle" id="stok-kalem-ekle-button">
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
        $("#stok-kalem-ekle-form").submit(function(){
            $("#stok-kalem-ekle-button").addClass('disabled');
            return true;
        });
    });
</script>

