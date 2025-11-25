<?php

if(file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    require_once '../vendor/autoload.php';
}

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;

class DreamHostStorage {
    private $conn;
    private $ftpServer = "ams1-shared-01.dreamhost.com";
    private $ftpUsername = "dh_ydctig";
    private $ftpPassword = "535272Pars";
    private $s3AccessKey = '00525dc9b1bab480000000002';
    private $s3SecretKey = 'K005amNxCvqHHbDkjQ8bTw7MzQwzitQ';
    private $s3Endpoint = 'https://s3.us-east-005.dream.io';
    private $s3Region = 'us-east-1';
    private $firmaAyarlari = null;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->loadFirmaAyarlari();
    }

    /**
     * Firma storage ayarlarını yükle
     */
    private function loadFirmaAyarlari() {
        if (isset($_SESSION['firma_id'])) {
            $sth = $this->conn->prepare('SELECT dosya_depolama_tipi, s3_endpoint, s3_region, s3_access_key, s3_secret_key, s3_bucket FROM firmalar WHERE id = :firma_id');
            $sth->execute(['firma_id' => $_SESSION['firma_id']]);
            $this->firmaAyarlari = $sth->fetch(PDO::FETCH_ASSOC);
            
            // Firma S3 ayarları varsa, default ayarları override et
            if ($this->firmaAyarlari && $this->firmaAyarlari['dosya_depolama_tipi'] == 's3') {
                if (!empty($this->firmaAyarlari['s3_endpoint'])) {
                    $this->s3Endpoint = $this->firmaAyarlari['s3_endpoint'];
                }
                if (!empty($this->firmaAyarlari['s3_region'])) {
                    $this->s3Region = $this->firmaAyarlari['s3_region'];
                }
                if (!empty($this->firmaAyarlari['s3_access_key'])) {
                    $this->s3AccessKey = $this->firmaAyarlari['s3_access_key'];
                }
                if (!empty($this->firmaAyarlari['s3_secret_key'])) {
                    $this->s3SecretKey = $this->firmaAyarlari['s3_secret_key'];
                }
            }
        }
    }

    /**
     * Depolama tipini kontrol et (local veya s3)
     */
    private function isLocalStorage(): bool {
        return $this->firmaAyarlari && $this->firmaAyarlari['dosya_depolama_tipi'] == 'local';
    }

    public function uploadFileToFtp($modul, $sourceFile, $fileName): bool {
        // FTP ayarlarını veritabanından çek
        $sth = $this->conn->prepare('SELECT * FROM ftp_ayarlar WHERE modul = :modul');
        $sth->execute(['modul' => $modul]);
        $ftpAyar = $sth->fetch(PDO::FETCH_ASSOC);

        // Veritabanından ayarları kontrol et
        if (!$ftpAyar) {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = 'FTP ayarları veritabanında bulunamadı';
            return false;
        }

        $targetDir = $ftpAyar["hedef_klasor"];
        $connId = @ftp_connect($this->ftpServer);
        if ($connId === false) {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = "Güvenli bağlantı kurulamadı: {$this->ftpServer}";
            return false;
        }

        try {
            // Kullanıcı adı ve şifre ile oturum aç
            if (!@ftp_login($connId, $this->ftpUsername, $this->ftpPassword)) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = "FTP oturum açma başarısız.";
                return false;
            }

            // Pasif modu etkinleştir
            if (!ftp_pasv($connId, true)) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = "Pasif mod etkinleştirilemedi.";
                return false;
            }

            // Hedef klasörün varlığını kontrol et ve yoksa oluştur
            $pathParts = explode("/", $targetDir);
            $currentPath = "";
            foreach ($pathParts as $part) {
                if (empty($part)) continue;
                $currentPath .= ($currentPath ? "/" : "") . $part;

                // Klasörün varlığını kontrol et
                $dirExists = false;
                $fileList = @ftp_nlist($connId, $currentPath ? $currentPath : ".");
                if ($fileList !== false) {
                    foreach ($fileList as $file) {
                        $newFiles = explode('/', $file);
                        foreach ($newFiles as $newFile) {
                            if ($newFile == $part) {
                                $dirExists = true;
                                break;
                            }
                        }
                    }
                }

                // Klasör yoksa oluştur
                if (!$dirExists) {
                    if (!@ftp_mkdir($connId, $currentPath)) {
                        $_SESSION['durum'] = 'error';
                        $_SESSION['mesaj'] = "Klasör oluşturulamadı: $currentPath";
                        return false;
                    }
                    if (!@ftp_chmod($connId, 0755, $currentPath)) {
                        $_SESSION['durum'] = 'error';
                        $_SESSION['mesaj'] = "Klasör izinleri ayarlanamadı: $currentPath";
                        return false;
                    }
                }
            }

            $targetFile = $targetDir . "/" . $fileName;

            if (!@ftp_put($connId, $targetFile, $sourceFile, FTP_BINARY)) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = "Dosya FTP'ye yüklenemedi: $sourceFile -> $targetFile";
                return false;
            }
            if (!@ftp_chmod($connId, 0644, $targetFile)) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = "Dosya izinleri ayarlanamadı: $targetFile";
                return false;
            }
        } catch (Exception $e) {
            ftp_close($connId);
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = "Bağlantı kapatıldı (hata sonrası): " . $e->getMessage();
            return false;
        }

        ftp_close($connId);
        return true;
    }

    public function uploadFileToS3($modul, $sourceFile, $fileName): bool {
        try {
            // Validate source file
            if (empty($sourceFile) || !file_exists($sourceFile)) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Kaynak dosya bulunamadı veya boş';
                return false;
            }

            // Veritabanından bucket ve hedef klasör bilgisini çek
            $sth = $this->conn->prepare('SELECT bucket, hedef_klasor, kaynak_klasor FROM ftp_ayarlar WHERE modul = :modul');
            $sth->execute(['modul' => $modul]);
            $ftpAyar = $sth->fetch(PDO::FETCH_ASSOC);

            if (!$ftpAyar) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Modül ayarları veritabanında bulunamadı';
                return false;
            }

            $targetFolder = $ftpAyar['hedef_klasor'] ?? '';
            
            // LOCAL STORAGE: Dosyayı yerel sunucuya kaydet
            if ($this->isLocalStorage()) {
                $localPath = 'dosyalar/' . ($targetFolder ? $targetFolder . '/' : '');
                
                // Klasör yoksa oluştur
                if (!is_dir($localPath)) {
                    mkdir($localPath, 0755, true);
                }
                
                $targetPath = $localPath . $fileName;
                
                // Dosyayı kopyala
                if (move_uploaded_file($sourceFile, $targetPath) || copy($sourceFile, $targetPath)) {
                    return true;
                } else {
                    $_SESSION['durum'] = 'error';
                    $_SESSION['mesaj'] = 'Dosya local sunucuya kaydedilemedi';
                    return false;
                }
            }

            // S3 STORAGE: Dosyayı S3'e yükle
            if (empty($ftpAyar['bucket'])) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Bucket bilgisi veritabanında bulunamadı';
                return false;
            }

            $bucket = $ftpAyar['bucket'];
            $key = $targetFolder ? $targetFolder . '/' . $fileName : $fileName;

            // S3 client oluştur
            $s3 = new S3Client([
                'version'     => 'latest',
                'region'      => $this->s3Region,
                'endpoint'    => $this->s3Endpoint,
                'credentials' => [
                    'key'     => $this->s3AccessKey,
                    'secret'  => $this->s3SecretKey,
                ],
            ]);

            // Bucket'ın varlığını kontrol et
            try {
                $s3->headBucket(['Bucket' => $bucket]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                if ($e->getAwsErrorCode() === 'NotFound' || $e->getStatusCode() === 404) {
                    // Bucket yoksa oluştur
                    try {
                        $s3->createBucket(['Bucket' => $bucket]);
                        // Bucket'ın oluşturulmasını bekle
                        $s3->waitUntil('BucketExists', ['Bucket' => $bucket]);
                    } catch (\Exception $createEx) {
                        $_SESSION['durum'] = 'error';
                        $_SESSION['mesaj'] = "Bucket oluşturulamadı: " . $createEx->getMessage();
                        return false;
                    }
                } else {
                    $_SESSION['durum'] = 'error';
                    $_SESSION['mesaj'] = "Bucket kontrolü başarısız: " . $e->getMessage();
                    return false;
                }
            }

            // Dosyayı yükle
            $client = new Client();
            $request = new Request('PUT', "{$this->s3Endpoint}/{$bucket}/{$key}", [], fopen($sourceFile, 'r'));
            $signer = new SignatureV4('s3', $this->s3Region);
            $signedRequest = $signer->signRequest($request, new Credentials($this->s3AccessKey, $this->s3SecretKey));
            $response = $client->send($signedRequest);

            $_SESSION['durum'] = 'success';
            $_SESSION['mesaj'] = "Dosya başarıyla yüklendi";
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = "Dosya yükleme hatası: " . $e->getMessage();
            return false;
        }
    }

    public function getFileFromS3($modul, $fileName): string {
        // Veritabanından bucket ve hedef klasör bilgisini çek
        $sth = $this->conn->prepare('SELECT bucket, hedef_klasor FROM ftp_ayarlar WHERE modul = :modul');
        $sth->execute(['modul' => $modul]);
        $ftpAyar = $sth->fetch(PDO::FETCH_ASSOC);

        if (!$ftpAyar) {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = 'Modül ayarları veritabanında bulunamadı';
            return '';
        }

        $hedef_klasor = $ftpAyar['hedef_klasor'] ?? '';

        // LOCAL STORAGE: Yerel dosya yolunu döndür
        if ($this->isLocalStorage()) {
            $localPath = 'dosyalar/' . ($hedef_klasor ? $hedef_klasor . '/' : '') . $fileName;
            return '/' . $localPath;
        }

        // S3 STORAGE: Presigned URL oluştur
        if (empty($ftpAyar['bucket'])) {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = 'Bucket bilgisi veritabanında bulunamadı';
            return '';
        }

        $bucket = $ftpAyar['bucket'];

        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => $this->s3Region,
            'endpoint'    => $this->s3Endpoint,
            'credentials' => [
                'key'     => $this->s3AccessKey,
                'secret'  => $this->s3SecretKey,
            ],
        ]);

        $expires = '+5 minutes';
        $command = $s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key'    => $hedef_klasor.'/'.$fileName,
        ]);

        $request = $s3->createPresignedRequest($command, $expires);
        return htmlspecialchars((string) $request->getUri());
    }

    public function deleteFileFromS3($modul, $fileName): bool {
        try {
            // Veritabanından bucket ve hedef klasör bilgisini çek
            $sth = $this->conn->prepare('SELECT bucket, hedef_klasor FROM ftp_ayarlar WHERE modul = :modul');
            $sth->execute(['modul' => $modul]);
            $ftpAyar = $sth->fetch(PDO::FETCH_ASSOC);

            if (!$ftpAyar) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Modül ayarları veritabanında bulunamadı';
                return false;
            }

            $targetFolder = $ftpAyar['hedef_klasor'] ?? '';

            // LOCAL STORAGE: Yerel dosyayı sil
            if ($this->isLocalStorage()) {
                $localPath = 'dosyalar/' . ($targetFolder ? $targetFolder . '/' : '') . $fileName;
                if (file_exists($localPath)) {
                    return unlink($localPath);
                }
                return true; // Dosya zaten yok, başarılı say
            }

            // S3 STORAGE: S3'ten dosyayı sil
            if (empty($ftpAyar['bucket'])) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Bucket bilgisi veritabanında bulunamadı';
                return false;
            }

            $bucket = $ftpAyar['bucket'];
            $key = $targetFolder ? $targetFolder . '/' . $fileName : $fileName;

            // S3 client oluştur
            $s3 = new S3Client([
                'version'     => 'latest',
                'region'      => $this->s3Region,
                'endpoint'    => $this->s3Endpoint,
                'credentials' => [
                    'key'     => $this->s3AccessKey,
                    'secret'  => $this->s3SecretKey,
                ],
            ]);

            // Dosyanın varlığını kontrol et
            try {
                $s3->headObject(['Bucket' => $bucket, 'Key' => $key]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                if ($e->getStatusCode() === 404) {
                    $_SESSION['durum'] = 'error';
                    $_SESSION['mesaj'] = "Silinecek dosya bulunamadı: $key";
                    return false;
                }
                throw $e;
            }

            // Dosyayı sil
            $result = $s3->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            // Silme işleminin başarılı olup olmadığını kontrol et
            if ($result['DeleteMarker'] ?? false) {
                $_SESSION['durum'] = 'success';
                $_SESSION['mesaj'] = "Dosya başarıyla silindi: $key";
                return true;
            } else {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = "Dosya silinirken hata oluştu";
                return false;
            }
        } catch (\Exception $e) {
            $_SESSION['durum'] = 'error';
            $_SESSION['mesaj'] = "Dosya silme hatası: " . $e->getMessage();
            return false;
        }
    }
    public function deleteOldBackupsFromS3($modul): bool { 
        try {
            // Veritabanından bucket ve hedef klasör bilgisini çek
            $sth = $this->conn->prepare('SELECT bucket, hedef_klasor FROM ftp_ayarlar WHERE modul = :modul');
            $sth->execute(['modul' => $modul]);
            $ftpAyar = $sth->fetch(PDO::FETCH_ASSOC);

            if (!$ftpAyar || empty($ftpAyar['bucket'])) {
                $_SESSION['durum'] = 'error';
                $_SESSION['mesaj'] = 'Bucket bilgisi veritabanında bulunamadı';
                return false;
            }

            $bucket = $ftpAyar['bucket'];
            $targetFolder = $ftpAyar['hedef_klasor'] ?? '';

            // S3 client oluştur
            $s3 = new S3Client([
                'version'     => 'latest',
                'region'      => $this->s3Region,
                'endpoint'    => $this->s3Endpoint,
                'credentials' => [
                    'key'     => $this->s3AccessKey,
                    'secret'  => $this->s3SecretKey,
                ],
            ]);

            $cutoffDate = new DateTime('now', new DateTimeZone('+0300'));
            $cutoffDate->modify('-7 days');

            // S3 bucket'taki dosyaları listele
            $objects = $s3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $targetFolder ? $targetFolder . '/' : '', // Klasör filtresi
            ]);

            $deletedCount = 0;
            $errors = [];

            // Dosyaları kontrol et ve eski olanları sil
            foreach ($objects['Contents'] as $object) {
                $lastModified = new DateTime($object['LastModified']);
                if ($lastModified < $cutoffDate) {
                    $fileName = basename($object['Key']);
                    $success = $this->deleteFileFromS3($modul, $fileName);
                    if ($success) {
                        $deletedCount++;
                    } else {
                        $errors[] = $_SESSION['mesaj'];
                    }
                }
            }

            // Sonuçları raporla
            // if ($deletedCount > 0) {
            //     $_SESSION['durum'] = 'success';
            //     $_SESSION['mesaj'] = "$deletedCount adet eski yedek başarıyla silindi.";
            // } elseif (empty($errors)) {
            //     $_SESSION['durum'] = 'info';
            //     $_SESSION['mesaj'] = 'Bir haftadan eski yedek bulunamadı.';
            // } else {
            //     $_SESSION['durum'] = 'error';
            //     $_SESSION['mesaj'] = 'Bazı dosyalar silinirken hata oluştu: ' . implode(', ', $errors);
            // }

            return empty($errors);
        } catch (\Exception $e) {
            // $_SESSION['durum'] = 'error';
            // $_SESSION['mesaj'] = 'Eski yedekler silinirken hata: ' . $e->getMessage();
            return false;
        }
    } 
}
?>