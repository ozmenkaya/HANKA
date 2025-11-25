<?php 
    include "include/oturum_kontrol.php";

    $sql  = "SELECT a.id, a.aciklama, CONCAT( p1.ad, ' ', p1.soyad ) as olusturan, a.olusturma_tarihi,";
    $sql .= " CONCAT( p2.ad, ' ', p2.soyad ) as guncelleyen, a.guncelleme_tarihi  FROM grup_kodu AS a";
    $sql .= " LEFT JOIN personeller p1 on a.olusturan_id = p1.id";
    $sql .= " LEFT JOIN personeller p2 on a.guncelleyen_id = p2.id";  
    $sql .= " WHERE a.firma_id = :firma_id ORDER BY olusturma_tarihi ASC";

    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $grup_kodlari = $sth->fetchAll(PDO::FETCH_ASSOC);

?> 
 <div class="row">
        <div class="card mt-2 border-secondary">
            <div class="card-header d-flex justify-content-between border-secondary">
                <h5 >
                    <i class="fa-solid fa-building"></i> Grup Kodları
                </h5> 
                <div>
                    <div class="d-md-flex justify-content-end"> 
                        <div class="btn-group" role="group" aria-label="Basic example">
                            <a href="javascript:window.history.back();" 
                                class="btn btn-secondary"
                                data-bs-target="#grup-kodu-ekle-modal"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Geri Dön"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                            <?php if(in_array(DEPO_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                                <button type="button" class="btn btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#grup-kodu-ekle-modal"
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
                    <table id="myTable" class="table table-hover table-sm" >
                        <thead class="table-primary">
                            <tr>
                                <th style="text-align: center; vertical-align: middle;">#</th>
                                <th>Açıklama</th>
                                <th>Olş.Klnc.</th>
                                <th class="text-center">Olş.Trh.</th>
                                <th>Gnc.Klnc.</th>
                                <th class="text-center">Gnc.Trh.</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($grup_kodlari as $index => $grup_kodu){ ?>  
                                <tr class="table-success">
                                    <td class="table-primary" style="padding:0px 6px;text-align: center; vertical-align: middle;"><?php echo $index + 1; ?></td>
                                    <td style="padding:0px 6px;vertical-align: middle;"><?php echo $grup_kodu['aciklama']; ?></td>
                                    <td style="padding:0px 6px;vertical-align: middle;"><?php echo $grup_kodu['olusturan']; ?></td>
                                    <td class="text-center" style="padding:0px 6px;vertical-align: middle;">
                                        <?php echo $grup_kodu['olusturma_tarihi'] ? date('d.m.Y H:i:s', strtotime($grup_kodu['olusturma_tarihi'])) : ''; ?>
                                    </td> 
                                    <td style="padding:0px 6px;vertical-align: middle;"><?php echo $grup_kodu['guncelleyen']; ?></td>
                                    <td class="text-center" style="padding:0px 6px;vertical-align: middle;">
                                        <?php echo $grup_kodu['guncelleme_tarihi'] ? date('d.m.Y H:i:s', strtotime($grup_kodu['guncelleme_tarihi'])) : ''; ?>
                                    </td>
                                    <td class="text-center align-middle text-center" style="padding:0px 6px;vertical-align: middle;">
                                        <div class="btn-group custom-dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                               <?php if (in_array(DEPO_GUNCELLE, $_SESSION['sayfa_idler'])) { ?>
                                                    <a 
                                                        class="dropdown-item pt-0 pb-0 guncelleButonu"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#grup-kodu-guncelle-modal"
                                                        data-grup-kodu-id="<?php echo $grup_kodu['id']; ?>"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="Güncelle"
                                                        href="javascript:;">Güncelle</a>
                                                    <div class="dropdown-divider"></div>
                                                <?php } ?>
                                                <?php if(in_array(DEPO_SIL, $_SESSION['sayfa_idler'])){ ?>
                                                    <a href="/index.php?url=grup_kodu_db_islem&islem=grup_kodu_sil&id=<?php echo $grup_kodu['id']; ?>" 
                                                        onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"  
                                                        class="dropdown-item pt-0 pb-0"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil">Sil</a>
                                                <?php }else{?> 
                                                    <a href="javascript:;" 
                                                        class="btn btn-danger disabled btn-sm"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil">Sil</a>    
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
    <div class="modal fade" id="grup-kodu-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="width:450px;">
                    <div class="modal-header bg-light pt-1 pb-1">
                        <h4 class="modal-title" id="myCenterModalLabel">Grup Kodu Ekle</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form action="/index.php?url=grup_kodu_db_islem" method="POST" id="grup-kodu-ekle-form" class="row g-3 needs-validation">
                            <div class="row mb-1" style="padding-right: 0px;"> 
                                <div class="col-md-12" style="padding-right: 0px;">
                                    <label for="grupKoduTanimi" class="form-label">Tanım</label>
                                    <input type="text" class="form-control" id="grupKoduTanimi" name="grup_kodu_tanimi" placeholder="Tanımı Girin">
                                </div>
                            </div>    
                            <div class="text-end">
                                <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                                <button type="submit" class="btn btn-success waves-effect waves-light" name="grup_kodu_ekle" id="grup-kodu-ekle-button">Kaydet</button>                                
                            </div>
                        </form>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div> 
    </div>
    <!-- Grup Kodu Güncelle Modal -->
    <div class="modal fade" id="grup-kodu-guncelle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="guncelleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="width:450px;">
                <div class="modal-header bg-light pt-1 pb-1">
                    <h4 class="modal-title" id="guncelleModalLabel">Grup Kodu Güncelle</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="/index.php?url=grup_kodu_db_islem&islem=grup_kodu_guncelle" method="POST" id="depo-guncelle-form" class="row g-3 needs-validation">
                        <input type="hidden" name="grup_kodu_id" id="guncelleGrupKoduId">
                        <div class="row mb-1" style="padding-right: 0px;">
                            <div class="col-md-12" style="padding-right: 0px;">
                                <label for="guncelleGrupTanimi" class="form-label">Tanım</label>
                                <input type="text" class="form-control" id="guncelleGrupTanimi" name="grup_kodu_tanimi" placeholder="Grup Tanımı Girin">
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-success waves-effect waves-light" name="grup_kodu_guncelle" id="grup-kodu-guncelle-button">Güncelle</button>
                        </div>
                    </form>
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
                const bsToast = new bootstrap.Toast(toast[0], { delay: 3000 }); 
                bsToast.show();
            }

            // Güncelle Butonuna Tıklama
            $('.guncelleButonu').click(function() {
                let grupId = $(this).data('grup-kodu-id');

                // AJAX ile depo ve alan verilerini çek
                $.ajax({
                    url: '/index.php?url=grup_kodu_db_islem&islem=get_grup_kodu',
                    type: 'POST',
                    data: { id: grupId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Formu doldur 
                            $('#guncelleGrupKoduId').val(response.grup.id); 
                            $('#guncelleGrupTanimi').val(response.grup.aciklama);                
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
                let aciklama = $('#guncelleGrupTanimi').val().trim();

                if (aciklama === '') {
                    showErrorToast('Grup tanımı zorunludur.');
                    e.preventDefault();
                    return false;
                }
            }); 
        });
    </script>