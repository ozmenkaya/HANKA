<?php 
    include "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT * FROM departmanlar WHERE firma_id = :firma_id ORDER BY `departmanlar`.`departman` ASC');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);


    $sth = $conn->prepare('SELECT id,ad,soyad FROM personeller WHERE firma_id = :firma_id ORDER BY `personeller`.`ad` ASC');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);
 
    #echo "<pre>"; print_r($musteriler); exit;
?>
 
 <div class="row">
        <div class="card mt-2 border-secondary">
            <div class="card-header d-flex justify-content-between border-secondary">
                <h5 >
                    <i class="fa-solid fa-building"></i> Departmanlar
                </h5> 
                <div>
                    <div class="d-md-flex justify-content-end"> 
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
                            <?php if(in_array(DEPARTAN_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                                <button type="button" class="btn btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#departman-ekle-modal"
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
                                <th>Departman</th>
                                <th>Anket Sayısı</th>
                                <th>Sorumlu Personeller</th>
                                <th class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($departmanlar as $index => $departman){ ?>
                                <?php 
                                    $sql = 'SELECT COUNT(id) AS anket_sayisi FROM `departman_formlar` 
                                        WHERE departman_id = :departman_id';
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam("departman_id", $departman['id']);
                                    $sth->execute();
                                    $anket = $sth->fetch(PDO::FETCH_ASSOC);
                                ?>

                                <tr class="<?php echo $anket['anket_sayisi'] == 0 ? 'table-danger':'table-success'; ?>">
                                    <th class="table-primary"><?php echo $index + 1; ?></th>
                                    <th><?php echo $departman['departman']; ?></th>
                                    <th>
                                        <?php echo $anket['anket_sayisi']; ?> Adet
                                    </th>
                                    <th style="width:30%">
                                        <?php 
                                            $sorumlu_personel_idler = json_decode($departman['sorumlu_personel_idler'], true);
                                            $sorumlu_personel_idler = array_filter($sorumlu_personel_idler);
                                            $sorumlu_personeller    = [];
                                            if(!empty($sorumlu_personel_idler)){
                                                $sorumlu_personel_idler = implode(',',$sorumlu_personel_idler);
                                                $sql = "SELECT ad, soyad FROM personeller WHERE id IN({$sorumlu_personel_idler})";
                                                $sth = $conn->prepare($sql);
                                                $sth->execute();
                                                $sorumlu_personeller = $sth->fetchAll(PDO::FETCH_ASSOC);
                                            }
                                        ?>

                                        <?php foreach ($sorumlu_personeller as $sorumlu_personel) { ?>
                                            <span class="badge bg-secondary p-2 mb-1">
                                                <?php echo $sorumlu_personel['ad'].' '.$sorumlu_personel['soyad']; ?>
                                            </span>
                                        <?php }?>
                                    </th>
                                    <td>
                                        <div class="d-flex justify-content-end"> 
                                            <div class="btn-group" role="group">
                                                <a href="/index.php?url=departman_form&id=<?php echo $departman['id']; ?>" 
                                                    class="btn btn-info"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Anketler"
                                                >
                                                    <i class="fa-solid fa-align-justify"></i>
                                                </a>

                                                <a href="/index.php?url=departman_planlama&id=<?php echo $departman['id']; ?>"  
                                                    class="btn btn-secondary"
                                                    class="btn btn-warning"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Detay"
                                                >
                                                    <i class="fa-solid fa-table-list"></i>
                                                </a>
                                                <?php if(in_array(DEPARTAN_GUNCELLE, $_SESSION['sayfa_idler'])){ ?>
                                                    <a href="/index.php?url=departman_guncelle&id=<?php echo $departman['id']; ?>" 
                                                        class="btn btn-warning"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Düzenle"
                                                    >
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </a>
                                                <?php } ?>

                                                <?php if(in_array(DEPARTAN_SIL, $_SESSION['sayfa_idler']) && !$departman['kullanildi_mi']){ ?>
                                                    <a href="/index.php?url=departman_db_islem&islem=departman_sil&id=<?php echo $departman['id']; ?>" 
                                                        onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"  
                                                        class="btn btn-danger"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil"
                                                    >
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                <?php }else{?> 
                                                    <a href="javascript:;" 
                                                        class="btn btn-danger disabled"
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
    <!-- Departman Ekle Modal -->
    <div class="modal fade" id="departman-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h4 class="modal-title" id="myCenterModalLabel">Departman Ekle</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form action="/index.php?url=departman_db_islem" method="POST" id="departman-ekle-form" class="row g-3 needs-validation">
                            <div> 
                                <label for="departman" class="form-label">Departman</label>
                                <input type="text" class="form-control" name="departman" id="departman" required >                   
                            </div>
                            <div>
                                <label for="sorumlu_personel_idler" class="form-label fw-bold">Departmandan Sorumlu Kişiler</label>  
                                <select  class="form-select form-select-lg" id="sorumlu_personel_idler" name="sorumlu_personel_idler[]" multiple>
                                    <option value="0">Seçiniz</option>
                                    <?php foreach ($personeller as $key => $personel) { ?>
                                        <option value="<?php echo $personel['id']; ?>"><?php echo $personel['ad'].' '.$personel['soyad']; ?></option>
                                    <?php }?>
                                </select>  
                            </div>        
                            <div class="text-end">
                                <button type="submit" class="btn btn-success waves-effect waves-light" name="departman_ekle" id="departman-ekle-button">Kaydet</button>
                                <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                            </div>
                        </form>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>

    <!-- <div class="modal fade" id="departman-ekle-modal2" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form  action="/index.php?url=departman_db_islem" method="POST" id="departman-ekle-form" class="row g-3 needs-validation" >
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Departman Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="departman" id="departman" required >
                            <label for="departman" class="form-label">Departman</label>
                        </div>
                        <div class="mb-3">
                            <label for="sorumlu_personel_idler" class="form-label text-danger fw-bold">Departmandan Sorumlu Kişiler</label>  
                            <select  class="form-select form-select-lg" id="sorumlu_personel_idler" name="sorumlu_personel_idler[]" multiple>
                                <option value="0">Seçiniz</option>
                                <?php foreach ($personeller as $key => $personel) { ?>
                                    <option value="<?php echo $personel['id']; ?>"><?php echo $personel['ad'].' '.$personel['soyad']; ?></option>
                                <?php }?>
                            </select>     
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit" name="departman_ekle" id="departman-ekle-button">
                            <i class="fa-regular fa-square-plus"></i> KAYDET
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> İPTAL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div> -->
    </div>
    <?php 
        include "include/uyari_session_oldur.php";
    ?>
    <script>
        $(function(){
            //formu gönderdiğinde buttonu pasif yapma
            $("#departman-ekle-form").submit(function(){
                $("#departman-ekle-button").addClass('disabled');
                return true;
            });

            //modal açıldığında focus yapma
            $('#departman-ekle-modal').on('shown.bs.modal', function () {
                $('#departman').focus();
            });

        });                                        
    </script>
