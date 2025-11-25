<?php
/**
 * Customers/Müşteriler Endpoint
 */

switch($method) {
    case "GET":
        if ($id) {
            // Tek müşteri getir
            $stmt = $conn->prepare("SELECT * FROM musteri WHERE id = ? AND firma_id = ?");
            $stmt->execute([$id, $firma_id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                sendError("Müşteri bulunamadı", 404);
            }
            
            sendResponse([
                "success" => true,
                "data" => $customer
            ]);
        } else {
            // Müşteri listesi
            $limit = $_GET["limit"] ?? 50;
            $offset = $_GET["offset"] ?? 0;
            $search = $_GET["search"] ?? null;
            
            $limit = (int)$limit;
            $offset = (int)$offset;
            
            $sql = "SELECT * FROM musteri WHERE firma_id = ?";
            $params = [$firma_id];
            
            if ($search) {
                $sql .= " AND (firma_unvani LIKE ? OR marka LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Toplam sayı
            $countSql = "SELECT COUNT(*) as total FROM musteri WHERE firma_id = ?";
            $countParams = [$firma_id];
            if ($search) {
                $countSql .= " AND (firma_unvani LIKE ? OR marka LIKE ?)";
                $countParams[] = "%$search%";
                $countParams[] = "%$search%";
            }
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch()["total"];
            
            sendResponse([
                "success" => true,
                "data" => $customers,
                "pagination" => [
                    "total" => $total,
                    "limit" => $limit,
                    "offset" => $offset,
                    "count" => count($customers)
                ]
            ]);
        }
        break;
        
    case "POST":
        // Yeni müşteri ekle
        if (!isset($input["firma_unvani"])) {
            sendError("firma_unvani gerekli");
        }
        
        $stmt = $conn->prepare("
            INSERT INTO musteri (
                firma_id, firma_unvani, marka, yetkili_adi, telefon, 
                email, adres, vergi_dairesi, vergi_no, durum, tarih
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $stmt->execute([
            $firma_id,
            $input["firma_unvani"],
            $input["marka"] ?? null,
            $input["yetkili_adi"] ?? null,
            $input["telefon"] ?? null,
            $input["email"] ?? null,
            $input["adres"] ?? null,
            $input["vergi_dairesi"] ?? null,
            $input["vergi_no"] ?? null
        ]);
        
        $new_id = $conn->lastInsertId();
        
        sendResponse([
            "success" => true,
            "message" => "Müşteri eklendi",
            "id" => $new_id
        ], 201);
        break;
        
    case "PUT":
        // Müşteri güncelle
        if (!$id) {
            sendError("Müşteri ID gerekli");
        }
        
        $updates = [];
        $params = [];
        
        $allowed = ["firma_unvani", "marka", "yetkili_adi", "telefon", "email", "adres", "vergi_dairesi", "vergi_no"];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            sendError("Güncellenecek alan yok");
        }
        
        $params[] = $id;
        $params[] = $firma_id;
        
        $sql = "UPDATE musteri SET " . implode(", ", $updates) . " WHERE id = ? AND firma_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        sendResponse([
            "success" => true,
            "message" => "Müşteri güncellendi",
            "affected_rows" => $stmt->rowCount()
        ]);
        break;
        
    case "DELETE":
        // Müşteri sil (soft delete)
        if (!$id) {
            sendError("Müşteri ID gerekli");
        }
        
        $stmt = $conn->prepare("UPDATE musteri SET durum = 0 WHERE id = ? AND firma_id = ?");
        $stmt->execute([$id, $firma_id]);
        
        sendResponse([
            "success" => true,
            "message" => "Müşteri silindi"
        ]);
        break;
        
    default:
        sendError("Desteklenmeyen method: " . $method, 405);
}
