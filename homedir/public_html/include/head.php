    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css" >
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" >
    <link rel="stylesheet" href="assets/node_modules/datatables.net-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="assets/node_modules/datatables.net-bs4/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="css/lightbox.min.css">
    <link rel="stylesheet" href="css/dashboard.css" >
    <link rel="stylesheet" href="css/tab-page.css" >
    <?php $logo =  $_SESSION['logo']; ?>
    <link rel="shortcut icon"       href="dosyalar/logo/<?php echo $logo;?>" type="image/x-icon" >
    <link rel="apple-touch-icon"    href="dosyalar/logo/<?php echo $logo;?>" type="image/png" >
    <meta property="og:image" content="dosyalar/logo/<?php echo $logo;?>">

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

    <!-- 
    <meta name="description" content="An Express-inspired web framework written in Go.">
    <meta property="og:title" content="Fiber">
    <meta property="og:description" content="An Express-inspired web framework written in Go.">
    <meta property="og:site_name" content="Fiber">
    <meta property="og:url" content="https://gofiber.io">
    <meta content="website" property="og:type">
    -->
    <style type="text/css">
    .adres-item .form-group>label { color: #202223bf; }

    .adres-item .form-floating>label {
        padding: 0.40rem 0.75rem;
        z-index: 99;
        color: #202223bf;
        font-size: 0.80rem;
    }

    .adres-item .select2-container--bootstrap-5 .select2-selection {
        width: 100%;
        min-height: calc(2.2em + .75rem + 2px)!important;
    }
    .adres-item .select2-container--bootstrap-5 .select2-selection--single {
        padding: 1.2rem 2rem .375rem 0.5rem!important;
    }
    .adres-item .form-control {
        max-height: 51px!important;
        padding: 15px 0 0 8px!important;
    }
    .adres-item .form-floating>.form-control-plaintext~label, .adres-item .form-floating>.form-control:focus~label, .adres-item .form-floating> .adres-item .form-control:not(:placeholder-shown)~label, .adres-item .form-floating>.form-select~label {
        opacity: .65;
        transform: none;
    }

    .form-floating>.form-select-plaintext~label, .form-floating>.form-select:not(:placeholder-shown)~label, .form-floating>.form-select~label {
        transform: scale(.85) translateY(-.7rem) translateX(.15rem);
    }
    .form-floating>.form-select~label {
        z-index: 999;
        top: 0;
        left: 5px;
    }
    .select2-container--bootstrap-5 .select2-selection--single {
        padding: 1.4rem 2.25rem .5rem .6rem!important;
        height: 56px !important;
    }
    </style>