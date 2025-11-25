<?php
// AJAX istekleri için oturum kontrolü
// HTML redirect yerine JSON hata döndürür

if(!isset($_SESSION['giris_kontrol'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'durum' => 'oturum_hatasi',
        'mesaj' => 'Oturumunuz sona erdi. Lütfen sayfayı yenileyip tekrar giriş yapın.'
    ]);
    exit;
}
