<?php
require_once "../include/db.php";
require_once "../include/helper.php";

try { 
    $storage = new DreamHostStorage($conn);

    // FTP bağlantı bilgileri
    // $ftp_server   = "ams1-shared-01.dreamhost.com"; // DreamHost FTP adresi
    // $ftp_username = "dh_ydctig";                 // FTP kullanıcı adı
    // $ftp_password = "535272Pars";                // FTP şifresi

    // FTP ayarlarını veritabanından çek
    $sth = $conn->prepare("SELECT * FROM ftp_ayarlar WHERE modul = 'yedek'");
    $sth->execute();
    $ftp_ayar = $sth->fetch(PDO::FETCH_ASSOC);

    // Veritabanından ayarları kontrol et
    // if (!$ftp_ayar) {
    //     $error_msg = "FTP ayarları veritabanında bulunamadı.";
    //     write_log($error_msg, $log_file);
    //     throw new Exception($error_msg);
    // }

    // Klasör yolları
     $source_dir = $ftp_ayar["kaynak_klasor"];   // Yerel kaynak klasör
    // $target_dir = $ftp_ayar["hedef_klasor"];    // FTP üzerindeki hedef klasör
    $log_file = "ftp_operations.log";           // Log dosyası

    // Log yazma fonksiyonu
    function write_log($message, $log_file) {
        $timestamp = date("Y-m-d H:i:s");
        $log_message = "[$timestamp] $message\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    // Kaynak klasörün varlığını kontrol et
    // if (!is_dir($source_dir)) {
    //     $error_msg = "Kaynak klasör bulunamadı: $source_dir";
    //     write_log($error_msg, $log_file);
    //     throw new Exception($error_msg);
    // }

    // FTP bağlantısı
    // $conn_id = @ftp_connect($ftp_server);
    // if ($conn_id === false) {
    //     $error_msg = "Güvenli bağlantı kurulamadı: $ftp_server";
    //     write_log($error_msg, $log_file);
    //     throw new Exception($error_msg);
    // }
    //write_log("FTP bağlantısı kuruldu: $ftp_server", $log_file);

    try {
        // Kullanıcı adı ve şifre ile oturum aç
        // if (!@ftp_login($conn_id, $ftp_username, $ftp_password)) {
        //     $error_msg = "FTP oturum açma başarısız.";
        //     write_log($error_msg, $log_file);
        //     throw new Exception($error_msg);
        // }
        //write_log("FTP oturum açma başarılı.", $log_file);

        // Pasif modu etkinleştir
        // if (!ftp_pasv($conn_id, true)) {
        //     $error_msg = "Pasif mod etkinleştirilemedi.";
        //     write_log($error_msg, $log_file);
        //     throw new Exception($error_msg);
        // }
        //write_log("Pasif mod etkinleştirildi.", $log_file);

        // Hedef klasörün varlığını kontrol et ve yoksa oluştur
        // $path_parts = explode("/", $target_dir); // Hedef klasörü ayır
        // $current_path = "";
        // foreach ($path_parts as $part) {
        //     if (empty($part)) continue; // Boş yol parçalarını atla
        //     $current_path .= ($current_path ? "/" : "") . $part;

        //     // Klasörün varlığını kontrol et
        //     $dir_exists = false;
        //     $file_list = @ftp_nlist($conn_id, $current_path ? $current_path : ".");
        //     if ($file_list !== false) {
        //         foreach ($file_list as $file) {
        //             if ($file == $part || $file == $current_path) {
        //                 $dir_exists = true;
        //                 break;
        //             }
        //         }
        //     }

        //     // Klasör yoksa oluştur
        //     if (!$dir_exists) {
        //         if (!@ftp_mkdir($conn_id, $current_path)) {
        //             $error_msg = "Klasör oluşturulamadı: $current_path";
        //             write_log($error_msg, $log_file);
        //             throw new Exception($error_msg);
        //         }
        //         write_log("Klasör oluşturuldu: $current_path", $log_file);
        //         if (!@ftp_chmod($conn_id, 0755, $current_path)) {
        //             $error_msg = "Klasör izinleri ayarlanamadı: $current_path";
        //             write_log($error_msg, $log_file);
        //             throw new Exception($error_msg);
        //         }
        //     }
        // }

        // Yerel kaynak klasördeki .sql dosyalarını listele
        $files = array_diff(scandir($source_dir), array('.', '..')); // . ve .. hariç tüm dosyaları al
        $sql_files = array_filter($files, function($file) use ($source_dir) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'sql'; // Sadece .sql uzantılı dosyaları filtrele
        });

        if (empty($sql_files)) {
            $msg = "Yerel kaynak klasörde .sql dosyası bulunamadı: $source_dir";
            write_log($msg, $log_file);
            //echo $msg . "\n";
        } else {
            foreach ($sql_files as $file) {
                $file_name = basename($file);
                $source_file = $source_dir . DIRECTORY_SEPARATOR . $file_name; // Yerel dosya yolu
                //$target_file = $target_dir . "/" . $file_name; // FTP hedef dosya yolu (örneğin: GULERMAT/YEDEKLER/dosya.sql)

                $result = $storage->uploadFileToS3('yedek', $source_file, $file_name);

                // Dosyayı FTP'ye yükle
                // if (!file_exists($source_file)) {
                //     $error_msg = "Yerel dosya bulunamadı: $source_file";
                //     write_log($error_msg, $log_file);
                //     continue;
                // }

                // if (!@ftp_put($conn_id, $target_file, $source_file, FTP_BINARY)) {
                //     $error_msg = "Dosya FTP'ye yüklenemedi: $source_file -> $target_file";
                //     write_log($error_msg, $log_file);
                //     continue;
                // }
                // write_log("Dosya kopyalandı: $source_file -> $target_file", $log_file);
                // //echo "Dosya kopyalandı: $target_file\n";

                // // Dosya izinlerini ayarla
                // if (!@ftp_chmod($conn_id, 0644, $target_file)) {
                //     $error_msg = "Dosya izinleri ayarlanamadı: $target_file";
                //     write_log($error_msg, $log_file);
                // }

                // Yerel kaynak dosyayı sil
                if($result){
                    if (!@unlink($source_file)) {
                        $error_msg = "Yerel dosya silinemedi: $source_file";
                    write_log($error_msg, $log_file);
                    } else { 
                        write_log("Yerel dosya silindi: $source_file", $log_file);
                        //echo "Dosya silindi: $source_file\n";
                    }
                } 
            }
            $storage->deleteOldBackupsFromS3('yedek');
        }
    } catch (Exception $e) {
        // İç try-catch bloğunda hata olursa bağlantıyı kapat ve hatayı fırlat
        //ftp_close($conn_id);
        write_log("Bağlantı kapatıldı (hata sonrası).", $log_file);
        throw $e;
    }

    // Bağlantıyı kapat
    //ftp_close($conn_id);
    //write_log("Bağlantı kapatıldı.", $log_file);
} catch (Exception $e) {
    // Hata mesajını göster
    $error_msg = "Hata: " . $e->getMessage();
    //write_log($error_msg, $log_file);
    echo $error_msg . "\n";
}
?>