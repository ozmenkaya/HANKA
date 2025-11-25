<?php 
    include "include/oturum_kontrol.php";

    $id = isset($_GET['id']) ? $_GET['id'] : 0;

    $sth = $conn->prepare('SELECT * FROM stok_alt_depolar WHERE id =:id');
    $sth->bindParam('id', $id);
    $sth->execute();
    $stok_alt_depo = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($stok_alt_depo))
    {
        require_once "include/yetkisiz.php";
        exit;
    }

    #echo "<pre>"; print_R($stok_alt_depo); exit;
?>
<div class="row mt-2">
    <div class="card">
        <div class="card-header">
            <h5>Alt Stok Depo Düzenle</h5>
        </div>
        <div class="card-body pt-0">
            <form action="/index.php?url=stok_alt_depolar_db_islem" method="POST">
                <input type="hidden" name="id" value="<?php echo $stok_alt_depo['id']; ?>">
                <input type="hidden" name="stok_alt_kalem_id" value="<?php echo $stok_alt_depo['stok_alt_kalem_id']; ?>">
                <input type="hidden" name="stok_kodu" value="<?php echo $stok_alt_depo['stok_kodu']; ?>">
                <input type="hidden" name="stok_id" value="<?php echo $_GET['stok_id']; ?>">
                <div class="alert alert-danger fw-bold d-flex justify-content-between border-3">
                    <span>
                        1- Stok Güncellenirken Takip İçin 
                        <span class="text-decoration-underline fst-italic">QR</span> Kod Oluşacaktır.
                    </span>
                    <span>
                        <i class="fa-solid fa-qrcode"></i>
                    </span>
                </div>

                <div class="form-floating col-md-12">
                    <input type="number" class="form-control" name="adet" id="adet" value="<?php echo $stok_alt_depo['adet']; ?>" required >
                    <label for="adet" class="form-label">Miktar</label>
                </div>

                <div class="row g-3 mt-1">
                    <div class="form-floating col-md-6">
                        <select class="form-select" id="para_cinsi" name="para_cinsi" required>
                            <option value="TL"      <?php echo $stok_alt_depo['para_cinsi'] == 'TL' ? 'selected':''; ?>>TL</option>
                            <option value="DOLAR"   <?php echo $stok_alt_depo['para_cinsi'] == 'DOLAR' ? 'selected':''; ?>>DOLAR</option>
                            <option value="EURO"    <?php echo $stok_alt_depo['para_cinsi'] == 'EURO' ? 'selected':''; ?>>EURO</option>
                            <option value="POUND"   <?php echo $stok_alt_depo['para_cinsi'] == 'POUND' ? 'selected':''; ?>>POUND</option>
                        </select>
                        <label for="para_cinsi" class="form-label">Para Cinsi</label>
                    </div>

                    <div class="form-floating col-md-6">
                        <input type="number" class="form-control" name="maliyet" id="maliyet" value="<?php echo $stok_alt_depo['maliyet']; ?>" required step="0.001">
                        <label for="maliyet" class="form-label">Toplam Maliyet</label>
                    </div>
                </div>

                <div class="form-floating col-md-12 mt-2">
                    <input type="text" class="form-control" name="fatura_no" id="fatura_no" value="<?php echo $stok_alt_depo['fatura_no']; ?>"  >
                    <label for="fatura_no" class="form-label">Fatura No</label>
                </div>
                <?php 
                    $sth = $conn->prepare('SELECT id, firma_adi FROM tedarikciler WHERE firma_id = :firma_id');
                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                    $sth->execute();
                    $tedarikciler = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>    
                <div class="form-floating col-md-12 mt-2">
                    <select name="tedarikci_id" id="tedarikci_id" class="form-control" required> 
                        <?php foreach ($tedarikciler as $tedarikci) { ?>
                            <option value="<?php echo $tedarikci['id'];?>" <?php echo $tedarikci['id'] == $stok_alt_depo['tedarikci_id'] ? 'selected':''; ?>><?php echo $tedarikci['firma_adi']; ?></option>
                        <?php }?>
                    </select>
                    <label for="tedarikci_id" class="form-label">Tedarikçi</label>
                </div>

                <?php 
                    $sql = "SELECT siparisler.stok_alt_depo_kod,siparisler.siparis_no, siparisler.isin_adi,
                            musteri.marka FROM `siparisler` 
                            JOIN musteri ON musteri.id = siparisler.musteri_id
                            WHERE siparisler.firma_id = :firma_id ORDER BY siparisler.isin_adi ASC";
                    $sth = $conn->prepare($sql);
                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                    $sth->execute();
                    $siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="form-floating col-md-12 mt-2">
                    <select class="form-select form-select-lg" id="stok_alt_depo_kod" name="stok_alt_depo_kod">
                        <option value="">Seçiniz</option>    
                        <?php foreach ($siparisler as $index => $siparis) { ?>
                            <option value="<?php echo $siparis['stok_alt_depo_kod']; ?>" <?php echo $siparis['stok_alt_depo_kod'] == $stok_alt_depo['stok_alt_depo_kod'] ? 'selected':'';?>>
                                <?php echo ($index + 1).' - '.$siparis['siparis_no'].' - '.$siparis['marka'].' - '.$siparis['isin_adi'];?>
                            </option>  
                        <?php }?>
                    </select>
                        <label for="stok_alt_depo_kod" class="form-label">Siparişler</label>
                </div>
                <div class="form-floating col-md-12 mt-2">
                    <button type="submit" class="btn btn-warning" name="stok_alt_depo_guncelle">
                        <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
    unset($_SESSION['durum']);
    unset($_SESSION['mesaj']);
?>
