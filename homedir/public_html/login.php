<?php
include "include/db.php";
if(isset($_SESSION['giris_kontrol']))
{
    if($_SESSION['yetki_id'] == URETIM_YETKI_ID){ //üretim personeli ise
        header('Location: /index.php?url=makina_listesi');
    }else{
        header('Location: /');
    }
    exit();
}

$domain = $_SERVER['HTTP_HOST'];
$sth    = $conn->prepare('SELECT id, firma_adi, logo FROM firmalar WHERE domain_adi = :domain_adi');
$sth->bindParam('domain_adi', $domain);
$sth->execute();
$firma = $sth->fetch(PDO::FETCH_ASSOC);

#echo "<pre>"; print_r($firma); exit;
$logo       = isset($firma['logo']) && !empty($firma['logo']) ? $firma['logo'] : 'varsayilan.svg';
$firma_adi  = isset($firma['firma_adi']) ? $firma['firma_adi'] : 'HANKASYS';
?>
<!doctype html>
<html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="Gülermat Otomasyon V.1">
        <meta name="generator" content="HANKASYS">
        <title><?php echo $firma_adi ?> Sys SASS</title>
        
        <link href="css/bootstrap.min.css" rel="stylesheet" >
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" />
        <meta name="theme-color" content="#712cf9">
        <link rel="shortcut icon" href="dosyalar/logo/<?php echo $logo;?>" type="image/x-icon" />
        <link rel="apple-touch-icon" href="dosyalar/logo/<?php echo $logo;?>" type="image/png" />

        <link href="css/sign-in.css" rel="stylesheet">
    </head>
    <body class="text-center">
        <main class="form-signin w-100 m-auto">
            <form action="login_kontrol.php" method="POST" id="giris-form">
                <img class="img-fluid w-100 mb-3" 
                    src="dosyalar/logo/<?php echo $logo; ?>"  style="width:75px;height:75px;object-fit:contain"
                >
                <input type="hidden" name="url" value="<?php echo isset($_GET['url']) ? $_GET['url'] :''; ?>">
                <!-- <h4 class="mb-2 fw-normal"><?php echo $firma_adi; ?></h4> -->
                <div class="form-floating mb-2">
                    <input type="email" class="form-control" id="email"  name="email" 
                        value="<?php echo isset($_SESSION['post']) ? $_SESSION['post']['email'] :'';?>" required>
                    <label for="email">Email</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="sifre"  name="sifre" required>
                    <label for="sifre">Şifre</label>
                </div>

                <div class="form-floating mb-2">
                    <div class="form-check form-switch fs-5" style="text-align: left;">
                        <input class="form-check-input" type="checkbox" role="switch" id="beni-hatirla" >
                        <label class="form-check-label fs-6 text-success" for="beni-hatirla">Beni Hatırla</label>
                    </div>
                </div>

                <button type="submit" class="w-100 btn btn-lg btn-primary" name="giris" id="giris-button">
                    <i class="fa-solid fa-right-to-bracket"></i> Giriş
                </button>
                <p class="mt-2 mb-3 text-muted">&copy; Hanka Systems</p>

            </form>
        </main>

        
        <script src="assets/node_modules/jquery/dist/jquery.min.js"></script>
        <script src="js/notify.js"></script>
        <script>
            const durum = "<?php echo isset($_SESSION['durum'])  ? $_SESSION['durum'] :  ''; ?>"
            if(durum == 'error'){
                $.notify(
                    "<?php echo isset($_SESSION['mesaj']) ? $_SESSION['mesaj'] : '';?>", 
                    "error"
                );
            }
            const beniHatirla = localStorage.getItem('beniHatirla');
            if(beniHatirla){
                $("#email").val(localStorage.getItem('email'));
                $("#beni-hatirla").prop("checked", true);
            }

            $(function(){
                $("#giris-form").submit(function(){
                    if($("#beni-hatirla").is(":checked")){
                        localStorage.setItem("email", $("#email").val());
                        localStorage.setItem("beniHatirla", true);
                    }else{
                        localStorage.clear();
                    }
                    $("#giris-button").addClass('disabled');
                    return true;
                });

                $("#sifre").val('');
            });
        </script>    
        
        <?php 
            require_once "include/uyari_session_oldur.php";
        ?>
        
    </body>
</html>
