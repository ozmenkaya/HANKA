<?php  

//ülkenin illeri getirme
if(isset($_GET['musteri_id']))
{
    $ulke_id = $_GET['musteri_id'];

    $sth = $conn->prepare('SELECT id, isin_adi FROM siparisler WHERE musteri_id=:musteri_id');
    $sth->bindParam('musteri_id', $musteri_id);
    $sth->execute();
    $sehirler = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode($siparisler);

}

//şehirin ilçeleri getirme
if(isset($_GET['sehir_id']))
{
    $sehir_id = $_GET['sehir_id'];
    $sth = $conn->prepare('SELECT id, baslik FROM ilceler WHERE sehir_id=:sehir_id');
    $sth->bindParam('sehir_id', $sehir_id);
    $sth->execute();
    $ilceler = $sth->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode($ilceler);
}