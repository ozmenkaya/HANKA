<?php 
    $sth = $conn->prepare('SELECT makinalar.*, departmanlar.departman FROM makinalar 
    JOIN departmanlar ON makinalar.departman_id = departmanlar.id 
    WHERE makinalar.firma_id = :firma_id ORDER BY departmanlar.departman');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $makinalar = $sth->fetchAll(PDO::FETCH_ASSOC);
    #echo "<pre>"; print_r($personeller); exit;
?>
    <div class="row">
        <div class="card mt-2">
            <div class="card-header d-flex justify-content-between">
                <h5>
                    <i class="fa-solid fa-gears"></i> Makinalar
                </h5>
                <div>
                    <div class="d-md-flex justify-content-end"> 
                        <div class="btn-group" role="group" aria-label="Basic example">
                            <a href="javascript:window.history.back();" 
                                class="btn btn-secondary"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Geri Dön"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                            <a href="/index.php?url=makina_ekle"  class="btn btn-primary"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom" 
                                data-bs-title="Makina Ekle"
                            > 
                                <i class="fa-solid fa-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table id="myTable" class="table table-hover table-striped">
                        <thead class="table-primary">
                        <tr>
                            <th>Sıra</th>
                            <th>Adı</th>
                            <th>Modeli</th>
                            <th>Seri No</th>
                            <th>Departmanı</th>
                            <th class="text-center">Durumu</th>
                            <th class="text-center">Makina Ayar Süresi</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                        </thead>
                        <tbody>
                            <?php foreach($makinalar as $key=> $makina){ ?>
                                <tr class="makina-row <?php echo $makina['makina_ayar_suresi_varmi'] == 'var' ? 'table-success':'table-danger';?>" 
                                    data-makina-id="<?php echo $makina['id']; ?>" 
                                    style="cursor: pointer;">
                                    <th class="table-primary"><?php echo $key + 1 ; ?></th>
                                    <td><?php echo $makina['makina_adi']; ?></td>
                                    <td><?php echo $makina['makina_modeli']; ?></td>
                                    <td><?php echo $makina['makina_seri_no']; ?></td>
                                    <td><?php echo $makina['departman']; ?></td>
                                    <th class="text-center">
                                        <?php if($makina['durumu'] == 'aktif'){ ?>
                                            <span class="badge bg-success">AKTİF</span>
                                        <?php }elseif($makina['durumu'] == 'bakımda'){?> 
                                            <span class="badge bg-warning">BAKIMDA</span>
                                        <?php }else{ ?>
                                            <span class="badge bg-danger">PASİF</span>
                                        <?php } ?>
                                    </th>
                                    <th class="text-center">
                                        <?php if($makina['makina_ayar_suresi_varmi'] == 'var'){ ?>
                                            <span class="badge bg-success">VAR</span>
                                        <?php }else{ ?>
                                            <span class="badge bg-danger">YOK</span>
                                        <?php } ?>
                                    </th>

                                    <td>
                                        <div class="d-flex justify-content-end"> 
                                            <div class="btn-group" role="group" aria-label="Basic example">
                                                <a href="/index.php?url=makina_guncelle&id=<?php echo $makina['id']; ?>" 
                                                    class="btn btn-warning"
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="bottom" 
                                                    data-bs-title="Güncelle"
                                                >
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                                <?php if($makina['kullanildi_mi'] == 'hayır' && in_array($makina['durumu'], ['pasif'])){ ?>
                                                    <a href="/index.php?url=makina_db_islem&islem=makina_sil&id=<?php echo $makina['id']; ?>" 
                                                        onClick="return confirm('Silmek İstediğinize Emin Misiniz?')"
                                                        class="btn btn-danger"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil"
                                                    >
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                <?php }else{?> 
                                                    <a href="#" 
                                                        class="btn btn-danger disabled"
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="bottom" 
                                                        data-bs-title="Sil"
                                                    >
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </td> 
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>     
    
    <script>
    // Makina satırlarına tıklama özelliği
    document.addEventListener('DOMContentLoaded', function() {
        const makinaRows = document.querySelectorAll('.makina-row');
        
        makinaRows.forEach(row => {
            // Hover efekti
            row.addEventListener('mouseenter', function() {
                this.classList.add('table-active');
            });
            
            row.addEventListener('mouseleave', function() {
                this.classList.remove('table-active');
            });
            
            // Tıklama olayı
            row.addEventListener('click', function(e) {
                // Eğer butonlara tıklandıysa satır tıklamasını iptal et
                if (e.target.closest('.btn') || e.target.closest('a')) {
                    return;
                }
                
                const makinaId = this.getAttribute('data-makina-id');
                window.location.href = '/index.php?url=makina_guncelle&id=' + makinaId;
            });
        });
    });
    </script>
    
    <?php 
        include_once "include/uyari_session_oldur.php";
    ?>