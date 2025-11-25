<?php 
require_once "include/oturum_kontrol.php";

if($_SESSION['yetki_id'] != 0)
{
    require_once "include/yetkisiz.php";
    die();
}

$sth = $conn->prepare('SELECT * FROM firmalar');
$sth->execute();
$firmalar = $sth->fetchAll(PDO::FETCH_ASSOC);

#echo "<pre>"; print_r($firmalar); exit;
?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5> 
                <i class="fa-solid fa-building"></i> Firma İşlemleri
            </h5>

            <div>
                <div class="d-flex justify-content-end"> 
                    <div class="btn-group" role="group">
                        <a href="javascript:window.history.back();" 
                            class="btn btn-secondary"
                            data-bs-target="#departman-ekle-modal"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom" 
                            data-bs-title="Geri Dön"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>

                        <button data-bs-toggle="modal" data-bs-target="#firma-ekle-modal" 
                            class="btn btn-primary align-self-end" type="button"
                            data-bs-placement="bottom" 
                            data-bs-title=" Firma Ekle"
                        > 
                            <i class="fa-solid fa-plus"></i>
                        </button>
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
                            <th>Logo</th>
                            <th>Firma Adı</th>
                            <th>Domain</th>
                            <th>Email</th>
                            <th>Tarih</th>
                            <th>Makina Ekran IP</th>
                            <th>Sipariş Kodu</th>
                            <th class="text-center">Eksik Üretimde Onay</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firmalar as  $key => $firma) { ?>
                            <tr>
                                <th class="table-primary"><?php echo $key + 1; ?></th>
                                <td>
                                    <?php $logo = $firma['logo'] != '' && file_exists("dosyalar/logo/{$firma['logo']}") ? $firma['logo'] : 'varsayilan.svg'; ?>
                                    <img class="object-fit-fill border rounded" src="dosyalar/logo/<?php echo $logo;?>" 
                                        alt="<?php echo $firma['firma_adi']; ?>"  loading="lazy" 
                                        style="width:50px;height:50px">
                                </td>
                                <td class="align-middle table-secondary">
                                    <?php echo $firma['firma_adi']; ?>
                                </td>
                                <td class="align-middle">
                                    <span class="badge text-bg-primary p-2">
                                        <?php echo $firma['domain_adi']; ?>
                                    </span>
                                </td>
                                <th class="align-middle text-success">
                                    <?php echo $firma['email']; ?>
                                </th>
                                <th class="align-middle text-success">
                                    <?php echo date('d-m-Y H:i',strtotime($firma['tarih'])); ?>
                                </th>
                                <th class="align-middle">
                                    <?php 
                                        $makina_ekran_ipler = array_map('trim', explode("\n", $firma['makina_ekran_ipler']));
                                        $makina_ekran_ipler = array_filter($makina_ekran_ipler);
                                        sort($makina_ekran_ipler);
                                    ?>
                                    <?php foreach ($makina_ekran_ipler as $makina_ekran_ip) { ?>
                                        <span class="badge bg-secondary p-2 mb-1 fw-bold">
                                            <i class="fa-solid fa-wifi"></i>
                                            <?php echo $makina_ekran_ip; ?>
                                        </span>
                                    <?php }?>
                                </th>
                                <th class="align-middle">
                                    <span class="badge text-bg-success p-1">
                                        <?php echo $firma['siparis_no_baslangic_kodu']; ?>
                                    </span>
                                </th>
                                <th class="align-middle text-center">
                                    <?php if($firma['eksik_uretimde_onay_isteme_durumu'] == 'evet'){ ?>
                                        <span class="badge text-bg-danger p-2">
                                            EVET
                                        </span>
                                    <?php }else{ ?>
                                        <span class="badge text-bg-success p-2">
                                            HAYIR
                                        </span>
                                    <?php }?>
                                </th>

                                <td class="align-middle">
                                    <div class="d-flex justify-content-end">
                                        <div class="btn-group" role="group" aria-label="Basic example">
                                            <a href="/index.php?url=firma_guncelle&id=<?php echo $firma['id']; ?>"  
                                                class="btn btn-warning"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="bottom" 
                                                data-bs-title="Güncelle"
                                            >
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="/index.php?url=firma_db_islem&islem=firma-sil&id=<?php echo $firma['id']; ?>" 
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
</div>


<!-- Firma Ekle Modal -->
<div class="modal fade" id="firma-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="/index.php?url=firma_db_islem" id="firma-ekle-form" enctype="multipart/form-data" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">
                        <i class="fa-solid fa-building"></i> Firma Ekleme İşlemi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating m-1">
                        <input type="text" class="form-control" name="domain_adi" id="domain_adi" required >
                        <label for="domain_adi" class="form-label">Domain</label>
                    </div>
                    <div class="form-floating m-1">
                        <input type="text" class="form-control" name="firma_adi" id="firma_adi" required >
                        <label for="firma_adi" class="form-label">Firma Adı</label>
                    </div>
                    <div class="form-floating m-1">
                        <input type="email" class="form-control" name="email" id="email" required >
                        <label for="email" class="form-label">Email</label>
                    </div>
                    <div class="form-floating m-1">
                        <input class="form-control" type="file" id="logo" name="logo" >           
                        <label for="logo" class="form-label">Logo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="firma-ekle" id="firma-ekle-button">
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
    include_once "include/uyari_session_oldur.php"; 
?>
<script>
    $(function(){
        //firma ekle modal açıldığında autofocus
        $('#firma-ekle-modal').on('shown.bs.modal', function () {
            $('#domain_adi').focus()
        });

        //formu gönderdiğinde butonu pasif duruma getirme 
        $("#firma-ekle-form").submit(function(){
            $("#firma-ekle-button").addClass('disabled');
            return true;
        });
    });
</script>