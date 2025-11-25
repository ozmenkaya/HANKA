<?php 
    include_once "include/oturum_kontrol.php";

    $sql = "SELECT * FROM kur";
    $sth = $conn->prepare($sql);
    $sth->execute();
    $kurlar = $sth->fetchAll(PDO::FETCH_ASSOC);

?> 
    <div class="row mt-2">
        <?php 
            
            $musteri_id  = isset($_GET['musteri_id']) ? intval($_GET['musteri_id']) : 0;
            $sth = $conn->prepare('SELECT id, marka FROM musteri WHERE id=:id');
            $sth->execute([':id' => $musteri_id]);
            $musteri = $sth->fetch(PDO::FETCH_ASSOC);

            if(empty($musteri))
            {
                require_once "include/yetkisiz.php"; exit;
            }

            $sql = 'SELECT id, siparis_no, isin_adi, termin, onay_baslangic_durum, islem, fiyat, para_cinsi, tarih 
            FROM siparisler 
            WHERE musteri_id = :id AND firma_id = :firma_id AND islem != "iptal"';

            if(isset($_GET['baslangic_tarihi']) && isset($_GET['bitis_tarihi'])){
                $baslangic_tarihi   = $_GET['baslangic_tarihi'];
                $bitis_tarihi       = $_GET['bitis_tarihi'];
                $sql .= " AND tarih >= '{$baslangic_tarihi} 00:00:00' AND  tarih <= '$bitis_tarihi 23:59:59' ";
            }
            $sql .= ' ORDER BY id DESC';
            $sth = $conn->prepare($sql);
            $sth->bindParam('id', $musteri_id);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $siparisler = $sth->fetchAll(PDO::FETCH_ASSOC);
            #echo "<pre>"; print_r($siparisler); exit;

            $uretimdeki_is_sayisi               = 0;
            $uretimdeki_is_sayisi_toplam_fiyat  = 0;

            $uretimdeki_tamamlanmis_is_sayisi   = 0;
            $uretimdeki_tamamlanmis_is_sayisi_toplam_fiyat   = 0;

            $teslim_edilen                      = 0;
            $teslim_edilen_toplam_fiyat         = 0;

            $toplam_is_tutari                   = 0;

            $para_birimi                        = '₺';
            
            foreach ($siparisler as $siparis) {
                $kur_data = ['dollar'=>1, 'euro'=>1, 'pound'=>1, 'tl'=>1];
                foreach ($kurlar as $kur) {
                    if($kur['tarih'] == date('Y-m-d', strtotime($siparis['tarih']))){
                        $kur_data = $kur;
                        $kur_data['tl'] = 1;
                        break;
                    }
                }
                $kur = 1;
                if($siparis['para_cinsi'] == 'TL') $kur = 1;
                else if($siparis['para_cinsi'] == 'DOLAR')  $kur = $kur_data['dollar'];
                else if($siparis['para_cinsi'] == 'EURO')   $kur = $kur_data['euro'];
                else if($siparis['para_cinsi'] == 'POUND')  $kur = $kur_data['pound'];


                if($siparis['islem'] == 'tamamlandi'){
                    $uretimdeki_tamamlanmis_is_sayisi++;
                    $uretimdeki_tamamlanmis_is_sayisi_toplam_fiyat += $siparis['fiyat']*$kur;
                }
                else if($siparis['islem'] == 'teslim_edildi'){
                    $teslim_edilen++;
                    $teslim_edilen_toplam_fiyat += $siparis['fiyat']*$kur;
                }
                else if($siparis['islem'] == 'islemde'){
                    $uretimdeki_is_sayisi_toplam_fiyat += $siparis['fiyat']*$kur;
                    $uretimdeki_is_sayisi++;
                } 

                $toplam_is_tutari += $siparis['fiyat']*$kur;
            }

                
        ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-solid fa-bag-shopping"></i>
                    <b><?php echo $musteri['marka']; ?></b> Sipariş Listesi
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
            <div class="card-body">
                <div class="row gx-3 d-flex align-items-stretch justify-content-between">
                    <div class="col-md-3 d-flex align-self-stretch">
                        <div class="p-3 text-white bg-primary rounded-3 flex-fill">
                            <h1 class="p-2"><?php echo count($siparisler); ?></h1>
                            <div class="row">
                                <div class="col-md-5 fw-bold">
                                    Toplam İş
                                </div>
                                <div class="col-md-7 text-end">
                                    <h5>
                                        <?php echo number_format($toplam_is_tutari, 2); ?> 
                                        <?php echo $para_birimi;?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-self-stretch">
                        <div class="p-3 text-white bg-success border border-success-subtle rounded-3 flex-fill">
                            <h1 class="p-2"><?php echo $uretimdeki_is_sayisi; ?></h1>
                            <div class="row">
                                <div class="col-md-5 fw-bold">
                                    Üretimdeki İş
                                </div>
                                <div class="col-md-7 text-end">
                                    <h5>
                                        <?php echo number_format($uretimdeki_is_sayisi_toplam_fiyat, 2); ?> 
                                        <?php echo $para_birimi;?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-self-stretch">
                        <div class="p-3 text-white bg-dark border border-dark-subtle rounded-3 flex-fill">
                            <h1 class="p-2"><?php echo $uretimdeki_tamamlanmis_is_sayisi; ?></h1>
                            <div class="row">
                                <div class="col-md-5 fw-bold">
                                    Teslime Hazır İş
                                </div>
                                <div class="col-md-7 text-end">
                                    <h5>
                                        <?php echo number_format($uretimdeki_tamamlanmis_is_sayisi_toplam_fiyat,2); ?> 
                                        <?php echo $para_birimi;?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-self-stretch">
                        <div class="p-3 text-white bg-warning border border-warning-subtle rounded-3 flex-fill">
                            <h1 class="p-2"><?php echo $teslim_edilen; ?></h1>
                            <div class="row">
                                <div class="col-md-5 fw-bold">
                                    Teslim Edilen İş
                                </div>
                                <div class="col-md-7 text-end">
                                    <h5>
                                        <?php echo number_format($teslim_edilen_toplam_fiyat,2) ; ?> 
                                        <?php echo $para_birimi;?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row mt-3">
                    <div class="col-md-10">
                        <form method="GET"  class="form-inline d-flex  align-items-center mx-2" >
                            <input type="hidden" name="url" value="siparis">
                            <input type="hidden" name="musteri_id" value="<?php echo $musteri_id;?>">

                            <div class="input-group flex-nowrap w-25 mx-2">
                                <span class="input-group-text fw-bold text-success">Başlangıç T.:</span>
                                <input type="date" class="form-control"  name="baslangic_tarihi" 
                                value="<?php echo isset($_GET['baslangic_tarihi']) ? $_GET['baslangic_tarihi'] : ''?>">
                            </div>
                            
            
                            <div class="input-group flex-nowrap w-25">
                                <span class="input-group-text fw-bold text-success">Bitiş T.:</span>
                                <input type="date" class="form-control"  name="bitis_tarihi" 
                                value="<?php echo isset($_GET['bitis_tarihi']) ? $_GET['bitis_tarihi'] : ''?>">
                                <button class="input-group-text btn-primary btn fw-bold" type="submit">
                                    <i class="fa-regular fa-paper-plane"></i> GETİR
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex justify-content-end"> 
                            <div class="btn-group" role="group">
                                <?php 
                                    $excel_url_tarih = '';
                                    if(isset($_GET['baslangic_tarihi']) && isset($_GET['bitis_tarihi'])){
                                        $baslangic_tarihi   = $_GET['baslangic_tarihi'];
                                        $bitis_tarihi       = $_GET['bitis_tarihi'];
                                        $excel_url_tarih    = "&baslangic_tarihi={$baslangic_tarihi}&bitis_tarihi={$bitis_tarihi}";
                                    }
                                ?>
                                <a href="/index.php?url=siparis_db_islem&islem=siparis_excel&musteri_id=<?php echo $musteri_id;?>
                                    <?php echo $excel_url_tarih;?>" class="btn btn-success"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="bottom" 
                                    data-bs-title="Excel"
                                >
                                    <i class="fa-regular fa-file-excel"></i>
                                </a>
                                <a href="/index.php?url=siparis_ekle&musteri_id=<?php echo $musteri_id;?>"  
                                    class="btn btn-primary"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="bottom" 
                                    data-bs-title="Sipariş Ekle"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                </a>
                                <a href="/index.php?url=musteriler" type="button" class="btn btn-warning"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="bottom" 
                                    data-bs-title="Müşteriler"
                                >
                                    <i class="fa-solid fa-users"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table id="myTable" class="table table-hover table-sm">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="text-center;align-middle">#</th>
                                        <th>Sipariş Kodu</th>
                                        <th>İşin Adı</th>
                                        <th>İlerleme</th>
                                        <th class="text-center">Termin</th>
                                        <th class="text-center">Fiyat</th>
                                        <th class="text-end;text-center;align-middle">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($siparisler as $key=>$siparis) { ?>
                                        <?php  
                                            $simdiki_tarih = time(); // or your date as well
                                            $termin_tarih = strtotime($siparis['termin']);
                                            $tarih_fark = $termin_tarih - $simdiki_tarih;

                                            $satir_class = "";
                                            //if($planlama['mevcut_asama'] == $planlama['asama_sayisi']) $satir_class = 'table-success';
                                            if ($tarih_fark < 0 )  $satir_class = 'table-danger';
                                        ?>
                                        <tr class="<?php echo $satir_class; ?>">
                                            <th class="table-primary text-center align-middle"><?php echo $key + 1; ?></th>
                                            <th class="table-secondary align-middle"><?php echo $siparis['siparis_no']; ?></th>
                                            <td class="align-middle"><?php echo $siparis['isin_adi']; ?></td>
                                            <?php 
                                                $sql = "SELECT id,mevcut_asama,asama_sayisi, isim, departmanlar,uretilecek_adet,durum,biten_urun_adedi,`onay_durum`
                                                FROM `planlama` WHERE siparis_id = :siparis_id AND aktar_durum = 'orijinal'";
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam('siparis_id', $siparis['id']);
                                                $sth->execute();
                                                $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);


                                                $sql = 'SELECT COUNT(siparis_id) AS log_sayisi FROM `siparis_log` WHERE  siparis_id = :siparis_id';
                                                $sth = $conn->prepare($sql);
                                                $sth->bindParam('siparis_id', $siparis['id']);
                                                $sth->execute();
                                                $siparis_log = $sth->fetch(PDO::FETCH_ASSOC);

                                            ?>
                                            <td> 
                                                <?php foreach ($planlamalar as $planlama) { ?>
                                                    <?php 
                                                        
                                                        if($planlama['durum'] == 'baslamadi')   { $mevcut_asama_sonuc = 0;}
                                                        else if($planlama['durum'] == 'bitti')  { $mevcut_asama_sonuc = $planlama['mevcut_asama'];}
                                                        else                                    { $mevcut_asama_sonuc = $planlama['mevcut_asama'] + 1;}

                                                        $asama_yuzdesi = round($mevcut_asama_sonuc*100/($planlama['asama_sayisi'] == 0 ? 1 : $planlama['asama_sayisi']),2);

                                                    ?>
                                                    <ul class="list-group mb-1">
                                                        <li class="list-group-item d-flex gap-2">
                                                            <i class="fa-brands fa-product-hunt fs-4"></i>
                                                            <span class="fw-bold text-decoration-underline">
                                                                <?php echo $planlama['isim']; ?> 
                                                            </span>
                                                            <span class="badge bg-secondary mb-1">
                                                                <?php echo $mevcut_asama_sonuc; ?> / 
                                                                <?php echo $planlama['asama_sayisi']?>
                                                            </span>
                                                            <?php if( ($planlama['mevcut_asama'] == 0 &&  $planlama['asama_sayisi'] == 0)){?>
                                                                <span class="badge bg-danger mb-1"> Planlama Yapılmadı </span>
                                                            <?php }else if($planlama['mevcut_asama'] < $planlama['asama_sayisi'] ){ ?>
                                                                <?php 
                                                                    $departmanlar = json_decode($planlama['departmanlar'], true); 
                                                                    $mevcut_departman_id = $departmanlar[$planlama['mevcut_asama']];

                                                                    $sql = "SELECT departman FROM `departmanlar` WHERE id = :id";
                                                                    $sth = $conn->prepare($sql);
                                                                    $sth->bindParam('id', $mevcut_departman_id);
                                                                    $sth->execute();
                                                                    $departman = $sth->fetch(PDO::FETCH_ASSOC);
                                                                ?>
                                                                
                                                                <span class="badge bg-secondary mb-1"> 
                                                                    <?php echo $departman['departman']; ?>  
                                                                </span>
                                                            <?php }else{ ?>
                                                                <span class="badge bg-success mb-1"> Bitti </span>              
                                                            <?php } ?>
                                                            <span class="badge bg-secondary mb-1"> 
                                                                <?php echo number_format($planlama['biten_urun_adedi'],0,SAYI_KESIRLI_AYIRICI,SAYI_BINLIK_AYIRICI); ?> / 
                                                                <?php echo number_format($planlama['uretilecek_adet'],0,SAYI_KESIRLI_AYIRICI,SAYI_BINLIK_AYIRICI); ?>
                                                            </span>
                                                        </li>
                                                        <?php if($planlama['onay_durum'] == 'evet'){?>
                                                            <li class="list-group-item">
                                                                <div class="progress" role="progressbar"  aria-valuenow="<?php echo $asama_yuzdesi;?>" aria-valuemin="0" aria-valuemax="100" style="height: 25px">
                                                                    <div class="progress-bar progress-bar-striped fw-bold <?php echo $planlama['asama_sayisi'] == $planlama['mevcut_asama'] ? 'bg-success' :''; ?>" style="width: <?php echo $asama_yuzdesi; ?>%">
                                                                        <?php echo $asama_yuzdesi; ?>%
                                                                    </div>
                                                                </div>   
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                <?php }?>

                                                <?php if(empty($planlamalar)){?>
                                                    <span class="badge text-bg-danger p-2">PLANLAMA YAPILMADI</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-center;align-middle">
                                                <?php echo round($tarih_fark / (60 * 60 * 24)); ?> gün - 
                                                <?php echo date('d-m-Y', strtotime($siparis['termin'])) ;?>
                                            </td>
                                            <td class="text-end fw-bold table-success align-middle">
                                                <?php 
                                                    if($siparis['para_cinsi'] == 'TL')            $para_birimi = '₺';
                                                    else if($siparis['para_cinsi'] == 'DOLAR')    $para_birimi = '$';
                                                    else if($siparis['para_cinsi'] == 'EURO')     $para_birimi = '€';
                                                    else if($siparis['para_cinsi'] == 'POUND')    $para_birimi = '£';
                                                ?>
                                                <?php echo number_format($siparis['fiyat'],2,'.',',').' '.$para_birimi ; ?>
                                            </td>
                                            <td class="text-center align-middle table_sm_pd">
                                            <div class="btn-group custom-dropdown">
                                                <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="mdi mdi-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a 
                                                        target="_blank"
                                                        class="dropdown-item pt-0 pb-0"                  
                                                        href="/index.php?url=siparis_db_islem&islem=onay-pdf&siparis-id=<?php echo $siparis['id']; ?>">Onay Formu</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a 
                                                        class="dropdown-item pt-0 pb-0" 
                                                        onClick="return confirm('Tekrar Sipariş Yapmak İstediğinize Emin Misiniz?')"                    
                                                        href="/index.php?url=siparis_db_islem&islem=siparis-tekrar&siparis-id=<?php echo $siparis['id']; ?>">Sipariş Tekrar</a>
                                                    <div class="dropdown-divider"></div>
                                                    <?php if(in_array(SIPARIS_GOR, $_SESSION['sayfa_idler'])){ ?>
                                                        <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparis_gor&siparis_id=<?php echo $siparis['id']; ?>">Siparişi Gör
                                                        <span class="badge bg-success rounded-pill ms-1" style="font-size:11px;"><?php echo $siparis_log['log_sayisi']; ?></span>
                                                        <div class="dropdown-divider"></div></a>
                                                    <?php } ?>
                                                    <?php if(in_array(SIPARIS_DUZENLE, $_SESSION['sayfa_idler']) && $siparis['onay_baslangic_durum'] == 'hayır'){ ?>
                                                        <a class="dropdown-item pt-0 pb-0" href="/index.php?url=siparis_guncelle&siparis_id=<?php echo $siparis['id']; ?>">Güncelle</a>
                                                        <div class="dropdown-divider"></div>
                                                    <?php }else{?> 
                                                        <a 
                                                            data-bs-title="<b class='text-danger'>Sipariş İşleme Alındığı<br> İçin Güncelle Yapılamaz</b>"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-html="true"
                                                            class="dropdown-item pt-0 pb-0" href="javascript:;" aria-disabled="true">Güncelle</a>
                                                        <div class="dropdown-divider" style="margin-top:2px"></div>
                                                    <?php } ?>
                                                    <?php if(in_array(SIPARIS_SIL, $_SESSION['sayfa_idler']) && $siparis['onay_baslangic_durum'] == 'hayır'){ ?>
                                                        <a href="/index.php?url=siparis_db_islem&islem=siparis_sil&id=<?php echo $siparis['id']; ?>&musteri_id=<?php echo $musteri_id;?>" 
                                                           onClick="return confirm('Silmek İstediğinize Emin Misiniz?')">Sil</a>
                                                        <div class="dropdown-divider" style="margin-top:2px"></div>
                                                    <?php }else{?> 
                                                        <a 
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="bottom" 
                                                            data-bs-html="true"
                                                            data-bs-title="<b class='text-danger'>Sipariş İşleme Alındığı<br> İçin Silme Yapılamaz</b>"
                                                            class="dropdown-item pt-0 pb-0" href="javascript:;" aria-disabled="true">Sil</a>
                                                        <div class="dropdown-divider" style="margin-top:2px"></div>
                                                    <?php } ?>
                                                </div>
                                            </div> 
                                            </td> 
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        </
                    </div>
                    
                </div>

            </div>
        </div>
    </div>  
  <?php 
        include_once "include/uyari_session_oldur.php"; 
    ?>