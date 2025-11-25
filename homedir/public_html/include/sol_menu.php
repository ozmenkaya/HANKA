 <!--- Menu -->
 <ul class="menu">
 
<li class="menu-item">
    <a href="/" class="menu-link">
        <span class="menu-icon"><i class="mdi mdi-home"></i></span>
        <span class="menu-text"> Anasayfa </span>
    </a>
</li>


<?php if( in_array(MUSTERI_GOR, $_SESSION['sayfa_idler']) ){ ?> 
    <li class="menu-item">
        <a href="/index.php?url=musteriler" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-human"></i></span>
            <span class="menu-text"> Müşteriler </span>
        </a>
    </li>   
<?php }?>

<?php if(in_array(TUM_SIPARISLERI_GOR, $_SESSION['sayfa_idler'])){ ?>
    <li class="menu-item">
        <a href="/index.php?url=siparisler_onay" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-equal-box"></i></span>
            <span class="menu-text">Siparişler</span>
        </a>
    </li>
<?php } ?>  

<?php if( in_array(DEPARTMAN_GOR, $_SESSION['sayfa_idler'])){ ?>
    <li class="menu-item">
        <a href="/index.php?url=departman" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-home-modern"></i></span>
            <span class="menu-text"> Departmanlar </span>
        </a>
    </li>   
<?php }?>

<?php if(in_array(MAKINA_GOR, $_SESSION['sayfa_idler'])){  ?>
    <li class="menu-item">
        <a href="/index.php?url=makina" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-washing-machine"></i></span>
            <span class="menu-text"> Makinalar </span>
        </a>
    </li>   
<?php } ?>

<?php if(in_array(PERSONEL_GOR, $_SESSION['sayfa_idler'])){ ?>
    <li class="menu-item">
        <a href="/index.php?url=personel" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-account-multiple"></i></span>
            <span class="menu-text"> Personeller </span>
        </a>
    </li>   
<?php }?>

<?php if( $_SESSION['yetki_id'] == SUPER_ADMIN_YETKI_ID ){  ?>
    <li class="menu-item">
    <a href="#menuFirma" data-bs-toggle="collapse" class="menu-link">
        <span class="menu-icon"><i class="mdi mdi-home-modern"></i></span>
        <span class="menu-text"> Firmalar </span>
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse" id="menuFirma">
        <ul class="sub-menu">
            <li class="menu-item">
                <a href="/index.php?url=firma" class="menu-link">
                    <span class="menu-text">Firma Listesi</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/index.php?url=superadmin_personel_ekle" class="menu-link">
                    <span class="menu-text">Firmalara Personel Ekle</span>
                </a>
            </li>  
            <li class="menu-item">
                <a href="/index.php?url=super_admin_firma_kurma_yardim" class="menu-link">
                    <span class="menu-text">Firma Kurulum Yardımı</span>
                </a>
            </li> 
        </ul>
    </div>
</li>

<?php }?>

<?php if(in_array(DEPO_GOR, $_SESSION['sayfa_idler'])){ ?>
    <li class="menu-item">
        <a href="/index.php?url=stok" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-package-variant-closed"></i></span>
            <span class="menu-text">Stok</span>
        </a>
    </li> 
<?php }?>

<?php if(in_array(ARSIV_GOR, $_SESSION['sayfa_idler'])){  ?>
    <li class="menu-item">
        <a href="/index.php?url=arsiv_kalem" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-ungroup"></i></span>
            <span class="menu-text"> Arşiv </span>
        </a>
    </li>   
<?php }?>

<?php if(in_array(TEDARIKCI_GOR, $_SESSION['sayfa_idler'])){  ?>
    <li class="menu-item">
        <a href="/index.php?url=tedarikci" class="menu-link">
            <span class="menu-icon"><i class="mdi mdi-nutrition"></i></span>
            <span class="menu-text"> Tedarikçi </span>
        </a>
    </li>   
<?php }?>

<li class="menu-item">
    <a href="/index.php?url=fason" class="menu-link">
        <span class="menu-icon"><i class="mdi mdi-collage"></i></span>
        <span class="menu-text"> Fason </span>
    </a>
</li>
  
<li class="menu-item">
    <a href="#menuPlanlama" data-bs-toggle="collapse" class="menu-link">
        <span class="menu-icon"><i class="mdi mdi-camera-rear-variant"></i></span>
        <span class="menu-text"> Planlama </span>
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse" id="menuPlanlama">
        <ul class="sub-menu">
            <?php if(in_array(PLANLAMA, $_SESSION['sayfa_idler'])){  ?>
                <li class="menu-item">
                    <a href="/index.php?url=planlama" class="menu-link">
                        <span class="menu-text">Planlama Kontrol</span>
                    </a>
                </li>
            <?php } ?>  
            <?php if( in_array(URETIM_KONTROL, $_SESSION['sayfa_idler']) ){ ?>
            <li class="menu-item">
                <a href="/index.php?url=uretim_kontrol" class="menu-link">
                    <span class="menu-text">Üretim Kontrol</span>
                </a>
            </li>  
            <?php } ?>
            <?php if(in_array(MAKINA_IS_PLANI, $_SESSION['sayfa_idler'])){  ?>
            <li class="menu-item">
                <a href="/index.php?url=makina_is_planlama" class="menu-link">
                    <span class="menu-text">Makina Planlama</span>
                </a>
            </li> 
            <?php }?>
        </ul>
    </div>
</li>

<li class="menu-item">
    <a href="#menuRapor" data-bs-toggle="collapse" class="menu-link">
        <span class="menu-icon"><i class="mdi mdi-equal-box"></i></span>
        <span class="menu-text"> Raporlar </span>
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse" id="menuRapor">
        <ul class="sub-menu">
            <?php if(in_array(RAPORLAR, $_SESSION['sayfa_idler'])){  ?>
                <li class="menu-item">
                    <a href="/index.php?url=rapor_siparisler" class="menu-link">
                        <span class="menu-text">Siparis Raporu</span>
                    </a>
                </li>
            <?php } ?>
            <li class="menu-item">
                <a href="/index.php?url=giris_loglari" class="menu-link">
                    <span class="menu-text">Giriş Logları</span>
            <li class="menu-item">
                <a href="/index.php?url=raporlar" class="menu-link">
                    <span class="menu-text"><i class="fa-solid fa-file-excel"></i> Excel Raporları</span>
                </a>
            <li class="menu-item">
                <a href="/index.php?url=rapor_ayarlari" class="menu-link">
                    <span class="menu-text"><i class="mdi mdi-cog"></i> Rapor Ayarları</span>
                </a>
            </li>
                </a>
            </li>   
        </ul>
    </div>
</li>

<?php if(in_array(DEPO, $_SESSION['sayfa_idler'])){  ?>
    <li class="menu-item">
    <a href="/index.php?url=depo" class="menu-link">
        <span class="menu-icon"><i class="mdi mdi-package-variant"></i></span>
        <span class="menu-text"> Depo </span>
    </a>
</li>
<?php }?>

<?php if( $_SESSION['yetki_id'] == SUPER_ADMIN_YETKI_ID ){  ?>
<li class="menu-item">
    <a href="/index.php?url=hata_loglari" class="menu-link">
        <span class="menu-icon"><i class="mdi mdi-bug"></i></span>
        <span class="menu-text"> Hatalar </span>
    </a>
</li>
<?php } ?>

</ul>
<!--- End Menu -->
