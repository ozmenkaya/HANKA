<?php 
    require_once "include/oturum_kontrol.php";

?>
        <div class="row mt-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-regular fa-comment-dots"></i> Geri Bildirim
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
                                <button class="btn btn-primary" 
                                    data-bs-placement="bottom" 
                                    data-bs-title="Geri Bildirimde Bulun"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#geri-bildirim-modal"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable"  class="table table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <?php if($_SESSION['yetki_id'] == SUPER_ADMIN_YETKI_ID){?>
                                        <th>Kimden</th>
                                    <?php } ?>
                                    <th>Başlık</th>
                                    <th>Tarih</th>
                                    <th>Önem Sırası</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $sql = 'SELECT geri_bildirim.*,
                                        personeller.ad, personeller.soyad
                                        FROM geri_bildirim 
                                        JOIN personeller ON personeller.id = geri_bildirim.kimden
                                        WHERE ust_id = 0 ';
                                    if($_SESSION['yetki_id'] != SUPER_ADMIN_YETKI_ID){
                                        $sql .= " AND kimden = :kimden";
                                    }
                                    $sql .= " ORDER BY id DESC";
                                    $sth = $conn->prepare($sql);
                                    if($_SESSION['yetki_id'] != SUPER_ADMIN_YETKI_ID){
                                        $sth->bindParam('kimden', $_SESSION['personel_id']);
                                    }
                                    $sth->execute();
                                    $geri_bildirimler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($geri_bildirimler as $index =>$geri_bildirim) { ?>
                                    <?php 
                                        $sql = 'SELECT geri_bildirim_id FROM `geri_bildirim_gorunum_durumu` 
                                            WHERE personel_id = :personel_id AND geri_bildirim_ust_id = :geri_bildirim_ust_id
                                            ORDER BY geri_bildirim_id DESC';
                                        $sth = $conn->prepare($sql);
                                        $sth->bindParam('personel_id', $_SESSION['personel_id']);
                                        $sth->bindParam('geri_bildirim_ust_id', $geri_bildirim['id']);
                                        $sth->execute();
                                        $geri_bildirim_gorunum_durum = $sth->fetch(PDO::FETCH_ASSOC);  
                                        

                                        $sql = 'SELECT COUNT(id) AS alt_bildirim_sayisi  FROM `geri_bildirim` 
                                        WHERE ust_id = :ust_id ';
                                        if(!empty($geri_bildirim_gorunum_durum )){
                                            $sql .= ' AND id > :id;';
                                        }
                                        $sth = $conn->prepare($sql);
                                        $sth->bindParam('ust_id', $geri_bildirim['id']);
                                        if(!empty($geri_bildirim_gorunum_durum )){
                                            $sth->bindParam('id', $geri_bildirim_gorunum_durum['geri_bildirim_id']);
                                        }
                                        $sth->execute();
                                        $alt_geri_bildirim = $sth->fetch(PDO::FETCH_ASSOC);   
                                        //echo $sql; 
                                    ?>
                                    <tr>
                                        <th class="table-primary"><?php echo $index + 1; ?></th>
                                        <?php if($_SESSION['yetki_id'] == SUPER_ADMIN_YETKI_ID){?>
                                            <td><?php echo $geri_bildirim['ad'].' '.$geri_bildirim['soyad'];?></td>
                                        <?php } ?>
                                        <td><?php echo $geri_bildirim['baslik']; ?></td>
                                        <td><?php echo date('d-m-Y H:i:s', strtotime($geri_bildirim['tarih'])); ?></td>
                                        <td>
                                            <?php if($geri_bildirim['onem_sirasi'] == 'az'){ ?>
                                                <span class="badge text-bg-success p-2"> AZ </span>
                                            <?php }else if($geri_bildirim['onem_sirasi'] == 'orta'){ ?>
                                                <span class="badge text-bg-secondary p-2">ORTA</span>
                                            <?php }else{?>
                                                <span class="badge text-bg-danger p-2">ÇOK</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <div class="d-md-flex justify-content-end"> 
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <a href="/index.php?url=geri_bildirim_detay&id=<?php echo $geri_bildirim['id']; ?>" 
                                                        class="btn btn-primary position-relative"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Detay"
                                                    >
                                                        <i class="fa-regular fa-comment-dots"></i>
                                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                                            <?php echo $alt_geri_bildirim['alt_bildirim_sayisi']; ?>
                                                        </span>
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

        <!-- Geri Dönüşüm Modal -->
        <div class="modal fade" id="geri-bildirim-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="staticBackdropLabel">Geri Bildirimde Bulun</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="/index.php?url=geri_bildirim_db_islem" class="row gx-3"  method="POST" enctype="multipart/form-data">
                            <div class="form-floating mb-2">
                                <select class="form-select" name="onem_sirasi" id="onem_sirasi" required>
                                    <option value="">Seçiniz</option>
                                    <option value="az">Az</option>
                                    <option value="orta">Orta</option>
                                    <option value="cok">Çok</option>
                                </select>
                                <label for="onem_sirasi" class="form-label">Öncelik Durumu</label>
                            </div>
                            <div class="form-floating mb-2">
                                <input type="text" class="form-control" name="baslik" id="baslik" required="">
                                <label for="baslik" class="form-label">Başlık</label>
                            </div>

                            <div class="form-floating mb-2">
                                <textarea class="form-control" name="icerik" id="icerik" cols="30" rows="10"></textarea>
                                <label for="icerik" class="form-label">İçerik</label>    
                            </div>

                            <!--<div class="form-floating mb-2">
                                <input class="form-control" type="file" id="dosyalar" name="dosyalar[]" multiple>
                                <label for="dosyalar" class="form-label">Dosya(lar)</label>
                            </div>-->

                            <div class="mb-2">
                                <div class="input-group">
                                    <label for="dosyalar" class="input-group-text">Dosya(lar)</label>
                                    <input type="file" class="form-control" id="dosyalar" name="dosyalar[]" multiple />    
                                </div>              
                            </div> 

                            <div class="form-floating d-flex justify-content-end mb-2">
                                <button  class="btn btn-primary" type="submit" name="ekle">
                                    <i class="fa-regular fa-square-plus"></i> EKLE
                                </button>
                                <button type="button" class="btn btn-secondary ms-1" data-bs-dismiss="modal">
                                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                                </button>
                            </div>
                            
                        </form>
                    </div>
                    <!--<<div class="modal-footer">
                       
                    </div>-->
                </div>
            </div>
        </div>
 
        <?php  
            include_once "include/uyari_session_oldur.php"; 
        ?>
        <script>
            $(function(){
                //modal başlık focus
                $('#geri-bildirim-modal').on('shown.bs.modal', function () {
                    $('#onem_sirasi').focus();
                });
            });
        </script>