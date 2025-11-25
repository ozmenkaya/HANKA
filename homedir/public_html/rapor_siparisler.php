<?php
    require_once "include/oturum_kontrol.php";
?>
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>
                        <i class="fa-solid fa-flag-checkered"></i> Bitmiş Sipariş Raporları
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover table-sm">
                            <thead class="table-primary">
                                <tr>
                                    <th class="text-center;align-middle">#</th>
                                    <th class="align-middle">Sipariş No</th>
                                    <th class="align-middle" style="max-width: 250px;">İşin Adı</th>
                                    <th class="align-middle">Müşteri</th>
                                    <th class="align-middle">Müşteri Temsilcisi</th>
                                    <th class="align-middle">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                
                                    $sth = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, 
                                    musteri.marka,
                                    personeller.ad, personeller.soyad
                                    FROM siparisler 
                                    JOIN musteri ON musteri.id = siparisler.musteri_id
                                    JOIN personeller ON personeller.id = siparisler.musteri_temsilcisi_id
                                    WHERE siparisler.firma_id = :firma_id AND siparisler.islem = "tamamlandi" ORDER BY siparisler.id DESC');
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->execute();
                                    $tamamlanmis_siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($tamamlanmis_siparisler as $key => $siparis) { ?>
                                    <tr>
                                        <th class="text-center align-middle table-primary"><?php echo $key + 1; ?></th>
                                        <td class="align-middle"><?php echo $siparis['siparis_no']; ?></td>
                                        <td class="align-middle"><?php echo $siparis['isin_adi']; ?></td>
                                        <td class="align-middle"><?php echo $siparis['marka']; ?></td>
                                        <td class="align-middle"><?php echo $siparis['ad'].' '.$siparis['soyad']; ?></td>
                                        <td class="align-middle text-center">
                                            <a href="/index.php?url=rapor_siparis_detay&siparis-id=<?php echo $siparis['id'];?>" class="btn btn-sm btn-success">
                                                <i class="fa-solid fa-flag-checkered"></i>
                                            </a>
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
<?php 
    include_once "include/uyari_session_oldur.php"; 
?>