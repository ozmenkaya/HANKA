/**
 * AJAX Helper Functions
 * Sayfa yenilemeden işlem yapma yardımcıları
 */

// API çağrısı yap
async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            },
            ...options
        });
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Bağlantı hatası' };
    }
}

// GET request
async function apiGet(url, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = queryString ? `${url}?${queryString}` : url;
    return await apiCall(fullUrl, { method: 'GET' });
}

// POST request
async function apiPost(url, data = {}) {
    const formData = new FormData();
    Object.keys(data).forEach(key => formData.append(key, data[key]));
    
    return await apiCall(url, {
        method: 'POST',
        body: formData
    });
}

// JSON POST request
async function apiPostJSON(url, data = {}) {
    return await apiCall(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
}

// Tablo güncelleme
function updateTableRow(tableId, rowId, html) {
    const row = document.querySelector(`#${tableId} tr[data-id="${rowId}"]`);
    if (row) {
        row.outerHTML = html;
    }
}

// Tablo satırı ekleme
function addTableRow(tableId, html, position = 'top') {
    const tbody = document.querySelector(`#${tableId} tbody`);
    if (!tbody) return;
    
    if (position === 'top') {
        tbody.insertAdjacentHTML('afterbegin', html);
    } else {
        tbody.insertAdjacentHTML('beforeend', html);
    }
}

// Tablo satırı silme
function deleteTableRow(tableId, rowId) {
    const row = document.querySelector(`#${tableId} tr[data-id="${rowId}"]`);
    if (row) {
        row.style.transition = 'opacity 0.3s';
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 300);
    }
}

// Loading göster/gizle
function showLoading(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.classList.add('loading');
        el.style.opacity = '0.5';
        el.style.pointerEvents = 'none';
    }
}

function hideLoading(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.classList.remove('loading');
        el.style.opacity = '1';
        el.style.pointerEvents = 'auto';
    }
}

// Başarı/Hata mesajı (notify.js kullanıyorsanız)
function showMessage(message, type = 'success') {
    if (typeof $.notify !== 'undefined') {
        $.notify(message, type);
    } else {
        alert(message);
    }
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Form verisini objeye çevir
function formToObject(formId) {
    const form = document.getElementById(formId);
    if (!form) return {};
    
    const formData = new FormData(form);
    const obj = {};
    formData.forEach((value, key) => {
        obj[key] = value;
    });
    return obj;
}

// Debounce (arama için)
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Kullanım örnekleri:
/*
// Makina durumlarını getir
const makinalar = await apiGet('/api/makina_api.php', { action: 'getMakinaDurumlari' });
if (makinalar.success) {
    console.log(makinalar.data);
}

// Üretim adedi güncelle
const result = await apiPost('/api/makina_api.php', {
    action: 'updateUretimAdet',
    planlama_id: 123,
    adet: 50
});
showMessage(result.message, result.success ? 'success' : 'error');

// Arama (debounce ile)
const aramaInput = document.getElementById('arama');
aramaInput.addEventListener('input', debounce(async function(e) {
    const sonuclar = await apiGet('/api/arama.php', { q: e.target.value });
    // Sonuçları göster
}, 300));
*/
