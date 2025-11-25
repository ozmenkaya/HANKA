$(document).ready(function() {
    const $modalDialog = $('#etiketModal .modal-dialog');
    const $modalContent = $('#etiketModal .modal-content');
    const $modalBody = $('#etiketModal .modal-body');
 
    const modalWidthCm = 15;
    const modalWidthPx = modalWidthCm * 37.8;
    $modalDialog.css({ width: modalWidthCm + 'cm', 'min-width': modalWidthCm + 'cm' });
    $modalContent.css({ width: modalWidthCm + 'cm' });
    $modalBody.css({ width: modalWidthCm + 'cm' });
 
    function updateScaling() {
        const widthCm = parseFloat($('#labelWidth').val()) || 11;
        const heightCm = parseFloat($('#labelHeight').val()) || 8;
        const $labelContent = $('#label-content');
 
        $labelContent.css({
            width: widthCm + 'cm',
            height: heightCm + 'cm'
        });

        $labelContent.css({
            '--label-width': widthCm + 'cm',
            '--label-height': heightCm + 'cm'
        });

        const extraPaddingPx = 60;
        const extraPaddingCm = extraPaddingPx / 37.8;
        const newModalWidthCm = Math.max(modalWidthCm, widthCm + extraPaddingCm);
        $modalDialog.css({
            width: newModalWidthCm + 'cm',
            'min-width': modalWidthCm + 'cm'
        });
        $modalContent.css({
            width: newModalWidthCm + 'cm'
        });
        $modalBody.css({
            width: newModalWidthCm + 'cm'
        });
 
        const scaleX = widthCm / 11;
        const scaleY = heightCm / 8;
        const scale = Math.min(scaleX, scaleY);

        $labelContent.find('.label-table th, .label-table td').css({
            'font-size': (12 * scale) + 'px',  
            'line-height': (14 * scale) + 'px'  
        });

        $labelContent.find('.label-header').css({
            'font-size': (18 * scale) + 'px'  
        });
    }
 
    updateScaling(); 

    $('#labelWidth, #labelHeight').on('input', function() {
        updateScaling();
    });

    $('#boxQuantity').on('input', function() {
        const quantity = $(this).val();
        $('#boxQuantityValue').text(quantity || '0');
    });

    $('#etiketYazdir').click(function() {
        const $labelContent = $('#label-content');
        const widthCm = parseFloat($('#labelWidth').val()) || 11;
        const heightCm = parseFloat($('#labelHeight').val()) || 8;
 
        updateScaling();

        $labelContent.css({
            '--label-width': widthCm + 'cm',
            '--label-height': heightCm + 'cm',
            width: widthCm + 'cm !important',
            height: heightCm + 'cm !important'
        });

        $labelContent.find('.label-table').css({
            width: '100% !important',
            'table-layout': 'fixed !important'
        });

        window.print();
    });

    $('#saveAsPdf').click(function() {
        const widthCm = parseFloat($('#labelWidth').val());
        const heightCm = parseFloat($('#labelHeight').val());

        updateScaling();

        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = String(today.getFullYear()).slice(-2);
        const dateString = `${day}${month}${year}`;

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            orientation: widthCm > heightCm ? 'landscape' : 'portrait',
            unit: 'mm',
            format: [widthCm * 10, heightCm * 10] 
        });

        html2canvas(document.querySelector('#label-content'), {
            scale: 2,
            useCORS: true
        }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = widthCm * 10;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            doc.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
            doc.save(`Etiket_${dateString}.pdf`);
        });
    });

    /* Birimler Modal Başlangıç */
            $('#openUnitModal').click(function() {
                $('#unitModal').modal('show');
                $('#searchInputUnit').val(''); // Modal açıldığında arama kutusunu temizle
                $('.unit-row').show(); // Tüm satırları göster
            });

            // Tablo satırına tıklayınca
            $('.unit-row').click(function() {
                // ID'yi al
                var unitId = $(this).data('id');
                // Textbox'a ID'yi yaz
                $('#olcuBirimi').val(unitId);
                // Modalı kapat
                $('#unitModal').modal('hide');
            });

            // Arama kutusu ile filtreleme
            $('#searchInputUnit').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#unitTable tbody tr').filter(function() {
                    $(this).toggle(
                        $(this).find('td:eq(1)').text().toLowerCase().indexOf(value) > -1 || // Ölçü birimi adına göre
                        $(this).find('td:eq(2)').text().toLowerCase().indexOf(value) > -1    // Açıklamaya göre
                    );
                });
            });

    /* Birimler Modal Bitiş */
    /* Stok Türü Modal Başlangıç */
    $('#openStokTuruModal').click(function() {
        $('#stokTuruModal').modal('show');
        $('#searchInputStokTuru').val(''); // Modal açıldığında arama kutusunu temizle
        $('.stok-turu-row').show(); // Tüm satırları göster
    });

    // Tablo satırına tıklayınca
    $('.stok-turu-row').click(function() {
        // ID'yi al
        var stokTuruId = $(this).data('id');
        // Textbox'a ID'yi yaz
        $('#stokTuru').val(stokTuruId);
        // Modalı kapat
        $('#stokTuruModal').modal('hide');
    });

    // Arama kutusu ile filtreleme
    $('#searchInputStokTuru').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#stokTuruTable tbody tr').filter(function() {
            $(this).toggle(
                $(this).find('td:eq(1)').text().toLowerCase().indexOf(value) > -1 || 
                $(this).find('td:eq(2)').text().toLowerCase().indexOf(value) > -1  
            );
        });
    });

    depoSec = "";

    /* Stok Türü Modal Bitiş */        
    /* Depo Modal Başlangıç */
    $('#openGirisDepoModal').click(function() {
        $('#depoModal').modal('show');
        $('#searchInputDepo').val(''); 
        $('.depo-row').show();

        depoSec = "girisDepo";
    });

    $('#openCikisDepoModal').click(function() {
        $('#depoModal').modal('show');
        $('#searchInputDepo').val(''); 
        $('.depo-row').show();

        depoSec = "cikisDepo";
    });

    // Tablo satırına tıklayınca
    $('.depo-row').click(function() {
        // ID'yi al
        var depoId = $(this).data('id');
        // Textbox'a ID'yi yaz

        if (depoSec == "girisDepo") {
            $('#girisDepo').val(depoId);
        }else{
            $('#cikisDepo').val(depoId);
        }

        // Modalı kapat
        $('#depoModal').modal('hide');
    });

    // Arama kutusu ile filtreleme
    $('#searchInputDepo').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#depoTable tbody tr').filter(function() {
            $(this).toggle(
                $(this).find('td:eq(1)').text().toLowerCase().indexOf(value) > -1 || 
                $(this).find('td:eq(2)').text().toLowerCase().indexOf(value) > -1  
            );
        });
    });

    /* Depo Modal Bitiş */     
    /* Para Birimi Modal Başlangıç */

    paraBirimiSec = "";

    $('#opensPb1Modal').click(function() {
        $('#pbModal').modal('show');
        $('#searchInputPb').val(''); 
        $('.pb-row').show();

        paraBirimiSec = "spb1";
    });

    $('#opensPb2Modal').click(function() {
        $('#pbModal').modal('show');
        $('#searchInputPb').val(''); 
        $('.pb-row').show();

        paraBirimiSec = "spb2";
    });

    $('#opensPb3Modal').click(function() {
        $('#pbModal').modal('show');
        $('#searchInputPb').val(''); 
        $('.pb-row').show();

        paraBirimiSec = "spb3";
    });

    $('#openaPb1Modal').click(function() {
        $('#pbModal').modal('show');
        $('#searchInputPb').val(''); 
        $('.pb-row').show();

        paraBirimiSec = "apb1";
    });

    $('#openaPb2Modal').click(function() {
        $('#pbModal').modal('show');
        $('#searchInputPb').val(''); 
        $('.pb-row').show();

        paraBirimiSec = "apb2";
    });

    $('#openaPb3Modal').click(function() {
        $('#pbModal').modal('show');
        $('#searchInputPb').val(''); 
        $('.pb-row').show();

        paraBirimiSec = "apb3";
    });

    // Tablo satırına tıklayınca
    $('.pb-row').click(function() {
        // ID'yi al
        var pbId = $(this).data('id');
        // Textbox'a ID'yi yaz

        if (paraBirimiSec == "spb1") {
            $('#spb1').val(pbId);
        }else if (paraBirimiSec == "spb2"){
            $('#spb2').val(pbId);
        }else if (paraBirimiSec == "spb3"){
            $('#spb3').val(pbId);
        }else if (paraBirimiSec == "apb1"){
            $('#apb1').val(pbId);
        }else if (paraBirimiSec == "apb2"){
            $('#apb2').val(pbId);
        }else if (paraBirimiSec == "apb3"){
            $('#apb3').val(pbId);
        }
        else{

        }

        // Modalı kapat
        $('#pbModal').modal('hide');
    });

    // Arama kutusu ile filtreleme
    $('#searchInputPb').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#pbTable tbody tr').filter(function() {
            $(this).toggle(
                $(this).find('td:eq(1)').text().toLowerCase().indexOf(value) > -1 || 
                $(this).find('td:eq(2)').text().toLowerCase().indexOf(value) > -1  
            );
        });
    });

    /* Para Birimi Modal Bitiş */  
    /* Tedarikçi Modal Başlangıç */

    tedarikciSec = "";

    $('#opensCariModal').click(function() {
        $('#tedarikciModal').modal('show');
        $('#searchInputPb').val(''); 
        $('.tedarikci-row').show();

        tedarikciSec = "scari";
    });

    $('#openUreticiModal').click(function() {
        $('#tedarikciModal').modal('show');
        $('#searchInputTedarikci').val(''); 
        $('.tedarikci-row').show();

        tedarikciSec = "uretici";
    });
  
    // Tablo satırına tıklayınca
    $('.tedarikci-row').click(function() {
        // ID'yi al
        var tedarikciId = $(this).data('id');
        // Textbox'a ID'yi yaz

        if (tedarikciSec == "scari") {
            $('#cariKodu').val(tedarikciId);
        }else if (tedarikciSec == "uretici"){
            $('#ureticiKodu').val(tedarikciId);
        }else{

        }

        // Modalı kapat
        $('#tedarikciModal').modal('hide');
    });

    // Arama kutusu ile filtreleme
    $('#searchInputTedarikci').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#tedarikciTable tbody tr').filter(function() {
            $(this).toggle(
                $(this).find('td:eq(1)').text().toLowerCase().indexOf(value) > -1 || 
                $(this).find('td:eq(2)').text().toLowerCase().indexOf(value) > -1  
            );
        });
    });

    /* Tedarikçi Modal Bitiş */  
    /* Grup Kodu Modal Başlangıç */

    $('#openGrupKoduModal').click(function() {
        $('#grupModal').modal('show');
        $('#searchInputGrupKodu').val(''); 
        $('.grup-kodu-row').show();
    });

    // Tablo satırına tıklayınca
    $('.grup-kodu-row').click(function() {
        // ID'yi al
        var id = $(this).data('id');
        // Textbox'a ID'yi yaz

        $('#grupKodu').val(id);
        
        // Modalı kapat
        $('#grupModal').modal('hide');
    });

    // Arama kutusu ile filtreleme
    $('#searchInputGrupKodu').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#grupKoduTable tbody tr').filter(function() {
            $(this).toggle(
                $(this).find('td:eq(1)').text().toLowerCase().indexOf(value) > -1 || 
                $(this).find('td:eq(2)').text().toLowerCase().indexOf(value) > -1  
            );
        });
    });

    /* Grup Kodu Modal Bitiş */  
    /* Kod Modal Başlangıç */

    kodSec = "";

    $('#openKod1Modal').click(function() {
        $('#kod1Modal').modal('show');
        $('#searchInputKod').val(''); 
        $('.kod-row').show();

        kodSec = "kod1";
    });

    $('#openKod2Modal').click(function() {
        $('#kod2Modal').modal('show');
        $('#searchInputKod').val(''); 
        $('.kod-row').show();

        kodSec = "kod2";
    });

    $('#openKod3Modal').click(function() {
        $('#kod3Modal').modal('show');
        $('#searchInputKod').val(''); 
        $('.kod-row').show();

        kodSec = "kod3";
    });

    $('#openKod4Modal').click(function() {
        $('#kod4Modal').modal('show');
        $('#searchInputKod').val(''); 
        $('.kod-row').show();

        kodSec = "kod4";
    });

    $('#openKod5Modal').click(function() {
        $('#kod5Modal').modal('show');
        $('#searchInputKod').val(''); 
        $('.kod-row').show();

        kodSec = "kod5";
    });
  
    // Tablo satırına tıklayınca
    $('.kod-row').click(function() {
        // ID'yi al
        var id = $(this).data('id');
        // Textbox'a ID'yi yaz

        if (kodSec == "kod1") {
            $('#kod1').val(id);
        }else if (kodSec == "kod2"){
            $('#kod2').val(id);
        }else if (kodSec == "kod3"){
            $('#kod3').val(id);
        }else if (kodSec == "kod4"){
            $('#kod4').val(id);
        }else if (kodSec == "kod5"){
            $('#kod5').val(id);
        }else{

        }

        // Modalı kapat
        $('#kodModal').modal('hide');
    });

    // Arama kutusu ile filtreleme
    $('#searchInputKod').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#kodTable tbody tr').filter(function() {
            $(this).toggle(
                $(this).find('td:eq(1)').text().toLowerCase().indexOf(value) > -1 || 
                $(this).find('td:eq(2)').text().toLowerCase().indexOf(value) > -1  
            );
        });
    });

    /* Kod Modal Bitiş */  
    $('#durum').on('change', function() {
        $('#durumText').text($(this).is(':checked') ? 'Aktif' : 'Pasif');
    });

    /* Stok Modal Başlngıç */
    function updateStokScaling() {
        const $stokModalDialog  = $('#stokEtiketModal .modal-dialog');
        const $stokModalContent = $('#stokEtiketModal .modal-content');
        const $stokModalBody    = $('#stokEtiketModal .modal-body');
    
        const stokModalWidthCm = 15;
        const stokModalWidthPx = stokModalWidthCm * 37.8;
        $stokModalDialog.css({ width: stokModalWidthCm + 'cm', 'min-width': stokModalWidthCm + 'cm' });
        $stokModalContent.css({ width: stokModalWidthCm + 'cm' });
        $stokModalBody.css({ width: stokModalWidthCm + 'cm' });

        const widthCm = parseFloat($('#stokLabelWidth').val()) || 11;
        const heightCm = parseFloat($('#stokLabelHeight').val()) || 8;
        const $labelContent = $('#stok-label-content');
 
        $labelContent.css({
            width: widthCm + 'cm',
            height: heightCm + 'cm'
        });

        $labelContent.css({
            '--label-width': widthCm + 'cm',
            '--label-height': heightCm + 'cm'
        });

        const extraPaddingPx = 60;
        const extraPaddingCm = extraPaddingPx / 37.8;
        const newModalWidthCm = Math.max(stokModalWidthCm, widthCm + extraPaddingCm);
        $stokModalDialog.css({
            width: newModalWidthCm + 'cm',
            'min-width': stokModalWidthCm + 'cm'
        });
        $stokModalContent.css({
            width: newModalWidthCm + 'cm'
        });
        $stokModalBody.css({
            width: newModalWidthCm + 'cm'
        });
 
        const scaleX = widthCm / 11;
        const scaleY = heightCm / 8;
        const scale = Math.min(scaleX, scaleY);

        $labelContent.find('.label-table th, .label-table td').css({
            'font-size': (12 * scale) + 'px',  
            'line-height': (14 * scale) + 'px'  
        });

        $labelContent.find('.label-header').css({
            'font-size': (18 * scale) + 'px'  
        });
    }
 
    updateStokScaling(); 

    $('#stokLabelWidth, #stokLabelHeight').on('input', function() {
        updateStokScaling();
    });

    $('#stokBoxQuantity').on('input', function() {
        const quantity = $(this).val();
        $('#stokBoxQuantityValue').text(quantity || '0');
    });

    $('#stokDate').on('change', function() {
        let tarih = $(this).val(); 
        if (tarih) {
            let date = new Date(tarih);
            let formattedDate = date.toLocaleDateString('tr-TR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }).split('.').join('.'); 
            $('#stokDateValue').text(formattedDate);
        } else {
            $('#stokDateValue').text('');
        }
    });

    $('#stokEtiketModalButton').click(function() {
        tarih = $(this).data('tarih');
        $('#stokDateValue').text(tarih);

        stokKodu = $(this).data('stok-kodu');
        $('#stokKodu').text(stokKodu);

        supplier = $(this).data('supplier');
        $('#stokSupplier').text(supplier);

        adet = $(this).data('adet');
        $('#stokBoxQuantityValue').text(adet);

    });  

    $('#stokEtiketYazdir').click(function() {
        const widthCm  = parseFloat($('#stokLabelWidth').val()) || 11;
        const heightCm = parseFloat($('#stokLabelHeight').val()) || 8;
        const widthMm = widthCm * 10;
        const heightMm = heightCm * 10;
 
        updateStokScaling();

        // Etiket içeriğini klonla
        const labelContent = $('#stok-label-content').clone();
        
        // Yeni pencere aç ve içeriği yazdır
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Etiket Yazdır</title>
                <style>
                    @page {
                        size: ${widthMm}mm ${heightMm}mm;
                        margin: 0;
                    }
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    html, body {
                        width: ${widthCm}cm;
                        height: ${heightCm}cm;
                        margin: 0;
                        padding: 0;
                        overflow: hidden;
                    }
                    
                    body {
                        font-family: Arial, sans-serif;
                    }
                    
                    #stok-label-content {
                        width: ${widthCm}cm !important;
                        height: ${heightCm}cm !important;
                        margin: 0 !important;
                        padding: 10px;
                        border: 1px solid #000;
                        box-sizing: border-box;
                        page-break-inside: avoid;
                        page-break-after: avoid;
                    }
                    
                    .label-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 5px;
                        border-bottom: 2px solid #000;
                        padding-bottom: 5px;
                    }
                    
                    .label-table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 10px;
                    }
                    
                    .label-table th,
                    .label-table td {
                        border: 1px solid #000;
                        padding: 3px;
                        text-align: left;
                    }
                    
                    .label-table th {
                        background-color: #f0f0f0;
                        font-weight: bold;
                        width: 35%;
                    }
                    
                    .label-table td {
                        width: 65%;
                    }
                    
                    .qr-code img {
                        width: 50px !important;
                        height: 50px !important;
                    }
                    
                    @media print {
                        html, body {
                            width: ${widthCm}cm;
                            height: ${heightCm}cm;
                            overflow: hidden;
                        }
                        
                        #stok-label-content {
                            border: 1px solid #000;
                            page-break-inside: avoid;
                            page-break-after: avoid;
                        }
                    }
                </style>
            </head>
            <body>
                ${labelContent.prop('outerHTML')}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Yazdırma işlemi
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
                setTimeout(() => {
                    printWindow.close();
                }, 500);
            }, 250);
        };
    });

    $('#stokSaveAsPdf').click(function() {
        const widthCm = parseFloat($('#stokLabelWidth').val());
        const heightCm = parseFloat($('#stokLabelHeight').val());

        updateStokScaling();

        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = String(today.getFullYear()).slice(-2);
        const dateString = `${day}${month}${year}`;

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            orientation: widthCm > heightCm ? 'landscape' : 'portrait',
            unit: 'mm',
            format: [widthCm * 10, heightCm * 10] 
        });

        html2canvas(document.querySelector('#stok-label-content'), {
            scale: 2,
            useCORS: true
        }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = widthCm * 10;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            doc.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
            doc.save(`Etiket_${dateString}.pdf`);
        });
    });
    /* Stok Modal Bitiş */  
 }); 

 