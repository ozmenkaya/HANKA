<?php
// header('Content-Type: application/json');
// include "include/db.php";
include "include/oturum_kontrol.php";

if (!isset($_GET['siparis_id'])) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Sipariş ID eksik."]);
    exit;
}

$siparis_id = $_GET['siparis_id'];

// Siparişin üretim aşamalarını al
$sth = $conn->prepare("SELECT detaylar FROM planlama WHERE siparis_id = :siparis_id");
$sth->bindParam(':siparis_id', $siparis_id);
$sth->execute();
$planlama = $sth->fetch(PDO::FETCH_ASSOC);

if (!$planlama) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Sipariş için üretim planı bulunamadı."]);
    exit;
}

$detaylar = json_decode($planlama['detaylar'], true);
if (!is_array($detaylar)) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Geçersiz üretim aşaması verisi."]);
    exit;
}

$oneri = [];

foreach ($detaylar as $asama) {
    if (!isset($asama['departman_id']) || !isset($asama['isim'])) {
        continue; // Hatalı veri varsa atla
    }
    
    $sth = $conn->prepare("SELECT id, makina_adi FROM makinalar WHERE departman_id = :departman_id AND durumu = 'aktif' ORDER BY makina_bakim_suresi ASC LIMIT 1");
    $sth->bindParam(':departman_id', $asama['departman_id']);
    $sth->execute();
    $makine = $sth->fetch(PDO::FETCH_ASSOC);
    
    $oneri[] = [
        "asama_adi" => $asama['isim'],
        "makine" => $makine ? $makine['makina_adi'] : "Uygun makine bulunamadı"
    ];
}

// Makine önerilerini döndür
ob_clean();
echo json_encode([
    "success" => true,
    "oneriler" => $oneri
]);
exit;
