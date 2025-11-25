<?php 
    require_once "include/oturum_kontrol.php";

    $id = intval($_GET['id']);

    $sth = $conn->prepare('SELECT * FROM firmalar WHERE id =:id');
    $sth->bindParam('id', $id);
    $sth->execute();
    $firma = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($firma))
    {
        require_once "include/yetkisiz.php";
        die();
    }
?>
        <div class="row mt-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>
                        <i class="fa-solid fa-building"></i> Firma Güncelle
                    </h5>
                    <div>
                        <div class="d-md-flex justify-content-end"> 
                            <div class="btn-group" role="group" aria-label="Basic example">
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
                    </div>
                </div>
                <div class="card-body">
                    <form action="/index.php?url=firma_db_islem"  enctype="multipart/form-data" method="POST" autocomplete="off">
                        <input type="hidden" name="id" value="<?php echo $firma['id']; ?>">
                        <div class="form-floating m-1">
                            <input type="text" class="form-control" name="domain_adi" id="domain_adi" 
                                value="<?php echo $firma['domain_adi'];?>" required autofocus>
                            <label for="domain_adi" class="form-label">Domain</label>
                        </div>
                        <div class="form-floating m-1">
                            <input type="text" class="form-control" name="firma_adi" id="firma_adi" value="<?php echo $firma['firma_adi'];?>" required >
                            <label for="firma_adi" class="form-label">Firma Adı</label>
                        </div>
                        <div class="form-floating m-1">
                            <input type="email" class="form-control" name="email" id="email" value="<?php echo $firma['email'];?>" required >
                            <label for="email" class="form-label">Email</label>
                        </div>
                        <div class="form-floating m-1">
                            <input class="form-control" type="file" id="logo" name="logo" >           
                            <label for="logo" class="form-label">Logo</label>
                        </div>
                        <div class="form-floating m-1">
                            <?php if($firma['logo'] != ''){ ?>
                                <span class="text-success fw-bold">LOGO: </span>
                                <img class="mb-4 object-fit-fill border rounded mt-2" src="dosyalar/logo/<?php echo $firma['logo'];?>" 
                                    alt="<?php echo $firma['firma_adi']; ?>" style="width:72px;height:72px" loading="lazy" >
                            <?php }else{?>
                                <h5 class="text-danger fw-bold">Logo Yok</h5>    
                            <?php } ?>
                        </div>
                        <div class="form-floating m-1">
                            <button type="submit" class="btn btn-warning" name="firma-guncelle">
                                <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            include_once "include/uyari_session_oldur.php"; 
        ?>