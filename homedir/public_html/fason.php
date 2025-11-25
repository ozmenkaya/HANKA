<?php
    require_once "include/oturum_kontrol.php";
?>
        <div class="row">
            <?php require_once "include/uyari_session.php";?>
            <div class="card mt-2">
                <div class="card-header">
                    <h5>
                        <i class="fa-regular fa-object-group"></i> Fasona Gidecekler ve Gidenler
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
                                    <th class="text-end">Ü. Miktar</th>
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
                                    WHERE planlama.firma_id = :firma_id AND  planlama.durum IN("baslamadi","basladi","beklemede","fasonda") ');
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->execute();
                                    $planlanmalar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    $fason_datalar = [];
                                    //print_r($planlanmalar);
                                ?>
                                <?php foreach ($planlanmalar as $planlama_index => $planlanma) { 
                                        $fasonlar   = json_decode($planlanma['fason_durumlar'], true); 
                                        $fason      = isset($fasonlar[$planlanma['mevcut_asama']]) ? $fasonlar[$planlanma['mevcut_asama']] : 0;
                                        
                                        $departmanlar = json_decode($planlanma['departmanlar'], true);
                                        $departman_id = isset( $departmanlar[$planlanma['mevcut_asama']]) ? 
                                                        $departmanlar[$planlanma['mevcut_asama']] : 0;

                                        $tedarikciler = json_decode($planlanma['fason_tedarikciler'], true);
                                        $tedarikci_id = isset($tedarikciler[$planlanma['mevcut_asama']]) ? 
                                                        $tedarikciler[$planlanma['mevcut_asama']] : 0;

                                        
                                        //echo $adet;
                                        //echo $fason;
                                        //print_r($planlanma);
                                        if($fason == 1){
                                            $sth = $conn->prepare('SELECT id,departman FROM `departmanlar` WHERE `departmanlar`.`id` = :id');
                                            $sth->bindParam('id', $departman_id);
                                            $sth->execute();
                                            $departman = $sth->fetch(PDO::FETCH_ASSOC); 
                                            $planlanma['departman'] = $departman;

                                            $sth = $conn->prepare('SELECT firma_adi FROM `tedarikciler` WHERE id = :id');
                                            $sth->bindParam('id', $tedarikci_id);
                                            $sth->execute();
                                            $tedarikci = $sth->fetch(PDO::FETCH_ASSOC); 
                                            $planlanma['tedarikci'] = $tedarikci;

                                            $adetler    = json_decode($planlanma['adetler'], true);
                                            $adet       = $adetler[$planlanma['mevcut_asama']];
                                            $planlanma['adet'] = $adet;

                                            $fason_datalar[] = $planlanma;

                                        }
                                } ?>

                                <?php 
                                    //echo "<pre>";
                                    //print_r($fason_datalar);
                                ?>

                                <?php foreach ($fason_datalar  as $fason_index => $fason_data) { ?>
                                    <tr>
                                        <th class="table-primary"><?php echo $fason_index+1; ?></th>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm fw-bold text-decoration-underline siparis-detay" 
                                                data-bs-toggle="tooltip" data-bs-placement="bottom" 
                                                data-bs-html="true" data-bs-custom-class="custom-tooltip" 
                                                data-bs-title="<b><i class='fa-regular fa-rectangle-list'></i> Sipariş Detayları</b>"
                                                data-planlama-id="<?php echo $fason_data['id']?>"
                                            >                                                 
                                                <?php echo $fason_data['siparis_no']; ?>
                                            </button>
                                        </td>
                                        <td><?php echo $fason_data['marka']; ?></td>
                                        <td><?php echo $fason_data['isin_adi']; ?></td>
                                        <td><?php echo $fason_data['isim']; ?></td>
                                        <td><?php echo $fason_data['departman']['departman'];?></td>
                                        <td><?php echo isset($fason_data['tedarikci']['firma_adi']) ? $fason_data['tedarikci']['firma_adi'] : '-'; ?></td>
                                        <td class="text-end">
                                            <?php echo number_format($fason_data['adet']); ?> <?php echo $fason_data['birim_ad']; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $sth = $conn->prepare('SELECT id,durum FROM uretim_fason_durum_loglar 
                                                WHERE planlama_id = :planlama_id AND departman_id = :departman_id AND mevcut_asama = :mevcut_asama ORDER BY id DESC');
                                                $sth->bindParam('planlama_id', $fason_data['id']);
                                                $sth->bindParam('departman_id', $fason_data['departman']['id']);
                                                $sth->bindParam('mevcut_asama', $fason_data['mevcut_asama']);
                                                $sth->execute();
                                                $fason_islem = $sth->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                            <div class="d-flex justify-content-end">
                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                    <?php if(empty($fason_islem) || $fason_islem['durum'] == 'iptal'){ ?>
                                                        <button class="btn btn-success planlama-stok-getir" 
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="İşi Fasona Gönder"
                                                            data-planlama-id="<?php echo $fason_data['id'];?>"
                                                        >
                                                            <i class="fa-solid fa-play"></i>
                                                        </button>
                                                    <?php }else{?> 
                                                        <button  class="btn btn-warning planlama-arsiv-stok-getir-fason-iptal" 
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-title="Fasonu İptal Et"
                                                            data-planlama-id="<?php echo $fason_data['id'];?>"
                                                        >
                                                            <i class="fa-solid fa-backward-step"></i>
                                                        </button>
                                                        <button class="btn btn-success planlama-arsiv-stok-getir-fasondan-geldi"
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="bottom" 
                                                                data-bs-title="Fasondan Geldi" 
                                                                data-planlama-id="<?php echo $fason_data['id'];?>">
                                                            <i class="fa-solid fa-stop"></i>
                                                        </button>

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
        </div>

        <!-- Sipariş Detay Modal -->
        <div class="modal fade" id="siparis-detay-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-bag-shopping"></i> Sipariş Detay
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="siparis-detay-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fasona Gönderilecek Stok ve Arşivler -->
        <div class="modal fade" id="fason-stok-arsiv-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="fason-modal-title">
                            <i class="fa-regular fa-object-group"></i> Fasona Gönderilecek Stok ve Arşivler
                        </h1>
                    </div>
                    <div class="modal-body" id="fason-stok-arsiv-modal-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fasona İptal İçin Stok ve Arşivler -->
        <div class="modal fade" id="fason-iptal-stok-arsiv-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="fason-modal-title">
                            <i class="fa-regular fa-object-group"></i> İptal Durumunda Fasondan Gelen Stok ve Arşivler
                        </h1>
                    </div>
                    <div class="modal-body" id="fason-iptal-stok-arsiv-modal-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fason Geldi İçin Stok ve Arşivler -->
        <div class="modal fade" id="fasondan-geldi-stok-arsiv-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">
                            <i class="fa-regular fa-object-group"></i> Fasondan Gelme Stok ve Arşivler
                        </h1>
                    </div>
                    <div class="modal-body" id="fason-geldi-stok-arsiv-modal-body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-regular fa-rectangle-xmark"></i> KAPAT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php 
            include_once "include/uyari_session_oldur.php"; 
        ?>

        <script>
            //planlama detay
            $(".siparis-detay").click(function(){
                const planlamaId = $(this).data('planlama-id');
                $.ajax({
                    url         : '/index.php?url=uretim_kontrol_db_islem&islem=siparis-detay', 
                    dataType    : 'JSON', 
                    data        : {planlama_id:planlamaId},
                    type        : 'POST',
                    success     : function (data) {
                        let resimlerHTML = '', formHTML = '';
                        data.siparis_dosyalar.forEach((dosya) => {
                            resimlerHTML += `
                                <a class="text-decoration-none example-image-link" 
                                    href="dosyalar/siparisler/${dosya.ad}" 
                                    data-lightbox="example-set" data-title=""
                                >
                                    <img src="dosyalar/siparisler/${dosya.ad}" 
                                        class="rounded img-thumbnai border border-secondary-subtle object-fit-fill mb-1 mt-1" 
                                        style="height:50px; min-height:50px; width:50px;"
                                    >
                                </a>
                            `;
                        });
                        let veriler;
                        if(data.planlama.tip_id == 1){
                            veriler = JSON.parse(data.planlama.veriler);
                        }else if(data.planlama.tip_id == 2 || data.planlama.tip_id == 3){
                            veriler = JSON.parse(data.planlama.veriler)[data.planlama.alt_urun_id-1];
                        }

                        if(veriler.form){
                            for(const [key, value] of Object.entries(veriler.form)){
                                if(value != ''){
                                    formHTML += `
                                        <li class="list-group-item list-group-item-warning"><b>${key}:</b> ${value}</li>
                                    `;
                                }
                                
                            }
                        }
                        

                        $("#siparis-detay-body").html(`
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <ul class="list-group">
                                        <li class="list-group-item active fw-bold" aria-current="true">Sipariş Detay</li>
                                        <li class="list-group-item"><b>Türü   : </b> ${data.planlama.tur}</li>
                                        <li class="list-group-item"><b>Ü.Adet : </b> ${data.planlama.uretilecek_adet}</li>
                                        <li class="list-group-item list-group-item-primary"><b>Teslimat Adresi : </b> ${data.planlama.teslimat_adresi}</li>
                                        <li class="list-group-item list-group-item-primary"><b>Teslimat Ülkesi : </b> ${data.planlama.ulke_adi}</li>
                                        <li class="list-group-item list-group-item-primary"><b>Teslimat Şehri : </b> ${data.planlama.sehir_adi}</li>
                                        <li class="list-group-item list-group-item-primary"><b>Teslimat İlçesi : </b> ${data.planlama.ilce_adi}</li>
                                        <li class="list-group-item"><b>Termin Tarihi : </b> ${data.planlama.termin}</li>
                                        <li class="list-group-item"><b>Üretim Tarihi : </b> ${data.planlama.uretim}</li>
                                        <li class="list-group-item"><b>M. Temsilcisi : </b> ${data.planlama.ad} ${data.planlama.soyad}</li>
                                        <li class="list-group-item"><b>Müşteri  : </b> ${data.planlama.marka} / ${data.planlama.firma_unvani}</li>
                                        <li class="list-group-item"><b>Vade Tarihi  : </b> ${data.planlama.vade}</li>
                                        <li class="list-group-item"><b>Fiyat  : </b> ${data.planlama.fiyat} ${data.planlama.para_cinsi}</li>
                                        <li class="list-group-item"><b>Ödeme Şekli  : </b> ${data.planlama.odeme_sekli}</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-group">
                                        <li class="list-group-item list-group-item-success fw-bold" aria-current="true">${data.planlama.alt_urun_id}. Alt Ürün</li>
                                        <li class="list-group-item"><b>İsim         : </b> ${veriler.isim}</li>
                                        <li class="list-group-item"><b>Miktar       : </b> ${veriler.miktar}</li>
                                        <li class="list-group-item"><b>Birim Fiyat  : </b> ${veriler.birim_fiyat} ${data.planlama.para_cinsi}</li>
                                        <li class="list-group-item"><b>KDV          : </b> ${veriler.kdv}</li>
                                        <li class="list-group-item"><b>Numune       : </b> ${veriler.numune == 1 ? '<span class="badge text-bg-success">VAR</span>':'<span class="badge text-bg-danger">YOK</span>'}</li>
                                        <li class="list-group-item"><b>Açıklama     : </b> ${veriler.aciklama}</li>
                                        ${formHTML}
                                    </ul>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h5>
                                        <i class="fa-regular fa-image"></i> Sipariş Dosyaları
                                    </h5>
                                </div>
                                <div class="card-body">
                                    ${resimlerHTML}
                                </div>
                            </div>
                        `);
                        $("#siparis-detay-modal").modal('show');
                    },
                    error: function (response) {
                        $.notify("Veri Çekme Başarısız(Error)","error");
                    }
                });
            });

            $(document).on('change', '.arsiv', function(){
                if($(this).is(':checked'))  $(this).val(1); 
                else                        $(this).val(0); 
            });

            //Fason Geldi İçin Stok ve Arşivleri Getirme
            $(".planlama-arsiv-stok-getir-fasondan-geldi").click(function(){
                const planlama_id= $(this).data('planlama-id');
                $.ajax({
                    url         : "/index.php?url=fason_db_islem",
                    dataType    : "JSON",
                    type        : "POST",
                    data        : {planlama_id ,islem:'planlama-stok-arsiv-getir'},
                    success     : function(data){
                        let fasonHTML = '', stok_alt_kalem_veri;
                        for(let i = 0; i < data.stok_veriler.length; i++){
                            stok_alt_kalem_veri = JSON.parse(data.stok_veriler[i].stok_alt_kalem.veri);
                            
                            fasonHTML += `
                                <div class="text-danger fw-bold text-decoration-underline">
                                    <i class="fa-sharp fa-solid fa-layer-group"></i>
                                    ${i+1} - 
                                    ${data.stok_veriler[i].stok_kalem.stok_kalem}  
                                    ${Object.keys(stok_alt_kalem_veri).join('/')} - ${Object.values(stok_alt_kalem_veri).join('/')}
                                    <span class="text-primary">Stok Kodu: ${data.stok_veriler[i].stok_alt_depo.stok_kodu}</span>
                                </div>
                                <div class="form-floating col-md-12">
                                    <input type="hidden"  name="stok_id[]" value="${data.stok_veriler[i].stok_kalem.id}">
                                    <input type="hidden"  name="stok_alt_kalem_id[]" value="${data.stok_veriler[i].stok_alt_kalem.id}">
                                    <input type="hidden"  name="stok_alt_depo_id[]" value="${data.stok_veriler[i].stok_alt_depo.id}">
                                    <input type="hidden"  name="birim_id[]" value="${data.stok_veriler[i].stok_alt_depo.birim_id}">

                                    <input type="number" min="0" name="gelen_adet[]" id="gelen-adet-${i}" 
                                        class="form-control"  required value="0">
                                    <label for="gelen-adet-${i}" class="form-label fw-bold">
                                        Fasondan Gelen Stok Miktarı (${data.stok_veriler[i].stok_alt_depo.birim_ad})
                                    </label>
                                </div>
                            `;
                        }

                        let arsivHTML = '';
                        data.arsiv_kalemler.forEach((arsiv_kalem, index)=>{
                            arsivHTML += `
                                <input type="hidden" name="arsiv_alt_id[]" value="${arsiv_kalem.id}">
                                <li class="list-group-item">
                                    <div class="form-check">
                                        <input name="arsivden_gelme_durumu[]"  class="form-check-input arsiv" type="checkbox" value="0" id="arsiv-${index+1}"> 
                                        <div class="text-danger text-decoration-underline fw-bold mb-2">(Geldi Mi?)</div>
                                        <label class="form-check-label" for="arsiv-${index+1}">
                                            <b class="text-danger">
                                                <i class="fa-regular fa-folder-open"></i>
                                                ${index+1}. ARŞİV -
                                            </b> 
                                            <b>Kod:</b>${arsiv_kalem.kod} <b>Ebat:</b>${arsiv_kalem.ebat} <b>Detay:</b>${arsiv_kalem.detay}
                                        </label>
                                    </div>
                                </li>
                            `;
                        });

                        if(arsivHTML == ''){
                            arsivHTML = `<li class="list-group-item fw-bold text-danger">
                                <i class="fa-solid fa-circle-exclamation"></i> Fasona Gönderilecek Arşiv Yok.
                            </li>`;
                        }

                        if(fasonHTML == ''){
                            fasonHTML = `
                                <ul class="list-group ms-2">
                                    <li class="list-group-item active fw-bold" aria-current="true">
                                        <i class="fa-sharp fa-solid fa-layer-group"></i> Göndericek Stoklar
                                    </li>
                                    <li class="list-group-item fw-bold text-danger" aria-current="true">
                                        <i class="fa-solid fa-circle-exclamation"></i> Fasona Gönderilecek Stok Yok(Sadece Ürünün Kendisi Gönderilecek).
                                    </li>
                                </ul>
                            `;
                        }


                        $("#fason-geldi-stok-arsiv-modal-body").html(`
                            <ul class="list-group mb-2">
                                <li class="list-group-item"><b>Müşteri: </b>${data.planlama.marka}</li>
                                <li class="list-group-item"><b>Ürün Adı: </b>${data.planlama.isin_adi}</li>
                            </ul>
                            <form action="/index.php?url=fason_db_islem" method="POST" class="row g-3 needs-validation">
                                <input type="hidden" name="planlama_id" value="${data.planlama.id}">
                                <input type="hidden" name="siparis_id" value="${data.planlama.siparis_id}">
                                <input type="hidden" name="departman_id" value="${data.departman_id}">
                                <input type="hidden" name="mevcut_asama" value="${data.planlama.mevcut_asama}">
                                <input type="hidden" name="asama_sayisi" value="${data.planlama.asama_sayisi}">
                                <input type="hidden" name="grup_kodu" value="${data.planlama.grup_kodu}">
                                <div class="form-floating col-md-6">
                                    <input type="number" min="0" name="uretilen_adet" id="uretilen_adet" 
                                        class="form-control"  required >
                                    <label for="uretilen_adet" class="form-label fw-bold">
                                        Fasondan Üretilen Adet 
                                    </label>
                                </div>
                                <div class="form-floating col-md-6">
                                    <input type="number" min="0" name="uretirken_verilen_fire_adet" id="uretirken_verilen_fire_adet" 
                                        class="form-control"  required >
                                    <label for="uretirken_verilen_fire_adet" class="form-label fw-bold">
                                        Fasondan Verilen Fire 
                                    </label>
                                </div>
                                ${fasonHTML}
                                <ul class="list-group ms-2">
                                    <li class="list-group-item active fw-bold" aria-current="true">
                                        <i class="fa-regular fa-folder-open"></i>  Gelen Arşivler
                                    </li>
                                    ${arsivHTML}
                                </ul>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success" name="fasondan-geldi">
                                        <i class="fa-solid fa-backward-fast"></i> FASONDA GELDİ
                                    </button>
                                </div>
                            </form>
                        `);

                        $("#fasondan-geldi-stok-arsiv-modal").modal('show');
                    },
                    error: function (response) {
                        $.notify("Veri Çekme Başarısız(Error)","error");
                    }
                });
            });

            //Fason İptal İçin Stok ve Arşivleri Getirme
            $(".planlama-arsiv-stok-getir-fason-iptal").click(function(){
                const planlama_id= $(this).data('planlama-id');
                $.ajax({
                    url         : "/index.php?url=fason_db_islem",
                    dataType    : "JSON",
                    type        : "POST",
                    data        : {planlama_id ,islem:'planlama-stok-arsiv-getir'},
                    success     : function(data){
                        let fasonHTML = '', stok_alt_kalem_veri;
                        for(let i = 0; i < data.stok_veriler.length; i++){
                            stok_alt_kalem_veri = JSON.parse(data.stok_veriler[i].stok_alt_kalem.veri);
                            
                            log(stok_alt_kalem_veri)
                            fasonHTML += `
                                <div class="text-danger fw-bold text-decoration-underline">
                                    <i class="fa-sharp fa-solid fa-layer-group"></i>
                                    ${i+1} - 
                                    ${data.stok_veriler[i].stok_kalem.stok_kalem}  
                                    ${Object.keys(stok_alt_kalem_veri).join('/')} - ${Object.values(stok_alt_kalem_veri).join('/')}
                                    <span class="text-primary">Stok Kodu: ${data.stok_veriler[i].stok_alt_depo.stok_kodu}</span>
                                </div>
                                <div class="form-floating col-md-12">
                                    <input type="hidden"  name="stok_id[]" value="${data.stok_veriler[i].stok_kalem.id}">
                                    <input type="hidden"  name="stok_alt_kalem_id[]" value="${data.stok_veriler[i].stok_alt_kalem.id}">
                                    <input type="hidden"  name="stok_alt_depo_id[]" value="${data.stok_veriler[i].stok_alt_depo.id}">
                                    <input type="hidden"  name="birim_id[]" value="${data.stok_veriler[i].stok_alt_depo.birim_id}">

                                    <input type="number" min="0" name="gelen_adet[]" id="gelen_adet-${i}" 
                                        class="form-control"  required value="${data.stok_veriler[i].stok_alt_depo.adet}">
                                    <label for="gelen_adet-${i}" class="form-label fw-bold">
                                        Fasondan Gelen Stok Miktarı (${data.stok_veriler[i].stok_alt_depo.birim_ad})
                                    </label>
                                </div>
                            `;
                        }

                        let arsivHTML = '';
                        data.arsiv_kalemler.forEach((arsiv_kalem, index)=>{
                            arsivHTML += `
                                <input type="hidden" name="arsiv_alt_id[]" value="${arsiv_kalem.id}">
                                <li class="list-group-item">
                                    <div class="form-check">
                                        <input name="arsivden_gelme_durumu[]"  class="form-check-input arsiv" type="checkbox" value="0" id="arsiv-${index+1}"> 
                                        <div class="text-danger text-decoration-underline fw-bold mb-2">(Geldi Mi?)</div>
                                        <label class="form-check-label" for="arsiv-${index+1}">
                                            <b class="text-danger">
                                                <i class="fa-regular fa-folder-open"></i>
                                                ${index+1}. ARŞİV -
                                            </b> 
                                            <b>Kod:</b>${arsiv_kalem.kod} <b>Ebat:</b>${arsiv_kalem.ebat} <b>Detay:</b>${arsiv_kalem.detay}
                                        </label>
                                    </div>
                                </li>
                            `;
                        });

                        if(arsivHTML == ''){
                            arsivHTML = `<li class="list-group-item fw-bold text-danger">
                                <i class="fa-solid fa-circle-exclamation"></i> Fasona Gönderilecek Arşiv Yok.
                            </li>`;
                        }

                        if(fasonHTML == ''){
                            fasonHTML = `
                                <ul class="list-group ms-2">
                                    <li class="list-group-item active fw-bold" aria-current="true">
                                        <i class="fa-sharp fa-solid fa-layer-group"></i> Göndericek Stoklar
                                    </li>
                                    <li class="list-group-item fw-bold text-danger" aria-current="true">
                                        <i class="fa-solid fa-circle-exclamation"></i> Fasona Gönderilecek Stok Yok(Sadece Ürünün Kendisi Gönderilecek).
                                    </li>
                                </ul>
                            `;
                        }
                        let tedarikciHTML = '';
                        data.tedarikciler.forEach((tedarikci, index) => {
                            tedarikciHTML += `<option value="${tedarikci.id}" class="fw-bold">
                                ${index+1}- ${tedarikci.firma_adi}
                            </option>`;
                        });

                        $("#fason-iptal-stok-arsiv-modal-body").html(`
                            <ul class="list-group mb-2">
                                <li class="list-group-item"><b>Müşteri: </b>${data.planlama.marka}</li>
                                <li class="list-group-item"><b>Ürün Adı: </b>${data.planlama.isin_adi}</li>
                            </ul>
                            <form action="/index.php?url=fason_db_islem" method="POST" class="row g-3 needs-validation">
                                <input type="hidden" name="planlama_id" value="${data.planlama.id}">
                                <input type="hidden" name="departman_id" value="${data.departman_id}">
                                <input type="hidden" name="mevcut_asama" value="${data.planlama.mevcut_asama}">
                                ${fasonHTML}
                                <ul class="list-group ms-2">
                                    <li class="list-group-item active fw-bold" aria-current="true">
                                        <i class="fa-regular fa-folder-open"></i> Gelen Arşivler
                                    </li>
                                    ${arsivHTML}
                                </ul>
                                <div class="form-floating">
                                    <textarea class="form-control" name="iptal_sebebi" id="iptal_sebebi" style="height:100px" required></textarea>
                                    <label for="iptal_sebebi">İptal Sebebi</label>
                                </div>
                                <div class="form-floating">
                                    <select class="form-select" name="fason_id" id="fason" required>
                                        <option class="fw-bold" selected value="">Seçiniz</option>
                                        ${tedarikciHTML}
                                    </select>
                                    <label for="fason">Yeni Fasoncu</label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-danger" name="fason-iptal">
                                        <i class="fa-regular fa-circle-xmark"></i> İPTAL ET
                                    </button>
                                </div>
                            </form>
                        `);

                        $("#fason-iptal-stok-arsiv-modal").modal('show');
                    },
                    error: function (response) {
                        $.notify("Veri Çekme Başarısız(Error)","error");
                    }
                });
            });

            //planlama stokları getir
            $(".planlama-stok-getir").click(function(){
                const planlama_id= $(this).data('planlama-id');
                $.ajax({
                    url         : "/index.php?url=fason_db_islem",
                    dataType    : "JSON",
                    type        : "POST",
                    data        : {planlama_id ,islem:'planlama-stok-arsiv-getir'},
                    success     : function(data){
                        //log(data)
                        let fasonHTML = '', stok_alt_kalem_veri;
                        for(let i = 0; i < data.stok_veriler.length; i++){
                            stok_alt_kalem_veri = JSON.parse(data.stok_veriler[i].stok_alt_kalem.veri);
                            
                            fasonHTML += `
                                <div class="text-danger fw-bold text-decoration-underline">
                                    <i class="fa-sharp fa-solid fa-layer-group"></i>
                                    ${i+1} - 
                                    ${data.stok_veriler[i].stok_kalem.stok_kalem}  
                                    ${Object.keys(stok_alt_kalem_veri).join('/')} - ${Object.values(stok_alt_kalem_veri).join('/')}
                                    <span class="text-primary">Stok Kodu: ${data.stok_veriler[i].stok_alt_depo.stok_kodu}</span>
                                </div>
                                <div class="form-floating col-md-12">
                                    <input type="hidden"  name="stok_id[]" value="${data.stok_veriler[i].stok_kalem.id}">
                                    <input type="hidden"  name="stok_alt_kalem_id[]" value="${data.stok_veriler[i].stok_alt_kalem.id}">
                                    <input type="hidden"  name="stok_alt_depo_id[]" value="${data.stok_veriler[i].stok_alt_depo.id}">
                                    <input type="hidden"  name="birim_id[]" value="${data.stok_veriler[i].stok_alt_depo.birim_id}">

                                    <input type="number" min="0" name="tuketim_miktari[]" id="tuketim_miktari-${i}" 
                                        class="form-control"  required value="${data.stok_veriler[i].stok_alt_depo.adet}">
                                    <label for="tuketim_miktari-${i}" class="form-label fw-bold">
                                        Fasona Verilecek Stok Miktarı (${data.stok_veriler[i].stok_alt_depo.birim_ad})
                                    </label>
                                </div>
                            `;
                        }

                        let arsivHTML = '';
                        data.arsiv_kalemler.forEach((arsiv_kalem, index)=>{
                            arsivHTML += `
                                <input type="hidden" name="arsiv_alt_id[]" value="${arsiv_kalem.id}">
                                <li class="list-group-item">
                                    <b class="text-danger">
                                        <i class="fa-regular fa-folder-open"></i>
                                        ${index+1}. ARŞİV -
                                    </b> 
                                    <b>Kod:</b>${arsiv_kalem.kod} <b>Ebat:</b>${arsiv_kalem.ebat} <b>Detay:</b>${arsiv_kalem.detay}
                                </li>
                            `;
                        });

                        if(arsivHTML == ''){
                            arsivHTML = `<li class="list-group-item fw-bold text-danger">
                                <i class="fa-solid fa-circle-exclamation"></i> Fasona Gönderilecek Arşiv Yok.
                            </li>`;
                        }

                        if(fasonHTML == ''){
                            fasonHTML = `
                                <ul class="list-group ms-2">
                                    <li class="list-group-item active fw-bold" aria-current="true">
                                        <i class="fa-sharp fa-solid fa-layer-group"></i> Göndericek Stoklar
                                    </li>
                                    <li class="list-group-item fw-bold text-danger" aria-current="true">
                                        <i class="fa-solid fa-circle-exclamation"></i> Fasona Gönderilecek Stok Yok(Sadece Ürünün Kendisi Gönderilecek).
                                    </li>
                                </ul>
                            `;
                        }
                        
                        $("#fason-stok-arsiv-modal-body").html(`
                            <ul class="list-group mb-2">
                                <li class="list-group-item"><b>Müşteri: </b>${data.planlama.marka}</li>
                                <li class="list-group-item"><b>Ürün Adı: </b>${data.planlama.isin_adi}</li>
                            </ul>
                            <form action="/index.php?url=fason_db_islem" method="POST" class="row g-3 needs-validation">
                                <input type="hidden" name="planlama_id" value="${data.planlama.id}">
                                <input type="hidden" name="departman_id" value="${data.departman_id}">
                                <input type="hidden" name="mevcut_asama" value="${data.planlama.mevcut_asama}">
                                ${fasonHTML}
                                <ul class="list-group ms-2">
                                    <li class="list-group-item active fw-bold" aria-current="true">
                                        <i class="fa-regular fa-folder-open"></i> Göndericek Arşivler
                                    </li>
                                    ${arsivHTML}
                                </ul>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success" name="fasona-gonder">
                                        <i class="fa-regular fa-paper-plane"></i> GÖNDER
                                    </button>
                                </div>
                            </form>
                        `);
                        $("#fason-stok-arsiv-modal").modal('show');
                    },
                    error: function (response) {
                        $.notify("Veri Çekme Başarısız(Error)","error");
                    }
                });
            });
        </script>