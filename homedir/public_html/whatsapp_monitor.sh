#!/bin/bash
# WhatsApp mesaj geçmişini göster

echo "📱 WHATSAPP MESAJ GEÇMİŞİ"
echo "=========================="
echo ""

ssh root@91.99.186.98 "mysql -u hanka_user -p'HankaDB2025!' panelhankasys_crm2 -e \"
SELECT 
    id,
    from_number as 'Kimden',
    LEFT(body, 30) as 'Mesaj',
    LEFT(response, 40) as 'Cevap',
    created_at as 'Tarih'
FROM whatsapp_messages 
ORDER BY id DESC 
LIMIT 10;
\" 2>/dev/null"

echo ""
echo "İstatistikler:"
echo "--------------"

ssh root@91.99.186.98 "mysql -u hanka_user -p'HankaDB2025!' panelhankasys_crm2 -e \"
SELECT 
    COUNT(*) as 'Toplam Mesaj',
    COUNT(DISTINCT from_number) as 'Farklı Kullanıcı',
    MAX(created_at) as 'Son Mesaj'
FROM whatsapp_messages;
\" 2>/dev/null"
