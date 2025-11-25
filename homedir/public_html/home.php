<div class="row">
<div class="card mb-2 mt-2 border-secondary">
    <div class="card-header d-flex justify-content-between border-secondary">
        <h5>
            <i class="fa-solid fa-house"></i> Anasayfa
        </h5>
        <div>
            <a href="javascript:window.history.back();" 
                class="btn btn-secondary"
                data-bs-target="#departman-ekle-modal"
                data-bs-toggle="tooltip"
                data-bs-placement="bottom" 
                data-bs-title="Geri Dön"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Dashboard KPI Kartları -->
        <div class="row g-3 mb-4" id="dashboard-kpi">
            <div class="col-md-4">
                <div class="card border-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Bugünkü Üretim</h6>
                                <h2 class="mb-0" id="kpi-bugun-uretim">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h2>
                            </div>
                            <div class="fs-1 text-primary">
                                <i class="fa-solid fa-industry"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-warning h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Aktif İşler</h6>
                                <h2 class="mb-0" id="kpi-aktif-is">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h2>
                            </div>
                            <div class="fs-1 text-warning">
                                <i class="fa-solid fa-tasks"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Makinalar</h6>
                                <h2 class="mb-0" id="kpi-makinalar">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h2>
                            </div>
                            <div class="fs-1 text-success">
                                <i class="fa-solid fa-cogs"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Bekleyen Sipariş</h6>
                                <h2 class="mb-0" id="kpi-bekleyen-siparis">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h2>
                            </div>
                            <div class="fs-1 text-info">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-danger h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Arızalı Makina</h6>
                                <h2 class="mb-0" id="kpi-arizali-makina">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h2>
                            </div>
                            <div class="fs-1 text-danger">
                                <i class="fa-solid fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Dashboard KPI verilerini yükle
        async function yukleKPI() {
            try {
                const response = await fetch('/api/dashboard_api.php');
                const result = await response.json();
                
                if(result.success) {
                    const data = result.data;
                    document.getElementById('kpi-bugun-uretim').textContent = data.bugun_uretim.toLocaleString();
                    document.getElementById('kpi-aktif-is').textContent = data.aktif_is.toLocaleString();
                    document.getElementById('kpi-makinalar').innerHTML = 
                        data.calisan_makina + '<small class="text-muted">/' + data.toplam_makina + '</small>';
                    document.getElementById('kpi-bekleyen-siparis').textContent = data.bekleyen_siparis.toLocaleString();
                    document.getElementById('kpi-arizali-makina').textContent = data.arizali_makina.toLocaleString();
                } else {
                    console.error('KPI Hatası:', result.message);
                }
            } catch(error) {
                console.error('KPI Yükleme Hatası:', error);
            }
        }

        // Sayfa yüklendiğinde ve her 30 saniyede bir
        yukleKPI();
        setInterval(yukleKPI, 30000);
        </script>
    </div>
</div>
</div>
