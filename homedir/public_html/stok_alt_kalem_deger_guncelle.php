<?php 
    require_once "include/oturum_kontrol.php";

    $id = isset($_GET['id']) ? $_GET['id'] : 0;

    $sth = $conn->prepare('SELECT * FROM stok_alt_kalem_degerler WHERE id = :id AND firma_id = :firma_id');
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $stok_kalem_deger = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($stok_kalem_deger))
    {
        require_once "include/yetkisiz.php";
        die();
    }
?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header">
            <h5>Stok Alt Kalem Güncelle</h5>
        </div>
        <div class="card-body pt-0">
            <form action="/index.php?url=stok_alt_kalem_deger_db_islem" method="POST">
                <input type="hidden" name="id" value="<?php echo $stok_kalem_deger['id'];?>">
                <input type="hidden" name="stok_id" value="<?php echo $stok_kalem_deger['stok_id'];?>">
                <input type="hidden" name="ad_eski" value="<?php echo $stok_kalem_deger['ad'];?>">
                <div class="form-floating col-md-12">
                    <input type="text" class="form-control" name="ad" id="ad" value="<?php echo $stok_kalem_deger['ad']; ?>" required >
                    <label for="ad" class="form-label">Alt Stok Adı</label>
                </div>
                <div class="form-floating col-md-12 mt-2">
                    <select name="kolon_tipi" id="kolon_tipi" class="form-control" required>
                        <option value="number" <?php echo $stok_kalem_deger['kolon_tipi'] == 'number' ? 'selected':''; ?>>Sayı</option>
                        <option value="text"   <?php echo $stok_kalem_deger['kolon_tipi'] == 'text' ? 'selected':''; ?>>Yazı</option>
                    </select>
                    <label for="kolon_tipi">Opsiyonlar</label>
                </div>
                <div class="form-floating col-md-12 mt-2">
                    <button class="btn btn-warning" type="submit" name="stok_alt_kalem_deger_guncelle">
                        <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include_once "include/uyari_session_oldur.php"; ?>
