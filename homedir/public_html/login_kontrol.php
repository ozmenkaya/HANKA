<?php 
require_once "include/db.php";

//çıkış yap
if(isset($_GET['islem']) && $_GET['islem'] == 'cikis-yap')
{
    // DB işlem sayfalarını ve AJAX endpoint'lerini filtrele
    $blocked_patterns = ['_db_islem', '_ajax', 'login_kontrol', 'logout', '/ajax/', '/api/'];
    $url = '';
    
    if(isset($_COOKIE['url'])){
        $cookie_url = $_COOKIE['url'];
        $is_blocked = false;
        
        foreach($blocked_patterns as $pattern){
            if(strpos($cookie_url, $pattern) !== false){
                $is_blocked = true;
                break;
            }
        }
        
        if(!$is_blocked){
            $url = $cookie_url;
        }
    }
    
    session_destroy();
    empty($url) ? header('Location: login.php') : header('Location: login.php?url='.$url);
    die();
}

//giriş yap
if(isset($_POST['giris']))
{
    $url    = trim($_POST['url']);
    $email  = trim($_POST['email']);
    $sifre  = sha1($_POST['sifre']);

    $sql = 'SELECT id, firma_id, ad, soyad, yetki_id, email FROM personeller 
            WHERE email=:email AND sifre=:sifre AND durum="aktif"';
    $sth = $conn->prepare($sql);
    $sth->bindParam('email', $email);
    $sth->bindParam('sifre', $sifre);
    $sth->execute();
    $personel = $sth->fetch(PDO::FETCH_ASSOC);

    //echo "<pre>"; print_r($personel); exit;

    if($personel) //doğru 
    {
        //giriş log
        $sql = "INSERT INTO giris_log(ip, email, tarayici, durum) VALUES(:ip, :email,:tarayici, 'basarılı');";
        $sth = $conn->prepare($sql);
        $sth->bindValue("ip", getUserIP());
        $sth->bindParam("email", $email);
        $sth->bindValue("tarayici", json_encode(getBrowser()));
        $durum = $sth->execute();

        $sth = $conn->prepare('SELECT * FROM firmalar WHERE id = :id');
        $sth->bindParam('id', $personel['firma_id']);
        $sth->execute();
        $firma = $sth->fetch(PDO::FETCH_ASSOC);



        //makina ekranı ip kontrolu
        if($personel['yetki_id'] == URETIM_YETKI_ID && $firma['static_ip_varmi'] == 'var'){
            

            $makina_ekran_ipler = array_map('trim', explode("\n", $firma['makina_ekran_ipler']));
            $my_ip              = $_SERVER['REMOTE_ADDR'];

            if(!in_array($my_ip, $makina_ekran_ipler)){
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Mevcut IP ile Makina Ekranına Giriş Yapamazsınız!';
                header("Location: login.php"); exit;
            }
        }

        $sql = "SELECT sayfa_idler FROM `yetki_sayfalar` WHERE firma_id = :firma_id AND yetki_id = :yetki_id ";
        $sth = $conn->prepare($sql);
        $sth->bindParam('firma_id', $personel['firma_id']);
        $sth->bindParam('yetki_id', $personel['yetki_id']);
        $sth->execute();
        $sayfa_idler = $sth->fetch(PDO::FETCH_ASSOC);

        $_SESSION['sayfa_idler'] = isset($sayfa_idler['sayfa_idler']) ? json_decode($sayfa_idler['sayfa_idler'], true) : [];
    
        $_SESSION['giris_kontrol']  = true;
        $_SESSION['personel_id']    = $personel['id'];
        $_SESSION['firma_id']       = $personel['firma_id'];
        $_SESSION['ad']             = $personel['ad'];
        $_SESSION['soyad']          = $personel['soyad'];
        $_SESSION['email']          = $personel['email'];
        $_SESSION['yetki_id']       = $personel['yetki_id'];
        $_SESSION['logo']           = $firma['logo'] != '' ? $firma['logo'] : 'varsayilan.svg';
        $_SESSION['firma_adi']      = $firma['firma_adi'];

        //echo "<pre>"; print_r($_SESSION);
        //exit;

        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Giriş Başarılı';

        if($personel['yetki_id'] == URETIM_YETKI_ID){ //üretim personeli ise
            header('Location: /index.php?url=makina_listesi');
            exit;
        }

        //Makina ekranından diğer ekranaları gittiğinde makina ekranına yönlendirmesi ise 2.koşul
        if(!empty($url) && !in_array(parse_url($url, PHP_URL_PATH), ['/makina_listesi.php'])){
            header('Location: '.$url); exit;
        }

        header('Location: /'); exit;

    }
    else  //yanlış email veya şifre
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Email ve/veya Şifreniz Hatalı';
        $_SESSION['post']  = $_POST;

        $sql = "INSERT INTO giris_log(ip, email,tarayici, durum) VALUES(:ip, :email,:tarayici, 'basarısız');";
        $sth = $conn->prepare($sql);
        $sth->bindValue("ip", getUserIP());
        $sth->bindParam("email", $email);
        $sth->bindValue("tarayici", json_encode(getBrowser()));
        $durum = $sth->execute();

        header("Location: login.php");
        die();
    }
}
else 
{
    header('Location: login.php');
    die();
}
