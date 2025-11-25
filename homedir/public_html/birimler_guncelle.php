<?php
    require_once "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT * FROM birimler WHERE id = :id AND firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);
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
                        <div class="modal-header">
                            <h5 class="modal-title" id="staticBackdropLabel">
                                <i class="fa-solid fa-ruler-vertical"></i>
                                Birim Ekle
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="birim" id="birim" required >
                                <label for="birim" class="form-label">Birim</label>
                            </div>
                        </div>
                        <div class="modal-footer">
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