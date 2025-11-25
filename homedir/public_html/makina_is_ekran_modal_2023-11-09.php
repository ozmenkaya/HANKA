<?php 
    $uretim_islem_tarih_baslatma_tarih  = isset($planlama_tarih['baslatma_tarih']) ? $planlama_tarih['baslatma_tarih'] : date('Y-m-d H:i:s');
    $uretim_islem_tarih_id              = isset($planlama_tarih['id']) ? $planlama_tarih['id'] : 0;
?>
<!-- Sipariş Detay Modal -->
<div class="modal fade" id="siparis-detay-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Sipariş Detay</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-1">
                    <div class="col-md-6">
                        <ol class="list-group list-group-numbered mb-2">
                            <li class="list-group-item active fw-bold" aria-current="true">Sipariş Detayları</li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">İşin Adı</div>
                                    <?php echo $is['isin_adi']; ?>
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Sipariş Adeti</div>
                                    <?php echo $is['uretilecek_adet']; ?>
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Termin Tarihi</div>
                                    <?php echo date('d-m-Y', strtotime($is['termin'])); ?>       
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Paketleme</div>
                                    <?php echo $is['paketleme']; ?>       
                                </div>
                            </li>
                            
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Açıklama</div>
                                    <?php echo $is['aciklama']; ?>       
                                </div>
                            </li>
                        </ol>

                        <?php 
                            $stok_alt_kalemler_hepsi = json_decode($is['stok_alt_kalemler'], true);
                            //print_r($stok_alt_kalemler_hepsi);
                            $stok_alt_kalemler_sonuc_idler = [];
                            foreach ($stok_alt_kalemler_hepsi as $stok_alt_kalemler) {
                                $stok_alt_kalemler = array_filter($stok_alt_kalemler);
                                foreach ($stok_alt_kalemler as $stok_alt_kalem) {
                                    $stok_alt_kalemler_sonuc_idler[] = $stok_alt_kalem;
                                }
                            }
                            $stok_alt_kalemler_sonuc_idler_birlestir = implode(',',$stok_alt_kalemler_sonuc_idler);
                            //echo $stok_alt_kalemler_sonuc_idler_birlestir.'=>';
                            $sql = "SELECT stok_alt_kalemler.veri, birimler.ad,stok_kalemleri.stok_kalem FROM `stok_alt_kalemler` 
                            JOIN stok_kalemleri ON stok_kalemleri.id = `stok_alt_kalemler`.`stok_id`
                            LEFT JOIN birimler ON birimler.id = stok_alt_kalemler.birim_id  
                            WHERE stok_alt_kalemler.firma_id = :firma_id";

                            if(!empty($stok_alt_kalemler_sonuc_idler_birlestir)){
                                $sql .= " AND stok_alt_kalemler.id IN({$stok_alt_kalemler_sonuc_idler_birlestir }) ";
                            }
                            
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $stok_veriler = $sth->fetchAll(PDO::FETCH_ASSOC);
                            //print_r($stok_veriler);
                        ?>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item active fw-bold" aria-current="true">MAZLEMELER</li>
                            <?php foreach ($stok_veriler as $stok_veri) { ?>
                                <?php 
                                    $veri   = json_decode($stok_veri['veri'], true); 
                                    $keys   = array_keys($veri);
                                    $values = array_values($veri);
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold"><?php echo $stok_veri['stok_kalem']; ?></div>
                                        <?php 
                                            echo implode("/", $keys).' => ';
                                            echo implode("/", $values);
                                        ?>
                                    </div>
                                </li>
                            <?php }?>
                        </ol>

                    </div>
                    <div class="col-md-6">
                        <?php if($is['tip_id'] == TEK_URUN){?>
                            <?php 
                                $veriler = json_decode($is['veriler'], true);  
                                $veriler = isset($veriler['form']) ? $veriler['form'] : [];
                            ?>
                            <ul class="list-group">
                                <?php foreach ($veriler as $key => $value) { ?>
                                    <?php if(!empty($value)){ ?>
                                        <li class="list-group-item list-group-item-warning">
                                            <b><?php echo $key; ?>:</b> <?php echo $value; ?>
                                        </li>
                                    <?php } ?>
                                <?php }?>
                            </ul>
                        <?php }else if(in_array($is['tip_id'], [GRUP_URUN_TEK_FIYAT, GRUP_URUN_AYRI_FIYAT])){?>
                            <?php 
                                $veriler = json_decode($is['veriler'],true)[$is['alt_urun_id']-1];  
                                //echo "<pre>";print_r($veriler);
                                $veriler = isset($veriler['form']) ? $veriler['form'] : [];
                            ?>
                            <ul class="list-group">
                                <?php foreach ($veriler as $key => $value) { ?>
                                    <?php if(!empty($value)){ ?>
                                        <li class="list-group-item list-group-item-warning">
                                            <b><?php echo $key; ?>:</b> <?php echo $value; ?>
                                        </li>
                                    <?php } ?>
                                <?php }?>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa-solid fa-image"></i> Sipariş Resimleri</h5>
                    </div>
                    <div class="card-body">
                        <?php if($is['tip_id'] == TEK_URUN){?>
                            <?php foreach ($siparis_resimler as $siparis_dosya) { ?>
                                <?php 
                                    $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                ?>
                                <?php if($uzanti == 'pdf'){ ?>
                                    <a  class="text-decoration-none siparis-pdf-dosya" href="javascript:;" 
                                        data-siparis-url="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>">
                                        <img src="dosyalar/pdf.png" 
                                            class="rounded img-thumbnai object-fit-fill" 
                                            alt="" 
                                            style="height:50px; min-height:50px; width:50px;"
                                            
                                        > 
                                    </a>
                                <?php }else{?>
                                    <a class="text-decoration-none example-image-link" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                data-lightbox="example-set" data-title="">
                                        <img src="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                            class="rounded img-thumbnai border border-secondary-subtle object-fit-fill mb-1 mt-1" 
                                        style="height:50px; min-height:50px; width:50px;">
                                    </a>
                                <?php } ?>
                                <?php if(empty($siparis_resimler)){?>
                                    <h6 class="text-danger fw-bold">Dosya Yok</h6>
                                <?php } ?>
                            <?php } ?>
                        <?php }else if(in_array($is['tip_id'], [GRUP_URUN_TEK_FIYAT, GRUP_URUN_AYRI_FIYAT])){?>
                            <?php $resim_varmi = false; ?>
                            <?php foreach ($siparis_resimler as $siparis_dosya) { ?>
                                <?php if($index == $siparis_dosya['alt_urun_index']){ ?>
                                    <?php $resim_varmi = true; ?>
                                    <?php 
                                        $uzanti = pathinfo("dosyalar/siparisler/{$siparis_dosya['ad']}", PATHINFO_EXTENSION);
                                    ?>
                                    <?php if($uzanti == 'pdf'){ ?>
                                        <a class="text-decoration-none siparis-pdf-dosya" href="javascript:;" 
                                            data-siparis-url="dosyalar/siparisler/<?php echo $siparis_dosya['ad'];?>">
                                            <img src="dosyalar/pdf.png" 
                                                class="rounded img-thumbnai object-fit-fill" 
                                                alt="" 
                                                style="height:50px; min-height:50px; width:50px;"
                                                
                                            > 
                                        </a>
                                    <?php }else{?>
                                        <a class="text-decoration-none example-image-link-<?php echo $index; ?>" href="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                    data-lightbox="example-set-<?php echo $index; ?>" data-title="">
                                            <img src="dosyalar/siparisler/<?php echo $siparis_dosya['ad']; ?>" 
                                                class="rounded img-fluid object-fit-fill border border-secondary-subtle mb-1 mt-1" 
                                                style="height:50px; min-height:50px; width:50px;">
                                        </a>
                                    <?php }?>
                                <?php }?>
                            <?php } ?> 
                            <?php if(!$resim_varmi){?>
                                <h6 class="text-danger fw-bold">Dosya Yok</h6>
                            <?php }?>
                        <?php }?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>


<!-- 1- Makina Ayar Modal -->
<div class="modal fade" id="makina-ayar-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-warning">
            <div class="modal-header">
                <h5>
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    Makina Ayar İşlemi
                </h5>
            </div>
            <div class="modal-body" style="display:flex;justify-content:center;align-items:center;flex-direction:column"> 
                <span style="font-size:150px">Ayar Süresi</span>
                <h5 style="font-size:200px">
                    <span id="makina-ayar-gecen-sure" class="fw-bold">00:00:00</span>
                </h5>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="uretim_makina_ayar_baslatma_tarihi">
                <button type="button" class="btn btn-secondary btn-lg" id="makina-ayar-bitir" data-bs-dismiss="modal">
                    <i class="fa-solid fa-screwdriver-wrench"></i> MAKİNA AYAR BİTİR
                </button>
            </div>
        </div>
    </div>
</div>


<!-- 2- Başlangıç Form Modal -->
<div class="modal fade" id="form-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5 fw-bold">
                    <i class="fa-solid fa-align-justify"></i> Kontrol Listesi
                </h1>
            </div>
            <div class="modal-header" style="display: block !important;">
                <form action="/index.php?url=makina_is_ekran_db_islem" method="POST" class="needs-validation" id="isi-baslat-form">
                    <div class="mb-2" id="formlar"></div>
                    <div class="card mb-2 border-secondary" >
                        <div class="card-header">
                            <h5 class="fw-bold">
                                <i class="fa-sharp fa-solid fa-layer-group"></i>
                                Tüketilen Stoklar
                            </h5>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="siparis_id"      value="<?php echo $is['siparis_id']; ?>">
                            <input type="hidden" name="tekil_kod"       value="<?php echo $is['tekil_kod']; ?>">
                            <input type="hidden" name="planlama_id"     value="<?php echo $planlama_id; ?>">
                            <input type="hidden" name="makina_id"       value="<?php echo $makina_id; ?>">
                            <input type="hidden" name="departman_id"    value="<?php echo $departman_id; ?>">
                            <input type="hidden" name="mevcut_asama"    value="<?php echo $is['mevcut_asama'];?>">
                            <input type="hidden" name="grup_kodu"       value="<?php echo $is['grup_kodu'];?>">
                            
                            <?php foreach ($planlanmis_stok_veriler as $index => $planlanmis_stok_veri) { ?>
                                <input type="hidden" name="birim_id[]" value="<?php echo $planlanmis_stok_veri['birim_id'];?>">
                                <input type="hidden" name="stok_id[]" value="<?php echo $planlanmis_stok_veri['stok_id'];?>">
                                <input type="hidden" name="stok_alt_kalem_id[]" value="<?php echo $planlanmis_stok_veri['stok_alt_kalem_id'];?>">
                                <input type="hidden" name="stok_alt_depo_id[]"  value="<?php echo $planlanmis_stok_veri['stok_alt_depo_id'];?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-12 fw-bold d-flex justify-content-between">
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $index +1; ?>-
                                            <?php echo "STOK KODU : ".$planlanmis_stok_veri['stok_kodu'];  ?>
                                        </span> 
                                        <?php 
                                            $stok_alt_kalem_veri            = json_decode($planlanmis_stok_veri['veri'], true);
                                            $stok_alt_kalem_veri_degerler   = array_values($stok_alt_kalem_veri);
                                        ?>
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $planlanmis_stok_veri['stok_kalem']; ?> - 
                                            <?php 
                                                echo implode('/', $stok_alt_kalem_veri_degerler); 
                                            ?>
                                        </span>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" min="0" step=".001" id="tuketim_miktari-<?php echo $index; ?>" 
                                            name="tuketim_miktari[]" required value="<?php echo $makina['makina_ayar_suresi_varmi'] == 'yok' ? 0:''; ?>">
                                        <label for="tuketim_miktari-<?php echo $index; ?>">Tüketilen Stok Miktarı (<?php echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" min="0" step=".001" id="fire_miktari-<?php echo $index; ?>" 
                                            name="fire_miktari[]" required  value="<?php echo $makina['makina_ayar_suresi_varmi'] == 'yok' ? 0:''; ?>">
                                        <label for="fire_miktari-<?php echo $index; ?>">Fire Stok Miktarı (<?php  echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                </div>
                                <hr class="border-3">
                            <?php }?>

                            <?php if(empty($planlanmis_stok_veriler)){ ?>
                                <div class="alert alert-danger fw-bold">
                                    1- Tüketilen Stok Bulunmuyor!
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success fw-bold" name="isi_baslat" id="isi-baslat-button">
                            <i class="fa-regular fa-paper-plane"></i> GÖNDER
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<!-- 3- Mola Modal -->
<div class="modal fade" id="mola-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>
                    <i class="fa-solid fa-mug-saucer"></i>
                    Mola Başladı
                </h5>
                <h5 class="fw-bold">
                    <i class="fa-regular fa-clock"></i> 
                    <span id="mola-gecen-sure" class="text-decoration-underline">00:00:00</span>
                </h5>
            </div>
            <div class="modal-body"> 
                <table id="myTable" class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Başlangıç Tarihi</th>
                            <th>Bitiş Tarihi</th>
                            <th class="text-center">Geçen Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sql = "SELECT  baslatma_tarihi, bitis_tarihi,TIMEDIFF(bitis_tarihi,baslatma_tarihi) AS gecen_sure     
                            FROM uretim_mola_log 
                            WHERE departman_id = :departman_id AND  tekil_kod = :tekil_kod ";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('departman_id', $departman_id);
                            $sth->bindParam('tekil_kod', $is['tekil_kod']);
                            $sth->execute();
                            $uretim_mola_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($uretim_mola_loglar  as $index => $uretim_mola_log) { ?>
                            <tr>
                                <th><?php echo $index+1;?></th>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($uretim_mola_log['baslatma_tarihi'])); ?></td>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($uretim_mola_log['bitis_tarihi'])); ?></td>
                                <td class="text-center"><?php echo $uretim_mola_log['gecen_sure']; ?></td>
                            </tr>
                        <?php }?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <form action="/index.php?url=makina_is_ekran_db_islem" method="POST" id="mola-bitir-form">
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id; ?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id; ?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id; ?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama']; ?>">
                    <input type="hidden" name="tekil_kod" value="<?php echo $is['tekil_kod']; ?>">
                    <input type="hidden" id="mola_baslatma_tarih" name="mola_baslatma_tarih">
                    <button type="submit" class="btn btn-secondary btn-lg" name="mola-bitir" id="mola-bitir-button">
                        <i class="fa-solid fa-mug-saucer"></i> MOLAYI BİTİR
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 4- Yemek Mola Modal -->
<div class="modal fade" id="yemek-mola-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>
                    <i class="fa-solid fa-utensils"></i>
                    Yemek Molası Başladı
                </h5>
                <h5 class="fw-bold">
                    <i class="fa-regular fa-clock"></i> 
                    <span id="yemek-mola-gecen-sure" class="text-decoration-underline">00:00:00</span>
                </h5>
            </div>
            <div class="modal-body">
                <table id="myTable" class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Başlangıç Tarihi</th>
                            <th>Bitiş Tarihi</th>
                            <th class="text-center">Geçen Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sql = "SELECT  baslatma_tarihi,bitis_tarihi,
                            TIMEDIFF(bitis_tarihi,baslatma_tarihi) AS gecen_sure 
                            FROM uretim_yemek_mola_log 
                            WHERE departman_id = :departman_id AND mevcut_asama = :mevcut_asama 
                                AND  tekil_kod = :tekil_kod";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('departman_id', $departman_id);
                            $sth->bindParam('mevcut_asama', $is['mevcut_asama']);
                            $sth->bindParam('tekil_kod', $is['tekil_kod']);
                            $sth->execute();
                            $uretim_yemek_mola_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($uretim_yemek_mola_loglar as $index => $uretim_yemek_mola_log) { ?>
                            <tr>
                                <th><?php echo $index + 1; ?></th>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($uretim_yemek_mola_log['baslatma_tarihi'])); ?></td>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($uretim_yemek_mola_log['bitis_tarihi'])); ?></td>
                                <td class="text-center"><?php echo $uretim_yemek_mola_log['gecen_sure']; ?></td>
                            </tr>
                        <?php }?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <form action="/index.php?url=makina_is_ekran_db_islem" method="POST" id="yemek-mola-bitir-form">
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id; ?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id; ?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id; ?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama']; ?>">
                    <input type="hidden" name="tekil_kod" value="<?php echo $is['tekil_kod']; ?>">
                    <input type="hidden" id="yemek_mola_baslatma_tarih" name="yemek_mola_baslatma_tarih">
                    <button type="submit" class="btn btn-secondary btn-lg" name="yemek-mola-bitir" id="yemek-mola-bitir-button">
                        <i class="fa-solid fa-utensils"></i> YEMEK MOLASINI BİTİR
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 5- Toplantı  Modal -->
<div class="modal fade" id="toplanti-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>
                    <i class="fa-solid fa-handshake"></i>
                    Toplantı Başladı
                </h5>
                <h5 class="fw-bold">
                    <i class="fa-regular fa-clock"></i> 
                    <span id="toplanti-gecen-sure" class="text-decoration-underline">00:00:00</span>
                </h5>
            </div>
            <div class="modal-body">
                <table id="myTable" class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Başlangıç Tarihi</th>
                            <th>Bitiş Tarihi</th>
                            <th class="text-center">Geçen Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sql = "SELECT  baslatma_tarihi, bitis_tarihi,
                            TIMEDIFF(bitis_tarihi,baslatma_tarihi) AS gecen_sure  
                            FROM uretim_toplanti_log 
                            WHERE  departman_id = :departman_id AND mevcut_asama = :mevcut_asama AND tekil_kod = :tekil_kod ";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('departman_id', $departman_id);
                            $sth->bindParam('mevcut_asama', $is['mevcut_asama']);
                            $sth->bindParam('tekil_kod', $is['tekil_kod']);
                            $sth->execute();
                            $uretim_toplanti_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($uretim_toplanti_loglar as $index => $uretim_toplanti_log) { ?>
                            <tr>
                                <th><?php echo $index + 1; ?></th>
                                <td><?php echo date('d-m-Y H:i:s',strtotime($uretim_toplanti_log['baslatma_tarihi'])); ?></td>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($uretim_toplanti_log['bitis_tarihi'])); ?></td>
                                <td class="text-center"><?php echo $uretim_toplanti_log['gecen_sure']; ?></td>
                            </tr>
                        <?php }?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <form action="/index.php?url=makina_is_ekran_db_islem" method="POST" id="toplanti-bitir-form">
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id; ?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id; ?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id; ?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama']; ?>">
                    <input type="hidden" name="tekil_kod" value="<?php echo $is['tekil_kod']; ?>">
                    <input type="hidden" id="toplanti_baslatma_tarih" name="toplanti_baslatma_tarih">

                    <button type="submit" class="btn btn-secondary btn-lg" name="toplanti-bitir" id="toplanti-bitir-button">
                        <i class="fa-solid fa-handshake"></i> TOPLANTIYI BİTİR
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 6- Paydos Modal -->
<div class="modal fade" id="paydos-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-right-from-bracket"></i> Paydos İşlemi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group fw-bold border-secondary">
                    <li class="list-group-item active" aria-current="true">DİKKAT EDİLMESİ GEREKENLER</li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i> Tüketim Miktarlanı Fireler ile birlikte girin!
                    </li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i> Üretilen Miktarlanı Fireler ile birlikte girin!
                    </li>
                </ul>
                <form action="/index.php?url=makina_is_ekran_db_islem" class="needs-validation mt-2" method="POST" id="paydos-bitir-form">
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>">
                    <input type="hidden" name="grup_kodu" value="<?php echo $is['grup_kodu'];?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                    <input type="hidden" name="asama_sayisi" value="<?php echo $is['asama_sayisi'];?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_id" value="<?php echo $uretim_islem_tarih_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_baslatma_tarih" value="<?php echo $uretim_islem_tarih_baslatma_tarih;?>">
                    
                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                Üretilen Adet ve Fireler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretilen_adet" class="form-control" id="uretilen_adet"  required>
                                    <label for="uretilen_adet" class="form-label">Üretilen Adet</label>
                                </div>
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretirken_verilen_fire_adet" class="form-control" id="uretirken_verilen_fire_adet"  required>
                                    <label for="uretirken_verilen_fire_adet" class="form-label">Üretirken Verilen Fire </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-sharp fa-solid fa-layer-group"></i>
                                Tüketilen Stoklar
                            </h5>
                        </div>
                        <div class="card-body">        
                            <?php foreach ($planlanmis_stok_veriler as $index => $planlanmis_stok_veri) { ?>
                                <input type="hidden" name="birim_id[]" value="<?php echo $planlanmis_stok_veri['birim_id'];?>">
                                <input type="hidden" name="stok_id[]" value="<?php echo $planlanmis_stok_veri['stok_id'];?>">
                                <input type="hidden" name="stok_alt_kalem_id[]" value="<?php echo $planlanmis_stok_veri['stok_alt_kalem_id'];?>">
                                <input type="hidden" name="stok_alt_depo_id[]"  value="<?php echo $planlanmis_stok_veri['stok_alt_depo_id'];?>">
                                <div class="row g-3">
                                    <div class="col-md-12 fw-bold d-flex justify-content-between">
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $index +1; ?>-
                                            <?php echo "STOK KODU : ".$planlanmis_stok_veri['stok_kodu'];  ?>
                                        </span> 
                                        <?php 
                                            $stok_alt_kalem_veri            = json_decode($planlanmis_stok_veri['veri'], true);
                                            $stok_alt_kalem_veri_degerler   = array_values($stok_alt_kalem_veri);
                                        ?>
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $planlanmis_stok_veri['stok_kalem']; ?> - 
                                            <?php 
                                                echo implode('/', $stok_alt_kalem_veri_degerler); 
                                            ?>
                                        </span>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="tuketim_miktari-<?php echo $index; ?>" name="tuketim_miktari[]" required>
                                        <label for="tuketim_miktari-<?php echo $index; ?>">Tüketilen Stok Miktarı (<?php echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="fire_miktari-<?php echo $index; ?>" name="fire_miktari[]" required>
                                        <label for="fire_miktari-<?php echo $index; ?>">Fire Stok Miktarı (<?php  echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                </div>
                                <hr class="border-3">
                            <?php }?>

                            <?php if(empty($planlanmis_stok_veriler)){?>
                                <div class="alert alert-danger fw-bold">
                                    1- Tüketilen Stok Bulunmuyor!
                                </div>
                            <?php }?>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success" name="paydos" id="paydos-bitir-button">
                            <i class="fa-regular fa-paper-plane"></i> GÖNDER
                        </button>
                    </div>                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>


<!-- 7- Devret Modal -->
<div class="modal fade" id="devret-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> 
                    Başka Makineye Devret
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group border-secondary">
                    <li class="list-group-item active fw-bold" aria-current="true">DİKKAT EDİLMESİ GEREKENLER</li>
                    <li class="list-group-item list-group-item-danger fw-bold">
                        <i class="fa-solid fa-triangle-exclamation"></i> Üretilen Miktarları Fireler ile birlikte girin!
                    </li>
                    <li class="list-group-item list-group-item-danger fw-bold">
                        <i class="fa-solid fa-triangle-exclamation"></i> Tüketim Miktarları Fireler ile birlikte girin!
                    </li>
                </ul>
                <form action="/index.php?url=makina_is_ekran_db_islem" class="needs-validation mt-2" method="POST" id="devret-bitir-form">
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>">
                    <input type="hidden" name="grup_kodu" value="<?php echo $is['grup_kodu'];?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                    <input type="hidden" name="asama_sayisi" value="<?php echo $is['asama_sayisi'];?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_id" value="<?php echo $uretim_islem_tarih_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_baslatma_tarih" value="<?php echo $uretim_islem_tarih_baslatma_tarih;?>">
                    
                    <div class="row g-3">
                        <div class="form-floating col-md-6 mb-2">
                            <select class="form-select" name="devredilen_makina_id" id="devredilen_makina_id" required>
                                <option selected disabled value="">Seçiniz</option>
                                <?php foreach ($calisan_makina_haric_departmandaki_makinalar as $calisan_makina_haric_makina) { ?>
                                    <option value="<?php echo $calisan_makina_haric_makina['id'];?>">
                                        <?php echo $calisan_makina_haric_makina['makina_adi'].' '.$calisan_makina_haric_makina['makina_modeli'];?>
                                    </option>
                                <?php }?>
                            </select>
                            <label for="devredilen_makina_id" class="form-label">Makinalar</label>
                        </div>

                        <div class="form-floating col-md-6 mb-2">
                            <textarea name="devretme_sebebi" id="devretme_sebebi" class="form-control" style="height:100px"></textarea>
                            <label for="devretme_sebebi" class="form-label">Devretme Sebebi</label>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                Üretilen Adet ve Fireler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretilen_adet" class="form-control" id="devret_uretilen_adet"  required>
                                    <label for="devret_uretilen_adet" class="form-label">Üretilen Adet</label>
                                </div>
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretirken_verilen_fire_adet" class="form-control" id="devret_uretirken_verilen_fire_adet"  required>
                                    <label for="devret_uretirken_verilen_fire_adet" class="form-label">Üretirken Verilen Fire </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-sharp fa-solid fa-layer-group"></i>
                                Tüketilen Stoklar
                            </h5>
                        </div>
                        <div class="card-body">   
                            <?php foreach ($planlanmis_stok_veriler as $index => $planlanmis_stok_veri) { ?>
                                <input type="hidden" name="birim_id[]" value="<?php echo $planlanmis_stok_veri['birim_id'];?>">
                                <input type="hidden" name="stok_id[]" value="<?php echo $planlanmis_stok_veri['stok_id'];?>">
                                <input type="hidden" name="stok_alt_kalem_id[]" value="<?php echo $planlanmis_stok_veri['stok_alt_kalem_id'];?>">
                                <input type="hidden" name="stok_alt_depo_id[]"  value="<?php echo $planlanmis_stok_veri['stok_alt_depo_id'];?>">
                                <div class="row g-3">
                                    <div class="col-md-12 fw-bold d-flex justify-content-between">
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $index +1; ?>-
                                            <?php echo "STOK KODU : ".$planlanmis_stok_veri['stok_kodu'];  ?>
                                        </span> 
                                        <?php 
                                            $stok_alt_kalem_veri            = json_decode($planlanmis_stok_veri['veri'], true);
                                            $stok_alt_kalem_veri_degerler   = array_values($stok_alt_kalem_veri);
                                        ?>
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $planlanmis_stok_veri['stok_kalem']; ?> - 
                                            <?php 
                                                echo implode('/', $stok_alt_kalem_veri_degerler); 
                                            ?>
                                        </span>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="devret_tuketim_miktari-<?php echo $index; ?>" name="tuketim_miktari[]" required>
                                        <label for="devret_tuketim_miktari-<?php echo $index; ?>">Tüketilen Stok Miktarı (<?php echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="devret_fire_miktari-<?php echo $index; ?>" name="fire_miktari[]" required>
                                        <label for="devret_fire_miktari-<?php echo $index; ?>">Fire Stok Miktarı (<?php  echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                </div>
                                <hr class="border-3">
                            <?php }?>

                            <?php if(empty($planlanmis_stok_veriler)){?>
                                <div class="alert alert-danger fw-bold">
                                    1- Tüketilen Stok Bulunmuyor!
                                </div>
                            <?php }?>

                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success" name="devret" id="devret-bitir-button">
                            <i class="fa-regular fa-paper-plane"></i>
                            GÖNDER
                        </button>
                    </div>                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>  


<!-- 8- Kontrol Modal -->
<div class="modal fade" id="kontrol-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-list-check"></i>
                    Kontrol Anketleri 
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"> 
                <div id="baslangic_formlar"></div>   
                <hr>
                <div id="bitisteki_formlar"></div>  
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"  data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 9- Değiştir Modal -->
<div class="modal fade" id="degistir-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-retweet"></i> İş Değiştir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group mb-2 fw-bold border-secondary">
                    <li class="list-group-item active" aria-current="true">DİKKAT EDİLMESİ GEREKENLER</li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> Üretilen Miktarlarını fireler ile birlikte girin!
                    </li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> Tüketim Miktarlarını fireler ile birlikte girin!
                    </li>
                </ul>
                <form action="/index.php?url=makina_is_ekran_db_islem" class="needs-validation mt-2" method="POST" id="is-degistir-form">    
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>">
                    <input type="hidden" name="grup_kodu" value="<?php echo $is['grup_kodu'];?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                    <input type="hidden" name="asama_sayisi" value="<?php echo $is['asama_sayisi'];?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_id" value="<?php echo $uretim_islem_tarih_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_baslatma_tarih" value="<?php echo $uretim_islem_tarih_baslatma_tarih;?>">
                    
                    <div class="form-floating col-md-12 mb-2">
                        <textarea name="degistirme_sebebi" id="degistirme_sebebi" class="form-control" style="height: 100px;"></textarea>
                        <label for="degistirme_sebebi" class="form-label">İşi Değiştirme Sebebi</label>
                    </div>

                    <div class="form-floating col-md-12 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="sorun_bildirisin_mi" value="1" id="sorun_bildirisin_mi" >
                            <label class="form-check-label text-decoration-underline fw-bold" for="sorun_bildirisin_mi">Durum Bildirilisin Mi?</label>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                Üretilen Adet ve Fireler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">            
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretilen_adet" class="form-control" id="devret_uretilen_adet"  required>
                                    <label for="devret_uretilen_adet" class="form-label">Üretilen Adet</label>
                                </div>
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretirken_verilen_fire_adet" class="form-control" id="devret_uretirken_verilen_fire_adet"  required>
                                    <label for="devret_uretirken_verilen_fire_adet" class="form-label">Üretirken Verilen Fire </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-sharp fa-solid fa-layer-group"></i>
                                Tüketilen Stoklar
                            </h5>
                        </div>
                        <div class="card-body"> 
                            <?php foreach ($planlanmis_stok_veriler as $index => $planlanmis_stok_veri) { ?>
                                <input type="hidden" name="birim_id[]" value="<?php echo $planlanmis_stok_veri['birim_id'];?>">
                                <input type="hidden" name="stok_id[]" value="<?php echo $planlanmis_stok_veri['stok_id'];?>">
                                <input type="hidden" name="stok_alt_kalem_id[]" value="<?php echo $planlanmis_stok_veri['stok_alt_kalem_id'];?>">
                                <input type="hidden" name="stok_alt_depo_id[]"  value="<?php echo $planlanmis_stok_veri['stok_alt_depo_id'];?>">
                                <div class="row g-3">
                                    <div class="col-md-12 fw-bold d-flex justify-content-between">
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $index +1; ?>-
                                            <?php echo "STOK KODU : ".$planlanmis_stok_veri['stok_kodu'];  ?>
                                        </span> 
                                        <?php 
                                            $stok_alt_kalem_veri            = json_decode($planlanmis_stok_veri['veri'], true);
                                            $stok_alt_kalem_veri_degerler   = array_values($stok_alt_kalem_veri);
                                        ?>
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $planlanmis_stok_veri['stok_kalem']; ?> - 
                                            <?php 
                                                echo implode('/', $stok_alt_kalem_veri_degerler); 
                                            ?>
                                        </span>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="devret_tuketim_miktari-<?php echo $index; ?>" name="tuketim_miktari[]" required>
                                        <label for="devret_tuketim_miktari-<?php echo $index; ?>">Tüketilen Stok Miktarı (<?php echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="devret_fire_miktari-<?php echo $index; ?>" name="fire_miktari[]" required>
                                        <label for="devret_fire_miktari-<?php echo $index; ?>">Fire Stok Miktarı (<?php  echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                </div>
                                <hr class="border-3">
                            <?php }?>

                            <?php if(empty($planlanmis_stok_veriler)){?>
                                <div class="alert alert-danger fw-bold">
                                    1- Tüketilen Stok Bulunmuyor!
                                </div>
                            <?php }?>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success" name="degistir" id="is-degistir-button">
                            <i class="fa-regular fa-paper-plane"></i> GÖNDER
                        </button>
                    </div>                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>


<!-- 10- Mesaj Modal -->
<div class="modal fade" id="mesaj-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="mesajStaticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="mesajStaticBackdropLabel">
                    <i class="fa-solid fa-envelope"></i>
                    Mesaj İşlemi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-body">
                        <form class="row g-3 needs-validation mb-2" action="/index.php?url=/index.php?url=makina_is_ekran_db_islem" method="POST" id="mesaj-gonder-form">
                            <input type="hidden" name="departman_id"    value="<?php echo $departman_id; ?>">
                            <input type="hidden" name="planlama_id"     value="<?php echo $planlama_id ?>">
                            <input type="hidden" name="makina_id"       value="<?php echo $makina_id; ?>">
                            <input type="hidden" name="grup_kodu"       value="<?php echo $is['grup_kodu']; ?>">
                            <input type="hidden" name="mevcut_asama"    value="<?php echo $is['mevcut_asama']; ?>">
                            <div class="form-floating col-md-12">
                                <textarea name="mesaj" id="mesaj" class="form-control" style="height: 100px;"></textarea>
                                <label for="mesaj" class="form-label">Mesaj</label>
                            </div>
                            <div class="form-floating col-md-12">
                                <button type="submit" class="btn btn-primary" name="mesaj-gonder" id="mesaj-gonder-button">
                                    <i class="fa-regular fa-paper-plane"></i> GÖNDER
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <table id="myTable" class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Tarih</th>
                            <th>Ad Soyad</th>
                            <th>Departman</th>
                            <th>Makina Adı/Model</th>
                            <th style="width:40% !important">Mesaj</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mesajlar as $index => $mesaj) { ?>
                            <tr>
                                <th class="table-primary"><?php echo $index + 1; ?></th>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($mesaj['tarih'])); ?></td>
                                <td><?php echo $mesaj['ad'].' '.$mesaj['soyad']; ?></td>
                                <td><?php echo $mesaj['departman']; ?></td>
                                <td><?php echo $mesaj['makina_adi'].' '.$mesaj['makina_modeli']; ?></td>
                                <td style="width:40% !important"><?php echo $mesaj['mesaj'];?></td>
                            </tr>
                        <?php }?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>


<!-- 11- Bitir Modal -->
<div class="modal fade" id="bitir-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">
                    <i class="fa-solid fa-circle-stop"></i> İşlemi Bitir
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group fw-bold mb-1 border-secondary">
                    <li class="list-group-item active fw-bold fs-6" aria-current="true">ÖNEMLİ BİLGİLER</li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-circle-exclamation fs-6"></i> Üretilen Miktarları fireler ile birlikte girin!
                    </li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-circle-exclamation fs-6"></i> Tüketim Miktarları fireler ile birlikte girin!
                    </li>
                    
                    <?php if($firma_ayar['eksik_uretimde_onay_isteme_durumu'] == 'evet'){ ?>
                        <li class="list-group-item list-group-item-danger text-decoration-underline fs-6">
                            <i class="fa-solid fa-circle-exclamation"></i> Ürün Eksik Üretildiğinde Onay Alındıktan Sonra Bir Sonraki Aşamaya Geçer
                        </li>
                    <?php } ?>
                </ul>

                <form action="/index.php?url=makina_is_ekran_db_islem"  method="POST" id="isi-bitir-form">
                    <input type="hidden" name="siparis_id" value="<?php echo $is['siparis_id'];?>">
                    <input type="hidden" name="grup_kodu" value="<?php echo $is['grup_kodu'];?>">
                    <input type="hidden" name="eksik_uretimde_onay_isteme_durumu" value="<?php echo $firma_ayar['eksik_uretimde_onay_isteme_durumu'];?>">
                    <input type="hidden" name="min_uretilecek_adet" value="<?php echo $kalan_adet;?>">
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                    <input type="hidden" name="asama_sayisi" value="<?php echo $is['asama_sayisi'];?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                    <input type="hidden" name="tekil_kod" value="<?php echo $is['tekil_kod'];?>">
                    <input type="hidden" name="uretim_islem_tarih_id" value="<?php echo $uretim_islem_tarih_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_baslatma_tarih" value="<?php echo $uretim_islem_tarih_baslatma_tarih;?>">
                    
                    <div id="bitir-formlar" class="mb-1"></div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                Üretilen Adet ve Fireler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretilen_adet" class="form-control" id="devret_uretilen_adet"  required>
                                    <label for="devret_uretilen_adet" class="form-label fw-bold">Üretilen Adet</label>
                                </div>
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretirken_verilen_fire_adet" class="form-control" id="devret_uretirken_verilen_fire_adet"  required>
                                    <label for="devret_uretirken_verilen_fire_adet" class="form-label fw-bold">Üretirken Verilen Fire Adet</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-sharp fa-solid fa-layer-group"></i>
                                Tüketilen Stoklar
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($planlanmis_stok_veriler as $index => $planlanmis_stok_veri) { ?>
                                <input type="hidden" name="birim_id[]" value="<?php echo $planlanmis_stok_veri['birim_id'];?>">
                                <input type="hidden" name="stok_id[]" value="<?php echo $planlanmis_stok_veri['stok_id'];?>">
                                <input type="hidden" name="stok_alt_kalem_id[]" value="<?php echo $planlanmis_stok_veri['stok_alt_kalem_id'];?>">
                                <input type="hidden" name="stok_alt_depo_id[]"  value="<?php echo $planlanmis_stok_veri['stok_alt_depo_id'];?>">
                                <div class="row g-3">
                                    <div class="col-md-12 fw-bold d-flex justify-content-between">
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $index +1; ?>-
                                            <?php echo "STOK KODU : ".$planlanmis_stok_veri['stok_kodu'];  ?>
                                        </span> 
                                        <?php 
                                            $stok_alt_kalem_veri            = json_decode($planlanmis_stok_veri['veri'], true);
                                            $stok_alt_kalem_veri_degerler   = array_values($stok_alt_kalem_veri);
                                        ?>
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $planlanmis_stok_veri['stok_kalem']; ?> - 
                                            <?php 
                                                echo implode('/', $stok_alt_kalem_veri_degerler); 
                                            ?>
                                        </span>
                                    </div>

                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="bitir_tuketim_miktari-<?php echo $index; ?>" name="tuketim_miktari[]" required>
                                        <label for="bitir_tuketim_miktari-<?php echo $index; ?>" class="form-label fw-bold">
                                            Tüketilen Stok Miktarı (<?php echo $planlanmis_stok_veri['birim_ad']; ?>)
                                        </label>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="bitir_fire_miktari-<?php echo $index; ?>" name="fire_miktari[]" required>
                                        <label for="bitir_fire_miktari-<?php echo $index; ?>" class="form-label fw-bold">
                                            Fire Stok Miktarı (<?php  echo $planlanmis_stok_veri['birim_ad']; ?>)
                                        </label>
                                    </div>
                                </div>
                                <hr class="border-3">
                            <?php }?>

                            <?php if(empty($planlanmis_stok_veriler)){?>
                                <div class="alert alert-danger fw-bold">
                                    1- Tüketilen Stok Bulunmuyor!
                                </div>
                            <?php }?>

                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success fw-bold" name="is_bitir_son_asama" id="isi-bitir-button">
                            <i class="fa-regular fa-paper-plane"></i> GÖNDER
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 12- Yetkili Çağırma Modal -->
<div class="modal fade" id="yetkili-cagirma-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>
                    <i class="fa-solid fa-user"></i>
                    Yetkili Çağırma
                </h5>
            </div>
            <div class="modal-body"> 
                <div class="card m-2">
                    <div class="card-body">
                        <form id="yetkili-amir-cagir-form" action="/index.php?url=makina_is_ekran_db_islem"  method="POST" class="row g-3 needs-validation">
                            <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>">   
                            <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>"> 
                            <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                            <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                            <input type="hidden" name="tekil_kod" value="<?php echo $is['tekil_kod'];?>">
                            <div class="form-floating col-md-12">
                                <select class="form-select" id="gelen_personel_id" name="gelen_personel_id" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($departmandan_yetkili_kisiler as  $yetkili) { ?>
                                        <option value="<?php echo  $yetkili['id'];?>">
                                            <?php echo $yetkili['ad'].' '.$yetkili['soyad']; ?>
                                        </option>
                                    <?php }?>
                                </select>
                                <label for="gelen_personel_id" class="form-label">Yetkili Amir</label>
                            </div>
                            <div class="form-floating col-md-12 d-grid">
                                <button type="submit" class="btn btn-success btn-lg" name="yetkili_cagirma" id="yetkili-amir-cagir-button">
                                    <i class="fa-solid fa-user"></i> ÇAGIR
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- 
                <table id="myTable" class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Gelen Ad Soyad</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        /*
                            $sql ="SELECT personeller.ad, personeller.soyad, uretim_yetkili_log.tarih FROM `personeller` 
                            JOIN uretim_yetkili_log ON uretim_yetkili_log.gelen_personel_id = personeller.id
                            WHERE uretim_yetkili_log.firma_id = :firma_id AND uretim_yetkili_log.planlama_id = :planlama_id";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->bindParam('planlama_id', $planlama_id);
                            $sth->execute();
                            $yetkili_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
                            */
                        ?>
                        <?php //foreach ($yetkili_loglar as $index => $yetkili_log) { ?>
                            <tr>
                                <th class="table-primary"><?php //echo $index + 1; ?></th>
                                <td><?php //echo $yetkili_log['ad'].' '.$yetkili_log['soyad'];?></td>
                                <td><?php //echo date('d-m-Y H:i:s', strtotime($yetkili_log['tarih'])); ?></td>
                            </tr>
                        <?php //}?>
                    </tbody>
                </table>
                -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-rectangle-xmark"></i> KAPAT
                </button>
            </div>
        </div>
    </div>
</div>


<!-- 13- Arıza Modal -->
<div class="modal fade" id="ariza-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="arizaStaticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="arizaStaticBackdropLabel">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    Arıza İşlemi
                </h5>
                <h5 class="fw-bold">
                    <i class="fa-regular fa-clock"></i> 
                    <span id="ariza-gecen-sure" class="text-decoration-underline">00:00:00</span>
                </h5>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-body"> 
                        <form action="/index.php?url=makina_is_ekran_db_islem"  method="POST" class="row g-3 needs-validation mb-3" id="ariza-bitir-form">
                            <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>">     
                            <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>">   
                            <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                            <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                            <input type="hidden" id="ariza_baslatma_tarih" name="ariza_baslatma_tarih">
                            <div class="form-floating col-md-12">
                                <textarea class="form-control" id="ariza-mesaj" name="ariza_mesaj" required style="height:150px"></textarea>
                                <label for="ariza-mesaj" class="col-sm-2 col-form-label fw-bold">Arıza Sebebi</label>
                            </div>
                            <div class="form-floating col-md-12">
                                <button type="submit" class="btn btn-success fw-bold"  name="ariza-bitir" id="ariza-bitir-button">
                                    ARIZA BİTTİ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <table id="myTable" class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr class="table-primary">
                            <th>#</th>
                            <th>Başlangıç Tarihi</th>
                            <th>Bitiş Tarihi</th>
                            <th style="width:40% !important">Mesaj</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sql = "SELECT baslatma_tarihi,bitis_tarihi,mesaj FROM uretim_ariza_log 
                                    WHERE firma_id = :firma_id AND planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama";
                            $sth = $conn->prepare($sql);
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->bindParam('planlama_id', $planlama_id);
                            $sth->bindParam('mevcut_asama', $is['mevcut_asama']);
                            $sth->execute();
                            $ariza_loglar = $sth->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($ariza_loglar as $index => $ariza_log) { ?>
                            <tr>
                                <th class="table-primary"><?php echo $index + 1; ?></th>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($ariza_log['baslatma_tarihi']));?></td>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($ariza_log['bitis_tarihi']));?></td>
                                <td style="width:40% !important"><?php echo $ariza_log['mesaj'];?></td>
                            </tr>
                        <?php }?>
                    </tbody>
                </table>
                
            </div>
            
        </div>
    </div>
</div>

<!-- Sipariş Pdf -->
<div class="modal fade" id="siparis-pdf-modal"  tabindex="-1" aria-labelledby="arizaStaticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="arizaStaticBackdropLabel">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    Sipariş PDF
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="height:1000px">
                <object id="siparis-pdf-object" data="" type="application/pdf" width="100%" height="100%"></object>          
            </div>
        </div>
    </div>
</div>

<?php 
    $sql = "SELECT id, baslatma_tarihi FROM `uretim_bakim_log`  
        WHERE planlama_id = :planlama_id AND mevcut_asama = :mevcut_asama  AND bitis_tarihi = '0000-00-00 00:00:00'";
    $sth = $conn->prepare($sql);
    $sth->bindParam("planlama_id", $planlama_id);
    $sth->bindParam("mevcut_asama", $is['mevcut_asama']);
    $sth->execute();
    $bakim_personel_log = $sth->fetch(PDO::FETCH_ASSOC);

    
?>
<!-- 14- Bakım Personel Çağırma Modal -->
<div class="modal fade" id="bakim-personel-cagirma-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>
                    <i class="fa-regular fa-circle-user"></i> Bakım Yetkili Çağırma
                </h5>
                <h5 class="fw-bold">
                    <i class="fa-regular fa-clock"></i> <span id="bakim-gecen-sure">00:00:00</span>
                </h5>
            </div>
            <div class="modal-body"> 
                <?php if(empty($bakim_personel_log)){ ?>
                    <div class="card">
                        <div class="card-body">
                            <form action="/index.php?url=makina_is_ekran_db_islem"  method="POST" class="row g-3 needs-validation mb-3" id="bakim-personeli-cagir-form">
                                <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>"> 
                                <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>">  
                                <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                                <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                                <div class="form-floating col-md-12">
                                    <select class="form-select" id="gelen_personel_id" name="gelen_personel_id" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($bakim_personeller as  $bakim_personel) { ?>
                                            <option value="<?php echo  $bakim_personel['id'];?>">
                                                <?php echo $bakim_personel['ad'].' '.$bakim_personel['soyad']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <label for="gelen_personel_id" class="form-label">Bakım Personeli</label>
                                </div>
                                <div class="form-floating col-md-12 d-grid">
                                    <button type="submit" class="btn btn-success btn-lg mt-1" name="bakim_personel_cagir" id="bakim-personeli-cagir-button">
                                        <i class="fa-solid fa-user"></i> BAKIM PERSONEL ÇAGIR
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php }else{?> 
                    <div class="card mb-3">
                        <div class="card-body"> 
                            <form action="/index.php?url=makina_is_ekran_db_islem"  method="POST" class="row g-3 needs-validation mb-3" id="bakim-bitir-form">
                                <input type="hidden" name="uretim_bakim_log_id" value="<?php echo $bakim_personel_log['id']; ?>">
                                <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                                <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>"> 
                                <div class="form-floating col-md-12 d-grid">
                                    <button class="btn btn-lg btn-secondary fs-4" id="bakim-personeli-geldi">
                                        <i class="fa-regular fa-circle-user"></i> BAKIM PERSONELİ GELDİGİN TIKLAYINIZ!
                                    </button>
                                </div>
                                <div class="form-floating col-md-12">
                                    <textarea class="form-control" id="bakim-mesaj" name="ariza_sebebi" required style="height:130px;"></textarea>
                                    <label for="bakim-mesaj" class="col-sm-2 col-form-label fw-bold">Arıza Sebebi</label>
                                </div>
                                <div class="form-floating col-md-12">
                                    <div class="border rounded p-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="bakim_sorunu_cozuldumu" value="1" id="bakim_sorunu_cozuldumu" >
                                            <label class="form-check-label text-decoration-underline fw-bold" for="bakim_sorunu_cozuldumu">Sorun Çözüldü Mü?</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-floating col-md-12 d-grid">
                                    <button type="submit" class="btn btn-success fw-bold"  name="bakim_bitir" id="bakim-bitir-button">
                                        BAKIM BİTİR
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php } ?>
                <!--
                <table class="table table-bordered table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Bakım Personel Ad Soyad</th>
                            <th>Başlama Tarihi</th>
                            <th>Personel Gelme Tarihi</th>
                            <th>Bitiş Tarihi</th>
                        </tr>
                    </thead>
                    <tbody id="bakim-personel-cagirma-table-data"></tbody>
                </table>
                -->
            </div>
            <?php if(empty($bakim_personel_log)){ ?>
            <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-rectangle-xmark"></i> KAPAT
                    </button>
            </div>
            <?php } ?>
        </div>
    </div>
</div>  


<!-- 15- Aktar Modal -->
<div class="modal fade" id="aktar-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">
                    <i class="fa-solid fa-solid fa-share-nodes"></i> İşi Aktar
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group fw-bold mb-1">
                    <li class="list-group-item active fw-bold" aria-current="true">ÖNEMLİ BİLGİLER</li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> Üretim Miktarları Fireler ile birlikte girin!
                    </li>
                    <li class="list-group-item list-group-item-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> Tüketim Miktarları Fireler ile birlikte girin!
                    </li>
                    <?php if($firma_ayar['eksik_uretimde_onay_isteme_durumu'] == 'evet'){ ?>
                        <li class="list-group-item list-group-item-danger text-decoration-underline fs-6">
                            <i class="fa-solid fa-circle-exclamation"></i> Ürün Eksik Üretildiğinde Onay Alındıktan Sonra Bir Sonraki Aşamaya Geçer
                        </li>
                    <?php } ?>
                </ul>
                <form action="/index.php?url=makina_is_ekran_db_islem"  method="POST" class="g-3 needs-validation">
                    <input type="hidden" name="siparis_id" value="<?php echo $is['siparis_id'];?>">
                    <input type="hidden" name="planlama_id" value="<?php echo $planlama_id;?>">
                    <input type="hidden" name="grup_kodu" value="<?php echo $is['grup_kodu'];?>">
                    <input type="hidden" name="mevcut_asama" value="<?php echo $is['mevcut_asama'];?>">
                    <input type="hidden" name="asama_sayisi" value="<?php echo $is['asama_sayisi'];?>">
                    <input type="hidden" name="departman_id" value="<?php echo $departman_id;?>">
                    <input type="hidden" name="makina_id" value="<?php echo $makina_id;?>">
                    <input type="hidden" name="tekil_kod" value="<?php echo $is['tekil_kod'];?>">
                    <input type="hidden" name="uretim_islem_tarih_id" value="<?php echo $uretim_islem_tarih_id;?>">
                    <input type="hidden" name="uretim_islem_tarih_baslatma_tarih" value="<?php echo $uretim_islem_tarih_baslatma_tarih;?>">
                    
                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                Üretilen Adet ve Fireler
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-floating col-md-6">
                                    <input type="number" min="0" max="<?php echo $kalan_adet-1;?>" name="uretilen_adet" class="form-control" id="aktar_uretilen_adet"  required>
                                    <label for="aktar_uretilen_adet" class="form-label">Üretilen Adet</label>
                                </div>
                                <div class="form-floating col-md-6">
                                    <input type="number" name="uretirken_verilen_fire_adet" class="form-control" id="aktar_uretirken_verilen_fire_adet"  required>
                                    <label for="aktar_uretirken_verilen_fire_adet" class="form-label">Üretirken Verilen Fire </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-2 border-secondary">
                        <div class="card-header">
                            <h5>
                                <i class="fa-sharp fa-solid fa-layer-group"></i>
                                Tüketilen Stoklar
                            </h5>
                        </div>
                        <div class="card-body"> 
                            <?php foreach ($planlanmis_stok_veriler as $index => $planlanmis_stok_veri) { ?>
                                <input type="hidden" name="birim_id[]" value="<?php echo $planlanmis_stok_veri['birim_id'];?>">
                                <input type="hidden" name="stok_id[]" value="<?php echo $planlanmis_stok_veri['stok_id'];?>">
                                <input type="hidden" name="stok_alt_kalem_id[]" value="<?php echo $planlanmis_stok_veri['stok_alt_kalem_id'];?>">
                                <input type="hidden" name="stok_alt_depo_id[]"  value="<?php echo $planlanmis_stok_veri['stok_alt_depo_id'];?>">
                                <div class="row g-3">
                                    <div class="col-md-12 fw-bold d-flex justify-content-between">
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $index +1; ?>-
                                            <?php echo "STOK KODU : ".$planlanmis_stok_veri['stok_kodu'];  ?>
                                        </span> 
                                        <?php 
                                            $stok_alt_kalem_veri            = json_decode($planlanmis_stok_veri['veri'], true);
                                            $stok_alt_kalem_veri_degerler   = array_values($stok_alt_kalem_veri);
                                        ?>
                                        <span class="text-decoration-underline badge bg-secondary fs-6">
                                            <i class="fa-sharp fa-solid fa-layer-group"></i>
                                            <?php echo $planlanmis_stok_veri['stok_kalem']; ?> - 
                                            <?php 
                                                echo implode('/', $stok_alt_kalem_veri_degerler); 
                                            ?>
                                        </span>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="aktar_tuketim_miktari-<?php echo $index; ?>" name="tuketim_miktari[]" required>
                                        <label for="aktar_tuketim_miktari-<?php echo $index; ?>">Tüketilen Stok Miktarı (<?php echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                    <div class="form-floating col-md-6">
                                        <input type="number" class="form-control" step=".001" id="aktar_fire_miktari-<?php echo $index; ?>" name="fire_miktari[]" required>
                                        <label for="aktar_fire_miktari-<?php echo $index; ?>">Fire Stok Miktarı (<?php  echo $planlanmis_stok_veri['birim_ad']; ?>)</label>
                                    </div>
                                </div>
                            <?php }?>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success fw-bold" name="aktar">
                            <i class="fa-regular fa-paper-plane"></i>  GÖNDER
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">KAPAT</button>
            </div>
        </div>
    </div>
</div>


<script>
    $(function(){
        $("#isi-baslat-form").submit(function(){
            $("#isi-baslat-button").addClass('disabled');
            return true;
        });

        $("#mola-bitir-form").submit(function(){
            $("#mola-bitir-button").addClass('disabled');
            return true;
        });

        $("#yemek-mola-bitir-form").submit(function(){
            $("#yemek-mola-bitir-button").addClass('disabled');
            return true;
        });

        $("#toplanti-bitir-form").submit(function(){
            $("#toplanti-bitir-button").addClass('disabled');
            return true;
        });

        $("#mesaj-gonder-form").submit(function(){
            $("#mesaj-gonder-button").addClass('disabled');
            return true;
        });

        $("#paydos-bitir-form").submit(function(){
            $("#paydos-bitir-button").addClass('disabled');
            return true;
        });

        $("#devret-bitir-form").submit(function(){
            $("#devret-bitir-button").addClass('disabled');
            return true;
        });

        $("#is-degistir-form").submit(function(){
            $("#is-degistir-button").addClass('disabled');
            return true;
        });

        $("#ariza-bitir-form").submit(function(){
            $("#ariza-bitir-button").addClass('disabled');
            return true;
        });

        $("#yetkili-amir-cagir-form").submit(function(){
            $("#yetkili-amir-cagir-button").addClass('disabled');
            return true;
        });

        $("#bakim-personeli-cagir-form").submit(function(){
            $("#bakim-personeli-cagir-button").addClass('disabled');
            return true;
        });

        $("#bakim-bitir-form").submit(function(){
            $("#bakim-bitir-button").addClass('disabled');
            return true;
        });
        


        $("#isi-bitir-form").submit(function(){
            $("#isi-bitir-button").addClass('disabled');
            return true;
        });

        //Makina Ayar Başlatma
        $("#makina-ayar").click(function(){
            var makina_ayar_gecen_sure_interval;
            if(confirm("Makina Ayarına Başlamak İstediğinize Emin Misiniz?")){
                const baslatma_tarih = new Date();
                const makina_ayar_suresi_varmi = "<?php echo $makina['makina_ayar_suresi_varmi']; ?>";
                log(makina_ayar_suresi_varmi)
                if(makina_ayar_suresi_varmi == 'yok'){ //Makina Ayar Süresi Yok
                    log(makina_ayar_suresi_varmi)
                    $("#uretim_makina_ayar_baslatma_tarihi").val();
                    $("#makina-ayar-bitir").trigger('click');
                }else{ //Makina Ayar Süresi Var
                    $("#makina-ayar-modal").modal('show');
                    $("#uretim_makina_ayar_baslatma_tarihi").val(formatDate(baslatma_tarih));
                    makina_ayar_gecen_sure_interval = setInterval(()=>{
                        $("#makina-ayar-gecen-sure").text(iki_tarih_arasindaki(baslatma_tarih, new Date()))
                    }, 1000);
                }
            }
        });


        //Makina Ayar Bitir
        $("#makina-ayar-bitir").click(()=>{
            //clearInterval(makina_ayar_gecen_sure_interval);
            const makina_ayar_baslatma_tarih = $("#uretim_makina_ayar_baslatma_tarihi").val();
            $.ajax({
                url         : "/index.php?url=makina_is_ekran_db_islem&islem=makina-ayar-bitir&makina_id=<?php echo $makina_id; ?>&mevcut_asama=<?php echo $is['mevcut_asama'];?>&planlama_id=<?php echo $planlama_id; ?>&departman_id=<?php echo $departman_id; ?>&makina_ayar_baslatma_tarih=" + makina_ayar_baslatma_tarih + "&makina_ayar_suresi_varmi=<?php echo $makina['makina_ayar_suresi_varmi']; ?>",
                dataType    : "JSON",
                success     : function(data){
                    if(data.durum){
                        $("#makina-ayar-modal").modal('hide');
                        baslatgicFormlariGetir();
                    }else{
                        $.notify("İşlemde Hata Meydana Geldi!", "error");
                    }
                }
            });
        });


        //formları seçme ve seçmeme işlemi
        $(document).on('change','.formlar-input',function(){
            if($(this).is(':checked'))  $("."+$(this).attr('id')).val('1');
            else                        $("."+$(this).attr('id')).val('0');
        });


        //kontrol formaları getirme ve modal basma
        $("#kontrol").click(()=> {
            $.ajax({
                url         : "/index.php?url=makina_is_ekran_db_islem&islem=kontrol_formalari_getir&planlama_id="+<?php echo $planlama_id; ?>+"&departman_id=<?php echo $departman_id; ?>&tekil_kod=<?php echo $is['tekil_kod']; ?>" ,
                dataType    : "JSON",
                success     : function(data){
                    let baslangic_formlarHTML = `
                        <ul class="list-group">
                            <li class="list-group-item active fw-bold fs-5" aria-current="true">
                                <i class="fa-regular fa-circle-play"></i> Başlangıçtaki Formlar
                            </li>
                    `;
                    let zorunluk_durumu;
                    data.baslangic_formlar.forEach((form) => {
                        zorunluk_durumu = form.deger == '1' ? 'checked' : '';
                        baslangic_formlarHTML += `
                            <li class="list-group-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="form-${form.id}" ${zorunluk_durumu}  disabled>
                                    <label class="form-check-label fw-bold" for="form-${form.id}">${form.konu}</label>
                                </div>
                            </li>
                        `;
                    });


                    if(data.baslangic_formlar.length == 0){
                        baslangic_formlarHTML += `
                            <li class="list-group-item text-danger fw-bold">
                                <i class="fa-solid fa-circle-exclamation"></i> Bu Departmanda Kontrol Anketi Yok.
                            </li>
                        `;
                    }
                    baslangic_formlarHTML += '</ul>';
                    
                    let bitisteki_formlarHTML =  `
                        <ul class="list-group">
                            <li class="list-group-item active fw-bold fs-5" aria-current="true">
                                <i class="fa-regular fa-circle-stop"></i> Bitişteki Formlar
                            </li>
                    `;
                    data.bitisteki_formlar.forEach((form, index) => {
                        bitisteki_formlarHTML += `
                            <li class="list-group-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckCheckedDisabled" disabled>
                                    <label class="form-check-label fw-bold" for="flexSwitchCheckCheckedDisabled">${form.konu}</label>
                                </div>
                            </li>
                        `;
                    });
                    if(data.bitisteki_formlar.length == 0){
                        bitisteki_formlarHTML += `
                            <li class="list-group-item text-danger fw-bold">
                                <i class="fa-solid fa-circle-exclamation"></i> Bu Departmanda Kontrol Anketi Yok.
                            </li>
                        `;
                    }
                    bitisteki_formlarHTML += '</ul>';

                    $("#baslangic_formlar").html(baslangic_formlarHTML);
                    $("#bitisteki_formlar").html(bitisteki_formlarHTML);

                    $("#kontrol-modal").modal('show');
                }
            });
        });

        //iş bitime modal açma
        $("#is-bitir").click(()=>{
            const departman_id = <?php echo $departman_id; ?>;
            const planlama_id  = <?php echo $planlama_id; ?>;
            $.ajax({
                url         : "/index.php?url=makina_is_ekran_db_islem",
                dataType    : "JSON",
                type        : "POST",
                data        : {departman_id, 'islem':'is_bitir_form_getir'},
                success     : function(data)
                {
                    let bitirformlarHTML = '', zorunluk_durumu, zorunluk_yazisi;
                    bitirformlarHTML += '<ul class="list-group border-secondary">';

                    data.formlar.forEach((form) => {
                        zorunluk_durumu = form.zorunluluk_durumu == 'evet' ? 'required' : ''
                        zorunluk_yazisi = form.zorunluluk_durumu == 'evet' ? '<span class="text-danger">[*]</span>' : ''
                        bitirformlarHTML += `
                            <li class="list-group-item">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="bitir_formlar[${form.id}]" class="form-${form.id}" value="0">
                                    <input class="form-check-input formlar-input" type="checkbox"  role="switch" id="form-${form.id}" ${zorunluk_durumu}>
                                    <label class="form-check-label" for="form-${form.id}">${zorunluk_yazisi} ${form.konu}</label>
                                </div>
                            </li>
                        `;
                    });
                    bitirformlarHTML += '</ul>';
                    $("#bitir-formlar").html(bitirformlarHTML);
                    $("#bitir-modal").modal('show');
                }
            });
        });

        //Bakım 
        $("#bakim-baslat").click(function(){
            //if(confirm("Bakım Personelini Çağırmak İstediğinize Emin Misiniz?")){
                $("#bakim-personel-cagirma-modal").modal('show');
                let baslatma_tarih = new Date();
                <?php if(!empty($bakim_personel_log)){ ?>
                    baslatma_tarih = new Date("<?php echo $bakim_personel_log['baslatma_tarihi'];?>");
                <?php } ?>

                let bakim_gecen_sure_interval = setInterval(()=>{
                    $("#bakim-gecen-sure").text(iki_tarih_arasindaki(baslatma_tarih, new Date()))
                }, 1000);
            //}
        });

        //Bakım Personeli Geldi
        $("#bakim-personeli-geldi").click(function(){
            $(this).addClass('disabled');
            const uretim_bakim_log_id = <?php echo isset($bakim_personel_log['id']) ? $bakim_personel_log['id'] : 0; ?>;
            $.ajax({
                url         : "/index.php?url=makina_is_ekran_db_islem",
                dataType    : "JSON",
                type        : "POST",
                data        : {uretim_bakim_log_id, 'islem':'bakim_personeli_geldi'},
                success     : function(data)
                {
                    if(data.durum){
                        $("#bakim-personeli-geldi").remove();
                    }
                }
            });
        });

        <?php if(!empty($bakim_personel_log)){ ?>
            $("#bakim-baslat").trigger("click");
        <?php }?>

        //sipariş pdfleri modalda gösterme
        $(".siparis-pdf-dosya").click(function(){
            const pdf_url = $(this).data('siparis-url');
            $("#siparis-pdf-object").attr('data', pdf_url);
            $("#siparis-pdf-modal").modal('show');
        });
    });

    function baslatgicFormlariGetir(){
        $.ajax({
            url         : "/index.php?url=makina_is_ekran_db_islem&islem=formalari_getir&planlama_id=<?php echo $planlama_id; ?>&departman_id=<?php echo $departman_id; ?>",
            dataType    : "JSON",
            success     : function(data){
                console.log(data);
                let formHTML = `<ul class="list-group">`;
                let zorunluk_durumu;
                let zorunluk_yazisi;
                data.formlar.forEach((form) => {
                    zorunluk_durumu = form.zorunluluk_durumu == 'evet' ? 'required' : '';
                    zorunluk_yazisi = form.zorunluluk_durumu == 'evet' ? '<span class="text-danger">[*]</span>' : '';

                    formHTML += `
                        <li class="list-group-item">
                            <div class="form-check form-switch">
                                <input type="hidden" name="formlar[${form.id}]" class="form-${form.id}" value="0">
                                <input class="form-check-input formlar-input" type="checkbox"  role="switch" 
                                    id="form-${form.id}" ${zorunluk_durumu}>
                                <label class="form-check-label" for="form-${form.id}">${zorunluk_yazisi} ${form.konu}</label>
                            </div>
                        </li>
                    `;
                });
                if(data.formlar.length == 0){
                    formHTML += `
                        <li class="list-group-item text-danger fw-bold">1- Bu Departmanda Kontrol Anket Yok.</li>
                    `;
                }
                formHTML += `</ul>`;
                $("#formlar").append(formHTML);
                $("#form-modal").modal('show');
            }
        });
    }

    const tarih_arasindaki_fark = setInterval(function(){
        const ilkTarih = new Date("<?php echo isset($planlama_tarih['baslatma_tarih']) ? $planlama_tarih['baslatma_tarih'] : date('Y-m-d H:i:s')?>");
        const ikinciTarih = new Date();

        $("#sure-farki").text(iki_tarih_arasindaki(ilkTarih, ikinciTarih));
    }, 1000);
</script>