<?php
    require_once "include/oturum_kontrol.php";

    $sql = "SELECT makinalar.id, makinalar.makina_adi, makinalar.makina_modeli, makinalar.makina_seri_no,
            departmanlar.departman,departmanlar.id AS departman_id
            FROM `makinalar` 
            JOIN makina_personeller ON makina_personeller.makina_id = makinalar.id 
            JOIN departmanlar ON departmanlar.id = makinalar.departman_id
            WHERE makinalar.firma_id = :firma_id AND makina_personeller.personel_id = :personel_id AND makinalar.durumu = 'aktif'";

    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('personel_id', $_SESSION['personel_id']);
    $sth->execute();
    $makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT id, asama_sayisi,mevcut_asama, makinalar, departmanlar, onay_durum, durum FROM `planlama` 
        WHERE firma_id = :firma_id AND aktar_durum = 'orijinal' ";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT makina_ekran_ipler FROM `firmalar` WHERE id = :id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $_SESSION['firma_id']);
    $sth->execute();
    $firma_ayar = $sth->fetch(PDO::FETCH_ASSOC);

    $makina_ekran_ipler = array_map('trim', explode("\n", $firma_ayar['makina_ekran_ipler']));
    $my_ip              = $_SERVER['REMOTE_ADDR'];
    //echo "<pre>";print_r($_SERVER);
    //echo $my_ip;
    //print_r($makina_ekran_ipler); exit;

    
?>
        <!-- <style>
            a.disabled {
                cursor: no-drop;
            }
        </style> -->
        <div class="container">
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card border-secondary">
                        <div class="card-body d-md-flex justify-content-between">
                            <h4 class="d-flex align-items-center gap-2">
                                <i class="fa-regular fa-circle-user"></i>
                                <span class="fw-bold">Hoş Geldin, </span>
                                <span class="badge bg-secondary"><?php echo $_SESSION['ad'].' '.$_SESSION['soyad']; ?></span> 
                            </h4>
                            <div>
                                <a class="btn btn-warning fw-bold" href="/index.php?url=sifre_guncelle">
                                    <i class="fa-solid fa-lock fs-4"></i>
                                    Şifre Değiştir
                                </a>
                                <span class="badge bg-success fs-6 me-2" id="realtime-durum">
                                    <i class="fa-solid fa-circle-dot"></i> Anlık Veri
                                </span>
                                <a 
                                    class="btn btn-danger fw-bold" 
                                    href="/index.php?url=login_kontrol&islem=cikis-yap"
                                    onClick="return confirm('Çıkmak İstediğinize Emin Misiniz?')"
                                >
                                    <i class="fa-solid fa-arrow-right-from-bracket fs-4"></i>
                                    ÇIKIŞ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php foreach ($makinalar as $makina) { ?>
                    <?php 
                        $is_sayisi = 0;
                        $aktif_is_varmi = false;
                        $planlama_id = 0;
                        foreach ($planlamalar as $planlama) {
                            $planla_makinalar   = json_decode($planlama['makinalar'], true);
                            $departmanlar       = json_decode($planlama['departmanlar'], true);

                            if(isset($planla_makinalar[$planlama['mevcut_asama']]) && 
                                isset($departmanlar[$planlama['mevcut_asama']]) && 
                                $planla_makinalar[$planlama['mevcut_asama']] == $makina['id'] && 
                                $departmanlar[$planlama['mevcut_asama']] == $makina['departman_id'] && 
                                $planlama['onay_durum'] == 'evet'){
                                $is_sayisi++;
                                if($planlama['durum'] == 'basladi') 
                                {
                                    $aktif_is_varmi = true;
                                    $planlama_id = $planlama['id'];
                                }
                            }

                        }  
                    ?>
                    <div class="col-md-6 mb-3">    
                        <div class="card border-secondary">
                            <div class="card-header border-secondary d-flex justify-content-between">
                                <h5>
                                    <i class="fa-solid fa-building"></i>
                                    <?php echo $makina['departman']; ?>
                                </h5>
                                <div>
                                    <span class="fw-semibold fst-italic fs-6">İş: </span>
                                    <b class="text-danger fw-bold fs-5" data-realtime="is_sayisi" data-makina-id="<?php echo $makina['id']; ?>"><?php echo $is_sayisi; ?></b>
                                </div>
                            </div>
                            <div class="card-body">
                                <ul class="list-group mb-2">
                                    <li class="list-group-item">
                                        <b>Ad:      </b> 
                                        <span class="text-decoration-underline"><?php echo $makina['makina_adi']; ?></span>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Model:   </b> 
                                        <span class="text-decoration-underline"><?php echo $makina['makina_modeli']; ?></span> 
                                    </li>
                                    <li class="list-group-item">
                                        <b>Seri No: </b>
                                        <span class="text-decoration-underline"><?php echo $makina['makina_seri_no']; ?></span>
                                    </li>
                                </ul>
                                
                                <div class="d-grid gap-2">
                                    <div class="btn-group btn-group-lg" role="group" aria-label="Basic example">
                                        <?php if($aktif_is_varmi){ ?>
                                            <a href="/index.php?url=makina_is_ekran&planlama-id=<?php echo $planlama_id; ?>&makina-id=<?php echo $makina['id']; ?>" class="btn btn-primary">
                                                <i class="fa-solid fa-paper-plane"></i> İşe Git
                                            </a>
                                        <?php }?>

                                        <?php if( $is_sayisi != 0){?>
                                            <a href="/index.php?url=makina_is_listesi&makina-id=<?php echo $makina['id']; ?>" class="btn btn-success">
                                                <i class="fa-solid fa-list"></i> İş Listesi
                                            </a>
                                        <?php }else { ?>
                                            <a href="javascript:;" class="btn btn-danger disabled">
                                                <i class="fa-solid fa-list"></i> İş Listesi
                                            </a>
                                        <?php } ?>
                                            
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }?>

                <?php if(empty($makinalar)){?>
                    <div class="col-md-12">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h4 class="text-danger fw-bold">
                                    1- Sizin Sorunluluğunuz Makina Bulunmuyor! (Admin İle İletişime Geçiniz)
                                </h4>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php 
            include_once "include/uyari_session_oldur.php";
        ?>

        <script>
        $(document).ready(function() {
            // Anlık veri güncellemesi için değişkenler
            var guncellemeAktif = true;
            var guncellemeSikligi = 10000; // 10 saniye
            
            // Anlık veri güncelleme fonksiyonu
            function verilerıGuncelle() {
                if (!guncellemeAktif) return;
                
                // Durum badge'ini sarıya çevir (yükleniyor)
                $('#realtime-durum')
                    .removeClass('bg-success bg-danger')
                    .addClass('bg-warning')
                    .html('<i class="fa-solid fa-spinner fa-spin"></i> Güncelleniyor...');
                
                $.ajax({
                    url: '/index.php?url=makina_listesi_db_islem&islem=realtime-makina-sayilari',
                    type: 'GET',
                    dataType: 'json',
                    timeout: 5000,
                    success: function(response) {
                        if (response.success) {
                            // Her makina için iş sayısını güncelle
                            response.makinalar.forEach(function(makina) {
                                var element = $('[data-realtime="is_sayisi"][data-makina-id="' + makina.makina_id + '"]');
                                if (element.length > 0) {
                                    var mevcutDeger = parseInt(element.text());
                                    var yeniDeger = parseInt(makina.is_sayisi);
                                    
                                    // Değer değiştiyse animasyonla güncelle
                                    if (mevcutDeger !== yeniDeger) {
                                        element.fadeOut(200, function() {
                                            $(this).text(yeniDeger).fadeIn(200);
                                        });
                                    }
                                }
                            });
                            
                            // Durum badge'ini yeşile çevir (başarılı)
                            $('#realtime-durum')
                                .removeClass('bg-warning bg-danger')
                                .addClass('bg-success')
                                .html('<i class="fa-solid fa-circle-dot"></i> Anlık Veri');
                        } else {
                            console.error('Güncelleme hatası:', response.error);
                            durumHataGoster();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX hatası:', status, error);
                        durumHataGoster();
                    }
                });
            }
            
            // Hata durumunda badge'i kırmızı yap
            function durumHataGoster() {
                $('#realtime-durum')
                    .removeClass('bg-success bg-warning')
                    .addClass('bg-danger')
                    .html('<i class="fa-solid fa-circle-exclamation"></i> Bağlantı Hatası');
                
                // 5 saniye sonra tekrar dene
                setTimeout(function() {
                    verilerıGuncelle();
                }, 5000);
            }
            
            // İlk güncellemeyi hemen yap
            verilerıGuncelle();
            
            // Periyodik güncelleme başlat
            var guncellemeInterval = setInterval(verilerıGuncelle, guncellemeSikligi);
            
            // Sayfa görünür değilken güncellemeyi durdur (performans için)
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    guncellemeAktif = false;
                    clearInterval(guncellemeInterval);
                } else {
                    guncellemeAktif = true;
                    verilerıGuncelle();
                    guncellemeInterval = setInterval(verilerıGuncelle, guncellemeSikligi);
                }
            });
        });
        </script>