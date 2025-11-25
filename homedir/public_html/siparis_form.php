<?php  
    include "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT * FROM siparis_form WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $siparis_formlar = $sth->fetchAll(PDO::FETCH_ASSOC);
    //echo "<pre>"; print_r($turler); exit;

?> 
    <div class="row">
        <div class="card mt-2">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-solid fa-list"></i> Sipariş Formu
                </h5>
                <div >
                    <div class="d-md-flex justify-content-end"> 
                        <div class="btn-group" role="group" >
                            <a href="javascript:window.history.back();" 
                                class="btn btn-secondary"
                                data-bs-target="#departman-ekle-modal"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Geri Dön"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                data-bs-target="#tur-ekle-modal"
                                data-bs-placement="bottom" 
                                data-bs-title="Sipariş Form Ekle"
                            >
                                <i class="fa-solid fa-plus"></i> 
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <table id="myTable" class="table table-hover" >
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Değer</th>
                            <th>Türler</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($siparis_formlar as $index=>$siparis_form){ ?>
                            <?php 
                                $tur_idler = json_decode($siparis_form['tur_idler'], true);  
                                $tur_idler = implode(', ', $tur_idler );
                                $tur_idler = empty($tur_idler) ? '0' : $tur_idler;
                                $sth = $conn->prepare("SELECT tur FROM turler WHERE firma_id = :firma_id AND id IN($tur_idler)");
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $turler = $sth->fetchAll(PDO::FETCH_ASSOC);

                                //$sth->debugDumpParams();
                            ?>
                            <tr>
                                <th class="table-primary"><?php echo $index + 1;?></th>
                                <td><?php echo $siparis_form['deger']; ?></td>
                                <td>
                                    <?php foreach ($turler as $key => $tur) { ?>
                                        <span class="badge text-bg-primary p-2 mt-1"><?php echo $tur['tur']; ?></span>
                                    <?php }?>
                                    <?php if(empty($turler)){ ?>
                                        <span class="text-danger fw-bold p-2">-</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="d-md-flex justify-content-end"> 
                                        <div class="btn-group" role="group" aria-label="Basic example">                
                                            <a href="/index.php?url=siparis_form_guncelle&id=<?php echo $siparis_form['id']; ?>" type="button" 
                                                class="btn btn-warning"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Güncelle"
                                            >
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <a href="/index.php?url=siparis_form_db_islem&islem=siparis_form_sil&id=<?php echo $siparis_form['id']; ?>" 
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
    <!-- Sipari Değer Ekle -->
    <div class="modal fade" id="tur-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form  action="/index.php?url=siparis_form_db_islem" method="POST" id="siparis-deger-ekle-form">
                    <div class="modal-header">
                        <h5>Sipariş Form Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating p-1">
                            <input type="text" class="form-control" name="deger" id="deger" required >
                            <label for="deger" class="form-label">Değer</label>
                        </div>

                        <div class="form-group p-1">
                            <textarea class="form-control" name="options" placeholder="Eğer bir seçenek kutusu kullanmak isterseniz seçenekleri alt alta yazabilirsiniz."></textarea>
                        </div>

                        <?php 
                            $sth = $conn->prepare('SELECT * FROM turler WHERE firma_id = :firma_id');
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $turler = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <ul class="list-group m-1">
                            <?php foreach ($turler as $key => $tur) { ?>          
                                <li class="list-group-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" name="tur_id[]" type="checkbox" role="switch" 
                                            id="tur-<?php echo $tur['id']; ?>" value="<?php echo $tur['id']; ?>"
                                        >
                                        <label class="form-check-label" for="tur-<?php echo $tur['id']; ?>">
                                            <?php echo $tur['tur']; ?>
                                        </label>
                                    </div>
                                </li>
                            <?php }?>
                            <?php if(empty($turler)){?>
                                <li class="list-group-item list-group-item-danger d-flex justify-content-between fw-bold">
                                    <span>Lütfen Tür Ekleyiniz. </span>
                                    <a href="/index.php?url=turler" class="btn btn-success btn-sm">
                                        <i class="fa-solid fa-chart-simple"></i> Tür Ekle
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit" name="deger_ekle" id="siparis-deger-ekle-button" 
                            <?php echo empty($turler) ? 'disabled':'';?>
                        >
                            <i class="fa-regular fa-square-plus"></i> KAYDET
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-circle-xmark"></i> İPTAL
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
            $("#siparis-deger-ekle-form").submit(function(){
                $("#siparis-deger-ekle-button").addClass('disabled');
                return true;
            });
        });  
    </script> 

