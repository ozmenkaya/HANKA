<?php
    ob_start();

    // Mikrofon ve kamera izni için Permissions-Policy header
    header("Permissions-Policy: microphone=*, camera=*");

    // Güvenli oturum kontrolü ve veritabanı bağlantısı
    // LOCAL DEVELOPMENT: db_local.php kullan
    if (file_exists("include/db_local.php")) {
        require_once "include/db_local.php";
    } else {
        require_once "include/db.php";
    }
    require_once "include/oturum_kontrol.php";

    // Akıllı sayfa tespiti - parametrelere göre otomatik yönlendirme
    if (!isset($_GET['url']) && isset($_GET['musteri_id'])) {
        // musteri_id varsa ve url yoksa, müşteri detay sayfasına yönlendir
        $_GET['url'] = 'musteri_guncelle';
    }

    // AJAX endpoint kontrolü - db_islem dosyaları için HTML wrapper olmadan direkt çalıştır
    $page = isset($_GET['url']) ? basename($_GET['url']) : 'home';
    $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
    
    // AJAX endpoint'leri - HTML wrapper olmadan direkt çalıştır
    $ajax_endpoints = ['_db_islem', '_data', 'siparis_durum_guncelle'];
    foreach ($ajax_endpoints as $endpoint) {
        if (strpos($page, $endpoint) !== false || $page === $endpoint) {
            $file = $page . ".php";
            if (file_exists($file)) {
                include($file);
                exit;
            }
        }
    }

    // Güvenli bir şekilde $_SESSION değişkenlerini kontrol etme
    $logo = isset($_SESSION['logo']) ? htmlspecialchars($_SESSION['logo'], ENT_QUOTES, 'UTF-8') : 'default-logo.png';
    $firma_adi = isset($_SESSION['firma_adi']) ? htmlspecialchars($_SESSION['firma_adi'], ENT_QUOTES, 'UTF-8') : 'Hanka Sys SAAS';
?>
<!DOCTYPE html>
<html lang="en"> 
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Permissions-Policy" content="microphone=*, camera=*">
        <title><?php echo htmlspecialchars($firma_adi, ENT_QUOTES, 'UTF-8'); ?> - Hanka Sys SAAS</title>
        <meta content="Hanka Sys SAAS" name="description" />
        <meta content="Hanka Sys SAAS" name="author" />

        <!-- Favicon ve Logo -->
        <link rel="shortcut icon" href="dosyalar/logo/<?php echo $logo; ?>" type="image/x-icon">
        <link rel="apple-touch-icon" href="dosyalar/logo/<?php echo $logo; ?>" type="image/png">
        <meta property="og:image" content="dosyalar/logo/<?php echo $logo; ?>">

        <!-- Bootstrap Tables css -->
        <link href="assets/libs/bootstrap-table/bootstrap-table.min.css" rel="stylesheet" type="text/css" />

        <!-- Plugins css -->
        <link href="assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/libs/selectize/css/selectize.bootstrap3.css" rel="stylesheet" type="text/css" />

        <!-- Theme Config Js -->
        <script src="assets/js/head.js"></script>

        <!-- Bootstrap css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" id="app-style" />

        <!-- App css -->
        <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />

        <!-- Icons css -->
        <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
 
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
        
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="assets/datatables/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="assets/datatables/css/responsive.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/css/buttons.dataTables.min.css">

        <!-- Custom CSS -->
        <link rel="stylesheet" href="css/lightbox.min.css">
        <link rel="stylesheet" href="css/dashboard.css">
        <link rel="stylesheet" href="css/tab-page.css">
        
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
        <!-- Select2 Bootstrap 5 Theme -->
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

        <!-- Custom Styles -->
        <style>
            .adres-item .form-group > label {
                color: #202223bf;
            }

            .adres-item .form-floating > label {
                padding: 0.40rem 0.75rem;
                z-index: 99;
                color: #202223bf;
                font-size: 0.80rem;
            }

            .adres-item .select2-container--bootstrap-5 .select2-selection {
                width: 100%;
                min-height: calc(2.2em + .75rem + 2px) !important;
            }

            .adres-item .select2-container--bootstrap-5 .select2-selection--single {
                padding: 1.2rem 2rem .375rem 0.5rem !important;
            }

            .adres-item .form-control {
                max-height: 51px !important;
                padding: 15px 0 0 8px !important;
            }

            .adres-item .form-floating > .form-control-plaintext ~ label,
            .adres-item .form-floating > .form-control:focus ~ label,
            .adres-item .form-floating > .form-control:not(:placeholder-shown) ~ label,
            .adres-item .form-floating > .form-select ~ label {
                opacity: .65;
                transform: none;
            }

            .form-floating > .form-select-plaintext ~ label,
            .form-floating > .form-select:not(:placeholder-shown) ~ label,
            .form-floating > .form-select ~ label {
                transform: scale(.85) translateY(-.7rem) translateX(.15rem);
            }

            .form-floating > .form-select ~ label {
                z-index: 999;
                top: 0;
                left: 5px;
            }

            .select2-container--bootstrap-5 .select2-selection--single {
                padding: 1.4rem 2.25rem .5rem .6rem !important;
                height: 56px !important;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
        <!-- <script src="assets/dist/js/bootstrap.bundle.min.js"></script> -->
        
        <script>
            let log = console.log;
            let error = console.error;
            let table = console.table;
            const durum = "<?php echo isset($_SESSION['durum'])  ? $_SESSION['durum'] :  ''; ?>"
            
            $(function () {

                let isMobile = window.matchMedia("only screen and (max-width: 760px)").matches;

                if (isMobile) {
                    $("#sidebarMenu").removeClass('sidebar')
                }

                console.log("%c##  ##        ####       ###      ## ##   ##      ####           ######  ",'color:red;font-size:16px;font-style: italic;')
                console.log("%c##  ##       ##  ##      ## ##    ## ##  ##      ##  ##          ##      ",'color:red;font-size:16px;font-style: italic;')
                console.log("%c##  ##      ##    ##     ##  ##   ## ####       ##    ##         ######      ",'color:red;font-size:16px;font-style: italic;')
                console.log("%c######     ##########    ##   ##  ## ####      ##########        ######  ",'color:red;font-size:16px;font-style: italic;')
                console.log("%c##  ##    ##        ##   ##    ## ## ##  ##   ##        ##           ##  ",'color:red;font-size:16px;font-style: italic;')
                console.log("%c##  ##   ##          ##  ##     #### ##   ## ##          ##      ######  ",'color:red;font-size:16px;font-style: italic;')
 
                
                // Order by the grouping
                $('#example tbody').on('click', 'tr.group', function () {
                    var currentOrder = table.order()[0];
                    if (currentOrder[0] === 2 && currentOrder[1] === 'asc') {
                        table.order([2, 'desc']).draw();
                    } else {
                        table.order([2, 'asc']).draw();
                    }
                });
                           
                $('.buttons-copy, .buttons-csv, .buttons-print, .buttons-pdf, .buttons-excel').addClass('btn btn-primary me-1');

                if(durum != ''){
                    $.notify(
                        "<?php echo isset($_SESSION['mesaj']) ? $_SESSION['mesaj'] : ''?>",
                        "<?php echo isset($_SESSION['durum']) ? $_SESSION['durum'] : ''?>"
                    );
                }
            });

        </script>

        <script>
            const tooltipList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const modalList = document.querySelectorAll('[data-bs-toggle="modal"]');
            [...tooltipList, ...modalList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
        </script>
        <!-- Mobile Responsive CSS -->
        <link href="/css/mobile-responsive.css" rel="stylesheet" type="text/css" />
        <link href="/css/bildirim.css" rel="stylesheet" type="text/css" />
    </head>

    <body>
        <!-- Begin page -->
        <div id="wrapper">
            <?php  
               // $page zaten yukarıda tanımlandı (AJAX endpoint kontrolü için)

               if($_SESSION['yetki_id'] == URETIM_YETKI_ID and $page === 'home' and $page !== 'login_kontrol' ){ 
                $page = "makina_listesi";
              }

               If ($page !== 'makina_is_ekran' && $page !== 'makina_listesi' && $page !== 'makina_is_listesi' && $page !== 'makina_is_listesi_basit')  {
            ?>
            <!-- ========== Menu ========== -->
            <div class="app-menu">
                <!-- Brand Logo -->
                <div class="logo-box">
                    <!-- Brand Logo Light -->
                    <!-- <a href="index.html" class="logo-light">
                        <img src="dosyalar/logo/<?php echo $logo; ?>" alt="logo" class="logo-lg">
                        <img src="dosyalar/logo/<?php echo $logo; ?>" alt="small logo" class="logo-sm">
                    </a> -->

                    <!-- Brand Logo Dark -->
                    <a href="/" class="logo-dark">
                        <img src="dosyalar/logo/<?php echo $logo; ?>" alt="dark logo" class="logo-lg" style="margin-right:3px;width:50px;height:50px">
                        <?php echo $firma_adi; ?>
                    </a>
                </div>

                <!-- menu-left -->
                <div class="scrollbar"> 
                    <div class="user-box text-center">
                        <img src="assets/images/users/user-1.jpg" alt="user-img" title="Mat Helme" class="rounded-circle avatar-md">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle h5 mb-1 d-block" data-bs-toggle="dropdown">Geneva Kennedy</a>
                            <div class="dropdown-menu user-pro-dropdown">
                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <i class="fe-user me-1"></i>
                                    <span>My Account</span>
                                </a>
                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <i class="fe-settings me-1"></i>
                                    <span>Settings</span>
                                </a>
                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <i class="fe-lock me-1"></i>
                                    <span>Lock Screen</span>
                                </a>
                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <i class="fe-log-out me-1"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Admin Head</p>
                    </div>
                    
                    <!-- Sol Menü -->
                    <?php  
                          require_once "include/sol_menu.php";                
                    ?>

                    <div class="clearfix"></div>
                </div>
            </div>
            <?php } ?>
            <!-- ========== Left menu End ========== -->

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->
            <div class="content-page">
                <!-- ========== Topbar Start ========== -->
                <?php 
                
                    If ($page !== 'makina_is_ekran' && $page !== 'makina_listesi' && $page !== 'makina_is_listesi' && $page !== 'makina_is_listesi_basit') {
                        require_once "include/header.php";
                    } 

                ?>
                <!-- ========== Topbar End ========== -->

                <div class="content">
                    <!-- Start Content-->
                    <div class="container-fluid">
                        <?php
                        // Güvenli bir şekilde URL parametresini al
                        //$page = isset($_GET['url']) ? basename($_GET['url']) : 'home';
 
                        $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page); // Güvenlik için temizleme

                        $file = $page . ".php";

                        if (file_exists($file)) {
                            include($file);
                        } else {
                            include("404.php");
                        }
                        ?>
                    </div> <!-- container -->
                </div> <!-- content -->

                <!-- Footer Start --> 
                <!-- end Footer -->
            </div>
            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->

        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>

        <!-- App js -->
        <script src="assets/js/app.min.js"></script>

        <!-- Bootstrap Tables js -->
        <script src="assets/libs/bootstrap-table/bootstrap-table.min.js"></script>

        <!-- Init js -->
        <script src="assets/js/pages/bootstrap-tables.init.js"></script>
        <script src="assets/js/pages/bootstrap-table-tr-TR.min.js"></script>

        <!-- Plugins js-->
        <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
        <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
        <script src="assets/libs/selectize/js/standalone/selectize.min.js"></script>

        <!-- Dashboar 1 init js-->
        <?php if ($page == 'home' || $page == 'dashboard') { ?>
        <script src="assets/js/pages/dashboard-1.init.js"></script>
        <?php } ?>

        <!-- This is data table -->
        <script src="assets/datatables/js/jquery.dataTables.min.js"></script>
        <script src="assets/datatables/js/dataTables.bootstrap5.min.js"></script>
        <script src="assets/datatables/js/dataTables.responsive.min.js"></script>
        <script src="assets/datatables/js/responsive.bootstrap5.min.js"></script>

        <!-- DataTables Buttons Extension -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/js/buttons.print.min.js"></script>
       
        <script src="assets/dist/js/toastr.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
        
        <script src="js/lightbox.min.js"></script>
        <script src="js/notify.js"></script> 

        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
 
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

        <script src="js/site.js"></script> 

        <script>
            $(document).ready(function() {
                // Select2 için gerekli ayarlar
                $('.js-example-basic-single').select2({
                    theme: 'bootstrap-5'
                });
                $('.form-select').select2({
                    theme: 'bootstrap-5'
                }); 

                lightbox.option({
                    'resizeDuration'                :   200,
                    'wrapAround'                    :   true,
                    'alwaysShowNavOnTouchDevices'   :   true,
                    'disableScrolling'              :   true,
                    'imageFadeDuration'             :   200,
                    'fadeDuration'                  :   200,
                    'albumLabel'                    :   'Resim %1/%2'
                });

                $('#myTable, .table-data').DataTable({
                    "displayLength": 50,
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });

                var table = $('#example, .table-data').DataTable({
                    "columnDefs": [{
                        "visible": false,
                        "targets": 2
                    }],
                    "order": [
                        [2, 'asc']
                    ],
                    "displayLength": 25,
                    "drawCallback": function (settings) {
                        var api = this.api();
                        var rows = api.rows({
                            page: 'current'
                        }).nodes();
                        var last = null;
                        api.column(2, {
                            page: 'current'
                        }).data().each(function (group, i) {
                            if (last !== group) {
                                $(rows).eq(i).before('<tr class="group"><td colspan="5">' + group + '</td></tr>');
                                last = group;
                            }
                        });
                    }
                });

                // responsive table
                $('#config-table').DataTable({
                    responsive: true
                });
                $('#example23').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            });
        </script> 

    <!-- Mobile Menu Script -->
    <script src="/js/mobile-menu.js"></script>
    <script src="/js/bildirim.js"></script>
    </body>

</html>