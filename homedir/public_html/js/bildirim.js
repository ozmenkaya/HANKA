// Bildirim Sistemi
class BildirimSistemi {
    constructor() {
        this.checkInterval = 30000; // 30 saniye
        this.timer = null;
        this.bildirimIzniVarMi = false;
        this.init();
    }
    
    init() {
        // Browser notification izni
        this.bildirimIzniIste();
        
        // İlk yükleme
        this.okunmamisBildirimleriGetir();
        
        // Periyodik kontrol
        this.baslatPeriyodikKontrol();
        
        // Event listeners
        this.setupEventListeners();
    }
    
    bildirimIzniIste() {
        if ("Notification" in window) {
            if (Notification.permission === "granted") {
                this.bildirimIzniVarMi = true;
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(permission => {
                    this.bildirimIzniVarMi = (permission === "granted");
                });
            }
        }
    }
    
    async okunmamisBildirimleriGetir() {
        try {
            const response = await fetch('/api/bildirim_api.php?action=okunmamis');
            const result = await response.json();
            
            if(result.success) {
                const data = result.data;
                this.badgeGuncelle(data.sayi);
                
                // Yeni bildirim varsa browser notification göster
                if(data.bildirimler && data.bildirimler.length > 0) {
                    const sonBildirim = data.bildirimler[0];
                    const kayitliSonId = localStorage.getItem('son_bildirim_id');
                    
                    if(!kayitliSonId || parseInt(kayitliSonId) < parseInt(sonBildirim.id)) {
                        this.browserBildirimGoster(sonBildirim);
                        localStorage.setItem('son_bildirim_id', sonBildirim.id);
                    }
                }
                
                this.dropdownGuncelle(data.bildirimler);
            }
        } catch(error) {
            console.error('Bildirim hatası:', error);
        }
    }
    
    badgeGuncelle(sayi) {
        const badge = document.querySelector('.noti-icon-badge');
        if(badge) {
            badge.textContent = sayi;
            badge.style.display = sayi > 0 ? 'inline-block' : 'none';
        }
    }
    
    dropdownGuncelle(bildirimler) {
        const dropdown = document.querySelector('#bildirim-dropdown-liste');
        if(!dropdown) return;
        
        if(bildirimler.length === 0) {
            dropdown.innerHTML = '<div class="p-3 text-center text-muted">Yeni bildirim yok</div>';
            return;
        }
        
        let html = '';
        bildirimler.forEach(bildirim => {
            const icon = this.getTurIcon(bildirim.tur);
            const renk = this.getTurRenk(bildirim.tur);
            const tarih = this.formatTarih(bildirim.olusturma_tarihi);
            
            html += `
                <a href="${bildirim.link || '#'}" 
                   class="dropdown-item notify-item ${bildirim.okundu === 'hayir' ? 'unread' : ''}"
                   data-bildirim-id="${bildirim.id}"
                   onclick="bildirimSistemi.bildirimOkundu(${bildirim.id})">
                    <div class="notify-icon bg-${renk}">
                        <i class="${icon}"></i>
                    </div>
                    <p class="notify-details">
                        <strong>${this.escapeHtml(bildirim.baslik)}</strong>
                        <small class="text-muted">${this.escapeHtml(bildirim.mesaj)}</small>
                        <small class="text-muted d-block">${tarih}</small>
                    </p>
                </a>
            `;
        });
        
        dropdown.innerHTML = html;
    }
    
    browserBildirimGoster(bildirim) {
        if(!this.bildirimIzniVarMi) return;
        
        const notification = new Notification(bildirim.baslik, {
            body: bildirim.mesaj,
            icon: '/dosyalar/logo/logo.png',
            badge: '/dosyalar/logo/logo.png',
            tag: 'hanka-bildirim-' + bildirim.id,
            requireInteraction: false
        });
        
        notification.onclick = () => {
            window.focus();
            if(bildirim.link) {
                window.location.href = bildirim.link;
            }
            notification.close();
        };
        
        // 10 saniye sonra otomatik kapat
        setTimeout(() => notification.close(), 10000);
    }
    
    async bildirimOkundu(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'okundu_isaretle');
            formData.append('id', id);
            
            await fetch('/api/bildirim_api.php', {
                method: 'POST',
                body: formData
            });
            
            // Badge'i güncelle
            setTimeout(() => this.okunmamisBildirimleriGetir(), 500);
        } catch(error) {
            console.error('Okundu işaretleme hatası:', error);
        }
    }
    
    async tumunuOkunduIsaretle() {
        try {
            const formData = new FormData();
            formData.append('action', 'tumunu_okundu_isaretle');
            
            const response = await fetch('/api/bildirim_api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if(result.success) {
                this.badgeGuncelle(0);
                this.okunmamisBildirimleriGetir();
            }
        } catch(error) {
            console.error('Toplu okundu hatası:', error);
        }
    }
    
    baslatPeriyodikKontrol() {
        this.timer = setInterval(() => {
            this.okunmamisBildirimleriGetir();
        }, this.checkInterval);
    }
    
    durdurPeriyodikKontrol() {
        if(this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
    
    setupEventListeners() {
        // Sayfa görünürlüğü değiştiğinde
        document.addEventListener('visibilitychange', () => {
            if(document.hidden) {
                this.durdurPeriyodikKontrol();
            } else {
                this.okunmamisBildirimleriGetir();
                this.baslatPeriyodikKontrol();
            }
        });
    }
    
    getTurIcon(tur) {
        const icons = {
            'basari': 'mdi mdi-check-circle',
            'bilgi': 'mdi mdi-information',
            'uyari': 'mdi mdi-alert',
            'hata': 'mdi mdi-close-circle'
        };
        return icons[tur] || icons['bilgi'];
    }
    
    getTurRenk(tur) {
        const renkler = {
            'basari': 'success',
            'bilgi': 'info',
            'uyari': 'warning',
            'hata': 'danger'
        };
        return renkler[tur] || 'info';
    }
    
    formatTarih(tarih) {
        const simdi = new Date();
        const bildirimTarih = new Date(tarih);
        const fark = Math.floor((simdi - bildirimTarih) / 1000); // saniye
        
        if(fark < 60) return 'Az önce';
        if(fark < 3600) return Math.floor(fark / 60) + ' dakika önce';
        if(fark < 86400) return Math.floor(fark / 3600) + ' saat önce';
        if(fark < 2592000) return Math.floor(fark / 86400) + ' gün önce';
        
        return bildirimTarih.toLocaleDateString('tr-TR');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance
let bildirimSistemi;

// Sayfa yüklendiğinde başlat
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        bildirimSistemi = new BildirimSistemi();
    });
} else {
    bildirimSistemi = new BildirimSistemi();
}
