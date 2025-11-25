<?php 
    include "include/oturum_kontrol.php";
?> 
    <div class="row">
        <div class="card mt-2">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-sharp fa-solid fa-layer-group"></i> Stoklar
                </h5>
                <div>
                    <a href="javascript:window.history.back();" 
                        class="btn btn-secondary"
                        data-bs-target="#departman-ekle-modal"
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom" 
                        data-bs-title="Geri Dön"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <?php 
                    $sth = $conn->prepare('SELECT * FROM stok_kalemleri WHERE firma_id = :firma_id');
                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                    $sth->execute();
                    $stok_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if(empty($stok_kalemler)){ ?>
                    <div class="alert alert-danger" role="alert">
                        <a href="/index.php?url=stok_kalem" class="btn btn-danger btn-sm">
                            <i class="fa-solid fa-plus"></i> Stok Kalem Ekleyiniz
                        </a>
                    </div>
                <?php } ?>
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <?php foreach ($stok_kalemler as $key => $stok_kalem) { ?>
                            <?php 
                                $aktif_class = "";
                                if(!isset($_GET['stok_id']) && $key == 0)
                                {
                                    $aktif_class = "active";
                                }
                                else if(isset($_GET['stok_id']) && $_GET['stok_id'] == $stok_kalem['id'] )
                                {
                                    $aktif_class = "active";
                                }
                            ?>

                            <button class="nav-link fw-bold <?php echo $aktif_class; ?>" id="nav-tab-<?php echo $key;?>" data-bs-toggle="tab" 
                                data-bs-target="#nav-<?php echo $key;?>" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                                <?php echo ($key+1).'- '.$stok_kalem['stok_kalem']; ?>
                            </button>
                        <?php }?>
                    </div>
                </nav>
                <div class="tab-content mt-2" id="nav-tabContent">
                    <?php foreach ($stok_kalemler as $key => $stok_kalem) { ?>
                        <?php 
                            $aktif_class = "";
                            if(!isset($_GET['stok_id']) && $key == 0)
                            {
                                $aktif_class = "show active";
                            }
                            else if(isset($_GET['stok_id']) && $_GET['stok_id'] == $stok_kalem['id'] )
                            {
                                $aktif_class = "show active";
                            }
                        ?>
                        <div class="tab-pane fade <?php echo $aktif_class; ?>" id="nav-<?php echo $key; ?>" role="tabpanel" 
                            aria-labelledby="nav-tab-<?php echo $key; ?>" tabindex="0">
                            <div class="row m-1 d-flex justify-content-center">
                                <div class="card">
                                    <div class="card-body">
                                        <?php 
                                            $sth = $conn->prepare('SELECT * FROM stok_alt_kalem_degerler 
                                                WHERE stok_id = :stok_id');
                                            $sth->bindParam('stok_id', $stok_kalem['id']);
                                            $sth->execute();
                                            $stok_alt_kalem_degerler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <?php if(empty($stok_alt_kalem_degerler)){ ?>
                                            <div class="alert alert-danger" role="alert">
                                                <a href="/index.php?url=stok_kalem" class="btn btn-danger btn-sm">
                                                    <i class="fa-solid fa-plus"></i> Stok Kalem Ekleyiniz
                                                </a>
                                            </div>
                                        <?php } ?>
                                        <form class="row g-3 needs-validation stok-alt-kalem-form" action="/index.php?url=stok_alt_kalem_db_islem" method="POST">
                                            
                                            <input type="hidden" name="stok_id" value="<?php echo $stok_kalem['id']; ?>">
                                            <?php foreach ($stok_alt_kalem_degerler as $key=>$stok_alt_kalem_deger) { ?>
                                                <div class="form-floating col-md-2 mb-1">
                                                    <input type="<?php echo $stok_alt_kalem_deger['kolon_tipi']?>" class="form-control" 
                                                        name="alt_stok_kalem_ad[<?php echo $stok_alt_kalem_deger['ad']; ?>]" 
                                                        id="<?php echo $key; ?>" required 
                                                    >
                                                    <label for="<?php echo $key; ?>" class="fw-bold form-label" >
                                                        <?php echo $stok_alt_kalem_deger['ad']; ?>
                                                    </label>
                                                </div>
                                            <?php }?>
                                            <?php if(!empty($stok_alt_kalem_degerler )){ ?>
                                                <div class="form-floating col-md-2">
                                                    <button type="submit" class="btn btn-primary btn-lg mt-1 stok-alt-kalem-button" name="stok_alt_kalem_ekle">
                                                        <i class="fa-regular fa-square-plus"></i> KAYDET
                                                    </button>
                                                </div>
                                            <?php } ?>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <?php 
                                    $sth = $conn->prepare("SELECT id,veri,stok_id, toplam_stok 
                                    FROM stok_alt_kalemler WHERE stok_id = :stok_id AND firma_id = :firma_id");
                                    $sth->bindParam('stok_id', $stok_kalem['id']);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->execute();
                                    $stok_alt_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <div class="table-responsive">
                                    <table id="myTable" class="table table-hover table-striped" >
                                        <thead class="table-primary">
                                            <tr>
                                                <th>#</th>
                                                <?php if(isset($stok_alt_kalemler[0])){ ?>
                                                    <?php $veriler = json_decode($stok_alt_kalemler[0]['veri'], true); ?>
                                                    <?php foreach ($veriler as $key => $veri) { ?>
                                                        <th><?php echo $key;?></th>
                                                    <?php } ?>
                                                <?php } ?>
                                                <th class="text-end">G. Stok</th>
                                                <th class="text-end">Tüketilen Stok</th>
                                                <th class="text-end">T. Stok</th>
                                                <th class="text-end">Fire</th>
                                                <th class="text-end">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stok_alt_kalemler as $key => $stok_alt_kalem) { 
                                                $veriler = json_decode($stok_alt_kalem['veri'], true); 

                                                $sth = $conn->prepare('SELECT birimler.ad FROM birimler 
                                                                        JOIN stok_alt_depolar ON stok_alt_depolar.birim_id = birimler.id 
                                                                        WHERE stok_alt_depolar.stok_alt_kalem_id = :stok_alt_kalem_id');
                                                $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem['id']);
                                                $sth->execute();
                                                $birim_bilgi = $sth->fetch(PDO::FETCH_ASSOC);

                                                $sql = "SELECT SUM(tuketim_miktari) AS toplam_tuketim_miktari ,
                                                        SUM(fire_miktari) AS toplam_fire_miktar 
                                                        FROM `stok_alt_depolar_kullanilanlar` 
                                                        WHERE stok_alt_kalem_id = :stok_alt_kalem_id";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam('stok_alt_kalem_id', $stok_alt_kalem['id']);
                                                $sth->execute();
                                                $toplam_tuketim = $sth->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                                <tr class="stok-row" 
                                                    data-stok-alt-kalem-id="<?php echo $stok_alt_kalem['id']; ?>" 
                                                    data-stok-id="<?php echo $stok_kalem['id']; ?>">
                                                    <th class="table-primary"><?php echo $key + 1; ?></th>
                                                    <?php foreach ($veriler as $key => $veri) { ?>
                                                        <th><?php echo $veri; ?></th>
                                                    <?php }?>
                                                    <th class="text-end">
                                                        <?php echo number_format($stok_alt_kalem['toplam_stok'], 0, '','.'); ?>
                                                        <?php echo isset($birim_bilgi['ad']) ? $birim_bilgi['ad'] : ''; ?>
                                                    </th>
                                                    <th class="text-end">
                                                        <?php echo isset($toplam_tuketim['toplam_tuketim_miktari']) && is_numeric($toplam_tuketim['toplam_tuketim_miktari']) 
                                                            ? number_format($toplam_tuketim['toplam_tuketim_miktari'], 0, '', '.') 
                                                            : '0'; ?>
                                                        <?php echo isset($birim_bilgi['ad']) ? $birim_bilgi['ad'] : ''; ?>
                                                    </th>
                                                    <th class="text-end">
                                                        <?php echo number_format($stok_alt_kalem['toplam_stok'] - $toplam_tuketim['toplam_tuketim_miktari']- $toplam_tuketim['toplam_fire_miktar'], 0, '','.'); ?>
                                                        <?php echo isset($birim_bilgi['ad']) ? $birim_bilgi['ad'] : ''; ?>
                                                    </th>
                                                    <th class="text-end">
                                                        <?php echo isset($toplam_tuketim['toplam_fire_miktar']) && is_numeric($toplam_tuketim['toplam_fire_miktar']) 
                                                            ? number_format($toplam_tuketim['toplam_fire_miktar'], 0, '', '.') 
                                                            : '0'; ?>
                                                        <?php echo isset($birim_bilgi['ad']) ? $birim_bilgi['ad'] : ''; ?>
                                                    </th>                                             
                                                    <th>
                                                        <div class="d-md-flex justify-content-end"> 
                                                            <div class="btn-group" role="group" aria-label="Basic example">
                                                                <a href="/index.php?url=stok_alt_depolar&stok_alt_kalem_id=<?php echo $stok_alt_kalem['id']; ?>&stok_id=<?php echo $stok_kalem['id']; ?>"  
                                                                    class="btn btn-secondary"
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-placement="bottom" 
                                                                    data-bs-title="Değerler"    
                                                                >
                                                                    <i class="fa-solid fa-table-list"></i>
                                                                </a>    
                                                                <a href="/index.php?url=stok_alt_kalem_guncelle&id=<?php echo $stok_alt_kalem['id']; ?>&stok_id=<?php echo $stok_alt_kalem['stok_id']; ?>" 
                                                                    class="btn btn-warning"
                                                                    data-bs-toggle="tooltip" 
                                                                    data-bs-placement="bottom" 
                                                                    data-bs-title="Güncelle"
                                                                >
                                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                                </a>
                                                                <a href="/index.php?url=stok_alt_kalem_db_islem&islem=stok_alt_kalem_sil&id=<?php echo $stok_alt_kalem['id']; ?>&stok_id=<?php echo $stok_alt_kalem['stok_id']; ?>" 
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
                                                    </th>
                                                </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php  
        include_once "include/uyari_session_oldur.php";
    ?>
    
    <style>
        .stok-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .stok-row:hover {
            background-color: #f8f9fa !important;
        }
    </style>
    
    <script>
        $(function(){
            $(".stok-alt-kalem-form").submit(function(){
                $(".stok-alt-kalem-button").addClass('disabled');
                return true;
            });
            
            // Satıra tıklandığında değerler sayfasına git
            $(document).on('click', '.stok-row', function(e) {
                // Eğer tıklanan element buton veya link değilse
                if (!$(e.target).closest('a, button, .btn-group').length) {
                    const stokAltKalemId = $(this).data('stok-alt-kalem-id');
                    const stokId = $(this).data('stok-id');
                    window.location.href = `/index.php?url=stok_alt_depolar&stok_alt_kalem_id=${stokAltKalemId}&stok_id=${stokId}`;
                }
            });
        });
    </script> 