<?php
date_default_timezone_set('Europe/Istanbul');
$path = "/home/panelhankasys/public_html/";
$tarih = date('Y-m-d');

file_put_contents($path."cron/kur_log.log", "[".date('d-m-Y H:i:s')."] Cron Çalıştı\n", FILE_APPEND);
//file_put_contents(__DIR__."/cron/log-{$tarih}.log", $_SERVER["DOCUMENT_ROOT"]."\n", FILE_APPEND);

#veritabanı bağlantı
try{    
    $conn = new PDO(
        'mysql:host=195.201.104.228;port=3306;dbname=panelhankasys_mes;charset=utf8mb4',
        'panelhankasys_pars',
        '+fAy9+]CoCe.',
        [
            PDO::ATTR_DEFAULT_FETCH_MODE    =>  PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE               =>  PDO::ERRMODE_EXCEPTION,
        ]
    );
}catch (PDOException $e){
    file_put_contents($path."cron/kur_log.log", "[".date('d-m-Y H:i:s')."] Veritabanı bağlantı sorunu\n", FILE_APPEND);
    exit;
}




$sql = 'SELECT * FROM `kur`  WHERE tarih = :tarih';
$sth = $conn->prepare($sql);
$sth->bindParam("tarih", $tarih);
$sth->execute();
$para = $sth->fetch(PDO::FETCH_ASSOC);

if(!empty($para)){
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    echo json_encode(['mesaj'=>$tarih.' tarihli kur zaten çekilmiş']); 
    exit;
}

$sql = 'SELECT email FROM `firmalar`  WHERE id = 1';
$sth = $conn->prepare($sql);
$sth->execute();
$firma = $sth->fetch(PDO::FETCH_OBJ);



$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL             => 'https://www.tcmb.gov.tr/kurlar/today.xml',
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_ENCODING        => '',
    CURLOPT_MAXREDIRS       => 10,
    CURLOPT_TIMEOUT         => 0,
    CURLOPT_FOLLOWLOCATION  => true,
    CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST   => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);
$data = new SimpleXMLElement($response);


if(isset($data->Currency)){
    $dollar = 0;
    $euro   = 0;
    $pound  = 0;
    foreach ($data->Currency as $Currency) {
        if($Currency->attributes()->Kod == 'USD')       $dollar = $Currency->ForexSelling;
        else if($Currency->attributes()->Kod == 'EUR')  $euro   = $Currency->ForexSelling;
        else if($Currency->attributes()->Kod == 'GBP')  $pound  = $Currency->ForexSelling;
    }

    $sql = "INSERT INTO kur(dollar, euro, pound, tarih) 
        VALUES(:dollar, :euro, :pound, :tarih);";
    $sth = $conn->prepare($sql);
    $sth->bindParam("dollar", $dollar);
    $sth->bindParam("euro", $euro);
    $sth->bindParam("pound", $pound);
    $sth->bindParam("tarih", $tarih);
    $durum = $sth->execute();

    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    echo json_encode(['mesaj'=>$tarih.' tarihli kur çekildi']); 
}



if($durum){
    $to      = $firma->email;
    $subject = 'KUR ÇEKME';
    $message = $tarih.' tarihinden kur çekildi';
    $headers = "From: {$firma->email}" . "\r\n" .
        "Reply-To: {$firma->email}" . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    //mail($to, $subject, $message, $headers);
}


