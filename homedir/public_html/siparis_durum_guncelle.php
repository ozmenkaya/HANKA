<?php
require_once "include/oturum_kontrol.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siparis_id = isset($_POST['siparis_id']) ? intval($_POST['siparis_id']) : 0;
    $durum = isset($_POST['durum']) ? intval($_POST['durum']) : 0;
    $firma_id = isset($_POST['firma_id']) ? intval($_POST['firma_id']) : $_SESSION['firma_id'];
    
    if ($siparis_id > 0) {
        try {
            // aktif kolonu güncelleniyor (1=aktif, 0=pasif)
            $sth = $conn->prepare('UPDATE siparisler SET aktif = :aktif WHERE id = :id AND firma_id = :firma_id');
            $sth->bindParam(':aktif', $durum, PDO::PARAM_INT);
            $sth->bindParam(':id', $siparis_id, PDO::PARAM_INT);
            $sth->bindParam(':firma_id', $firma_id, PDO::PARAM_INT);
            
            if ($sth->execute()) {
                echo json_encode(['success' => true, 'message' => 'Sipariş durumu güncellendi']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Güncelleme başarısız']);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz sipariş ID']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}
