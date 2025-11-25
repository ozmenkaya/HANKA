<?php  
    include "include/oturum_kontrol.php";

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $sth = $conn->prepare('SELECT * FROM departmanlar WHERE id = :id AND firma_id = :firma_id');
    $sth->bindParam('id', $id);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departman = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($departman))
    {
        include 'include/yetkisiz.php'; exit;
    }

    $sth = $conn->prepare('SELECT * FROM stok_kalemleri WHERE  firma_id = :firma_id');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $stok_kalemler = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT * FROM departman_planlama WHERE departman_id = :departman_id AND firma_id = :firma_id');
    $sth->bindParam('departman_id', $departman['id']);
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $departman_planlama = $sth->fetch(PDO::FETCH_ASSOC);
 
    $sth = $conn->prepare('SELECT * FROM makina_is_buttonlar');
    $sth->execute();
    $makina_is_buttonlar = $sth->fetchAll(PDO::FETCH_ASSOC);

    $sth = $conn->prepare('SELECT * FROM birimler WHERE firma_id = :firma_id  ORDER BY ad');
    $sth->bindParam('firma_id', $_SESSION['firma_id']);
    $sth->execute();
    $birimler = $sth->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="row mt-2">
    <div class="col-md-12">
        <div class="card border-secondary">
            <div class="card-header d-flex justify-content-between border-secondary">
                <h5>
                    <i class="fa-solid fa-building"></i> Departman : 
                    <b><?php echo $departman['departman']; ?></b>
                </h5>
                <div>
                    <div class="d-flex justify-content-end"> 
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
                <?php if(empty($departman_planlama)){?>
                    <form  action="/index.php?url=departman_planlama_db_islem" method="POST" id="departman-planlama-kaydet-form">
                        <input type="hidden" name="departman_id" value="<?php echo $departman['id']; ?>">
                        <div class="row mb-2">
                            <div class="col-md-4" id="stoklar">
                                <div class="input-group input-group-lg stok mb-2">
                                    <button class="btn btn-outline-success stok-ekle" type="button">
                                            <i class="fa-solid fa-plus"></i> Stok
                                    </button>
                                    <select class="form-select"  name="stok[]">
                                        <option selected value="0">Yok</option>
                                        <?php foreach ($stok_kalemler as $index_stok_id => $stok_kalem) { ?>
                                            <option value="<?php echo $stok_kalem['id']; ?>">
                                                <?php echo $stok_kalem['stok_kalem']; ?>
                                            </option>
                                        <?php }?>
                                    </select>
                                    <button class="btn btn-outline-danger stok-sil" <?php echo $index_stok_id == 0 ? 'disabled' :''?> type="button">
                                        <i class="fa-solid fa-minus"></i> 
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="form-floating col-md-4 g-3">
                                <select class="form-select" id="birim_id" name="birim_id" required>
                                    <option selected disabled value="">Seçiniz</option>
                                    <?php foreach ($birimler as $birim) { ?>
                                        <option  value="<?php echo $birim['id']; ?>">
                                            <?php echo $birim['ad']; ?>
                                        </option>
                                    <?php }?>
                                </select>
                                <label for="birim_id" class="form-label">Birim</label>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>
                                    <i class="fa-solid fa-gear"></i> Makina Ekran Ayarları
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$id);
                                    $sth->execute();
                                    $makina_is_buttonlar_firma_ayarlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    //print_r($makina_is_buttonlar_firma_ayarlar);
                                ?>
                                <div class="row mb-2">   
                                    <?php foreach ($makina_is_buttonlar as $makina_is_button) { ?>
                                        <?php 
                                            $durum = 1;
                                            foreach ($makina_is_buttonlar_firma_ayarlar as $makina_is_buttonlar_firma_ayar) {
                                                if($makina_is_buttonlar_firma_ayar['makina_is_button_id'] == $makina_is_button['id']){
                                                    $durum = $makina_is_buttonlar_firma_ayar['durum'];
                                                    break;
                                                }
                                            }
                                        ?>
                                    
                                        <div class="form-floating col-md-3 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text fw-bold" ><?php echo $makina_is_button['ad']; ?></span>
                                                <select class="form-select" name="makina_is_button_idler[<?php echo $makina_is_button['id']?>]">
                                                    <option value="0" <?php echo $durum == 0 ? 'selected': '';?>>Kapalı</option>
                                                    <option value="1" <?php echo $durum == 1 ? 'selected': '';?>>Açık</option>
                                                </select>
                                            </div>
                                        </div>
                                    <?php }?>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>
                                    <i class="fa-solid fa-tags"></i> Etiket Ayarı
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2 g-3">
                                    <div class="form-floating col-md-4">
                                        <select class="form-select" id="etiket_varmi" name="etiket_varmi" required>
                                            <option  value="var" >Var</option>
                                            <option  value="yok"  selected>Yok</option>
                                        </select>
                                        <label for="etiket_varmi" class="form-label">Etiket Var Mı?</label>
                                    </div>  

                                    <div class="form-floating col-md-4">
                                        <input type="number" class="form-control" name="genislik" id="genislik" >
                                        <label for="genislik" class="form-label">Etiketin Genişliği</label>
                                    </div>

                                    <div class="form-floating col-md-4">
                                        <input type="number" class="form-control" name="yukseklik" id="yukseklik" >
                                        <label for="yukseklik" class="form-label">Etiketin Yüksekliği</label>
                                    </div>
                                </div>
                                
                                <label for="cke5-feature-rich-demo" class="fw-bold">Etiket Tasarımı</label> 
                                <div>
                                    <div class="btn-group mb-2 fw-bold" role="group">
                                        <button type="button" class="btn btn-outline-secondary etiket-ad" data-etiket-ad="termin">
                                            <i class="fa-solid fa-plus"></i> Termin Tarihi
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary etiket-ad" data-etiket-ad="isin_adi">
                                            <i class="fa-solid fa-plus"></i> İşin Adı
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary etiket-ad" data-etiket-ad="marka">
                                            <i class="fa-solid fa-plus"></i> Müşteri Adı
                                        </button>
                                    </div>
                                </div>
                                            
                                <div class="form-floating col-md-12 ">
                                    <textarea class="form-control" name="etiket_tasarim"  
                                        id="cke5-feature-rich-demo"></textarea>
                                </div>       
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button class="btn btn-primary" type="submit" name="departman_plan_ekle" id="departman-planlama-kaydet-button">
                                    <i class="fa-regular fa-square-plus"></i> KAYDET
                                </button>
                                <a href="/index.php?url=departman" class="btn btn-secondary">
                                    <i class="fa-regular fa-rectangle-xmark"></i> DEPARTMANLAR
                                </a>
                            </div>
                        </div>
                    </form>
                <?php }else{ ?>
                    <form  action="/index.php?url=departman_planlama_db_islem" method="POST">
                        <input type="hidden" name="id" value="<?php echo $departman_planlama['id']; ?>">
                        <input type="hidden" name="departman_id" value="<?php echo $departman_planlama['departman_id']; ?>">
                        <?php $stok_idler = json_decode($departman_planlama['stok']); ?>
                        <div class="row">
                            <div class="col-md-4" id="stoklar">
                                <?php foreach ($stok_idler as $index_stok_id => $stok_id) { ?>
                                    <div class="input-group input-group-lg stok mb-2">
                                        <button class="btn btn-outline-success stok-ekle" type="button">
                                            <i class="fa-solid fa-plus"></i> Stok
                                        </button>
                                        <select class="form-select"  name="stok[]">
                                            <option value="0">Seçiniz</option>
                                            <?php foreach ($stok_kalemler as $stok_kalem) { ?>
                                                <option value="<?php echo $stok_kalem['id']; ?>" <?php echo $stok_kalem['id'] == $stok_id ? 'selected' : ''; ?>>
                                                    <?php echo $stok_kalem['stok_kalem']; ?>
                                                </option>
                                            <?php }?>
                                        </select>
                                        <button class="btn btn-outline-danger stok-sil" <?php echo $index_stok_id == 0 ? 'disabled' :''?> type="button">
                                            <i class="fa-solid fa-minus"></i> 
                                        </button>
                                    </div>
                                <?php }?>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="form-floating col-md-4 g-3">
                                <select class="form-select" id="birim_id" name="birim_id" required>
                                    <option  value="">Seçiniz</option>
                                    <?php foreach ($birimler as $birim) { ?>
                                        <option  value="<?php echo $birim['id']; ?>" <?php echo $birim['id'] == $departman_planlama['birim_id'] ? 'selected':''; ?>>
                                            <?php echo $birim['ad']; ?>
                                        </option>
                                    <?php }?>
                                </select>
                                <label for="birim_id" class="form-label">Birim</label>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>
                                    <i class="fa-solid fa-gear"></i> Makina Ekran Ayarları
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                    $sql = "SELECT makina_is_button_id, durum FROM `makina_is_buttonlar_firma_ayarlar` 
                                    WHERE firma_id = :firma_id AND departman_id = :departman_id";
                                    $sth = $conn->prepare($sql);
                                    $sth->bindParam('firma_id', $_SESSION['firma_id']);
                                    $sth->bindParam('departman_id',$id);
                                    $sth->execute();
                                    $makina_is_buttonlar_firma_ayarlar = $sth->fetchAll(PDO::FETCH_ASSOC);
                                    //print_r($makina_is_buttonlar_firma_ayarlar);
                                ?>
                                <div class="row mb-2"> 
                                    <?php foreach ($makina_is_buttonlar as $makina_is_button) { ?>
                                        <?php 
                                            $durum = 1;
                                            foreach ($makina_is_buttonlar_firma_ayarlar as $makina_is_buttonlar_firma_ayar) {
                                                if($makina_is_buttonlar_firma_ayar['makina_is_button_id'] == $makina_is_button['id']){
                                                    $durum = $makina_is_buttonlar_firma_ayar['durum'];
                                                    break;
                                                }
                                            }
                                        ?>
                                        <div class="form-floating col-md-4">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text fw-bold" ><?php echo $makina_is_button['ad']; ?></span>
                                                <select class="form-select" name="makina_is_button_idler[<?php echo $makina_is_button['id']?>]">
                                                    <option value="1" <?php echo $durum == 1 ? 'selected': '';?>>Açık</option>
                                                    <option value="0" <?php echo $durum == 0 ? 'selected': '';?>>Kapalı</option>
                                                </select>
                                            </div>
                                        </div>
                                    <?php }?>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>
                                    <i class="fa-solid fa-tags"></i> Etiket Ayarı
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2 g-3">
                                    <div class="form-floating col-md-4">
                                        <select class="form-select" id="etiket_varmi" name="etiket_varmi" required>
                                            <option  value="var" <?php echo $departman_planlama['etiket_varmi'] == 'var' ? 'selected':'';?>>Var</option>
                                            <option  value="yok" <?php echo $departman_planlama['etiket_varmi'] == 'yok' ? 'selected':'';?>>Yok</option>
                                        </select>
                                        <label for="etiket_varmi" class="form-label">Etiket Var Mı?</label>
                                    </div>  

                                    <div class="form-floating col-md-4">
                                        <input type="number" class="form-control" name="genislik" id="genislik" value="<?php echo $departman_planlama['genislik'];?>">
                                        <label for="genislik" class="form-label">Etiketin Genişliği</label>
                                    </div>

                                    <div class="form-floating col-md-4">
                                        <input type="number" class="form-control" name="yukseklik" id="yukseklik" value="<?php echo $departman_planlama['genislik'];?>">
                                        <label for="yukseklik" class="form-label">Etiketin Yüksekliği</label>
                                    </div>
                                </div>
                                
                                <label for="cke5-feature-rich-demo" class="fw-bold">Etiket Tasarımı</label> 
                                <div>
                                    <div class="btn-group mb-2 fw-bold" role="group">
                                        <button type="button" class="btn btn-outline-secondary etiket-ad" data-etiket-ad="termin">
                                            <i class="fa-solid fa-plus"></i> Termin Tarihi
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary etiket-ad" data-etiket-ad="isin_adi">
                                            <i class="fa-solid fa-plus"></i> İşin Adı
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary etiket-ad" data-etiket-ad="marka">
                                            <i class="fa-solid fa-plus"></i> Müşteri Adı
                                        </button>
                                    </div>
                                </div>

                                <div class="form-floating col-md-12 ">
                                    <textarea class="form-control" name="etiket_tasarim"  
                                        id="cke5-feature-rich-demo"><?php echo $departman_planlama['etiket_tasarim']; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <button class="btn btn-warning fw-bold" type="submit" name="departman_plan_guncelle">
                                    <i class="fa-regular fa-pen-to-square"></i> GÜNCELLE
                                </button>
                                <a href="/index.php?url=departman" class="btn btn-secondary fw-bold" >
                                    <i class="fa-regular fa-rectangle-xmark"></i> DEPARTMANLAR
                                </a>
                            </div>
                        </div>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
</div>  
<?php include_once "include/uyari_session_oldur.php"; ?>
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

<script>
/*
ClassicEditor
    .create( document.querySelector( '#cke5-feature-rich-demo' ), {
    extraPlugins: [ CustomizationPlugin ]
} )
    .then( newEditor => {
    window.editor = newEditor;
    // The following line adds CKEditor 5 inspector.
    CKEditorInspector.attach( newEditor, {
        isCollapsed: true
    } );
} )
    .catch( error => {
    console.error( error );
} );
*/

let editor;
ClassicEditor
.create( document.querySelector( '#cke5-feature-rich-demo' ) )
.then( newEditor  => {
    editor = newEditor ;
} )
.catch( error => {
        console.error( error );
} );


$(function(){

    //etiketi textarea koy
    $(".etiket-ad").click(function(){
        const etiketAd = $(this).data('etiket-ad');
        
        editor.setData(editor.getData() +  ` #${etiketAd}# `);

        log(editorData)
    });
    
    $("#departman-planlama-kaydet-form").submit(function(){
        $("#departman-planlama-kaydet-button").addClass('disabled');
        return true;
    });

    // stok input ekle
    $(document).on('click', '.stok-ekle', function(){
        let stokKlon = $(this).closest('.stok').clone();
        stokKlon.find('button').prop('disabled', false);
        $('#stoklar').append(stokKlon);
    });

    // stok input silme
    $(document).on('click', '.stok-sil', function(){
        if(confirm('Silmek İstediğinize Emin Misiniz?'))
        {
            if( $('.stok').length > 1){
                $(this).closest('.stok').remove(); 
            }
        }
        
    });
});
</script> 