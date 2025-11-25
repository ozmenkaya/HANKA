<?php
    require_once "include/oturum_kontrol.php";
?>
<div class="row mt-2">
    <?php require_once "include/uyari_session.php";?>
    <div class="card">
        <div class="card-header">
            <h5>
                <i class="fa-regular fa-object-group"></i> Fasonlar
            </h5>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table id="myTable" class="table table-hover" >
                    <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Sipariş Kodu</th>
                        <th>Müşteri</th>
                        <th>Ürün Adı</th>
                        <th>Alt Ürün</th>
                        <th>Departman</th>
                        <th>Tedarikçi</th>
                        <th class="text-end">Miktar</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $sth = $conn->prepare('SELECT planlama.id, planlama.isim,planlama.mevcut_asama, planlama.departmanlar, `planlama`.`arsiv_altlar`,
                            `planlama`.`adetler`,`planlama`.`fason_durumlar`, `planlama`.`fason_tedarikciler`, `planlama`.`stok_alt_kalemler`, `planlama`.`stok_alt_depo_adetler`,
                            siparisler.isin_adi, siparisler.siparis_no,
                            musteri.marka,
                            birimler.ad  AS birim_ad
                            FROM planlama 
                            JOIN siparisler ON siparisler.id = planlama.siparis_id
                            JOIN musteri ON musteri.id = `siparisler`.`musteri_id` 
                            JOIN birimler ON birimler.id = `siparisler`.`birim_id` 
                            WHERE planlama.firma_id = :firma_id AND  planlama.durum != "bitti" ');
                            $sth->bindParam('firma_id', $_SESSION['firma_id']);
                            $sth->execute();
                            $planlanmalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                            $sira = 0;
                        ?>
                        <?php foreach ($planlanmalar as $key => $planlanma) { ?>
                            <?php 
                                $fasonlar = json_decode($planlanma['fason_durumlar'], true); 
                                $fason  = isset($fasonlar[$planlanma['mevcut_asama']]) ? $fasonlar[$planlanma['mevcut_asama']] : 0;
                            ?>
                            <?php if(in_array(1,$fasonlar)){ ?>
                                <?php 
                                    $adetler = json_decode($planlanma['adetler'], true);
                                    $adet = $adetler[$planlanma['mevcut_asama']];

                                    $departmanlar = json_decode($planlanma['departmanlar'], true);
                                    $departman_id = $departmanlar[$planlanma['mevcut_asama']];

                                    $sth = $conn->prepare('SELECT departman FROM `departmanlar` WHERE `departmanlar`.`id` = :id');
                                    $sth->bindParam('id', $departman_id);
                                    $sth->execute();
                                    $departman = $sth->fetch(PDO::FETCH_ASSOC);   
                                    
                                    $tedarikciler = json_decode($planlanma['fason_tedarikciler'], true);
                                    $tedarikci_id = $tedarikciler[$planlanma['mevcut_asama']];
                                    if($tedarikci_id != 0){
                                        $sth = $conn->prepare('SELECT firma_adi FROM `tedarikciler` WHERE id = :id');
                                        $sth->bindParam('id', $tedarikci_id);
                                        $sth->execute();
                                        $tedarikci = $sth->fetch(PDO::FETCH_ASSOC); 
                                    }
                                    else{
                                        $tedarikci['firma_adi'] = '-';
                                    }

                                    $stok_alt_kalemler = json_decode($planlanma['stok_alt_kalemler'], true);
                                    $stok_kalem_adetler_idler = $stok_alt_kalemler[$planlanma['mevcut_asama']];
                                    
                                ?>
                                <tr class="<?php echo $fason == 1 ? 'table-success':'';?>">
                                    <th><?php echo ($sira + 1);?></th>
                                    <td><?php echo $planlanma['siparis_no'];?></td>
                                    <td><?php echo $planlanma['marka']; ?></td>
                                    <td><?php echo $planlanma['isin_adi']; ?></td>
                                    <td><?php echo $planlanma['isim']; ?></td>
                                    <td><?php echo $departman['departman'];?></td>
                                    <td><?php echo $tedarikci['firma_adi']; ?> </td>
                                    <td class="text-end"><?php echo number_format($adet, 0, '',','); ?> <?php echo $planlanma['birim_ad']; ?></td>
                                    <?php if($fason == 1){ ?>
                                        <?php 
                                            $sth = $conn->prepare('SELECT id FROM planlama_fason_durumlar 
                                            WHERE planlama_id = :planlama_id AND departman_id = :departman_id AND asama = :asama');
                                            $sth->bindParam('planlama_id', $planlanma['id']);
                                            $sth->bindParam('departman_id', $departman_id);
                                            $sth->bindParam('asama', $planlanma['mevcut_asama']);
                                            $sth->execute();
                                            $fason_islem = $sth->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end">
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <?php if(empty($fason_islem)){ ?>
                                                        <a class="btn btn-success fason-stok-tuketim" 
                                                            onClick="return confirm('İş Tedarikçiye Gönderilsin Mi?')"
                                                            href="/index.php?url=fason_db_islem&islem=fasona-gitti&planlama-id=<?php echo $planlanma['id'];?>&departman-id=<?php echo $departman_id;?>&asama=<?php echo $planlanma['mevcut_asama']; ?>">
                                                            <i class="fa-solid fa-play"></i>
                                                        </a>
                                                    <?php }else{ ?>
                                                        <button class="btn btn-danger fason-stok-tuketim" data-id="<?php echo $planlanma['id'];?>">
                                                            <i class="fa-solid fa-stop"></i>
                                                        </button>
                                                        <a class="btn  btn-warning" 
                                                            onClick="return confirm('İşlemi İptal Etmek İstediğinize Emin Misiniz?')"
                                                            href="/index.php?url=fason_db_islem&islem=fason-geri-al&planlama-id=<?php echo $planlanma['id'];?>&departman-id=<?php echo $departman_id;?>&asama=<?php echo $planlanma['mevcut_asama']; ?>">
                                                            <i class="fa-solid fa-backward-step"></i>
                                                        </a>
                                                    <?php }?>
                                                    <button class="btn btn-primary planlama-detay"  
                                                        data-planlama-id=<?php echo $planlanma['id'];?>
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Planlama Detay"
                                                    >
                                                        <i class="fa-regular fa-rectangle-list"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    <?php }else{?> 
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end">
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <button class="btn btn-primary planlama-detay"  
                                                        data-planlama-id=<?php echo $planlanma['id'];?>
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Planlama Detay"
                                                    >
                                                        <i class="fa-regular fa-rectangle-list"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    <?php } ?>
                                </tr>
                                <?php $sira++; ?>
                            <?php } ?>
                        <?php } // foreach?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php 
    include_once "include/uyari_session_oldur.php"; 
?>

<!-- Stok kalemde tüketilenler -->
<div class="modal fade" id="fason-stok-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="fason-modal-title"></h1>
            </div>
            <div class="modal-body">
                <form action="/index.php?url=fason_db_islem" method="POST" id="fasonlar-form"></form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">KAPAT</button>
            </div>
        </div>
    </div>
</div>

<!-- Fason İş Detay -->
<div class="modal fade" id="planlama-detay-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Fason İş Detay</h1>
            </div>
            <div class="modal-body" id="planlama-detay-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">KAPAT</button>
            </div>
        </div>
    </div>
</div>

<script>
    //planlama detay
    $(".planlama-detay").click(function(){
        const planlama_id = $(this).data('planlama-id');
        $.ajax({
            url         : "/index.php?url=fason_db_islem",
            dataType    : "JSON",
            type        : "POST",
            data        : {islem:'planlama-detay', planlama_id},
            success     : function(data){
                //console.log(data.stok_kalem_datalar);
                let planlamaHTML = `
                    <ul class="list-group">
                        <li class="list-group-item active" aria-current="true">Detaylar</li>
                `;
                planlamaHTML += `<li class="list-group-item"><b>Sipariş Kodu:</b> ${data.siparis_no}</li>`;
                planlamaHTML += `<li class="list-group-item"><b>Müşteri:</b> ${data.marka}</li>`;
                planlamaHTML += `<li class="list-group-item"><b>Ürün Adı:</b> ${data.isin_adi}</li>`;
                planlamaHTML += `<li class="list-group-item"><b>Alt Ürün:</b> ${data.isim}</li>`;
                planlamaHTML += `<li class="list-group-item"><b>Departman:</b> ${data.departman}</li>`;
                planlamaHTML += `<li class="list-group-item"><b>Tedarikçi: </b>${data.tedarikci}</li>`;
                planlamaHTML += `<li class="list-group-item"><b>Miktar: </b>${data.adet} ${data.birim_ad}</li>`;
                let detayHTML;
                for(const stok_kalem_data of data.stok_kalem_datalar){
                    detayHTML = '';
                    for(const [key, value] of Object.entries(JSON.parse(stok_kalem_data.veri))){
                        detayHTML += `${key} <span class="badge bg-secondary">${value}</span>`;
                    }
                    planlamaHTML += `<li class="list-group-item">Malzeme: <span class="badge bg-secondary">${stok_kalem_data.stok_kalem}</span> - 
                        Adet: <span class="badge bg-secondary">${stok_kalem_data.adet}</span> <br>
                        Detay: ${detayHTML}
                    </li>`;
                }

                planlamaHTML += `</ul>`;
                $("#planlama-detay-body").html(planlamaHTML);
                $("#planlama-detay-modal").modal('show');
            }
        })
    });

    $(".fason-stok-tuketim").click(function(){
        const id= $(this).data('id');
        $.ajax({
            url         : "/index.php?url=fason_db_islem",
            dataType    : "JSON",
            type        : "POST",
            data        : {islem:'planlama-stok_kalemleri_getir', id},
            success     : function(data){
                //console.log(data)
                $("#fason-modal-title").html(`
                    Sipariş Kodu: <span class="badge bg-secondary">${data.siparis_no}</span> - 
                    Müşteri: <span class="badge bg-secondary">${data.marka}</span> - 
                    Ürün Adı: <span class="badge bg-secondary">${data.isin_adi}</span>
                `);
                let fasonHTML = `
                    <input type="hidden" name="planlama_id" value="${id}">
                    <input type="hidden" name="departman_id" value="${data.departman_id}">
                    <input type="hidden" name="planlama_fason_durum_id" value="${data.planlama_fason_durum_id}">
                    <div class="row">
                        <div class="form-floating col-md-8">
                            <input type="number" min="1" name="uretilen_adet" class="form-control"  required>
                            <label for="uretilen_adet" class="form-label">Üretilen ${data.birim_ad}</label>
                        </div>
                        <div class="col-md-4 mt-2 text-danger fw-bold">
                            Üretilecek ${data.birim_ad}[${data.adet}]
                        </div>
                    </div>
                `;
                for(let i = 0; i < data.stok_kalemler.length; i++){
                    if(data.stok_kalemler[i] == 0) continue;
                    fasonHTML += `
                        <h6 class="text-danger mt-3">${data.stok_kalemleri[i].stok_kalem}  => Gönderilen Miktar: ${data.stok_kalem_adetler[i]} ${data.stok_kalemleri[i].ad}</h6>
                        <input type="hidden" name="stok_kalemler_birim_idler[]" value="${data.stok_kalemleri[i].birim_id}">
                        <input type="hidden" name="stok_alt_kalem_idler[]" value="${data.stok_kalemler[i]}">
                        <input type="hidden" name="stok_kalemler_adetler[]" value="${data.stok_kalem_adetler[i]}">
                    
                        <div class="row">
                            <div class="form-floating col-md-4">
                                <input type="number" min="0" name="tuketim_miktarlar[]" id="tuketim_miktarlar-${data.stok_kalemler[i]}" 
                                class="form-control"  required>
                                <label for="tuketim_miktarlar-${data.stok_kalemler[i]}" class="form-label">${data.stok_kalemleri[i].stok_kalem} ${data.stok_kalemleri[i].ad}</label>
                            </div>
                            <div class="form-floating col-md-4">
                                <input type="number" min="0" name="stok_kalemler_fireler[]" id="stok_kalemler_idler_fireler-${data.stok_kalemler[i]}" class="form-control" >
                                <label for="stok_kalemler_idler_fireler-${data.stok_kalemler[i]}" class="form-label">Fire ${data.stok_kalemleri[i].ad}</label>
                            </div>
                            <div class="form-floating col-md-4">
                                <input type="number" min="0" name="stok_kalemler_geri_gelenler[]" id="stok_kalemler_idler_geri_gelenler-${data.stok_kalemler[i]}" class="form-control" >
                                <label for="stok_kalemler_idler_geri_gelenler-${data.stok_kalemler[i]}" class="form-label">Geri Gelen Malzeme Miktar </label>
                            </div>
                        </div>
                    `;
                }

                fasonHTML += `
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-success" name="fason_asama_bitir">GÖNDER</button>
                    </div>
                `;
                $("#fasonlar-form").html(fasonHTML);
                $("#fason-stok-modal").modal('show');
            }
        });
    });
</script>