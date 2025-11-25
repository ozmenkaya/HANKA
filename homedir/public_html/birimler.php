<?php
        require_once "include/oturum_kontrol.php";
?> 

        <div class="row">
            <div class="card mt-2 border-secondary">
                <div class="card-header d-flex justify-content-between border-secondary">
                    <h5>
                        <i class="fa-solid fa-ruler-vertical"></i>
                        Birimler 
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
                                <?php //if(in_array(DEPARTAN_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                                    <button type="button" class="btn btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#birim-ekle-modal"
                                        data-bs-placement="bottom" 
                                        data-bs-title="Ekle"
                                    >
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                <?php //} ?>
                            </div>
                        </div>
                    </div> 
                </div>
       
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table 
                            id="demo-custom-toolbar" 
                            data-toggle="table" 
                            data-toolbar="#demo-delete-row" 
                            data-search="true"
                            data-show-refresh="true" 
                            data-show-columns="true" 
                            data-sort-name="id" 
                            data-page-list="[5, 10, 20]"
                            data-page-size="50" 
                            data-pagination="true" 
                            data-locale="tr-TR"
                            data-show-pagination-switch="true" 
                            >
                            <thead class="table-light">
                                <tr>
                                    <th data-field="id" data-sortable="true" data-align="center">#</th>
                                    <th data-field="birim" data-sortable="true">Birim</th>
                                    <th data-field="islemler" data-align="center">İşlemler</th>
                                </tr> 
                            </thead>
                        
                            <tbody>
                                <?php 
                                    $sth = $conn->prepare('SELECT * FROM birimler WHERE firma_id = :firma_id');
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->execute();
                                    $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($birimler as $index => $birim) { ?>
                                    <tr>
                                        <td style="padding: 3px 13px"><?php echo $index + 1; ?></td>
                                        <td style="padding: 3px 13px"><?php echo $birim['ad']; ?></td>
                                        <td style="padding: 3px 13px">
                                            <div class="d-flex justify-content-end"> 
                                                <div class="btn-group" role="group">
                                                    <a href="/index.php?url=birim_guncelle&id=<?php echo $birim['id']; ?>" 
                                                        class="btn btn-warning"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Düzenle"
                                                        style="padding: 4px 10px;"
                                                    >
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </a>

                                                    <a href="/index.php?url=birim_db_islem&islem=birim_sil&id=<?php echo $birim['id']; ?>" 
                                                        onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"
                                                        class="btn btn-danger"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil"
                                                        style="padding: 4px 10px;"
                                                    >
                                                        <i class="fa-solid fa-trash-can"></i>
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
        <!-- Birim Ekle Modal -->
                <div class="modal fade" id="birim-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h4 class="modal-title" id="myCenterModalLabel">Birim Ekle</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <form action="/index.php?url=birim_db_islem" method="POST" id="birim-ekle-form" class="row g-3 needs-validation">
                                <div>  
                                    <label for="birim" class="form-label">Birim</label>
                                    <input type="text" class="form-control" name="birim" id="birim" required >                                  
                                </div>    
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success waves-effect waves-light" name="birim_ekle" id="birim-ekle-button">Kaydet</button>
                                    <button type="button" class="btn btn-danger waves-effect waves-light" data-bs-dismiss="modal">İptal</button>
                                </div>
                            </form>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div> 
        </div>
        <?php  
            include_once "include/uyari_session_oldur.php"; 
        ?>
        <script>
            $(function(){
                //formu gönderdiğinde buttonu pasif yapma
                $("#birim-ekle-form").submit(function(){
                    $("#birim-ekle-button").addClass('disabled');
                    return true;
                });

                //modal açıldığında focus yapma
                $('#birim-ekle-modal').on('shown.bs.modal', function () {
                    $('#birim').focus();
                });

            });                                        
        </script>