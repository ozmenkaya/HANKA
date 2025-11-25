<div class="row">
<div class="card border-secondary">
    <div class="card-header d-flex justify-content-between border-secondary">
        <h5>
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Giriş Logları
        </h5>
        <div>
            <a href="javascript:window.history.back();" 
                class="btn btn-secondary"
                data-bs-toggle="tooltip"
                data-bs-placement="bottom" 
                data-bs-title="Geri Dön"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table id="myTable" class="table table-hover" >
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th class="text-center">İp</th>
                        <th>Tarayıcı</th>
                        <th>Tarayıcı Versiyon</th>
                        <th>İşletim Sistemi</th>
                        <th>Tarih</th>
                        <th class="text-end">Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $sql = 'SELECT * FROM giris_log WHERE email = :email ORDER BY id DESC';
                        $sth = $conn->prepare($sql);
                        $sth->bindParam('email', $_SESSION['email']);
                        $sth->execute();
                        $girisler = $sth->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php foreach ($girisler as $index => $giris) { ?>
                        <?php 
                            $tarayici = json_decode($giris['tarayici'], true);
                        ?>
                        <tr class="<?php echo $giris['durum'] =='basarılı' ? 'table-success':'table-danger'; ?>">
                            <th class="table-primary"><?php echo $index + 1; ?></th>
                            <td class="text-center">
                                <span class="badge text-bg-secondary p-2 fw-bold fs-6">
                                    <i class="mdi mdi-wifi"></i> <?php echo $giris['ip']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if(isset($tarayici['name']) && $tarayici['name'] == 'Mozilla Firefox' ){ ?>
                                    <i class="fa-brands fa-firefox"></i>
                                <?php }else if(isset($tarayici['name']) && $tarayici['name'] == 'Google Chrome'){?> 
                                    <i class="fa-brands fa-chrome"></i>    
                                <?php }else if(isset($tarayici['name']) && $tarayici['name'] == 'Edge'){?> 
                                    <i class="fa-brands fa-edge"></i>    
                                <?php } ?>
                                <?php echo isset($tarayici['name']) ? $tarayici['name'] : '-' ?>
                            </td>
                            <td><?php echo isset($tarayici['version']) ? $tarayici['version'] : '-' ?></td>
                            <td>
                                <?php if(isset($tarayici['platform']) && $tarayici['platform'] == 'Windows' ){ ?>
                                    <i class="fa-brands fa-windows"></i>
                                <?php }else if(isset($tarayici['platform']) && $tarayici['platform'] == 'Linux'){?> 
                                    <i class="fa-brands fa-linux"></i>
                                <?php }else if(isset($tarayici['platform']) && $tarayici['platform'] == 'Mac OS'){?> 
                                    <i class="fa-brands fa-apple"></i>
                                <?php }else if(isset($tarayici['platform']) && $tarayici['platform'] == 'Android'){ ?>
                                    <i class="fa-brands fa-android"></i>
                                <?php } ?>
                                <?php echo isset($tarayici['platform']) ? $tarayici['platform'] : '-' ?>
                            </td>
                            <td><?php echo date('d-m-Y H:i:s',strtotime($giris['tarih'])); ?></td>
                            <td class="text-end">
                                <?php  if($giris['durum'] =='basarılı'){?> 
                                    <span class="badge text-bg-success">BAŞARILI</span>
                                <?php }else{?> 
                                    <span class="badge text-bg-danger">BAŞARISIZ</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php }?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
