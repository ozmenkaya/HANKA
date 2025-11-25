<?php
require_once 'include/oturum_kontrol.php';

// Sadece admin yetkisi kontrolü
if(!in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID, ADMIN_YETKI_ID])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok!']);
    exit;
}

$backup_dir = '/var/backups/db';
$db_host = 'localhost';
$db_user = 'debian-sys-maint';
$db_pass = 'jOkmqRdD1DhBG77E';
$db_name = 'panelhankasys_crm2';

// Yeni yedek oluştur
if(isset($_POST['islem']) && $_POST['islem'] == 'olustur') {
    try {
        // Backup klasörü yoksa oluştur
        if(!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        $command = "mysqldump -h {$db_host} -u {$db_user} -p{$db_pass} {$db_name} > {$backup_file} 2>&1";
        
        exec($command, $output, $return_var);
        
        if($return_var === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Yedek başarıyla oluşturuldu',
                'file' => basename($backup_file),
                'size' => filesize($backup_file)
            ]);
        } else {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Yedek oluşturulamadı: ' . implode("\n", $output)
            ]);
        }
    } catch(Exception $e) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Yedek indir
if(isset($_GET['islem']) && $_GET['islem'] == 'indir' && isset($_GET['dosya'])) {
    $dosya = basename($_GET['dosya']); // Güvenlik için
    $dosya_yolu = $backup_dir . '/' . $dosya;
    
    if(file_exists($dosya_yolu)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $dosya . '"');
        header('Content-Length: ' . filesize($dosya_yolu));
        readfile($dosya_yolu);
        exit;
    } else {
        die('Dosya bulunamadı!');
    }
}

// Yedek sil
if(isset($_POST['islem']) && $_POST['islem'] == 'sil' && isset($_POST['dosya'])) {
    $dosya = basename($_POST['dosya']); // Güvenlik için
    $dosya_yolu = $backup_dir . '/' . $dosya;
    
    if(file_exists($dosya_yolu)) {
        if(unlink($dosya_yolu)) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Yedek silindi']);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Dosya silinemedi']);
        }
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Dosya bulunamadı']);
    }
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
?>
