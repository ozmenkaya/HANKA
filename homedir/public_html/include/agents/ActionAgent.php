<?php
/**
 * HANKA Action Agent
 * Database işlemleri, email/WhatsApp gönderimi, otomasyonlar
 */

class ActionAgent {
    private $conn;
    private $firma_id;
    
    // Action tipleri
    const ACTION_EMAIL = 'email';
    const ACTION_WHATSAPP = 'whatsapp';
    const ACTION_DATABASE = 'database';
    const ACTION_NOTIFICATION = 'notification';
    
    public function __construct($conn, $firma_id) {
        $this->conn = $conn;
        $this->firma_id = $firma_id;
    }
    
    /**
     * Action çalıştırıcı
     */
    public function execute($params) {
        $action_type = $params['action'] ?? 'unknown';
        
        error_log("⚡ ActionAgent: $action_type çalıştırılıyor");
        
        try {
            switch ($action_type) {
                case 'send_email':
                    return $this->sendEmail($params);
                    
                case 'send_whatsapp':
                    return $this->sendWhatsApp($params);
                    
                case 'create_report':
                    return $this->createReport($params);
                    
                case 'update_stock':
                    return $this->updateStock($params);
                    
                case 'create_order':
                    return $this->createOrder($params);
                    
                case 'send_payment_reminder':
                    return $this->sendPaymentReminder($params);
                    
                case 'notify_low_stock':
                    return $this->notifyLowStock($params);
                    
                default:
                    return $this->customAction($params);
            }
        } catch (Exception $e) {
            error_log("❌ ActionAgent hatası: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'action' => $action_type
            ];
        }
    }
    
    /**
     * Email gönderimi
     */
    private function sendEmail($params) {
        $to = $params['to'] ?? '';
        $subject = $params['subject'] ?? 'HANKA CRM Bildirimi';
        $body = $params['body'] ?? '';
        $from = $params['from'] ?? 'noreply@hankasys.com';
        
        if (empty($to) || empty($body)) {
            throw new Exception("Email parametreleri eksik");
        }
        
        error_log("📧 Email gönderiliyor: $to - $subject");
        
        // PHP mail() kullanımı (basit versiyon)
        // Production'da SMTP kullanılmalı (PHPMailer, SwiftMailer vb.)
        
        $headers = [
            'From' => $from,
            'Reply-To' => $from,
            'X-Mailer' => 'HANKA CRM Agent',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        $headers_string = '';
        foreach ($headers as $key => $value) {
            $headers_string .= "$key: $value\r\n";
        }
        
        $success = mail($to, $subject, $body, $headers_string);
        
        // Log'a kaydet
        $this->logAction(self::ACTION_EMAIL, [
            'to' => $to,
            'subject' => $subject,
            'success' => $success
        ]);
        
        return [
            'success' => $success,
            'action' => 'send_email',
            'to' => $to,
            'summary' => $success ? "Email başarıyla gönderildi" : "Email gönderilemedi"
        ];
    }
    
    /**
     * WhatsApp mesajı gönder
     */
    private function sendWhatsApp($params) {
        $phone = $params['phone'] ?? '';
        $message = $params['message'] ?? '';
        
        if (empty($phone) || empty($message)) {
            throw new Exception("WhatsApp parametreleri eksik");
        }
        
        error_log("💬 WhatsApp gönderiliyor: $phone");
        
        // WhatsApp Business API entegrasyonu
        // Bu örnek için simüle edilmiş
        
        // TODO: Gerçek WhatsApp API entegrasyonu
        // - Twilio WhatsApp API
        // - WhatsApp Business API
        // - 3rd party servisler
        
        $api_url = 'https://api.whatsapp.com/send'; // Placeholder
        
        $data = [
            'phone' => $phone,
            'text' => $message,
            'firma_id' => $this->firma_id
        ];
        
        // Simülasyon için başarılı kabul edelim
        $success = true; // Gerçek API çağrısı yapılacak
        
        // Log'a kaydet
        $this->logAction(self::ACTION_WHATSAPP, [
            'phone' => $phone,
            'message' => substr($message, 0, 100),
            'success' => $success
        ]);
        
        return [
            'success' => $success,
            'action' => 'send_whatsapp',
            'phone' => $phone,
            'summary' => $success ? "WhatsApp mesajı gönderildi" : "WhatsApp gönderilemedi"
        ];
    }
    
    /**
     * Rapor oluştur ve gönder
     */
    private function createReport($params) {
        $report_type = $params['report_type'] ?? 'daily';
        $recipients = $params['recipients'] ?? [];
        $format = $params['format'] ?? 'email'; // email, pdf, excel
        
        error_log("📊 Rapor oluşturuluyor: $report_type");
        
        // AnalyticsAgent ile rapor al
        require_once __DIR__ . '/AnalyticsAgent.php';
        $analytics = new AnalyticsAgent($this->conn, $this->firma_id);
        
        $report_data = $analytics->analyze(['type' => $report_type]);
        
        if (!$report_data['success']) {
            throw new Exception("Rapor oluşturulamadı");
        }
        
        // Raporu formatla
        $formatted_report = $this->formatReport($report_data, $format);
        
        // Gönder
        $sent_count = 0;
        foreach ($recipients as $recipient) {
            if ($format === 'email') {
                $result = $this->sendEmail([
                    'to' => $recipient,
                    'subject' => "HANKA CRM - " . ucfirst($report_type) . " Raporu",
                    'body' => $formatted_report
                ]);
                
                if ($result['success']) {
                    $sent_count++;
                }
            }
        }
        
        return [
            'success' => true,
            'action' => 'create_report',
            'report_type' => $report_type,
            'recipients_count' => count($recipients),
            'sent_count' => $sent_count,
            'summary' => "$sent_count kişiye $report_type raporu gönderildi"
        ];
    }
    
    /**
     * Stok güncelleme
     */
    private function updateStock($params) {
        $urun_id = $params['urun_id'] ?? 0;
        $miktar = $params['miktar'] ?? 0;
        $operation = $params['operation'] ?? 'add'; // add, subtract, set
        
        if ($urun_id <= 0) {
            throw new Exception("Geçersiz ürün ID");
        }
        
        error_log("📦 Stok güncelleniyor: Ürün $urun_id, Miktar $miktar, İşlem: $operation");
        
        // Mevcut stok
        $sql = "SELECT stok_miktar, urun_adi FROM urunler 
                WHERE id = $urun_id AND firma_id = {$this->firma_id}";
        $result = $this->conn->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            throw new Exception("Ürün bulunamadı");
        }
        
        $row = $result->fetch_assoc();
        $current_stock = $row['stok_miktar'];
        $urun_adi = $row['urun_adi'];
        
        // Yeni stok hesapla
        switch ($operation) {
            case 'add':
                $new_stock = $current_stock + $miktar;
                break;
            case 'subtract':
                $new_stock = $current_stock - $miktar;
                break;
            case 'set':
                $new_stock = $miktar;
                break;
            default:
                throw new Exception("Geçersiz işlem: $operation");
        }
        
        // Stok negatif olamaz
        if ($new_stock < 0) {
            throw new Exception("Stok negatif olamaz (mevcut: $current_stock, işlem: -$miktar)");
        }
        
        // Güncelle
        $sql = "UPDATE urunler SET stok_miktar = $new_stock WHERE id = $urun_id";
        $success = $this->conn->query($sql);
        
        // Stok hareketi kaydet
        if ($success) {
            $hareket_tipi = $operation === 'add' ? 'giris' : 'cikis';
            $sql = "INSERT INTO stok_hareketleri (firma_id, urun_id, miktar, hareket_tipi, aciklama, tarih)
                    VALUES ({$this->firma_id}, $urun_id, $miktar, '$hareket_tipi', 'Agent otomatik güncelleme', NOW())";
            $this->conn->query($sql);
        }
        
        // Log
        $this->logAction(self::ACTION_DATABASE, [
            'operation' => 'update_stock',
            'urun_id' => $urun_id,
            'old_stock' => $current_stock,
            'new_stock' => $new_stock,
            'success' => $success
        ]);
        
        return [
            'success' => $success,
            'action' => 'update_stock',
            'urun_adi' => $urun_adi,
            'old_stock' => $current_stock,
            'new_stock' => $new_stock,
            'summary' => "$urun_adi stoğu güncellendi: $current_stock → $new_stock"
        ];
    }
    
    /**
     * Ödeme hatırlatıcısı gönder
     */
    private function sendPaymentReminder($params) {
        $fatura_id = $params['fatura_id'] ?? 0;
        
        if ($fatura_id <= 0) {
            throw new Exception("Geçersiz fatura ID");
        }
        
        // Fatura bilgilerini al
        $sql = "SELECT f.*, m.unvan, m.email, m.telefon
                FROM faturalar f
                LEFT JOIN musteriler m ON f.musteri_id = m.id
                WHERE f.id = $fatura_id AND f.firma_id = {$this->firma_id}";
        
        $result = $this->conn->query($sql);
        if (!$result || $result->num_rows === 0) {
            throw new Exception("Fatura bulunamadı");
        }
        
        $fatura = $result->fetch_assoc();
        
        // Email içeriği
        $message = "Sayın {$fatura['unvan']},\n\n" .
                  "Fatura No: {$fatura['fatura_no']}\n" .
                  "Tutar: {$fatura['tutar']} TL\n" .
                  "Vade Tarihi: {$fatura['vade_tarihi']}\n\n" .
                  "Ödemenizi bekliyoruz.\n\n" .
                  "HANKA CRM";
        
        $actions_taken = [];
        
        // Email gönder
        if (!empty($fatura['email'])) {
            $email_result = $this->sendEmail([
                'to' => $fatura['email'],
                'subject' => "Ödeme Hatırlatması - {$fatura['fatura_no']}",
                'body' => nl2br($message)
            ]);
            $actions_taken[] = 'email';
        }
        
        // WhatsApp gönder
        if (!empty($fatura['telefon'])) {
            $whatsapp_result = $this->sendWhatsApp([
                'phone' => $fatura['telefon'],
                'message' => $message
            ]);
            $actions_taken[] = 'whatsapp';
        }
        
        return [
            'success' => true,
            'action' => 'send_payment_reminder',
            'fatura_no' => $fatura['fatura_no'],
            'musteri' => $fatura['unvan'],
            'actions' => $actions_taken,
            'summary' => "Ödeme hatırlatması gönderildi: {$fatura['fatura_no']} ({$fatura['unvan']})"
        ];
    }
    
    /**
     * Düşük stok bildirimi
     */
    private function notifyLowStock($params) {
        $recipients = $params['recipients'] ?? ['admin@hankasys.com'];
        
        // Düşük stoklu ürünleri al
        $sql = "SELECT urun_adi, stok_miktar, min_stok_miktar
                FROM urunler
                WHERE firma_id = {$this->firma_id}
                  AND stok_miktar < min_stok_miktar
                  AND aktif = 1
                ORDER BY (min_stok_miktar - stok_miktar) DESC
                LIMIT 20";
        
        $result = $this->conn->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            return [
                'success' => true,
                'action' => 'notify_low_stock',
                'summary' => "Düşük stoklu ürün bulunamadı"
            ];
        }
        
        $low_stock_products = [];
        while ($row = $result->fetch_assoc()) {
            $low_stock_products[] = $row;
        }
        
        // Email içeriği
        $message = "<h2>🚨 Düşük Stok Uyarısı</h2>";
        $message .= "<p>" . count($low_stock_products) . " adet ürünün stoğu kritik seviyede!</p>";
        $message .= "<table border='1' cellpadding='5'>";
        $message .= "<tr><th>Ürün</th><th>Mevcut Stok</th><th>Min. Stok</th><th>Eksik</th></tr>";
        
        foreach ($low_stock_products as $product) {
            $eksik = $product['min_stok_miktar'] - $product['stok_miktar'];
            $message .= "<tr>";
            $message .= "<td>{$product['urun_adi']}</td>";
            $message .= "<td>{$product['stok_miktar']}</td>";
            $message .= "<td>{$product['min_stok_miktar']}</td>";
            $message .= "<td style='color:red;font-weight:bold;'>$eksik</td>";
            $message .= "</tr>";
        }
        
        $message .= "</table>";
        
        // Gönder
        $sent_count = 0;
        foreach ($recipients as $recipient) {
            $result = $this->sendEmail([
                'to' => $recipient,
                'subject' => "🚨 Düşük Stok Uyarısı - " . count($low_stock_products) . " Ürün",
                'body' => $message
            ]);
            
            if ($result['success']) {
                $sent_count++;
            }
        }
        
        return [
            'success' => true,
            'action' => 'notify_low_stock',
            'product_count' => count($low_stock_products),
            'sent_count' => $sent_count,
            'summary' => "$sent_count kişiye " . count($low_stock_products) . " ürün için düşük stok bildirimi gönderildi"
        ];
    }
    
    /**
     * Sipariş oluştur
     */
    private function createOrder($params) {
        // TODO: Sipariş oluşturma logic'i
        
        return [
            'success' => false,
            'action' => 'create_order',
            'summary' => "Sipariş oluşturma henüz implemente edilmedi"
        ];
    }
    
    /**
     * Custom action
     */
    private function customAction($params) {
        return [
            'success' => false,
            'error' => "Bilinmeyen action: " . ($params['action'] ?? 'unknown'),
            'actions' => []
        ];
    }
    
    /**
     * Rapor formatlama
     */
    private function formatReport($report_data, $format) {
        if ($format === 'email') {
            $html = "<html><body>";
            $html .= "<h2>HANKA CRM Raporu</h2>";
            $html .= "<p><strong>Tarih:</strong> " . date('d.m.Y H:i') . "</p>";
            $html .= "<hr>";
            $html .= "<div>" . nl2br($report_data['summary']) . "</div>";
            $html .= "</body></html>";
            return $html;
        }
        
        // Diğer formatlar için (PDF, Excel vb.)
        return json_encode($report_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Action log'u
     */
    private function logAction($action_type, $details) {
        $details_json = $this->conn->real_escape_string(json_encode($details, JSON_UNESCAPED_UNICODE));
        
        $sql = "INSERT INTO agent_action_log (firma_id, action_type, details, created_at)
                VALUES ({$this->firma_id}, '$action_type', '$details_json', NOW())";
        
        $this->conn->query($sql);
    }
    
    public function getStatus() {
        return [
            'active' => true,
            'name' => 'ActionAgent',
            'capabilities' => [
                'send_email',
                'send_whatsapp',
                'create_report',
                'update_stock',
                'send_payment_reminder',
                'notify_low_stock'
            ]
        ];
    }
}
