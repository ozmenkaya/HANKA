<div class="row">
    <div class="col-md-12">
        <?php if(isset($_GET['durum']) && $_GET['durum'] == 'basarili') { ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <h4><?php echo $_GET['mesaj']; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </div>
        <?php }else if(isset($_GET['durum']) && $_GET['durum'] == 'basarisiz'){ ?> 
            <div class="alert alert-success alert-dismissible" role="alert">
                <h4><?php echo $_GET['mesaj']; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </div>  
        <?php } ?>
    </div>
</div>

