<?php
session_start();
require_once 'include/db.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/icons.min.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/app.min.css" rel="stylesheet" type="text/css">
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Dashboard - KPI Kartları</h2>
            <hr>
        </div>
    </div>

    <div class="row" id="kpiKartlari">
        <div class="col-12 text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <p class="mt-2">Veriler yükleniyor...</p>
        </div>
    </div>
</div>

<script>
// Basit fetch kullanarak - ajax-helper.js olmadan
async function yukleKPI() {
    try {
        const response = await fetch('/api/dashboard_api.php');
        const result = await response.json();
        
        console.log('API Response:', result);
        
        if(!result.success) {
            document.getElementById('kpiKartlari').innerHTML = 
                '<div class="col-12"><div class="alert alert-danger">Hata: ' + result.message + '</div></div>';
            return;
        }
        
        const data = result.data;
        
        const html = `
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-muted">Bugünkü Üretim</h5>
                        <h2 class="text-primary">${data.bugun_uretim.toLocaleString('tr-TR')}</h2>
                        <p class="text-muted mb-0">Adet</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-muted">Aktif İşler</h5>
                        <h2 class="text-success">${data.aktif_is}</h2>
                        <p class="text-muted mb-0">Devam ediyor</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-muted">Makinalar</h5>
                        <h2 class="text-info">${data.calisan_makina} / ${data.toplam_makina}</h2>
                        <p class="text-muted mb-0">%${data.makina_kullanim_orani} Kullanımda</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-muted">Bekleyen Sipariş</h5>
                        <h2 class="text-warning">${data.bekleyen_siparis}</h2>
                        <p class="text-danger mb-0">
                            <i class="mdi mdi-alert"></i> ${data.arizali_makina} Arızalı Makina
                        </p>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('kpiKartlari').innerHTML = html;
        
    } catch(error) {
        console.error('JavaScript Hatası:', error);
        document.getElementById('kpiKartlari').innerHTML = 
            '<div class="col-12"><div class="alert alert-danger">JavaScript hatası: ' + error.message + '</div></div>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    yukleKPI();
    setInterval(yukleKPI, 30000); // Her 30 saniyede güncelle
});
</script>

</body>
</html>
