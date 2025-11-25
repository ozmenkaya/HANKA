<?php 
	include "include/oturum_kontrol.php";
	$sth = $conn->prepare('SELECT personeller.*, yetkiler.yetki FROM personeller JOIN yetkiler ON yetkiler.id = personeller.yetki_id  WHERE firma_id = :firma_id;');
	$sth->bindParam("firma_id", $_SESSION['firma_id']);
	$sth->execute();
	$personeller = $sth->fetchAll(PDO::FETCH_ASSOC);
	#echo "<pre>"; print_r($personeller); exit;
?>
<div class="row">
        <div class="card mt-2">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-solid fa-users"></i> Personeller
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
                            <a href="/index.php?url=personel_db_islem&islem=personel_csv" 
                                class="btn btn-success"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="bottom" 
                                data-bs-title="Excel"
                            >
                                <i class="fa-regular fa-file-excel"></i>
                            </a>
                            <a href="/index.php?url=personel_ekle" class="btn btn-primary"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="bottom" 
                                data-bs-title="Personel Ekle"
                            > 
                                <i class="fa-solid fa-user-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table id="myTable" class="table table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Sıra</th>
                                <th>Adı</th>
                                <th>Soyadı</th>
                                <th><i class="fa-regular fa-envelope"></i> Email</th>
                                <th>İşe Başlama Tarihi</th>
                                <th>Departmanlar</th>
                                <th>Görevi</th>
                                <th>Durumu</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($personeller as $index => $personel){ 
                                $satir_class = '';
                                if($personel['yetki_id'] == URETIM_YETKI_ID)        $satir_class= 'table-warning';
                                elseif($personel['yetki_id'] == MUSTERI_TEMSILCI_ID)$satir_class= 'table-secondary';
                                elseif($personel['yetki_id'] == ADMIN_YETKI_ID)     $satir_class= 'table-success';
                                $sth = $conn->prepare('SELECT COUNT(*) AS toplam_musteri FROM musteri 
                                WHERE musteri_temsilcisi_id = :musteri_temsilcisi_id AND firma_id = :firma_id');
                                $sth->bindParam('musteri_temsilcisi_id', $personel['id']);
                                $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                $sth->execute();
                                $musteri_sayisi = $sth->fetch(PDO::FETCH_ASSOC);   
                                ?>
                                <tr class="<?php echo $satir_class;?>">
                                    <th class="table-primary"><?php echo $index + 1; ?></th>
                                    <th><?php echo $personel['ad']; ?></th>
                                    <th><?php echo $personel['soyad']; ?></th>
                                    <th>
                                        <i class="fa-regular fa-envelope"></i>
                                        <?php echo $personel['email']; ?>
                                    </th>
                                    <th><?php echo date('d-m-Y', strtotime($personel['ise_baslama'])); ?></th>
                                    <?php 
                                        $sth = $conn->prepare('SELECT departmanlar.departman FROM personel_departmanlar JOIN departmanlar 
                                            ON departmanlar.id = personel_departmanlar.departman_id WHERE personel_id = :personel_id');
                                        $sth->bindParam('personel_id', $personel['id']);
                                        $sth->execute();
                                        $personel_departmanlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                    <th>
                                        <?php foreach ($personel_departmanlar as $personel_departman ) { ?>
                                        <span class="badge bg-secondary"> <?php echo $personel_departman['departman'] ?> </span>
                                        <?php } ?>
                                    </th>
                                    <th><?php echo $personel['yetki']; ?></th>
                                    <th>
                                        <?php if($personel['durum'] == 'aktif'){ ?>
                                        <span class="badge bg-success">AKTİF</span>
                                        <?php }else{ ?>
                                        <span class="badge bg-danger">PASİF</span>
                                        <?php } ?>
                                    </th>
                                    <td>
                                        <div class="d-md-flex justify-content-end">
                                            <div class="btn-group" role="group" aria-label="Basic example">
                                                <a href="/index.php?url=personel_guncelle&id=<?php echo $personel['id']; ?>"
                                                    class="btn btn-warning"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Güncelle"
                                                >
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                                <?php if(!in_array($personel['yetki_id'],[ADMIN_YETKI_ID, SUPER_ADMIN_YETKI_ID] ) && $musteri_sayisi['toplam_musteri'] == 0){ ?>
                                                    <a href="/index.php?url=personel_db_islem&islem=personel_sil&id=<?php echo $personel['id']; ?>" 
                                                        onClick="return confirm('Silmek İstediğinize Emin Misiniz?')" 
                                                        class="btn btn-danger"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil"
                                                    >
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                <?php }else{ ?> 
                                                    <button
                                                        class="btn btn-danger disabled"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil Yetki Yok"
                                                    >
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                <?php }?>
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
</div>
<?php 
    include_once "include/uyari_session_oldur.php";
?>