<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="fe-bell"></i> Bildirimler
                </h4>
                <div>
                    <button onclick="bildirimSistemi.tumunuOkunduIsaretle()" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-check-all"></i> Tümünü Okundu İşaretle
                    </button>
                    <a href="javascript:window.history.back();" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Geri
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="bildirimler-liste">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                        <p class="mt-2 text-muted">Bildirimler yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tüm bildirimleri yükle
async function tumBildirimleriYukle() {
    try {
        const response = await fetch('/api/bildirim_api.php?action=liste');
        const result = await response.json();
        
        const liste = document.getElementById('bildirimler-liste');
        
        if(!result.success || !result.data || result.data.length === 0) {
            liste.innerHTML = `
                <div class="text-center p-5">
                    <i class="mdi mdi-bell-off text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">Henüz bildiriminiz yok</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        result.data.forEach(bildirim => {
            const iconClass = getTurIcon(bildirim.tur);
            const renkClass = getTurRenk(bildirim.tur);
            const okunmamisClass = bildirim.okundu === 'hayir' ? 'unread' : '';
            const tarih = new Date(bildirim.olusturma_tarihi).toLocaleString('tr-TR');
            
            html += `
                <div class="card bildirim-card ${bildirim.tur} ${okunmamisClass}">
                    <div class="card-body">
                        <div class="bildirim-header">
                            <div class="d-flex align-items-center">
                                <div class="notify-icon bg-${renkClass} me-3">
                                    <i class="${iconClass}"></i>
                                </div>
                                <h5 class="bildirim-baslik mb-0">${escapeHtml(bildirim.baslik)}</h5>
                            </div>
                            <small class="bildirim-tarih">${tarih}</small>
                        </div>
                        <p class="bildirim-mesaj mt-2">${escapeHtml(bildirim.mesaj)}</p>
                        ${bildirim.link ? `<a href="${bildirim.link}" class="btn btn-sm btn-outline-primary mt-2">Detaya Git</a>` : ''}
                    </div>
                </div>
            `;
        });
        
        liste.innerHTML = html;
        
    } catch(error) {
        console.error('Bildirimler yükleme hatası:', error);
        document.getElementById('bildirimler-liste').innerHTML = `
            <div class="alert alert-danger">
                Bildirimler yüklenirken bir hata oluştu.
            </div>
        `;
    }
}

function getTurIcon(tur) {
    const icons = {
        'basari': 'mdi mdi-check-circle',
        'bilgi': 'mdi mdi-information',
        'uyari': 'mdi mdi-alert',
        'hata': 'mdi mdi-close-circle'
    };
    return icons[tur] || icons['bilgi'];
}

function getTurRenk(tur) {
    const renkler = {
        'basari': 'success',
        'bilgi': 'info',
        'uyari': 'warning',
        'hata': 'danger'
    };
    return renkler[tur] || 'info';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Sayfa yüklendiğinde
tumBildirimleriYukle();
</script>
