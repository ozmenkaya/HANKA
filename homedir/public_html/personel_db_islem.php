<?php

#echo "<pre>"; print_r($_POST); exit;
#echo "<pre>"; print_r($_GET);

require_once "include/oturum_kontrol.php";

//excel  işlemi
if(isset($_GET['islem']) && $_GET['islem'] == 'personel_csv')
{

    //https://www.codexworld.com/export-data-to-csv-file-using-php-mysql/
    $delimiter = ","; 
    $filename = "personeller_" . date('Y-m-d-His') . ".csv"; 

    // Create a file pointer 
    $f = fopen('php://memory', 'w'); 


    $fields = ['SIRA','AD', 'SOYAD', 'DOGUM TARIHI', 'EMAIL', 'ADRES', 'TELEFON', 'SABIT HAT', 'GOREV']; 
    fputcsv($f, $fields, $delimiter); 

    $sth = $conn->prepare('SELECT personeller.ad, personeller.soyad, personeller.dogum_tarihi, personeller.email,
    personeller.adres, personeller.cep_numarasi, personeller.sabit_hat, yetkiler.yetki 
    FROM personeller JOIN yetkiler ON yetkiler.id = personeller.yetki_id WHERE firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);

    foreach ($personeller as $key=> $personel) {
        $lineData = [
            $key+1,                     $personel['ad'],        $personel['soyad'], 
            $personel['dogum_tarihi'],  $personel['email'], 
            $personel['adres'],         $personel['cep_numarasi'], 
            $personel['sabit_hat'],     $personel['yetki']
        ]; 
        fputcsv($f, $lineData, $delimiter); 
    }

    fseek($f, 0); 
    // Set headers to download file rather than displayed 
    header("Content-Description: File Transfer");
	header("Content-Encoding: UTF-8");
    header('Content-type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    header('Content-Transfer-Encoding: binary'); 
    //output all remaining data on a file pointer 
    fpassthru($f); 

}

#personel ekle
if(isset($_POST['personel_ekle']))
{
    $ad             = ucwords(trim($_POST['ad']));
    $soyad          = ucwords(trim($_POST['soyad']));
    $adres          = ucwords(trim($_POST['adres']));
    $durum          = trim($_POST['durum']);
    $yetki_id       = $_POST['yetki_id'];
    $cep_numarasi   = trim($_POST['cep_numarasi']);
    $sabit_hat      = trim($_POST['sabit_hat']);
    $email          = trim($_POST['email']);
    $sifre          = sha1(trim($_POST['sifre']));
    $dogum_tarihi   = $_POST['dogum_tarihi'];
    $ise_baslama    = $_POST['ise_baslama'];
    $aciklama       = ucwords(trim($_POST['aciklama']));


    $sth = $conn->prepare('SELECT id FROM personeller WHERE email =:email');
    $sth->bindParam('email', $email);
    $sth->execute();
    $personel = $sth->fetch(PDO::FETCH_ASSOC);

    if(!empty($personel))
    {
        $_SESSION['durum'] = 'basarisiz';
        $_SESSION['mesaj'] = 'Aynı Emailden Daha Önceden Mevcut';
        header("Location: /index.php?url=personel_ekle");
        die();
    }

    $sql = "INSERT INTO personeller(firma_id, ad, soyad, adres, yetki_id, cep_numarasi, sabit_hat, email, sifre, dogum_tarihi, ise_baslama, aciklama,durum) 
            VALUES(:firma_id, :ad, :soyad, :adres, :yetki_id, :cep_numarasi, :sabit_hat, :email, :sifre, :dogum_tarihi, :ise_baslama, :aciklama, :durum);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);
    $sth->bindParam("ad", $ad);
    $sth->bindParam("soyad", $soyad);
    $sth->bindParam("adres", $adres);
    $sth->bindParam("yetki_id", $yetki_id);
    $sth->bindParam("cep_numarasi", $cep_numarasi);
    $sth->bindParam("sabit_hat", $sabit_hat);
    $sth->bindParam("email", $email);
    $sth->bindParam("sifre", $sifre);
    $sth->bindParam("dogum_tarihi", $dogum_tarihi);
    $sth->bindParam("ise_baslama", $ise_baslama);
    $sth->bindParam("aciklama", $aciklama);
    $sth->bindParam("durum", $durum);
    $durum = $sth->execute();

    if($durum == true)
    {
        $personel_id = $conn->lastInsertId();

        for($i = 0; isset($_POST['departman_idler']) &&  $i < count($_POST['departman_idler']); $i++)
        {
            $sql = "INSERT INTO personel_departmanlar(personel_id, departman_id) VALUES(:personel_id, :departman_id)";
            $sth = $conn->prepare($sql);
            $sth->bindParam('personel_id', $personel_id);
            $sth->bindParam('departman_id', $_POST['departman_idler'][$i]);
            $durum = $sth->execute();
        
        }
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Personel Ekleme İşlemi Başarılı';
        header("Location: /index.php?url=personel");    
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['durum'] = 'Personel Ekleme İşlemi Başarısız';
        header("Location: /index.php?url=personel_ekle");
    }
    die();
}

#personel sil
if(isset($_GET['islem']) && $_GET['islem'] == 'personel_sil')
{
    $id = $_GET['id'];

    $sql = "DELETE FROM personeller WHERE id=:id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $durum = $sth->execute(); 
    
    if($durum == true)
    {
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarılı';
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Silme İşlemi Başarısız';
    }
    header('Location: /index.php?url=personel');
    die();
}


#personel_guncelle
if(isset($_POST['personel_guncelle']))
{
    $id             = $_POST['id'];
    $ad             = ucwords(trim($_POST['ad']));
    $soyad          = ucwords(trim($_POST['soyad']));
    $adres          = ucwords(trim($_POST['adres']));
    $durum          = trim($_POST['durum']);
    $yetki_id       = $_POST['yetki_id'];
    $cep_numarasi   = trim($_POST['cep_numarasi']);
    $sabit_hat      = trim($_POST['sabit_hat']);
    $email          = trim($_POST['email']);
    $sifre          = trim($_POST['sifre']);
    $dogum_tarihi   = $_POST['dogum_tarihi'];
    $ise_baslama    = $_POST['ise_baslama'];
    $aciklama       = ucwords(trim($_POST['aciklama']));

    if(!empty($sifre)) //şifre boş gelmemişse
    {
        $sql = "UPDATE personeller SET ad = :ad, soyad = :soyad, adres = :adres, yetki_id = :yetki_id, 
        cep_numarasi = :cep_numarasi, sabit_hat = :sabit_hat, email = :email, sifre = :sifre, dogum_tarihi = :dogum_tarihi, 
        ise_baslama = :ise_baslama, 
        aciklama = :aciklama, durum = :durum 
        WHERE id = :id AND firma_id = :firma_id;";
    }
    else 
    {
        $sql = "UPDATE personeller SET ad = :ad, soyad = :soyad, adres = :adres,
        yetki_id = :yetki_id, cep_numarasi = :cep_numarasi, sabit_hat = :sabit_hat, email = :email,
        dogum_tarihi = :dogum_tarihi, ise_baslama = :ise_baslama, aciklama = :aciklama, durum = :durum
        WHERE id = :id AND firma_id = :firma_id;";

    }
    $sth = $conn->prepare($sql);
    $sth->bindParam("ad", $ad);
    $sth->bindParam("soyad", $soyad);
    $sth->bindParam("adres", $adres);
    $sth->bindParam("yetki_id", $yetki_id);
    $sth->bindParam("cep_numarasi", $cep_numarasi);
    $sth->bindParam("sabit_hat", $sabit_hat);
    $sth->bindParam("email", $email);
    if(!empty($sifre))
    {
        $sth->bindParam("sifre", sha1($sifre));
    }
    $sth->bindParam("dogum_tarihi", $dogum_tarihi);
    $sth->bindParam("ise_baslama", $ise_baslama);
    $sth->bindParam("aciklama", $aciklama);
    $sth->bindParam("durum", $durum);
    $sth->bindParam("id", $id);
    $sth->bindParam("firma_id", $_SESSION['firma_id']);

    $durum = $sth->execute();
    
    if($durum == true)
    {
        $sql = "DELETE FROM personel_departmanlar WHERE personel_id =  :personel_id";
        $sth = $conn->prepare($sql);
        $sth->bindParam('personel_id', $id);
        $sth->execute();

        for($i = 0; isset($_POST['departman_idler']) && $i < count($_POST['departman_idler']); $i++)
        {
            $sql = "INSERT INTO personel_departmanlar(personel_id, departman_id) VALUES(:personel_id, :departman_id)";
            $sth = $conn->prepare($sql);
            $sth->bindParam('personel_id', $id);
            $sth->bindParam('departman_id', $_POST['departman_idler'][$i]);
            $durum = $sth->execute();
        
        }
        $_SESSION['durum'] = 'success';
        $_SESSION['mesaj'] = 'Personel Düzenleme İşlemi Başarılı';
        header("Location: /index.php?url=personel");
    }
    else 
    {
        $_SESSION['durum'] = 'error';
        $_SESSION['mesaj'] = 'Personel Düzenleme İşlemi Başarısız';
        header("Location: /index.php?url=personel_guncelle&id={$id}");
    }
    die();
}

//email kontrol
if(isset($_GET['islem']) && $_GET['islem'] == 'email-kontrol')
{
    $email = trim($_GET['email']);

    $sth = $conn->prepare('SELECT id FROM personeller WHERE email =:email');
    $sth->bindParam('email', $email);
    $sth->execute();
    $personel = $sth->fetch(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode(['data' => $personel]);
    exit;
}

//departman id ye göre personelleri getirme işlemi
if(isset($_GET['islem']) && $_GET['islem'] == 'departman-id-personel')
{
    $departman_id = $_GET['departman_id'];
    $sth = $conn->prepare('SELECT personeller.id, personeller.ad, personeller.soyad FROM personel_departmanlar JOIN personeller 
                    ON personel_departmanlar.personel_id = personeller.id 
                    WHERE personel_departmanlar.departman_id = :departman_id AND personeller.firma_id = :firma_id');
    $sth->bindParam('departman_id', $departman_id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $personeller = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode($personeller);
    exit;
}