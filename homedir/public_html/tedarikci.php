<?php 
include_once "include/oturum_kontrol.php";

$sth = $conn->prepare('SELECT * FROM tedarikciler WHERE firma_id = :firma_id');
$sth->bindParam('firma_id', $_SESSION['firma_id']);
$sth->execute();
$tedarikciler = $sth->fetchAll(PDO::FETCH_ASSOC);

if(!in_array(TEDARIKCI_GOR, $_SESSION['sayfa_idler']))
{
    require_once "include/yetkisiz.php";
    exit;
}
#echo "<pre>"; print_r($musteriler); exit;
#echo "<pre>"; print_r($_SESSION); exit;

?>
<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-parachute-box"></i> Tedarikçiler 
                <span class="text-primary">(<?php echo count($tedarikciler); ?> Firma)</span>
            </h5>
            <div>
                <?php if(in_array(TEDARIKCI_OLUSTUR, $_SESSION['sayfa_idler'])){ ?>
                    <a href="/index.php?url=tedarikci_ekle" class="btn btn-primary"
                        data-bs-toggle="tooltip" 
                        data-bs-placement="bottom" 
                        data-bs-title="Tedarikci Ekle"
                    >
                        <i class="fa-solid fa-plus"></i>  
                    </a>
                <?php }?>
            </div>
        </div>
        <div class="card-body pt-0">
            <table id="myTable" class="table table-hover table-striped" >
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Firma Adı</th>
                        <th>Firma Ünvanı</th>
                        <th>
                            <i class="fa-regular fa-envelope"></i> E-mail
                        </th>
                        <th>
                            <i class="fa-regular fa-address-book"></i> Adres
                        </th>
                        <th>Telefon</th>
                        <th>Fason Durumu</th>
                        <th>Stok/Departman</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tedarikciler as $key=>$tedarikci){ ?>
                    <tr class="<?php echo $tedarikci['fason'] == 'evet' ? 'table-secondary':'table-success';?> tedarikci-row" 
                        data-tedarikci-id="<?php echo $tedarikci['id']; ?>"
                        data-tedarikci-adi="<?php echo htmlspecialchars($tedarikci['firma_adi']); ?>"
                        data-fason="<?php echo $tedarikci['fason']; ?>"
                        style="cursor: pointer;"
                        title="Detayları görmek için tıklayın"
                    >
                        <th class="table-primary"><?php echo $key + 1; ?></th>
                        <td><?php echo $tedarikci['firma_adi']; ?></td>
                        <td><?php echo $tedarikci['tedarikci_unvani']; ?></td>
                        <td>
                            <i class="fa-regular fa-envelope"></i>
                            <?php echo $tedarikci['email']; ?>
                        </td>
                        <td>
                            <i class="fa-regular fa-address-book"></i>
                            <?php echo $tedarikci['tedarikci_adresi']; ?>
                        </td>
                        <td>
                            <a href="tel:<?php echo $tedarikci['tedarikci_telefonu']; ?>" class="badge bg-secondary p-2 fw-bold fs-6 text-decoration-none">
                                <?php echo $tedarikci['tedarikci_telefonu']; ?>
                            </a>
                        </td>
                        <td>
                            <?php if($tedarikci['fason'] == 'evet'){ ?>
                                <span class="badge bg-secondary">FASON</span>
                            <?php }else{?> 
                                <span class="badge bg-success">TEDARİKÇİ</span>
                            <?php }?>
                        </td>
                        <td>
                            <?php if($tedarikci['fason'] == 'evet'){ ?>
                                <?php 
                                    $departman_idler_json = isset($tedarikci['departman_idler']) ? $tedarikci['departman_idler'] : null;
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
                                    <span class="badge bg-secondary p-2 mb-1">
                                        <?php echo $departman['departman']; ?>
                                    </span>
                                <?php }?>
                            <?php }else{?> 
                                <?php 
                                    $stok_kalem_idler   = json_decode($tedarikci['stok_kalem_idler'], true);
                                    $stok_kalem_idler   = empty($stok_kalem_idler) ? [] : $stok_kalem_idler;
                                    $stok_kalemler      = [];
                                    if(!empty($stok_kalem_idler)){
                                        $stok_kalem_idler = implode(',',$stok_kalem_idler);
                                        $sql = "SELECT stok_kalem FROM stok_kalemleri WHERE id IN({$stok_kalem_idler})";
                                        $sth = $conn->prepare($sql);
                                        $sth->execute();
                                        $stok_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    }
                                ?>

                                <?php foreach ($stok_kalemler as $stok_kalem) { ?>
                                    <span class="badge bg-secondary p-2 mb-1">
                                        <?php echo $stok_kalem['stok_kalem']; ?>
                                    </span>
                                <?php }?>
                            <?php }?>
                        </td>
                        <td>
                            <div class="d-md-flex justify-content-end"> 
                                <div class="btn-group" role="group" aria-label="Basic example">
                                    <?php if(in_array(TEDARIKCI_DUZENLE, $_SESSION['sayfa_idler'])){ ?>
                                        <a href="/index.php?url=tedarikci_guncelle&id=<?php echo $tedarikci['id']; ?>"  
                                            class="btn btn-warning"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            data-bs-title="Güncelle"
                                        >
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                    <?php } ?>
                                    <?php if(in_array(TEDARIKCI_SIL, $_SESSION['sayfa_idler'])){ ?>
                                        <a href="/index.php?url=tedarikci_db_islem&islem=tedarikci_sil&id=<?php echo $tedarikci['id']; ?>" onClick="return confirm('Silmek İstediğinize Emin Misiniz?')" 
                                            class="btn btn-danger"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            data-bs-title="Sil"
                                        >
                                            <i class="fa-solid fa-trash-can"></i>
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
<?php 
    include_once "include/uyari_session_oldur.php";
?>

<script>
$(document).ready(function() {
    // Tedarikçi satırına tıklayınca ilgili sayfaya git
    $('.tedarikci-row').on('click', function(e) {
        // Eğer İşlemler sütunundaki butonlara tıklanmışsa, satır tıklamasını iptal et
        if ($(e.target).closest('.btn-group').length > 0) {
            return;
        }
        
        var tedarikciId = $(this).data('tedarikci-id');
        var fason = $(this).data('fason');
        
        if (fason === 'evet') {
            // FASON: Planlama sayfasına git
            window.location.href = '/index.php?url=tedarikci_planlama&tedarikci_id=' + tedarikciId;
        } else {
            // TEDARİKÇİ: Stok hareketleri sayfasına git
            window.location.href = '/index.php?url=tedarikci_stok_hareketleri&tedarikci_id=' + tedarikciId;
        }
    });
    
    // Satır üzerine gelindiğinde vurgu efekti
    $('.tedarikci-row').hover(
        function() {
            $(this).addClass('table-active');
        },
        function() {
            $(this).removeClass('table-active');
        }
    );
});
</script>
