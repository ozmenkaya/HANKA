    
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!--Custom JavaScript --> 
    <!--
    <script src="dist/js/custom.min.js"></script>
    -->
    <!-- This is data table -->
    <script src="assets/datatables/js/jquery.dataTables.min.js"></script>
    <script src="assets/datatables/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/datatables/js/dataTables.responsive.min.js"></script>
    <script src="assets/datatables/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/dist/js/toastr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
    
    <script src="js/lightbox.min.js"></script>
    <script src="js/notify.js"></script>
    <!--
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js" integrity="sha384-zNy6FEbO50N+Cg5wap8IKA4M/ZnLJgzc6w2NqACZaK0u0FXfOWRRJOnQtpZun8ha" crossorigin="anonymous"></script>
    <script src="css/dashboard.js"></script>
    <script src="assets/js/jquery_input_mask.js"></script>
    -->

    

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
            // Order by the grouping
            $('#example tbody').on('click', 'tr.group', function () {
                var currentOrder = table.order()[0];
                if (currentOrder[0] === 2 && currentOrder[1] === 'asc') {
                    table.order([2, 'desc']).draw();
                } else {
                    table.order([2, 'asc']).draw();
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
    