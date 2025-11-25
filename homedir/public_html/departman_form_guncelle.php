<?php 
    require_once "include/oturum_kontrol.php";

    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $sth = $conn->prepare('SELECT * FROM `departman_formlar` WHERE id = :id AND firma_id = :firma_id');
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $form = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($form)){
        require_once "include/yetkisiz.php";
        exit;
    }
?> 
    <div class="row mt-2">
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-align-justify"></i> Form Düzenle
                    </h5>
                    <div>
                        <div class="d-md-flex justify-content-end"> 
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <a href="javascript:window.history.back();" 
                                    class="btn btn-secondary"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom" 
                                    data-bs-title="Geri Dön"
                                >
                                    <i class="fa-solid fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="/index.php?url=departman_form_db_islem" method="POST" id="departman-form-guncelle-form">
                        <input type="hidden" name="id" value="<?php echo $form['id']; ?>">
                        <input type="hidden" name="departman_id" value="<?php echo $form['departman_id']; ?>">
                        <div class="form-floating col-md-12 mb-3">
                            <textarea class="form-control" id="konu" name="konu" style="height:100px !important" autofocus required><?php echo $form['konu']; ?></textarea>
                            <label for="konu" class="form-label">Konu</label>
                        </div>
                        <div class="form-floating col-md-12 mb-3">
                            <select name="gosterme_asamasi"  class="form-control" id="gosterme_asamasi" required>
                                <option selected disabled value="">Seçiniz</option>                
                                <option value="baslatta"    <?php echo $form['gosterme_asamasi'] == 'baslatta' ? 'selected':'';; ?>>Başta</option>                
                                <option value="bitirde"     <?php echo $form['gosterme_asamasi'] == 'bitirde' ? 'selected':''; ?>>Bitişte</option>                
                                <option value="her_durumda" <?php echo $form['gosterme_asamasi'] == 'her_durumda' ? 'selected':''; ?>>Her Zaman</option>   
                            </select>
                            <label for="gosterme_asamasi" class="form-label">Gösterma Zamanı</label>
                        </div>
                        <div class="form-floating col-md-12 mb-3">
                            <select name="zorunluluk_durumu"  class="form-control" id="zorunluluk_durumu" required>              
                                <option value="evet"    <?php echo $form['zorunluluk_durumu'] == 'evet' ? 'selected' : '';?>>Evet</option>                
                                <option value="hayır"   <?php echo $form['zorunluluk_durumu'] == 'hayır' ? 'selected' : '';?>>Hayır</option>                           
                            </select>
                            <label for="zorunluluk_durumu" class="form-label">Zorunluluk Durumu</label>
                        </div>
                        <div>
                            <button class="btn btn-warning" type="submit" name="form_guncelle" id="departman-form-guncelle-button">
                                <i class="fa-solid fa-pen-to-square"></i> GÜNCELLE
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> 
<?php  
    include_once "include/uyari_session_oldur.php"; 
?>
<script>
    $(function(){
        //formu gönderdiğinde buttonu pasif yapma
        $("#departman-form-guncelle-form").submit(function(){
            $("#departman-form-guncelle-button").addClass('disabled');
            return true;
        });
    });
</script> 