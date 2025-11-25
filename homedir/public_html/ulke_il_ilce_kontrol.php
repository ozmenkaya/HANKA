<?php   

//ülkenin illeri getirme
if(isset($_GET['ulke_id']))
{
    $ulke_id = $_GET['ulke_id'];

    $sth = $conn->prepare('SELECT id, baslik FROM sehirler WHERE ulke_id=:ulke_id');
    $sth->bindParam('ulke_id', $ulke_id);
    $sth->execute();
    $sehirler = $sth->fetchAll(PDO::FETCH_ASSOC);
    
    ob_clean();
    echo json_encode($sehirler);
    die();

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
    die();
}
