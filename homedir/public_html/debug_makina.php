<?php
session_start();
require_once 'include/db.php';

echo '<h3>SESSION Bilgileri:</h3><pre>';
print_r($_SESSION);
echo '</pre><hr>';

$makina_id = 2;
echo '<h3>Makina ID: ' . $makina_id . ' için Kontrol:</h3>';

if(isset($_SESSION['firma_id']) && isset($_SESSION['personel_id'])) {
    $sql = "SELECT makinalar.*, mp.personel_id 
            FROM makinalar 
            LEFT JOIN makina_personeller mp ON mp.makina_id = makinalar.id AND mp.personel_id = :personel_id
            WHERE makinalar.id = :makina_id AND makinalar.firma_id = :firma_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->bindParam('makina_id', $makina_id);
    $sth->bindParam('personel_id', $_SESSION['personel_id']);
    $sth->execute();
    $result = $sth->fetch(PDO::FETCH_ASSOC);
    
    echo '<pre>';
    print_r($result);
    echo '</pre>';
    
    echo '<hr><h3>makina_is_listesi.php SQL Sorgusu:</h3>';
    $sql2 = "SELECT makinalar.makina_adi, makinalar.makina_modeli, makinalar.form_tipi 
            FROM makinalar 
            JOIN makina_personeller ON makina_personeller.makina_id = makinalar.id
            WHERE makinalar.firma_id = :firma_id 
            AND makinalar.id = :id 
            AND makinalar.durumu = 'aktif' 
            AND makina_personeller.personel_id = :personel_id";
    $sth2 = $conn->prepare($sql2);
    $sth2->bindParam('firma_id', $_SESSION['firma_id']);
    $sth2->bindParam('id', $makina_id);
    $sth2->bindParam('personel_id', $_SESSION['personel_id']);
    $sth2->execute();
    $makina = $sth2->fetch(PDO::FETCH_ASSOC);
    
    echo '<pre>';
    echo 'Sonuç: ';
    print_r($makina);
    echo '</pre>';
    
    if(empty($makina)) {
        echo '<div style="color:red; font-weight:bold;">Makina bulunamadı veya yetkiniz yok!</div>';
    }
} else {
    echo '<div style="color:red; font-weight:bold;">SESSION firma_id veya personel_id bulunamadı!</div>';
}
?>
