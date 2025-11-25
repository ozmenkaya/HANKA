<?php
/**
 * Orders/Siparişler Endpoint
 */

switch($method) {
    case "GET":
        if ($id) {
            // Tek sipariş getir
            $stmt = $conn->prepare("
                SELECT s.*, m.firma_unvani as musteri_adi 
                FROM siparisler s
                LEFT JOIN musteri m ON s.musteri_id = m.id
                WHERE s.id = ? AND s.firma_id = ?
            ");
            $stmt->execute([$id, $firma_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                sendError("Sipariş bulunamadı", 404);
            }
            
            // JSON veriler decode
            if ($order["veriler"]) {
                $order["urunler"] = json_decode($order["veriler"], true);
            }
            
            sendResponse([
                "success" => true,
                "data" => $order
            ]);
        } else {
            // Sipariş listesi
            $limit = $_GET["limit"] ?? 50;
            $offset = $_GET["offset"] ?? 0;
            $musteri_id = $_GET["musteri_id"] ?? null;
            $start_date = $_GET["start_date"] ?? null;
            $end_date = $_GET["end_date"] ?? null;
            
            $sql = "
                SELECT s.*, m.firma_unvani as musteri_adi,
                (
                    (JSON_EXTRACT(s.veriler,'$[0].miktar') * JSON_EXTRACT(s.veriler,'$[0].birim_fiyat')) +
                    (JSON_EXTRACT(s.veriler,'$[1].miktar') * JSON_EXTRACT(s.veriler,'$[1].birim_fiyat')) +
                    (JSON_EXTRACT(s.veriler,'$[2].miktar') * JSON_EXTRACT(s.veriler,'$[2].birim_fiyat')) +
                    (JSON_EXTRACT(s.veriler,'$[3].miktar') * JSON_EXTRACT(s.veriler,'$[3].birim_fiyat')) +
                    (JSON_EXTRACT(s.veriler,'$[4].miktar') * JSON_EXTRACT(s.veriler,'$[4].birim_fiyat'))
                ) as toplam_tutar
                FROM siparisler s
                LEFT JOIN musteri m ON s.musteri_id = m.id
                WHERE s.firma_id = ?
            ";
            $params = [$firma_id];
            
            if ($musteri_id) {
                $sql .= " AND s.musteri_id = ?";
                $params[] = $musteri_id;
            }
            
            if ($start_date) {
                $sql .= " AND s.tarih >= ?";
                $params[] = $start_date;
            }
            
            if ($end_date) {
                $sql .= " AND s.tarih <= ?";
                $params[] = $end_date;
            }
            
            $limit = (int)$limit;
            $offset = (int)$offset;
            $sql .= " ORDER BY s.id DESC LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON decode
            foreach ($orders as &$order) {
                if ($order["veriler"]) {
                    $order["urunler"] = json_decode($order["veriler"], true);
                }
            }
            
            // Toplam sayı
            $countSql = "SELECT COUNT(*) as total FROM siparisler WHERE firma_id = ?";
            $countParams = [$firma_id];
            if ($musteri_id) {
                $countSql .= " AND musteri_id = ?";
                $countParams[] = $musteri_id;
            }
            if ($start_date) {
                $countSql .= " AND tarih >= ?";
                $countParams[] = $start_date;
            }
            if ($end_date) {
                $countSql .= " AND tarih <= ?";
                $countParams[] = $end_date;
            }
            
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch()["total"];
            
            sendResponse([
                "success" => true,
                "data" => $orders,
                "pagination" => [
                    "total" => $total,
                    "limit" => $limit,
                    "offset" => $offset,
                    "count" => count($orders)
                ]
            ]);
        }
        break;
        
    case "POST":
        // Yeni sipariş ekle
        if (!isset($input["musteri_id"]) || !isset($input["urunler"])) {
            sendError("musteri_id ve urunler gerekli");
        }
        
        // Sipariş numarası oluştur
        $stmt = $conn->prepare("SELECT MAX(id) as max_id FROM siparisler WHERE firma_id = ?");
        $stmt->execute([$firma_id]);
        $max_id = $stmt->fetch()["max_id"] ?? 0;
        $siparis_no = "GLR" . str_pad($max_id + 1, 6, "0", STR_PAD_LEFT);
        
        // veriler JSON oluştur (5 ürün olmalı)
        $veriler = [];
        for ($i = 0; $i < 5; $i++) {
            $veriler[] = [
                "miktar" => $input["urunler"][$i]["miktar"] ?? 0,
                "birim_fiyat" => $input["urunler"][$i]["birim_fiyat"] ?? 0,
                "isim" => $input["urunler"][$i]["isim"] ?? ""
            ];
        }
        
        $stmt = $conn->prepare("
            INSERT INTO siparisler (
                firma_id, musteri_id, siparis_no, veriler, 
                adet, fiyat, durum, tarih
            ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
        // adet ve fiyat (eski format için dummy değerler)
        $adet = 1;
        $fiyat = 0;
        
        $stmt->execute([
            $firma_id,
            $input["musteri_id"],
            $siparis_no,
            json_encode($veriler),
            $adet,
            $fiyat
        ]);
        
        $new_id = $conn->lastInsertId();
        
        sendResponse([
            "success" => true,
            "message" => "Sipariş eklendi",
            "id" => $new_id,
            "siparis_no" => $siparis_no
        ], 201);
        break;
        
    case "PUT":
        // Sipariş güncelle
        if (!$id) {
            sendError("Sipariş ID gerekli");
        }
        
        $updates = [];
        $params = [];
        
        if (isset($input["urunler"])) {
            // veriler JSON güncelle
            $veriler = [];
            for ($i = 0; $i < 5; $i++) {
                $veriler[] = [
                    "miktar" => $input["urunler"][$i]["miktar"] ?? 0,
                    "birim_fiyat" => $input["urunler"][$i]["birim_fiyat"] ?? 0,
                    "isim" => $input["urunler"][$i]["isim"] ?? ""
                ];
            }
            $updates[] = "veriler = ?";
            $params[] = json_encode($veriler);
        }
        
        if (isset($input["durum"])) {
            $updates[] = "durum = ?";
            $params[] = $input["durum"];
        }
        
        if (empty($updates)) {
            sendError("Güncellenecek alan yok");
        }
        
        $params[] = $id;
        $params[] = $firma_id;
        
        $sql = "UPDATE siparisler SET " . implode(", ", $updates) . " WHERE id = ? AND firma_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        sendResponse([
            "success" => true,
            "message" => "Sipariş güncellendi",
            "affected_rows" => $stmt->rowCount()
        ]);
        break;
        
    case "DELETE":
        // Sipariş sil (soft delete)
        if (!$id) {
            sendError("Sipariş ID gerekli");
        }
        
        $stmt = $conn->prepare("UPDATE siparisler SET durum = 0 WHERE id = ? AND firma_id = ?");
        $stmt->execute([$id, $firma_id]);
        
        sendResponse([
            "success" => true,
            "message" => "Sipariş silindi"
        ]);
        break;
        
    default:
        sendError("Desteklenmeyen method: " . $method, 405);
}
