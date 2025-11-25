<?php
    require_once "include/db.php";
    require_once "include/oturum_kontrol.php";
?>
<!DOCTYPE html>
<html lang="tr">
    <head>
        <title>Hanka Sys SAAS</title> 
        <?php require_once "include/head.php";?>
    </head>
    <body>
        <?php 
            require_once "include/header.php";
            require_once "include/sol_menu.php";
        ?>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5></h5>
                </div>
                <div class="card-body">
                </div>
            </div>
        </div>
        <?php 
            include_once "include/scripts.php"; 
            include_once "include/uyari_session_oldur.php"; 
        ?>
    </body>
</html>
