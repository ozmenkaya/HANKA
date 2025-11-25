<?php
// API Ayarları Sayfası
require_once "include/db.php";

// Sadece admin ve super admin erişebilir
if(!in_array($_SESSION['yetki_id'], [SUPER_ADMIN_YETKI_ID, ADMIN_YETKI_ID])){
    header("Location: /index.php");
    exit;
}

// API Key'leri çek
$sql = "SELECT * FROM api_keys WHERE firma_id = :firma_id ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute(['firma_id' => $_SESSION['firma_id']]);
$api_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php require_once "include/head.php"; ?>
    <title>API Ayarları - HANKA CRM</title>
    <style>
        .api-key-card {
            border-left: 4px solid #5470c6;
            transition: all 0.3s;
        }
        .api-key-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .api-key-text {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            word-break: break-all;
        }
        .badge-active {
            background-color: #28a745;
        }
        .badge-inactive {
            background-color: #dc3545;
        }
        .endpoint-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .endpoint-item {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .endpoint-item:last-child {
            border-bottom: none;
        }
        .method-badge {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        .method-get { background-color: #28a745; color: white; }
        .method-post { background-color: #007bff; color: white; }
        .method-put { background-color: #ffc107; color: black; }
        .method-delete { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php require_once "include/sidebar.php"; ?>
        
        <div class="content-page">
            <div class="content">
                <?php require_once "include/header.php"; ?>

                <div class="container-fluid">
                    <!-- Sayfa Başlığı -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    <i class="mdi mdi-api"></i> API Ayarları
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- API Bilgi Kartları -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card widget-flat">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="mdi mdi-key-variant widget-icon bg-success-lighten text-success"></i>
                                    </div>
                                    <h5 class="text-muted fw-normal mt-0" title="Toplam API Key">API Keys</h5>
                                    <h3 class="mt-3 mb-3"><?php echo count($api_keys); ?></h3>
                                    <p class="mb-0 text-muted">
                                        <span class="text-success me-2">
                                            <?php echo count(array_filter($api_keys, fn($k) => $k['is_active'] == 1)); ?> Aktif
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card widget-flat">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="mdi mdi-server-network widget-icon bg-info-lighten text-info"></i>
                                    </div>
                                    <h5 class="text-muted fw-normal mt-0" title="API Endpoint">Endpoints</h5>
                                    <h3 class="mt-3 mb-3">11</h3>
                                    <p class="mb-0 text-muted">
                                        <span class="text-info me-2">2 Modül</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card widget-flat">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="mdi mdi-check-circle widget-icon bg-primary-lighten text-primary"></i>
                                    </div>
                                    <h5 class="text-muted fw-normal mt-0" title="API Durumu">Durum</h5>
                                    <h3 class="mt-3 mb-3 text-success">Aktif</h3>
                                    <p class="mb-0 text-muted">
                                        <span class="text-primary">v1.0</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card widget-flat">
                                <div class="card-body">
                                    <div class="float-end">
                                        <i class="mdi mdi-link-variant widget-icon bg-warning-lighten text-warning"></i>
                                    </div>
                                    <h5 class="text-muted fw-normal mt-0" title="Base URL">Base URL</h5>
                                    <h3 class="mt-3 mb-3" style="font-size: 14px;">/api/v1/</h3>
                                    <p class="mb-0 text-muted">
                                        <span class="text-warning">REST API</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API Keys Listesi -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="header-title">
                                            <i class="mdi mdi-key-variant"></i> API Anahtarları
                                        </h4>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createKeyModal">
                                            <i class="mdi mdi-plus"></i> Yeni API Key Oluştur
                                        </button>
                                    </div>

                                    <?php if(empty($api_keys)): ?>
                                        <div class="alert alert-info">
                                            <i class="mdi mdi-information"></i> 
                                            Henüz API key oluşturulmamış. Yeni bir key oluşturmak için yukarıdaki butona tıklayın.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach($api_keys as $key): ?>
                                            <div class="card api-key-card mb-3">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <h5 class="mb-2">
                                                                <?php echo htmlspecialchars($key['name']); ?>
                                                                <span class="badge <?php echo $key['is_active'] ? 'badge-active' : 'badge-inactive'; ?> ms-2">
                                                                    <?php echo $key['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                                                </span>
                                                            </h5>
                                                            <div class="api-key-text mb-2">
                                                                <strong>API Key:</strong> <?php echo htmlspecialchars($key['api_key']); ?>
                                                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo htmlspecialchars($key['api_key']); ?>')">
                                                                    <i class="mdi mdi-content-copy"></i> Kopyala
                                                                </button>
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="mdi mdi-calendar"></i> Oluşturulma: <?php echo date('d.m.Y H:i', strtotime($key['created_at'])); ?>
                                                                <?php if($key['last_used']): ?>
                                                                    | <i class="mdi mdi-clock"></i> Son Kullanım: <?php echo date('d.m.Y H:i', strtotime($key['last_used'])); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <button class="btn btn-sm btn-info mb-2" onclick="toggleKeyStatus(<?php echo $key['id']; ?>, <?php echo $key['is_active'] ? 0 : 1; ?>)">
                                                                <i class="mdi mdi-<?php echo $key['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                                <?php echo $key['is_active'] ? 'Pasif Yap' : 'Aktif Yap'; ?>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger mb-2" onclick="deleteKey(<?php echo $key['id']; ?>)">
                                                                <i class="mdi mdi-delete"></i> Sil
                                                            </button>
                                                            <button class="btn btn-sm btn-secondary mb-2" data-bs-toggle="modal" data-bs-target="#permissionsModal<?php echo $key['id']; ?>">
                                                                <i class="mdi mdi-shield-check"></i> İzinler
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if($key['permissions']): ?>
                                                        <div class="mt-2">
                                                            <strong>İzinler:</strong>
                                                            <?php
                                                            $permissions = json_decode($key['permissions'], true);
                                                            foreach($permissions as $module => $perms):
                                                            ?>
                                                                <span class="badge bg-secondary me-1">
                                                                    <?php echo ucfirst($module); ?>: 
                                                                    <?php echo $perms['read'] ? 'R' : ''; ?>
                                                                    <?php echo $perms['write'] ? 'W' : ''; ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- İzinler Modal -->
                                            <div class="modal fade" id="permissionsModal<?php echo $key['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">API Key İzinleri</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Key:</strong> <?php echo htmlspecialchars($key['name']); ?></p>
                                                            <pre><?php echo json_encode(json_decode($key['permissions'], true), JSON_PRETTY_PRINT); ?></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API Endpoints Dokümantasyonu -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title mb-3">
                                        <i class="mdi mdi-book-open-variant"></i> API Endpoint'leri
                                    </h4>

                                    <div class="endpoint-list">
                                        <h6 class="mb-3">Status</h6>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-get">GET</span>
                                            <code>/api/v1/status</code>
                                            <span class="text-muted ms-2">- API durumu ve bilgileri</span>
                                        </div>

                                        <h6 class="mb-3 mt-4">Customers (Müşteriler)</h6>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-get">GET</span>
                                            <code>/api/v1/customers</code>
                                            <span class="text-muted ms-2">- Müşteri listesi</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-get">GET</span>
                                            <code>/api/v1/customers/{id}</code>
                                            <span class="text-muted ms-2">- Tek müşteri detayı</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-post">POST</span>
                                            <code>/api/v1/customers</code>
                                            <span class="text-muted ms-2">- Yeni müşteri ekle</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-put">PUT</span>
                                            <code>/api/v1/customers/{id}</code>
                                            <span class="text-muted ms-2">- Müşteri güncelle</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-delete">DELETE</span>
                                            <code>/api/v1/customers/{id}</code>
                                            <span class="text-muted ms-2">- Müşteri sil</span>
                                        </div>

                                        <h6 class="mb-3 mt-4">Orders (Siparişler)</h6>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-get">GET</span>
                                            <code>/api/v1/orders</code>
                                            <span class="text-muted ms-2">- Sipariş listesi</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-get">GET</span>
                                            <code>/api/v1/orders/{id}</code>
                                            <span class="text-muted ms-2">- Tek sipariş detayı</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-post">POST</span>
                                            <code>/api/v1/orders</code>
                                            <span class="text-muted ms-2">- Yeni sipariş ekle</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-put">PUT</span>
                                            <code>/api/v1/orders/{id}</code>
                                            <span class="text-muted ms-2">- Sipariş güncelle</span>
                                        </div>
                                        <div class="endpoint-item">
                                            <span class="method-badge method-delete">DELETE</span>
                                            <code>/api/v1/orders/{id}</code>
                                            <span class="text-muted ms-2">- Sipariş sil</span>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <a href="/api/v1/README.md" target="_blank" class="btn btn-info">
                                            <i class="mdi mdi-file-document"></i> Tam Dokümantasyon
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php require_once "include/footer.php"; ?>
        </div>
    </div>

    <!-- Yeni API Key Oluşturma Modal -->
    <div class="modal fade" id="createKeyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni API Key Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createKeyForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Key İsmi</label>
                            <input type="text" class="form-control" name="name" required 
                                   placeholder="Örn: Mobil Uygulama API">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">İzinler</label>
                            
                            <div class="card mb-2">
                                <div class="card-body">
                                    <h6>Customers (Müşteriler)</h6>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="permissions[customers][read]" value="1" checked>
                                        <label class="form-check-label">Okuma</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="permissions[customers][write]" value="1" checked>
                                        <label class="form-check-label">Yazma</label>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-2">
                                <div class="card-body">
                                    <h6>Orders (Siparişler)</h6>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="permissions[orders][read]" value="1" checked>
                                        <label class="form-check-label">Okuma</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="permissions[orders][write]" value="1" checked>
                                        <label class="form-check-label">Yazma</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="mdi mdi-alert"></i> 
                            <strong>Önemli:</strong> API key oluşturulduktan sonra bir daha gösterilmeyecektir. 
                            Lütfen güvenli bir yerde saklayın.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once "include/script.php"; ?>

    <script>
        // Clipboard'a kopyala
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Kopyalandı!',
                    text: 'API key panoya kopyalandı.',
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        }

        // Key durumunu değiştir
        function toggleKeyStatus(keyId, status) {
            Swal.fire({
                title: 'Emin misiniz?',
                text: status ? 'Bu API key aktif hale gelecek.' : 'Bu API key pasif hale gelecek.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Evet',
                cancelButtonText: 'İptal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/index.php?url=api_ayarlar_db_islem',
                        type: 'POST',
                        data: {
                            action: 'toggle_status',
                            key_id: keyId,
                            status: status
                        },
                        success: function(response) {
                            Swal.fire('Başarılı!', 'API key durumu güncellendi.', 'success')
                                .then(() => location.reload());
                        },
                        error: function() {
                            Swal.fire('Hata!', 'İşlem başarısız oldu.', 'error');
                        }
                    });
                }
            });
        }

        // Key sil
        function deleteKey(keyId) {
            Swal.fire({
                title: 'Emin misiniz?',
                text: 'Bu API key kalıcı olarak silinecek!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'İptal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/index.php?url=api_ayarlar_db_islem',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            key_id: keyId
                        },
                        success: function(response) {
                            Swal.fire('Silindi!', 'API key başarıyla silindi.', 'success')
                                .then(() => location.reload());
                        },
                        error: function() {
                            Swal.fire('Hata!', 'Silme işlemi başarısız oldu.', 'error');
                        }
                    });
                }
            });
        }

        // Yeni key oluşturma
        $('#createKeyForm').on('submit', function(e) {
            e.preventDefault();
            
            // İzinleri topla
            const permissions = {
                customers: {
                    read: $('input[name="permissions[customers][read]"]').is(':checked'),
                    write: $('input[name="permissions[customers][write]"]').is(':checked')
                },
                orders: {
                    read: $('input[name="permissions[orders][read]"]').is(':checked'),
                    write: $('input[name="permissions[orders][write]"]').is(':checked')
                }
            };

            $.ajax({
                url: '/index.php?url=api_ayarlar_db_islem',
                type: 'POST',
                data: {
                    action: 'create',
                    name: $('input[name="name"]').val(),
                    permissions: JSON.stringify(permissions)
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    $('#createKeyModal').modal('hide');
                    
                    Swal.fire({
                        title: 'Başarılı!',
                        html: `<p>API Key başarıyla oluşturuldu!</p>
                               <div style="background:#f8f9fa; padding:15px; border-radius:5px; margin-top:15px;">
                                   <strong>API Key (Bir daha gösterilmeyecek!):</strong><br>
                                   <code style="font-size:12px; word-break:break-all;">${data.api_key}</code><br>
                                   <button class="btn btn-sm btn-primary mt-2" onclick="copyToClipboard('${data.api_key}')">
                                       <i class="mdi mdi-content-copy"></i> Kopyala
                                   </button>
                               </div>`,
                        icon: 'success',
                        width: 600,
                        confirmButtonText: 'Tamam'
                    }).then(() => location.reload());
                },
                error: function() {
                    Swal.fire('Hata!', 'API key oluşturulamadı.', 'error');
                }
            });
        });
    </script>
</body>
</html>
