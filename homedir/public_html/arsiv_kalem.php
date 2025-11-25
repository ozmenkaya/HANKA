<?php 
    include "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT * FROM arsiv_kalemler 
        WHERE arsiv_kalemler.firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $arsiv_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
    <div class="row">
        <div class="card border-secondary mt-2">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-regular fa-folder-open"></i> ARŞİV KALEMLER
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
                            <a href="/index.php?url=arsiv_kalem_db_islem&islem=arsiv_kalem_csv" 
                                class="btn btn-success"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="bottom" 
                                data-bs-title="Excel"
                            >
                                <i class="fa-regular fa-file-excel"></i>
                            </a>
                            <?php if(in_array(ARSIV_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                                <button type="button" class="btn btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#arsiv-kalem-ekle-modal"
                                    data-bs-placement="bottom" 
                                    data-bs-title="Arsiv Ekle"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>	
            </div>
            <div class="card-body pt-0">
                <table id="myTable" class="table table-hover table-sm" >
                    <thead class="table-primary">
                        <tr>
                            <th class=" text-center align-middle">#</th>
                            <th>Arşiv</th>
                            <th>Departman</th>
                            <th>Arşiv Türü</th>
                            <th class="text-end">Arşiv Sayısı</th>
                            <th class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($arsiv_kalemler as $index => $arsiv_kalem){ ?>
                            <?php 
                                $sql = 'SELECT COUNT(*) AS arsiv_alt_sayisi FROM `arsiv_altlar` WHERE arsiv_id = :arsiv_id;';
                                $sth = $conn->prepare($sql);
                                $sth->bindParam('arsiv_id', $arsiv_kalem['id']);
                                $sth->execute();
                                $arsiv_alt_varmi = $sth->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <tr>
                                <th class="table-primary text-center align-middle table_sm_pd"><?php echo $index + 1; ?></th>
                                <th class="align-middle table_sm_pd"><?php echo $arsiv_kalem['arsiv']; ?></th>
                                <td class="align-middle table_sm_pd">
                                    <?php 
                                        // Null check ve json_decode güvenli hale getirildi
                                        $departman_idler_json = isset($arsiv_kalem['departman_idler']) ? $arsiv_kalem['departman_idler'] : null;
                                        $departman_idler   = !empty($departman_idler_json) ? json_decode($departman_idler_json, true) : [];
                                        $departman_idler   = empty($departman_idler) ? [] : $departman_idler;
                                        $departmanlar      = [];
                                        if(!empty($departman_idler)){
                                            $departman_idler = implode(',',$departman_idler);
                                            $sql = "SELECT departman FROM departmanlar WHERE id IN({$departman_idler})";
                                            $sth = $conn->prepare($sql);
                                            $sth->execute();
                                            $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                        }
                                    ?>

                                    <?php foreach ($departmanlar as $departman) { ?>
                                        <span style="padding-top: 7px!important;padding-bottom: 7px!important" class="badge bg-primary p-2 mb-1 mt-1">
                                            <?php echo $departman['departman']; ?>
                                        </span>
                                    <?php }?>
                                </td>
                                <td class="align-middle table_sm_pd"><?php echo (isset($arsiv_kalem['arsiv_tur_id']) && $arsiv_kalem['arsiv_tur_id'] == 1) ? 'FİZİKSEL' : 'DİJİTAL'; ?></td>
                                <th class="text-end align-middle table_sm_pd">
                                    <?php echo $arsiv_alt_varmi['arsiv_alt_sayisi']; ?> Adet
                                </th>
                                <td class="text-center align-middle table_sm_pd" style="width: 100px;">
                                            <div class="btn-group custom-dropdown">
                                                <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="mdi mdi-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <?php if(in_array(ARSIV_GOR, $_SESSION['sayfa_idler'])){ ?>
                                                        <a class="dropdown-item pt-0 pb-0" href="/index.php?url=arsiv_alt&arsiv_id=<?php echo $arsiv_kalem['id']; ?>">Arşiv Listesi</a>
                                                        <div class="dropdown-divider"></div>
                                                    <?php } ?>
                                                    <?php if(in_array(ARSIV_DUZENLE, $_SESSION['sayfa_idler'])){ ?>
                                                        <a class="dropdown-item pt-0 pb-0" href="/index.php?url=arsiv_kalem_guncelle&id=<?php echo $arsiv_kalem['id']; ?>">Güncelle</a>
                                                        <div class="dropdown-divider" style="margin-top:2px"></div>
                                                    <?php } ?>
                                                    <?php if(in_array(ARSIV_SIL, $_SESSION['sayfa_idler']) && !$arsiv_alt_varmi['arsiv_alt_sayisi']){ ?>
                                                        <a 
                                                            class="dropdown-item pt-0 pb-0" 
                                                            onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"  
                                                            href="/index.php?url=arsiv_kalem_db_islem&islem=arsiv_kalem_sil&id=<?php echo $arsiv_kalem['id']; ?>">Sil</a>
                                                    <?php }else{ ?>
                                                       <a 
                                                            class="dropdown-item custom-disabled-item pt-0 pb-0" 
                                                            data-bs-html="true"
                                                            data-bs-title="<b class='text-danger'>Alt Arşiv Olduğun İçin Silinemez veya Silme İzniniz Yoktur!</b>"
                                                            href="javascript:;" 
                                                            aria-disabled="true">
                                                            Sil
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
      <!-- Stok Kalem Ekle -->
    <div class="modal fade" id="arsiv-kalem-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form  action="/index.php?url=arsiv_kalem_db_islem" method="POST" id="arsiv-kalem-ekle-form">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="staticBackdropLabel"> Ekle</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-floating p-1">
                            <input type="text" class="form-control" name="arsiv" id="arsiv" required >
                            <label for="arsiv" class="form-label">Arşiv</label>
                        </div>
                        <?php  
                            $sth = $conn->prepare('SELECT * FROM departmanlar WHERE firma_id = :firma_id');
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="form-floating p-1">
                            <select class="select-margin form-select" id="departman_idler" name="departman_idler[]" multiple required>
                                <?php foreach ($departmanlar as $departman) { ?>
                                    <option  value="<?php echo $departman['id']; ?>">
                                        <?php echo $departman['departman']; ?>
                                    </option>
                                <?php }?>
                            </select>
                            <label style="z-index: 1" for="departman_idler" class="form-label">Departman Seçiniz</label>
                        </div>
                        <div class="form-floating p-1">
                            <select class="form-select" id="arsiv_tur_id" name="arsiv_tur_id" required>
                                <option selected disabled value="">Seçiniz..</option>
                                <option  value="0">DİJİTAL</option>
                                <option  value="1">FİZİKSEL</option>
                            </select>
                            <label for="arsiv_tur_id" class="form-label">Arşim Türü</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit" name="arsiv_kalem_ekle" id="arsiv-kalem-ekle-button">
                            <i class="fa-regular fa-square-plus"></i> KAYDET
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            İPTAL <i class="fa-regular fa-rectangle-xmark"></i>
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
            //formu gönderdiğinde buttonu pasif yapma
            $("#arsiv-kalem-ekle-form").submit(function(){
                $("#arsiv-kalem-ekle-button").addClass('disabled');
                return true;
            });

            //modal açıldığında focus yapma
            $('#arsiv-kalem-ekle-modal').on('shown.bs.modal', function () {
                $('#arsiv').focus();
            });
        });
    </script>
