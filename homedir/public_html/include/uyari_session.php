<div class="row">
    <div class="col-md-12">
        <?php if(isset($_SESSION['durum']) && $_SESSION['durum'] == 'basarili') { ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <h4><?php echo $_SESSION['mesaj']; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </div>
        <?php }else if(isset($_SESSION['durum']) && $_SESSION['durum'] == 'basarisiz'){ ?> 
            <div class="alert alert-danger alert-dismissible" role="alert">
                <h4><?php echo $_SESSION['mesaj']; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </div>  
        <?php } ?>
    </div>
</div>

