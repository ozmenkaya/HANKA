<?php 
    require_once "include/oturum_kontrol.php";
    require_once "include/helper.php";

    $sql = "SELECT * FROM `firmalar` WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam("id", $_SESSION['firma_id']);
    $sth->execute();
    $firma_ayarlar = $sth->fetch(PDO::FETCH_ASSOC);

    $storage = new DreamHostStorage($conn);
                                           
?>
    
<div class="row">
<div class="card mt-2 border-secondary">
    <div class="card-header d-flex justify-content-between border-secondary">
        <h5> 
            <i class="fa-solid fa-gear"></i> Firma Ayar
        </h5>
        <div class="d-flex justify-content-end"> 
            <div class="btn-group" role="group">
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
    </div>
    <div class="card-body">
        <form class="row g-3 needs-validation" id="firma-ayarlar-form" enctype="multipart/form-data" action="/index.php?url=firma_ayarlar_db_islem" method="POST" novalidate>
            
            <!--<div class="form-floating col-md-6">
                <input class="form-control" type="file" id="logo" name="logo" >           
                <label for="logo" class="form-label label-ayarla">Logo</label>
            </div> -->
            <div class="col-md-6">
                <div class="input-group">
                    <label for="logo" class="input-group-text">Logo yükle</label>
                    <input type="file" class="form-control" id="logo" name="logo" />    
                </div>              
            </div> 
            <div class="form-floating col-md-6">
                <?php 
                     //$logo = $firma_ayarlar['logo'] != '' && file_exists("dosyalar/logo/{$firma_ayarlar['logo']}") ? $firma_ayarlar['logo'] : 'varsayilan.svg';
                     $result_url = $storage->getFileFromS3('logo', $firma_ayarlar['logo']);
                     if(!isset($result_url)){
                        $result_url = "dosyalar/logo/varsayilan.svg";
                     }
                ?>
                <a class="text-decoration-none example-image-link-0" 
                    href="<?php echo $result_url ?>" 
                    data-lightbox="example-set-0" data-title="">
                    <img class="object-fit-fill" 
                        src="<?php echo $result_url ?>" 
                        alt="<?php echo $firma_ayarlar['firma_adi']; ?>"  loading="lazy" 
                        style="width:100px;height:50px"
                    >
                </a>
            </div> 
             <div class="col-md-6">
                <div class="input-group">
                    <label for="etiketLogo" class="input-group-text">Etiket Logo yükle</label>
                    <input type="file" class="form-control" id="etiketLogo" name="etiketLogo" />    
                </div>              
            </div> 
            <div class="form-floating col-md-6">
                <?php 
                
                    //$etiketLogo = $firma_ayarlar['etiket_logo'] != '' && file_exists("dosyalar/logo/{$firma_ayarlar['etiket_logo']}") ? $firma_ayarlar['etiket_logo'] : 'varsayilan.svg'; 
                     
                    $etiketLogoFile = isset($firma_ayarlar['etiket_logo']) ? $firma_ayarlar['etiket_logo'] : '';
                    $etiketLogo = $storage->getFileFromS3('logo', $etiketLogoFile);
                     if(!isset($result_url)){
                        $etiketLogo = "dosyalar/logo/varsayilan.svg";
                     }
                ?>
                <a class="text-decoration-none example-image-link-0" 
                    href="<?php echo $etiketLogo;?>" 
                    data-lightbox="example-set-0" data-title="">
                    <img class="object-fit-fill" 
                        src="<?php echo $etiketLogo;?>" 
                        alt="<?php echo $firma_ayarlar['firma_adi']; ?>"  loading="lazy" 
                        style="width:100px;height:50px"
                    >
                </a>
            </div>
            <div class="form-floating col-md-6">
                <input type="text" class="form-control" id="siparis_no_baslangic_kodu" name="siparis_no_baslangic_kodu" value="<?php echo $firma_ayarlar['siparis_no_baslangic_kodu'];?>" required>
                <label for="siparis_no_baslangic_kodu" class="form-label label-ayarla">Sipariş No Başlangıç Kodu</label>
            </div>

            <div class="form-floating col-md-6">
                <select class="form-select form-select-lg" id="static_ip_varmi"  name="static_ip_varmi" required>
                    <option value="var" <?php echo $firma_ayarlar['static_ip_varmi']== 'var' ? 'selected':'';?>>Var</option>
                    <option value="yok" <?php echo $firma_ayarlar['static_ip_varmi']== 'yok' ? 'selected':'';?>>Yok</option>
                </select>
                <label for="static_ip_varmi" class="form-label">Static İP Var mı?</label>
            </div>
 
            <div class="form-floating col-md-12" id="makina-ekran-ipler-satir" style="display:<?php echo $firma_ayarlar['static_ip_varmi']== 'yok' ? 'none':'' ?>">
                <textarea class="form-control" name="makina_ekran_ipler" id="makina_ekran_ipler" 
                    style="height:200px" required><?php echo $firma_ayarlar['makina_ekran_ipler']; ?></textarea>
                <label for="makina_ekran_ipler" class="form-label">Makina Ekrana Kontrol IP(ler)</label>
                <div class="text-danger fw-bold"> * Birden fazla ise alt alta ekleyiniz!</div>
            </div>

            <div class="form-floating col-md-6">
                <select class="form-select form-select-lg" id="eksik_uretimde_onay_isteme_durumu"  name="eksik_uretimde_onay_isteme_durumu" required>
                    <option value="evet" <?php echo $firma_ayarlar['eksik_uretimde_onay_isteme_durumu']== 'evet' ? 'selected':'';?>>Evet</option>
                    <option value="hayır" <?php echo $firma_ayarlar['eksik_uretimde_onay_isteme_durumu']== 'hayır' ? 'selected':'';?>>Hayır</option>
                </select>
                <label for="eksik_uretimde_onay_isteme_durumu" class="form-label">Eksik Mal Üretim Onay İsteme Durumu</label>
            </div>
            <div class="form-floating col-md-6">
                <select class="form-select form-select-lg" id="arsiv_getirme"  name="arsiv_getirme" required>
                    <option value="siparise_ozel" <?php echo $firma_ayarlar['arsiv_getirme']== 'siparise_ozel' ? 'selected':'';?>>Siparişe Özel</option>
                    <option value="tumu" <?php echo $firma_ayarlar['arsiv_getirme']== 'tumu' ? 'selected':'';?>>Tüm Siparişte</option>
                </select>
                <label for="arsiv_getirme" class="form-label">Arşiv Getirme İşlemi</label>
            </div>

            <div class="form-floating col-md-12">
                <div class="form-check form-switch fs-6">
                    <input class="form-check-input" type="checkbox" role="switch" name="stoga_geri_gonderme_durumu" 
                        id="stoga_geri_gonderme_durumu" <?php echo $firma_ayarlar['stoga_geri_gonderme_durumu'] == 'evet' ? 'checked':''; ?>>
                    <label class="form-check-label" for="stoga_geri_gonderme_durumu">Stoğa Geri Gönderilecek Mi?</label>
                </div>
            </div>

            <!-- DOSYA DEPOLAMA AYARLARI -->
            <div class="col-md-12">
                <hr class="my-4">
                <h5 class="text-primary"><i class="fa-solid fa-hard-drive"></i> Dosya Depolama Ayarları</h5>
            </div>

            <div class="form-floating col-md-6">
                <select class="form-select form-select-lg" id="dosya_depolama_tipi" name="dosya_depolama_tipi" required>
                    <option value="local" <?php echo $firma_ayarlar['dosya_depolama_tipi'] == 'local' ? 'selected':'';?>>
                        Yerel Sunucu (Local)
                    </option>
                    <option value="s3" <?php echo $firma_ayarlar['dosya_depolama_tipi'] == 's3' ? 'selected':'';?>>
                        S3 (DreamHost / AWS)
                    </option>
                </select>
                <label for="dosya_depolama_tipi" class="form-label">Dosya Depolama Tipi</label>
            </div>

            <!-- S3 AYARLARI (sadece S3 seçiliyse göster) -->
            <div class="col-md-12" id="s3-ayarlar-container" style="display: <?php echo $firma_ayarlar['dosya_depolama_tipi'] == 's3' ? 'block' : 'none'; ?>">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle"></i> 
                            <strong>S3 Ayarları:</strong> DreamHost Objects veya AWS S3 için connection bilgilerinizi girin.
                        </div>
                    </div>

                    <div class="form-floating col-md-6">
                        <input type="text" class="form-control" id="s3_endpoint" name="s3_endpoint" 
                            value="<?php echo $firma_ayarlar['s3_endpoint'] ?? ''; ?>" 
                            placeholder="https://objects-us-east-1.dream.io">
                        <label for="s3_endpoint" class="form-label">S3 Endpoint URL</label>
                        <div class="form-text">Örnek: https://objects-us-east-1.dream.io</div>
                    </div>

                    <div class="form-floating col-md-6">
                        <input type="text" class="form-control" id="s3_region" name="s3_region" 
                            value="<?php echo $firma_ayarlar['s3_region'] ?? ''; ?>" 
                            placeholder="us-east-1">
                        <label for="s3_region" class="form-label">S3 Region</label>
                        <div class="form-text">Örnek: us-east-1</div>
                    </div>

                    <div class="form-floating col-md-6">
                        <input type="text" class="form-control" id="s3_access_key" name="s3_access_key" 
                            value="<?php echo $firma_ayarlar['s3_access_key'] ?? ''; ?>" 
                            placeholder="Access Key">
                        <label for="s3_access_key" class="form-label">S3 Access Key</label>
                    </div>

                    <div class="form-floating col-md-6">
                        <input type="password" class="form-control" id="s3_secret_key" name="s3_secret_key" 
                            value="<?php echo $firma_ayarlar['s3_secret_key'] ?? ''; ?>" 
                            placeholder="Secret Key">
                        <label for="s3_secret_key" class="form-label">S3 Secret Key</label>
                    </div>

                    <div class="form-floating col-md-6">
                        <input type="text" class="form-control" id="s3_bucket" name="s3_bucket" 
                            value="<?php echo $firma_ayarlar['s3_bucket'] ?? ''; ?>" 
                            placeholder="my-bucket">
                        <label for="s3_bucket" class="form-label">Default S3 Bucket</label>
                        <div class="form-text">Varsayılan bucket adı (ftp_ayarlar tablosunda değiştirilebilir)</div>
                    </div>
                </div>
            </div>

            <div>
                <button class="btn btn-primary" type="submit" name="ayar_kaydet" id="firma-ayarlar-button">
                    <i class="fa-regular fa-paper-plane"></i> KAYDET
                </button>
            </div>
        </form>
    </div>
</div> 
</div> 
<?php  
include_once "include/uyari_session_oldur.php"; 
?>

<script>
$(document).ready(function() {
    $("#firma-ayarlar-form").submit(function(){
        $("#firma-ayarlar-button").addClass('disabled');
        return true;
    });

    //Static IP varsa ip giriş aç
    $("#static_ip_varmi").change(function(){
        const static_ip_varmi = $(this).val();
        if(static_ip_varmi == 'var')    $("#makina-ekran-ipler-satir").show();
        else                            $("#makina-ekran-ipler-satir").hide();
    });

    // Dosya depolama tipi değiştiğinde S3 ayarlarını göster/gizle
    $("#dosya_depolama_tipi").change(function(){
        const depolama_tipi = $(this).val();
        if(depolama_tipi == 's3') {
            $("#s3-ayarlar-container").slideDown();
        } else {
            $("#s3-ayarlar-container").slideUp();
        }
    });
    
}); 
</script> 