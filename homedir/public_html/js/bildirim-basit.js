// Basit Bildirim Sistemi - Mevcut badge'i kullanır
(function() {
    let checkInterval = 30000; // 30 saniye
    let timer = null;
    
    async function okunmamisBildirimleriKontrolEt() {
        try {
            const response = await fetch('/api/bildirim_api.php?action=okunmamis');
            const result = await response.json();
            
            if(result.success && result.data) {
                const sayi = result.data.sayi || 0;
                
                // Mevcut badge'i güncelle
                const badge = document.querySelector('.noti-icon-badge');
                if(badge) {
                    if(sayi > 0) {
                        badge.textContent = sayi;
                        badge.style.display = 'inline-block';
                        
                        // Browser notification
                        if(result.data.bildirimler && result.data.bildirimler.length > 0) {
                            const sonBildirim = result.data.bildirimler[0];
                            const kayitliSonId = localStorage.getItem('son_bildirim_id');
                            
                            if(!kayitliSonId || parseInt(kayitliSonId) < parseInt(sonBildirim.id)) {
                                tarayiciBildirimGoster(sonBildirim);
                                localStorage.setItem('son_bildirim_id', sonBildirim.id);
                            }
                        }
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        } catch(error) {
            console.log('Bildirim kontrolü yapılamadı:', error);
        }
    }
    
    function tarayiciBildirimGoster(bildirim) {
        if ("Notification" in window && Notification.permission === "granted") {
            const notification = new Notification(bildirim.baslik, {
                body: bildirim.mesaj,
                icon: '/dosyalar/logo/logo.png',
                tag: 'hanka-' + bildirim.id
            });
            
            notification.onclick = () => {
                window.focus();
                if(bildirim.link) {
                    window.location.href = bildirim.link;
                }
                notification.close();
            };
            
            setTimeout(() => notification.close(), 10000);
        }
    }
    
    function isteBildirimIzni() {
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }
    }
    
    // Başlat
    isteBildirimIzni();
    okunmamisBildirimleriKontrolEt();
    
    // Her 30 saniyede kontrol et
    timer = setInterval(okunmamisBildirimleriKontrolEt, checkInterval);
    
    // Sayfa gizlendiğinde durdur
    document.addEventListener('visibilitychange', () => {
        if(document.hidden) {
            if(timer) clearInterval(timer);
        } else {
            okunmamisBildirimleriKontrolEt();
            timer = setInterval(okunmamisBildirimleriKontrolEt, checkInterval);
        }
    });
})();

console.log('✅ Bildirim sistemi başlatıldı');
