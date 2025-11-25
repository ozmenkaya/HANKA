<?php
    require_once "include/oturum_kontrol.php";

    $sth = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, 
    musteri.marka,
    personeller.ad, personeller.soyad
    FROM siparisler 
    JOIN musteri ON musteri.id = siparisler.musteri_id
    JOIN personeller ON personeller.id = siparisler.musteri_temsilcisi_id
    WHERE siparisler.firma_id = :firma_id AND siparisler.islem = "teslim_edildi"');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $teslimi_bitmis_siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT siparisler.id, siparisler.siparis_no, siparisler.isin_adi, siparisler.adet,
    musteri.marka,
    personeller.ad, personeller.soyad
    FROM siparisler 
    JOIN musteri ON musteri.id = siparisler.musteri_id
    JOIN personeller ON personeller.id = siparisler.musteri_temsilcisi_id
    WHERE siparisler.firma_id = :firma_id AND siparisler.islem = "tamamlandi"');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $teslimi_bitmemis_siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT 
    siparisler.isin_adi,siparisler.siparis_no,
    `planlama`.`arsiv_altlar`,`planlama`.`stok_alt_kalemler`,`planlama`.`stok_alt_depolar`,
    `planlama`.`stok_alt_depo_adetler`,planlama.isim, planlama.mevcut_asama,`planlama`.`departmanlar`,
    planlama.fason_durumlar, planlama.fason_tedarikciler,planlama.makinalar, `planlama`.`arsiv_altlar`,
    planlama.id, `planlama`.`stok_alt_kalemler`, planlama.stok_alt_depo_adetler,planlama.stok_alt_depolar
    FROM siparisler 
    JOIN planlama ON planlama.siparis_id = siparisler.id 
    WHERE siparisler.firma_id = :firma_id AND planlama.onay_durum = 'evet' AND planlama.durum != 'bitti'
    ORDER BY `planlama`.`sira` ASC
    ";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $malzeme_siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);
?>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>
                                <i class="fa-solid fa-box-open"></i> Depodaki Ürünler
                            </h5>
                        </div>
                        <div class="card-body pt-0">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    <button class="nav-link active position-relative fw-bold" id="nav-tab-malzemeleri-hazirla" data-bs-toggle="tab" 
                                        data-bs-target="#nav-malzemeleri-hazirla" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                        1- Hazırlanacak Malzemeler
                                        <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-primary">
                                            <?php echo count($malzeme_siparisler); ?>
                                            <span class="visually-hidden">Hazırlanacak Malzemeler</span>
                                        </span>
                                    </button>
                                    <button class="nav-link position-relative fw-bold" id="nav-tab-gonderilecek-siparisler" data-bs-toggle="tab" 
                                        data-bs-target="#nav-gonderilecek-siparisler" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                        2- Gönderimi Bitmeyen Siparişler
                                        <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-primary">
                                            <?php echo count($teslimi_bitmemis_siparisler); ?>
                                            <span class="visually-hidden">Gönderimi Bitmeyen Siparişler</span>
                                        </span>
                                    </button>
                                    <button class="nav-link  position-relative fw-bold" id="nav-tab-gonderilmis-siparisler" data-bs-toggle="tab" 
                                        data-bs-target="#nav-gonderilmis-siparisler" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                        3- Gönderimi Bitmiş Siparişler
                                        <span class="position-absolute top-0 start-70 translate-middle badge rounded-pill bg-success">
                                            <?php echo count($teslimi_bitmis_siparisler); ?>
                                            <span class="visually-hidden">Gönderimi Bitmiş Siparişler</span>
                                        </span>
                                    </button>
                                </div>
                            </nav>

                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="nav-malzemeleri-hazirla" role="tabpanel" 
                                    aria-labelledby="nav-tab-malzemeleri-hazirla" tabindex="0">
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-striped table-hover" >
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Sipariş No</th>
                                                    <th>İşin Adı</th>
                                                    <th>İsim</th>
                                                    <th>Departman</th>
                                                    <th>Fason/Tedarikçi</th>
                                                    <th>Makina</th>
                                                    <th>Arşiv</th>
                                                    <th>Stok Kalemler</th>
                                                    <th>Stok Alt Kalemler</th>
                                                    <th>Stok Alt Depo</th>
                                                    <th>Stok Alt Depo Adet</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($malzeme_siparisler as $index => $malzeme_siparis) { ?>
                                                    <?php 
                                                        $mevcut_asama           = $malzeme_siparis['mevcut_asama'];
                                                        $departmanlar           = json_decode($malzeme_siparis['departmanlar'], true);
                                                        $fason_durumlar         = json_decode($malzeme_siparis['fason_durumlar'], true);
                                                        $fason_tedarikciler     = json_decode($malzeme_siparis['fason_tedarikciler'], true);
                                                        $makinalar              = json_decode($malzeme_siparis['makinalar'], true);
                                                        $arsiv_altlar           = json_decode($malzeme_siparis['arsiv_altlar'], true);
                                                        $stok_alt_kalemler      = json_decode($malzeme_siparis['stok_alt_kalemler'], true);
                                                        $stok_alt_depolar       = json_decode($malzeme_siparis['stok_alt_depolar'], true);
                                                        $stok_alt_depo_adetler  = json_decode($malzeme_siparis['stok_alt_depo_adetler'], true);
                                                        
                                                        $departman_id           = isset($departmanlar[$mevcut_asama]) ? $departmanlar[$mevcut_asama] : 0 ; 
                                                        $fason_durum            = isset($fason_durumlar[$mevcut_asama]) ? $fason_durumlar[$mevcut_asama] : 0; 
                                                        $fason_tedarikci_id     = isset($fason_tedarikciler[$mevcut_asama]) ? $fason_tedarikciler[$mevcut_asama] : 0 ; 
                                                        $makina_id              = isset($makinalar[$mevcut_asama]) ? $makinalar[$mevcut_asama] : 0; 
                                                        $arsiv_alt_idler        = array_filter(isset($arsiv_altlar[$mevcut_asama]) ? $arsiv_altlar[$mevcut_asama] : []); 
                                                        $stok_alt_kalem_idler   = array_filter(isset($stok_alt_kalemler[$mevcut_asama]) ? $stok_alt_kalemler[$mevcut_asama] : []); 
                                                        $stok_alt_depo_idler    = array_filter(isset($stok_alt_depolar[$mevcut_asama]) ? $stok_alt_depolar[$mevcut_asama] : []); 
                                                        $stok_alt_depo_adetler  = array_filter(isset($stok_alt_depo_adetler[$mevcut_asama]) ? $stok_alt_depo_adetler[$mevcut_asama] : []); 
                                                        
                                                        $sql = 'SELECT departman FROM departmanlar WHERE id = :id';
                                                        $sth = $conn->prepare($sql);
                                                        $sth->bindParam('id', $departman_id);
                                                        $sth->execute();
                                                        $departman = $sth->fetch(PDO::FETCH_ASSOC);

                                                        if($fason_tedarikci_id != 0){
                                                            $sql = 'SELECT firma_adi,tedarikci_unvani FROM tedarikciler WHERE id = :id';
                                                            $sth = $conn->prepare($sql);
                                                            $sth->bindParam('id', $fason_tedarikci_id);
                                                            $sth->execute();
                                                            $fason = $sth->fetch(PDO::FETCH_ASSOC);
                                                        }

                                                        if($makina_id != 0){
                                                            $sql = 'SELECT makina_adi,makina_modeli,makina_seri_no FROM makinalar WHERE id = :id';
                                                            $sth = $conn->prepare($sql);
                                                            $sth->bindParam('id', $makina_id);
                                                            $sth->execute();
                                                            $makina = $sth->fetch(PDO::FETCH_ASSOC);
                                                        }
                                                        $alt_arsivler = [];
                                                        if(!empty($arsiv_alt_idler)){
                                                            $arsiv_alt_idler = implode(',',$arsiv_alt_idler);
                                                            $sql = "SELECT arsiv_kalemler.arsiv,arsiv_altlar.fatura_no
                                                            FROM `arsiv_altlar` 
                                                            JOIN arsiv_kalemler ON arsiv_kalemler.id = arsiv_altlar.arsiv_id
                                                            WHERE arsiv_altlar.id IN($arsiv_alt_idler)";
                                                            $sth = $conn->prepare($sql);
                                                            $sth->execute();
                                                            $alt_arsivler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                        }
                                                        $stok_kalem_ve_stok_alt_kalemler = [];
                                                        if($stok_alt_kalem_idler){
                                                            $stok_alt_kalem_idler = implode(',',$stok_alt_kalem_idler);
                                                            $sql = "SELECT stok_alt_kalemler.veri,stok_kalemleri.stok_kalem
                                                            FROM `stok_alt_kalemler`
                                                            JOIN stok_kalemleri ON stok_kalemleri.id = stok_alt_kalemler.stok_id
                                                            WHERE stok_alt_kalemler.id IN($stok_alt_kalem_idler)
                                                            ";
                                                            $sth = $conn->prepare($sql);
                                                            $sth->execute();
                                                            $stok_kalem_ve_stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                        }
                                                        $stok_alt_depolar = [];
                                                        if(!empty($stok_alt_depo_idler)){
                                                            $stok_alt_depo_idler = implode(',',$stok_alt_depo_idler);
                                                            $sql = "SELECT stok_kodu,fatura_no FROM `stok_alt_depolar` 
                                                            WHERE id IN($stok_alt_depo_idler)";
                                                            $sth = $conn->prepare($sql);
                                                            $sth->execute();
                                                            $stok_alt_depolar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                                        }
                                                    ?>
                                                    <tr>
                                                        <th class="table-primary">
                                                            <?php echo $index+1; ?> 
                                                        </th>
                                                        <th class="table-secondary"><?php echo $malzeme_siparis['siparis_no'];?></th>
                                                        <td><?php echo $malzeme_siparis['isin_adi'];?></td>
                                                        <td><?php echo $malzeme_siparis['isim'];?></td>
                                                        <td><?php echo isset($departman['departman']) ? $departman['departman'] : 'Departman bulunamadı'; ?></td>
                                                        <td>
                                                            <?php if($fason_durum == 1){ ?>
                                                                <span class="badge bg-success">EVET</span>
                                                                / <?php echo $fason['firma_adi'].' '.$fason['tedarikci_unvani'];?>
                                                            <?php }else{?>
                                                                <span class="badge bg-danger">HAYIR</span>
                                                                / -
                                                            <?php }?>
                                                        </td>
                                                        <td>
                                                            <?php if($makina_id == 0){ ?>
                                                                <span class="badge bg-success p-2">FASON</span>
                                                            <?php }else{?>
                                                                <span class="badge bg-secondary p-2">
                                                                    <?php 
                                                                        if (is_array($makina)) {
                                                                            echo $makina['makina_adi'] . ' ' . $makina['makina_modeli'] . ' ' . $makina['makina_seri_no'];
                                                                        } else {
                                                                            echo "Makina bulunamadı";
                                                                        }
                                                                    ?>
                                                                </span>
                                                            <?php }?>
                                                        </td>
                                                        <td>
                                                            <ul class="list-group">
                                                                <?php foreach ($alt_arsivler as $index => $alt_arsiv) { ?>
                                                                    <li class="list-group-item bg-light mb-1">
                                                                        <span class="text-danger fw-bold"><?php echo ($index+1).' - '?></span>
                                                                        <?php echo  $alt_arsiv['arsiv']. ' '.$alt_arsiv['fatura_no'] ;?>
                                                                    </li>
                                                                <?php }?>
                                                                <?php if(empty($alt_arsivler)){?>
                                                                    <li class="list-group-item bg-light text-danger fw-bold"> YOK </li>
                                                                <?php }?>
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <ul class="list-group">
                                                                <?php foreach ($stok_kalem_ve_stok_alt_kalemler as $index => $stok_kalem_ve_stok_alt_kalem) { ?>
                                                                    <li class="list-group-item bg-light mb-1">
                                                                        <span class="text-danger fw-bold"><?php echo ($index+1).' - '?></span>
                                                                        <?php echo $stok_kalem_ve_stok_alt_kalem['stok_kalem'];?>
                                                                    </li>
                                                                <?php }?>  
                                                                <?php if(empty($stok_kalem_ve_stok_alt_kalemler)){?>
                                                                    <li class="list-group-item bg-light text-danger fw-bold"> YOK </li>
                                                                <?php }?>  
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <ul class="list-group">
                                                                <?php foreach ($stok_kalem_ve_stok_alt_kalemler as $index => $stok_kalem_ve_stok_alt_kalem) { ?>
                                                                    <?php 
                                                                        $keys = array_keys(json_decode($stok_kalem_ve_stok_alt_kalem['veri'] ,true));
                                                                        $values = array_values(json_decode($stok_kalem_ve_stok_alt_kalem['veri'] ,true));
                                                                    ?>
                                                                    <li class="list-group-item bg-light mb-1">
                                                                        <span class="text-danger fw-bold"><?php echo ($index+1).' - '?></span>
                                                                        <?php echo implode('/', $keys).' <br>'.implode('-',$values); ?>
                                                                    </li>
                                                                <?php }?>  
                                                                <?php if(empty($stok_kalem_ve_stok_alt_kalemler)){?>
                                                                    <li class="list-group-item bg-light text-danger fw-bold"> YOK </li>
                                                                <?php }?>  
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <ul class="list-group">
                                                                <?php foreach ($stok_alt_depolar  as $index => $stok_alt_depo) { ?>
                                                                    <li class="list-group-item bg-light mb-1">
                                                                        <span class="text-danger fw-bold"><?php echo ($index+1).' - '?></span>
                                                                        <b>STOK KODU:</b> <?php echo $stok_alt_depo['stok_kodu']; ?> <br>
                                                                        <b>FATURA NO:</b> <?php echo $stok_alt_depo['fatura_no']; ?>
                                                                    </li>
                                                                <?php }?>
                                                                <?php if(empty($stok_alt_depolar)){?>
                                                                    <li class="list-group-item bg-light text-danger fw-bold"> YOK </li>
                                                                <?php }?> 
                                                            </ul>
                                                        </td>
                                                        <td>
                                                            <ul class="list-group">
                                                                <?php foreach ($stok_alt_depo_adetler as $index => $stok_alt_depo_adet) { ?>
                                                                    <li class="list-group-item bg-light mb-1">
                                                                        <span class="text-danger fw-bold"><?php echo ($index+1).' - '; ?></span>
                                                                        <?php echo $stok_alt_depo_adet.' Adet';?> 
                                                                    </li>
                                                                <?php }?>
                                                                <?php if(empty($stok_alt_depo_adetler)){?>
                                                                    <li class="list-group-item bg-light text-danger fw-bold"> YOK </li>
                                                                <?php }?>  
                                                            </ul>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                <?php }?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade show" id="nav-gonderilecek-siparisler" role="tabpanel" 
                                    aria-labelledby="nav-tab-gonderilecek-siparisler" tabindex="0">
                                    <div class="table-responsive">
                                        <table id="myTable" class="table" >
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Sipariş No</th>
                                                    <th>İşin Adı</th>
                                                    <th>Müşteri</th>
                                                    <th>Müşteri Temsilcisi</th>
                                                    <th>Üretilen Adet</th>
                                                    <th>Teslim Adet</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($teslimi_bitmemis_siparisler as $key => $siparis) { ?>
                                                    <?php 
                                                        $sql = "SELECT isim,biten_urun_adedi,teslim_edilen_urun_adedi FROM `planlama` WHERE siparis_id = :siparis_id";   
                                                        $sth = $conn->prepare($sql);
                                                        $sth->bindParam('siparis_id', $siparis['id']);
                                                        $sth->execute();
                                                        $siparis_planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC); 
                                                    ?>
                                                    <tr>
                                                        <th><?php echo $key + 1; ?></th>
                                                        <td class="table-primary"><?php echo $siparis['siparis_no']; ?></td>
                                                        <td><?php echo $siparis['isin_adi']; ?></td>
                                                        <td><?php echo $siparis['marka']; ?></td>
                                                        <td><?php echo $siparis['ad'].' '.$siparis['soyad']; ?></td>
                                                        <td>
                                                            <?php foreach ($siparis_planlamalar as $key => $siparis_planlama) { ?>
                                                                <b >
                                                                    <?php echo $siparis_planlama['isim']; ?> :
                                                                    (
                                                                        <span class="badge bg-secondary">
                                                                            <?php echo number_format($siparis_planlama['biten_urun_adedi'],0,'',','); ?> / 
                                                                            <?php echo number_format($siparis['adet'],0,'',','); ?>
                                                                        </span>
                                                                    )
                                                                </b>
                                                                <?php 
                                                                    $bitme_orani = round(($siparis_planlama['biten_urun_adedi']/$siparis['adet'])*100,2);
                                                                ?>
                                                                <div class="progress mt-2" role="progressbar"  aria-valuenow="<?php echo $bitme_orani;?>" 
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    <div class="progress-bar" style="width: <?php echo $bitme_orani;?>%">
                                                                        <?php echo $bitme_orani;?>%
                                                                    </div>
                                                                </div>
                                                            <?php }?>
                                                        </td>
                                                        <td>
                                                            <?php foreach ($siparis_planlamalar as $key => $siparis_planlama) { ?>
                                                                <b>
                                                                    <?php echo $siparis_planlama['isim']; ?> :
                                                                    (
                                                                        <span class="badge bg-secondary">
                                                                            <?php echo number_format($siparis_planlama['teslim_edilen_urun_adedi'],0,'',','); ?> / 
                                                                            <?php echo number_format($siparis['adet'],0,'',','); ?>
                                                                        </span>
                                                                    )
                                                                </b>
                                                                <?php 
                                                                    $teslim_orani_orani = round(($siparis_planlama['teslim_edilen_urun_adedi']/$siparis['adet'])*100,2);
                                                                ?>
                                                                <div class="progress mt-2" role="progressbar" aria-label="Basic example" aria-valuenow="<?php echo $teslim_orani_orani; ?>" aria-valuemin="0" aria-valuemax="100">
                                                                    <div class="progress-bar bg-success" style="width: <?php echo $teslim_orani_orani; ?>%">
                                                                        <?php echo $teslim_orani_orani; ?>
                                                                    </div>
                                                                </div>
                                                            <?php }?>
                                                        </td>
                                                        <td class="text-end">
                                                            <button class="btn btn-sm btn-success teslim-edilecek" data-siparis-id="<?php echo $siparis['id']; ?>">
                                                                <i class="fa-solid fa-box-open"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php }?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade show" id="nav-gonderilmis-siparisler" role="tabpanel" 
                                    aria-labelledby="nav-tab-gonderilmis-siparisler" tabindex="0">
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-hover" >
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Sipariş No</th>
                                                    <th>İşin Adı</th>
                                                    <th>Müşteri</th>
                                                    <th>Müşteri Temsilcisi</th>
                                                    <th>Üretilen Adet</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                                <?php foreach ($teslimi_bitmis_siparisler as $key => $siparis) { ?>
                                                    <tr>
                                                        <th><?php echo $key + 1; ?></th>
                                                        <td class="table-primary"><?php echo $siparis['siparis_no']; ?></td>
                                                        <td><?php echo $siparis['isin_adi']; ?></td>
                                                        <td><?php echo $siparis['marka']; ?></td>
                                                        <td><?php echo $siparis['ad'].' '.$siparis['soyad']; ?></td>
                                                        <td></td>
                                                        <td class="text-end">
                                                            <button data-siparis-id="<?php echo $siparis['id']; ?>"  class="btn btn-sm btn-success gonderimi-bitmis-siparis-button">
                                                                <i class="fa-solid fa-flag-checkered"></i>
                                                            </button>
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
                </div>
            </div>
        <!-- Detay ve Teslim Etme Modal -->
        <div class="modal fade" id="detay-teslim-modal" tabindex="-1"  aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" >Detay Ve Teslim Edilecekler</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <nav>
                            <div class="nav nav-tabs" id="nav-detay-teslim-modal" role="tablist">
                                <button class="nav-link active position-relative fw-bold" id="nav-tab-detay-teslim" data-bs-toggle="tab" 
                                    data-bs-target="#nav-detay-teslim" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                    Teslimat
                                </button>
                                <button class="nav-link  position-relative fw-bold" id="nav-tab-uretilen-teslim-edilen-log" data-bs-toggle="tab" 
                                    data-bs-target="#nav-uretilen-teslim-edilen-log" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                    Teslim Edilen ve Üretilen Ürün Log
                                </button>
                            </div>
                        </nav>
                        <div class="tab-content mt-3" id="nav-detay-teslim-modal">
                            <div class="tab-pane fade show active" id="nav-detay-teslim" role="tabpanel" 
                                aria-labelledby="nav-tab-detay-teslim" tabindex="0">
                                <div class="row mb-3" id="teslim-detaylar">
                                    
                                </div>
                                <form action="/index.php?url=depo_db_islem" class="row g-3 needs-validation" method="POST">
                                    <div id="teslim-form-inputlar" class="mb-3">

                                    </div>    
                                    <div class="row">
                                        <div class="col-md-6 d-grid gap-2">
                                            <button type="submit" class="btn btn-success btn-lg" name="teslim_et" >TESLİM ET</button>
                                        </div>
                                    </div>        
                                </form>
                            </div>
                            <div class="tab-pane fade show" id="nav-uretilen-teslim-edilen-log" role="tabpanel" 
                                    aria-labelledby="nav-tab-uretilen-teslim-edilen-log" tabindex="0">
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-hover">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>Sıra</th>
                                                <th>Adet</th>
                                                <th>İsim</th>
                                                <th>İşin Adı</th>
                                                <th>İşlem</th>
                                                <th>Tarih</th>
                                            </tr>
                                        </thead>
                                        <tbody id="uretilen-teslim-edilen-log-tbody">   
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">KAPAT</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teslimi Bitmiş Siparişler Log --> 
        <div class="modal fade" id="teslimi-bitmis-siparis-log-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" >Teslimi Bitmiş Sipariş Log</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="myTable" class="table table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Sıra</th>
                                        <th>Adet</th>
                                        <th>İsim</th>
                                        <th>İşin Adı</th>
                                        <th>İşlem</th>
                                        <th>Tarih</th>
                                    </tr>
                                </thead>
                                <tbody id="teslimi-bitmis-siparis-log-tbody">   
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">KAPAT</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(function(){
                //gönderimi bitmiş sipariş logları
                $(document).on('click', '.gonderimi-bitmis-siparis-button', function(){
                    const siparis_id = $(this).data('siparis-id');
                    $.ajax({
                        url         : "/index.php?url=depo_db_islem",
                        dataType    : "JSON",
                        type        : 'POST',
                        data        : {siparis_id, 'islem':'gonderimi_bitmis_siparis_log'},
                        success     : function(data){
                            let logHTML = '', logDurum;
                            data.loglar.forEach((log, index) => {
                                logDurum = log.durum =='teslim' ?   '<span class="badge text-bg-success">TESLİM</span>' : 
                                                                    '<span class="badge text-bg-primary">ÜRETİM</span>';
                                logHTML += `
                                    <tr >
                                        <th>${index+1}</th>
                                        <td>${log.adet}</td>
                                        <td>${log.isim}</td>
                                        <td>${log.isin_adi}</td>
                                        <td>${logDurum}</td>
                                        <td>${log.tarih}</td>
                                    </tr>
                                `;
                            });

                            $("#teslimi-bitmis-siparis-log-tbody").html(logHTML);
                            $('#teslimi-bitmis-siparis-log-modal').modal('show');
                        }
                    });
                });

                $(document).on('click','.teslim-edilecek', function(){
                    const siparis_id = $(this).data('siparis-id');
                    
                    $.ajax({
                        url         : "/index.php?url=depo_db_islem",
                        dataType    : "JSON",
                        type        : 'POST',
                        data        : {siparis_id, 'islem':'siparisler_ve_log'},
                        success     : function(data){
                            console.log(data.loglar)
                            $("#teslim-detaylar").html('');
                            $("#teslim-form-inputlar").html(`
                                <input type="hidden" name="siparis_id" value="${siparis_id}">
                            `);
                            data.planlamalar.forEach((planlama) => {
                                $('#teslim-detaylar').append(`
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            <li class="list-group-item active fw-bold" aria-current="true"><b>İsim:</b> ${planlama.isim}</li>
                                            <li class="list-group-item"><b>Biten Adet:</b> ${planlama.biten_urun_adedi} ${planlama.birim_ad}</li>
                                            <li class="list-group-item"><b>Teslim Edilen:</b> ${planlama.teslim_edilen_urun_adedi} ${planlama.birim_ad}</li>
                                        </ul>
                                    </div>
                                `);

                                $("#teslim-form-inputlar").append(`
                                    <input type="hidden" name="planlanma_idler[]" class="form-control" value="${planlama.id}">
                                    <div class="form-floating col-md-6 mb-3">
                                        <input type="number" name="teslim_edilecekler[]" class="form-control" id="planlama-${planlama.id}" required>
                                        <label for="planlama-${planlama.id}" class="form-label">${planlama.isim} Teslim Edilen</label>
                                    </div>
                                `);
                            });

                            let logHTML = '', logDurum;
                            data.loglar.forEach((log, index) => {
                                logDurum = log.durum =='teslim' ?   '<span class="badge text-bg-success">TESLİM</span>' : 
                                                                    '<span class="badge text-bg-primary">ÜRETİM</span>';
                                logHTML += `
                                    <tr >
                                        <th>${index+1}</th>
                                        <td>${log.adet}</td>
                                        <td>${log.isim}</td>
                                        <td>${log.isin_adi}</td>
                                        <td>${logDurum}</td>
                                        <td>${log.tarih}</td>
                                    </tr>
                                `;
                            });

                            $("#uretilen-teslim-edilen-log-tbody").html(logHTML);
                            $('#detay-teslim-modal').modal('show');
                        }
                    });
                });
            });
        </script>