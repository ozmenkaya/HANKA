<?php 

# bir string tamsayıya çevirmek için intval
$a = intval('20'); //20

#onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"


/*
+ C --> create (ekleme)     => INSERT INTO
  INSERT INTO kullanicilar (adi, email, sifre) VALUES('Ali', 'ali@hotmail.com', '123445x');
+ R --> read (okuma)        => SELECT
    SELECT * FROM kullanicilar;
    SELECT name, surname FROM kullanicilar;
    SELECT name, surname FROM kullanicilar WHERE name = 'tekin';
    SELECT * FROM kullanicilar ORDER BY name ASC;  (a-z)
    SELECT * FROM kullanicilar ORDER BY name DESC;  (z-a)
    SELECT * FROM kullanicilar WHERE id > 4  ORDER BY surname DESC; (z-a)
- U --> update(güncelleme)  => UPDATE 
 - UPDATE kullanicilar SET adi = 'Veli', email = 'veli@gmail.com'  WHERE id = 1;
- D --> delete(silme)       => DELETE
    -  DELETE FROM kullanicilar; (tüm verileri siler)
    -  DELETE FROM kullanicilar WHERE id = 3; (id 3 olan veri sil)
    -  DELETE FROM kullanicilar WHERE adi = 'tekin'; (adi kolonu 'tekin' olan verileri sil) 

*/




#db bağlantı
try{    
    $conn = new PDO('mysql:host=localhost;port=3306;dbname=ozmen','root','');
}catch (PDOException $e){
    echo "<h1>Veritabanı bağlanti hatası...</h1>";
    #include "db_hata.php";
    exit;
}


#Read(getirme)
$sql = 'SELECT adi, email FROM kullanicilar';
$sth = $conn->prepare($sql);
$sth->execute();
$kullanicilar = $sth->fetchAll(PDO::FETCH_ASSOC);

#create(ekleme)

$ad             = $_POST['ad'];

$sql = "INSERT INTO personeller(ad) 
        VALUES(:ad);";
$sth = $conn->prepare($sql);
$sth->bindParam("ad", $ad);
$durum = $sth->execute();


#delete(silme)
$sql = "DELETE FROM kullanicilar WHERE id=:id";
$sth = $conn->prepare($sql);
$sth->bindParam('id', $id);
$durum = $sth->execute(); 



#update(güncelle)

$sql = "UPDATE kullanicilar SET adi = :adi  WHERE id = :id;";
$sth = $conn->prepare($sql);
$sth->bindParam('adi', $adi);
$sth->bindParam('id', $id);
$durum = $sth->execute();


#silme
$_SESSION['durum'] = 'success';
$_SESSION['mesaj'] = 'Silme İşlemi Başarılı';

$_SESSION['durum'] = 'error';
$_SESSION['mesaj'] = 'Silme İşlemi Başarısız';

#güncelle
$_SESSION['durum'] = 'success';
$_SESSION['mesaj'] = 'Güncelleme İşlemi Başarılı';

$_SESSION['durum'] = 'error';
$_SESSION['mesaj'] = 'Güncelleme İşlemi Başarısız';


#ekleme
$_SESSION['durum'] = 'success';
$_SESSION['mesaj'] = 'Ekleme İşlemi Başarılı';

$_SESSION['durum'] = 'error';
$_SESSION['mesaj'] = 'Ekleme İşlemi Başarısız';


//planlama pdf
/*
if(isset($_GET['islem']) && $_GET['islem'] == 'planlama-pdf'){
    $siparis_id = intval($_GET['siparis_id']);

    $sql = "SELECT isim, uretilecek_adet, departmanlar
            FROM `planlama` 
            WHERE siparis_id = :siparis_id AND firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('siparis_id',$siparis_id);
    $sth->bindParam('firma_id',$_SESSION['firma_id']);
    $sth->execute();
    $planlamalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $asamaTablosu   = "";
    $asamaIndex     = 0;
    $altUrunTablosu = "";
    foreach ($planlamalar as  $index => $planlama) {
        $departmanlar = json_decode($planlama['departmanlar'], true);
        $asamaIndex     = $index + 1;
        $asamaTablosu   = '';
        foreach ($departmanlar as $departmanIndex => $departman) {
            $asamaTablosu .= "
                <tr>
                    <th>{$asamaIndex}. Aşama</th>
                    <td>{$departman}</td>
                </tr>
            ";
        }
        

        $altUrunTablosu .= '
            <table id="customers">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Departman</th>
                    </tr>
                </thead>
                <tbody>'.$asamaTablosu.'</tbody>
            </table>
        ';
    }


    
    $html = '
        <!doctype html>
        <html lang="tr">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Planlama</title>
                <style>
                    #customers {
                        font-family: Arial, Helvetica, sans-serif;
                        border-collapse: collapse;
                        width: 100%;
                    }

                    #customers td, #customers th {
                        border: 1px solid #ddd;
                        padding: 8px;
                    }

                    #customers tr:nth-child(even){background-color: #f2f2f2;}

                    #customers tr:hover {background-color: #ddd;}

                    #customers th {
                        padding-top: 12px;
                        padding-bottom: 12px;
                        text-align: left;
                        background-color: #04AA6D;
                        color: white;
                    }
                </style>
            </head>
            <body>'.$altUrunTablosu.'</body>
        </html>
    ';

    
    //echo $html; return;
    

    $dompdf = new Dompdf();

    //echo $html; exit;
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4'); 
    $dompdf->render(); 
    $dompdf->stream('planlama-'.date('dmY_His').'.pdf');

    exit;
}
*/