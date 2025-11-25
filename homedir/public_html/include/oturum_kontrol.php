<?php 


if(!isset($_SESSION['giris_kontrol']))
{
    $url = isset($_COOKIE['url']) ? $_COOKIE['url'] : '';
    header('Location: login.php?url='.$url);
    die();
}
