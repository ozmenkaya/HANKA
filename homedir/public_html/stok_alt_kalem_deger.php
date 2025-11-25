<?php  
    require_once "include/oturum_kontrol.php";

    //echo 21; exit;

    $stok_id = intval($_GET['stok_id']);

    $sth = $conn->prepare('SELECT * FROM stok_kalemleri WHERE id = :id AND firma_id = :firma_id');
    $sth->bindParam('id', $stok_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $stok_kalem = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($stok_kalem)){
        include "include/yetkisiz.php";
        exit;
    }

    $sth = $conn->prepare('SELECT * FROM stok_alt_kalem_degerler WHERE stok_id = :stok_id');
    $sth->bindParam('stok_id', $stok_id);
    $sth->execute();
    $stok_alt_kalem_degerler = $sth->fetchAll(PDO::FETCH_ASSOC);
    #echo "<pre>"; print_r($stok_alt_kalemleri); exit;
?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-sharp fa-solid fa-layer-group fs-4"></i>
                Stok Kalem: <b><?php echo $stok_kalem['stok_kalem']; ?></b>
            </h5>
            <div>
                <div class="d-flex justify-content-end"> 
                    <div class="btn-group" role="group" aria-label="Basic example">
                        <a href="javascript:window.history.back();" 
                            class="btn btn-secondary"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom" 
                            data-bs-title="Geri Dön"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>

                        <button type="button" class="btn btn-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#stok-alt-kalem-ekle-modal"
                            data-bs-placement="bottom" 
                            data-bs-title="Ekle"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body pt-0">
            <?php require_once "include/uyari_session.php";?>
            <table id="myTable" class="table table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Alt Stok Adı</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody> 
                    <?php foreach ($stok_alt_kalem_degerler as $index => $stok_alt_kalem_deger) { ?>
                        <tr>
                            <th class="table-primary">
                                <?php echo $index + 1;?>
                            </th>
                            <td><?php echo $stok_alt_kalem_deger['ad']; ?></td>
                            <td>
                                <div class="d-md-flex justify-content-end"> 
                                    <div class="btn-group" role="group" aria-label="Basic example">
                                        <a href="/index.php?url=stok_alt_kalem_deger_guncelle&id=<?php echo $stok_alt_kalem_deger['id']; ?>" 
                                            class="btn btn-warning"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            data-bs-title="Güncelle" 
                                        >
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a href="/index.php?url=stok_alt_kalem_deger_db_islem&islem=stok_alt_kalem_deger_sil&stok_id=<?php echo $stok_id;?>&id=<?php echo $stok_alt_kalem_deger['id']; ?>" 
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
                    <?php }?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Stok Kalem Ekle -->
<div class="modal fade" id="stok-alt-kalem-ekle-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form  action="/index.php?url=stok_alt_kalem_deger_db_islem" method="POST" id="stok-kalem-deger-ekle-form">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" >
                        Stok Alt Kalem Değer Ekle
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="stok_id" value="<?php echo $stok_id;?>">
                    <div class="form-floating col-md-12">
                        <input type="text" class="form-control" name="ad" id="ad" required >
                        <label for="ad" class="form-label">Alt Stok Adı</label>
                    </div>
                    <div class="form-floating col-md-12 mt-2">
                        <select name="kolon_tipi" id="kolon_tipi" class="form-control" required>
                            <option value="" selected disabled>Seç...</option>
                            <option value="number">Sayı</option>
                            <option value="text">Yazı</option>
                        </select>
                        <label for="kolon_tipi">Opsiyonlar</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit" name="stok_alt_kalem_deger_ekle" id="stok-kalem-deger-ekle-button">
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
        $("#stok-kalem-deger-ekle-form").submit(function(){
            $("#stok-kalem-deger-ekle-button").addClass('disabled');
            return true;
        });
    });
</script>