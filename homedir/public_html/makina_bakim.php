<?php
    require_once "include/oturum_kontrol.php";

    if(!in_array($_SESSION['yetki_id'],[MAKINE_BAKIM_ID])){ 
        include_once "include/yetkisiz.php"; exit;
    }

    $sql = "SELECT makinalar.*,departmanlar.departman
    FROM `makinalar` 
    JOIN departmanlar ON departmanlar.id = makinalar.departman_id
    WHERE makinalar.firma_id = :firma_id 
    ORDER BY makinalar.`makina_son_bakim_tarih` ASC ";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $tum_makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT uretim_bakim_log.baslatma_tarihi, uretim_bakim_log.id AS uretim_bakim_log_id,
        makinalar.makina_adi, makinalar.makina_modeli,makinalar.makina_seri_no,makinalar.id,makinalar.durumu,
        departmanlar.departman,
        personeller.ad, personeller.soyad
        FROM `uretim_bakim_log` 
        JOIN makinalar ON makinalar.id = uretim_bakim_log.makina_id
        JOIN departmanlar ON departmanlar.id = uretim_bakim_log.departman_id
        JOIN personeller ON personeller.id = uretim_bakim_log.personel_id
        WHERE uretim_bakim_log.firma_id = :firma_id AND uretim_bakim_log.gelen_personel_id = :gelen_personel_id 
        AND uretim_bakim_log.durum = 'bakılmadı'";

    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('gelen_personel_id', $_SESSION['personel_id']);
    $sth->execute();
    $yeni_gelmis_ariza_makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);
    //echo "<pre>";print_R($yeni_gelmis_ariza_makinalar); exit;
?>
<div class="row mt-2">
    <div class="card mb-2">
        <div class="card-body d-flex justify-content-end">
            <div>
                <a href="/index.php?url=makina_bakim" class="btn btn-secondary btn-lg fw-bold">
                    <i class="fa-regular fa-clock"></i> <span id="geri-sayim">120</span> sn
                </a>
            </div>
        </div>
    </div>

    <div class="accordion" id="accordionExample">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button position-relative fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    <i class="fa-solid fa-gear mx-2"></i>
                    Arızalı Makinalar
                    <span class="position-absolute top-0 translate-middle badge rounded-pill bg-danger">
                        <?php echo count($yeni_gelmis_ariza_makinalar); ?>
                        <span class="visually-hidden">Arızalı Makinalar</span>
                    </span>
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover caption-top">
                            <caption class="fw-bold text-danger">Arızalı  Makinalar</caption>
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Ad Soyad</th>
                                    <th>Makina Adı</th>
                                    <th>Makina Model</th>
                                    <th>Seri No</th>
                                    <th>Departman</th>
                                    <th>Tarih</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yeni_gelmis_ariza_makinalar as $index => $yeni_gelmis_ariza_makina) { ?>
                                    <tr>
                                        <th class="table-primary"><?php echo $index+1;?></th>
                                        <td><?php echo $yeni_gelmis_ariza_makina['ad'].' '.$yeni_gelmis_ariza_makina['soyad'];?></td>
                                        <td><?php echo $yeni_gelmis_ariza_makina['makina_adi'];?></td>
                                        <td><?php echo $yeni_gelmis_ariza_makina['makina_modeli'];?></td>
                                        <td><?php echo $yeni_gelmis_ariza_makina['makina_seri_no'];?></td>
                                        <td><?php echo $yeni_gelmis_ariza_makina['departman'];?></td>
                                        <td><?php echo date('d-m-Y H:i:s', strtotime($yeni_gelmis_ariza_makina['baslatma_tarihi']));?></td>
                                        <td>
                                            <div class="d-md-flex justify-content-end">
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <button 
                                                        class="btn btn-primary makina-loglar"
                                                        data-makina-id="<?php echo $yeni_gelmis_ariza_makina['id']; ?>"
                                                        data-makina-ad-model-seri-no="<?php echo $yeni_gelmis_ariza_makina['makina_adi'].' '.$yeni_gelmis_ariza_makina['makina_modeli'].' '.$yeni_gelmis_ariza_makina['makina_seri_no'];?>"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Makina Loglar"
                                                    >
                                                        <i class="fa-solid fa-microchip"></i>
                                                    </button>
                                                    <button 
                                                        class="btn btn-warning makina-ariza-log"
                                                        data-makina-id="<?php echo $yeni_gelmis_ariza_makina['id']; ?>"
                                                        data-uretim-bakim-log-id="<?php echo $yeni_gelmis_ariza_makina['uretim_bakim_log_id']; ?>"
                                                        data-makina-durum="<?php echo $yeni_gelmis_ariza_makina['durumu']; ?>"
                                                        data-makina-ad-model-seri-no="<?php echo $yeni_gelmis_ariza_makina['makina_adi'].' '.$yeni_gelmis_ariza_makina['makina_modeli'].' '.$yeni_gelmis_ariza_makina['makina_seri_no'];?>"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="İşlem"
                                                    >
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
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
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed position-relative fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <i class="fa-solid fa-gear mx-2"></i> Makina Bakım
                    <span class="position-absolute top-0 translate-middle badge rounded-pill bg-danger">
                        <?php echo count($tum_makinalar); ?>
                        <span class="visually-hidden">Tüm Makinalar</span>
                    </span>
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover caption-top">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Adı</th>
                                    <th>Model</th>
                                    <th>Seri No</th>
                                    <th class="text-end">Bakım Süresi</th>
                                    <th class="text-end">Son Bakım Tarihi</th>
                                    <th class="text-center">Durum</th>
                                    <th>Açıklama</th>
                                    <th>Departman</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tum_makinalar as $index => $makina) { ?>
                                    <tr class="<?php echo $makina['makina_son_bakim_tarih'] == date('Y-m-d') ? 'table-danger':''; ?>">
                                        <th class="table-primary"><?php echo $index + 1; ?></th>
                                        <td><?php echo $makina['makina_adi']; ?></td>
                                        <td><?php echo $makina['makina_modeli']; ?></td>
                                        <td><?php echo $makina['makina_seri_no']; ?></td>
                                        <td class="text-end table-primary"><?php echo $makina['makina_bakim_suresi']; ?> Ay</td>
                                        <td class="text-end table-primary"><?php echo date('d-m-Y', strtotime($makina['makina_son_bakim_tarih'])); ?></td>
                                        <td class="text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input " 
                                                    type="checkbox" role="switch" disabled
                                                    <?php echo $makina['durumu'] == 'aktif'? 'checked' : ''?> 
                                                >
                                            </div>
                                        </td>
                                        <td><?php echo $makina['aciklama']; ?></td>
                                        <td><?php echo $makina['departman']; ?></td>
                                        <td>
                                            <div class="d-md-flex justify-content-end">
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <button 
                                                        class="btn btn-primary makina-loglar"
                                                        data-makina-id="<?php echo $makina['id']; ?>"
                                                        data-makina-ad-model-seri-no="<?php echo $makina['makina_adi'].' '.$makina['makina_modeli'].' '.$makina['makina_seri_no'];?>"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Makina Loglar"
                                                    >
                                                        <i class="fa-solid fa-microchip"></i>
                                                    </button>
                                                    <button 
                                                        class="btn btn-warning makina-log"
                                                        data-makina-id="<?php echo $makina['id']; ?>"
                                                        data-makina-durum="<?php echo $makina['durumu']; ?>"
                                                        data-makina-ad-model-seri-no="<?php echo $makina['makina_adi'].' '.$makina['makina_modeli'].' '.$makina['makina_seri_no'];?>"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="İşlem"
                                                    >
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
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
</div>

<!-- Makina Arıza Log Modal -->
<div class="modal fade" id="makina-ariza-log-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="makina-ariza-log-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="makina-ariza-log-label">
                    <span  class="badge bg-secondary makina-ad-model-seri-no"></span> Makina İşlem
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form  class="row g-3 needs-validation" action="/index.php?url=makina_bakim_db_islem" method="POST">
                    <input type="hidden" name="makina_ariza_id" id="makina-ariza-id">
                    <input type="hidden" name="uretim_bakim_log_id" id="uretim-bakim-log-id">
                    <div class="form-floating">
                        <select class="form-select" name="makina_ariza_durum" id="makina-ariza-durum">
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                        </select>
                        <label for="makina-ariza-durum">Makina Durum</label>
                    </div>
                    <div class="form-floating">
                        <textarea class="form-control" placeholder="Notunuz" name="ariza_konu" id="ariza_konu"></textarea>
                        <label for="konu">Not</label>
                    </div>  
                    <div class="form-floating">
                        <button class="btn btn-warning" name="islem" value="makina_ariza_log">
                            <i class="fa-regular fa-paper-plane"></i> İŞLEM YAP
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Makina Bakım Log Modal -->
<div class="modal fade" id="makina-bakim-log-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">
                    <span id="makina-ad-model-seri-no" class="badge bg-secondary"></span> Makina İşlem
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form  class="row g-3 needs-validation" action="/index.php?url=makina_bakim_db_islem" method="POST">
                    <input type="hidden" name="makina_id" id="makina-id">
                    <div class="form-floating">
                        <select class="form-select" name="makina_durum" id="makina-durum">
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                        </select>
                        <label for="makina-durum">Makina Durum</label>
                    </div>
                    <div class="form-floating">
                        <textarea class="form-control" placeholder="Notunuz" name="konu" id="konu"></textarea>
                        <label for="konu">Not</label>
                    </div>  
                    <div class="form-floating">
                        <button class="btn btn-warning" name="makina_bakim_log">
                            <i class="fa-regular fa-paper-plane"></i> İŞLEM YAP
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Makina Bakım Log Modal -->
<div class="modal fade" id="makina-loglar-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="makina-loglar-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="makina-loglar-label">
                    <span class="badge bg-secondary makina-ad-model-seri-no"></span> Makina İşlem
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="myTable" class="table table-hover">
                    <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th class="text-center">Durum</th>
                        <th>Not</th>
                        <th>Tarih</th>
                    </tr>
                    </thead>
                    <tbody id="makina-loglar-tbody">
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
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
    let geriSayim = 120;
    setInterval(function(){
        $("#geri-sayim").text(--geriSayim);
        if(geriSayim <= 1 ) window.location.reload();
    },1000)
    $(function(){

        //makina ait logları getirme
        $(".makina-loglar").click(async function(){
            const makinaId = $(this).data('makina-id');
            const makinaAdModelSeriNo = $(this).data('makina-ad-model-seri-no');

            const response          = await fetch("/index.php?url=makina_bakim_db_islem&islem=makina_log_getir&makina_id=" + makinaId);
            const makinaloglar      = await response.json();
            let makinaBakimLoglarHTML = '';
            let index = 0;
            let durum = '';
            for(const makina_bakim_log of makinaloglar?.makina_bakim_loglar){
                durum = makina_bakim_log.durum == 'aktif' ? '<span class="badge bg-success">AKTİF</span>':'<span class="badge bg-danger">PASİF</span>';
                makinaBakimLoglarHTML +=`
                    <tr>
                        <th>${++index}</th>
                        <td class="text-center">${durum}</td>
                        <td>${makina_bakim_log.konu}</td>
                        <td>${makina_bakim_log.tarih}</td>
                    </tr>
                `;
            }
            $(".makina-ad-model-seri-no").text(makinaAdModelSeriNo);
            $("#makina-loglar-tbody").html(makinaBakimLoglarHTML);
            $("#makina-loglar-modal").modal('show');
        });

        //Makina Ariza Log Modal Açma
        $(".makina-ariza-log").click(function(){
            const makinaId = $(this).data('makina-id');
            const uretimBakimLogId = $(this).data('uretim-bakim-log-id');
            const makinaDurum = $(this).data('makina-durum');
            const makinaAdModelSeriNo = $(this).data('makina-ad-model-seri-no');
            $("#makina-ariza-id").val(makinaId);
            $("#uretim-bakim-log-id").val(uretimBakimLogId);
            $("#makina-ariza-durum").val(makinaDurum);
            $(".makina-ad-model-seri-no").text(makinaAdModelSeriNo);
            $("#makina-ariza-log-modal").modal('show');
        });

        $(".makina-log").click(function(){
            const makinaId = $(this).data('makina-id');
            const makinaDurum = $(this).data('makina-durum');
            const makinaAdModelSeriNo = $(this).data('makina-ad-model-seri-no');
            $("#makina-id").val(makinaId);
            $("#makina-durum").val(makinaDurum);
            $("#makina-ad-model-seri-no").text(makinaAdModelSeriNo);
            $("#makina-bakim-log-modal").modal('show');
        });
    });
</script>