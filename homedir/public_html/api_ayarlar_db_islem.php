<?php
// API Ayarları Database İşlemleri
require_once "include/db.php";

// Sadece admin ve super admin erişebilir
if(!in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID, ADMIN_YETKI_ID])){
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$action = $_POST['action'] ?? '';
$firma_id = $_SESSION['firma_id'];

try {
    switch($action) {
        case 'create':
            // Yeni API Key oluştur
            $name = $_POST['name'] ?? '';
            $permissions = $_POST['permissions'] ?? '{}';
            
            if(empty($name)) {
                throw new Exception('Key ismi gerekli');
            }
            
            // Benzersiz API key oluştur
            $api_key = bin2hex(random_bytes(32));
            
            $sql = "INSERT INTO api_keys (firma_id, api_key, name, permissions, is_active, created_at) 
                    VALUES (:firma_id, :api_key, :name, :permissions, 1, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'firma_id' => $firma_id,
                'api_key' => $api_key,
                'name' => $name,
                'permissions' => $permissions
            ]);
            
            echo json_encode([
                'success' => true,
                'api_key' => $api_key,
                'message' => 'API key başarıyla oluşturuldu'
            ]);
            break;
            
        case 'toggle_status':
            // Key durumunu değiştir
            $key_id = $_POST['key_id'] ?? 0;
            $status = $_POST['status'] ?? 0;
            
            $sql = "UPDATE api_keys SET is_active = :status WHERE id = :id AND firma_id = :firma_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'status' => $status,
                'id' => $key_id,
                'firma_id' => $firma_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Durum güncellendi'
            ]);
            break;
            
        case 'delete':
            // Key sil
            $key_id = $_POST['key_id'] ?? 0;
            
            $sql = "DELETE FROM api_keys WHERE id = :id AND firma_id = :firma_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'id' => $key_id,
                'firma_id' => $firma_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'API key silindi'
            ]);
            break;
            
        case 'update_permissions':
            // İzinleri güncelle
            $key_id = $_POST['key_id'] ?? 0;
            $permissions = $_POST['permissions'] ?? '{}';
            
            $sql = "UPDATE api_keys SET permissions = :permissions WHERE id = :id AND firma_id = :firma_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'permissions' => $permissions,
                'id' => $key_id,
                'firma_id' => $firma_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'İzinler güncellendi'
            ]);
            break;
            
        default:
            throw new Exception('Geçersiz işlem');
    }
    
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
