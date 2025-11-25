<?php 
    require_once "include/SimpleXLSXGen.php";

    if(isset($_GET['islem']) && $_GET['islem'] == 'tuketim-getir')
    {
        try{
            $id    = $_GET['id'];
            $parms = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                    kl.planlama_id, 
                    kl.mevcut_asama, 
                    kl.stok_id, 
                    st.stok_kalem, 
                    kl.tuketim_miktari, 
                    kl.fire_miktari,
                    coalesce(brm.ad,'') as birim,
                    coalesce(concat(prs.ad,' ', prs.soyad),'') as personel,
                    DATE_FORMAT(kl.tarih, '%d.%m.%Y %H:%i:%s') AS tarih
                    FROM stok_alt_depolar_kullanilanlar AS kl
                    LEFT JOIN stok_kalemleri as st on st.id = kl.stok_id and st.firma_id  = :firma_id
                    left join personeller prs on prs.id = kl.personel_id and prs.firma_id = :firma_id
                    left join birimler brm on brm.id = kl.birim_id and brm.firma_id = :firma_id
                    WHERE planlama_id = :planlama_id and mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $tuketimler = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $tuketimler
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'teslimat-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]); 

            $sql = "SELECT 
                    tsl.planlama_id, 
                    coalesce(tsl.teslim_adedi,'')  as teslim_adedi,
                    coalesce(concat(p.ad,' ', p.soyad),'') as personel,
                    DATE_FORMAT(tsl.tarih, '%d.%m.%Y %H:%i:%s') AS tarih
                    FROM teslim_edilenler tsl
                    LEFT JOIN personeller p on p.id = tsl.personel_id and p.firma_id = :firma_id
                    WHERE planlama_id = :planlama_id";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $teslimatlar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $teslimatlar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'fason-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                    fsn.planlama_id, 
                    coalesce(fsn.durum,'') as durum,
                    coalesce(fsn.iptal_sebebi,'') as iptal_sebebi,
                    coalesce(concat(p.ad,' ', p.soyad),'') as personel,
                    DATE_FORMAT(fsn.gidis_tarihi, '%d.%m.%Y %H:%i:%s') AS gidis_tarihi,
                    DATE_FORMAT(fsn.gelis_tarihi, '%d.%m.%Y %H:%i:%s') AS gelis_tarihi
                    FROM uretim_fason_durum_loglar fsn
                    LEFT JOIN personeller p on p.id = fsn.personel_id and p.firma_id = :firma_id
                    WHERE fsn.planlama_id = :planlama_id and fsn.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $fasonlar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $fasonlar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'ariza-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                        ariza.planlama_id,
                        ariza.mevcut_asama,
                        ariza.mesaj,
                        concat(prs.ad,' ', prs.soyad) as personel,
                        DATE_FORMAT(ariza.baslatma_tarihi, '%d.%m.%Y %H:%i:%s') AS baslatma_tarihi,
                        DATE_FORMAT(ariza.bitis_tarihi, '%d.%m.%Y %H:%i:%s') AS bitis_tarihi
                    FROM uretim_ariza_log as ariza
                    left join personeller prs on prs.id = ariza.personel_id and prs.firma_id = ariza.firma_id
                    WHERE ariza.planlama_id = :planlama_id and ariza.mevcut_asama = :mevcut_asama and ariza.firma_id = :firma_id";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $arizalar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $arizalar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'bakim-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                    bakim.planlama_id,
                    bakim.mevcut_asama,
                    bakim.ariza_sebebi,
                    bakim.sorun_cozuldu_mu,
                    concat(gln_prs.ad,' ', gln_prs.soyad) as gelen_personel,
                    concat(prs.ad,' ', prs.soyad) as personel,
                    DATE_FORMAT(bakim.personel_gelme_tarihi, '%d.%m.%Y %H:%i:%s') AS personel_gelme_tarihi,
                    DATE_FORMAT(bakim.baslatma_tarihi, '%d.%m.%Y %H:%i:%s') AS baslatma_tarihi,
                    DATE_FORMAT(bakim.bitis_tarihi, '%d.%m.%Y %H:%i:%s') AS bitis_tarihi
                    FROM uretim_bakim_log as bakim
                    left join personeller gln_prs on gln_prs.id = bakim.gelen_personel_id and bakim.firma_id = gln_prs.firma_id
                    left join personeller prs on prs.id = bakim.personel_id and bakim.firma_id = prs.firma_id
                    WHERE bakim.planlama_id = :planlama_id and bakim.mevcut_asama = :mevcut_asama and bakim.firma_id = :firma_id";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $bakimlar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $bakimlar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'degistirme-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                    dgs_log.planlama_id,
                    dgs_log.mevcut_asama,
                    dgs_log.degistirme_sebebi,
                    CASE WHEN dgs_log.sorun_bildirisin_mi = 1 THEN 'Evet' Else 'Hayır' End AS sorun_bildirimi,
                    COALESCE(concat(prs.ad,' ', prs.soyad),'') as personel,
                    DATE_FORMAT(dgs_log.tarih, '%d.%m.%Y %H:%i:%s') AS tarih
                    FROM uretim_degistir_loglar as dgs_log
                    left join personeller prs on prs.id = dgs_log.personel_id and prs.firma_id = :firma_id
                    WHERE dgs_log.planlama_id = :planlama_id and dgs_log.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $degistirmeler = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $degistirmeler
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'devretme-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                    ds.planlama_id,
                    ds.mevcut_asama,
                    COALESCE(m1.makina_adi,'') as hangi_makinadan,
                    COALESCE(m2.makina_adi,'') as hangi_makinaya,
                    ds.devretme_sebebi,
                    concat(prs.ad,' ', prs.soyad) as personel,
                    DATE_FORMAT(ds.tarih, '%d.%m.%Y %H:%i:%s') AS tarih
                    FROM uretim_makina_devretme_sebebi_loglar as ds
                    left join makinalar m1 on m1.id = ds.hangi_makinadan and m1.firma_id  = :firma_id
                    left join makinalar m2 on m2.id = ds.hangi_makinaya and m2.firma_id   = :firma_id
                    left join personeller prs on prs.id = ds.personel_id and prs.firma_id = :firma_id
                    WHERE ds.planlama_id = :planlama_id and ds.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $devretme = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $devretme
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'mesaj-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                        msj.planlama_id,
                        msj.mevcut_asama, 
                        coalesce(msj.mesaj,'') as mesaj,
                        coalesce(concat(prs.ad,' ', prs.soyad),'') as personel,
                        DATE_FORMAT(msj.tarih, '%d.%m.%Y %H:%i:%s') AS tarih
                    FROM uretim_mesaj_log as msj
                    left join personeller prs on prs.id = msj.personel_id and prs.firma_id = :firma_id
                    WHERE msj.planlama_id = :planlama_id and msj.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $mesajlar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $mesajlar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'mola-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                        ml.planlama_id,
                        ml.mevcut_asama, 
                        coalesce(concat(prs.ad,' ', prs.soyad),'') as personel,
                        DATE_FORMAT(ml.baslatma_tarihi, '%d.%m.%Y %H:%i:%s') AS baslatma_tarihi,
                        DATE_FORMAT(ml.bitis_tarihi, '%d.%m.%Y %H:%i:%s') AS bitis_tarihi
                    FROM uretim_mola_log as ml
                    left join personeller prs on prs.id = ml.personel_id and prs.firma_id = :firma_id
                    WHERE ml.planlama_id = :planlama_id and ml.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $molalar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $molalar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'paydos-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                        paydos.planlama_id,
                        paydos.mevcut_asama, 
                        coalesce(concat(prs.ad,' ', prs.soyad),'') as personel,
                        DATE_FORMAT(paydos.tarih, '%d.%m.%Y %H:%i:%s') AS tarih
                    FROM uretim_paydos_loglar as paydos
                    left join personeller prs on prs.id = paydos.personel_id and prs.firma_id = paydos.firma_id
                    WHERE paydos.planlama_id = :planlama_id and paydos.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->execute();
            $molalar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $molalar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'toplanti-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                        top.planlama_id,
                        top.mevcut_asama, 
                        coalesce(concat(prs.ad,' ', prs.soyad),'') as personel,
                        DATE_FORMAT(top.baslatma_tarihi, '%d.%m.%Y %H:%i:%s') AS baslatma_tarihi,
                        DATE_FORMAT(top.bitis_tarihi, '%d.%m.%Y %H:%i:%s') AS bitis_tarihi
                    FROM uretim_toplanti_log as top
                    left join personeller prs on prs.id = top.personel_id and prs.firma_id = :firma_id
                    WHERE top.planlama_id = :planlama_id and top.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $toplantilar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $toplantilar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'yemekmola-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                        ymk.planlama_id,
                        ymk.mevcut_asama, 
                        coalesce(concat(prs.ad,' ', prs.soyad),'') as personel,
                        DATE_FORMAT(ymk.baslatma_tarihi, '%d.%m.%Y %H:%i:%s') AS baslatma_tarihi,
                        DATE_FORMAT(ymk.bitis_tarihi, '%d.%m.%Y %H:%i:%s') AS bitis_tarihi
                    FROM uretim_yemek_mola_log as ymk
                    left join personeller prs on prs.id = ymk.personel_id and prs.firma_id = :firma_id
                    WHERE ymk.planlama_id = :planlama_id and ymk.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->bindParam('firma_id', $_SESSION['firma_id']);
            $sth->execute();
            $toplantilar = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $toplantilar
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'yetkili-getir')
    {
        try{
            $id           = $_GET['id'];
            $parms        = explode('_', $id);
            $planlama_id  = intval($parms[0]);
            $mevcut_asama = intval($parms[1]);

            $sql = "SELECT 
                        ytk.planlama_id,
                        ytk.mevcut_asama,
                        concat(gln_prs.ad,' ', gln_prs.soyad) as gelen_personel,
                        concat(prs.ad,' ', prs.soyad) as personel,
                        DATE_FORMAT(ytk.tarih, '%d.%m.%Y %H:%i:%s') AS tarih
                    FROM uretim_yetkili_log as ytk
                    left join personeller gln_prs on gln_prs.id = ytk.gelen_personel_id and ytk.firma_id = gln_prs.firma_id
                    left join personeller prs on prs.id = ytk.personel_id and ytk.firma_id = prs.firma_id
                    WHERE ytk.planlama_id = :planlama_id and ytk.mevcut_asama = :mevcut_asama";

            $sth = $conn->prepare($sql);
            $sth->bindParam('planlama_id', $planlama_id);
            $sth->bindParam('mevcut_asama', $mevcut_asama);
            $sth->execute();
            $yetkiler = $sth->fetchAll(PDO::FETCH_ASSOC);
            ob_clean(); 
            echo json_encode([
            'success' => true,
            'data' => $yetkiler
            ]);
            exit; 
        } catch (PDOException $e) { 
            ob_clean(); 
            echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
            ]);
            exit; 
        }
    }
    if(isset($_GET['islem']) && $_GET['islem'] == 'rapor_excel')
    {
        $siparis_id = $_GET['siparis_id'];

        $sql = 'select 
                pl.id, 
                ua.planlama_id, 
                pl.teslim_durumu,
                pl.alt_urun_id, 
                pl.isim as urun,
                pl.uretilecek_adet,
                ua.uretilen_adet,
                ua.uretirken_verilen_fire_adet,
                ua.asama_sayisi,
                ua.mevcut_asama,
                ua.baslangic_tarihi,
                ua.bitis_tarihi,
                ua.personel_id,
                concat(prs.ad," ", prs.soyad) as personel,
                dp.departman,
                mk.makina_adi
            from siparisler sip 
            left join planlama as pl on sip.id = pl.siparis_id and
                                        sip.firma_id  = pl.firma_id
            left join uretilen_adetler ua on COALESCE(ua.planlama_id,0) = COALESCE(pl.id,0) and 
                                            COALESCE(ua.firma_id,0)    = COALESCE(pl.firma_id,0)
            left join personeller prs on prs.id = ua.personel_id and prs.firma_id = ua.firma_id 
            left join departmanlar dp on dp.id = ua.departman_id and dp.firma_id = ua.firma_id 
            left join makinalar mk on mk.id = ua.makina_id and mk.firma_id = ua.firma_id 
            where sip.id = :id AND sip.firma_id = :firma_id
            order by pl.id desc';

        $sth = $conn->prepare($sql);
        $sth->bindParam('id', $siparis_id);
        $sth->bindParam('firma_id', $_SESSION['firma_id']);
        $sth->execute();
        $excel_data = $sth->fetchAll(PDO::FETCH_ASSOC);

        $basliklar = [
                        'ID',
                        'Planlama ID',
                        'Teslim Durumu',
                        'Alt Ürün ID',
                        'Ürün',
                        'Üretilecek Adet',
                        'Üretilen Adet',
                        'Fire Adet',
                        'Aşama Sayısı',
                        'Mevcut Aşama',
                        'Başlangıç Tarihi',
                        'Bitiş Tarihi',
                        'Personel ID',
                        'Personel',
                        'Departman',
                        'Makina Adı'
                    ];

        $excel_data = array_merge([$basliklar], $excel_data);

        $renk = '#FFFF00'; 
        foreach ($excel_data[0] as $index => $baslik) {  
            $excel_data[0][$index] = "<style bgcolor=\"{$renk}\">{$baslik}</style>";
        }

        $xlsx = Shuchkin\SimpleXLSXGen::fromArray( $excel_data );
        $tarih = date('dmY');
        $xlsx->downloadAs("Üretimler-{$tarih}.xlsx");

        exit;
    }
?>