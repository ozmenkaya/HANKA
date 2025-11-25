<?php 
    include "include/oturum_kontrol.php";

    $sql  = "SELECT a.id, a.depo_kodu, a.depo_tanimi, CONCAT( p1.ad, ' ', p1.soyad ) as olusturan, a.olusturma_tarihi,";
    $sql .= "CONCAT( p2.ad, ' ', p2.soyad ) as guncelleyen, a.guncelleme_tarihi  FROM depolar AS a";
    $sql .= " LEFT JOIN personeller p1 on a.olusturan_id = p1.id";
    $sql .= " LEFT JOIN personeller p2 on a.guncelleyen_id = p2.id";  
    $sql .= " WHERE a.firma_id = :firma_id ORDER BY olusturma_tarihi ASC";

    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $depolar = $sth->fetchAll(PDO::FETCH_ASSOC);

?>
 
 <style>
    .modern-table {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .modern-table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border: none;
        padding: 12px 8px;
    }
    .modern-table tbody tr {
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    .modern-table tbody tr:hover {
        background-color: #f8f9fa !important;
        border-left: 3px solid #667eea;
        transform: translateX(2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .modern-table tbody td {
        vertical-align: middle;
        padding: 10px 8px;
    }
    .action-dropdown .dropdown-toggle {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        border-radius: 6px;
        padding: 4px 10px;
    }
    .action-dropdown .dropdown-toggle:hover {
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
    }
    .header-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }
    .btn-action {
        transition: all 0.3s ease;
    }
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .badge-count {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
    }
</style>

<div class="row">
    <div class="card mt-2 shadow-sm border-0">
        <div class="card-header header-gradient">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">
                    <i class="fa-solid fa-warehouse"></i> Depo Yönetimi
                    <span class="badge bg-light text-dark badge-count ms-2"><?php echo count($depolar); ?></span>
                </h5> 
                <div class="mt-2 mt-md-0">
                    <div class="btn-group" role="group" aria-label="Depo Actions">
                        <a href="javascript:window.history.back();" 
                            class="btn btn-light btn-action"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom" 
                            data-bs-title="Geri Dön"
                        >
                            <i class="fa-solid fa-arrow-left"></i> Geri
                        </a>
                        <?php if(in_array(DEPO_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                            <button type="button" class="btn btn-success btn-action" 
                                data-bs-toggle="modal" 
                                data-bs-target="#depo-ekle-modal"
                                data-bs-placement="bottom" 
                                data-bs-title="Yeni Depo Ekle"
                            >
                                <i class="fa-solid fa-plus"></i> Yeni Depo
                            </button>
                        <?php } ?>
                    </div>
                </div> 
            </div>
        </div>
        <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="myTable" class="table table-hover modern-table mb-0" >
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center"><i class="fa-solid fa-barcode"></i> Depo Kodu</th>
                                <th><i class="fa-solid fa-tag"></i> Depo Tanımı</th>
                                <th><i class="fa-solid fa-user-plus"></i> Oluşturan</th>
                                <th class="text-center"><i class="fa-solid fa-calendar-plus"></i> Oluş. Tarihi</th>
                                <th><i class="fa-solid fa-user-pen"></i> Güncelleyen</th>
                                <th class="text-center"><i class="fa-solid fa-calendar-check"></i> Güncelleme</th>
                                <th class="text-center"><i class="fa-solid fa-cog"></i> İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($depolar as $index => $depo){ ?>  
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $index + 1; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-primary"><?php echo $depo['depo_kodu']; ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo $depo['depo_tanimi']; ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fa-solid fa-user"></i> <?php echo $depo['olusturan']; ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">
                                            <?php echo $depo['olusturma_tarihi'] ? date('d.m.Y H:i', strtotime($depo['olusturma_tarihi'])) : '-'; ?>
                                        </small>
                                    </td> 
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $depo['guncelleyen'] ? '<i class="fa-solid fa-user"></i> '.$depo['guncelleyen'] : '-'; ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">
                                            <?php echo $depo['guncelleme_tarihi'] ? date('d.m.Y H:i', strtotime($depo['guncelleme_tarihi'])) : '-'; ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group action-dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end shadow">
                                                    <a 
                                                       class="dropdown-item alanGorButonu" 
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#depo-alan-modal"
                                                       data-depo-id="<?php echo $depo['id']; ?>"
                                                       data-bs-placement="bottom"
                                                       data-bs-title="Depo Alanları"
                                                       href="javascript:;">
                                                       <i class="fa-solid fa-layer-group text-info"></i> Depo Alanları
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                               <?php if (in_array(DEPO_GUNCELLE, $_SESSION['sayfa_idler'])) { ?>
                                                    <a 
                                                        class="dropdown-item guncelleButonu"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#depo-guncelle-modal"
                                                        data-depo-id="<?php echo $depo['id']; ?>"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="Güncelle"
                                                        href="javascript:;">
                                                        <i class="fa-solid fa-pen-to-square text-warning"></i> Güncelle
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                <?php } ?>
                                                <?php if(in_array(DEPO_SIL, $_SESSION['sayfa_idler'])){ ?>
                                                    <a href="/index.php?url=depo_db_islem&islem=depo_sil&id=<?php echo $depo['id']; ?>" 
                                                        onClick="return confirm('⚠️ Bu depoyu silmek istediğinize emin misiniz?')"  
                                                        class="dropdown-item text-danger"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil">
                                                        <i class="fa-solid fa-trash"></i> Sil
                                                    </a>
                                                <?php }else{?> 
                                                    <a href="javascript:;" 
                                                        class="dropdown-item text-muted disabled"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Yetkiniz Yok">
                                                        <i class="fa-solid fa-trash"></i> Sil
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
    <!-- Depo Ekle Modal -->
    <div class="modal fade" id="depo-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light pt-1 pb-1">
                        <h4 class="modal-title" id="myCenterModalLabel">Depo Ekle</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form action="/index.php?url=depo_db_islem" method="POST" id="depo-ekle-form" class="row g-3 needs-validation">
                            <div class="row mb-1">
                                <div class="col-md-4">
                                    <label for="depoKodu" class="form-label">Depo Kodu</label>
                                    <input type="text" class="form-control" id="depoKodu" name="depo_kodu" placeholder="Depo Kodu Girin" required>  
                                </div>
                                <div class="col-md-8">
                                    <label for="depoTanimi" class="form-label">Depo Tanımı</label>
                                    <input type="text" class="form-control" id="depoTanimi" name="depo_tanimi" placeholder="Depo Tanımı Girin">
                                </div>
                            </div>   
                            <div class="row">
                                <div class="col-md-12"> 
                                    <h6 class="mt-2">Alan Tanımlama</h6>
                                </div> 
                            </div> 
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <label for="alanKodu" class="form-label">Alan Kodu</label>
                                    <input type="text" class="form-control" id="alanKodu" placeholder="Alan Kodu Girin">
                                </div>
                                <div class="col-md-6">
                                    <label for="alanTanimi" class="form-label">Alan Tanımı</label>
                                    <input type="text" class="form-control" id="alanTanimi" placeholder="Alan Tanımı Girin">
                                </div> 
                                <div class="col-md-2 align-self-end"> 
                                    <button type="button" class="btn btn-blue" id="alanEkle">Ekle</button> 
                                </div> 
                            </div>  
                            <div class="row">
                                <table class="table table-bordered" id="alanlarTablosuEkle">
                                    <thead>
                                        <tr>
                                            <th class="pt-1 pb-1 text-center">Alan Kodu</th>
                                            <th class="pt-1 pb-1">Alan Tanımı</th>
                                            <th class="pt-1 pb-1 text-center">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>

                                <div id="hiddenInputs"></div>
                            </div> 
                            <div class="text-end">
                                <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                                <button type="submit" class="btn btn-success waves-effect waves-light" name="depo_ekle" id="depo-ekle-button">Kaydet</button>                                
                            </div>
                        </form>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div> 
    </div>
    <!-- Depo Güncelle Modal -->
    <div class="modal fade" id="depo-guncelle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="guncelleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light pt-1 pb-1">
                    <h4 class="modal-title" id="guncelleModalLabel">Depo Güncelle</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="/index.php?url=depo_db_islem&islem=depo_guncelle" method="POST" id="depo-guncelle-form" class="row g-3 needs-validation">
                        <input type="hidden" name="depo_id" id="guncelleDepoId">
                        <div class="row mb-1">
                            <div class="col-md-4">
                                <label for="guncelleDepoKodu" class="form-label">Depo Kodu</label>
                                <input type="text" class="form-control" id="guncelleDepoKodu" name="depo_kodu" placeholder="Depo Kodu Girin" required>
                            </div>
                            <div class="col-md-8">
                                <label for="guncelleDepoTanimi" class="form-label">Depo Tanımı</label>
                                <input type="text" class="form-control" id="guncelleDepoTanimi" name="depo_tanimi" placeholder="Depo Tanımı Girin">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="mt-2">Alan Tanımlama</h6>
                            </div>
                        </div>
                        <div class="row mb-2 justify-content-end">
                            <div class="col-md-4">
                                <label for="guncelleAlanKodu" class="form-label">Alan Kodu</label>
                                <input type="text" class="form-control" id="guncelleAlanKodu" placeholder="Alan Kodu Girin">
                            </div>
                            <div class="col-md-6">
                                <label for="guncelleAlanTanimi" class="form-label">Alan Tanımı</label>
                                <input type="text" class="form-control" id="guncelleAlanTanimi" placeholder="Alan Tanımı Girin">
                            </div> 
                            <div class="col-md-2 align-self-end"> 
                                    <button type="button" class="btn btn-blue" id="alanEkle">Ekle</button> 
                            </div> 
                        </div>
                        <div class="row">
                            <table class="table table-bordered" id="guncelleAlanlarTablosu">
                                <thead>
                                    <tr>
                                        <th class="pt-1 pb-1 text-center">Alan Kodu</th>
                                        <th class="pt-1 pb-1">Alan Tanımı</th>
                                        <th class="pt-1 pb-1 text-center">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div id="guncelleHiddenInputs"></div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-success waves-effect waves-light" name="depo_guncelle" id="depo-guncelle-button">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Depo Alanları Modal -->
    <div class="modal fade" id="depo-alan-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="alanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light pt-1 pb-1">
                    <h4 class="modal-title" id="alanModalLabel">Depo Alanları</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4"> 
                        <div class="row">
                            <table class="table table-bordered" id="alanlarTablosu">
                                <thead>
                                    <tr>
                                        <th class="pt-1 pb-1 text-center">Alan Kodu</th>
                                        <th class="pt-1 pb-1">Alan Tanımı</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-blue waves-effect waves-light" data-bs-dismiss="modal">Kapat</button>
                        </div> 
                </div>
            </div>
        </div>
    </div>
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <!-- Mesaj buraya gelecek -->
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php 
        include "include/uyari_session_oldur.php";
    ?>
   <script> 
        $(document).ready(function() {
            // Toast’ı başlatma fonksiyonu
            function showErrorToast(message) {
                const toast = $('#errorToast');
                toast.find('.toast-body').text(message);
                const bsToast = new bootstrap.Toast(toast[0], { delay: 3000 }); // 3 saniye sonra kapanır
                bsToast.show();
            }

            // Ekle Butonu
            $('#alanEkle').click(function() {
                let alanKodu = $('#alanKodu').val().trim();
                let alanTanimi = $('#alanTanimi').val().trim();

                if (alanKodu === '' || alanTanimi === '') {
                    showErrorToast('Lütfen alan kodu ve tanımı girin.');
                    return;
                }

                let row = `
                    <tr>
                        <td class="text-center pt-1 pb-1 align-middle">${alanKodu}</td>
                        <td class="pt-1 pb-1 align-middle">${alanTanimi}</td>
                        <td class="text-center pt-1 pb-1 align-middle"><button type="button" class="btn btn-danger btn-sm silButonu">Sil</button></td>
                    </tr>
                `;
                $('#alanlarTablosuEkle tbody').append(row);

                let index = $('#alanlarTablosu tbody tr').length - 1;
                let hiddenInputs = `
                    <input type="hidden" name="alanlar[${index}][alan_kodu]" value="${alanKodu}">
                    <input type="hidden" name="alanlar[${index}][alan_tanimi]" value="${alanTanimi}">
                `;
                $('#hiddenInputs').append(hiddenInputs);

                $('#alanKodu').val('');
                $('#alanTanimi').val('');
            });

            // Sil Butonu
            $(document).on('click', '.silButonu', function() {
                let row = $(this).closest('tr');
                let rowIndex = $('#alanlarTablosuEkle tbody tr').index(row);

                $(`#hiddenInputs input[name^="alanlar[${rowIndex}]"]`).remove();
                row.remove();

                $('#hiddenInputs input[name*="alan_kodu"]').each(function(index) {
                    $(this).attr('name', `alanlar[${index}][alan_kodu]`);
                });
                $('#hiddenInputs input[name*="alan_tanimi"]').each(function(index) {
                    $(this).attr('name', `alanlar[${index}][alan_tanimi]`);
                });
            });

            // Güncelle Modal için
            $('#guncelleAlanEkle').click(function() {
                let alanKodu   = $('#guncelleAlanKodu').val().trim();
                let alanTanimi = $('#guncelleAlanTanimi').val().trim();

                if (alanKodu === '' || alanTanimi === '') {
                    showErrorToast('Lütfen alan kodu ve tanımı girin.');
                    return;
                }

                let row = `
                    <tr>
                        <td class="text-center pt-1 pb-1 align-middle">${alanKodu}</td>
                        <td class="pt-1 pb-1 align-middle">${alanTanimi}</td>
                        <td class="text-center pt-1 pb-1 align-middle"><button type="button" class="btn btn-danger btn-sm gsilButonu">Sil</button></td>
                    </tr>
                `;
                $('#guncelleAlanlarTablosu tbody').append(row);

                let index = $('#guncelleAlanlarTablosu tbody tr').length - 1;
                let hiddenInputs = `
                    <input type="hidden" name="alanlar[${index}][alan_kodu]" value="${alanKodu}">
                    <input type="hidden" name="alanlar[${index}][alan_tanimi]" value="${alanTanimi}">
                `;
                $('#guncelleHiddenInputs').append(hiddenInputs);

                $('#guncelleAlanKodu').val('');
                $('#guncelleAlanTanimi').val('');
            });

            // Sil Butonu (Güncelle Modal)
            $(document).on('click', '#guncelleAlanlarTablosu .gsilButonu', function() {
                let row = $(this).closest('tr');
                let rowIndex = $('#guncelleAlanlarTablosu tbody tr').index(row);

                $(`#guncelleHiddenInputs input[name^="alanlar[${rowIndex}]"]`).remove();
                row.remove();

                $('#guncelleHiddenInputs input[name*="alan_kodu"]').each(function(index) {
                    $(this).attr('name', `alanlar[${index}][alan_kodu]`);
                });

                $('#guncelleHiddenInputs input[name*="alan_tanimi"]').each(function(index) {
                    $(this).attr('name', `alanlar[${index}][alan_tanimi]`);
                });
  
            });

            // Güncelle Butonuna Tıklama
            $('.guncelleButonu').click(function() {
                let depoId = $(this).data('depo-id');

                // AJAX ile depo ve alan verilerini çek
                $.ajax({
                    url: '/index.php?url=depo_db_islem&islem=get_depo',
                    type: 'POST',
                    data: { depo_id: depoId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Formu doldur
                            $('#guncelleDepoId').val(response.depo.id);
                            $('#guncelleDepoKodu').val(response.depo.depo_kodu);
                            $('#guncelleDepoTanimi').val(response.depo.depo_tanimi);

                            // Alanlar tablosunu doldur
                            $('#guncelleAlanlarTablosu tbody').empty();
                            $('#guncelleHiddenInputs').empty();
                            response.alanlar.forEach(function(alan, index) {
                                let row = `
                                    <tr>
                                        <td class="text-center pt-1 pb-1 align-middle">${alan.alan_kodu}</td>
                                        <td class="pt-1 pb-1 align-middle">${alan.alan_tanimi}</td>
                                        <td class="text-center pt-1 pb-1 align-middle"><button type="button" class="btn btn-danger btn-sm gsilButonu">Sil</button></td>
                                    </tr>
                                `;
                                $('#guncelleAlanlarTablosu tbody').append(row);

                                let hiddenInputs = `
                                    <input type="hidden" name="alanlar[${index}][alan_kodu]" value="${alan.alan_kodu}">
                                    <input type="hidden" name="alanlar[${index}][alan_tanimi]" value="${alan.alan_tanimi}">
                                `;
                                $('#guncelleHiddenInputs').append(hiddenInputs);
                            });
                        } else {
                            showErrorToast('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        showErrorToast('Veriler yüklenirken bir hata oluştu.');
                    }
                });
            });

            $('#depo-guncelle-form').submit(function(e) {
                let depoKodu = $('#guncelleDepoKodu').val().trim();
                let depoTanimi = $('#guncelleDepoTanimi').val().trim();
                let alanlarCount = $('#guncelleAlanlarTablosu tbody tr').length;

                if (depoKodu === '') {
                    showErrorToast('Depo kodu zorunludur.');
                    e.preventDefault();
                    return false;
                }
                if (depoTanimi === '') {
                    showErrorToast('Depo tanımı zorunludur.');
                    e.preventDefault();
                    return false;
                }
                if (alanlarCount === 0) {
                    showErrorToast('Lütfen en az bir alan ekleyin.');
                    e.preventDefault();
                    return false;
                }
            });

            // Depo Alnları Gör Butonuna Tıklama
            $('.alanGorButonu').click(function() {
                let depoId = $(this).data('depo-id');

                // AJAX ile depo ve alan verilerini çek
                $.ajax({
                    url: '/index.php?url=depo_db_islem&islem=get_depo',
                    type: 'POST',
                    data: { depo_id: depoId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) { 
                            // Alanlar tablosunu doldur
                            $('#alanlarTablosu tbody').empty(); 
                            response.alanlar.forEach(function(alan, index) {
                                let row = `
                                    <tr>
                                        <td class="text-center pt-1 pb-1 align-middle">${alan.alan_kodu}</td>
                                        <td class="pt-1 pb-1 align-middle">${alan.alan_tanimi}</td>
                                    </tr>
                                `;
                                $('#alanlarTablosu tbody').append(row); 
                            });
                        } else {
                            showErrorToast('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        showErrorToast('Veriler yüklenirken bir hata oluştu.');
                    }
                });
            });
        });
    </script>