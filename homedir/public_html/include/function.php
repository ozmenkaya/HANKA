<?php 

require_once "sabitler.php";
function yetkiAdi($yetki_id){
    if($yetki_id == SUPER_ADMIN_YETKI_ID)   return "SÜPER ADMİN";
    if($yetki_id == ADMIN_YETKI_ID)         return "ADMİN";
    if($yetki_id == MUSTERI_TEMSILCI_ID)    return "MÜŞTERİ TEMSİLCİSİ";
    if($yetki_id == SATIS_TEMSILCI_ID)      return "SATIŞ TEMSİLCİSİ";
    if($yetki_id == PAZARLAMACI_ID)         return "PAZARLAMA";
    if($yetki_id == PERSONEL_ID)            return "PERSONEL";
    if($yetki_id == URETIM_YETKI_ID)        return "ÜRETİM";
    if($yetki_id == URETIM_AMIRI_ID)        return "ÜRETİM AMIRI";
    if($yetki_id == MAKINE_BAKIM_ID)        return "MAKİNA BAKIMCI";
    if($yetki_id == PLANLAMA_ID)            return "PLANLAMACI";
    return "-";
}

function uuid4() {
    $out = bin2hex(random_bytes(18));
    $out[8]  = "-";
    $out[13] = "-";
    $out[18] = "-";
    $out[23] = "-";
    $out[14] = "4";
    $out[19] = ["8", "9", "a", "b"][random_int(0, 3)];
    return $out;
}

function getBaseUrl() 
{
    $host = $_SERVER['HTTP_HOST'];
    $hostName       = $_SERVER['HTTP_HOST']; 

    if(isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    }
    else{
        $protocol = 'http';
    }
    return $protocol.'://'.$hostName;
}

function getUserIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}


function getBrowser() { 
    $u_agent    = $_SERVER['HTTP_USER_AGENT'];
    $bname      = 'App';
    $platform   = 'Bilinmiyor';
    $version    = "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'Linux';
    }elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'Mac OS';
    }elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'Windows';
    }elseif (preg_match('/Android/i', $u_agent)) {
        $platform = 'Android';
    }
    $ub = '';
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)){
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    }elseif(preg_match('/Firefox/i',$u_agent)){
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    }elseif(preg_match('/OPR/i',$u_agent)){
        $bname = 'Opera';
        $ub = "Opera";
    }elseif(preg_match('/Edg/i',$u_agent)){
        $bname = 'Edge';
        $ub = "Edge";
    }elseif(preg_match('/Chrome/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
        $bname = 'Google Chrome';
        $ub = "Chrome";
    }elseif(preg_match('/Safari/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
        $bname = 'Apple Safari';
        $ub = "Safari";
    }elseif(preg_match('/Netscape/i',$u_agent)){
        $bname = 'Netscape';
        $ub = "Netscape";
    }elseif(preg_match('/Trident/i',$u_agent)){
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
      // we have no matching number just continue
    }
    
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
      //we will have two since we are not using 'other' argument yet
      //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= isset($matches['version'][0]) ? $matches['version'][0] : '?';
        }else {
            $version= isset($matches['version'][1]) ? $matches['version'][1]: '?';
        }
    }else {
        $version= $matches['version'][0];
    }

    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        //'pattern'   => $pattern
    );
} 

function secondToHHMMSS($seconds){
    //echo $seconds."<br>";
    $hours      = floor($seconds / 3600);
    $minutes    = floor(($seconds % 3600) / 60);
    $seconds    = $seconds % 60;

    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

function timeToSeconds($time) {
    list($hours, $minutes, $seconds) = explode(":", $time);
    return $hours * 3600 + $minutes * 60 + $seconds;
}

// Convert seconds to "hh:mm:ss" format
function secondsToTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

function gorunmeyen_geri_bildirim_sayisi(){
    global $conn;
    $sql = 'SELECT *
        FROM geri_bildirim
        WHERE ust_id = 0 ';
    if($_SESSION['yetki_id'] != SUPER_ADMIN_YETKI_ID){
        $sql .= " AND kimden = :kimden";
    }
    $sql .= " ORDER BY id DESC";
    $sth = $conn->prepare($sql);
    if($_SESSION['yetki_id'] != SUPER_ADMIN_YETKI_ID){
        $sth->bindParam('kimden', $_SESSION['personel_id']);
    }
    $sth->execute();
    $geri_bildirimler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $toplam_gorunmeyen_mesaj = 0;
    foreach ($geri_bildirimler as $index =>$geri_bildirim) { 
        $sql = 'SELECT geri_bildirim_id FROM `geri_bildirim_gorunum_durumu` 
            WHERE personel_id = :personel_id AND geri_bildirim_ust_id = :geri_bildirim_ust_id
            ORDER BY geri_bildirim_id DESC';
        $sth = $conn->prepare($sql);
        $sth->bindParam('personel_id', $_SESSION['personel_id']);
        $sth->bindParam('geri_bildirim_ust_id', $geri_bildirim['id']);
        $sth->execute();
        $geri_bildirim_gorunum_durum = $sth->fetch(PDO::FETCH_ASSOC);  
        

        $sql = 'SELECT COUNT(id) AS alt_bildirim_sayisi  FROM `geri_bildirim` 
        WHERE ust_id = :ust_id ';
        if(!empty($geri_bildirim_gorunum_durum )){
            $sql .= ' AND id > :id;';
        }
        $sth = $conn->prepare($sql);
        $sth->bindParam('ust_id', $geri_bildirim['id']);
        if(!empty($geri_bildirim_gorunum_durum )){
            $sth->bindParam('id', $geri_bildirim_gorunum_durum['geri_bildirim_id']);
        }
        $sth->execute();
        $alt_geri_bildirim = $sth->fetch(PDO::FETCH_ASSOC);   

        $toplam_gorunmeyen_mesaj += $alt_geri_bildirim['alt_bildirim_sayisi'];
    }
    return $toplam_gorunmeyen_mesaj ;
}