<?php
    require_once "include/oturum_kontrol.php";
    require_once "include/helper.php";

    $id = intval($_GET['id']);

    $sql = 'SELECT * FROM geri_bildirim WHERE id = :id';
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->execute();
    $geri_bildirim_kontrol = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($geri_bildirim_kontrol)){
        include_once "include/yetkisiz.php"; exit;
    }

    if($_SESSION['yetki_id'] != SUPER_ADMIN_YETKI_ID && $geri_bildirim_kontrol['kimden'] != $_SESSION['personel_id']){
        include_once "include/yetkisiz.php"; exit;
    }


    $sql = 'SELECT geri_bildirim.*, personeller.ad, personeller.soyad
            FROM geri_bildirim 
            JOIN personeller ON personeller.id = geri_bildirim.kimden
            WHERE (geri_bildirim.id = :id OR geri_bildirim.ust_id = :ust_id) ORDER BY id DESC';
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('ust_id', $id);
    $sth->execute();
    $geri_bildirimler = $sth->fetchAll(PDO::FETCH_ASSOC);
    //echo "<pre>"; print_R($geri_bildirimler); exit;


    $sql = "INSERT INTO geri_bildirim_gorunum_durumu(geri_bildirim_id, geri_bildirim_ust_id, personel_id) 
        VALUES(:geri_bildirim_id, :geri_bildirim_ust_id, :personel_id);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("geri_bildirim_id", $geri_bildirimler[0]['id']);
    $sth->bindParam("geri_bildirim_ust_id", $id);
    $sth->bindParam("personel_id", $_SESSION['personel_id']);
    $durum = $sth->execute();

?>
        <!-- <style>
            textarea{
                height: 150px !important;
            }
        </style> -->
        <div class="row mt-2">
            <div class="card border-secondary">
                <div class="card-header d-flex justify-content-between border-secondary">
                    <h5>
                        <i class="fa-regular fa-comment-dots"></i>  Geri Bilgirim Detay - Önem Önceliği:
                        <?php 
                            $onem_sirasi = $geri_bildirimler[0]['onem_sirasi'];
                        ?>
                        <?php if($onem_sirasi == 'az'){ ?>
                            <span class="badge text-bg-success p-2"> AZ </span>
                        <?php }else if($onem_sirasi == 'orta'){ ?>
                            <span class="badge text-bg-secondary p-2">ORTA</span>
                        <?php }else{?>
                            <span class="badge text-bg-danger p-2">ÇOK</span>
                        <?php } ?>
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
                <div class="card-body pt-0"> <!-- right -->
                    <?php //echo "<pre>";print_r($_SESSION); ?>
                    <div class="timeline">
                        <?php foreach ($geri_bildirimler as $key => $geri_bildirim) { ?>
                            <?php 
                                $sql = 'SELECT * FROM geri_bildirim_dosyalar WHERE geri_donusum_id = :geri_donusum_id';
                                $sth = $conn->prepare($sql);
                                $sth->bindParam('geri_donusum_id', $geri_bildirim['id']);
                                $sth->execute();
                                $geri_bildirim_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);    
                            ?>
                            <div class="geri-bildirim <?php echo $geri_bildirim['kimden'] == $_SESSION['personel_id'] ? 'right':'left';?>">
                                <div class="date"></div>
                                <i class="icon fa-regular fa-comment-dots"></i>
                                <div class="content">
                                    <h6 class="fw-bold text-primary">
                                        <?php if($geri_bildirim['kimden'] == $_SESSION['personel_id'] ){ ?>
                                            <i class="fa-regular fa-circle-user"></i>
                                            BEN
                                        <?php }else{ ?>
                                            <i class="fa-solid fa-circle-user"></i>
                                            <?php echo $geri_bildirim['ad'].' '.$geri_bildirim['soyad'] ; ?>
                                        <?php } ?>
                                    </h6>
                                    <h6 class="fw-bold text-danger">
                                        <i class="fa-solid fa-calendar-days"></i>
                                        <?php echo date('d-m-Y H:i:s', strtotime($geri_bildirim['tarih'])); ?>
                                    </h6>
                                    <h2>
                                        <i class="fa-solid fa-heading"></i>
                                        <?php echo $geri_bildirim['baslik'];?>
                                    </h2>
                                    <p>
                                        <i class="fa-regular fa-comment-dots"></i>
                                        <?php 
                                            echo str_replace(["\r\n","\r", "\n"], "<br>", $geri_bildirim['icerik']);
                                        ?>
                                    </p>
                                    <div class="mb-2 dosyalar">
                                        <?php foreach ($geri_bildirim_dosyalar as $key => $geri_bildirim_dosya) { ?>
                                            <?php 
                                                // $uzanti = pathinfo("dosyalar/geri-bildirim/{$geri_bildirim_dosya['ad']}", PATHINFO_EXTENSION);
                                                $storage = new DreamHostStorage($conn);
                                                $result_url  = $storage->getFileFromS3('geri_bildirim', $geri_bildirim_dosya['ad']);

                                                $uzanti = pathinfo($result_url, PATHINFO_EXTENSION);
                                            ?>
                                            <?php if($uzanti == 'pdf'){ ?>
                                                <a class="text-decoration-none pdf-modal-goster"  href="javascript:;"
                                                    data-href="<?php echo $result_url ?>"
                                                >
                                                    <img src="dosyalar/pdf.png" 
                                                        class="rounded img-thumbnai object-fit-fill" 
                                                        alt="" 
                                                        style="height:50px; min-height:50px; width:50px;"
                                                        
                                                    > 
                                                </a>
                                            <?php } else if(in_array($uzanti, ['doc','docx'])){ ?>
                                                <a class="text-decoration-none word-modal-goster" href="javascript:;"
                                                    data-href="<?php echo $result_url?>" 
                                                >
                                                    <img src="dosyalar/word.png" 
                                                        class="rounded img-thumbnai object-fit-fill" 
                                                        alt="" 
                                                        style="height:50px; min-height:50px; width:50px;"
                                                        
                                                    > 
                                                </a>
                                            <?php }else{?>
                                                <a class="text-decoration-none example-image-link"
                                                    href="<?php echo $result_url ?>" 
                                                    data-lightbox="example-set" 
                                                    data-title=""
                                                >
                                                    <img src="<?php echo $result_url; ?>" 
                                                        class="rounded img-fluid object-fit-fill mb-1 mt-1" 
                                                        style="height:50px; min-height:50px; width:50px;" 
                                                        loading="lazy"
                                                    >
                                                </a>
                                            <?php } ?>
                                        <?php }?>
                                    </div>
                                </div>
                            </div>
                        <?php }?>
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
                            <input type="hidden" name="ust_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="ait_id" value="<?php echo $geri_bildirim_kontrol['kimden']; ?>">
                            <input type="hidden" name="onem_sirasi" value="<?php echo $geri_bildirim_kontrol['onem_sirasi']; ?>">

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

                            <!--<div class="form-floating mb-2">
                                <button  class="btn btn-primary" type="submit" name="detaydan-ekle">
                                    <i class="fa-regular fa-square-plus"></i> EKLE
                                </button>
                            </div>-->

                            <div class="form-floating d-flex justify-content-end mb-2">
                                <button  class="btn btn-primary" type="submit" name="detaydan-ekle">
                                    <i class="fa-regular fa-square-plus"></i> EKLE
                                </button>
                                <button type="button" class="btn btn-secondary ms-1" data-bs-dismiss="modal">
                                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                                </button>
                            </div>
                        </form>
                    </div>
                    <!--<div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>-->
                </div>
            </div>
        </div>

        <!--  PDF Modal -->
        <div class="modal fade" id="arsiv-pdf-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">
                            <i class="fa-regular fa-file-pdf"></i> DOSYA
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="arsiv-pdf-modal-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php 
            include_once "include/uyari_session_oldur.php"; 
        ?>
        <script>
            $(function(){
                //PDF Modalda Göster
                $(document).on('click', '.pdf-modal-goster', function(){
                    const pdfURL = $(this).data('href');
                    $("#arsiv-pdf-modal-body").html(`
                        <div class="ratio ratio-16x9">
                            <iframe src="${pdfURL}"  allowfullscreen></iframe>
                        </div>
                    `);
                    $("#arsiv-pdf-modal").modal('show');
                });

                $(document).on('click', '.word-modal-goster', function(){
                    const pdfURL = $(this).data('href');
                    const protocol = window.location.protocol;
                    const hostname = window.location.hostname;
                    $("#arsiv-pdf-modal-body").html(`
                        <div class="ratio ratio-16x9">
                            <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${protocol}//${hostname}/${pdfURL}"  allowfullscreen></iframe>
                        </div>
                    `);
                    $("#arsiv-pdf-modal").modal('show');
                });

                //modal başlık focus
                $('#geri-bildirim-modal').on('shown.bs.modal', function () {
                    $('#onem_sirasi').focus();
                });
            });
        </script>