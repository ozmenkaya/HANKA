<?php 
    include_once "include/oturum_kontrol.php";
    require_once "include/helper.php";

    $arsiv_id = intval($_GET['arsiv_id']);
    $sth = $conn->prepare('SELECT id, arsiv FROM arsiv_kalemler WHERE id=:id AND firma_id = :firma_id;');
    $sth->bindParam('id', $arsiv_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $arsiv = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($arsiv)){
        include "include/yetkisiz.php"; exit;
    }

?>
<div class="row mt-2">
    <div class="card border-3">
        <div class="card-header d-flex justify-content-between">
            <h5 id="arsiv">
                <i class="fa-regular fa-folder-open"></i>
                <b><?php echo $arsiv['arsiv']; ?></b> ARŞİVLERİ
            </h5>

            <div>
                <div class="d-flex justify-content-end"> 
                    <div class="btn-group align-self-end" role="group" >
                        <a href="javascript:window.history.back();" 
                            class="btn btn-secondary"
                            data-bs-target="#departman-ekle-modal"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom" 
                            data-bs-title="Geri Dön"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <a href="/index.php?url=arsiv_alt_db_islem&islem=arsiv_alt_excel&arsiv_id=<?php echo $arsiv_id;?>"  
                            class="btn btn-success"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom" 
                            data-bs-title="Excel"
                        >
                            <i class="fa-regular fa-file-excel"></i>
                        </a>
                        <a href="/index.php?url=arsiv_alt_ekle&arsiv_id=<?php echo $arsiv_id;?>"  
                            name="arsiv_alt_ekle" 
                            class="btn btn-primary"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom" 
                            data-bs-title="Alt Arşiv Ekle"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </a>
                        <a href="/index.php?url=arsiv_kalem"  
                            class="btn btn-secondary"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom" 
                            data-bs-title="Arşiv Kalemler"
                        >
                            <i class="fa-solid fa-table-list"></i>
                        </a>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="card-body pt-0">
            <table id="myTable" class="table table-hover table-sm" >
                <thead class="table-primary">
                    <tr>
                        <th class="text-center align-middle">#</th>
                        <th>Arşiv Kod</th>
                        <th>Müşteri Adı</th>
                        <th>Sipariş Adı</th>
                        <th>Ebat</th>
                        <th>Adet</th>
                        <th>Detay</th>
                        <th>Açıklama</th>
                        <th>Nerede</th>
                        <th>Görsel</th>
                        <th class="text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                        $sth = $conn->prepare('SELECT arsiv_altlar.id, arsiv_id, arsiv_altlar.kod, 
                        arsiv_altlar.ebat, arsiv_altlar.adet, arsiv_altlar.qr_kod,
                        arsiv_altlar.detay, arsiv_altlar.aciklama,arsiv_altlar.durum,
                        musteri.marka,
                        siparisler.isin_adi
                        FROM arsiv_altlar LEFT JOIN musteri ON musteri.id = arsiv_altlar.musteri_id
                        LEFT JOIN siparisler ON siparisler.id = arsiv_altlar.siparis_id
                        WHERE arsiv_id = :id AND arsiv_altlar.firma_id = :firma_id');
                        $sth->bindParam('id', $arsiv_id);
                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                        $sth->execute();
                        $arsiv_altlar = $sth->fetchAll(PDO::FETCH_ASSOC);

            
                    ?>
                    <?php foreach ($arsiv_altlar as $index=>$arsiv_alt) { ?>
                        <?php 
                            $satir_class = '';
                            if($arsiv_alt['durum'] == 'uretimde') $satir_class = 'table-info';    
                            if($arsiv_alt['durum'] == 'fasonda') $satir_class = 'table-danger';    
                            if($arsiv_alt['durum'] == 'fabrika_icinde_kullanmakta') $satir_class = 'table-primary';    
                        ?>
                        <tr class="<?php echo $satir_class; ?>">
                            <th class="table-primary text-center align-middle"><?php echo $index+1;?></th>
                            <th class="table-secondary align-middle"><?php echo $arsiv_alt['kod']; ?></th>
                            <td class="align-middle"><?php echo $arsiv_alt['marka']; ?></td>
                            <td class="align-middle"><?php echo $arsiv_alt['isin_adi']; ?></td>
                            <td class="align-middle"><?php echo $arsiv_alt['ebat']; ?></td>
                            <td class="text-center align-middle table-primary">
                                <span class="badge bg-success px-2">
                                    <?php echo $arsiv_alt['adet']; ?>
                                </span>
                            </td>
                            <td class="align-middle"><?php echo $arsiv_alt['detay']; ?></td>
                            <td class="align-middle"><?php echo $arsiv_alt['aciklama']; ?></td>
                            <td class="align-middle">
                                <?php if( $arsiv_alt['durum'] == 'arsivde'){?> 
                                    <span class="badge bg-success">ARŞİVDE</span>
                                <?php }else if($arsiv_alt['durum'] == 'uretimde'){?> 
                                    <span class="badge bg-info">ÜRETİMDE ŞUAN</span>
                                <?php }else if($arsiv_alt['durum'] == 'fasonda'){ ?> 
                                    <span class="badge bg-danger">FASONDA</span>
                                <?php }else if($arsiv_alt['durum'] == 'fabrika_icinde_kullanmakta'){ ?> 
                                    <span class="badge bg-primary">FABRIKDA KULLANILMAKTA</span>
                                <?php }else{?> 
                                    <span class="badge bg-danger">-</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php 
                                    $sth = $conn->prepare('SELECT ad  FROM arsiv_alt_dosyalar WHERE firma_id = :firma_id AND arsiv_alt_id = :arsiv_alt_id');
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('arsiv_alt_id', $arsiv_alt['id']);
                                    $sth->execute();
                                    $arsiv_dosyalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php if(!empty($arsiv_dosyalar)){?>
                                    <?php foreach ($arsiv_dosyalar as  $arsiv_dosya) { ?>
                                        <?php 
                                            $storage = new DreamHostStorage($conn);
                                            $result_url  = $storage->getFileFromS3('arsiv', $arsiv_dosya['ad']);

                                            $uzanti = pathinfo($result_url, PATHINFO_EXTENSION);
                                        ?>
                                        <?php if($uzanti == 'pdf'){ ?>
                                            <a class="text-decoration-none shadow-lg pdf-modal-goster" data-href="<?php echo $result_url; ?>" 
                                                href="javascript:;">
                                                <img src="dosyalar/pdf.png" 
                                                    class="rounded img-thumbnai object-fit-fill" 
                                                    style="height:50px; max-height:50px; width:50px;max-width:50px;"
                                                    loading="lazy"
                                                > 
                                            </a>
                                        <?php }else{?>
                                            <a class="text-decoration-none shadow-lg example-image-link" href="<?php echo $result_url; ?>" 
                                                        data-lightbox="example-set-<?php echo $index; ?>" data-title="">
                                                <img src="<?php echo $result_url; ?>" 
                                                    class="rounded img-thumbnai border border-secondary-subtle object-fit-fill mb-1" 
                                                    style="height:50px; max-height:50px; width:50px;max-width:50px;"
                                                    loading="lazy"
                                                >
                                            </a>
                                        <?php } ?>
                                    <?php }?>
                                <?php }else{?> 
                                    <h6 class="text-danger fw-bold">Dosya Yok</h6>    
                                <?php } ?>
                            </td>
                            
                            <td class="align-middle text-center">
                                <div class="d-flex justify-content-center"> 
                                    <div class="btn-group justify-content-end" role="group" aria-label="Basic example">
                                        <a 
                                            data-href="dosyalar/qr-code/<?php echo $arsiv_alt['qr_kod']?>"
                                            data-arsiv-kodu ="<?php echo $arsiv_alt['kod']; ?>";
                                            class="btn btn-secondary qr-kod-modal-goster"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            data-bs-title="QR Kod"
                                        >
                                            <i class="fa-solid fa-qrcode"></i>
                                        </a>
                                        
                                        <?php if(in_array(ARSIV_DUZENLE, $_SESSION['sayfa_idler'])){ ?>
                                            <a href="/index.php?url=arsiv_alt_guncelle&arsiv_alt_id=<?php echo $arsiv_alt['id']; ?>" 
                                                class="btn btn-warning"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Güncelle"
                                            >
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        <?php } ?> 

                                        <?php if(in_array(ARSIV_SIL, $_SESSION['sayfa_idler'])){ ?>
                                            <a href="/index.php?url=arsiv_alt_db_islem&islem=arsiv_alt_sil&arsiv_alt_id=<?php echo $arsiv_alt['id']; ?>&arsiv_id=<?php echo $arsiv_alt['arsiv_id']?>" 
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
                    <?php }?>
                </tbody>
            </table>
        </div>
    </div>   
</div>

<!-- Arşiv PDF Modal -->
<div class="modal fade" id="arsiv-pdf-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">
                    <i class="fa-regular fa-file-pdf"></i> ARŞİV PDF
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

<!--  QR Kod  Modal -->
<div class="modal fade" id="arsiv-qr-kod-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <i class="fa-regular fa-folder-open"></i> Arşiv Bilgi ve QR Kodu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="arsiv-qr-kod-modal-body">
                
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
        //QR Kod  Modalda Göster
        $(".qr-kod-modal-goster").click(function(){
            const pdfURL = $(this).data('href');
            const arsivKodu = $(this).data('arsiv-kodu');
            $("#arsiv-qr-kod-modal-body").html(`
                <div class="row mb-2">
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item active fw-bold" aria-current="true">ARŞİV BİLGİ</li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Arşiv Kodu</span>
                                <span>${arsivKodu}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-3 offset-md-1">
                        <div class="ratio ratio-1x1">
                            <img src="${pdfURL}" class="rounded img-thumbnai object-fit-fill" >
                        </div>
                    </div>
                </div>
            `);
            $("#arsiv-qr-kod-modal").modal('show');
        });

        //PDF Modalda Göster
        $(".pdf-modal-goster").click(function(){
            const pdfURL = $(this).data('href');
            $("#arsiv-pdf-modal-body").html(`
                <div class="ratio ratio-16x9">
                    <iframe src="${pdfURL}"  allowfullscreen></iframe>
                </div>
            `);
            $("#arsiv-pdf-modal").modal('show');
        });
    });
</script>