<?php  
    include_once "include/oturum_kontrol.php";

    if(!in_array(MUSTERI_GOR, $_SESSION['sayfa_idler']))
    {
        require_once "include/yetkisiz.php";
        die();
    } 

    if($_SESSION['yetki_id'] == ADMIN_YETKI_ID)
    {
        $sth = $conn->prepare('SELECT musteri.id, musteri.marka, musteri.firma_unvani, musteri.yetkili_mail,musteri.yetkili_cep,
        personeller.ad, personeller.soyad  
        FROM musteri LEFT JOIN personeller ON musteri.musteri_temsilcisi_id = personeller.id 
        WHERE  musteri.firma_id = :firma_id');
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
    }
    else 
    {
        $sth = $conn->prepare('SELECT musteri.id, musteri.marka, musteri.firma_unvani, musteri.yetkili_mail,musteri.yetkili_cep,
        personeller.ad, personeller.soyad  
        FROM musteri LEFT JOIN personeller ON musteri.musteri_temsilcisi_id = personeller.id 
        WHERE musteri.musteri_temsilcisi_id = :musteri_temsilcisi_id 
            AND musteri.firma_id = :firma_id ');
        $sth->bindParam('musteri_temsilcisi_id', $_SESSION['personel_id']);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
    }

    $musteriler = $sth->fetchAll(PDO::FETCH_ASSOC);

?>   
        
</style>
    <div class="card mt-2 border-secondary">
        <div class="card-header d-md-flex justify-content-between border-secondary">
            <h5>
                <i class="fa-solid fa-users"></i> Müşteriler 
                <span class="text-primary fw-bold fs-6">(<?php echo count($musteriler); ?> Kişi)</span>
            </h5>
            <h5>
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="text-primary fw-bold fs-6" id="is-sayisi"></span>
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
                        <a href="/index.php?url=musteri_db_islem&islem=musteri_excel" 
                            class="btn btn-success"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="bottom" 
                            data-bs-title="Excel"
                        >
                            <i class="fa-regular fa-file-excel"></i>
                        </a>
                        <?php if(in_array(MUSTERI_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                            <a href="/index.php?url=musteri_ekle" class="btn btn-primary" 
                                data-bs-toggle="tooltip" 
                                data-bs-placement="bottom" 
                                data-bs-title="Müşteri Ekle"
                            >
                                <i class="fa-solid fa-user-plus"></i>
                            </a>
                        <?php }?>
                    </div>
                </div>
            </div>	
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table id="myTable" class="table table-hover table-striped">
                    <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Marka</th>
                        <th>Firma Ünvanı</th>
                        <th>M.Temsilcisi</th>
                        <th><i class="fa-regular fa-envelope"></i> Y. Email</th>
                        <th><i class="fa-solid fa-phone"></i> Y. Tel</th>
                        <th class="text-end">İş</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php $toplam_is_sayisi = 0; ?>
                        <?php foreach($musteriler as $key=>$musteri){ ?>
                            <?php 
                                $sth = $conn->prepare("SELECT COUNT(*) AS toplam_is FROM siparisler 
                                        WHERE musteri_id = {$musteri['id']} AND firma_id =:firma_id AND islem != 'iptal'");
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $siparis = $sth->fetch(PDO::FETCH_ASSOC);    
                            ?>
                            <tr class="musteri-row" data-musteri-id="<?php echo $musteri['id']; ?>" style="cursor: pointer;">
                                <th class="table-primary align-middle text-center"><?php echo $key + 1; ?> </th>
                                <td class="align-middle"><?php echo $musteri['marka']; ?></td>
                                <td class="align-middle"><?php echo $musteri['firma_unvani']; ?></td>
                                <td class="align-middle"><?php echo $musteri['ad'].' '.$musteri['soyad']; ?></td>
                                <td class="align-middle">
                                    
                                    <i class="fa-regular fa-envelope"></i> 
                                    <?php echo $musteri['yetkili_mail']; ?>
                                    
                                </td>
                                <th class="align-middle"> 
                                    <span class="badge bg-soft-danger text-danger" style="padding-top:5px!important">
                                         <a href="tel:<?php echo $musteri['yetkili_mail']; ?>">
                                            <i class="fa-solid fa-phone"></i>  
                                            <?php echo $musteri['yetkili_cep'] ; ?>
                                         </a>  
                                    </span>
                                </th>
                                <th class="text-end align-middle">
                                    <?php echo $siparis['toplam_is'] ; ?> Adet
                                </th>
                                <td class="text-center align-middle">
                                        <div class="btn-group custom-dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <?php if(in_array(SIPARIS_EKLE, $_SESSION['sayfa_idler'])){ ?>
                                                    <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparis_ekle&musteri_id=<?php echo $musteri['id']; ?>">Sipariş Ekle</a>
                                                    <div class="dropdown-divider"></div>
                                                <?php } ?>
                                                <?php if(in_array(SIPARIS_GOR, $_SESSION['sayfa_idler'])){ ?>
                                                    <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparis&musteri_id=<?php echo $musteri['id']; ?>">Sipariş Listesi</a>
                                                    <div class="dropdown-divider"></div>
                                                <?php } ?>
                                                <?php if(in_array(MUSTERI_GUNCELLE, $_SESSION['sayfa_idler'])){ ?>
                                                    <a class="dropdown-item pt-0 pb-0" href="/index.php?url=musteri_guncelle&id=<?php echo $musteri['id']; ?>">Güncelle</a>
                                                    <div class="dropdown-divider"></div>
                                                <?php } ?>
                                                <?php if(in_array(MUSTERI_GUNCELLE, $_SESSION['sayfa_idler'])){ ?>
                                                    <?php if($siparis['toplam_is'] == 0){?>
                                                        <a class="dropdown-item pt-0 pb-0" href="/index.php?url=musteri_db_islem&islem=musteri_sil&id=<?php echo $musteri['id']; ?>">Sil</a>
                                                    <?php }else{ ?>
                                                       <a class="dropdown-item custom-disabled-item pt-0 pb-0" href="javascript:;" aria-disabled="true">
                                                            Sil
                                                       </a>
                                                    <?php }?>
                                                <?php } ?>
                                            </div>
                                        </div> 
                                </td> 
                            </tr>
                            <?php $toplam_is_sayisi += $siparis['toplam_is']; ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> 
<?php  
    include_once "include/uyari_session_oldur.php";
?>
<script>
    $(function(){
        $("#is-sayisi").text(`Toplam : (<?php echo $toplam_is_sayisi; ?> İş)`);

        // Satıra tıklandığında sipariş listesine git
        $(".musteri-row").on("click", function(e) {
            // Dropdown menüsüne tıklanmışsa satır tıklamasını engelle
            if ($(e.target).closest('.btn-group, .dropdown-menu').length > 0) {
                return;
            }
            
            var musteriId = $(this).data("musteri-id");
            window.location.href = "/index.php?url=siparis&musteri_id=" + musteriId;
        });
    });
</script> 