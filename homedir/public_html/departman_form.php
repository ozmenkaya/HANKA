<?php
    require_once "include/oturum_kontrol.php";

    $departman_id = isset($_GET['id']) ? $_GET['id'] : 0;
    $sth = $conn->prepare('SELECT departman FROM `departmanlar` WHERE id = :id AND firma_id = :firma_id');
    $sth->bindParam('id', $departman_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departman = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($departman))
    {
        include_once "include/yetkisiz.php";
        exit;
    }

?>
<div class="row mt-2">
    <div class="col-md-12">
        <div class="card border-secondary">
            <div class="card-header d-flex justify-content-between border-secondary">
                <h5>
                    <i class="fa-solid fa-align-justify"></i>
                    <?php echo $departman['departman']; ?> Anketleri
                </h5>
                <div>
                    <div class="d-md-flex justify-content-end"> 
                        <div class="btn-group" role="group" aria-label="Basic example">
                            <a href="/index.php?url=departman" 
                                class="btn btn-secondary"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Geri Dön"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                            <button  class="btn  btn-primary" data-bs-toggle="modal" 
                                data-bs-target="#form-ekle-modal"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Ekle"
                            >
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <?php 
                    $sql = "SELECT id, konu, gosterme_asamasi, zorunluluk_durumu FROM `departman_formlar` WHERE firma_id = :firma_id AND departman_id = :departman_id";
                    $sth = $conn->prepare($sql);
                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                    $sth->bindParam('departman_id', $departman_id);
                    $sth->execute();
                    $formlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="table-responsive">
                    <table id="myTable" class="table table-hover" >
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Konu</th>
                                <th>Aşama</th>
                                <th>Zorunluluk Durumu</th>
                                <th class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($formlar as $key=>$form) { ?>
                                <tr>
                                    <th class="table-primary"><?php echo $key+1; ?></th>
                                    <td><?php echo $form['konu']; ?></td>
                                    <td>
                                        <?php if($form['gosterme_asamasi'] == 'baslatta'){ ?>
                                            <span class="badge text-bg-primary">BAŞLANGIÇTA</span>
                                        <?php }else if($form['gosterme_asamasi'] == 'bitirde'){ ?>
                                            <span class="badge text-bg-success">BİTİŞTE</span>
                                        <?php }else{?>
                                            <span class="badge text-bg-info">HER ZAMAN</span>
                                        <?php }?>
                                    </td>
                                    <td>
                                        <?php if($form['zorunluluk_durumu'] == 'evet'){ ?>
                                            <span class="badge text-bg-success">EVET</span>
                                        <?php }else{?> 
                                            <span class="badge text-bg-primary">HAYIR</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end"> 
                                            <div class="btn-group" role="group">
                                                <a href="/index.php?url=departman_form_guncelle&id=<?php echo $form['id']; ?>" 
                                                    class="btn btn-warning"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Düzenle"
                                                >
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="/index.php?url=siparis_ekle&islem=form_sil&id=<?php echo $form['id']; ?>&departman_id=<?php echo $departman_id; ?>" 
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
                            <?php }?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!--Departman Form Modal -->
<div class="modal fade" id="form-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"  aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">
                    <i class="fa-solid fa-align-justify"></i> Anket Ekle
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/index.php?url=departman_form_db_islem" method="POST" id="departman-form-ekle-form">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id; ?>">
                    <div class="form-floating col-md-12 mb-3">
                        <textarea class="form-control" id="konu" name="konu"  style="height:100px !important"  required></textarea>
                        <label for="konu" class="form-label">Konu</label>
                    </div>
                    <div class="form-floating col-md-12 mb-3">
                        <select name="gosterme_asamasi"  class="form-control" id="gosterme_asamasi" required>
                            <option selected disabled value="">Seçiniz</option>                
                            <option value="baslatta">Başta</option>                
                            <option value="bitirde">Bitişte</option>                
                            <option value="her_durumda">Her Zaman</option>                          
                        </select>
                        <label for="gosterme_asamasi" class="form-label">Gösterma Zamanı</label>
                    </div>
                    
                    <div class="form-floating col-md-12 mb-3">
                        <select name="zorunluluk_durumu"  class="form-control" id="zorunluluk_durumu" required>              
                            <option value="">Seçiniz</option>                
                            <option value="evet">Evet</option>                
                            <option value="hayır">Hayır</option>                           
                        </select>
                        <label for="zorunluluk_durumu" class="form-label">Zorunluluk Durumu</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg" type="submit" name="form_ekle" id="departman-form-ekle-button">
                            <i class="fa-solid fa-plus"></i> EKLE
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">KAPAT</button>
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
        $("#departman-form-ekle-form").submit(function(){
            $("#departman-form-ekle-button").addClass('disabled');
            return true;
        });

        //modal açıldığında focus yapma
        $('#form-ekle-modal').on('shown.bs.modal', function () {
            $('#konu').focus();
        });
    });
</script>