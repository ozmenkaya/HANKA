<?php
    require_once "include/oturum_kontrol.php";

    if(!in_array($_SESSION['yetki_id'],[SUPER_ADMIN_YETKI_ID,ADMIN_YETKI_ID])){
        require_once "include/yetkisiz.php"; exit;
    }
    $aktif_tab = isset($_GET['aktif_tab']) ? intval($_GET['aktif_tab']) : 0;

    $sql = 'SELECT * FROM yetkiler';
    if($_SESSION['yetki_id'] != SUPER_ADMIN_YETKI_ID){
        $sql .= ' WHERE id != 0';
    }
    $sth = $conn->prepare($sql);
    $sth->execute();
    $yetkiler = $sth->fetchAll(PDO::FETCH_ASSOC);


    $sql = 'SELECT * FROM sayfalar';
    $sth = $conn->prepare($sql);
    $sth->execute();
    $sayfalar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sayfalar_chunck = array_chunk($sayfalar, 17);

    //echo "<pre>"; print_r($sayfalar); exit;
?>
<div class="row">
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <h5>
                <i class="fa-solid fa-plug-circle-check"></i> Sayfa Yetkiler
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
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <?php foreach ($yetkiler as $index => $yetki) { ?>
                        <button data-aktif-tab="<?php echo $index;?>"  class="sayfalar-button nav-link fw-bold <?php echo  $index == $aktif_tab ? 'active':''; ?>" 
                            id="nav-tab-<?php echo $index;?>" data-bs-toggle="tab" 
                            data-bs-target="#nav-<?php echo $index;?>" type="button" role="tab" 
                            aria-controls="nav-profile" aria-selected="false"
                        >
                            <?php echo ($index+1).'- '.$yetki['yetki']; ?>
                        </button>
                    <?php }?>
                </div>
            </nav>
            <div class="tab-content mt-2" id="nav-tabContent">
                <?php foreach ($yetkiler as $index => $yetki) { ?>
                    <?php 
                        $sql = 'SELECT * FROM yetki_sayfalar WHERE firma_id = :firma_id AND yetki_id = :yetki_id';
                        $sth = $conn->prepare($sql);
                        $sth->bindParam('firma_id', $_SESSION['firma_id']);
                        $sth->bindParam('yetki_id', $yetki['id']);
                        $sth->execute();
                        $yetki_sayfa = $sth->fetch(PDO::FETCH_ASSOC);   
                        $yetki_sayfa_idler  = []; 
                        if(isset($yetki_sayfa['sayfa_idler'])){
                            $yetki_sayfa_idler = json_decode($yetki_sayfa['sayfa_idler'] , true);
                        }
                        $yetki_sayfa_idler  = empty($yetki_sayfa_idler) ? [] : $yetki_sayfa_idler ;
                    ?>
                    <div class="tab-pane fade <?php echo $index == $aktif_tab ? 'show active':''; ?>" 
                            id="nav-<?php echo $index; ?>" role="tabpanel" 
                            aria-labelledby="nav-tab-<?php echo $index; ?>" tabindex="0"
                        >   
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input tum-yetkiler" type="checkbox" role="switch"  data-index="<?php echo $index; ?>"
                                id="tum-yetkiler-<?php echo $index; ?>"
                            >
                            <label class="form-check-label" for="tum-yetkiler-<?php echo $index; ?>">
                                Tüm Yetkileri Ver
                            </label>
                        </div>

                        <form action="/index.php?url=sayfa_yetkiler_db_islem" method="POST">
                            <input type="hidden" name="aktif_tab" value="0">
                            <input type="hidden" name="yetki_id" value="<?php echo $yetki['id']; ?>">
                            <input type="hidden" name="yetki_sayfalar_id" value="<?php echo empty($yetki_sayfa) ? 0 : $yetki_sayfa['id']; ?>">
                            <div class="row">
                                <?php foreach ($sayfalar_chunck as $sayfa_chunck_index => $sayfalar) { ?>
                                    <div class="col-md-4">
                                        <?php foreach ($sayfalar as $sayfa_index => $sayfa) { ?>
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input yetki-<?php echo $index; ?>" type="checkbox" role="switch" 
                                                    id="sayfa-<?php echo $index.'-'.$sayfa_chunck_index.'-'.$sayfa_index; ?>" 
                                                    value="<?php echo $sayfa['id']; ?>" name="sayfa_idler[]"
                                                    <?php echo in_array($sayfa['id'], $yetki_sayfa_idler) ? 'checked' :''; ?>
                                                >
                                                <label class="form-check-label" for="sayfa-<?php echo $index.'-'.$sayfa_chunck_index.'-'.$sayfa_index; ?>">
                                                    <?php echo $sayfa['ad']; ?>
                                                </label>
                                            </div>
                                        <?php }?>
                                    </div>
                                <?php } ?>
                                
                            </div>
                            <button type="submit" class="btn btn-primary" name="sayfa-yetki-kaydet">
                                <i class="fa-solid fa-plug-circle-check"></i> <?php echo $yetki['yetki']; ?> KAYDET
                            </button>
                        </form>
                    </div> 
                <?php }?>    
            </div>
        </div>
    </div>
</div>
<?php 
    include_once "include/uyari_session_oldur.php"; 
?>
<script>
    $(function(){
        $(".sayfalar-button").click(function(){
            const aktifTab = $(this).data('aktif-tab');
            $("[name=aktif_tab]").val(aktifTab);
        });

        $(".tum-yetkiler").click(function(){
            const index = $(this).data('index');
            if($(this).is(':checked')){
                $(`.yetki-${index}`).prop('checked', true);
            }else{
                $(`.yetki-${index}`).prop('checked', false);
            }
        });
    });
</script>